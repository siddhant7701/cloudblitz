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
                        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ?, status = ? WHERE id = ?");
                        $stmt->bindParam(1, $name);
                        $stmt->bindParam(2, $email);
                        $stmt->bindParam(3, $hashed_password);
                        $stmt->bindParam(4, $status);
                        $stmt->bindParam(5, $user_id);
                    }
                } else {
                    // Update user without changing password
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, status = ? WHERE id = ?");
                    $stmt->bindParam(1, $name);
                    $stmt->bindParam(2, $email);
                    $stmt->bindParam(3, $status);
                    $stmt->bindParam(4, $user_id);
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
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <input type="checkbox" id="nav-toggle">
    
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Navbar -->
        <?php include 'includes/navbar.php'; ?>
        
        <main>
            <div class="page-header">
                <h1>Edit User</h1>
                <a href="users.php" class="btn secondary">
                    <span class="fas fa-arrow-left"></span> Back to Users
                </a>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="alert success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3>User Details</h3>
                </div>
                <div class="card-body">
                    <form action="edit-user.php?id=<?php echo $user_id; ?>" method="POST">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password">
                            <small>Leave blank to keep current password. New password must be at least 6 characters long.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password">
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" required>
                                <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn primary">Update User</button>
                            <a href="users.php" class="btn secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    
    <!-- Admin JS -->
    <script src="js/admin.js"></script>
</body>
</html>
