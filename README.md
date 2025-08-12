# ECommerce Platform

A complete e-commerce marketplace built with PHP, MySQL, Bootstrap, and JavaScript.

## 🚀 Features

### 🔐 Authentication & Security
- Role-based access control (User, Seller, Admin)
- Secure password hashing
- MD5 encryption support
- Session management
- Input sanitization and validation

### 🛒 User Features
- Product browsing with search, filter, and sort
- Shopping cart functionality
- Order management and tracking
- Profile and address management
- Product reviews and ratings
- Contact form with email support

### 🏪 Seller Features
- Seller dashboard with analytics
- Product management (CRUD operations)
- Multiple image upload for products
- Category management
- Order processing and status updates
- Inventory tracking
- Sales reports and statistics

### 👨‍💼 Admin Features
- System overview dashboard
- User management (all roles)
- Global product management
- Order oversight and management
- System statistics and reports
- Revenue tracking

### 🎨 UI/UX Features
- Responsive Bootstrap 5 design
- Font Awesome icons
- Beautiful CSS animations
- Toast notifications (Toastr.js)
- Mobile-friendly interface
- Loading states and smooth transitions

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

## 🛠️ Installation

### 1. Clone/Download the Project
```bash
git clone <repository-url>
cd ecommerce
```

### 2. Database Setup
1. Create a MySQL database named `ecommerce`
2. Import the database schema:
```bash
mysql -u your_username -p ecommerce < database/create_database.sql
```

### 3. Configuration
1. Update database credentials in `config/database.php`:
```php
private $host = 'localhost';
private $dbname = 'ecommerce';
private $username = 'your_username';
private $password = 'your_password';
```

### 4. File Permissions
Create upload directories and set permissions:
```bash
mkdir -p assets/images/products
chmod 755 assets/images/products
```

### 5. Web Server Configuration
Configure your web server to point to the project directory.

## 📁 Project Structure

```
ecommerce/
├── config/                 # Configuration files
│   ├── database.php        # Database connection
│   ├── session.php         # Session management
│   └── utils.php           # Utility functions
├── includes/               # Common includes
│   ├── header.php          # Header template
│   └── footer.php          # Footer template
├── assets/                 # Static assets
│   ├── css/style.css       # Custom styles
│   ├── js/main.js          # JavaScript functions
│   └── images/             # Image uploads
├── auth/                   # Authentication
│   ├── login.php           # Login page
│   ├── register.php        # Registration page
│   └── logout.php          # Logout handler
├── user/                   # User interface
│   ├── index.php           # User dashboard
│   ├── products.php        # Product catalog
│   ├── product_details.php # Product details
│   ├── cart.php            # Shopping cart
│   ├── checkout.php        # Checkout process
│   ├── orders.php          # Order history
│   ├── profile.php         # User profile
│   └── contact.php         # Contact form
├── seller/                 # Seller interface
│   ├── index.php           # Seller dashboard
│   ├── products.php        # Product management
│   ├── orders.php          # Order management
│   └── profile.php         # Seller profile
├── admin/                  # Admin interface
│   ├── index.php           # Admin dashboard
│   ├── users.php           # User management
│   ├── products.php        # Product oversight
│   ├── orders.php          # Order management
│   └── profile.php         # Admin profile
├── ajax/                   # AJAX endpoints
│   ├── add_to_cart.php     # Add to cart
│   ├── get_cart_count.php  # Get cart count
│   ├── update_cart.php     # Update cart
│   ├── remove_from_cart.php# Remove from cart
│   └── search_products.php # Product search
├── pages/                  # Static pages
│   ├── about.php           # About us
│   ├── contact.php         # Contact page
│   ├── faq.php             # FAQ
│   ├── privacy.php         # Privacy policy
│   └── terms.php           # Terms of service
├── database/               # Database files
│   └── create_database.sql # Database schema
└── index.php               # Main entry point
```

## 🔧 Key Features Explained

### Authentication System
- Role-based access control with three user types
- Secure password hashing using PHP's `password_hash()`
- Session management with automatic role-based redirects

### Product Management
- Multiple image support for products
- Category hierarchy with parent-child relationships
- Inventory tracking with automatic stock updates
- Featured products and bestsellers

### Shopping Cart
- AJAX-powered cart operations
- Real-time cart count updates
- Persistent cart across sessions
- Stock validation before checkout

### Order Processing
- Complete order workflow from cart to delivery
- Order status tracking
- Automatic inventory updates
- Order history and management

### Security Features
- SQL injection prevention with PDO prepared statements
- XSS protection with input sanitization
- CSRF protection considerations
- Secure file upload handling

## 🎨 Customization

### Styling
- Modify `assets/css/style.css` for custom styles
- Bootstrap 5 classes available throughout
- CSS custom properties for easy theming

### Functionality
- Add new AJAX endpoints in the `ajax/` directory
- Extend user roles in `config/session.php`
- Add new product fields in database and forms

## 🐛 Troubleshooting

### Common Issues
1. **Database Connection Error**: Check credentials in `config/database.php`
2. **Image Upload Fails**: Ensure `assets/images/products/` has write permissions
3. **Session Issues**: Verify PHP session configuration
4. **AJAX Errors**: Check browser console for JavaScript errors

### Debug Mode
Enable error reporting for development:
```php
// Add to top of index.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 📱 Browser Support

- Chrome 60+
- Firefox 55+
- Safari 11+
- Edge 79+

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

## 📞 Support

For support and questions:
- Email: duyh030774@gmail.com
- Documentation: Check the code comments
- Issues: Create an issue in the repository

## 🔮 Future Enhancements

- Payment gateway integration
- Advanced reporting and analytics
- Email notifications
- Product reviews and ratings
- Wishlist functionality
- Advanced search filters
- Multi-language support
- API development for mobile apps

---


**Built with ❤️ using PHP, MySQL, Bootstrap, and JavaScript**

