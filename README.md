# 📚 StudySwap Hub

**Pakistan's Student Book Exchange Platform** — Swap, buy, sell, or share academic books and study resources across universities, all in one place.

---

## 🌟 Overview

StudySwap Hub is a PHP-based web application that connects Pakistani university students to exchange academic books and study materials. Students can list their books for swapping, selling, or free sharing, and send requests to other students directly through the platform.

---

## ✨ Features

### 👤 Student Features
- **Registration & Login** — University-specific student accounts
- **Browse Books** — Search and filter by category, listing type, and university
- **Add Book Listing** — Create listings with cover image and PDF upload
- **Listing Types:**
  - 🔁 **Swap** — Trade your book for another
  - 💰 **Sale** — Sell at a fixed price
  - 🆓 **Free** — Share for free download
- **Book Detail Page** — View full info and send a swap/buy/download request
- **Request System** — Send, accept, or decline requests
- **Wishlist** — Save books you are interested in
- **Notifications** — Real-time alerts for requests and updates
- **Swap History** — View your past transactions
- **My Books** — Manage your own listings
- **User Profile** — Bio, rating, university, and swap count
- **Dashboard** — Stats overview, incoming requests, and accepted swaps

### 🔐 Admin Features
- **Admin Panel** — Manage users and book listings
- **User Control** — Activate or deactivate student accounts
- **Content Moderation** — Remove inappropriate listings
- **Platform Stats** — Total users, books, and swaps at a glance

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP (procedural + PDO) |
| Database | MySQL |
| Frontend | HTML5, CSS3, JavaScript |
| Icons | Font Awesome 6.5 |
| File Storage | Local server (`uploads/`) |
| Sessions | PHP Sessions |

---

## 📁 Project Structure

```
studyswap_hub/
│
├── index.php                    # Homepage — hero, featured books, university showcase
├── browse.php                   # Book listing with search & filters
├── book-detail.php              # Single book detail + request form
├── add-book.php                 # Add new book listing (image + PDF upload)
├── dashboard.php                # User dashboard — stats, requests, activity
├── my-books.php                 # Manage your own listings
├── profile.php                  # User profile page
├── requests.php                 # Manage incoming/outgoing requests
├── swap-history.php             # Transaction history
├── wishlist.php                 # Saved books
├── notifications.php            # All notifications
├── admin.php                    # Admin panel
│
├── login.php                    # Login page
├── register.php                 # Student registration
├── logout.php                   # Session logout
│
├── buy-book.php                 # Book purchase flow
├── process-payment.php          # Payment processing
├── payment-success.php          # Payment confirmation page
├── process-free-download.php    # Free book download handler
├── download-book.php            # PDF download
├── delete-book.php              # Delete a listing
├── wishlist-toggle.php          # Add/remove wishlist (AJAX)
├── process-owner-response.php   # Accept/decline swap request
├── api-check-notifications.php  # Notification count API (AJAX)
│
├── db_connect.php               # Database connection + helper functions
├── database.sql                 # Database schema + default admin account
│
├── style.css                    # Main stylesheet
├── main.js                      # Frontend JavaScript
│
├── includes/
│   ├── navbar.php               # Navigation bar
│   ├── footer.php               # Footer
│   └── dashboard_sidebar.php    # Dashboard sidebar
│
└── uploads/
    ├── books/                   # Book cover images
    └── pdfs/                    # Uploaded book PDF files
```

---

## 🗄️ Database Schema

| Table | Description |
|-------|-------------|
| `users` | Student/admin accounts — name, email, university, role, rating |
| `books` | Book listings — title, author, category, condition, type, price |
| `requests` | Swap/sale/free requests between users |
| `swap_history` | Record of completed transactions |
| `wishlist` | Books saved by users |
| `notifications` | System and user notifications |

---

## ⚙️ Installation & Setup

### Requirements
- PHP 7.4+
- MySQL 5.7+ or MariaDB
- Apache or Nginx (XAMPP on Windows / LAMP on Linux)

### Steps

**1. Copy project files to your server root**

```bash
# XAMPP (Windows)
C:/xampp/htdocs/studyswap_hub/

# LAMP (Linux)
/var/www/html/studyswap_hub/
```

**2. Import the database**

```bash
# Via terminal
mysql -u root -p studyswap_hub < database.sql
```

Or via **phpMyAdmin**:
- Create a new database named `studyswap_hub`
- Import `database.sql`

**3. Configure database credentials**

Open `db_connect.php` and update:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'studyswap_hub');
define('DB_USER', 'root');    // your MySQL username
define('DB_PASS', '');        // your MySQL password
```

**4. Set folder permissions (Linux only)**

```bash
chmod 755 uploads/books uploads/pdfs
```

**5. Open in your browser**

```
http://localhost/studyswap_hub/
```

---

## 🔑 Default Admin Account

| Field | Value |
|-------|-------|
| Email | `admin@studyswap.pk` |
| Password | `admin123` |

> ⚠️ **Change the password immediately after your first login.**

---

## 📖 Usage Guide

### For Students
1. Register at `register.php` with your university details
2. Browse or search for books on `browse.php`
3. Click a book to view details and send a swap/buy/download request
4. List your own books via `add-book.php`
5. Manage incoming requests from your `dashboard.php`

### For Admins
1. Log in with the admin account
2. Go to `admin.php`
3. Manage users, moderate listings, and view platform statistics

---

## 📚 Book Categories

- Engineering
- Medical
- Business
- Science
- Arts & Humanities
- Law
- Other

---

## 🏫 Featured Universities

- NUST Islamabad
- FAST-NUCES Lahore
- LUMS Lahore
- UET Lahore
- QAU Islamabad

> Any university name can be entered during registration.

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/your-feature`)
3. Commit your changes (`git commit -m 'Add your feature'`)
4. Push to the branch (`git push origin feature/your-feature`)
5. Open a Pull Request

---

## 📄 License

This project is intended for educational purposes. Feel free to use and modify it.

---

## 👨‍💻 Author

**StudySwap Hub** — Built for Pakistani students

> *"Share knowledge, save money, build community."*
