# Hostel Management System

> Technology: PHP · MySQL

## Overview
This repository contains the source code for **Hostel Management System**, separated from the original multi-project archive.

## Project structure
The original application files are preserved in this repository. Review the source folders to locate the application entry points, configuration, database scripts, and static assets.

## Getting started
1. Review the configuration files and database scripts before running the application.
2. Configure a local development database and update the application settings with your own credentials.
3. Install the dependencies required by the project framework/runtime.
4. Run the application using the instructions appropriate to the detected stack.

## Security notes
- Never commit real passwords, API keys, private keys, or production database credentials.
- Use environment variables or a local, untracked configuration file for secrets.
- Review uploaded files and database exports before deploying publicly.
- This repository is public; do not add personal or confidential data.

## Modernization status
This repository has been separated from the original archive and cleaned of common IDE/build cache folders. Functional modernization and runtime verification should be completed against the application's original requirements before production use.

## License
No license is included. Add a license only if you own the rights to publish this code.


## إعداد قاعدة البيانات بأمان
- استورد ملف `DATABASE FILE/hostelmsphp.sql` لإنشاء الجداول فقط؛ تمت إزالة السجلات التجريبية وبيانات الأشخاص وكلمات المرور من النسخة العامة.
- أنشئ حساب مسؤول جديدًا محليًا ولا تستخدم بيانات دخول افتراضية منشورة.
- عيّن `DB_HOST` و`DB_USER` و`DB_PASSWORD` و`DB_NAME` في بيئة التشغيل بدل تعديل ملفات الاتصال.
- سجّل الدخول بالحسابات القديمة مرة واحدة لترقية تجزئات MD5 تلقائيًا إلى تجزئات حديثة. غيّر كلمات المرور الافتراضية/المستخدمة سابقًا فورًا.
- يتطلب المشروع PHP مع امتدادي MySQLi وPDO وقاعدة MySQL/MariaDB.

## تنبيه أمني
تم حذف ملفات بيانات الدخول التجريبية وتنظيف بيانات السجلات من ملف SQL العام. إذا سبق استخدام بيانات الدخول المنشورة، غيّرها فورًا وراجع سجل Git القديم لأن حذف الملفات من الفرع الحالي لا يمحوها من تاريخ المستودع.
