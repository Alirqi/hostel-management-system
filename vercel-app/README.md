# Hostel Management System — Vercel / Node.js

This is the Node.js migration workspace. The original PHP application remains in the repository root and is not deleted.

## Stack
- Express API deployed as a Vercel Node.js Function
- Static Arabic RTL dashboard
- Existing MySQL schema accessed with mysql2
- Signed, HttpOnly session cookie; bcrypt password hashing
- Legacy MD5 passwords are upgraded to bcrypt after a successful login

## Local setup
1. Import `../DATABASE FILE/hostelmsphp.sql` into a MySQL database.
2. Copy `.env.example` to `.env` and fill in the database connection and a random `SESSION_SECRET` of at least 32 characters.
3. Run `npm install` and `npm start` from this directory.
4. Open http://localhost:3000.

## Vercel setup
Set `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`, `SESSION_SECRET`, and (if required by the provider) `DB_SSL=true` in Vercel Project Environment Variables. Do not commit real credentials.

## Important
This migration is incremental. PHP files are retained. The initial Node.js release covers login, student profile/activity/room information, admin overview, rooms, courses, student creation/listing, bookings, and password changes. Verify the deployed app against a non-production database before migrating live data or switching users.
