<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

// Check if user has admin privileges
if ($_SESSION['admin_role'] !== 'admin') {
    header('Location: dashboard.php');
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

// Check if trying to edit super admin while not being super admin
if ($user_id === 1 && $_SESSION['admin_id'] !== 1) {
    header('Location: users.php');
    exit;
}

// Get user data
try {
    $stmt = $conn->prepare("SELECT * FROM admin_users WHERE id = ?");
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
    $name = $_POST['name'] ?? '';
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $role = $_POST['role'] ?? 'editor';
    $status = $_POST['status'] ?? 'active';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate input
    if (empty($name) || empty($username) || empty($email)) {
        $error_message = "Name, username, and email are required.";
    } else {
        try {
            // Check if username already exists (excluding current user)
            $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_users WHERE username = ? AND id != ?");
            $stmt->bindParam(1, $username);
            $stmt->bindParam(2, $user_id);
            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $error_message = "Username already exists. Please choose a different username.";
            } else {
                // Check if email already exists (excluding current user)
                $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_users WHERE email = ? AND id != ?");
                $stmt->bindParam(1, $email);
                $stmt->bindParam(2, $user_id);
                $stmt->execute();
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $error_message = "Email already exists. Please use a different email.";
                } else {
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
                            $stmt = $conn->prepare("UPDATE admin_users SET username = ?, name = ?, email = ?, password = ?, role = ?, status = ? WHERE id = ?");
                            $stmt->bindParam(1, $username);
                            $stmt->bindParam(2, $name);
                            $stmt->bindParam(3, $email);
                            $stmt->bindParam(4, $hashed_password);
                            $stmt->bindParam(5, $role);
                            $stmt->bindParam(6, $status);
                            $stmt->bindParam(7, $user_id);
                        }
                    } else {
                        // Update user without changing password
                        $stmt = $conn->prepare("UPDATE admin_users SET username = ?, name = ?, email = ?, role = ?, status = ? WHERE id = ?");
                        $stmt->bindParam(1, $username);
                        $stmt->bindParam(2, $name);
                        $stmt->bindParam(3, $email);
                        $stmt->bindParam(4, $role);
                        $stmt->bindParam(5, $status);
                        $stmt->bindParam(6, $user_id);
                    }
                    
                    if (!isset($error_message) && $stmt->execute()) {
                        $success_message = "Admin user updated successfully!";
                        
                        // Refresh user data
                        $stmt = $conn->prepare("SELECT * FROM admin_users WHERE id = ?");
                        $stmt->bindParam(1, $user_id);
                        $stmt->execute();
                        $user = $stmt->fetch();
                    } else if (!isset($error_message)) {
                        $error_message = "Error updating admin user: " . $conn->errorInfo()[2];
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
    <title>Edit Admin User - Admin Panel</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="css/admin.css">
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
                    <h1 class="h3 mb-0 text-gray-800">
                        Edit Admin User
                        <?php if ($user_id == 1): ?>
                            <span class="badge badge-pill super-admin-badge">Super Admin</span>
                        <?php endif; ?>
                    </h1>
                    <a href="users.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Users
                    </a>
                </div>
                
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Admin User Details</h6>
                    </div>
                    <div class="card-body">
                        <form action="edit-admin-user.php?id=<?php echo $user_id; ?>" method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Full Name</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="username">Username</label>
                                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="password">Password</label>
                                        <input type="password" class="form-control" id="password" name="password">
                                        <small class="form-text text-muted">Leave blank to keep current password. New password must be at least 6 characters long.</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="confirm_password">Confirm Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="role">Role</label>
                                        <select class="form-control" id="role" name="role" required <?php echo ($user_id == 1) ? 'disabled' : ''; ?>>
                                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            <option value="editor" <?php echo $user['role'] === 'editor' ? 'selected' : ''; ?>>Editor</option>
                                        </select>
                                        <?php if ($user_id == 1): ?>
                                            <input type="hidden" name="role" value="admin">
                                            <small class="form-text text-muted">Super Admin role cannot be changed.</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="status">Status</label>
                                        <select class="form-control" id="status" name="status" required <?php echo ($user_id == 1) ? 'disabled' : ''; ?>>
                                            <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                        <?php if ($user_id == 1): ?>
                                            <input type="hidden" name="status" value="active">
                                            <small class="form-text text-muted">Super Admin status cannot be changed.</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Admin User
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
</body>
</html>
