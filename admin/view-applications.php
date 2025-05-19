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
if (!isset($_GET['job_id']) || empty($_GET['job_id'])) {
    header('Location: manage-careers.php');
    exit;
}

$jobId = $_GET['job_id'];

// Get job details
try {
    $stmt = $conn->prepare("SELECT title FROM job_listings WHERE id = :id");
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

// Get applications for this job
try {
    $stmt = $conn->prepare("SELECT * FROM job_applications WHERE job_id = :job_id ORDER BY applied_at DESC");
    $stmt->bindParam(':job_id', $jobId, PDO::PARAM_INT);
    $stmt->execute();
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $applications = [];
    $_SESSION['error_message'] = "Error fetching applications: " . $e->getMessage();
}

// Get site settings for location info
try {
    $stmt = $conn->prepare("SELECT address, contact_email FROM site_settings LIMIT 1");
    $stmt->execute();
    $siteSettings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $siteSettings = ['address' => '', 'contact_email' => ''];
    // No need to show error for this
}

// Handle email sending
function sendApprovalEmail($applicantEmail, $applicantName, $jobTitle, $interviewDate, $location, $fromEmail) {
    $subject = "Your Application for " . $jobTitle . " has been Accepted";
    
    $message = "Dear " . $applicantName . ",\n\n";
    $message .= "Congratulations! We are pleased to inform you that your application for the position of " . $jobTitle . " has been accepted.\n\n";
    $message .= "We would like to invite you for an interview on " . $interviewDate . " at our office.\n\n";
    $message .= "Location: " . $location . "\n\n";
    $message .= "Please confirm your attendance by replying to this email.\n\n";
    $message .= "Best regards,\nHR Department\n";
    
    $headers = "From: " . $fromEmail . "\r\n";
    $headers .= "Reply-To: " . $fromEmail . "\r\n";
    
    return mail($applicantEmail, $subject, $message, $headers);
}

// Handle application status update
if (isset($_POST['update_status']) && isset($_POST['application_id']) && isset($_POST['status'])) {
    $applicationId = $_POST['application_id'];
    $status = $_POST['status'];
    $interviewDate = isset($_POST['interview_date']) ? $_POST['interview_date'] : null;
    $notes = isset($_POST['notes']) ? $_POST['notes'] : null;
    
    try {
        $stmt = $conn->prepare("UPDATE job_applications SET status = :status, notes = :notes, updated_at = NOW() WHERE id = :id");
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
        $stmt->bindParam(':id', $applicationId, PDO::PARAM_INT);
        $stmt->execute();
        
        // Send email if status is Shortlisted
        if ($status === 'Shortlisted' && $interviewDate) {
            // Get applicant details
            $stmt = $conn->prepare("SELECT name, email FROM job_applications WHERE id = :id");
            $stmt->bindParam(':id', $applicationId, PDO::PARAM_INT);
            $stmt->execute();
            $applicant = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($applicant) {
                $fromEmail = !empty($siteSettings['contact_email']) ? $siteSettings['contact_email'] : 'hr@cloudblitz.com';
                $location = !empty($siteSettings['address']) ? $siteSettings['address'] : 'Main Office';
                
                sendApprovalEmail(
                    $applicant['email'],
                    $applicant['name'],
                    $job['title'],
                    $interviewDate,
                    $location,
                    $fromEmail
                );
                
                $_SESSION['success_message'] = "Application status updated and email sent to applicant";
            } else {
                $_SESSION['success_message'] = "Application status updated but failed to send email";
            }
        } else {
            $_SESSION['success_message'] = "Application status updated successfully";
        }
        
        header("Location: view-applications.php?job_id=$jobId");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error updating application status: " . $e->getMessage();
    }
}

// Handle application deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $applicationId = $_GET['delete'];
    
    try {
        // First, get the resume path to delete the file
        $stmt = $conn->prepare("SELECT resume_path FROM job_applications WHERE id = :id");
        $stmt->bindParam(':id', $applicationId, PDO::PARAM_INT);
        $stmt->execute();
        $application = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($application && !empty($application['resume_path']) && file_exists('../' . $application['resume_path'])) {
            unlink('../' . $application['resume_path']); // Delete the resume file
        }
        
        // Then delete the application record
        $stmt = $conn->prepare("DELETE FROM job_applications WHERE id = :id");
        $stmt->bindParam(':id', $applicationId, PDO::PARAM_INT);
        $stmt->execute();
        
        $_SESSION['success_message'] = "Application deleted successfully";
        header("Location: view-applications.php?job_id=$jobId");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error deleting application: " . $e->getMessage();
    }
}

// View resume handler
if (isset($_GET['view_resume']) && !empty($_GET['view_resume'])) {
    $applicationId = $_GET['view_resume'];
    
    try {
        $stmt = $conn->prepare("SELECT resume_path FROM job_applications WHERE id = :id");
        $stmt->bindParam(':id', $applicationId, PDO::PARAM_INT);
        $stmt->execute();
        $application = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($application && !empty($application['resume_path'])) {
            $resumePath = '../' . $application['resume_path'];
            
            if (file_exists($resumePath)) {
                // Output the file
                $fileInfo = pathinfo($resumePath);
                $extension = strtolower($fileInfo['extension']);
                
                // Set appropriate content type based on file extension
                switch ($extension) {
                    case 'pdf':
                        header('Content-Type: application/pdf');
                        break;
                    case 'doc':
                        header('Content-Type: application/msword');
                        break;
                    case 'docx':
                        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
                        break;
                    default:
                        header('Content-Type: application/octet-stream');
                }
                
                header('Content-Disposition: inline; filename="' . $fileInfo['basename'] . '"');
                header('Content-Length: ' . filesize($resumePath));
                readfile($resumePath);
                exit;
            }
        }
        
        // If the resume file is not found or doesn't exist
        $_SESSION['error_message'] = "Resume file not found";
        header("Location: view-applications.php?job_id=$jobId");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error retrieving resume: " . $e->getMessage();
        header("Location: view-applications.php?job_id=$jobId");
        exit;
    }
}

// Page title
$pageTitle = "Applications for " . htmlspecialchars($job['title']) . " - Admin Dashboard";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
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
    
    <style>
        /* Additional custom styles for improved UI */
        .application-card {
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .application-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .badge-pill {
            padding: 0.5em 0.8em;
        }
        
        .badge-pending {
            background-color: #FFC107;
            color: #212529;
        }
        
        .badge-reviewed {
            background-color: #17A2B8;
            color: #fff;
        }
        
        .badge-shortlisted {
            background-color: #28A745;
            color: #fff;
        }
        
        .badge-interviewed {
            background-color: #007BFF;
            color: #fff;
        }
        
        .badge-rejected {
            background-color: #DC3545;
            color: #fff;
        }
        
        .dropdown-menu {
            min-width: 250px;
            padding: 10px;
        }
        
        .dropdown-item {
            padding: 8px 15px;
            border-radius: 4px;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        
        .dropdown-divider {
            margin: 8px 0;
        }
        
        .resume-preview {
            width: 100%;
            height: 600px;
            border: none;
        }
        
        .action-btns .btn {
            margin-right: 5px;
            display: inline-flex;
            align-items: center;
        }
        
        .action-btns .btn i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Page Content -->
        <div id="content">
            <!-- Top Navbar -->
            <?php include 'includes/navbar.php'; ?>

            <div class="container-fluid mt-4 mb-4">
                <div class="mb-4">
                    <a href="manage-careers.php" class="text-decoration-none d-flex align-items-center" style="color: #ff7700;">
                        <i class="fas fa-arrow-left me-2"></i> Back to Job Listings
                    </a>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h2 fw-bold">Applications for: <?php echo htmlspecialchars($job['title']); ?></h1>
                    <div class="text-muted">
                        Total Applications: <span class="badge badge-primary"><?php echo count($applications); ?></span>
                    </div>
                </div>
                
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                        <?php echo $_SESSION['success_message']; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        <?php echo $_SESSION['error_message']; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <?php if (count($applications) > 0): ?>
                    <!-- Application Filter and Search -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <input type="text" id="applicationSearch" class="form-control" placeholder="Search applications...">
                                        <div class="input-group-append">
                                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-md-end mt-3 mt-md-0">
                                        <!-- <div class="dropdown mr-2">
                                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="filterDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fas fa-filter mr-1"></i> Filter
                                            </button>
                                            <div class="dropdown-menu" aria-labelledby="filterDropdown">
                                                <button class="dropdown-item filter-btn" data-filter="all">All Applications</button>
                                                <div class="dropdown-divider"></div>
                                                <button class="dropdown-item filter-btn" data-filter="Pending">Pending</button>
                                                <button class="dropdown-item filter-btn" data-filter="Reviewed">Reviewed</button>
                                                <button class="dropdown-item filter-btn" data-filter="Shortlisted">Shortlisted</button>
                                                <button class="dropdown-item filter-btn" data-filter="Interviewed">Interviewed</button>
                                                <button class="dropdown-item filter-btn" data-filter="Rejected">Rejected</button>
                                            </div>
                                        </div> -->
                                        <div class="dropdown">
                                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="sortDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fas fa-sort mr-1"></i> Sort
                                            </button>
                                            <div class="dropdown-menu" aria-labelledby="sortDropdown">
                                                <button class="dropdown-item sort-btn" data-sort="newest">Newest First</button>
                                                <button class="dropdown-item sort-btn" data-sort="oldest">Oldest First</button>
                                                <button class="dropdown-item sort-btn" data-sort="name-asc">Name (A-Z)</button>
                                                <button class="dropdown-item sort-btn" data-sort="name-desc">Name (Z-A)</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Applications List -->
                    <div class="applications-container">
                        <?php foreach ($applications as $application): ?>
                            <div class="card application-card" data-status="<?php echo htmlspecialchars($application['status'] ?? 'Pending'); ?>">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h5 class="card-title mb-1"><?php echo htmlspecialchars($application['name']); ?></h5>
                                            <div class="d-flex align-items-center mb-2">
                                                <span class="badge badge-pill 
                                                    <?php 
                                                    $status = isset($application['status']) ? $application['status'] : 'Pending';
                                                    switch ($status) {
                                                        case 'Reviewed':
                                                            echo 'badge-reviewed';
                                                            break;
                                                        case 'Shortlisted':
                                                            echo 'badge-shortlisted';
                                                            break;
                                                        case 'Rejected':
                                                            echo 'badge-rejected';
                                                            break;
                                                        case 'Interviewed':
                                                            echo 'badge-interviewed';
                                                            break;
                                                        default:
                                                            echo 'badge-pending'; // Pending
                                                    }
                                                    ?>">
                                                    <?php echo htmlspecialchars($status); ?>
                                                </span>
                                                <small class="text-muted ml-2">
                                                    Applied on <?php echo date('M d, Y', strtotime($application['applied_at'])); ?>
                                                </small>
                                            </div>
                                            <div class="applicant-details">
                                                <p class="mb-1">
                                                    <i class="fas fa-envelope text-muted mr-2"></i>
                                                    <a href="mailto:<?php echo htmlspecialchars($application['email']); ?>">
                                                        <?php echo htmlspecialchars($application['email']); ?>
                                                    </a>
                                                </p>
                                                <p class="mb-1">
                                                    <i class="fas fa-phone text-muted mr-2"></i>
                                                    <a href="tel:<?php echo htmlspecialchars($application['phone']); ?>">
                                                        <?php echo htmlspecialchars($application['phone']); ?>
                                                    </a>
                                                </p>
                                                <?php if (!empty($application['notes'])): ?>
                                                <div class="mt-3">
                                                    <h6 class="mb-1">Notes:</h6>
                                                    <p class="text-muted small mb-0"><?php echo nl2br(htmlspecialchars($application['notes'])); ?></p>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-md-right mt-3 mt-md-0">
                                            <div class="action-btns">
                                                <?php if (isset($application['resume_path']) && !empty($application['resume_path'])): ?>
                                                    <a href="view-applications.php?job_id=<?php echo $jobId; ?>&view_resume=<?php echo $application['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary" target="_blank">
                                                        <i class="fas fa-file-alt"></i> View Resume
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-outline-secondary" disabled>
                                                        <i class="fas fa-file-alt"></i> No Resume
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <button type="button" class="btn btn-sm btn-outline-info" 
                                                        onclick="openStatusModal('<?php echo $application['id']; ?>', '<?php echo htmlspecialchars($application['name']); ?>', '<?php echo $application['status'] ?? 'Pending'; ?>')">
                                                    <i class="fas fa-edit"></i> Update Status
                                                </button>
                                                
                                                <a href="view-applications.php?job_id=<?php echo $jobId; ?>&delete=<?php echo $application['id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this application? This will also delete the resume file.')">
                                                    <i class="fas fa-trash-alt"></i> Delete
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Status Update Modal -->
                    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="statusModalLabel">Update Application Status</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form action="view-applications.php?job_id=<?php echo $jobId; ?>" method="post" id="statusForm">
                                        <input type="hidden" name="application_id" id="application_id" value="">
                                        <input type="hidden" name="update_status" value="1">
                                        
                                        <div class="form-group">
                                            <label for="applicant_name" class="form-label">Applicant</label>
                                            <input type="text" id="applicant_name" class="form-control" readonly>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="status" class="form-label">Status</label>
                                            <select id="status" name="status" required class="form-control">
                                                <option value="Pending">Pending</option>
                                                <option value="Reviewed">Reviewed</option>
                                                <option value="Shortlisted">Shortlisted</option>
                                                <option value="Interviewed">Interviewed</option>
                                                <option value="Rejected">Rejected</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group interview-date-group d-none">
                                            <label for="interview_date" class="form-label">Interview Date & Time</label>
                                            <input type="datetime-local" id="interview_date" name="interview_date" class="form-control">
                                            <small class="text-muted">Required for shortlisted applicants (for sending email invitation)</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="notes" class="form-label">Notes</label>
                                            <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Add any notes about this applicant..."></textarea>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                        Cancel
                                    </button>
                                    <button type="submit" form="statusForm" class="btn" style="background-color: #ff7700; color: white;">
                                        Update
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <!-- No Applications Message -->
                    <div class="card p-5 text-center">
                        <div class="py-5">
                            <i class="fas fa-file-alt fa-4x text-muted mb-3"></i>
                            <h3 class="h5 mb-3">No applications yet</h3>
                            <p class="text-muted">No one has applied for this position yet. Check back later or consider promoting this job listing.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap & jQuery Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Show/hide interview date field based on status selection
            $('#status').change(function() {
                if ($(this).val() === 'Shortlisted') {
                    $('.interview-date-group').removeClass('d-none');
                    $('#interview_date').prop('required', true);
                } else {
                    $('.interview-date-group').addClass('d-none');
                    $('#interview_date').prop('required', false);
                }
            });
            
            // Search functionality
            $('#applicationSearch').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $('.application-card').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });
            
            // Filter functionality
            $('.filter-btn').click(function() {
                var filterValue = $(this).data('filter');
                
                if (filterValue === 'all') {
                    $('.application-card').show();
                } else {
                    $('.application-card').hide();
                    $('.application-card[data-status="' + filterValue + '"]').show();
                }
            });
            
            // Sort functionality
            $('.sort-btn').click(function() {
                var sortValue = $(this).data('sort');
                var $cards = $('.application-card');
                
                $cards.sort(function(a, b) {
                    if (sortValue === 'newest') {
                        return new Date($(b).find('.text-muted small').text().replace('Applied on ', '')) - 
                               new Date($(a).find('.text-muted small').text().replace('Applied on ', ''));
                    } else if (sortValue === 'oldest') {
                        return new Date($(a).find('.text-muted small').text().replace('Applied on ', '')) - 
                               new Date($(b).find('.text-muted small').text().replace('Applied on ', ''));
                    } else if (sortValue === 'name-asc') {
                        return $(a).find('.card-title').text().localeCompare($(b).find('.card-title').text());
                    } else if (sortValue === 'name-desc') {
                        return $(b).find('.card-title').text().localeCompare($(a).find('.card-title').text());
                    }
                });
                
                var $container = $('.applications-container');
                $cards.detach().appendTo($container);
            });
            
            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();
        });
        
        // Function to open status modal with applicant details
        function openStatusModal(applicationId, applicantName, currentStatus) {
            $('#application_id').val(applicationId);
            $('#applicant_name').val(applicantName);
            $('#status').val(currentStatus);
            
            // Show/hide interview date field based on initial status
            if (currentStatus === 'Shortlisted') {
                $('.interview-date-group').removeClass('d-none');
                $('#interview_date').prop('required', true);
            } else {
                $('.interview-date-group').addClass('d-none');
                $('#interview_date').prop('required', false);
            }
            
            $('#statusModal').modal('show');
        }
    </script>
</body>
</html>