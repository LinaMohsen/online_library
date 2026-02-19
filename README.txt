Online-Library (PHP + MySQL) - Simple Library Management System

1) Put folder "library" inside your web root (htdocs for XAMPP).
   Example: C:\xampp\htdocs\library

2) Create DB and tables:
   - Open phpMyAdmin
   - Create database: library_db
   - Import:employee_db.sql

3) Configure DB credentials:
   - includes/config.php

4) Admin login:
   - URL: http://localhost/library/admin/login.php
   - Email: admin@local.test
   - Password: admin123

Notes:
- Uses Bootstrap 5 + custom CSS (assets/css/style.css)
- Uses mysqli with prepared statements.
