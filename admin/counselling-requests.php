<?php
// Include database connection
require_once '../includes/db_connect.php';

// Check if admin is logged in
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Handle status update
if (isset($_POST['update_status'])) {
    $request_id = (int)$_POST['request_id'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];
    $contacted_at = $status === 'contacted' ? date('Y-m-d H:i:s') : null;
    
    try {
        $stmt = $conn->prepare("UPDATE counselling_requests SET 
                               status = :status, 
                               notes = :notes, 
                               contacted_by = :contacted_by,
                               contacted_at = :contacted_at
                               WHERE id = :id");
        
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':contacted_by', $_SESSION['admin_id']);
        $stmt->bindParam(':contacted_at', $contacted_at);
        $stmt->bindParam(':id', $request_id);
        
        $stmt->execute();
        
        $success_message = "Request updated successfully!";
    } catch(PDOException $e) {
        $error_message = "Error updating request: " . $e->getMessage();
    }
}

// Handle delete request
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $request_id = (int)$_GET['id'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM counselling_requests WHERE id = :id");
        $stmt->bindParam(':id', $request_id);
        $stmt->execute();
        
        $success_message = "Request deleted successfully!";
    } catch(PDOException $e) {
        $error_message = "Error deleting request: " . $e->getMessage();
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$query = "SELECT cr.*, c.title as course_name, au.name as admin_name 
          FROM counselling_requests cr 
          LEFT JOIN courses c ON cr.course_id = c.id 
          LEFT JOIN admin_users au ON cr.contacted_by = au.id 
          WHERE 1=1";

$params = [];

if (!empty($status_filter)) {
    $query .= " AND cr.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($date_filter)) {
    $query .= " AND DATE(cr.created_at) = :date";
    $params[':date'] = $date_filter;
}

if (!empty($search)) {
    $query .= " AND (cr.name LIKE :search OR cr.email LIKE :search OR cr.phone LIKE :search)";
    $params[':search'] = "%$search%";
}

$query .= " ORDER BY cr.created_at DESC";

// Execute query
try {
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Error fetching requests: " . $e->getMessage();
    $requests = [];
}

// Get counts for dashboard
try {
    $stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM counselling_requests GROUP BY status");
    $stmt->execute();
    $status_counts = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status_counts[$row['status']] = $row['count'];
    }
    
    $total_count = array_sum($status_counts);
    $pending_count = isset($status_counts['pending']) ? $status_counts['pending'] : 0;
    $contacted_count = isset($status_counts['contacted']) ? $status_counts['contacted'] : 0;
    $completed_count = isset($status_counts['completed']) ? $status_counts['completed'] : 0;
    $cancelled_count = isset($status_counts['cancelled']) ? $status_counts['cancelled'] : 0;
} catch(PDOException $e) {
    $error_message = "Error fetching status counts: " . $e->getMessage();
    $total_count = $pending_count = $contacted_count = $completed_count = $cancelled_count = 0;
}

// Page title
$page_title = "Counselling Requests";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CloudBlitz Admin - <?php echo $page_title; ?></title>
    <!-- Include Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts - Roboto -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="css/admin.css">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body>
    <style>
        .bg-col {
            background-color: #ff7700;
        }
        .bg-col:hover {
            background-color: #ff9933;
            color:rgb(141, 138, 135);
        }
        li {
            display: list-item;
            text-align: -webkit-match-parent;
            unicode-bidi: isolate;
            list-style-type: none;}
        .action-buttons {   
            display: flex;
            gap: 5px;
        }
    </style>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <div id="content">
        <?php include 'includes/navbar.php'; ?>
        
        <div class="container-fluid px-4">
            <h1 class="h3 mb-4 text-gray-800">Counselling Requests</h1>
            
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Dashboard Cards -->
            <div class="row">
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-col text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>Total Requests</div>
                                <div class="h3 mb-0"><?php echo $total_count; ?></div>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a class="small text-white stretched-link" href="counselling-requests.php">View All</a>
                            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-col text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>Pending</div>
                                <div class="h3 mb-0"><?php echo $pending_count; ?></div>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a class="small text-white stretched-link" href="counselling-requests.php?status=pending">View Pending</a>
                            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-col text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>Contacted</div>
                                <div class="h3 mb-0"><?php echo $contacted_count; ?></div>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a class="small text-white stretched-link" href="counselling-requests.php?status=contacted">View Contacted</a>
                            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-col text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>Completed</div>
                                <div class="h3 mb-0"><?php echo $completed_count; ?></div>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a class="small text-white stretched-link" href="counselling-requests.php?status=completed">View Completed</a>
                            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-filter me-1"></i>
                    Filter Requests
                </div>
                <div class="card-body">
                    <form method="GET" action="counselling-requests.php" class="row g-3">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="contacted" <?php echo $status_filter === 'contacted' ? 'selected' : ''; ?>>Contacted</option>
                                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="date" name="date" value="<?php echo $date_filter; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" placeholder="Name, Email or Phone" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">Filter</button>
                            <a href="counselling-requests.php" class="btn btn-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Requests Table -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-table me-1"></i>
                    Counselling Requests
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="requestsTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Course</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($requests) > 0): ?>
                                    <?php foreach ($requests as $request): ?>
                                        <tr>
                                            <td><?php echo $request['id']; ?></td>
                                            <td><?php echo htmlspecialchars($request['name']); ?></td>
                                            <td><?php echo htmlspecialchars($request['email']); ?></td>
                                            <td><?php echo htmlspecialchars($request['phone']); ?></td>
                                            <td><?php echo htmlspecialchars($request['course_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $request['status'] === 'pending' ? 'warning' : 
                                                        ($request['status'] === 'contacted' ? 'info' : 
                                                        ($request['status'] === 'completed' ? 'success' : 'secondary')); 
                                                ?>">
                                                    <?php echo ucfirst($request['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button type="button" class="btn btn-sm btn-primary view-request" 
                                                            data-bs-toggle="modal" data-bs-target="#viewRequestModal" 
                                                            data-id="<?php echo $request['id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($request['name']); ?>"
                                                            data-email="<?php echo htmlspecialchars($request['email']); ?>"
                                                            data-phone="<?php echo htmlspecialchars($request['phone']); ?>"
                                                            data-course="<?php echo htmlspecialchars($request['course_name'] ?? 'N/A'); ?>"
                                                            data-message="<?php echo htmlspecialchars($request['message'] ?? ''); ?>"
                                                            data-created="<?php echo date('M d, Y H:i', strtotime($request['created_at'])); ?>"
                                                            data-status="<?php echo $request['status']; ?>"
                                                            data-notes="<?php echo htmlspecialchars($request['notes'] ?? ''); ?>"
                                                            data-contacted-by="<?php echo htmlspecialchars($request['admin_name'] ?? ''); ?>"
                                                            data-contacted-at="<?php echo $request['contacted_at'] ? date('M d, Y H:i', strtotime($request['contacted_at'])) : ''; ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-success update-status" 
                                                            data-bs-toggle="modal" data-bs-target="#updateStatusModal" 
                                                            data-id="<?php echo $request['id']; ?>"
                                                            data-status="<?php echo $request['status']; ?>"
                                                            data-notes="<?php echo htmlspecialchars($request['notes'] ?? ''); ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="javascript:void(0);" 
                                                       class="btn btn-sm btn-danger delete-btn" 
                                                       data-id="<?php echo $request['id']; ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No counselling requests found.</td>
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

<!-- View Request Modal -->
<div class="modal fade" id="viewRequestModal" tabindex="-1" aria-labelledby="viewRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="viewRequestModalLabel">Request Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Personal Information</h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Name:</strong> <span id="viewName"></span></p>
                                <p><strong>Email:</strong> <span id="viewEmail"></span></p>
                                <p><strong>Phone:</strong> <span id="viewPhone"></span></p>
                                <p><strong>Course:</strong> <span id="viewCourse"></span></p>
                                <p><strong>Date Submitted:</strong> <span id="viewCreated"></span></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Status Information</h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Current Status:</strong> <span id="viewStatus" class="badge"></span></p>
                                <p><strong>Contacted By:</strong> <span id="viewContactedBy"></span></p>
                                <p><strong>Contacted At:</strong> <span id="viewContactedAt"></span></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Message</h6>
                            </div>
                            <div class="card-body">
                                <p id="viewMessage" class="mb-0"></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Notes</h6>
                            </div>
                            <div class="card-body">
                                <p id="viewNotes" class="mb-0"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success update-from-view" data-bs-toggle="modal" data-bs-target="#updateStatusModal">Update Status</button>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="updateStatusModalLabel">Update Request Status</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="counselling-requests.php">
                <div class="modal-body">
                    <input type="hidden" name="request_id" id="updateRequestId">
                    <div class="mb-3">
                        <label for="updateStatus" class="form-label">Status</label>
                        <select class="form-select" id="updateStatus" name="status" required>
                            <option value="pending">Pending</option>
                            <option value="contacted">Contacted</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="updateNotes" class="form-label">Notes</label>
                        <textarea class="form-control" id="updateNotes" name="notes" rows="5" placeholder="Enter notes about your interaction..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_status" class="btn btn-success">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteConfirmModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this counselling request? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>



<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#requestsTable').DataTable({
            responsive: true,
            order: [[5, 'desc']], // Sort by date column descending
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]]
        });
        
        // View Request Modal
        $('.view-request').click(function() {
            const id = $(this).data('id');
            $('#viewName').text($(this).data('name'));
            $('#viewEmail').text($(this).data('email'));
            $('#viewPhone').text($(this).data('phone'));
            $('#viewCourse').text($(this).data('course'));
            $('#viewMessage').text($(this).data('message') || 'No message provided');
            $('#viewCreated').text($(this).data('created'));
            
            const status = $(this).data('status');
            $('#viewStatus').text(status.charAt(0).toUpperCase() + status.slice(1));
            
            // Store ID for update button
            $('.update-from-view').data('id', id);
            $('.update-from-view').data('status', status);
            $('.update-from-view').data('notes', $(this).data('notes'));
            
            // Set status badge color
            $('#viewStatus').removeClass().addClass('badge');
            if (status === 'pending') {
                $('#viewStatus').addClass('bg-warning');
            } else if (status === 'contacted') {
                $('#viewStatus').addClass('bg-info');
            } else if (status === 'completed') {
                $('#viewStatus').addClass('bg-success');
            } else {
                $('#viewStatus').addClass('bg-secondary');
            }
            
            $('#viewContactedBy').text($(this).data('contacted-by') || 'Not contacted yet');
            $('#viewContactedAt').text($(this).data('contacted-at') || 'Not contacted yet');
            $('#viewNotes').text($(this).data('notes') || 'No notes added');
        });
        
        // Update Status from View Modal
        $('.update-from-view').click(function() {
            const id = $(this).data('id');
            const status = $(this).data('status');
            const notes = $(this).data('notes');
            
            $('#viewRequestModal').modal('hide');
            $('#updateRequestId').val(id);
            $('#updateStatus').val(status);
            $('#updateNotes').val(notes);
        });
        
        // Update Status Modal
        $('.update-status').click(function() {
            $('#updateRequestId').val($(this).data('id'));
            $('#updateStatus').val($(this).data('status'));
            $('#updateNotes').val($(this).data('notes'));
        });
        
        // Delete Confirmation
        $('.delete-btn').click(function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            $('#confirmDeleteBtn').attr('href', 'counselling-requests.php?delete=1&id=' + id);
            $('#deleteConfirmModal').modal('show');
        });
        
        
        
        // Auto-close alerts after 5 seconds
        setTimeout(function() {
            $('.alert-dismissible').alert('close');
        }, 5000);
    });
</script>
<script src="js/admin.js"></script>
</body>
</html>