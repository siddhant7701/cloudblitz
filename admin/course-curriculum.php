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

// Check if curriculum_items table exists, create if not
try {
    $stmt = $conn->prepare("SHOW TABLES LIKE 'course_curriculum_items'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        $sql = "CREATE TABLE course_curriculum_items (
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
    // Add new curriculum item
    if (isset($_POST['add_item'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $display_order = (int)$_POST['display_order'];
        
        if (empty($title)) {
            $_SESSION['error'] = "Title is required.";
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO course_curriculum_items (course_id, title, description, display_order) VALUES (?, ?, ?, ?)");
                $stmt->execute([$courseId, $title, $description, $display_order]);
                $_SESSION['success'] = "Curriculum item added successfully.";
                header("Location: course-curriculum.php?course_id=$courseId");
                exit;
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error adding curriculum item: " . $e->getMessage();
            }
        }
    }
    
    // Update curriculum item
    if (isset($_POST['update_item'])) {
        $item_id = (int)$_POST['item_id'];
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $display_order = (int)$_POST['display_order'];
        
        if (empty($title)) {
            $_SESSION['error'] = "Title is required.";
        } else {
            try {
                $stmt = $conn->prepare("UPDATE course_curriculum_items SET title = ?, description = ?, display_order = ? WHERE id = ? AND course_id = ?");
                $stmt->execute([$title, $description, $display_order, $item_id, $courseId]);
                $_SESSION['success'] = "Curriculum item updated successfully.";
                header("Location: course-curriculum.php?course_id=$courseId");
                exit;
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error updating curriculum item: " . $e->getMessage();
            }
        }
    }
    
    // Delete curriculum item
    if (isset($_POST['delete_item'])) {
        $item_id = (int)$_POST['item_id'];
        
        try {
            $stmt = $conn->prepare("DELETE FROM course_curriculum_items WHERE id = ? AND course_id = ?");
            $stmt->execute([$item_id, $courseId]);
            $_SESSION['success'] = "Curriculum item deleted successfully.";
            header("Location: course-curriculum.php?course_id=$courseId");
            exit;
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error deleting curriculum item: " . $e->getMessage();
        }
    }
    
    // Reorder curriculum items
    if (isset($_POST['reorder_items'])) {
        $item_ids = $_POST['item_ids'];
        $orders = $_POST['orders'];
        
        try {
            $conn->beginTransaction();
            
            for ($i = 0; $i < count($item_ids); $i++) {
                $stmt = $conn->prepare("UPDATE course_curriculum_items SET display_order = ? WHERE id = ? AND course_id = ?");
                $stmt->execute([$orders[$i], $item_ids[$i], $courseId]);
            }
            
            $conn->commit();
            $_SESSION['success'] = "Curriculum items reordered successfully.";
            header("Location: course-curriculum.php?course_id=$courseId");
            exit;
        } catch (PDOException $e) {
            $conn->rollBack();
            $_SESSION['error'] = "Error reordering curriculum items: " . $e->getMessage();
        }
    }
}

// Get curriculum items
$curriculumItems = [];
try {
    $stmt = $conn->prepare("SELECT * FROM course_curriculum_items WHERE course_id = ? ORDER BY display_order, id");
    $stmt->execute([$courseId]);
    $curriculumItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error retrieving curriculum items: " . $e->getMessage();
}

// Get next display order
$nextOrder = 1;
if (!empty($curriculumItems)) {
    $maxOrder = max(array_column($curriculumItems, 'display_order'));
    $nextOrder = $maxOrder + 1;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Curriculum - <?php echo htmlspecialchars($course['title']); ?></title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/admin.css">
    
    <style>
        .curriculum-item {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .curriculum-item:hover {
            background-color: #f1f3f5;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .curriculum-item .item-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .curriculum-item .item-description {
            color: #666;
            font-size: 14px;
        }
        
        .curriculum-item .item-order {
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
        
        .curriculum-item .item-actions {
            display: flex;
            gap: 10px;
        }
        
        .curriculum-item .item-actions .btn {
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
                    <h1 class="h3 mb-0 text-gray-800">Manage Curriculum</h1>
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
                                
                                <!-- Add Curriculum Item Form -->
                                <div class="card mb-4">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">Add Curriculum Item</h6>
                                    </div>
                                    <div class="card-body">
                                        <form action="course-curriculum.php?course_id=<?php echo $courseId; ?>" method="POST">
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label for="title">Title <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="title" name="title" required>
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label for="display_order">Display Order</label>
                                                    <input type="number" class="form-control" id="display_order" name="display_order" value="<?php echo $nextOrder; ?>" min="1">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="description">Description</label>
                                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                            </div>
                                            <button type="submit" name="add_item" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Add Item
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                
                                <!-- Curriculum Items List -->
                                <div class="card">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">Curriculum Items</h6>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($curriculumItems)): ?>
                                            <div class="empty-state">
                                                <i class="fas fa-clipboard-list"></i>
                                                <h4>No Curriculum Items</h4>
                                                <p>Add curriculum items to help students understand what they'll learn in this course.</p>
                                            </div>
                                        <?php else: ?>
                                            <form id="reorderForm" action="course-curriculum.php?course_id=<?php echo $courseId; ?>" method="POST">
                                                <div id="sortable-list">
                                                    <?php foreach ($curriculumItems as $item): ?>
                                                        <div class="curriculum-item d-flex align-items-center" data-id="<?php echo $item['id']; ?>">
                                                            <div class="drag-handle">
                                                                <i class="fas fa-grip-vertical"></i>
                                                            </div>
                                                            <div class="item-order"><?php echo $item['display_order']; ?></div>
                                                            <div class="flex-grow-1">
                                                                <div class="item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                                                <?php if (!empty($item['description'])): ?>
                                                                    <div class="item-description"><?php echo htmlspecialchars($item['description']); ?></div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="item-actions">
                                                                <button type="button" class="btn btn-sm btn-primary edit-item" 
                                                                        data-id="<?php echo $item['id']; ?>"
                                                                        data-title="<?php echo htmlspecialchars($item['title']); ?>"
                                                                        data-description="<?php echo htmlspecialchars($item['description']); ?>"
                                                                        data-order="<?php echo $item['display_order']; ?>">
                                                                    <i class="fas fa-edit"></i> Edit
                                                                </button>
                                                                <button type="button" class="btn btn-sm btn-danger delete-item" 
                                                                        data-id="<?php echo $item['id']; ?>"
                                                                        data-title="<?php echo htmlspecialchars($item['title']); ?>">
                                                                    <i class="fas fa-trash"></i> Delete
                                                                </button>
                                                            </div>
                                                            <input type="hidden" name="item_ids[]" value="<?php echo $item['id']; ?>">
                                                            <input type="hidden" name="orders[]" value="<?php echo $item['display_order']; ?>" class="order-input">
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <div class="mt-3">
                                                    <button type="submit" name="reorder_items" class="btn btn-success">
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
    
    <!-- Edit Item Modal -->
    <div class="modal fade" id="editItemModal" tabindex="-1" role="dialog" aria-labelledby="editItemModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editItemModalLabel">Edit Curriculum Item</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="course-curriculum.php?course_id=<?php echo $courseId; ?>" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="item_id" id="edit_item_id">
                        <div class="form-group">
                            <label for="edit_title">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_title" name="title" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_description">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="edit_display_order">Display Order</label>
                            <input type="number" class="form-control" id="edit_display_order" name="display_order" min="1">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_item" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Item Modal -->
    <div class="modal fade" id="deleteItemModal" tabindex="-1" role="dialog" aria-labelledby="deleteItemModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteItemModalLabel">Confirm Deletion</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the curriculum item "<span id="delete_item_title"></span>"?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <form action="course-curriculum.php?course_id=<?php echo $courseId; ?>" method="POST">
                        <input type="hidden" name="item_id" id="delete_item_id">
                        <button type="submit" name="delete_item" class="btn btn-danger">Delete</button>
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
                        $('.curriculum-item').each(function(index) {
                            $(this).find('.item-order').text(index + 1);
                            $(this).find('.order-input').val(index + 1);
                        });
                    }
                });
            }
            
            // Edit Item Modal
            $('.edit-item').click(function() {
                var id = $(this).data('id');
                var title = $(this).data('title');
                var description = $(this).data('description');
                var order = $(this).data('order');
                
                $('#edit_item_id').val(id);
                $('#edit_title').val(title);
                $('#edit_description').val(description);
                $('#edit_display_order').val(order);
                
                $('#editItemModal').modal('show');
            });
            
            // Delete Item Modal
            $('.delete-item').click(function() {
                var id = $(this).data('id');
                var title = $(this).data('title');
                
                $('#delete_item_id').val(id);
                $('#delete_item_title').text(title);
                
                $('#deleteItemModal').modal('show');
            });
        });
    </script>
</body>
</html>
