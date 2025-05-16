<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

require_once '../includes/db_connect.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $status = $_POST['status'];
    
    // Validate input
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } else {
        try {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->bindParam(1, $email);
            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $error_message = "Email already exists. Please use a different email.";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, status, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->bindParam(1, $name);
                $stmt->bindParam(2, $email);
                $stmt->bindParam(3, $hashed_password);
                $stmt->bindParam(4, $status);
                
                if ($stmt->execute()) {
                    $success_message = "User added successfully!";
                    // Clear form data
                    $name = $email = $password = $confirm_password = '';
                    $status = 'active';
                } else {
                    $error_message = "Error adding user: " . $conn->errorInfo()[2];
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
    <title>Add User - Admin Panel</title>
    
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
                <h1>Add New User</h1>
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
                    <form action="add-user.php" method="POST">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required>
                            <small>Password must be at least 6 characters long.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" required>
                                <option value="active" <?php echo (isset($status) && $status === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo (isset($status) && $status === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn primary">Add User</button>
                            <button type="reset" class="btn secondary">Reset</button>
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
