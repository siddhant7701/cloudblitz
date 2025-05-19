<?php
// Include database connection
require_once '../includes/db_connect.php';

// Check if admin is logged in
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// Handle job deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $jobId = $_GET['delete'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM job_listings WHERE id = :id");
        $stmt->bindParam(':id', $jobId, PDO::PARAM_INT);
        $stmt->execute();
        
        $_SESSION['success_message'] = "Job listing deleted successfully";
        header('Location: manage-careers.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error deleting job listing: " . $e->getMessage();
    }
}

// Get all job listings
try {
    $stmt = $conn->prepare("SELECT * FROM job_listings ORDER BY created_at DESC");
    $stmt->execute();
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $jobs = [];
    $_SESSION['error_message'] = "Error fetching job listings: " . $e->getMessage();
}

// Page title
$pageTitle = "Manage Careers - Admin Dashboard";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Posts - CloudBlitz Admin</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.22/css/dataTables.bootstrap4.min.css">
    
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
            
<div class="container mt-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 fw-bold">Manage Job Listings</h1>
        <a href="add-job.php" class="btn" style="background-color: #ff7700; color: white;">Add New Job</a>
    </div>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success mb-4" role="alert">
            <p class="mb-0"><?php echo $_SESSION['success_message']; ?></p>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger mb-4" role="alert">
            <p class="mb-0"><?php echo $_SESSION['error_message']; ?></p>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    
    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th scope="col">Title</th>
                        <th scope="col">Department</th>
                        <th scope="col">Location</th>
                        <th scope="col">Type</th>
                        <th scope="col">Date Posted</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($jobs) > 0): ?>
                        <?php foreach ($jobs as $job): ?>
                            <tr>
                                <td>
                                    <div class="fw-medium"><?php echo htmlspecialchars($job['title']); ?></div>
                                </td>
                                <td>
                                    <div class="text-muted"><?php echo htmlspecialchars($job['department']); ?></div>
                                </td>
                                <td>
                                    <div class="text-muted"><?php echo htmlspecialchars($job['location']); ?></div>
                                </td>
                                <td>
                                    <div class="text-muted"><?php echo htmlspecialchars($job['job_type']); ?></div>
                                </td>
                                <td>
                                    <div class="text-muted"><?php echo date('M d, Y', strtotime($job['created_at'])); ?></div>
                                </td>
                                <td>
                                    <a href="view-applications.php?job_id=<?php echo $job['id']; ?>" class="btn btn-sm btn-primary view-request" >
                                                                                                <i class="fas fa-eye"></i>
Applications</a>
                                    <a href="edit-job.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-success update-status" >                                                        <i class="fas fa-edit"></i>
</a>
                                    <a href="manage-careers.php?delete=<?php echo $job['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this job listing?')">
                                                                                                <i class="fas fa-trash"></i>
</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">No job listings found</td>
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
<script src="js/admin.js"></script>
</body>
</html>