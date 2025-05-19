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

// Get admin user info
$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'];
$admin_role = $_SESSION['admin_role'];

// Get dashboard statistics
// Total courses
$stmt = $conn->prepare("SELECT COUNT(*) FROM courses");
$stmt->execute();
$total_courses = $stmt->fetchColumn();

// Total blog posts
$stmt = $conn->prepare("SELECT COUNT(*) FROM blog_posts");
$stmt->execute();
$total_posts = $stmt->fetchColumn();


$stmt = $conn->prepare("SELECT COUNT(*) FROM counselling_requests WHERE status = 'pending'");
$stmt->execute();
$total_requests = $stmt->fetchColumn();


// Total users
$stmt = $conn->prepare("SELECT COUNT(*) FROM admin_users");
$stmt->execute();
$total_users = $stmt->fetchColumn();




// Recent contact messages
$stmt = $conn->prepare("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$recent_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Education Website</title>
    
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
                <h1 class="h3 mb-4 text-gray-800">Dashboard</h1>
                
                <!-- Stats Cards -->
                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Courses</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_courses; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-book fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Blog Posts</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_posts; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-newspaper fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            New Counselling Requests</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_requests; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-newspaper fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Users</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_users; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-newspaper fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    
                    
                    
                    <!-- Recent Messages -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Recent Messages</h6>
                                <a href="messages.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Subject</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_messages as $message): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($message['name']); ?></td>
                                                <td><?php echo htmlspecialchars($message['email']); ?></td>
                                                <td><?php echo htmlspecialchars($message['subject']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($message['created_at'])); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                            
                                            <?php if (count($recent_messages) === 0): ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No recent messages</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                     <!-- Quick Actions -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <a href="add-course.php" class="btn btn-primary btn-block w-100">
                                            <i class="fas fa-plus-circle me-2"></i> Add New Course
                                        </a>
                                    </div>
                                 
                                    <div class="col-md-6 mb-3">
                                        <a href="messages.php" class="btn btn-warning btn-block w-100">
                                            <i class="fas fa-envelope me-2"></i> Check Messages
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- System Info -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">System Info</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <strong>PHP Version:</strong> <?php echo phpversion(); ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Database:</strong> MySQL
                                </div>
                                <div>
                                    <strong>Last Login:</strong> <?php echo date('M d, Y H:i'); ?>
                                </div>
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
    
    <!-- Custom JS -->
    <script src="js/admin.js"></script>
</body>
</html>