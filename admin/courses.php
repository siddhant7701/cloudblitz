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

// Process delete request
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    try {
        // First check if the course exists
        $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            // Delete the course
            $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
            $stmt->execute([$id]);
            
            $_SESSION['success'] = "Course deleted successfully.";
        } else {
            $_SESSION['error'] = "Course not found.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header('Location: courses.php');
    exit;
}

// Get sorting parameters
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Validate sort column to prevent SQL injection
$allowed_columns = ['title', 'category_name', 'instructor_name', 'price', 'level', 'status', 'created_at'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'created_at';
}

// Validate sort order
if ($sort_order != 'ASC' && $sort_order != 'DESC') {
    $sort_order = 'DESC';
}

// Get search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$level_filter = isset($_GET['level']) ? $_GET['level'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Get all courses with category and instructor names
try {
    $query = "
        SELECT c.*, cc.name as category_name, i.name as instructor_name 
        FROM courses c 
        LEFT JOIN course_categories cc ON c.category_id = cc.id 
        LEFT JOIN instructors i ON c.instructor_id = i.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Add search conditions
    if (!empty($search)) {
        $query .= " AND (c.title LIKE ? OR c.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    // Add category filter
    if (!empty($category_filter)) {
        $query .= " AND c.category_id = ?";
        $params[] = $category_filter;
    }
    
    // Add level filter
    if (!empty($level_filter)) {
        $query .= " AND c.level = ?";
        $params[] = $level_filter;
    }
    
    // Add status filter
    if (!empty($status_filter)) {
        $query .= " AND c.status = ?";
        $params[] = $status_filter;
    }
    
    // Add sorting
    $query .= " ORDER BY " . ($sort_column == 'category_name' ? 'cc.name' : 
                             ($sort_column == 'instructor_name' ? 'i.name' : "c.$sort_column")) . " $sort_order";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all categories for filter dropdown
    $stmt = $conn->prepare("SELECT id, name FROM course_categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    $courses = [];
    $categories = [];
}

// Helper function to generate sort URL
function getSortUrl($column, $currentSort, $currentOrder) {
    $newOrder = ($currentSort === $column && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    $params = $_GET;
    $params['sort'] = $column;
    $params['order'] = $newOrder;
    return 'courses.php?' . http_build_query($params);
}

// Helper function to get sort icon
function getSortIcon($column, $currentSort, $currentOrder) {
    if ($currentSort !== $column) {
        return '<i class="fas fa-sort"></i>';
    }
    return ($currentOrder === 'ASC') ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses - CloudBlitz Admin</title>
    
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
        .course-image {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        .filters-row {
            background-color: #f8f9fc;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        th a {
            color: #4e73df;
            text-decoration: none;
        }
        th a:hover {
            color: #2e59d9;
            text-decoration: none;
        }
        .action-buttons .btn {
            margin-right: 5px;
        }
        .badge.badge-success {
            background-color: #1cc88a;
        }
        .badge.badge-warning {
            background-color: #f6c23e;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(78, 115, 223, 0.05);
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
            
            <!-- Main Content -->
            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Courses Management</h1>
                    <div class="btn-group">
                        <a href="add-course.php" class="btn btn-primary">
                            <i class="fas fa-plus fa-sm text-white-50"></i> Add New Course
                        </a>
                        <a href="course-categories.php" class="btn btn-info">
                            <i class="fas fa-folder fa-sm text-white-50"></i> Manage Categories
                        </a>
                    </div>
                </div>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success']; 
                        unset($_SESSION['success']);
                        ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error']; 
                        unset($_SESSION['error']);
                        ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <!-- Filters -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Search & Filter</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="courses.php" class="form">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="search">Search:</label>
                                    <input type="text" class="form-control" id="search" name="search" 
                                           placeholder="Search by title or description" value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="category">Category:</label>
                                    <select class="form-control" id="category" name="category">
                                        <option value="">All Categories</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" <?php echo ($category_filter == $category['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="level">Level:</label>
                                    <select class="form-control" id="level" name="level">
                                        <option value="">All Levels</option>
                                        <option value="beginner" <?php echo ($level_filter == 'beginner') ? 'selected' : ''; ?>>Beginner</option>
                                        <option value="intermediate" <?php echo ($level_filter == 'intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                        <option value="advanced" <?php echo ($level_filter == 'advanced') ? 'selected' : ''; ?>>Advanced</option>
                                        <option value="all" <?php echo ($level_filter == 'all') ? 'selected' : ''; ?>>All Levels</option>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="status">Status:</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="">All Status</option>
                                        <option value="published" <?php echo ($status_filter == 'published') ? 'selected' : ''; ?>>Published</option>
                                        <option value="draft" <?php echo ($status_filter == 'draft') ? 'selected' : ''; ?>>Draft</option>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-search"></i> Filter
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">All Courses</h6>
                        <div class="dropdown no-arrow">
                            <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                                <a class="dropdown-item" href="courses.php"><i class="fas fa-sync fa-sm fa-fw mr-2 text-gray-400"></i> Reset Filters</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#" id="export-csv"><i class="fas fa-file-csv fa-sm fa-fw mr-2 text-gray-400"></i> Export to CSV</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="coursesTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th><a href="<?php echo getSortUrl('title', $sort_column, $sort_order); ?>">Title <?php echo getSortIcon('title', $sort_column, $sort_order); ?></a></th>
                                        <th><a href="<?php echo getSortUrl('category_name', $sort_column, $sort_order); ?>">Category <?php echo getSortIcon('category_name', $sort_column, $sort_order); ?></a></th>
                                        <th><a href="<?php echo getSortUrl('instructor_name', $sort_column, $sort_order); ?>">Instructor <?php echo getSortIcon('instructor_name', $sort_column, $sort_order); ?></a></th>
                                        <th><a href="<?php echo getSortUrl('price', $sort_column, $sort_order); ?>">Price <?php echo getSortIcon('price', $sort_column, $sort_order); ?></a></th>
                                        <th><a href="<?php echo getSortUrl('level', $sort_column, $sort_order); ?>">Level <?php echo getSortIcon('level', $sort_column, $sort_order); ?></a></th>
                                        <th><a href="<?php echo getSortUrl('status', $sort_column, $sort_order); ?>">Status <?php echo getSortIcon('status', $sort_column, $sort_order); ?></a></th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($courses as $course): ?>
                                    <tr>
                                        <td class="text-center">
                                            <?php if (!empty($course['image_path'])): ?>
                                                <img src="../<?php echo htmlspecialchars($course['image_path']); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>" class="course-image">
                                            <?php else: ?>
                                                <img src="../assets/img/course-placeholder.jpg" alt="No image" class="course-image">
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($course['title']); ?></td>
                                        <td><?php echo htmlspecialchars($course['category_name'] ?? 'Uncategorized'); ?></td>
                                        <td><?php echo htmlspecialchars($course['instructor_name'] ?? 'Not Assigned'); ?></td>
                                        <td>
                                            <?php 
                                            if (isset($course['price']) && $course['price'] > 0) {
                                                echo '$' . number_format($course['price'], 2);
                                            } else {
                                                echo '<span class="badge badge-info">Free</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $level_badge = '';
                                            switch(strtolower($course['level'] ?? 'all')):
                                                case 'beginner':
                                                    $level_badge = 'badge-success';
                                                    break;
                                                case 'intermediate':
                                                    $level_badge = 'badge-primary';
                                                    break;
                                                case 'advanced':
                                                    $level_badge = 'badge-danger';
                                                    break;
                                                default:
                                                    $level_badge = 'badge-info';
                                            endswitch;
                                            ?>
                                            <span class="badge <?php echo $level_badge; ?>">
                                                <?php echo htmlspecialchars(ucfirst($course['level'] ?? 'All Levels')); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (isset($course['status']) && $course['status'] === 'published'): ?>
                                                <span class="badge badge-success">Published</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Draft</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="action-buttons">
                                            <a href="view-course.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit-course.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="#" class="btn btn-sm btn-danger delete-course" data-id="<?php echo $course['id']; ?>" data-title="<?php echo htmlspecialchars($course['title']); ?>" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (count($courses) === 0): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No courses found matching your criteria</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <p class="text-muted">Showing <?php echo count($courses); ?> course(s)</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete the course: <span id="courseTitleToDelete"></span>?
                    <p class="text-danger mt-2">This action cannot be undone and will remove all lessons and materials associated with this course.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDelete" class="btn btn-danger">Delete Course</a>
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
    
    <script>
    $(document).ready(function() {
        // Show confirmation modal for delete
        $('.delete-course').click(function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var title = $(this).data('title');
            
            $('#courseTitleToDelete').text(title);
            $('#confirmDelete').attr('href', 'courses.php?delete=' + id);
            $('#deleteModal').modal('show');
        });
        
        // Initialize DataTables with limited functionality (since we're handling sorting server-side)
        $('#coursesTable').DataTable({
            "paging": false,
            "ordering": false,
            "info": false,
            "searching": false,
            "responsive": true,
            "language": {
                "emptyTable": "No courses found"
            }
        });
        
        // Export to CSV
        $('#export-csv').click(function(e) {
            e.preventDefault();
            window.location.href = 'export-courses.php?format=csv';
        });
        
        // Image preview on hover
        $('.course-image').hover(function() {
            $(this).css('transform', 'scale(1.5)');
            $(this).css('transition', 'transform 0.3s');
            $(this).css('z-index', '100');
        }, function() {
            $(this).css('transform', 'scale(1)');
            $(this).css('z-index', '1');
        });
    });
    </script>
</body>
</html>