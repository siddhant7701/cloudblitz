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

// Check if settings table exists, if not create it
try {
    $stmt = $conn->prepare("SHOW TABLES LIKE 'settings'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        // Create settings table
        $conn->exec("CREATE TABLE settings (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            contact_email VARCHAR(255),
            contact_phone VARCHAR(50),
            address TEXT,
            facebook VARCHAR(255),
            twitter VARCHAR(255),
            instagram VARCHAR(255),
            linkedin VARCHAR(255),
            logo VARCHAR(255),
            favicon VARCHAR(255),
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // Insert default settings
        $stmt = $conn->prepare("INSERT INTO settings (title, description, contact_email, contact_phone, address, facebook, twitter, instagram, linkedin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $default_title = "CloudBlitz Education";
        $default_desc = "A platform for online learning and education";
        $default_email = "contact@example.com";
        $default_phone = "+1 234 567 8900";
        $default_address = "123 Education St, Learning City, 12345";
        $default_fb = "https://facebook.com/";
        $default_twitter = "https://twitter.com/";
        $default_insta = "https://instagram.com/";
        $default_linkedin = "https://linkedin.com/";
        
        $stmt->execute([$default_title, $default_desc, $default_email, $default_phone, $default_address, $default_fb, $default_twitter, $default_insta, $default_linkedin]);
    }
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// Get current settings
try {
    $stmt = $conn->prepare("SELECT * FROM settings WHERE id = 1");
    $stmt->execute();
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If no settings exist, create default settings
    if (!$settings) {
        $stmt = $conn->prepare("INSERT INTO settings (title, description, contact_email, contact_phone, address, facebook, twitter, instagram, linkedin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $default_title = "CloudBlitz Education";
        $default_desc = "A platform for online learning and education";
        $default_email = "contact@example.com";
        $default_phone = "+1 234 567 8900";
        $default_address = "123 Education St, Learning City, 12345";
        $default_fb = "https://facebook.com/";
        $default_twitter = "https://twitter.com/";
        $default_insta = "https://instagram.com/";
        $default_linkedin = "https://linkedin.com/";
        
        $stmt->execute([$default_title, $default_desc, $default_email, $default_phone, $default_address, $default_fb, $default_twitter, $default_insta, $default_linkedin]);
        
        // Get the newly created settings
        $stmt = $conn->prepare("SELECT * FROM settings WHERE id = 1");
        $stmt->execute();
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error_message = "Error fetching settings: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $contact_email = $_POST['contact_email'];
    $contact_phone = $_POST['contact_phone'];
    $address = $_POST['address'];
    $facebook = $_POST['facebook'];
    $twitter = $_POST['twitter'];
    $instagram = $_POST['instagram'];
    $linkedin = $_POST['linkedin'];
    
    // Handle logo upload
    $logo_name = isset($settings['logo']) ? $settings['logo'] : '';
    
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        
        if (in_array($_FILES['logo']['type'], $allowed_types)) {
            $logo_name = time() . '_' . $_FILES['logo']['name'];
            $upload_path = "../images/" . $logo_name;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                // Logo uploaded successfully
                
                // Delete old logo if it exists
                if (!empty($settings['logo']) && file_exists("../images/" . $settings['logo'])) {
                    unlink("../images/" . $settings['logo']);
                }
            } else {
                $error_message = "Failed to upload logo.";
            }
        } else {
            $error_message = "Invalid logo format. Allowed formats: JPG, JPEG, PNG, GIF.";
        }
    }
    
    // Handle favicon upload
    $favicon_name = isset($settings['favicon']) ? $settings['favicon'] : '';
    
    if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/x-icon'];
        
        if (in_array($_FILES['favicon']['type'], $allowed_types)) {
            $favicon_name = time() . '_' . $_FILES['favicon']['name'];
            $upload_path = "../images/" . $favicon_name;
            
            if (move_uploaded_file($_FILES['favicon']['tmp_name'], $upload_path)) {
                // Favicon uploaded successfully
                
                // Delete old favicon if it exists
                if (!empty($settings['favicon']) && file_exists("../images/" . $settings['favicon'])) {
                    unlink("../images/" . $settings['favicon']);
                }
            } else {
                $error_message = "Failed to upload favicon.";
            }
        } else {
            $error_message = "Invalid favicon format. Allowed formats: JPG, JPEG, PNG, GIF, ICO.";
        }
    }
    
    if (!isset($error_message)) {
        // Update settings
        try {
            $stmt = $conn->prepare("UPDATE settings SET title = ?, description = ?, contact_email = ?, contact_phone = ?, address = ?, facebook = ?, twitter = ?, instagram = ?, linkedin = ?, logo = ?, favicon = ? WHERE id = 1");
            
            if ($stmt->execute([$title, $description, $contact_email, $contact_phone, $address, $facebook, $twitter, $instagram, $linkedin, $logo_name, $favicon_name])) {
                $success_message = "Settings updated successfully!";
                
                // Refresh settings data
                $stmt = $conn->prepare("SELECT * FROM settings WHERE id = 1");
                $stmt->execute();
                $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error_message = "Error updating settings";
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}
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
                                <label for="title">Site Title</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($settings['title'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Site Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($settings['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="logo">Site Logo</label>
                                <?php if (isset($settings['logo']) && !empty($settings['logo'])): ?>
                                    <div class="mb-3">
                                        <p class="mb-1">Current logo:</p>
                                        <img src="../images/<?php echo htmlspecialchars($settings['logo']); ?>" alt="Current logo" class="img-thumbnail" style="max-width: 200px;">
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
                                <?php if (isset($settings['favicon']) && !empty($settings['favicon'])): ?>
                                    <div class="mb-3">
                                        <p class="mb-1">Current favicon:</p>
                                        <img src="../images/<?php echo htmlspecialchars($settings['favicon']); ?>" alt="Current favicon" class="img-thumbnail" style="max-width: 32px;">
                                    </div>
                                <?php endif; ?>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="favicon" name="favicon">
                                    <label class="custom-file-label" for="favicon">Choose file</label>
                                </div>
                                <small class="form-text text-muted">Recommended size: 32x32 pixels. Allowed formats: ICO, PNG, JPG.</small>
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
                            
                            <h5 class="mt-4 mb-3">Social Media Links</h5>
                            
                            <div class="form-group">
                                <label for="facebook">Facebook</label>
                                <input type="url" class="form-control" id="facebook" name="facebook" value="<?php echo htmlspecialchars($settings['facebook'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="twitter">Twitter</label>
                                <input type="url" class="form-control" id="twitter" name="twitter" value="<?php echo htmlspecialchars($settings['twitter'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="instagram">Instagram</label>
                                <input type="url" class="form-control" id="instagram" name="instagram" value="<?php echo htmlspecialchars($settings['instagram'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="linkedin">LinkedIn</label>
                                <input type="url" class="form-control" id="linkedin" name="linkedin" value="<?php echo htmlspecialchars($settings['linkedin'] ?? ''); ?>">
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