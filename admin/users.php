<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

// Check if user has admin privileges
if ($_SESSION['admin_role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

require_once '../includes/db_connect.php';

// Handle user deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    // Prevent deleting super admin (ID 1)
    if ($delete_id === 1) {
        $error_message = "Cannot delete the super admin account.";
    } else {
        try {
            $stmt = $conn->prepare("DELETE FROM admin_users WHERE id = ? AND id != 1");
            $stmt->bindParam(1, $delete_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $success_message = "User deleted successfully!";
            } else {
                $error_message = "Failed to delete user.";
            }
        } catch(PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

// Handle status toggle
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $user_id = (int)$_GET['toggle_status'];
    
    // Prevent modifying super admin (ID 1) if not super admin
    if ($user_id === 1 && $_SESSION['admin_id'] !== 1) {
        $error_message = "You don't have permission to modify the super admin account.";
    } else {
        try {
            // Get current status
            $stmt = $conn->prepare("SELECT status FROM admin_users WHERE id = ?");
            $stmt->bindParam(1, $user_id);
            $stmt->execute();
            $user = $stmt->fetch();
            
            if ($user) {
                // Toggle status
                $new_status = ($user['status'] === 'active') ? 'inactive' : 'active';
                
                $stmt = $conn->prepare("UPDATE admin_users SET status = ? WHERE id = ?");
                $stmt->bindParam(1, $new_status);
                $stmt->bindParam(2, $user_id);
                $stmt->execute();
                
                $success_message = "User status updated successfully!";
            } else {
                $error_message = "User not found.";
            }
        } catch(PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

// Get all admin users
try {
    $stmt = $conn->prepare("SELECT * FROM admin_users ORDER BY id ASC");
    $stmt->execute();
    $admin_users = $stmt->fetchAll();
} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
    $admin_users = [];
}

// Get all regular users
try {
    $stmt = $conn->prepare("SELECT * FROM users ORDER BY id ASC");
    $stmt->execute();
    $regular_users = $stmt->fetchAll();
} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
    $regular_users = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Panel</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.22/css/dataTables.bootstrap4.min.css">
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="css/admin.css">
    
    <style>
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .user-avatar-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--secondary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .tab-content {
            padding-top: 20px;
        }
        
        .badge-role {
            background-color: var(--primary);
            color: white;
        }
        
        .badge-role.admin {
            background-color: var(--primary);
        }
        
        .badge-role.editor {
            background-color: var(--info);
        }
        
        .badge-role.user {
            background-color: var(--secondary);
        }
        
        .super-admin-badge {
            background-color: #6f42c1;
            color: white;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Page Content -->
        <div id="content">
            <!-- Navbar -->
            <?php include 'includes/navbar.php'; ?>
            
            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">User Management</h1>
                    <div>
                        <a href="add-admin-user.php" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Add Admin User
                        </a>
                        
                    </div>
                </div>
                
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <!-- User Management Tabs -->
                <ul class="nav nav-tabs" id="userTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="admin-users-tab" data-toggle="tab" href="#admin-users" role="tab">
                            Admin Users
                        </a>
                    </li>
                   
                </ul>
                
                <div class="tab-content" id="userTabsContent">
                    <!-- Admin Users Tab -->
                    <div class="tab-pane fade show active" id="admin-users" role="tabpanel">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Admin Users</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="adminUsersTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Avatar</th>
                                                <th>Name</th>
                                                <th>Username</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th>Status</th>
                                                <th>Last Login</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($admin_users as $user): ?>
                                                <tr>
                                                    <td><?php echo $user['id']; ?></td>
                                                    <td>
                                                        <div class="user-avatar-placeholder">
                                                            <i class="fas fa-user"></i>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($user['name']); ?>
                                                        <?php if ($user['id'] == 1): ?>
                                                            <span class="badge super-admin-badge">Super Admin</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td>
                                                        <span class="badge badge-role <?php echo $user['role']; ?>">
                                                            <?php echo ucfirst($user['role']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                            <?php echo ucfirst($user['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="edit-admin-user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            
                                                            <?php if ($_SESSION['admin_id'] == 1 && $user['id'] != 1): ?>
                                                                <a href="users.php?toggle_status=<?php echo $user['id']; ?>" class="btn btn-sm btn-<?php echo $user['status'] === 'active' ? 'warning' : 'success'; ?>" 
                                                                   onclick="return confirm('Are you sure you want to <?php echo $user['status'] === 'active' ? 'deactivate' : 'activate'; ?> this user?')">
                                                                    <i class="fas fa-<?php echo $user['status'] === 'active' ? 'ban' : 'check'; ?>"></i>
                                                                </a>
                                                                
                                                                <a href="users.php?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" 
                                                                   onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                                    <i class="fas fa-trash"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
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
    
    <!-- Admin JS -->
    <script src="js/admin.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#adminUsersTable').DataTable({
                order: [[0, 'asc']],
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]]
            });
            
            $('#regularUsersTable').DataTable({
                order: [[0, 'asc']],
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]]
            });
            
            // Activate tab based on hash in URL
            var hash = window.location.hash;
            if (hash) {
                $('.nav-tabs a[href="' + hash + '"]').tab('show');
            }
            
            // Change hash on tab change
            $('.nav-tabs a').on('shown.bs.tab', function (e) {
                window.location.hash = e.target.hash;
            });
        });
    </script>
</body>
</html>
