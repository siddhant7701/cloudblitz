<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'includes/db_connect.php';

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Get site settings from database
$stmt = $conn->prepare("SELECT * FROM site_settings WHERE id = 1");
$stmt->execute();
$site_settings = $stmt->fetch();

// Get main menu items
$stmt = $conn->prepare("SELECT * FROM menu_items WHERE menu_location = 'main' ORDER BY display_order");
$stmt->execute();
$menu_items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?><?php echo htmlspecialchars($site_settings['site_name']); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($site_settings['site_description']); ?>">
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($site_settings['favicon_path']); ?>" type="image/x-icon">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Internal CSS for Sidebar Navigation -->
    <style>
        /* General styles */
        body {
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
            transition: margin-left 0.3s ease;
            margin-left: 0;
            position: relative;
        }
        
        body.sidebar-open {
            margin-left: 250px;
        }
        
        /* Sidebar styles */
        .sidebar {
            height: 100%;
            width: 0;
            position: fixed;
            z-index: 1000;
            top: 0;
            left: 0;
            background-color: #ffffff;
            overflow-x: hidden;
            transition: 0.3s;
            padding-top: 60px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        
        .sidebar.open {
            width: 250px;
        }
        
        .sidebar .close-btn {
            position: absolute;
            top: 15px;
            right: 25px;
            font-size: 24px;
            color: #333;
            cursor: pointer;
            transition: 0.3s;
        }
        
        .sidebar .close-btn:hover {
            color: #ff5722;
        }
        
        .sidebar .nav-item {
            padding: 5px 15px;
            width: 100%;
            display: block;
            transition: 0.3s;
        }
        
        .sidebar .nav-link {
            color: #333;
            text-decoration: none;
            font-size: 16px;
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-left: 3px solid transparent;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
            font-size: 18px;
            color: #ff5722;
            width: 24px;
            text-align: center;
        }
        
        .sidebar .nav-link:hover {
            color: #ff5722;
            background-color: rgba(255, 87, 34, 0.05);
            border-left: 3px solid #ff5722;
            transform: translateX(5px);
        }
        
        .sidebar .nav-item.active .nav-link {
            color: #ff5722;
            border-left: 3px solid #ff5722;
            background-color: rgba(255, 87, 34, 0.08);
            font-weight: 500;
        }
        
        /* Sidebar header with logo */
        .sidebar-header {
            padding: 0 20px 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .sidebar-header img {
            max-width: 150px;
            height: auto;
        }
        
        /* Toggle button */
        .menu-toggle {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1050;
            cursor: pointer;
            background-color: #ff5722;
            color: #ffffff;
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .menu-toggle:hover {
            background-color: #e64a19;
            transform: scale(1.05);
        }
        
        .menu-toggle i {
            font-size: 20px;
            color: #ffffff;
        }
        
        /* Logo positioning */
        .navbar-brand {
            margin-left: 60px;
            transition: margin-left 0.3s ease;
        }
        
        /* Top bar with contact info and social links */
        .top-bar {
            background-color: #f8f9fa;
            padding: 10px 0;
            color: #333;
            position: relative;
            z-index: 99;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .top-bar a {
            color: #333;
            transition: color 0.3s ease;
        }
        
        .top-bar a:hover {
            color: #ff5722;
            text-decoration: none;
        }
        
        .contact-info {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-wrap: wrap;
        }
        
        .contact-info li {
            margin-right: 20px;
            font-size: 14px;
        }
        
        .contact-info i {
            margin-right: 5px;
            color: #ff5722;
        }
        
        .social-links {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: flex-end;
        }
        
        .social-links li {
            margin-left: 15px;
        }
        
        .social-links a {
            display: inline-block;
            width: 32px;
            height: 32px;
            background-color: rgba(255, 87, 34, 0.1);
            border-radius: 50%;
            text-align: center;
            line-height: 32px;
            transition: all 0.3s ease;
            color: #ff5722;
        }
        
        .social-links a:hover {
            background-color: #ff5722;
            color: #ffffff;
            transform: translateY(-3px);
        }
        
        /* Main navbar modifications */
        .navbar {
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 15px 0;
            transition: all 0.3s ease;
            position: relative;
        }
        
        /* Social links visibility control */
        .social-links-container {
            position: fixed;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 990;
            display: flex;
            flex-direction: column;
            transition: opacity 0.3s ease;
        }
        
        .social-links-container .social-links {
            flex-direction: column;
        }
        
        .social-links-container .social-links li {
            margin: 5px 0;
        }
        
        .hide-social {
            opacity: 0;
            pointer-events: none;
        }
        
        /* Sidebar section titles */
        .sidebar-section-title {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #999;
            padding: 10px 25px;
            margin-top: 15px;
        }
        
        /* For mobile devices */
        @media (max-width: 768px) {
            .navbar-brand {
                margin-left: 70px;
            }
            
            .social-links-container {
                display: none;
            }
            
            .contact-info {
                justify-content: center;
                margin-bottom: 10px;
            }
            
            .social-links {
                justify-content: center;
            }
            
            .sidebar {
                padding-top: 50px;
            }
            
            .sidebar .nav-link {
                padding: 10px 15px;
            }
            
            .menu-toggle {
                top: 15px;
                left: 15px;
                width: 40px;
                height: 40px;
            }
        }
    </style>
    
    <!-- Google Analytics -->
    <?php if (!empty($site_settings['google_analytics_id'])): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($site_settings['google_analytics_id']); ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '<?php echo htmlspecialchars($site_settings['google_analytics_id']); ?>');
    </script>
    <?php endif; ?>
</head>
<body>
    <!-- Menu Toggle Button -->
    <button class="menu-toggle" id="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Sidebar Navigation -->
    <div class="sidebar" id="sidebar">
        <span class="close-btn" onclick="toggleSidebar()"><i class="fas fa-times"></i></span>
        
        <div class="sidebar-header">
            <img src="images/logo.png" alt="<?php echo htmlspecialchars($site_settings['site_name']); ?>">
        </div>
        
        <div class="sidebar-section-title">Main Navigation</div>
        <ul class="navbar-nav">
            <li class="nav-item <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                <a class="nav-link" href="index.php"><i class="fas fa-home"></i> Home</a>
            </li>
            <li class="nav-item <?php echo $current_page === 'courses.php' ? 'active' : ''; ?>">
                <a class="nav-link" href="courses.php"><i class="fas fa-graduation-cap"></i> Courses</a>
            </li>
            <li class="nav-item <?php echo $current_page === 'blogs.php' ? 'active' : ''; ?>">
                <a class="nav-link" href="blogs.php"><i class="fas fa-blog"></i> Blog</a>
            </li>
            <li class="nav-item <?php echo $current_page === 'about.php' ? 'active' : ''; ?>">
                <a class="nav-link" href="about.php"><i class="fas fa-info-circle"></i> About Us</a>
            </li>
            <li class="nav-item <?php echo $current_page === 'contact.php' ? 'active' : ''; ?>">
                <a class="nav-link" href="contact.php"><i class="fas fa-envelope"></i> Contact</a>
            </li>
            
           
        </ul>
        </div>
    
    <!-- Header -->
    <header class="site-header">
        <!-- <div class="top-bar">
            <div class="container">
                <div class="row">
                    <div class="col-md-6">
                        <ul class="contact-info">
                            <?php if (!empty($site_settings['contact_email'])): ?>
                            <li><i class="fa fa-envelope"></i> <a href="mailto:<?php echo htmlspecialchars($site_settings['contact_email']); ?>"><?php echo htmlspecialchars($site_settings['contact_email']); ?></a></li>
                            <?php endif; ?>
                            
                            <?php if (!empty($site_settings['contact_phone'])): ?>
                            <li><i class="fa fa-phone"></i> <a href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9+]/', '', $site_settings['contact_phone'])); ?>"><?php echo htmlspecialchars($site_settings['contact_phone']); ?></a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="social-links">
                            <?php if (!empty($site_settings['facebook_url'])): ?>
                            <li><a href="<?php echo htmlspecialchars($site_settings['facebook_url']); ?>" target="_blank"><i class="fab fa-facebook-f"></i></a></li>
                            <?php endif; ?>
                            
                            <?php if (!empty($site_settings['twitter_url'])): ?>
                            <li><a href="<?php echo htmlspecialchars($site_settings['twitter_url']); ?>" target="_blank"><i class="fab fa-twitter"></i></a></li>
                            <?php endif; ?>
                            
                            <?php if (!empty($site_settings['instagram_url'])): ?>
                            <li><a href="<?php echo htmlspecialchars($site_settings['instagram_url']); ?>" target="_blank"><i class="fab fa-instagram"></i></a></li>
                            <?php endif; ?>
                            
                            <?php if (!empty($site_settings['linkedin_url'])): ?>
                            <li><a href="<?php echo htmlspecialchars($site_settings['linkedin_url']); ?>" target="_blank"><i class="fab fa-linkedin-in"></i></a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div> -->
        
        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container">
                <a class="navbar-brand" href="index.php">
                    <img src="images/logo.png" alt="<?php echo htmlspecialchars($site_settings['site_name']); ?>" class="logo">
                </a>
                 <div class="ml-auto d-none d-lg-block">
                      <div class="ml-auto d-none d-lg-block">
                    <a href="contact.php" class="btn btn-sm btn-primary">Let's Talk</a>
                </div>
            </div>
        </nav>
    </header>
    
    <!-- Add JavaScript for sidebar functionality -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Remove any existing sidebars or menu toggles that might be duplicated
            const existingSidebars = document.querySelectorAll('.sidebar:not(#sidebar)');
            existingSidebars.forEach(sidebar => {
                sidebar.parentNode.removeChild(sidebar);
            });
            
            const existingToggles = document.querySelectorAll('.menu-toggle:not(#menu-toggle)');
            existingToggles.forEach(toggle => {
                toggle.parentNode.removeChild(toggle);
            });
            
            // Setup intersection observer for footer
            const footerObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    const fixedSocialLinks = document.getElementById('fixed-social-links');
                    if (fixedSocialLinks && entry.isIntersecting) {
                        fixedSocialLinks.classList.add('hide-social');
                    } else if (fixedSocialLinks) {
                        fixedSocialLinks.classList.remove('hide-social');
                    }
                });
            }, { threshold: 0.1 });
            
            // Observe the footer when it exists
            const footer = document.querySelector('footer');
            if (footer) {
                footerObserver.observe(footer);
            }
        });
        
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menu-toggle');
            const body = document.body;
            
            if (sidebar && menuToggle) {
                sidebar.classList.toggle('open');
                menuToggle.classList.toggle('active');
                body.classList.toggle('sidebar-open');
                
                // Toggle hamburger icon
                const hamburgerIcon = menuToggle.querySelector('i');
                if (hamburgerIcon) {
                    if (sidebar.classList.contains('open')) {
                        hamburgerIcon.classList.remove('fa-bars');
                        hamburgerIcon.classList.add('fa-times');
                    } else {
                        hamburgerIcon.classList.remove('fa-times');
                        hamburgerIcon.classList.add('fa-bars');
                    }
                }
            }
        }
        
        // Close sidebar when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menu-toggle');
            
            if (sidebar && menuToggle && sidebar.classList.contains('open') && 
                !sidebar.contains(event.target) && 
                event.target !== menuToggle && 
                !menuToggle.contains(event.target)) {
                toggleSidebar();
            }
        });
    </script>
    
    <!-- Main Content -->
    <main>