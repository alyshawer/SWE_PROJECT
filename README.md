# Freelancer Job Board - Clean Structure

A simple, clean freelancer job board platform with admin management capabilities.

## 📁 Project Structure

```
SWE_PROJECT/
├── css/
│   └── style.css          # Modern responsive CSS
├── includes/
│   ├── header.php         # Reusable header with navigation
│   ├── footer.php         # Reusable footer
│   └── db.php            # Database connection & functions
├── app/
│   ├── controllers/      # MVC controllers (AuthController, JobController, etc.)
│   ├── views/            # Application views (see app/views)
│   └── helpers/          # Helper functions
├── public/
│   └── index.php         # Public entry / router for MVC
├── index.php             # Homepage
├── setup.sql            # Database schema
└── README.md            # This file
```

## 🚀 Setup Instructions

1. **Start XAMPP**
   - Start Apache and MySQL services

2. **Create Database**
   - Go to phpMyAdmin: http://localhost/phpmyadmin
   - Create database: `freelance_db`
   - Import: `setup.sql`

3. **Access Website**
   - Go to: http://localhost/SWE_PROJECT

## 👥 Test Accounts

- **Admin:** admin@example.com / admin123 (full access)
- **Client:** client1@example.com / client123 (can post/delete jobs)
- **Freelancer:** john@example.com / john123 (can browse jobs)

## ✨ Features

### User Management
- User registration (Freelancers & Clients only)
- Secure login with session management
- Admin account protection (cannot be deleted)

### Job Management
- Clients can post jobs
- Clients can delete their own jobs
- Admins can delete any job
- Browse all available jobs

### Admin Panel
- View all users and jobs
- Delete users (except protected admin)
- Delete any job
- Full platform management

### Security Features
- No admin registration allowed
- Protected super admin account
- Role-based access control
- Session management

## 🎯 How to Use

1. **Register:** Choose freelancer or client account
2. **Login:** Use your credentials
3. **Dashboard:** Access personalized dashboard
4. **Post Jobs:** Clients can post new jobs
5. **Manage:** Delete your own jobs or manage as admin

## 🛠️ Technical Details

- **Frontend:** HTML5, CSS3 (responsive design)
- **Backend:** PHP 7.4+
- **Database:** MySQL
- **Structure:** Clean separation of concerns
- **Security:** Session management, role-based access

## 📱 Responsive Design

The platform is fully responsive and works on:
- Desktop computers
- Tablets
- Mobile phones

Clean, simple, and professional design for easy use.