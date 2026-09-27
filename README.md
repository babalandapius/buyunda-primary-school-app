# Buyunda Primary School Management System

A lightweight, production-ready starter PHP school management application built with native PHP, PDO, MySQL, Tailwind CSS via CDN, and PHPMailer.

## Features

- Public landing page, About, Academics, Gallery, and Contact pages
- Full responsive design optimized for Mobile, Tablet, and Desktop
- Admin dashboard with real-time statistics & Notification Activity Center
- Teacher dashboard with daily attendance clock-in
- **Automated Email & Notification System**:
  - Teacher sign-in instant email alert to administration
  - Public contact inquiry notifications with direct reply-to
  - Pupil enrollment alerts to administration and welcome confirmations to parents
  - Report card grade summary & remark deliveries directly to parent email
  - Automatic new user provisioning welcome email with login credentials
  - Subject assignment notification emails to educators
  - SMTP diagnostic testing console in School Settings
  - Database notification logging & audit trail (`notification_logs`)
- Pupil management with parent contact tracking
- Report card management, subject grading, and print/PDF output
- User and role management (Admin / Teacher)
- Teacher subject scheduling and assignments
- School settings and profile management

## Stack

- PHP 8+
- MySQL (PDO)
- Tailwind CSS CDN
- PHPMailer 6.9+
- Dotenv

## Setup steps

1. Create the MySQL database and import `database/schema.sql`.
2. Copy the project to your Apache root or public_html folder (`c:/xampp/htdocs/buyunda-primary-school`).
3. Run `composer install`.
4. Configure your SMTP credentials in `.env` (or run in Sandbox/Logging mode):
   ```ini
   SMTP_HOST=smtp.gmail.com
   SMTP_PORT=587
   SMTP_USER=your-actual-email@gmail.com
   SMTP_PASS=your-16-digit-app-password
   SMTP_SECURE=tls
   ```
5. Seed the default users:

```bash
php scripts/seed_users.php
```

6. Open the app in the browser: `http://localhost/buyunda-primary-school/public/`

## Default login credentials

- Admin: `admin@buyundaprimaryschool.org` / `admin123`
- Teacher: `teacher@buyundaprimaryschool.org` / `teacher123`

## Notes

- Replace the logo placeholder at `assets/logo-placeholder.svg` with your real school logo when available.
- You can extend this starter app with fees, timetable, library, and parent portal modules.
