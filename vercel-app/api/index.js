const express = require("express");
const mysql = require("mysql2/promise");
const bcrypt = require("bcryptjs");
const crypto = require("node:crypto");

const app = express();
app.use(express.json({ limit: "1mb" }));
app.use(express.urlencoded({ extended: false }));

let pool;
function db() {
  if (!pool) {
    pool = mysql.createPool({
      host: process.env.DB_HOST || process.env.MYSQLHOST,
      port: Number(process.env.DB_PORT || process.env.MYSQLPORT || 3306),
      user: process.env.DB_USER || process.env.MYSQLUSER,
      password: process.env.DB_PASSWORD || process.env.MYSQLPASSWORD,
      database: process.env.DB_NAME || process.env.MYSQLDATABASE || process.env.MYSQL_DATABASE || "hostelmsphp",
      waitForConnections: true,
      connectionLimit: 5,
      queueLimit: 0,
      charset: "utf8mb4",
      enableKeepAlive: true,
      ssl: process.env.DB_SSL === "true" ? { rejectUnauthorized: true } : undefined
    });
  }
  return pool;
}
const secret = () => process.env.SESSION_SECRET;
function sign(value) {
  return crypto.createHmac("sha256", secret()).update(value).digest("base64url");
}
function setSession(res, user) {
  if (!secret() || secret().length < 32) throw new Error("SESSION_SECRET must contain at least 32 characters");
  const payload = Buffer.from(JSON.stringify({ ...user, exp: Date.now() + 7 * 86400000 })).toString("base64url");
  const token = payload + "." + sign(payload);
  res.setHeader("Set-Cookie", "hostel_session=" + token + "; Path=/; HttpOnly; SameSite=Lax; Max-Age=604800" + (process.env.NODE_ENV === "production" ? "; Secure" : ""));
}
function clearSession(res) {
  res.setHeader("Set-Cookie", "hostel_session=; Path=/; HttpOnly; SameSite=Lax; Max-Age=0" + (process.env.NODE_ENV === "production" ? "; Secure" : ""));
}
function readSession(req) {
  const raw = (req.headers.cookie || "").split(";").map(v => v.trim()).find(v => v.startsWith("hostel_session="));
  if (!raw || !secret()) return null;
  const token = decodeURIComponent(raw.slice("hostel_session=".length));
  const [payload, signature] = token.split(".");
  if (!payload || !signature) return null;
  const expected = Buffer.from(sign(payload));
  const supplied = Buffer.from(signature);
  if (expected.length !== supplied.length || !crypto.timingSafeEqual(expected, supplied)) return null;
  try {
    const data = JSON.parse(Buffer.from(payload, "base64url").toString());
    return data.exp > Date.now() ? data : null;
  } catch { return null; }
}
function auth(role) {
  return (req, res, next) => {
    const user = readSession(req);
    if (!user) return res.status(401).json({ error: "يرجى تسجيل الدخول أولاً." });
    if (role && user.role !== role) return res.status(403).json({ error: "ليس لديك صلاحية لتنفيذ هذا الإجراء." });
    req.user = user;
    next();
  };
}
const asyncRoute = fn => (req, res, next) => Promise.resolve(fn(req, res, next)).catch(next);
const bad = (res, message, status = 400) => res.status(status).json({ error: message });
const validEmail = value => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value || ""));
async function passwordMatches(input, stored) {
  if (!stored) return false;
  if (/^[a-f0-9]{32}$/i.test(stored)) return stored.toLowerCase() === crypto.createHash("md5").update(input).digest("hex");
  return bcrypt.compare(input, stored);
}
async function upgradePassword(table, id, password) {
  const hash = await bcrypt.hash(password, 12);
  await db().execute(`UPDATE ${table} SET password=? WHERE id=?`, [hash, id]);
}
app.get("/api/health", (req, res) => res.json({ ok: true, service: "hostel-management-api" }));

app.post("/api/auth/login", asyncRoute(async (req, res) => {
  const { identity, email, password, role = "student" } = req.body || {};
  const login = String(identity || email || "").trim();
  if (!login || !password || !["admin", "student"].includes(role)) return bad(res, "أدخل بيانات الدخول بشكل صحيح.");
  if (role === "student" && !validEmail(login)) return bad(res, "أدخل بريداً إلكترونياً صحيحاً.");
  const table = role === "admin" ? "admin" : "userregistration";
  const column = role === "admin" ? "(username=? OR email=?)" : "email=?";
  const params = role === "admin" ? [login, login] : [login];
  const [rows] = await db().execute(`SELECT id, username, email, password FROM ${table} WHERE ${column} LIMIT 1`, params);
  const account = rows[0];
  if (!account || !(await passwordMatches(String(password), account.password))) return bad(res, "بيانات الدخول غير صحيحة.", 401);
  if (/^[a-f0-9]{32}$/i.test(account.password)) await upgradePassword(table, account.id, String(password));
  const user = { id: account.id, role, email: account.email, username: account.username || null };
  setSession(res, user);
  if (role === "student") {
    const ip = String(req.headers["x-forwarded-for"] || req.socket.remoteAddress || req.ip || "").split(",")[0].trim();
    await db().execute("INSERT INTO userLog (userId,userEmail,userIp,city,country) VALUES (?,?,INET6_ATON(?),?,?)", [account.id, account.email, ip, "", ""]);
  } else {
    const ip = String(req.headers["x-forwarded-for"] || req.socket.remoteAddress || req.ip || "127.0.0.1").split(",")[0].trim();
    await db().execute("INSERT INTO adminlog (adminid,ip) VALUES (?,INET6_ATON(?))", [account.id, ip]);
  }
  res.json({ user });
}));
app.get("/api/auth/me", (req, res) => {
  const user = readSession(req);
  if (!user) return res.status(401).json({ error: "غير مسجل الدخول." });
  res.json({ user });
});
app.post("/api/auth/logout", (req, res) => { clearSession(res); res.json({ ok: true }); });

app.get("/api/student/dashboard", auth("student"), asyncRoute(async (req, res) => {
  const [users] = await db().execute("SELECT id,regNo,firstName,middleName,lastName,gender,contactNo,email FROM userregistration WHERE id=? LIMIT 1", [req.user.id]);
  if (!users[0]) return bad(res, "الحساب غير موجود.", 404);
  const [bookings] = await db().execute("SELECT roomno,seater,feespm,foodstatus,stayfrom,duration,course FROM registration WHERE regno=? ORDER BY id DESC LIMIT 1", [users[0].regNo]);
  res.json({ student: users[0], booking: bookings[0] || null });
}));
app.get("/api/student/activity", auth("student"), asyncRoute(async (req, res) => {
  const [rows] = await db().execute("SELECT INET6_NTOA(userIp) AS userIp,loginTime FROM userLog WHERE userId=? ORDER BY loginTime DESC LIMIT 50", [req.user.id]);
  res.json({ activity: rows.map(r => ({ ip: r.userIp || "", loginTime: r.loginTime })) });
}));
app.get("/api/student/room", auth("student"), asyncRoute(async (req, res) => {
  const [rows] = await db().execute("SELECT u.firstName,u.lastName,u.regNo,r.roomno,r.seater,r.feespm,r.foodstatus,r.stayfrom,r.duration,r.course FROM userregistration u LEFT JOIN registration r ON r.regno=u.regNo WHERE u.id=? ORDER BY r.id DESC LIMIT 1", [req.user.id]);
  res.json({ room: rows[0] || null });
}));

app.get("/api/admin/overview", auth("admin"), asyncRoute(async (req, res) => {
  const [[students]] = await db().query("SELECT COUNT(*) AS count FROM userregistration");
  const [[rooms]] = await db().query("SELECT COUNT(*) AS count FROM rooms");
  const [[courses]] = await db().query("SELECT COUNT(*) AS count FROM courses");
  const [[bookings]] = await db().query("SELECT COUNT(*) AS count FROM registration");
  const [recent] = await db().query("SELECT userEmail,loginTime FROM userLog ORDER BY loginTime DESC LIMIT 8");
  res.json({ counts: { students: students.count, rooms: rooms.count, courses: courses.count, bookings: bookings.count }, recent });
}));
app.get("/api/admin/rooms", auth("admin"), asyncRoute(async (req, res) => {
  const [rows] = await db().query("SELECT r.id,r.room_no,r.seater,r.fees,COUNT(b.id) AS occupied FROM rooms r LEFT JOIN registration b ON b.roomno=r.room_no GROUP BY r.id ORDER BY r.room_no");
  res.json({ rooms: rows });
}));
app.post("/api/admin/rooms", auth("admin"), asyncRoute(async (req, res) => {
  const roomNo = Number(req.body.room_no), seater = Number(req.body.seater), fees = Number(req.body.fees);
  if (!Number.isInteger(roomNo) || roomNo < 1 || ![1,2,3,4,5,6].includes(seater) || !Number.isFinite(fees) || fees < 0) return bad(res, "تحقق من رقم الغرفة والسعة والرسوم.");
  await db().execute("INSERT INTO rooms (room_no,seater,fees) VALUES (?,?,?)", [roomNo,seater,fees]);
  res.status(201).json({ ok: true });
}));
app.put("/api/admin/rooms/:id", auth("admin"), asyncRoute(async (req, res) => {
  const seater = Number(req.body.seater), fees = Number(req.body.fees);
  if (!Number.isInteger(seater) || seater < 1 || seater > 6 || !Number.isFinite(fees) || fees < 0) return bad(res, "بيانات الغرفة غير صحيحة.");
  const [r] = await db().execute("UPDATE rooms SET seater=?,fees=? WHERE id=?", [seater,fees,Number(req.params.id)]);
  if (!r.affectedRows) return bad(res, "الغرفة غير موجودة.", 404);
  res.json({ ok: true });
}));
app.delete("/api/admin/rooms/:id", auth("admin"), asyncRoute(async (req, res) => {
  const [used] = await db().execute("SELECT COUNT(*) AS count FROM registration b JOIN rooms r ON r.room_no=b.roomno WHERE r.id=?", [Number(req.params.id)]);
  if (used[0].count) return bad(res, "لا يمكن حذف غرفة مرتبطة بحجوزات.");
  await db().execute("DELETE FROM rooms WHERE id=?", [Number(req.params.id)]);
  res.json({ ok: true });
}));
app.get("/api/admin/courses", auth("admin"), asyncRoute(async (req, res) => {
  const [rows] = await db().query("SELECT id,course_code,course_sn,course_fn FROM courses ORDER BY course_fn");
  res.json({ courses: rows });
}));
app.post("/api/admin/courses", auth("admin"), asyncRoute(async (req, res) => {
  const { course_code, course_sn, course_fn } = req.body || {};
  if (![course_code,course_sn,course_fn].every(v => String(v || "").trim())) return bad(res, "أكمل بيانات البرنامج الدراسي.");
  await db().execute("INSERT INTO courses (course_code,course_sn,course_fn) VALUES (?,?,?)", [course_code,course_sn,course_fn]);
  res.status(201).json({ ok: true });
}));
app.put("/api/admin/courses/:id", auth("admin"), asyncRoute(async (req, res) => {
  const { course_code, course_sn, course_fn } = req.body || {};
  if (![course_code,course_sn,course_fn].every(v => String(v || "").trim())) return bad(res, "أكمل بيانات البرنامج الدراسي.");
  await db().execute("UPDATE courses SET course_code=?,course_sn=?,course_fn=? WHERE id=?", [course_code,course_sn,course_fn,Number(req.params.id)]);
  res.json({ ok: true });
}));
app.delete("/api/admin/courses/:id", auth("admin"), asyncRoute(async (req, res) => {
  await db().execute("DELETE FROM courses WHERE id=?", [Number(req.params.id)]);
  res.json({ ok: true });
}));
app.get("/api/admin/students", auth("admin"), asyncRoute(async (req, res) => {
  const [rows] = await db().query("SELECT id,regNo,firstName,middleName,lastName,gender,contactNo,email,regDate FROM userregistration ORDER BY id DESC");
  res.json({ students: rows });
}));
app.post("/api/admin/students", auth("admin"), asyncRoute(async (req, res) => {
  const b=req.body||{}, password=String(b.password||"");
  if (!b.regNo || !b.firstName || !b.lastName || !validEmail(b.email) || password.length<10) return bad(res, "أكمل البيانات واستخدم بريداً صحيحاً وكلمة مرور من 10 أحرف على الأقل.");
  const [existing]=await db().execute("SELECT id FROM userregistration WHERE email=? OR regNo=? LIMIT 1",[b.email,b.regNo]);
  if(existing.length) return bad(res,"البريد أو رقم التسجيل مستخدم مسبقاً.",409);
  const hash=await bcrypt.hash(password,12), today=new Date().toISOString().slice(0,10);
  await db().execute("INSERT INTO userregistration (regNo,firstName,middleName,lastName,gender,contactNo,email,password,updationDate,passUdateDate) VALUES (?,?,?,?,?,?,?,?,?,?)",[b.regNo,b.firstName,b.middleName||"",b.lastName,b.gender||"",String(b.contactNo||0),b.email,hash,today,today]);
  res.status(201).json({ok:true});
}));
app.delete("/api/admin/students/:id", auth("admin"), asyncRoute(async (req,res)=>{
  const [rows]=await db().execute("SELECT regNo FROM userregistration WHERE id=?",[Number(req.params.id)]);
  if(!rows[0]) return bad(res,"الطالب غير موجود.",404);
  const [bookings]=await db().execute("SELECT COUNT(*) AS count FROM registration WHERE regno=?",[rows[0].regNo]);
  if(bookings[0].count) return bad(res,"لا يمكن حذف حساب طالب لديه حجز؛ احذف الحجز أولاً.");
  await db().execute("DELETE FROM userregistration WHERE id=?",[Number(req.params.id)]);
  res.json({ok:true});
}));
app.get("/api/admin/bookings", auth("admin"), asyncRoute(async (req,res)=>{
  const [rows]=await db().query("SELECT id,roomno,seater,feespm,stayfrom,duration,course,regno,firstName,middleName,lastName,gender,contactno,emailid FROM registration ORDER BY id DESC");
  res.json({bookings:rows});
}));
app.post("/api/admin/bookings", auth("admin"), asyncRoute(async (req,res)=>{
  const b=req.body||{}, roomNo=Number(b.roomno), duration=Number(b.duration);
  if(!b.regno || !b.firstName || !b.lastName || !validEmail(b.emailid) || !/^\d{4}-\d{2}-\d{2}$/.test(b.stayfrom||"") || !Number.isInteger(duration) || duration<1 || duration>36) return bad(res,"تحقق من بيانات الطالب والحجز.");
  const conn=await db().getConnection();
  try {
    await conn.beginTransaction();
    const [rooms]=await conn.execute("SELECT seater,fees FROM rooms WHERE room_no=? FOR UPDATE",[roomNo]);
    if(!rooms[0]) throw new Error("الغرفة غير موجودة.");
    const [[occupied]]=await conn.execute("SELECT COUNT(*) AS count FROM registration WHERE roomno=?",[roomNo]);
    if(occupied.count>=rooms[0].seater) throw new Error("الغرفة ممتلئة.");
    const [[duplicate]]=await conn.execute("SELECT COUNT(*) AS count FROM registration WHERE emailid=? OR regno=?",[b.emailid,b.regno]);
    if(duplicate.count) throw new Error("يوجد حجز سابق لهذا الطالب أو البريد.");
    const columns=["roomno","seater","feespm","foodstatus","stayfrom","duration","course","regno","firstName","middleName","lastName","gender","contactno","emailid","egycontactno","guardianName","guardianRelation","guardianContactno","corresAddress","corresCIty","corresState","corresPincode","pmntAddress","pmntCity","pmnatetState","pmntPincode","updationDate"];
    const values=[roomNo,rooms[0].seater,rooms[0].fees,Number(b.foodstatus||0),b.stayfrom,duration,b.course||"",b.regno,b.firstName,b.middleName||"",b.lastName,b.gender||"",String(b.contactno||0),b.emailid,String(b.egycontactno||0),b.guardianName||"",b.guardianRelation||"",String(b.guardianContactno||0),b.corresAddress||"",b.corresCIty||"",b.corresState||"",Number(b.corresPincode||0),b.pmntAddress||"",b.pmntCity||"",b.pmnatetState||"",Number(b.pmntPincode||0),new Date().toISOString().slice(0,10)];
    await conn.execute("INSERT INTO registration ("+columns.join(",")+") VALUES ("+columns.map(()=>"?").join(",")+")",values);
    await conn.commit(); res.status(201).json({ok:true});
  } catch(e) { await conn.rollback(); return bad(res,e.message||"تعذر إنشاء الحجز."); }
  finally { conn.release(); }
}));
app.delete("/api/admin/bookings/:id", auth("admin"), asyncRoute(async (req,res)=>{
  await db().execute("DELETE FROM registration WHERE id=?",[Number(req.params.id)]);
  res.json({ok:true});
}));
app.post("/api/admin/password", auth("admin"), asyncRoute(async(req,res)=>{
  const {oldPassword,newPassword,confirmPassword}=req.body||{};
  if(String(newPassword||"").length<10 || newPassword!==confirmPassword) return bad(res,"كلمة المرور الجديدة يجب ألا تقل عن 10 أحرف وأن تتطابق مع التأكيد.");
  const [rows]=await db().execute("SELECT password FROM admin WHERE id=?",[req.user.id]);
  if(!rows[0] || !(await passwordMatches(String(oldPassword||""),rows[0].password))) return bad(res,"كلمة المرور الحالية غير صحيحة.",401);
  await upgradePassword("admin",req.user.id,String(newPassword));
  res.json({ok:true});
}));

app.use((err, req, res, next) => {
  console.error("Hostel API error:", err && err.message);
  if (res.headersSent) return next(err);
  const duplicate = err && err.code === "ER_DUP_ENTRY";
  res.status(duplicate ? 409 : 500).json({ error: duplicate ? "السجل موجود مسبقاً." : "حدث خطأ داخلي. تحقق من إعدادات الخادم وقاعدة البيانات." });
});
module.exports = app;
if (require.main === module) {
  const port = Number(process.env.PORT || 3000);
  app.listen(port, () => console.log("Hostel API listening on " + port));
}
