<?php 
// Include the config file if not already included
if (!isset($nav_items)) {
    include_once '../config.php';
}

// Determine current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Main Navigation -->
<nav class="main-navigation bg-white">
    <div class="container">
        <!-- Mobile Navigation -->
        <div class="collapse navbar-collapse d-md-none" id="mainNavigation">
            <ul class="navbar-nav mobile-nav">
                <?php foreach ($nav_items as $label => $url): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == $url) ? 'active' : ''; ?>" href="<?php echo $url; ?>">
                        <?php echo $label; ?>
                    </a>
                </li>
                <?php endforeach; ?>
                <li class="nav-item mt-3">
                    <a href="#contact-form" class="btn btn-primary rounded-pill px-4 w-100">LETS TALK <i class="fas fa-arrow-right ms-2"></i></a>
                </li>
            </ul>
        </div>
        
        <!-- Desktop Navigation -->
        <div class="desktop-nav d-none d-md-block">
            <ul class="nav flex-column main-menu">
                <?php foreach ($nav_items as $label => $url): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == $url) ? 'active' : ''; ?>" href="<?php echo $url; ?>">
                        <?php echo $label; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</nav>