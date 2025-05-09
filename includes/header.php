<?php 
// Include the config file if not already included
if (!isset($site_config)) {
    include_once '../config.php';
}

// Determine current page for active state in navigation
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' | ' . $site_config['site_name'] : $site_config['site_name']; ?></title>
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : $site_config['site_description']; ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Page-specific CSS -->
    <?php if (isset($page_specific_css)): ?>
    <link rel="stylesheet" href="<?php echo $page_specific_css; ?>">
    <?php endif; ?>
</head>
<body>
    <!-- Header content goes here -->
    <header class="main-header bg-white py-2 shadow-sm">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3 col-6">
                    <a href="index.php" class="logo-link">
                        <img src="assets/images/logo.png" alt="<?php echo $site_config['site_name']; ?>" class="img-fluid logo">
                    </a>
                </div>
                <div class="col-md-9 col-6 d-flex justify-content-end">
                    <div class="d-none d-md-block">
                        <a href="#contact-form" class="btn btn-primary rounded-pill px-4">LETS TALK <i class="fas fa-arrow-right ms-2"></i></a>
                    </div>
                    <button class="navbar-toggler ms-3 d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                </div>
            </div>
        </div>
    </header>
    <!-- Main navigation will be included separately -->