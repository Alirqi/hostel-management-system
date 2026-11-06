const fs = require("node:fs");
const path = require("node:path");
const mysql = require("mysql2/promise");
async function main() {
 const db = await mysql.createConnection({host:process.env.MYSQLHOST||process.env.DB_HOST,port:Number(process.env.MYSQLPORT||process.env.DB_PORT||3306),user:process.env.MYSQLUSER||process.env.DB_USER,password:process.env.MYSQLPASSWORD||process.env.DB_PASSWORD,database:process.env.MYSQLDATABASE||process.env.MYSQL_DATABASE||process.env.DB_NAME||"hostelmsphp",charset:"utf8mb4"});
 try {
  await db.query("CREATE TABLE IF NOT EXISTS schema_migrations (id VARCHAR(100) PRIMARY KEY, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)");
  const [done]=await db.execute("SELECT id FROM schema_migrations WHERE id=?",["initial-hostel-schema-v1"]);
  if(done.length){console.log("Hostel schema already initialized");return;}
  let source=fs.readFileSync(path.join(__dirname,"schema.sql"),"utf8");
  source=source.replace(/\\/\\*[\\s\\S]*?\\*\\//g,"").replace(/^\\s*--.*$/gm,"");
  source=source.replace(/CREATE TABLE\\s+`/gi,"CREATE TABLE IF NOT EXISTS `");
  const statements=source.split(";").map(s=>s.trim()).filter(s=>s&&!/^(SET\\s+SQL_MODE|SET\\s+time_zone|START TRANSACTION|COMMIT|USE\\s+)/i.test(s));
  for(const statement of statements) await db.query(statement);
  await db.execute("INSERT INTO schema_migrations (id) VALUES (?)",["initial-hostel-schema-v1"]);
  console.log("Hostel schema initialized successfully");
 } finally {await db.end();}
}
main().catch(e=>{console.error("Schema initialization failed:",e.message);process.exit(1);});
