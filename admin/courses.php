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

// Process delete request
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    try {
        // First check if the course exists
        $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            // Delete the course
            $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
            $stmt->execute([$id]);
            
            $_SESSION['success'] = "Course deleted successfully.";
        } else {
            $_SESSION['error'] = "Course not found.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header('Location: courses.php');
    exit;
}

// Get all courses with category and instructor names
try {
    $stmt = $conn->prepare("
        SELECT c.*, cc.name as category_name, i.name as instructor_name 
        FROM courses c 
        LEFT JOIN course_categories cc ON c.category_id = cc.id 
        LEFT JOIN instructors i ON c.instructor_id = i.id
        ORDER BY c.created_at DESC
    ");
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    $courses = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses - CloudBlitz Admin</title>
    
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
                    <h1 class="h3 mb-0 text-gray-800">Courses</h1>
                    <a href="add-course.php" class="d-none d-sm-inline-block btn btn-primary shadow-sm">
                        <i class="fas fa-plus fa-sm text-white-50"></i> Add New Course
                    </a>
                </div>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success']; 
                        unset($_SESSION['success']);
                        ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error']; 
                        unset($_SESSION['error']);
                        ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">All Courses</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Instructor</th>
                                        <th>Price</th>
                                        <th>Level</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($courses as $course): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($course['title']); ?></td>
                                        <td><?php echo htmlspecialchars($course['category_name'] ?? 'Uncategorized'); ?></td>
                                        <td><?php echo htmlspecialchars($course['instructor_name'] ?? 'Not Assigned'); ?></td>
                                        <td>
                                            <?php 
                                            if (isset($course['price']) && $course['price'] > 0) {
                                                echo '$' . number_format($course['price'], 2);
                                            } else {
                                                echo 'Free';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars(ucfirst($course['level'] ?? 'All Levels')); ?></td>
                                        <td>
                                            <?php if (isset($course['status']) && $course['status'] === 'published'): ?>
                                                <span class="badge badge-success">Published</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Draft</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="edit-course.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="courses.php?delete=<?php echo $course['id']; ?>" class="btn btn-sm btn-danger btn-delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (count($courses) === 0): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No courses found</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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
    
    <!-- Custom JS -->
    <script src="js/admin.js"></script>
</body>
</html>