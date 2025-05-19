<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

require_once '../includes/db_connect.php';

// Get course ID from URL
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// Check if course exists
try {
    $stmt = $conn->prepare("SELECT id, title FROM courses WHERE id = ?");
    $stmt->execute([$courseId]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$course) {
        $_SESSION['error'] = "Course not found.";
        header('Location: courses.php');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header('Location: courses.php');
    exit;
}

// Check if modules table exists, create if not
try {
    $stmt = $conn->prepare("SHOW TABLES LIKE 'course_modules'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        $sql = "CREATE TABLE course_modules (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            course_id INT(11) NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            display_order INT(11) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (course_id)
        )";
        $conn->exec($sql);
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new module
    if (isset($_POST['add_module'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $display_order = (int)$_POST['display_order'];
        
        if (empty($title)) {
            $_SESSION['error'] = "Module title is required.";
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO course_modules (course_id, title, description, display_order) VALUES (?, ?, ?, ?)");
                $stmt->execute([$courseId, $title, $description, $display_order]);
                $_SESSION['success'] = "Module added successfully.";
                header("Location: course-modules.php?course_id=$courseId");
                exit;
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error adding module: " . $e->getMessage();
            }
        }
    }
    
    // Update module
    if (isset($_POST['update_module'])) {
        $module_id = (int)$_POST['module_id'];
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $display_order = (int)$_POST['display_order'];
        
        if (empty($title)) {
            $_SESSION['error'] = "Module title is required.";
        } else {
            try {
                $stmt = $conn->prepare("UPDATE course_modules SET title = ?, description = ?, display_order = ? WHERE id = ? AND course_id = ?");
                $stmt->execute([$title, $description, $display_order, $module_id, $courseId]);
                $_SESSION['success'] = "Module updated successfully.";
                header("Location: course-modules.php?course_id=$courseId");
                exit;
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error updating module: " . $e->getMessage();
            }
        }
    }
    
    // Delete module
    if (isset($_POST['delete_module'])) {
        $module_id = (int)$_POST['module_id'];
        
        try {
            // Check if module has lessons
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM course_lessons WHERE module_id = ?");
            $stmt->execute([$module_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                $_SESSION['error'] = "Cannot delete module. It has lessons associated with it. Please delete the lessons first.";
            } else {
                $stmt = $conn->prepare("DELETE FROM course_modules WHERE id = ? AND course_id = ?");
                $stmt->execute([$module_id, $courseId]);
                $_SESSION['success'] = "Module deleted successfully.";
                header("Location: course-modules.php?course_id=$courseId");
                exit;
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error deleting module: " . $e->getMessage();
        }
    }
    
    // Reorder modules
    if (isset($_POST['reorder_modules'])) {
        $module_ids = $_POST['module_ids'];
        $orders = $_POST['orders'];
        
        try {
            $conn->beginTransaction();
            
            for ($i = 0; $i < count($module_ids); $i++) {
                $stmt = $conn->prepare("UPDATE course_modules SET display_order = ? WHERE id = ? AND course_id = ?");
                $stmt->execute([$orders[$i], $module_ids[$i], $courseId]);
            }
            
            $conn->commit();
            $_SESSION['success'] = "Modules reordered successfully.";
            header("Location: course-modules.php?course_id=$courseId");
            exit;
        } catch (PDOException $e) {
            $conn->rollBack();
            $_SESSION['error'] = "Error reordering modules: " . $e->getMessage();
        }
    }
}

// Get modules
$modules = [];
try {
    $stmt = $conn->prepare("SELECT * FROM course_modules WHERE course_id = ? ORDER BY display_order, id");
    $stmt->execute([$courseId]);
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error retrieving modules: " . $e->getMessage();
}

// Get next display order
$nextOrder = 1;
if (!empty($modules)) {
    $maxOrder = max(array_column($modules, 'display_order'));
    $nextOrder = $maxOrder + 1;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Modules - <?php echo htmlspecialchars($course['title']); ?></title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/admin.css">
    
    <style>
        .module-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .module-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .module-card .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e3e6f0;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .module-card .module-title {
            margin: 0;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
        }
        
        .module-card .module-number {
            background-color: #ff5722;
            color: #fff;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 15px;
        }
        
        .module-card .card-body {
            padding: 20px;
        }
        
        .module-card .card-text {
            color: #666;
            margin-bottom: 15px;
        }
        
        .module-card .card-footer {
            background-color: #f8f9fa;
            border-top: 1px solid #e3e6f0;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .module-card .badge {
            font-size: 85%;
        }
        
        .module-card .btn-group .btn {
            padding: 5px 10px;
            font-size: 14px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .empty-state i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 15px;
        }
        
        .empty-state h4 {
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #666;
            margin-bottom: 20px;
        }
        
        .drag-handle {
            cursor: move;
            color: #aaa;
            margin-right: 10px;
        }
        
        .drag-handle:hover {
            color: #666;
        }
        
        .sortable-ghost {
            opacity: 0.5;
            background-color: #e9ecef;
        }
        
        .lesson-count {
            font-size: 14px;
            color: #666;
            margin-top: 10px;
        }
        
        .lesson-count i {
            color: #ff5722;
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
            
            <!-- Main Content -->
            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Manage Modules</h1>
                    <div class="d-flex">
                        <a href="edit-course.php?id=<?php echo $courseId; ?>" class="d-none d-sm-inline-block btn btn-secondary shadow-sm mr-2">
                            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Course
                        </a>
                        <a href="../course.php?id=<?php echo $courseId; ?>" class="d-none d-sm-inline-block btn btn-info shadow-sm" target="_blank">
                            <i class="fas fa-eye fa-sm text-white-50"></i> Preview Course
                        </a>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Course: <?php echo htmlspecialchars($course['title']); ?></h6>
                            </div>
                            <div class="card-body">
                                <?php if (isset($_SESSION['error'])): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $_SESSION['error']; ?>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <?php unset($_SESSION['error']); ?>
                                <?php endif; ?>
                                
                                <?php if (isset($_SESSION['success'])): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <i class="fas fa-check-circle mr-2"></i> <?php echo $_SESSION['success']; ?>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <?php unset($_SESSION['success']); ?>
                                <?php endif; ?>
                                
                                <!-- Add Module Form -->
                                <div class="card mb-4">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">Add New Module</h6>
                                    </div>
                                    <div class="card-body">
                                        <form action="course-modules.php?course_id=<?php echo $courseId; ?>" method="POST">
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label for="title">Module Title <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="title" name="title" required>
                                                </div>
                                                <div class="form-group col-md-3">
                                                    <label for="display_order">Display Order</label>
                                                    <input type="number" class="form-control" id="display_order" name="display_order" value="<?php echo $nextOrder; ?>" min="1">
                                                </div>
                                                
                                            </div>
                                            <div class="form-group">
                                                <label for="description">Description</label>
                                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                            </div>
                                            <button type="submit" name="add_module" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Add Module
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                
                                <!-- Modules List -->
                                <div class="card">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">Course Modules</h6>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($modules)): ?>
                                            <div class="empty-state">
                                                <i class="fas fa-cubes"></i>
                                                <h4>No Modules Found</h4>
                                                <p>Add modules to organize your course content and lessons.</p>
                                            </div>
                                        <?php else: ?>
                                            <form id="reorderForm" action="course-modules.php?course_id=<?php echo $courseId; ?>" method="POST">
                                                <div id="sortable-list">
                                                    <?php foreach ($modules as $index => $module): ?>
                                                        <?php
                                                        // Get lesson count for this module
                                                        $lessonCount = 0;
                                                        try {
                                                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM course_lessons WHERE module_id = ?");
                                                            $stmt->execute([$module['id']]);
                                                            $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                                            $lessonCount = $result['count'];
                                                        } catch (PDOException $e) {
                                                            // Table might not exist, continue with default value
                                                        }
                                                        ?>
                                                        <div class="module-card" data-id="<?php echo $module['id']; ?>">
                                                            <div class="card-header">
                                                                <h5 class="module-title">
                                                                    <div class="drag-handle">
                                                                        <i class="fas fa-grip-vertical"></i>
                                                                    </div>
                                                                    <div class="module-number"><?php echo $module['display_order']; ?></div>
                                                                    <?php echo htmlspecialchars($module['title']); ?>
                                                                   
                                                                </h5>
                                                                <div class="btn-group">
                                                                    <a href="course-lessons.php?module_id=<?php echo $module['id']; ?>" class="btn btn-sm btn-info">
                                                                        <i class="fas fa-book"></i> Lessons (<?php echo $lessonCount; ?>)
                                                                    </a>
                                                                    <button type="button" class="btn btn-sm btn-primary edit-module" 
                                                                            data-id="<?php echo $module['id']; ?>"
                                                                            data-title="<?php echo htmlspecialchars($module['title']); ?>"
                                                                            data-description="<?php echo htmlspecialchars($module['description']); ?>"
                                                                            data-order="<?php echo $module['display_order']; ?>"
                                                                            >
                                                                        <i class="fas fa-edit"></i> Edit
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-danger delete-module" 
                                                                            data-id="<?php echo $module['id']; ?>"
                                                                            data-title="<?php echo htmlspecialchars($module['title']); ?>"
                                                                            data-lessons="<?php echo $lessonCount; ?>">
                                                                        <i class="fas fa-trash"></i> Delete
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <div class="card-body">
                                                                <?php if (!empty($module['description'])): ?>
                                                                    <p class="card-text"><?php echo nl2br(htmlspecialchars($module['description'])); ?></p>
                                                                <?php else: ?>
                                                                    <p class="card-text text-muted">No description available.</p>
                                                                <?php endif; ?>
                                                                
                                                                <div class="lesson-count">
                                                                    <i class="fas fa-book"></i> <?php echo $lessonCount; ?> lesson<?php echo $lessonCount !== 1 ? 's' : ''; ?>
                                                                </div>
                                                            </div>
                                                            <input type="hidden" name="module_ids[]" value="<?php echo $module['id']; ?>">
                                                            <input type="hidden" name="orders[]" value="<?php echo $module['display_order']; ?>" class="order-input">
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <div class="mt-3">
                                                    <button type="submit" name="reorder_modules" class="btn btn-success">
                                                        <i class="fas fa-save"></i> Save Order
                                                    </button>
                                                </div>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Module Modal -->
    <div class="modal fade" id="editModuleModal" tabindex="-1" role="dialog" aria-labelledby="editModuleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModuleModalLabel">Edit Module</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="course-modules.php?course_id=<?php echo $courseId; ?>" method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="edit_module_id" name="module_id">
                        <div class="form-group">
                            <label for="edit_title">Module Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_title" name="title" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_description">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="edit_display_order">Display Order</label>
                                <input type="number" class="form-control" id="edit_display_order" name="display_order" min="1">
                            </div>
                            
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_module" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Module Modal -->
    <div class="modal fade" id="deleteModuleModal" tabindex="-1" role="dialog" aria-labelledby="deleteModuleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModuleModalLabel">Confirm Deletion</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the module "<span id="delete_module_title"></span>"?</p>
                    <div id="delete_warning" class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i> This module has lessons associated with it. You must delete these lessons before deleting this module.
                    </div>
                    <div id="delete_confirm" class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle mr-2"></i> This action cannot be undone.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <form action="course-modules.php?course_id=<?php echo $courseId; ?>" method="POST">
                        <input type="hidden" id="delete_module_id" name="module_id">
                        <button type="submit" name="delete_module" id="confirm_delete_btn" class="btn btn-danger">Delete Module</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <!-- Sortable.js -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.10.2/Sortable.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize Sortable
            var el = document.getElementById('sortable-list');
            if (el) {
                var sortable = new Sortable(el, {
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    handle: '.drag-handle',
                    onEnd: function() {
                        // Update order inputs
                        $('.module-card').each(function(index) {
                            $(this).find('.module-number').text(index + 1);
                            $(this).find('.order-input').val(index + 1);
                        });
                    }
                });
            }
            
            // Edit Module Modal
            $('.edit-module').click(function() {
                var id = $(this).data('id');
                var title = $(this).data('title');
                var description = $(this).data('description');
                var order = $(this).data('order');
                
                $('#edit_module_id').val(id);
                $('#edit_title').val(title);
                $('#edit_description').val(description);
                $('#edit_display_order').val(order);
                
                $('#editModuleModal').modal('show');
            });
            
            // Delete Module Modal
            $('.delete-module').click(function() {
                var id = $(this).data('id');
                var title = $(this).data('title');
                var lessons = $(this).data('lessons');
                
                $('#delete_module_id').val(id);
                $('#delete_module_title').text(title);
                
                // Show/hide warnings based on lesson count
                if (lessons > 0) {
                    $('#delete_warning').show();
                    $('#confirm_delete_btn').prop('disabled', true);
                } else {
                    $('#delete_warning').hide();
                    $('#confirm_delete_btn').prop('disabled', false);
                }
                
                $('#deleteModuleModal').modal('show');
            });
        });
    </script>
</body>
</html>
