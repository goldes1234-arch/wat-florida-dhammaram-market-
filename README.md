# ระบบจองพื้นที่ขายของ (Temple/Event Market Booking System)

ระบบจองพื้นที่ขายของสำหรับงานวัด/งานอีเวนต์/ตลาดนัด — PHP + MySQL ล้วน (ไม่ใช้ framework/Composer) รันบน XAMPP

## เข้าใช้งาน

- หน้าเว็บสาธารณะ: http://localhost/ProjectWFD/Temple_Market/
- ระบบแอดมิน: http://localhost/ProjectWFD/Temple_Market/admin/login
  - อีเมล: `admin@templemarket.local`
  - รหัสผ่าน: `Passw0rd!2026`

> เปลี่ยนรหัสผ่านนี้ก่อนนำไปใช้งานจริง (ไปที่ตาราง `admin_users` หรือเพิ่มหน้าจัดการผู้ใช้ในเฟสถัดไป)

## โครงสร้างโปรเจค

```
index.php            หน้าประตูเดียวของระบบ (front controller)
app/Core/             Router, Database, View, Auth, Session, ฯลฯ
app/Controllers/      Admin/ และ Public/
app/Models/           กลุ่มคลาสเข้าถึงฐานข้อมูล (PDO)
app/Services/         ตรรกะทางธุรกิจ (การจองแบบ transaction, Stripe, อีเมล, ฯลฯ)
config/               ตั้งค่า .env และ routes.php
database/schema.sql   โครงสร้างฐานข้อมูล
database/seed.php     สคริปต์สร้างข้อมูลตัวอย่าง
resources/views/      Template ของหน้าเว็บ (Admin + Public)
resources/lang/       ไฟล์ภาษา ไทย/อังกฤษ
storage/logs/emails/  อีเมลที่ระบบ "ส่ง" จะถูกบันทึกเป็นไฟล์ .html ที่นี่เสมอ
uploads/               ไฟล์รูปภาพที่อัปโหลด (แบนเนอร์งาน, ผังร้าน, โลโก้)
```

## ตั้งค่าใหม่ตั้งแต่ต้น (ถ้าต้องรีเซ็ตฐานข้อมูล)

```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root -e "CREATE DATABASE IF NOT EXISTS temple_market CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
/Applications/XAMPP/xamppfiles/bin/mysql -u root temple_market < database/schema.sql
/Applications/XAMPP/xamppfiles/bin/php database/seed.php
```

`database/seed.php` ลบข้อมูลเดิมทั้งหมดแล้วสร้างใหม่ทุกครั้งที่รัน (ปลอดภัยที่จะรันซ้ำ) โดยจะได้:

- แอดมิน 1 บัญชี
- งาน 4 งาน ครบทั้ง 3 สถานะที่แสดงบนหน้าเว็บ (เร็วๆ นี้ / เปิดให้จอง / ปิดรับจอง) และ 1 งานฉบับร่าง (ไม่เผยแพร่)
- ล็อกขายของ 30 ล็อก ในสถานะผสมกัน (ว่าง/รอชำระเงิน/จองแล้ว)
- การจอง 13 รายการ ครบทุกวิธีชำระเงินและทุกสถานะ พร้อมประวัติการเปลี่ยนสถานะ
- ผู้แจ้งเตือนสนใจ 3 รายการ

## หมายเหตุการทำงานของระบบ

- **อีเมล**: ระบบพยายามส่งอีเมลจริงผ่าน `mail()` ของ PHP แต่เนื่องจากเครื่อง dev ส่วนใหญ่ไม่ได้ตั้งค่า mail server ไว้ ระบบจะบันทึกอีเมลทุกฉบับเป็นไฟล์ `.html` ไว้ที่ `storage/logs/emails/` เสมอ เปิดไฟล์นั้นดูได้โดยตรงเพื่อตรวจสอบเนื้อหาอีเมล
- **การชำระเงินออนไลน์ (Stripe)**: ช่องทางนี้จะไม่แสดงบนหน้าเว็บจนกว่าแอดมินจะกรอก Stripe Secret Key ในหน้าตั้งค่าระบบ (`/admin/settings`) เมื่อทดสอบบนเครื่อง local, Stripe จะยิง webhook มาที่ `localhost` ไม่ได้โดยตรง — ต้องใช้ `stripe listen --forward-to http://localhost/ProjectWFD/Temple_Market/stripe/webhook` หรือรอหน้า "กำลังตรวจสอบการชำระเงิน" หลังจากลูกค้าชำระเงินเสร็จ (ระบบจะเช็คสถานะอีกครั้งแบบ synchronous ให้อัตโนมัติ)
- **การจองพร้อมกัน (race condition)**: ระบบล็อกแถวของล็อก (`SELECT ... FOR UPDATE`) ภายใน transaction ทันทีที่มีการพยายามจอง ทำให้การจองซ้ำซ้อนพร้อมกันสองคนเป็นไปไม่ได้
- **สิทธิ์การเข้าถึงไฟล์**: โฟลเดอร์ `app/`, `config/`, `database/`, `resources/`, `storage/` ถูกกันไม่ให้เข้าถึงผ่านเว็บโดยตรงด้วย `.htaccess` (ทดสอบแล้วว่าได้ผล — ขึ้น `403 Forbidden`)
- **สำรองข้อมูล**: หน้า `/admin/backups` (เฉพาะ super_admin) มีปุ่มสำรองข้อมูลทันที และเก็บไฟล์ `.sql.gz` ล่าสุดตามจำนวนที่ตั้งไว้ใน `.env` (`BACKUP_RETENTION`, ค่าเริ่มต้น 14 ไฟล์) อัตโนมัติ
  - **ตั้งให้สำรองข้อมูลอัตโนมัติตามตารางเวลา**: ต้องตั้งค่า `BACKUP_CRON_SECRET` ใน `.env` ก่อน (สร้างด้วย `php -r "echo bin2hex(random_bytes(24));"`) แล้วนำ URL ที่แสดงในหน้า `/admin/backups` (รูปแบบ `https://yourdomain.com/cron/backup?token=...`) ไปตั้งเป็น cron job บนโฮสติ้งจริง (เช่น cPanel → Cron Jobs สั่งรันทุกวัน) — endpoint นี้ไม่ต้องล็อกอิน ยืนยันตัวตนด้วย token ในลิงก์แทน เก็บ URL นี้เป็นความลับ
  - ต้องมี `mysqldump` ใช้งานได้บนเซิร์ฟเวอร์ (ตั้ง path เต็มไว้ที่ `MYSQLDUMP_PATH` ถ้าไม่ได้อยู่ใน PATH ของเว็บเซิร์ฟเวอร์) และฟังก์ชัน `exec()` ของ PHP ต้องไม่ถูกปิดไว้ (`disable_functions`)

## เฟสถัดไป (ยังไม่ได้ทำในเฟสนี้ ตามสเปก)

- เชื่อมต่อ LINE OA สำหรับแจ้งเตือน (มีช่องเก็บ Channel Access Token ไว้ในตั้งค่าระบบแล้ว)
- ระบบแจ้งเตือนผ่าน SMS
- สิทธิ์ staff แบบละเอียด (ตอนนี้ตาราง `admin_users` มีคอลัมน์ `role` รองรับไว้แล้ว แต่ยังไม่มีหน้าจัดการ/จำกัดสิทธิ์ตามงาน)
