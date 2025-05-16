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

// Get message ID
$message_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get message details
$stmt = $conn->prepare("SELECT * FROM contact_messages WHERE id = :id");
$stmt->bindParam(':id', $message_id);
$stmt->execute();
$message = $stmt->fetch(PDO::FETCH_ASSOC);

// If message doesn't exist, redirect to messages page
if (!$message) {
    header('Location: messages.php');
    exit;
}

// Mark message as read
if (!$message['is_read']) {
    $stmt = $conn->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = :id");
    $stmt->bindParam(':id', $message_id);
    $stmt->execute();
    $message['is_read'] = 1;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Message - Admin Panel</title>
    
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
                    <h1 class="h3 mb-0 text-gray-800">View Message</h1>
                    <a href="messages.php" class="d-none d-sm-inline-block btn btn-secondary shadow-sm">
                        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Messages
                    </a>
                </div>
                
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Message Details</h6>
                        <div>
                            <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-reply"></i> Reply
                            </a>
                            <a href="messages.php?delete=<?php echo $message['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this message? This action cannot be undone.');">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-2 font-weight-bold">From:</div>
                            <div class="col-md-10"><?php echo htmlspecialchars($message['name']); ?> (<?php echo htmlspecialchars($message['email']); ?>)</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-2 font-weight-bold">Subject:</div>
                            <div class="col-md-10"><?php echo htmlspecialchars($message['subject']); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-2 font-weight-bold">Date:</div>
                            <div class="col-md-10"><?php echo date('F j, Y H:i:s', strtotime($message['created_at'])); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-2 font-weight-bold">Status:</div>
                            <div class="col-md-10">
                                <?php if ($message['is_read']): ?>
                                    <span class="badge badge-success">Read</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Unread</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-2 font-weight-bold">Message:</div>
                            <div class="col-md-10">
                                <div class="message-content p-3 bg-light rounded">
                                    <?php echo nl2br(htmlspecialchars($message['message'])); ?>
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
