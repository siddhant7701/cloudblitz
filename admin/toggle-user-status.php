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

try {
    // Get current status
    $stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
    $stmt->bindParam(1, $user_id);
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user) {
        // Toggle status
        $new_status = ($user['status'] === 'active') ? 'inactive' : 'active';
        
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->bindParam(1, $new_status);
        $stmt->bindParam(2, $user_id);
        $stmt->execute();
        
        // Set success message
        $_SESSION['success_message'] = "User status updated successfully!";
    } else {
        // Set error message
        $_SESSION['error_message'] = "User not found.";
    }
} catch(PDOException $e) {
    // Set error message
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
}

// Redirect back to users page
header('Location: users.php#regular-users');
exit;
