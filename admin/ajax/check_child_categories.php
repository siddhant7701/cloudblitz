<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['admin_id'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

require_once '../../includes/db_connect.php';

// Check if category_id is provided
if (!isset($_POST['category_id']) || empty($_POST['category_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Category ID is required']);
    exit;
}

$category_id = (int)$_POST['category_id'];

try {
    // Check if category has child categories
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM course_categories WHERE parent_id = ?");
    $stmt->execute([$category_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $has_children = ($result['count'] > 0);
    
    echo json_encode([
        'success' => true,
        'has_children' => $has_children,
        'child_count' => $result['count']
    ]);
    
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
