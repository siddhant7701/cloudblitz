<?php
// Include database connection
require_once '../includes/db_connect.php';

// Check if admin is logged in
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $title = trim($_POST['title'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $job_type = trim($_POST['job_type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $responsibilities = trim($_POST['responsibilities'] ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    
    // Basic validation
    if (empty($title) || empty($department) || empty($location) || empty($job_type) || empty($description)) {
        $_SESSION['error_message'] = "All required fields must be filled out";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO job_listings (title, department, location, job_type, description, responsibilities, requirements, created_at) 
                                   VALUES (:title, :department, :location, :job_type, :description, :responsibilities, :requirements, NOW())");
            
            $stmt->bindParam(':title', $title, PDO::PARAM_STR);
            $stmt->bindParam(':department', $department, PDO::PARAM_STR);
            $stmt->bindParam(':location', $location, PDO::PARAM_STR);
            $stmt->bindParam(':job_type', $job_type, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':responsibilities', $responsibilities, PDO::PARAM_STR);
            $stmt->bindParam(':requirements', $requirements, PDO::PARAM_STR);
            
            $stmt->execute();
            
            $_SESSION['success_message'] = "Job listing added successfully";
            header('Location: manage-careers.php');
            exit;
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error adding job listing: " . $e->getMessage();
        }
    }
}

// Page title
$pageTitle = "Add Job Listing - Admin Dashboard";
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
<div class="wrapper">
    <!-- Content Wrapper -->
    <div id="content">
        <div class="container-fluid">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <a href="manage-careers.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                    <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Job Listings
                </a>
            </div>

            <!-- Page Heading -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Add New Job Listing</h1>
            </div>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $_SESSION['error_message']; ?>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <!-- Content Row -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Job Details</h6>
                        </div>
                        <div class="card-body">
                            <form action="add-job.php" method="post">
                                <div class="row mb-4">
                                    <div class="col-md-4 mb-3">
                                        <label for="title" class="form-label font-weight-bold">Job Title *</label>
                                        <input type="text" id="title" name="title" required
                                               class="form-control">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="department" class="form-label font-weight-bold">Department *</label>
                                        <select id="department" name="department" required
                                                class="form-control">
                                            <option value="">Select Department</option>
                                            <option value="SALES">SALES</option>
                                            <option value="MARKETING">MARKETING</option>
                                            <option value="OPERATION">OPERATION</option>
                                            <option value="ACCOUNT & FINANCE">ACCOUNT & FINANCE</option>
                                            <option value="TRAINERS">TRAINERS</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="location" class="form-label font-weight-bold">Location *</label>
                                        <select id="location" name="location" required
                                                class="form-control">
                                            <option value="">Select Location</option>
                                            <option value="NAGPUR">NAGPUR</option>
                                            <option value="PUNE, WAKAD">PUNE, WAKAD</option>
                                            <option value="PUNE, KOTHRUD">PUNE, KOTHRUD</option>
                                            <option value="INDORE">INDORE</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="job_type" class="form-label font-weight-bold">Job Type *</label>
                                        <select id="job_type" name="job_type" required
                                                class="form-control">
                                            <option value="">Select Job Type</option>
                                            <option value="FULL TIME">FULL TIME</option>
                                            <option value="PART TIME">PART TIME</option>
                                            <option value="CONTRACT">CONTRACT</option>
                                            <option value="INTERNSHIP">INTERNSHIP</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="description" class="form-label font-weight-bold">Job Description *</label>
                                    <textarea id="description" name="description" rows="5" required
                                              class="form-control"></textarea>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="responsibilities" class="form-label font-weight-bold">Responsibilities</label>
                                    <textarea id="responsibilities" name="responsibilities" rows="5"
                                              class="form-control"></textarea>
                                    <small class="text-muted">Enter each responsibility on a new line</small>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="requirements" class="form-label font-weight-bold">Requirements</label>
                                    <textarea id="requirements" name="requirements" rows="5"
                                              class="form-control"></textarea>
                                    <small class="text-muted">Enter each requirement on a new line</small>
                                </div>
                                
                                <div class="text-right">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-plus-circle"></i> Add Job Listing
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Include Bootstrap JS and jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom scripts for sidebar toggle -->
<!-- <script>
    $(document).ready(function () {
        $('#sidebarCollapse').on('click', function () {
            $('#sidebar').toggleClass('active');
            $('#content').toggleClass('active');
            $(this).toggleClass('active');
        });
    });
</script> -->
<script src="js/admin.js"></script>
        </div>
    </div>  
</body>
</html>
