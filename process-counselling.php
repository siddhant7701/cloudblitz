<?php
// Include database connection
require_once 'includes/db_connect.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data and sanitize
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $created_at = date('Y-m-d H:i:s');
    $status = 'pending';

    // Validate required fields
    if (empty($name) || empty($email) || empty($phone)) {
        // Redirect back with error
        header('Location: ' . $_SERVER['HTTP_REFERER'] . '?error=missing_fields');
        exit;
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: ' . $_SERVER['HTTP_REFERER'] . '?error=invalid_email');
        exit;
    }

    try {
        // Insert into counselling_requests table
        $stmt = $conn->prepare("INSERT INTO counselling_requests (name, email, phone, course_id, message, created_at, status) 
                               VALUES (:name, :email, :phone, :course_id, :message, :created_at, :status)");
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':course_id', $course_id);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':created_at', $created_at);
        $stmt->bindParam(':status', $status);
        
        $stmt->execute();
        
        // Get course name for confirmation message
        $course_name = "our course";
        if ($course_id > 0) {
            $stmt = $conn->prepare("SELECT title FROM courses WHERE id = :id");
            $stmt->bindParam(':id', $course_id);
            $stmt->execute();
            $course = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($course) {
                $course_name = $course['title'];
            }
        }
        
        // Redirect with success message
        header('Location: ' . $_SERVER['HTTP_REFERER'] . '?success=1&course=' . urlencode($course_name));
        exit;
        
    } catch(PDOException $e) {
        // Log error
        error_log("Counselling request error: " . $e->getMessage());
        
        // Redirect with error
        header('Location: ' . $_SERVER['HTTP_REFERER'] . '?error=db_error');
        exit;
    }
} else {
    // If not POST request, redirect to home
    header('Location: index.php');
    exit;
}
?>
