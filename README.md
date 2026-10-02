# ระบบจองพื้นที่ขายของ (Temple/Event Market Booking System)

ระบบจองพื้นที่ขายของสำหรับ Wat Florida Dhammaram (งานวัด/งานอีเวนต์/ตลาดนัด) — PHP + MySQL ล้วน (ไม่ใช้ framework/Composer) รันบน XAMPP

Repo: https://github.com/goldes1234-arch/wat-florida-dhammaram-market-

```bash
git clone git@github.com:goldes1234-arch/wat-florida-dhammaram-market-.git
```

> อย่าสับสนกับ repo `wat-florida-dhammaram-calendar` ของวัดเดียวกัน — นั่นเป็นแอปมือถือปฏิทินวัด (Flutter) คนละระบบกับที่นี่โดยสิ้นเชิง

## เข้าใช้งาน

- หน้าเว็บสาธารณะ: http://localhost/ProjectWFD/Temple_Market/
- ระบบแอดมิน: http://localhost/ProjectWFD/Temple_Market/admin/login
  - อีเมล: `admin@templemarket.local`
  - รหัสผ่าน: `Passw0rd!2026`

> เปลี่ยนรหัสผ่านนี้ก่อนนำไปใช้งานจริง — ใช้ปุ่ม "ลืมรหัสผ่าน?" ในหน้า login เพื่อตั้งรหัสผ่านใหม่ได้เอง (ต้องตั้งค่า SMTP ก่อนถึงจะได้รับอีเมลลิงก์รีเซ็ต ดูหัวข้อ SMTP ด้านล่าง)

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
  - **"ตรวจสอบไฟล์" (Verify)**: ปุ่มข้างไฟล์แต่ละไฟล์ — คลายการบีบอัดทั้งไฟล์เพื่อเช็คว่าไฟล์ไม่เสีย (รวมถึงเช็คไฟล์ที่ถูกตัดไม่ครบ ไม่ใช่แค่เช็ค gzip อ่านได้) และมีโครงสร้างตารางข้างในจริง ไม่แตะฐานข้อมูลเลย ปลอดภัยที่จะกดเช็คได้ตลอดเวลา — แนะนำให้สุ่มกดเช็คเป็นระยะ ไฟล์สำรองที่ไม่เคยถูกเปิดดูเลยก็เหมือนไม่มีแผนสำรองจริง
  - **"กู้คืนจากไฟล์นี้" (Restore)**: เขียนทับฐานข้อมูลที่ใช้งานจริงทั้งหมดด้วยไฟล์สำรองนั้น ย้อนกลับไม่ได้ — ต้องพิมพ์ชื่อไฟล์ยืนยันให้ตรงทุกตัวอักษรก่อนถึงจะกดได้จริง ต้องมี `mysql` client ใช้งานได้บนเซิร์ฟเวอร์ด้วย (ตั้ง path เต็มไว้ที่ `MYSQL_PATH` ถ้าไม่ได้อยู่ใน PATH)
- **SMTP จริง**: ตั้งค่าได้ที่ `/admin/settings` → "ตั้งค่าเซิร์ฟเวอร์อีเมล (SMTP)" มีปุ่ม "ส่งอีเมลทดสอบ" ในหน้าเดียวกัน ถ้าไม่ตั้งค่า ระบบ fallback ไปใช้ `mail()` ของ PHP อัตโนมัติ (มักส่งไม่สำเร็จบนโฮสต์จริง จึงแนะนำให้ตั้งค่าก่อนขึ้นใช้งานจริง)
- **แจ้งเตือนแอดมินทาง LINE**: ตั้งค่าได้ที่ `/admin/settings` → กรอก LINE Channel Access Token (จาก LINE Developers Console, ประเภท Messaging API) ระบบจะ broadcast ข้อความไปยังผู้ติดตาม LINE OA ทุกครั้งที่มีการจองใหม่ ถ้าไม่ตั้งค่าไว้ ระบบจะข้ามการแจ้งเตือนนี้เฉยๆ
- **Waitlist**: เมื่อล็อกในงานเต็มหมด หน้ารายละเอียดงานจะโชว์ฟอร์ม "ขอแจ้งเตือนเมื่อมีล็อกว่าง" แทน และจะส่งอีเมลแจ้งอัตโนมัติทันทีที่มีล็อกว่างจากการยกเลิก/ปฏิเสธการจอง
- **ประวัติการใช้งาน**: หน้า `/admin/activity-log` (เฉพาะ super_admin) บันทึกการลบงาน/โซน/ล็อก และการจัดการบัญชีผู้ใช้งาน แยกจาก `booking_status_logs` ที่บันทึกเฉพาะการเปลี่ยนสถานะการจอง
- **ใบเสร็จแบบ PDF**: หน้าใบเสร็จ (`/booking/{code}/receipt`) มีปุ่ม "ดาวน์โหลด PDF" สร้างไฟล์ PDF จริง (ไม่ใช่แค่ print-to-PDF ของเบราว์เซอร์) รองรับภาษาไทยเต็มรูปแบบ พร้อม QR code ในตัว
  - ใช้ไลบรารี **tFPDF** (`vendor/tFPDF/`, รองรับ Unicode/TrueType — ธรรมดา FPDF ไม่รองรับภาษาไทย) กับฟอนต์ **Noto Sans Thai** (`vendor/tFPDF/font/unifont/`, สัญญาอนุญาต SIL OFL ฟรี) และ **phpqrcode** (`vendor/phpqrcode/`) สำหรับสร้าง QR — ทั้งสองวางเป็นไฟล์ตรงๆ ไม่ผ่าน Composer (ตรงกับที่โปรเจกต์นี้ไม่ใช้ framework/dependency manager มาตลอด)
  - **ข้อควรรู้ถ้าแก้โค้ดส่วนนี้**: tFPDF เวอร์ชันที่ใช้มีบั๊กเรื่องความกว้างตัวอักษร — `GetStringWidth()` คืนค่าผิดมากๆ (~230mm) สำหรับสระ/วรรณยุกต์ลอยของไทย (เช่น  ่ ้ ั) เพราะฉะนั้น `ReceiptPdfService` จึงเขียนฟังก์ชันตัดบรรทัดเอง (`wrapLines()`) แทนการใช้ `MultiCell()` ของ tFPDF ตรงๆ — ถ้าจะเพิ่มข้อความยาวๆ ในไฟล์ PDF ใหม่ ให้ใช้ `renderWrappedText()` ที่มีอยู่แล้ว อย่าเรียก `MultiCell()` ตรงๆ กับข้อความภาษาไทย
  - ไฟล์ QR ชั่วคราวถูกเขียนลง `storage/tmp/` (ไม่ใช่ `sys_get_temp_dir()` ของ PHP) เพราะบนโฮสติ้งบางที่ เว็บเซิร์ฟเวอร์เข้าถึง system temp directory ไม่ได้

## Staging environment

สภาพแวดล้อมทดสอบแยกจาก production เต็มรูปแบบ (subdomain + database ของตัวเอง) สำหรับลองฟีเจอร์ใหม่ก่อนขึ้นเว็บจริง รันบน Plesk เดียวกับ production

**ตั้งครั้งแรก:**

1. Plesk → Websites & Domains → เพิ่ม Subdomain เช่น `staging.floridatemplemarket.org` (document root แยกจาก production คนละโฟลเดอร์)
2. สร้างฐานข้อมูลใหม่แยกต่างหาก เช่น `florida_templemarket_staging` (ห้ามใช้ฐานเดียวกับ production)
3. Plesk → Git ของ subdomain นี้ → เชื่อม repo เดียวกับ production แต่เลือก branch `staging` แทน `main`
4. สร้าง `.env` บน staging เอง (ไม่ก็อปจาก production) โดยตั้ง `APP_ENV=staging` และชี้ `DB_*` ไปที่ฐานข้อมูล staging ที่สร้างไว้
5. รัน `mysql ... < database/schema.sql` แล้ว `php database/seed.php` บนฐานข้อมูล staging — **ห้ามก็อปปี้ข้อมูลจริงจาก production มา** ใช้ข้อมูลตัวอย่างจาก seed เท่านั้น
6. ตั้ง `BACKUP_CRON_SECRET` ของ staging เป็นคนละค่ากับ production แล้วตั้ง `/cron/post-deploy` ของ staging แยกต่างหาก (เหมือน production แต่ host คนละตัว)

**Deploy:** `git push origin staging` แล้วดึงขึ้น Plesk subdomain นั้น (ตั้ง auto-deploy ของ Plesk Git integration ได้ถ้าต้องการให้ pull อัตโนมัติ) — คนละ flow กับ production ที่ deploy จาก `main`

**ความปลอดภัยที่ระบบช่วยกันเองเมื่อ `APP_ENV=staging`:**

- ขึ้นแถบสีแดง "⚠ STAGING" บนทุกหน้า กันสับสนกับเว็บจริง
- Stripe: รับเฉพาะ secret key ที่ขึ้นต้นด้วย `sk_test_` เท่านั้น ถ้าใส่ live key (`sk_live_...`) ระบบจะถือว่ายังไม่เปิดใช้งานเสมอ (กันเงินจริงเคลื่อนบน staging โดยไม่ตั้งใจ)
- อีเมล: ไม่ส่งออกจริงไม่ว่าจะตั้ง SMTP ไว้หรือไม่ — บันทึกเป็นไฟล์ที่ `storage/logs/emails/` เหมือนตอน dev เท่านั้น (กันสแปมอีเมลจริงของลูกค้า/ผู้ติดตาม)
- LINE: **ระบบป้องกันให้ไม่ได้** — ถ้ากรอก LINE Channel Access Token เดียวกับ production ใน Settings ของ staging ข้อความจะ broadcast ไปหาผู้ติดตาม LINE OA ตัวจริงทันที ถ้าจะทดสอบฟีเจอร์ LINE บน staging ต้องสร้าง LINE OA แยกต่างหากสำหรับทดสอบเท่านั้น ห้ามใช้ token ของจริง

## เฟสถัดไป (ยังไม่ได้ทำในเฟสนี้ ตามสเปก)

- ระบบแจ้งเตือนผ่าน SMS
- สิทธิ์ staff แบบละเอียดตามงาน (ตอนนี้มีแค่ 3 ระดับ: super_admin / staff / checkin ยังไม่จำกัดสิทธิ์ให้ staff คนหนึ่งดูแลได้เฉพาะบางงาน)
