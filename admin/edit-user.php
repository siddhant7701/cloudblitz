<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

require_once '../includes/db_connect.php';

// Get user ID from URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no ID provided, redirect to users page
if ($user_id === 0) {
    header('Location: users.php');
    exit;
}

// Get user data
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bindParam(1, $user_id);
    $stmt->execute();
    $user = $stmt->fetch();
    
    if (!$user) {
        // User not found, redirect to users page
        header('Location: users.php');
        exit;
    }
} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $status = $_POST['status'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $bio = isset($_POST['bio']) ? trim($_POST['bio']) : $user['bio'];
    
    // Validate input
    if (empty($name) || empty($email)) {
        $error_message = "Name and email are required.";
    } else {
        try {
            // Check if email already exists (excluding current user)
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
            $stmt->bindParam(1, $email);
            $stmt->bindParam(2, $user_id);
            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $error_message = "Email already exists. Please use a different email.";
            } else {
                // Handle profile image upload
                $profile_image = $user['profile_image'];
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                    $filename = $_FILES['profile_image']['name'];
                    $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    
                    if (in_array($file_ext, $allowed)) {
                        // Create uploads directory if it doesn't exist
                        $upload_dir = '../uploads/users/';
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        // Generate unique filename
                        $new_filename = uniqid() . '.' . $file_ext;
                        $destination = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $destination)) {
                            // Delete old image if exists
                            if ($profile_image && file_exists('../' . $profile_image)) {
                                unlink('../' . $profile_image);
                            }
                            $profile_image = 'uploads/users/' . $new_filename;
                        } else {
                            $error_message = "Failed to upload image.";
                        }
                    } else {
                        $error_message = "Invalid file type. Allowed types: jpg, jpeg, png, gif.";
                    }
                }
                
                if (!isset($error_message)) {
                    // Update user
                    if (!empty($password)) {
                        // Password is being updated
                        if ($password !== $confirm_password) {
                            $error_message = "Passwords do not match.";
                        } elseif (strlen($password) < 6) {
                            $error_message = "Password must be at least 6 characters long.";
                        } else {
                            // Hash password
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            
                            // Update user with new password
                            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ?, profile_image = ?, bio = ?, status = ?, updated_at = NOW() WHERE id = ?");
                            $stmt->bindParam(1, $name);
                            $stmt->bindParam(2, $email);
                            $stmt->bindParam(3, $hashed_password);
                            $stmt->bindParam(4, $profile_image);
                            $stmt->bindParam(5, $bio);
                            $stmt->bindParam(6, $status);
                            $stmt->bindParam(7, $user_id);
                        }
                    } else {
                        // Update user without changing password
                        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, profile_image = ?, bio = ?, status = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->bindParam(1, $name);
                        $stmt->bindParam(2, $email);
                        $stmt->bindParam(3, $profile_image);
                        $stmt->bindParam(4, $bio);
                        $stmt->bindParam(5, $status);
                        $stmt->bindParam(6, $user_id);
                    }
                    
                    if (!isset($error_message) && $stmt->execute()) {
                        $success_message = "User updated successfully!";
                        
                        // Refresh user data
                        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                        $stmt->bindParam(1, $user_id);
                        $stmt->execute();
                        $user = $stmt->fetch();
                    } else if (!isset($error_message)) {
                        $error_message = "Error updating user: " . $conn->errorInfo()[2];
                    }
                }
            }
        } catch(PDOException $e) {
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
    <title>Edit User - Admin Panel</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="css/admin.css">
    
    <style>
        .preview-image {
            max-width: 150px;
            max-height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-top: 10px;
            border: 2px solid #4e73df;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Page Content -->
        <div id="content">
            <!-- Navbar -->
            <?php include 'includes/navbar.php'; ?>
            
            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Edit User</h1>
                    <a href="users.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Users
                    </a>
                </div>
                
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">User Details</h6>
                    </div>
                    <div class="card-body">
                        <form action="edit-user.php?id=<?php echo $user_id; ?>" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Full Name</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="email">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="password">Password</label>
                                        <input type="password" class="form-control" id="password" name="password">
                                        <small class="form-text text-muted">Leave blank to keep current password. New password must be at least 6 characters long.</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="confirm_password">Confirm Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="status">Status</label>
                                        <select class="form-control" id="status" name="status" required>
                                            <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="profile_image">Profile Image</label>
                                        <input type="file" class="form-control-file" id="profile_image" name="profile_image" accept="image/*" onchange="previewImage(this)">
                                        <small class="form-text text-muted">Upload a new profile picture (optional).</small>
                                        
                                        <div id="image-preview">
                                            <?php if (!empty($user['profile_image'])): ?>
                                                <img src="../<?php echo htmlspecialchars($user['profile_image']); ?>" class="preview-image" alt="Profile Image">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="bio">Bio</label>
                                        <textarea class="form-control" id="bio" name="bio" rows="5"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                        <small class="form-text text-muted">A short description about the user (optional).</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update User
                                </button>
                                <a href="users.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
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
    
    <!-- Admin JS -->
    <script src="js/admin.js"></script>
    
    <script>
        function previewImage(input) {
            var preview = document.getElementById('image-preview');
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                
                reader.onload = function(e) {
                    var img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'preview-image';
                    preview.appendChild(img);
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>