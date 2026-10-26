# Archbishop Flynn Secondary School – Website

PHP 8 + SQLite (no database setup needed). Runs on XAMPP at http://localhost/afss/

- **First run:** open http://localhost/afss/admin/ and create the administrator account.
  The database (`data/afss.sqlite`) is created and seeded with starter content automatically.
- **Public pages:** home, about/pages, governance, admissions, apply online, news & newsletter, events calendar, careers, gallery, contact.
- **Admin (`/admin`):** slides, pages, news, events, gallery, governance/staff, vacancies, admission & job applications (CSV export, CV download), messages, newsletter (subscribers, compose/send), site settings (contact, social, colours, logo, stats, admissions open/closed), admin users.
- **Email:** forms are always saved to the database; email notifications and newsletters use PHP `mail()`, so configure sendmail/SMTP in XAMPP's `php.ini` to deliver real mail.
- **Backup:** copy `data/` and `uploads/`.
