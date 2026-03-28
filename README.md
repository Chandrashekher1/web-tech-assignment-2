# 🎓 UniResults: Advanced University Result Management System

<div align="center">
  A premium, secure, and modern web application for managing university student results. Built using PHP, MySQL, HTML, CSS, and JavaScript. Featuring a stunning dark-mode glassmorphism interface.
</div>

---

## 📸 Screenshots

Here is a glimpse of the application's premium user interface:

### 🔐 Secure Login Portal
*(Student and Admin Authentication)*
![Login Page](assets/screenshots/login.png)

### 📊 Admin Dashboard
*(Overview of system statistics and recent uploads)*
![Admin Dashboard](assets/screenshots/admin-dashboard.png)

### 📤 Upload Results
*(Upload structured PDFs or add manually)*
![Upload Results](assets/screenshots/upload-results.png)

### 🧑‍🎓 Student Portal
*(Personalized student dashboard with visual performance indicators)*
![Student Portal](assets/screenshots/student-portal.png)

### 🏆 Class Results & Rankings
*(View class rankings and download Excel reports)*
![Class Results](assets/screenshots/class-results.png)

---

## ✨ Key Features

- **Dynamic Admin Panel** — Upload student results via structured PDFs, add manual entries, and manage student accounts.
- **Automated PDF Parsing** — Automatic text extraction and data grading from parsed PDF files using `Smalot/PdfParser`.
- **Student Portal** — Secure login access for students to view their semester-wise academic performance with visual progress bars.
- **Excel Report Generator** — Effortlessly download full class rankings and results as Excel spreadsheets using `PhpSpreadsheet`.
- **PDF Result Downloads** — Students can privately download their individual reports as formatted PDF documents utilizing `TCPDF`.
- **Premium UI/UX** — Modern dark-mode glassmorphism design with sleek subtle animations and a responsive layout natively built in CSS.
- **Robust Security** — Hardened with PDO prepared statements, secure session-based authentication, input sanitization, and strict file validation.

---

## 🚀 Setup Instructions

### Prerequisites
- **XAMPP** or **WAMP** (PHP 7.4+ with MySQL installed)
- **Composer** (PHP dependency manager) — [getcomposer.org](https://getcomposer.org)

### Step 1: Clone / Copy Project
Place the `web-tech-project` folder inside your local web server's root directory:
- **XAMPP:** `C:\xampp\htdocs\web-tech-project\`
- **WAMP:** `C:\wamp64\www\web-tech-project\`
- *Alternatively, run `php -S localhost:8000` via terminal inside the folder!*

### Step 2: Install Dependencies
Open a terminal inside the project directory and run:
```bash
composer install
```
*(Installs PDFParser, PhpSpreadsheet, and TCPDF)*

### Step 3: Configure the Database
1. Start **Apache** and **MySQL** from your XAMPP/WAMP control panel.
2. Go to **phpMyAdmin**: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
3. Under the **Import** tab, upload the `schema.sql` file located in the project root.
4. Click **Go** to create the `ResultManagement` database and seed the default data.

### Step 4: Run the Setup Script
Navigate to the setup script in your browser to generate secure password hashes for default accounts:
```
http://localhost/web-tech-project/setup.php (or localhost:8000/setup.php)
```
> **⚠️ Security Warning:** Please delete `setup.php` immediately after running it successfully!

### Step 5: Start the App!
Navigate to the root URL to start using the app:
```
http://localhost/web-tech-project/ (or localhost:8000)
```

---

## 🔑 Default Credentials

**Admin Access:**
- **ID:** `ADMIN001`
- **Password:** `admin123`

**Sample Student Access:**
- **ID:** `STU001` (up to `STU005`)
- **Password:** `student123`

---

## 📁 System Architecture

```text
web-tech-project/
├── admin/                 # Admin controllers & views (Dashboard, Uploads)
├── assets/                # Design system (CSS, JS, Images/Screenshots)
├── config/                # Database PDO Connection (`db.php`)
├── exports/               # PDF & Excel export handling scripts
├── student/               # Student controllers & views
├── uploads/               # Directory for uploaded PDF results
├── vendor/                # Composer dependencies 
├── composer.json          # Package configurations
├── schema.sql             # MySQL schema and initialization
└── index.php              # Application Entry Point
```

---

## 🛠️ Troubleshooting

| Issue | Solution |
|-------|----------|
| **"Database connection failed"** | Ensure MySQL is running and your credentials in `config/db.php` are correct. |
| **"PDF parsing library not installed"** | Make sure you ran `composer install` in your project root folder. |
| **Login not working** | Make sure you imported `schema.sql` and ran `setup.php` to hash passwords. |

---

## 📝 License
This project was developed for educational purposes. Feel free to modify, extend, and use it.
