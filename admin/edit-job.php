<?php
// Include database connection
require_once '../includes/db_connect.php';

// Check if admin is logged in
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// Check if job ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: manage-careers.php');
    exit;
}

$jobId = $_GET['id'];

// Get job details
try {
    $stmt = $conn->prepare("SELECT * FROM job_listings WHERE id = :id");
    $stmt->bindParam(':id', $jobId, PDO::PARAM_INT);
    $stmt->execute();
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) {
        $_SESSION['error_message'] = "Job listing not found";
        header('Location: manage-careers.php');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error fetching job details: " . $e->getMessage();
    header('Location: manage-careers.php');
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
            $stmt = $conn->prepare("UPDATE job_listings SET 
                                    title = :title, 
                                    department = :department, 
                                    location = :location, 
                                    job_type = :job_type, 
                                    description = :description, 
                                    responsibilities = :responsibilities, 
                                    requirements = :requirements 
                                    WHERE id = :id");
            
            $stmt->bindParam(':title', $title, PDO::PARAM_STR);
            $stmt->bindParam(':department', $department, PDO::PARAM_STR);
            $stmt->bindParam(':location', $location, PDO::PARAM_STR);
            $stmt->bindParam(':job_type', $job_type, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':responsibilities', $responsibilities, PDO::PARAM_STR);
            $stmt->bindParam(':requirements', $requirements, PDO::PARAM_STR);
            $stmt->bindParam(':id', $jobId, PDO::PARAM_INT);
            
            $stmt->execute();
            
            $_SESSION['success_message'] = "Job listing updated successfully";
            header('Location: manage-careers.php');
            exit;
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error updating job listing: " . $e->getMessage();
        }
    }
}

// Page title
$pageTitle = "Edit Job Listing - Admin Dashboard";

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
<link rel="stylesheet" href="./css/admin.css">

<div class="container mt-4 mb-4">
    <div class="mb-4">
        <a href="manage-careers.php" class="text-decoration-none d-flex align-items-center" style="color: #ff7700;">
            <svg class="me-1" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Job Listings
        </a>
    </div>

    <h1 class="h2 fw-bold mb-4">Edit Job Listing</h1>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger mb-4" role="alert">
            <p class="mb-0"><?php echo $_SESSION['error_message']; ?></p>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    
    <div class="card p-4">
        <form action="edit-job.php?id=<?php echo $jobId; ?>" method="post">
            <div class="row mb-4">
                <div class="col-md-6 mb-3 mb-md-0">
                    <label for="title" class="form-label fw-medium">Job Title *</label>
                    <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($job['title']); ?>"
                           class="form-control">
                </div>
                
                <div class="col-md-6">
                    <label for="department" class="form-label fw-medium">Department *</label>
                    <select id="department" name="department" required
                            class="form-select">
                        <option value="">Select Department</option>
                        <option value="SALES" <?php echo $job['department'] === 'SALES' ? 'selected' : ''; ?>>SALES</option>
                        <option value="MARKETING" <?php echo $job['department'] === 'MARKETING' ? 'selected' : ''; ?>>MARKETING</option>
                        <option value="OPERATION" <?php echo $job['department'] === 'OPERATION' ? 'selected' : ''; ?>>OPERATION</option>
                        <option value="ACCOUNT & FINANCE" <?php echo $job['department'] === 'ACCOUNT & FINANCE' ? 'selected' : ''; ?>>ACCOUNT & FINANCE</option>
                        <option value="TRAINERS" <?php echo $job['department'] === 'TRAINERS' ? 'selected' : ''; ?>>TRAINERS</option>
                    </select>
                </div>
                
                <div class="col-md-6 mt-3">
                    <label for="location" class="form-label fw-medium">Location *</label>
                    <select id="location" name="location" required
                            class="form-select">
                        <option value="">Select Location</option>
                        <option value="NAGPUR" <?php echo $job['location'] === 'NAGPUR' ? 'selected' : ''; ?>>NAGPUR</option>
                        <option value="PUNE, WAKAD" <?php echo $job['location'] === 'PUNE, WAKAD' ? 'selected' : ''; ?>>PUNE, WAKAD</option>
                        <option value="PUNE, KOTHRUD" <?php echo $job['location'] === 'PUNE, KOTHRUD' ? 'selected' : ''; ?>>PUNE, KOTHRUD</option>
                        <option value="INDORE" <?php echo $job['location'] === 'INDORE' ? 'selected' : ''; ?>>INDORE</option>
                    </select>
                </div>
                
                <div class="col-md-6 mt-3">
                    <label for="job_type" class="form-label fw-medium">Job Type *</label>
                    <select id="job_type" name="job_type" required
                            class="form-select">
                        <option value="">Select Job Type</option>
                        <option value="FULL TIME" <?php echo $job['job_type'] === 'FULL TIME' ? 'selected' : ''; ?>>FULL TIME</option>
                        <option value="PART TIME" <?php echo $job['job_type'] === 'PART TIME' ? 'selected' : ''; ?>>PART TIME</option>
                        <option value="CONTRACT" <?php echo $job['job_type'] === 'CONTRACT' ? 'selected' : ''; ?>>CONTRACT</option>
                        <option value="INTERNSHIP" <?php echo $job['job_type'] === 'INTERNSHIP' ? 'selected' : ''; ?>>INTERNSHIP</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label fw-medium">Job Description *</label>
                <textarea id="description" name="description" rows="5" required
                          class="form-control"><?php echo htmlspecialchars($job['description']); ?></textarea>
            </div>
            
            <div class="mb-3">
                <label for="responsibilities" class="form-label fw-medium">Responsibilities</label>
                <textarea id="responsibilities" name="responsibilities" rows="5"
                          class="form-control"><?php echo htmlspecialchars($job['responsibilities'] ?? ''); ?></textarea>
                <div class="form-text">Enter each responsibility on a new line</div>
            </div>
            
            <div class="mb-4">
                <label for="requirements" class="form-label fw-medium">Requirements</label>
                <textarea id="requirements" name="requirements" rows="5"
                          class="form-control"><?php echo htmlspecialchars($job['requirements'] ?? ''); ?></textarea>
                <div class="form-text">Enter each requirement on a new line</div>
            </div>
            
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn" style="background-color: #ff7700; color: white;">
                    Update Job Listing
                </button>
            </div>
        </form>
    </div>
</div>
</div>
</div>
</div>
<script src="js/admin.js"></script>
</body>
</html>
