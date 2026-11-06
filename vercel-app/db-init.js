const fs = require("node:fs");
const path = require("node:path");
const mysql = require("mysql2/promise");
const bcrypt = require("bcryptjs");

async function main() {
  const db = await mysql.createConnection({
    host: process.env.MYSQLHOST || process.env.DB_HOST,
    port: Number(process.env.MYSQLPORT || process.env.DB_PORT || 3306),
    user: process.env.MYSQLUSER || process.env.DB_USER,
    password: process.env.MYSQLPASSWORD || process.env.DB_PASSWORD,
    database: process.env.MYSQLDATABASE || process.env.MYSQL_DATABASE || process.env.DB_NAME || "hostelmsphp",
    charset: "utf8mb4"
  });
  try {
    await db.query("CREATE TABLE IF NOT EXISTS schema_migrations (id VARCHAR(100) PRIMARY KEY, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)");
    // Bring forward older databases that predate the admin username column.
    const [adminColumns] = await db.query("SHOW COLUMNS FROM admin LIKE 'username'");
    if (adminColumns.length === 0) {
      await db.query("ALTER TABLE admin ADD COLUMN username VARCHAR(255) NOT NULL DEFAULT '' AFTER id");
      await db.query("UPDATE admin SET username = SUBSTRING_INDEX(email, '@', 1) WHERE username = ''");
      console.log("Administrator username column migrated");
    }
    const bootstrapPassword = process.env.ADMIN_BOOTSTRAP_PASSWORD;
    if (bootstrapPassword) {
      const [admins] = await db.query("SELECT id FROM admin LIMIT 1");
      if (admins.length === 0) {
        const username = process.env.ADMIN_BOOTSTRAP_USERNAME || "hosteladmin";
        const email = process.env.ADMIN_BOOTSTRAP_EMAIL || "admin@hostel.local";
        const passwordHash = await bcrypt.hash(bootstrapPassword, 12);
        await db.execute("INSERT INTO admin (username, email, password, updation_date) VALUES (?, ?, ?, CURDATE())", [username, email, passwordHash]);
        console.log("Initial administrator account created");
      } else console.log("Administrator already exists; bootstrap skipped");
    }
    const [done] = await db.execute("SELECT id FROM schema_migrations WHERE id=?", ["initial-hostel-schema-v1"]);
    if (done.length) { console.log("Hostel schema already initialized"); return; }
    let source = fs.readFileSync(path.join(__dirname, "schema.sql"), "utf8");
    source = source.replace(/\/\*[\s\S]*?\*\//g, "").replace(/^\s*--.*$/gm, "");
    source = source.replace(/CREATE TABLE\s+/gi, "CREATE TABLE IF NOT EXISTS ");
    const statements = source.split(";").map(s => s.trim()).filter(s => s && !/^(SET\s+SQL_MODE|SET\s+time_zone|START TRANSACTION|COMMIT|USE\s+)/i.test(s));
    for (const statement of statements) await db.query(statement);
    await db.execute("INSERT INTO schema_migrations (id) VALUES (?)", ["initial-hostel-schema-v1"]);
    console.log("Hostel schema initialized successfully");
  } finally { await db.end(); }
}
main().catch(err => { console.error("Schema initialization failed:", err.message); process.exit(1); });
