🛒 ShopNow – Multi-Vendor E-Commerce Platform (PHP & MySQL)

ShopNow is a full-stack multi-vendor e-commerce web application built using Core PHP, MySQL, HTML, CSS, and JavaScript.
It supports user shopping, admin/vendor product management, order processing, returns & cancellations, stock handling, and email notifications.

This project is ideal for learning full-stack development, college projects, and practical PHP system design.

---

📁 Project Structure
RRV/
│
├── admin/          # Admin (Seller) panel
├── user/           # User-facing storefront
├── vendor/         # (Reserved / future use)
├── uploads/        # Uploaded product images
├── config/         # Database & mail configuration
│   ├── db.php
│   └── mail_config.php
│
├── .gitignore
└── README.md

---

✨ Features Overview
👤 User Features

User Registration & Login

Secure Password Hashing

Forgot Password & Reset via Email

Product Browsing & Search

Product Variants (Size-based)

Add to Cart & Buy Now

Cart Management (Increase / Decrease / Remove)

Checkout with Cash on Delivery

Order Placement with Stock Locking

Order History & Order Details

Item Cancellation (within 24 hrs)

Order Cancellation (within 24 hrs)

Product Return Request (within 4 days after delivery)

Profile View & Account Deletion

Email Notifications for:

Order placed

Cancellation

Return requests

---

🧑‍💼 Admin (Seller) Features

Admin Registration & Login

Forgot Password & Reset

Secure Session-based Authentication

Dashboard with Statistics

Product Management

Add product with image

Add multiple size variants

Edit product & variants

Safe deletion (prevents deleting used variants)

Order Management

View all orders containing admin’s products

Mark orders as Delivered

Cancel orders

Return Management

Approve return

Reject return with reason

Automatic Stock Restoration

Email Notifications for:

New orders

Order cancellations

Return approvals / rejections

---

🔐 Security Highlights

Passwords hashed using password_hash()

Password verification using password_verify()

SQL Injection protection using prepared statements

Session-based authentication

Admin ownership validation

Stock locking using SELECT ... FOR UPDATE

Time-based rules enforced server-side

Open redirect protection

File upload validation (image extensions only)

---

🧾 Business Rules Implemented
| Feature            | Rule                         |
| ------------------ | ---------------------------- |
| Order Cancellation | Within 24 hours              |
| Item Cancellation  | Within 24 hours              |
| Return Request     | Within 4 days after delivery |
| Payment Method     | Cash on Delivery             |
| Stock Handling     | Atomic & transactional       |
| Multi-Vendor       | One order → multiple admins  |

---

🛠️ Tech Stack
| Layer    | Technology                   |
| -------- | ---------------------------- |
| Backend  | Core PHP                     |
| Database | MySQL                        |
| Frontend | HTML, CSS, JavaScript        |
| Email    | PHPMailer                    |
| Server   | Apache (XAMPP / WAMP / LAMP) |

---

⚙️ Installation & Setup

1️⃣ Clone the Repository
git clone https://github.com/your-username/shopnow.git

2️⃣ Move Project to Server Directory
htdocs/RRV   (XAMPP)
www/RRV      (WAMP)

3️⃣ Create Database
CREATE DATABASE shopnow;

Import your provided SQL schema into this database.

---

4️⃣ Configure Database

Edit:
config/mail_config.php

$conn = mysqli_connect("localhost", "root", "", "shopnow");

---

5️⃣ Configure Email (PHPMailer)

Edit:
config/mail_config.php

Add your SMTP credentials (Gmail / Outlook / SMTP provider).

---

6️⃣ Run the Application

User Panel
👉 http://localhost/RRV/user/index.php

Admin Panel
👉 http://localhost/RRV/admin/admin_login.php

---

📦 Default Roles
| Role   | Access                    |
| ------ | ------------------------- |
| User   | Shopping, orders, returns |
| Admin  | Products, orders, returns |
| Vendor | (future expansion)        |

---

🚀 Future Enhancements

Online Payment Gateway (Razorpay / Stripe)

Wishlist

Product Reviews & Ratings

Invoice PDF generation

Shipment Tracking

Admin Analytics Dashboard

Super Admin Panel

REST API version

---

🎓 Educational Value

This project demonstrates:

Real-world e-commerce workflows

Transaction handling

Multi-vendor architecture

Clean PHP logic separation

Secure authentication

Database normalization

Full CRUD operations

---

🙌 Author

Developed with ❤️ as a Full-Stack PHP Learning Project
