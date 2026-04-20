# Lisingo - C2C E-Commerce Platform

**Lisingo** is a Customer-to-Customer (C2C) e-commerce platform built for the South African market. It enables individual consumers to buy and sell goods directly with each other, facilitating peer-to-peer transactions in a safe, simple, and secure online marketplace.

## Features

### Main Platform (Customer Website)
- **User Registration & Authentication** - Secure sign-up, login, and session management
- **Product Listings** - Create, edit, and manage product listings with multiple images
- **Browse & Search** - Filter products by category, condition, price range, and sort order
- **Shopping Cart** - Add/remove items, update quantities with AJAX
- **Checkout & Orders** - Complete purchases with EFT, Cash, or Card payment options
- **Messaging System** - Direct communication between buyers and sellers
- **User Profiles** - View seller profiles, ratings, and active listings
- **Responsive Design** - Fully responsive with Bootstrap 5 for mobile, tablet, and desktop

### Admin Website
- **Dashboard** - Overview stats (users, products, orders, revenue)
- **User Management** - Full CRUD operations for all user types
- **Role-Based Access Control (RBAC)** - Create, read, update, delete roles with granular permissions
- **Product Moderation** - Approve, suspend, or remove product listings
- **Order Management** - Track and update order statuses
- **Category Management** - CRUD operations for product categories
- **Reports** - Analytics including top sellers, popular products, category distribution

### RBAC Permissions
| Permission | Super Admin | Admin | Moderator | User |
|---|:---:|:---:|:---:|:---:|
| Manage Users | Yes | Yes | No | No |
| Manage Products | Yes | Yes | Yes | No |
| Manage Orders | Yes | Yes | Yes | No |
| Manage Categories | Yes | Yes | No | No |
| Manage Roles | Yes | No | No | No |
| View Reports | Yes | Yes | Yes | No |

## Tech Stack

- **Frontend**: HTML5, CSS3, JavaScript (jQuery), Bootstrap 5
- **Backend**: PHP 8.x
- **Database**: MySQL 8.x
- **Icons**: Bootstrap Icons
- **Fonts**: Google Fonts (Poppins)

## Installation

### Prerequisites
- PHP 8.0+
- MySQL 8.0+
- Web server (Apache/Nginx) or PHP built-in server

### Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/mashuduthenga60-bit/Lisingo.git
   cd Lisingo
   ```

2. **Create the database**
   ```bash
   mysql -u root -p < database.sql
   ```

3. **Configure database connection**
   Edit `config/database.php` with your database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'lisingo_db');
   define('SITE_URL', 'http://localhost:8000');
   ```

4. **Create upload directories**
   ```bash
   mkdir -p uploads/products uploads/profiles
   chmod 777 uploads/products uploads/profiles
   ```

5. **Start the server**
   ```bash
   php -S localhost:8000
   ```

6. **Access the platform**
   - Main site: http://localhost:8000
   - Admin panel: http://localhost:8000/admin/

### Default Admin Credentials
- **Email**: admin@lisingo.co.za
- **Password**: Admin@123

## Project Structure

```
Lisingo/
├── admin/                  # Admin panel
│   ├── includes/           # Admin header, footer, sidebar
│   ├── index.php           # Dashboard
│   ├── users.php           # User management (CRUD)
│   ├── user_edit.php       # Edit user details
│   ├── products.php        # Product moderation
│   ├── orders.php          # Order management
│   ├── categories.php      # Category management (CRUD)
│   ├── roles.php           # RBAC role management (CRUD)
│   └── reports.php         # Analytics & reports
├── api/                    # AJAX API endpoints
│   └── cart.php            # Cart operations
├── assets/                 # Static assets
│   ├── css/style.css       # Custom styles
│   └── js/main.js          # Custom JavaScript
├── config/
│   └── database.php        # Database configuration
├── diagrams/               # Design diagrams
├── includes/               # Shared components
│   ├── header.php          # Main site header
│   ├── footer.php          # Main site footer
│   └── functions.php       # Helper functions
├── pages/                  # Main site pages
│   ├── register.php        # User registration
│   ├── login.php           # User login
│   ├── logout.php          # User logout
│   ├── products.php        # Browse products
│   ├── product_detail.php  # Product detail view
│   ├── sell.php            # Create listing
│   ├── edit_product.php    # Edit listing
│   ├── cart.php            # Shopping cart
│   ├── checkout.php        # Checkout process
│   ├── orders.php          # Order history
│   ├── my_products.php     # My listings
│   ├── profile.php         # User profile
│   ├── edit_profile.php    # Edit profile
│   ├── messages.php        # Messaging system
│   └── search.php          # Search results
├── uploads/                # User-uploaded files
│   ├── products/           # Product images
│   └── profiles/           # Profile images
├── database.sql            # Database schema & seeds
├── index.php               # Homepage
└── README.md
```

## Database Schema

The platform uses 9 tables:
- **roles** - RBAC roles with granular permissions
- **users** - User accounts with role assignments
- **categories** - Product categories (supports hierarchy)
- **products** - Product listings with multiple images
- **orders** - Purchase orders with status tracking
- **cart** - Shopping cart items
- **wishlist** - User wishlists
- **reviews** - Seller reviews and ratings
- **messages** - Buyer-seller messaging

## License

This project is developed for educational purposes as part of the ICT2612 module.

## Author

Mashudu Thenga - University of South Africa (UNISA)
