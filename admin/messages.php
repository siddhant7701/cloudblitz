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

// Mark message as read
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $message_id = $_GET['mark_read'];
    
    try {
        $stmt = $conn->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = :id");
        $stmt->bindParam(':id', $message_id);
        $stmt->execute();
        
        $success_message = "Message marked as read.";
    } catch (PDOException $e) {
        $error_message = "Error updating message: " . $e->getMessage();
    }
}

// Delete message
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $message_id = $_GET['delete'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = :id");
        $stmt->bindParam(':id', $message_id);
        $stmt->execute();
        
        $success_message = "Message deleted successfully.";
    } catch (PDOException $e) {
        $error_message = "Error deleting message: " . $e->getMessage();
    }
}

// Get all messages
$stmt = $conn->prepare("SELECT * FROM contact_messages ORDER BY created_at DESC");
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Admin Panel</title>
    
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
            
            <!-- Main Content -->
            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Contact Messages</h1>
                </div>
                
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">All Messages</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Subject</th>
                                        <th>Message</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($messages as $message): ?>
                                    <tr class="<?php echo $message['is_read'] ? '' : 'font-weight-bold'; ?>">
                                        <td><?php echo $message['id']; ?></td>
                                        <td><?php echo htmlspecialchars($message['name']); ?></td>
                                        <td><?php echo htmlspecialchars($message['email']); ?></td>
                                        <td><?php echo htmlspecialchars($message['subject']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($message['message'], 0, 100)) . (strlen($message['message']) > 100 ? '...' : ''); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?></td>
                                        <td>
                                            <?php if ($message['is_read']): ?>
                                                <span class="badge badge-success">Read</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Unread</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="view-message.php?id=<?php echo $message['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if (!$message['is_read']): ?>
                                            <a href="messages.php?mark_read=<?php echo $message['id']; ?>" class="btn btn-sm btn-success">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <?php endif; ?>
                                            <a href="messages.php?delete=<?php echo $message['id']; ?>" class="btn btn-sm btn-danger btn-delete" onclick="return confirm('Are you sure you want to delete this message? This action cannot be undone.');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
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
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.22/js/dataTables.bootstrap4.min.js"></script>
    
    <!-- Custom JS -->
    <script src="js/admin.js"></script>
</body>
</html>
