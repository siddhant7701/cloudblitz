  
# ☁️ CloudBlitz Learning Management System ☁️

[![PHP Version](https://img.shields.io/badge/PHP-7.2%2B-blue.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange.svg)](https://www.mysql.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

![CloudBlitz LMS](https://placeholder.svg?height=300&width=800&query=CloudBlitz+Modern+Learning+Platform)

**A powerful, feature-rich learning management system for educational institutions and online course providers**

[Features](#features) • [Installation](#installation) • [Pages Documentation](#pages-documentation) • [Admin Guide](#admin-guide) • [Customization](#customization) • [Security Best Practices](#-security-best-practices) • [Contributing](#-contributing) • [Project Structure](#-project-structure) • [License](#-license) • [Support](#-support)

</div>

---

## ✨ Features

### 🧑‍🎓 For Students
- **Course Enrollment** - Seamless browsing and registration for courses
- **Interactive Learning Dashboard** - Track progress with visual indicators
- **Structured Curriculum** - Access modules, lessons, and resources
- **Career Portal** - Discover job opportunities in relevant fields
- **Support System** - Request counseling and assistance

### 👨‍🏫 For Instructors
- **Course Management** - Create and organize educational content
- **Student Tracking** - Monitor engagement and performance
- **Content Publishing** - Share knowledge through the blog system
- **Communication Hub** - Connect with students effectively

### 👑 For Administrators
- **Centralized Control Panel** - Manage all aspects of the platform
- **Comprehensive User Management** - Handle all user types and permissions
- **Content Moderation** - Oversee courses, blogs, and other content
- **Job Board Administration** - Create and manage career listings
- **System Configuration** - Customize platform settings and appearance

---

## 🚀 Installation

### Prerequisites
- PHP 7.2 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled (for clean URLs)

### Step-by-Step Setup

1. **Clone the repository**
   \`\`\`bash
   git clone https://github.com/siddhnat7701/cloudblitz.git
   cd cloudblitz
   \`\`\`

2. **Database Configuration**
   - Create a MySQL database named `cloudblitz`
   \`\`\`sql
   CREATE DATABASE cloudblitz CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   \`\`\`
   - Import the database schema (if available)
   \`\`\`bash
   mysql -u username -p cloudblitz &lt; database/cloudblitz.sql
   \`\`\`
   - Configure database connection in `includes/db_connect.php`:
   \`\`\`php
   $servername = "localhost";
   $username = "your_db_username";
   $password = "your_db_password";
   $dbname = "cloudblitz";
   \`\`\`

3. **Server Configuration**
   - Point your web server's document root to the project's root directory
   - Ensure these directories have write permissions:
     - `/uploads`
     - `/temp`
     - `/logs`

4. **Access the Application**
   - Frontend: `http://yourdomain.com` or `http://localhost/cloudblitz`
   - Admin Panel: `http://yourdomain.com/admin` or `http://localhost/cloudblitz/admin`
   - Default admin credentials:
     - Username: `admin`
     - Password: `admin123` (change immediately after first login)

---

## 📄 Pages Documentation

### 🏠 Homepage (`index.php`)
The homepage serves as the main entry point for users, featuring:

- **Hero Section**: Showcases featured courses and platform benefits
- **Course Categories**: Displays popular course categories with icons
- **Featured Courses**: Highlights top-rated or featured courses
- **Testimonials**: Shows student testimonials and success stories
- **Blog Preview**: Displays recent blog posts
- **Call-to-Action**: Encourages user registration or course exploration

**Technical Details**:
- Connects to database via `includes/db_connect.php`
- Loads header and footer from `includes/header.php` and `includes/footer.php`
- Uses JavaScript in `js/script.js` for carousel and interactive elements

### 📚 Courses Page (`courses.php`)
The courses page lists all available courses with filtering options:

- **Search Functionality**: Allows users to search for specific courses
- **Category Filters**: Enables filtering courses by category
- **Sorting Options**: Sorts courses by popularity, date, or price
- **Pagination**: Displays courses in paginated format
- **Quick View**: Shows course preview on hover

**Technical Details**:
- Retrieves course data from the `courses` table
- Implements AJAX filtering via `js/script.js`
- Responsive grid layout adapts to different screen sizes

### 📖 Single Course Page (`course.php`)
Displays detailed information about a specific course:

- **Course Header**: Shows title, instructor, rating, and price
- **Course Description**: Provides detailed course information
- **Curriculum**: Lists modules and lessons in an accordion format
- **Instructor Profile**: Displays information about the course instructor
- **Reviews**: Shows student reviews and ratings
- **Related Courses**: Suggests similar courses
- **Enrollment Button**: Allows students to enroll in the course

**Technical Details**:
- Retrieves course data using the course ID from URL parameter
- Loads curriculum from `course_modules` and `course_lessons` tables
- Implements video preview functionality for selected lessons

### 📝 Blog Listing Page (`blogs.php`)
Lists all blog posts with filtering and search options:

- **Featured Posts**: Highlights important or popular articles
- **Category Navigation**: Allows filtering posts by category
- **Search Functionality**: Enables searching for specific topics
- **Pagination**: Organizes posts in paginated format
- **Author Information**: Shows post authors and publication dates

**Technical Details**:
- Retrieves blog posts from the `blog_posts` table
- Implements category filtering via GET parameters
- Uses responsive masonry layout for post display

### 📰 Single Blog Post Page (`blog-post.php`)
Displays a complete blog article with related content:

- **Article Header**: Shows title, author, date, and featured image
- **Content Body**: Displays the full article with formatting
- **Social Sharing**: Allows sharing the post on social media
- **Comments Section**: Enables user discussion
- **Related Posts**: Suggests similar articles
- **Author Bio**: Provides information about the author

**Technical Details**:
- Retrieves post content using the post ID from URL parameter
- Implements comment system with moderation
- Uses syntax highlighting for code snippets if present

### 👥 About Page (`about.php`)
Presents information about the platform and its mission:

- **Mission Statement**: Explains the platform's purpose and goals
- **Team Section**: Introduces key team members
- **History Timeline**: Shows the platform's development journey
- **Partners/Clients**: Displays logos of affiliated organizations
- **Testimonials**: Features success stories and feedback

**Technical Details**:
- Static content with dynamic testimonials from the database
- Implements parallax scrolling effects
- Uses CSS animations for the timeline feature

### 💼 Careers Page (`careers.php`)
Lists job opportunities and career resources:

- **Job Listings**: Displays available positions
- **Filter Options**: Allows filtering by job type, location, etc.
- **Application Form**: Enables users to apply for positions
- **Career Resources**: Provides career development content
- **Company Benefits**: Highlights benefits of joining the organization

**Technical Details**:
- Retrieves job listings from the `jobs` table
- Implements application form submission via AJAX
- Uses filtering system for job categories and types

### 📞 Contact Page (`contact.php`)
Provides contact information and communication channels:

- **Contact Form**: Allows users to send inquiries
- **Map Integration**: Shows physical location(s)
- **Contact Information**: Lists email, phone, and address details
- **Social Media Links**: Connects to social media profiles
- **FAQ Section**: Answers common questions

**Technical Details**:
- Implements form validation with JavaScript
- Processes form submissions via `process-contact.php`
- Uses Google Maps API for location display

### 💬 Counseling Request Page (`process-counselling.php`)
Enables students to request academic counseling:

- **Request Form**: Collects student information and counseling needs
- **Topic Selection**: Allows selecting specific counseling topics
- **Scheduling Options**: Provides time slot selection
- **Confirmation System**: Confirms submission and next steps

**Technical Details**:
- Saves requests to the `counselling_requests` table
- Sends email notifications to administrators
- Implements calendar integration for scheduling

### 🔍 Job Details Page (`job-details.php`)
Displays detailed information about a specific job posting:

- **Job Description**: Provides comprehensive role information
- **Requirements**: Lists qualifications and skills needed
- **Application Process**: Explains how to apply
- **Company Information**: Gives context about the employer
- **Similar Jobs**: Suggests related opportunities

**Technical Details**:
- Retrieves job data using the job ID from URL parameter
- Implements application tracking system
- Uses structured data markup for better SEO

---

## 🛠️ Admin Guide

### Dashboard (`admin/index.php`)
The admin dashboard provides an overview of platform activities:

- **Statistics Overview**: Shows key metrics (users, courses, revenue)
- **Recent Activities**: Displays latest platform actions
- **Quick Actions**: Provides shortcuts to common tasks
- **System Notifications**: Alerts about important events

**Technical Details**:
- Aggregates data from multiple database tables
- Implements real-time updates via AJAX
- Uses Chart.js for statistical visualizations

### User Management
Comprehensive user administration system:

#### Admin Users (`admin/add-admin-user.php`, `admin/edit-admin-user.php`, `admin/users.php`)
- Add, edit, and manage administrator accounts
- Set permissions and access levels
- Track admin activities

#### Instructors (`admin/add-instructors.php`, `admin/edit-instructor.php`, `admin/instructors.php`)
- Manage instructor profiles and permissions
- Review instructor applications
- Assign courses to instructors

#### Students (managed through `admin/users.php`)
- View and manage student accounts
- Track enrollment and progress
- Handle student requests and issues

### Course Management

#### Course Listing (`admin/courses.php`)
- View all courses with filtering options
- Manage course visibility and status
- Track enrollment statistics

#### Course Creation/Editing (`admin/add-course.php`, `admin/edit-course.php`)
- Create new courses with detailed information
- Set pricing, categories, and prerequisites
- Upload course images and promotional materials

#### Curriculum Management
- **Modules** (`admin/course-modules.php`): Create and organize course modules
- **Lessons** (`admin/course-lessons.php`): Add lessons with various content types
- **Curriculum** (`admin/course-curriculum.php`): Arrange the complete course structure

### Blog Management

#### Blog Posts (`admin/blog-posts.php`)
- View and manage all blog articles
- Filter posts by category, author, or status
- Track post performance metrics

#### Post Creation/Editing (`admin/blog-post-add.php`, `admin/blog-post-edit.php`)
- Create and edit blog content with rich text editor
- Schedule posts for future publication
- Set categories and tags for better organization

### Career Management

#### Job Listings (`admin/manage-careers.php`)
- View and manage all job postings
- Track application statistics
- Set job visibility and status

#### Job Creation/Editing (`admin/add-job.php`, `admin/edit-job.php`)
- Create detailed job descriptions
- Set requirements and qualifications
- Define application process and deadlines

### Communication Management

#### Messages (`admin/messages.php`, `admin/view-message.php`)
- View and respond to user messages
- Filter messages by status or department
- Set up automated responses

#### Counseling Requests (`admin/counselling-requests.php`)
- Manage student counseling requests
- Assign counselors to requests
- Track request status and outcomes

### System Settings (`admin/settings.php`)
- Configure general platform settings
- Customize email templates
- Manage payment gateways
- Set up backup and security options

---

## 🎨 Customization

### Theme Customization
- Modify CSS files in the `css/` directory
- Update color schemes in `css/style.css`
- Customize responsive breakpoints for different devices

### Content Customization
- Edit static content in PHP files
- Update images in the `images/` directory
- Modify email templates in the appropriate directory

### Functionality Extensions
- Add new features by creating additional PHP files
- Extend existing functionality by modifying core files
- Implement hooks system for plugin-like extensions

---

## 🔒 Security Best Practices

1. **Change Default Credentials**
   - Immediately change the default admin username and password
   - Use strong, unique passwords for all accounts

2. **Secure Your Installation**
   - Implement SSL/TLS for secure data transmission
   - Set proper file permissions (755 for directories, 644 for files)
   - Keep all software components updated

3. **Data Protection**
   - Regularly backup your database and files
   - Implement proper input validation and sanitization
   - Use prepared statements for all database queries

4. **Access Control**
   - Implement proper role-based access control
   - Use session timeout for inactive users
   - Log and monitor suspicious activities

---

## 🤝 Contributing

We welcome contributions to improve CloudBlitz! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

Please ensure your code follows our coding standards and includes appropriate documentation.

---

  
**CloudBlitz LMS** - Empowering Education Through Technology

[⬆ Back to Top](#-cloudblitz-learning-management-system-)

</div>
