<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

require_once '../includes/db_connect.php';

// Get current user data
try {
    $stmt = $conn->prepare("SELECT * FROM admin_users WHERE id = ?");
    $stmt->bindParam(1, $_SESSION['admin_id']);
    $stmt->execute();
    $user = $stmt->fetch();
    
    if (!$user) {
        // User not found, redirect to login
        session_destroy();
        header('Location: index.php');
        exit;
    }
} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate input
    if (empty($name) || empty($email)) {
        $error_message = "Name and email are required.";
    } else {
        try {
            // Check if email already exists (excluding current user)
            $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_users WHERE email = ? AND id != ?");
            $stmt->bindParam(1, $email);
            $stmt->bindParam(2, $_SESSION['admin_id']);
            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $error_message = "Email already exists. Please use a different email.";
            } else {
                // If changing password
                if (!empty($new_password)) {
                    // Verify current password
                    if (!password_verify($current_password, $user['password'])) {
                        $error_message = "Current password is incorrect.";
                    } elseif ($new_password !== $confirm_password) {
                        $error_message = "New passwords do not match.";
                    } elseif (strlen($new_password) < 6) {
                        $error_message = "New password must be at least 6 characters long.";
                    } else {
                        // Hash new password
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        
                        // Update user with new password
                        $stmt = $conn->prepare("UPDATE admin_users SET name = ?, email = ?, password = ? WHERE id = ?");
                        $stmt->bindParam(1, $name);
                        $stmt->bindParam(2, $email);
                        $stmt->bindParam(3, $hashed_password);
                        $stmt->bindParam(4, $_SESSION['admin_id']);
                    }
                } else {
                    // Update user without changing password
                    $stmt = $conn->prepare("UPDATE admin_users SET name = ?, email = ? WHERE id = ?");
                    $stmt->bindParam(1, $name);
                    $stmt->bindParam(2, $email);
                    $stmt->bindParam(3, $_SESSION['admin_id']);
                }
                
                if (!isset($error_message) && $stmt->execute()) {
                    $success_message = "Profile updated successfully!";
                    
                    // Update session name
                    $_SESSION['admin_name'] = $name;
                    
                    // Refresh user data
                    $stmt = $conn->prepare("SELECT * FROM admin_users WHERE id = ?");
                    $stmt->bindParam(1, $_SESSION['admin_id']);
                    $stmt->execute();
                    $user = $stmt->fetch();
                } else if (!isset($error_message)) {
                    $error_message = "Error updating profile: " . $conn->errorInfo()[2];
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
    <title>My Profile - Admin Panel</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="css/admin.css">
    
    <style>
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-right: 20px;
        }
        
        .profile-info h2 {
            margin-bottom: 5px;
        }
        
        .profile-info p {
            margin-bottom: 0;
            color: #666;
        }
        
        .profile-role {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            margin-left: 10px;
            color: white;
        }
        
        .profile-role.admin {
            background-color: var(--primary);
        }
        
        .profile-role.editor {
            background-color: var(--info);
        }
        
        .profile-role.super-admin {
            background-color: #6f42c1;
        }
        
        .nav-tabs {
            margin-bottom: 20px;
        }
        
        .tab-content {
            padding: 20px;
            background-color: white;
            border: 1px solid #dee2e6;
            border-top: none;
            border-radius: 0 0 0.25rem 0.25rem;
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
                    <h1 class="h3 mb-0 text-gray-800">My Profile</h1>
                </div>
                
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="profile-info">
                                <h2>
                                    <?php echo htmlspecialchars($user['name']); ?>
                                    <?php if ($user['id'] == 1): ?>
                                        <span class="profile-role super-admin">Super Admin</span>
                                    <?php else: ?>
                                        <span class="profile-role <?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span>
                                    <?php endif; ?>
                                </h2>
                                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                                <p><i class="fas fa-user-tag"></i> Username: <?php echo htmlspecialchars($user['username']); ?></p>
                                <p><i class="fas fa-clock"></i> Last Login: <?php echo $user['last_login'] ? date('F j, Y, g:i a', strtotime($user['last_login'])) : 'Never'; ?></p>
                            </div>
                        </div>
                        
                        <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="edit-tab" data-toggle="tab" href="#edit" role="tab">Edit Profile</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="password-tab" data-toggle="tab" href="#password" role="tab">Change Password</a>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="profileTabsContent">
                            <!-- Edit Profile Tab -->
                            <div class="tab-pane fade show active" id="edit" role="tabpanel">
                                <form action="profile.php" method="POST" id="profile-form">
                                    <div class="form-group">
                                        <label for="name">Full Name</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="email">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="username">Username</label>
                                        <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                        <small class="form-text text-muted">Username cannot be changed.</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Update Profile
                                        </button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Change Password Tab -->
                            <div class="tab-pane fade" id="password" role="tabpanel">
                                <form action="profile.php" method="POST" id="password-form">
                                    <!-- Hidden fields to preserve profile data -->
                                    <input type="hidden" name="name" value="<?php echo htmlspecialchars($user['name']); ?>">
                                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                                    
                                    <div class="form-group">
                                        <label for="current_password">Current Password</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="new_password">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        <small class="form-text text-muted">Password must be at least 6 characters long.</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="confirm_password">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-key"></i> Change Password
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
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
        $(document).ready(function() {
            // Activate tab based on hash in URL
            var hash = window.location.hash;
            if (hash) {
                $('.nav-tabs a[href="' + hash + '"]').tab('show');
            }
            
            // Change hash on tab change
            $('.nav-tabs a').on('shown.bs.tab', function (e) {
                window.location.hash = e.target.hash;
            });
        });
    </script>
</body>
</html>
