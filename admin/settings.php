<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// Include database connection
require_once '../includes/db_connect.php';

// Initialize messages
$error_message = null;
$success_message = null;

// Check if site_settings table exists, if not create it
try {
    $stmt = $conn->prepare("SHOW TABLES LIKE 'site_settings'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        // Create site_settings table if it doesn't exist
        $conn->exec("CREATE TABLE site_settings (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            site_name VARCHAR(100) NOT NULL,
            site_description TEXT DEFAULT NULL,
            logo_path VARCHAR(255) DEFAULT NULL,
            favicon_path VARCHAR(255) DEFAULT NULL,
            contact_email VARCHAR(100) DEFAULT NULL,
            contact_phone VARCHAR(50) DEFAULT NULL,
            address TEXT DEFAULT NULL,
            footer_about TEXT DEFAULT NULL,
            facebook_url VARCHAR(255) DEFAULT NULL,
            twitter_url VARCHAR(255) DEFAULT NULL,
            instagram_url VARCHAR(255) DEFAULT NULL,
            linkedin_url VARCHAR(255) DEFAULT NULL,
            google_analytics_id VARCHAR(50) DEFAULT NULL,
            map_url TEXT DEFAULT NULL
        )");
        
        // Insert default settings
        $stmt = $conn->prepare("INSERT INTO site_settings (
            site_name, 
            site_description, 
            contact_email, 
            contact_phone, 
            address, 
            footer_about,
            facebook_url, 
            twitter_url, 
            instagram_url, 
            linkedin_url,
            map_url
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $default_name = "CloudBlitz Education";
        $default_desc = "A platform for online learning and education";
        $default_email = "contact@example.com";
        $default_phone = "+1 234 567 8900";
        $default_address = "123 Education St, Learning City, 12345";
        $default_footer = "CloudBlitz Education provides high-quality online learning experiences for students worldwide.";
        $default_fb = "https://facebook.com/";
        $default_twitter = "https://twitter.com/";
        $default_insta = "https://instagram.com/";
        $default_linkedin = "https://linkedin.com/";
        $default_map = "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3022.2158462500784!2d-73.9867703!3d40.7484405!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c259a9b30eac9f%3A0x618bf9c14e7c5044!2sEmpire%20State%20Building!5e0!3m2!1sen!2sus!4v1609459772493!5m2!1sen!2sus";
        
        $stmt->execute([
            $default_name, 
            $default_desc, 
            $default_email, 
            $default_phone, 
            $default_address, 
            $default_footer,
            $default_fb, 
            $default_twitter, 
            $default_insta, 
            $default_linkedin,
            $default_map
        ]);
    }
    
    // Check if map_url column exists, if not add it
    $stmt = $conn->prepare("SHOW COLUMNS FROM site_settings LIKE 'map_url'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $conn->exec("ALTER TABLE site_settings ADD COLUMN map_url TEXT AFTER linkedin_url");
        // Set a default map URL
        $conn->exec("UPDATE site_settings SET map_url = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3022.2158462500784!2d-73.9867703!3d40.7484405!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c259a9b30eac9f%3A0x618bf9c14e7c5044!2sEmpire%20State%20Building!5e0!3m2!1sen!2sus!4v1609459772493!5m2!1sen!2sus' WHERE id = 1");
    }
    
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// Get current settings
try {
    $stmt = $conn->prepare("SELECT * FROM site_settings WHERE id = 1");
    $stmt->execute();
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If no settings exist, create default settings
    if (!$settings) {
        $stmt = $conn->prepare("INSERT INTO site_settings (
            site_name, 
            site_description, 
            contact_email, 
            contact_phone, 
            address, 
            footer_about,
            facebook_url, 
            twitter_url, 
            instagram_url, 
            linkedin_url,
            map_url
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $default_name = "CloudBlitz Education";
        $default_desc = "A platform for online learning and education";
        $default_email = "contact@example.com";
        $default_phone = "+1 234 567 8900";
        $default_address = "123 Education St, Learning City, 12345";
        $default_footer = "CloudBlitz Education provides high-quality online learning experiences for students worldwide.";
        $default_fb = "https://facebook.com/";
        $default_twitter = "https://twitter.com/";
        $default_insta = "https://instagram.com/";
        $default_linkedin = "https://linkedin.com/";
        $default_map = "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3022.2158462500784!2d-73.9867703!3d40.7484405!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c259a9b30eac9f%3A0x618bf9c14e7c5044!2sEmpire%20State%20Building!5e0!3m2!1sen!2sus!4v1609459772493!5m2!1sen!2sus";
        
        $stmt->execute([
            $default_name, 
            $default_desc, 
            $default_email, 
            $default_phone, 
            $default_address, 
            $default_footer,
            $default_fb, 
            $default_twitter, 
            $default_insta, 
            $default_linkedin,
            $default_map
        ]);
        
        // Get the newly created settings
        $stmt = $conn->prepare("SELECT * FROM site_settings WHERE id = 1");
        $stmt->execute();
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error_message = "Error fetching settings: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data with proper validation and sanitization
    $site_name = trim(filter_input(INPUT_POST, 'site_name', FILTER_SANITIZE_STRING));
    $site_description = trim(filter_input(INPUT_POST, 'site_description', FILTER_SANITIZE_STRING));
    $contact_email = trim(filter_input(INPUT_POST, 'contact_email', FILTER_SANITIZE_EMAIL));
    $contact_phone = trim(filter_input(INPUT_POST, 'contact_phone', FILTER_SANITIZE_STRING));
    $address = trim(filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING));
    $footer_about = trim(filter_input(INPUT_POST, 'footer_about', FILTER_SANITIZE_STRING));
    $facebook_url = trim(filter_input(INPUT_POST, 'facebook_url', FILTER_SANITIZE_URL));
    $twitter_url = trim(filter_input(INPUT_POST, 'twitter_url', FILTER_SANITIZE_URL));
    $instagram_url = trim(filter_input(INPUT_POST, 'instagram_url', FILTER_SANITIZE_URL));
    $linkedin_url = trim(filter_input(INPUT_POST, 'linkedin_url', FILTER_SANITIZE_URL));
    $google_analytics_id = trim(filter_input(INPUT_POST, 'google_analytics_id', FILTER_SANITIZE_STRING));
    $map_url = trim(filter_input(INPUT_POST, 'map_url', FILTER_SANITIZE_URL));
    
    // Basic validation
    if (empty($site_name)) {
        $error_message = "Site name is required";
    } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format";
    }
    
    // Handle logo upload
    $logo_path = isset($settings['logo_path']) ? $settings['logo_path'] : '';
    
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        
        if (in_array($_FILES['logo']['type'], $allowed_types)) {
            $logo_path = time() . '_' . basename($_FILES['logo']['name']);
            $upload_dir = "../images/";
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $upload_path = $upload_dir . $logo_path;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                // Logo uploaded successfully
                
                // Delete old logo if it exists
                if (!empty($settings['logo_path']) && file_exists($upload_dir . $settings['logo_path'])) {
                    unlink($upload_dir . $settings['logo_path']);
                }
            } else {
                $error_message = "Failed to upload logo. Error: " . $_FILES['logo']['error'];
            }
        } else {
            $error_message = "Invalid logo format. Allowed formats: JPG, JPEG, PNG, GIF.";
        }
    }
    
    // Handle favicon upload
    $favicon_path = isset($settings['favicon_path']) ? $settings['favicon_path'] : '';
    
    if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/x-icon'];
        
        if (in_array($_FILES['favicon']['type'], $allowed_types)) {
            $favicon_path = time() . '_' . basename($_FILES['favicon']['name']);
            $upload_dir = "../images/";
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $upload_path = $upload_dir . $favicon_path;
            
            if (move_uploaded_file($_FILES['favicon']['tmp_name'], $upload_path)) {
                // Favicon uploaded successfully
                
                // Delete old favicon if it exists
                if (!empty($settings['favicon_path']) && file_exists($upload_dir . $settings['favicon_path'])) {
                    unlink($upload_dir . $settings['favicon_path']);
                }
            } else {
                $error_message = "Failed to upload favicon. Error: " . $_FILES['favicon']['error'];
            }
        } else {
            $error_message = "Invalid favicon format. Allowed formats: JPG, JPEG, PNG, GIF, ICO.";
        }
    }
    
    if (!isset($error_message)) {
        // Update settings
        try {
            $stmt = $conn->prepare("UPDATE site_settings SET 
                site_name = ?, 
                site_description = ?, 
                logo_path = ?, 
                favicon_path = ?, 
                contact_email = ?, 
                contact_phone = ?, 
                address = ?, 
                footer_about = ?,
                facebook_url = ?, 
                twitter_url = ?, 
                instagram_url = ?, 
                linkedin_url = ?,
                google_analytics_id = ?,
                map_url = ?
                WHERE id = 1");
            
            if ($stmt->execute([
                $site_name, 
                $site_description, 
                $logo_path, 
                $favicon_path, 
                $contact_email, 
                $contact_phone, 
                $address, 
                $footer_about,
                $facebook_url, 
                $twitter_url, 
                $instagram_url, 
                $linkedin_url,
                $google_analytics_id,
                $map_url
            ])) {
                $success_message = "Settings updated successfully!";
                
                // Refresh settings data
                $stmt = $conn->prepare("SELECT * FROM site_settings WHERE id = 1");
                $stmt->execute();
                $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error_message = "Error updating settings: " . implode(", ", $stmt->errorInfo());
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

// For debugging - uncomment if needed to see what's happening
// echo "<pre>"; print_r($_POST); echo "</pre>";
// echo "<pre>"; print_r($_FILES); echo "</pre>";
// echo "<pre>"; print_r($settings); echo "</pre>";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings - CloudBlitz Admin</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Page Content -->
        <div id="content">
            <!-- Top Navbar -->
            <?php include 'includes/navbar.php'; ?>
            
            <!-- Main Content -->
            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Site Settings</h1>
                </div>
                
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">General Settings</h6>
                    </div>
                    <div class="card-body">
                        <form action="settings.php" method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="site_name">Site Name</label>
                                <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="site_description">Site Description</label>
                                <textarea class="form-control" id="site_description" name="site_description" rows="3" required><?php echo htmlspecialchars($settings['site_description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="logo">Site Logo</label>
                                <?php if (isset($settings['logo_path']) && !empty($settings['logo_path'])): ?>
                                    <div class="mb-3">
                                        <p class="mb-1">Current logo:</p>
                                        <img src="../images/<?php echo htmlspecialchars($settings['logo_path']); ?>" alt="Current logo" class="img-thumbnail" style="max-width: 200px;">
                                    </div>
                                <?php endif; ?>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="logo" name="logo">
                                    <label class="custom-file-label" for="logo">Choose file</label>
                                </div>
                                <small class="form-text text-muted">Recommended size: 200x60 pixels. Allowed formats: JPG, JPEG, PNG, GIF.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="favicon">Favicon</label>
                                <?php if (isset($settings['favicon_path']) && !empty($settings['favicon_path'])): ?>
                                    <div class="mb-3">
                                        <p class="mb-1">Current favicon:</p>
                                        <img src="../images/<?php echo htmlspecialchars($settings['favicon_path']); ?>" alt="Current favicon" class="img-thumbnail" style="max-width: 32px;">
                                    </div>
                                <?php endif; ?>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="favicon" name="favicon">
                                    <label class="custom-file-label" for="favicon">Choose file</label>
                                </div>
                                <small class="form-text text-muted">Recommended size: 32x32 pixels. Allowed formats: ICO, PNG, JPG.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="footer_about">Footer About Text</label>
                                <textarea class="form-control" id="footer_about" name="footer_about" rows="3"><?php echo htmlspecialchars($settings['footer_about'] ?? ''); ?></textarea>
                                <small class="form-text text-muted">Brief description that appears in the site footer.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="google_analytics_id">Google Analytics ID</label>
                                <input type="text" class="form-control" id="google_analytics_id" name="google_analytics_id" value="<?php echo htmlspecialchars($settings['google_analytics_id'] ?? ''); ?>">
                                <small class="form-text text-muted">Format: UA-XXXXXXXX-X</small>
                            </div>
                            
                            <h5 class="mt-4 mb-3">Contact Information</h5>
                            
                            <div class="form-group">
                                <label for="contact_email">Contact Email</label>
                                <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="contact_phone">Contact Phone</label>
                                <input type="text" class="form-control" id="contact_phone" name="contact_phone" value="<?php echo htmlspecialchars($settings['contact_phone'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($settings['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="map_url">Google Maps Embed URL</label>
                                <input type="text" class="form-control" id="map_url" name="map_url" value="<?php echo htmlspecialchars($settings['map_url'] ?? ''); ?>">
                                <small class="form-text text-muted">The iframe URL from Google Maps embed code</small>
                            </div>
                            
                            <h5 class="mt-4 mb-3">Social Media Links</h5>
                            
                            <div class="form-group">
                                <label for="facebook_url">Facebook</label>
                                <input type="url" class="form-control" id="facebook_url" name="facebook_url" value="<?php echo htmlspecialchars($settings['facebook_url'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="twitter_url">Twitter</label>
                                <input type="url" class="form-control" id="twitter_url" name="twitter_url" value="<?php echo htmlspecialchars($settings['twitter_url'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="instagram_url">Instagram</label>
                                <input type="url" class="form-control" id="instagram_url" name="instagram_url" value="<?php echo htmlspecialchars($settings['instagram_url'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="linkedin_url">LinkedIn</label>
                                <input type="url" class="form-control" id="linkedin_url" name="linkedin_url" value="<?php echo htmlspecialchars($settings['linkedin_url'] ?? ''); ?>">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <!-- Custom JS -->
    <script src="js/admin.js"></script>
    
    <script>
        // Update custom file input label with selected filename
        $('.custom-file-input').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').html(fileName);
        });
    </script>
</body>
</html>