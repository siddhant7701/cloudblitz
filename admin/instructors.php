<?php
// Start the session
session_start();

// Include database connection
include('../includes/db_connect.php');

// Handle delete action
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];

    try {
        // First check if the instructor exists
        $stmt = $conn->prepare("SELECT * FROM instructors WHERE id = ?");
        $stmt->execute([$delete_id]);
        $instructor = $stmt->fetch();
        
        if ($instructor) {
            // Check if instructor has any courses
            $stmt = $conn->prepare("SELECT COUNT(*) FROM courses WHERE instructor_id = ?");
            $stmt->execute([$delete_id]);
            $course_count = $stmt->fetchColumn();
            
            if ($course_count > 0) {
                $error_message = "Cannot delete instructor. They are assigned to $course_count course(s).";
            } else {
                // Get image filename before deleting
                $image_file = $instructor['image_path'];
                
                // Delete the instructor
                $stmt = $conn->prepare("DELETE FROM instructors WHERE id = ?");
                $result = $stmt->execute([$delete_id]);
                
                if ($result) {
                    // Delete the image file if it exists
                    if (!empty($image_file) && file_exists("../uploads/instructors/$image_file")) {
                        unlink("../uploads/instructors/$image_file");
                    }
                    
                    $success_message = "Instructor deleted successfully.";
                } else {
                    $error_message = "Failed to delete instructor.";
                }
            }
        } else {
            $error_message = "Instructor not found.";
        }
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}

// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Prepare query for instructors list
try {
    if (!empty($search)) {
        $stmt = $conn->prepare("
            SELECT * FROM instructors 
            WHERE name LIKE ? OR email LIKE ? OR title LIKE ? 
            ORDER BY name ASC
        ");
        $stmt->execute(["%$search%", "%$search%", "%$search%"]);
    } else {
        $stmt = $conn->prepare("
            SELECT * FROM instructors 
            ORDER BY name ASC
        ");
        $stmt->execute();
    }
    $instructors = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error fetching instructors: " . $e->getMessage();
    $instructors = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Instructors - CloudBlitz Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.10.22/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary: #ff7700;
            --primary-light: #ff9933;
            --primary-dark: #e66000;
            --secondary: #333333;
            --secondary-light: #555555;
            --secondary-dark: #222222;
            --white: #ffffff;
            --light-gray: #f5f5f5;
            --gray: #e0e0e0;
            --dark-gray: #888888;
            --danger: #dc3545;
            --success: #28a745;
            --warning: #ffc107;
            --info: #17a2b8;
        }

        #content {
            width: calc(100% - 250px);
            min-height: 100vh;
            transition: all 0.3s;
            position: absolute;
            top: 0;
            right: 0;
        }

        #content.active {
            width: 100%;
        }

        /* Navbar */
        .navbar {
            padding: 15px 10px;
            background: #fff;
            border: none;
            border-radius: 0;
            margin-bottom: 30px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        /* Cards */
        .card {
            margin-bottom: 24px;
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }

        .card-header h6 {
            font-weight: 700;
            color: var(--primary);
        }

        /* Buttons */
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        /* Tables */
        .table-responsive {
            display: block;
            width: 100%;
            overflow-x: auto;
        }

        .table {
            width: 100%;
            margin-bottom: 1rem;
            color: #212529;
        }

        .table th,
        .table td {
            padding: 0.75rem;
            vertical-align: top;
            border-top: 1px solid #e3e6f0;
        }

        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #e3e6f0;
        }

        /* Instructor specific styles */
        .instructor-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -250px;
            }
            #sidebar.active {
                margin-left: 0;
            }
            #content {
                width: 100%;
            }
            #content.active {
                width: calc(100% - 250px);
            }
            #sidebarCollapse span {
                display: none;
            }
        }
    </style>
</head>
<body>
    <link rel="stylesheet" href="./css/admin.css">
      <?php include 'includes/sidebar.php'; ?>
    
    <div id="content">
        <?php include 'includes/navbar.php'; ?>
   
            <div class="container-fluid px-4">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-4 text-gray-800">Manage Instructors</h1>
                    <a href="add-instructors.php" class="d-none d-sm-inline-block btn btn-primary shadow-sm">
                        <i class="fas fa-plus fa-sm text-white-50"></i> Add New Instructor
                    </a>
                </div>

               
                
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <!-- Search Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-search"></i> Search Instructors
                    </div>
                    <div class="card-body">
                        <form action="" method="get" class="form-inline">
                            <div class="input-group mb-4 mr-sm-6">
                                <input type="text" name="search" class="form-control" placeholder="Search instructors..." value="<?php echo htmlspecialchars($search); ?>">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                </div>
                            </div>
                            <?php if (!empty($search)): ?>
                                <a href="instructors.php" class="btn btn-secondary mb-2">Clear Search</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Instructors List -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Instructors</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Title</th>
                                        <th>Rating</th>
                                        <th>Courses</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($instructors)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No instructors found. <a href="add-instructors.php">Add your first instructor</a>.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($instructors as $instructor): ?>
                                            <tr>
                                                <td><?php echo $instructor['id']; ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if (!empty($instructor['image_path'])): ?>
                                                            <img src="../uploads/instructors/<?php echo htmlspecialchars($instructor['image_path']); ?>" alt="<?php echo htmlspecialchars($instructor['name']); ?>" class="instructor-image mr-2">
                                                        <?php else: ?>
                                                            <div class="bg-secondary rounded-circle mr-2 d-flex align-items-center justify-content-center text-white" style="width: 50px; height: 50px;">
                                                                <i class="fas fa-user"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($instructor['name']); ?></strong>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($instructor['email']); ?></td>
                                                <td><?php echo htmlspecialchars($instructor['title']); ?></td>
                                                <td>
                                                    <?php 
                                                    $rating = isset($instructor['rating']) ? $instructor['rating'] : 0;
                                                    echo number_format($rating, 1);
                                                    ?>
                                                    <div class="small">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <?php if ($i <= $rating): ?>
                                                                <i class="fas fa-star text-warning"></i>
                                                            <?php elseif ($i <= $rating + 0.5): ?>
                                                                <i class="fas fa-star-half-alt text-warning"></i>
                                                            <?php else: ?>
                                                                <i class="far fa-star text-warning"></i>
                                                            <?php endif; ?>
                                                        <?php endfor; ?>
                                                    </div>
                                                </td>
                                                <td><?php echo $instructor['course_count']; ?></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="view-instructor.php?id=<?php echo $instructor['id']; ?>" class="btn btn-info btn-sm" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="edit-instructor.php?id=<?php echo $instructor['id']; ?>" class="btn btn-warning btn-sm" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#deleteModal<?php echo $instructor['id']; ?>" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                    
                                                    <!-- Delete Modal -->
                                                    <div class="modal fade" id="deleteModal<?php echo $instructor['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel<?php echo $instructor['id']; ?>" aria-hidden="true">
                                                        <div class="modal-dialog" role="document">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="deleteModalLabel<?php echo $instructor['id']; ?>">Confirm Delete</h5>
                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                        <span aria-hidden="true">&times;</span>
                                                                    </button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    Are you sure you want to delete the instructor: <strong><?php echo htmlspecialchars($instructor['name']); ?></strong>?
                                                                    <?php if (isset($instructor['course_count']) && $instructor['course_count'] > 0): ?>
                                                                        <div class="alert alert-warning mt-2">
                                                                            This instructor is assigned to <?php echo $instructor['course_count']; ?> course(s). You need to reassign these courses before deleting.
                                                                        </div>
                                                                    <?php else: ?>
                                                                        <p class="text-danger mt-2">This action cannot be undone.</p>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                    <?php if (!isset($instructor['course_count']) || $instructor['course_count'] == 0): ?>
                                                                        <a href="instructors.php?delete=<?php echo $instructor['id']; ?>" class="btn btn-danger">Delete</a>
                                                                    <?php else: ?>
                                                                        <button type="button" class="btn btn-danger" disabled>Delete</button>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
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
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.22/js/dataTables.bootstrap4.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Toggle sidebar
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar, #content').toggleClass('active');
            });
            
            // Initialize DataTable
            $('#dataTable').DataTable({
                "paging": true,
                "searching": false, // Disable built-in search as we have our own
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true
            });
        });
    </script>
</body>
</html>
