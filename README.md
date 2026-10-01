# Online Complaint Management System (ResolveCMS)

A modern, responsive, and secure web-based grievance redressal and complaint tracking system built with **PHP**, **MySQL**, **HTML5**, **Vanilla CSS**, and **JavaScript**, specifically architected for deployment on **XAMPP / WAMP / LAMP**.

---

## 🌟 Key Features

### 👤 Citizen / User Portal
1. **User Registration & Secure Login**:
   - Clean registration form (Name, Email, Phone, Address, Password).
   - Secure password hashing using PHP `password_hash()` (bcrypt).
   - Instant 1-click demo credential fillers on login screen.
2. **Lodge / Submit Complaints**:
   - Department / Category selection (Roads, Sanitation, Water & Electricity, Billing, Safety, etc.).
   - Urgency & Priority tagging (Low, Medium, High, Urgent).
   - Drag-and-drop file upload for supporting evidence and photos (JPG, PNG, PDF, DOC, DOCX up to 10MB).
   - Automatic generation of unique tracking identifiers (e.g. `CMP-2026-7841`).
3. **Real-Time Status Tracking**:
   - Visual 4-stage stepper: **Submitted &rarr; Under Review &rarr; In Progress &rarr; Resolved/Rejected**.
   - Inspection of official department remarks and updates.
   - Comprehensive audit trail showing who updated the ticket and when.
4. **My Complaints & Search**:
   - Live instant filtering without page reloads.
   - Filter by status (`Pending`, `In Progress`, `Resolved`, `Rejected`) and priority.

### 🛡️ Administrator Portal
1. **Executive Dashboard**:
   - Real-time KPI summary (Total Complaints, Pending, In Progress, Resolved, Resolution Rate %).
   - Urgent Attention / High Priority tickets queue.
   - Recent grievance influx monitor.
2. **Complaint Management & Review**:
   - Search across citizen name, email, tracking ID, title, and keywords.
   - Full inspection of citizen's issue, attached proof download, and contact details.
3. **Status & Directive Updates**:
   - Change status to **Pending**, **In Progress**, **Resolved**, or **Rejected**.
   - Re-evaluate priority and re-assign department if necessary.
   - Record official administrative remarks and directives visible to the citizen.
   - Automatic logging in the `complaint_logs` table for accountability.
4. **Category & Department Management**:
   - Add new civic departments or categories.
   - View ticket counts per department and manage categories.
5. **Citizen & Staff Directory**:
   - View registered users, contact info, roles, and total lodged complaints.

### 🔍 Public Tracking Tool
- Anyone with a valid Complaint Tracking Code can instantly check the progress and official notes on `track.php` without signing in.

---

## 💻 Tech Stack
- **Frontend**: HTML5, Vanilla CSS3 (Custom Design System with Glassmorphism, Responsive Grid, and Badges), Vanilla JavaScript
- **Backend**: PHP 7.4 / 8.x (Object-Oriented PDO database abstraction)
- **Database**: MySQL 5.7+ / MariaDB 10.x+
- **Server Environment**: XAMPP (Apache + MySQL)

---

## 🚀 How to Setup and Run in XAMPP

### Step 1: Start Services in XAMPP
1. Open the **XAMPP Control Panel**.
2. Start both **Apache** and **MySQL** (both status indicators should turn green).

### Step 2: Import the Database
1. Open your web browser and navigate to:
   ```
   http://localhost/phpmyadmin/
   ```
2. Click on the **Databases** tab, type `complaint_db` as the database name, and click **Create**.
3. Select `complaint_db` in the left sidebar, click on the **Import** tab at the top.
4. Click **Choose File** and select `database.sql` from this project directory.
5. Click **Import** at the bottom.
*(Alternatively, you can copy the contents of `database.sql` and run them directly in the SQL tab).*

### Step 3: Place Project Files in `htdocs`
1. Copy or move this folder into your XAMPP web root:
   ```
   C:\xampp\htdocs\Smit-pro
   ```
2. Make sure the folder `uploads/` exists and is writable for file attachments.

### Step 4: Open in Your Web Browser
Open your browser and navigate to:
```
http://localhost/Smit-pro/
```

---

## 🔑 Pre-Configured Demo Credentials

| Role | Email Address | Password |
|---|---|---|
| **Administrator** | `admin@cms.com` | `admin123` |
| **Citizen User** | `user@cms.com` | `user123` |

*(Tip: On the login page, you can also click the quick buttons **"👤 Citizen User"** or **"🛡️ Administrator"** to auto-fill the credentials!)*

---

## 📁 Directory Structure
```
Smit-pro/
├── assets/
│   ├── css/
│   │   └── style.css            # Custom modern responsive CSS stylesheet
│   └── js/
│       └── main.js              # Live search, filters, drag-and-drop file upload
├── config/
│   └── db.php                   # PDO database connection & helper utilities
├── includes/
│   ├── auth.php                 # Authentication guards (requireUser, requireAdmin)
│   ├── footer.php               # Global footer
│   ├── header.php               # Global HTML head & navigation include
│   └── navbar.php               # Dynamic role-based navigation bar
├── uploads/                     # Storage for user complaint attachments
│   └── .gitkeep
├── admin/
│   ├── categories.php           # Add/Delete civic departments
│   ├── complaint_details.php    # Inspect complaint, update status & add remarks
│   ├── complaints.php           # Full search & management table
│   ├── dashboard.php            # Admin KPI overview & critical queue
│   └── users.php                # Directory of registered citizens & staff
├── user/
│   ├── dashboard.php            # Citizen overview & stats
│   ├── my_complaints.php        # Citizen's complaints list with search/filter
│   ├── new_complaint.php        # Complaint submission form with file upload
│   └── view_complaint.php       # Citizen complaint detail & timeline view
├── database.sql                 # MySQL schema with sample seed data
├── index.php                    # Home landing page with instant tracker
├── login.php                    # Sign-in page with demo auto-fill
├── logout.php                   # Session logout handler
├── register.php                 # Citizen registration page
├── track.php                    # Public tracking portal by Tracking ID
└── README.md                    # Project documentation & setup instructions
```
