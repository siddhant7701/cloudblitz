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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'editor';
    $status = $_POST['status'] ?? 'active';
    
    // Validate input
    if (empty($name) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } else {
        try {
            // Check if username already exists
            $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_users WHERE username = ?");
            $stmt->bindParam(1, $username);
            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $error_message = "Username already exists. Please choose a different username.";
            } else {
                // Check if email already exists
                $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_users WHERE email = ?");
                $stmt->bindParam(1, $email);
                $stmt->execute();
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $error_message = "Email already exists. Please use a different email.";
                } else {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert admin user
                    $stmt = $conn->prepare("INSERT INTO admin_users (username, password, name, email, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->bindParam(1, $username);
                    $stmt->bindParam(2, $hashed_password);
                    $stmt->bindParam(3, $name);
                    $stmt->bindParam(4, $email);
                    $stmt->bindParam(5, $role);
                    $stmt->bindParam(6, $status);
                    
                    if ($stmt->execute()) {
                        $success_message = "Admin user added successfully!";
                        // Clear form data
                        $name = $username = $email = $password = $confirm_password = '';
                        $role = 'editor';
                        $status = 'active';
                    } else {
                        $error_message = "Error adding admin user: " . $conn->errorInfo()[2];
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
    <title>Add Admin User - Admin Panel</title>
    
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
                    <h1 class="h3 mb-0 text-gray-800">Add Admin User</h1>
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
                        <form action="add-admin-user.php" method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Full Name</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="username">Username</label>
                                        <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="password">Password</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <small class="form-text text-muted">Password must be at least 6 characters long.</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="confirm_password">Confirm Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="role">Role</label>
                                        <select class="form-control" id="role" name="role" required>
                                            <option value="admin" <?php echo (isset($role) && $role === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                            <option value="editor" <?php echo (isset($role) && $role === 'editor') ? 'selected' : ''; ?>>Editor</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="status">Status</label>
                                        <select class="form-control" id="status" name="status" required>
                                            <option value="active" <?php echo (isset($status) && $status === 'active') ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo (isset($status) && $status === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-plus"></i> Add Admin User
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fas fa-undo"></i> Reset
                                </button>
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
