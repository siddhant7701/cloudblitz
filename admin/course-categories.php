<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

require_once '../includes/db_connect.php';

// Check if categories table exists, create if not
try {
    $stmt = $conn->prepare("SHOW TABLES LIKE 'course_categories'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        $sql = "CREATE TABLE course_categories (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            slug VARCHAR(255) UNIQUE,
            parent_id INT(11) DEFAULT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (parent_id)
        )";
        $conn->exec($sql);
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new category
    if (isset($_POST['add_category'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $slug = trim($_POST['slug']);
        $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $status = $_POST['status'];
        
        // Generate slug if not provided
        if (empty($slug)) {
            $slug = strtolower(str_replace(' ', '-', $name));
        }
        
        if (empty($name)) {
            $_SESSION['error'] = "Category name is required.";
        } else {
            try {
                // Check if slug already exists
                $stmt = $conn->prepare("SELECT id FROM course_categories WHERE slug = ?");
                $stmt->execute([$slug]);
                if ($stmt->rowCount() > 0) {
                    $_SESSION['error'] = "Slug already exists. Please choose a different one.";
                } else {
                    $stmt = $conn->prepare("INSERT INTO course_categories (name, description, slug, parent_id, status) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $description, $slug, $parent_id, $status]);
                    $_SESSION['success'] = "Category added successfully.";
                    header("Location: course-categories.php");
                    exit;
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error adding category: " . $e->getMessage();
            }
        }
    }
    
    // Update category
    if (isset($_POST['update_category'])) {
        $category_id = (int)$_POST['category_id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $slug = trim($_POST['slug']);
        $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $status = $_POST['status'];
        
        // Generate slug if not provided
        if (empty($slug)) {
            $slug = strtolower(str_replace(' ', '-', $name));
        }
        
        if (empty($name)) {
            $_SESSION['error'] = "Category name is required.";
        } else {
            try {
                // Check if slug already exists (excluding current category)
                $stmt = $conn->prepare("SELECT id FROM course_categories WHERE slug = ? AND id != ?");
                $stmt->execute([$slug, $category_id]);
                if ($stmt->rowCount() > 0) {
                    $_SESSION['error'] = "Slug already exists. Please choose a different one.";
                } else {
                    $stmt = $conn->prepare("UPDATE course_categories SET name = ?, description = ?, slug = ?, parent_id = ?, status = ? WHERE id = ?");
                    $stmt->execute([$name, $description, $slug, $parent_id, $status, $category_id]);
                    $_SESSION['success'] = "Category updated successfully.";
                    header("Location: course-categories.php");
                    exit;
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error updating category: " . $e->getMessage();
            }
        }
    }
    
    // Delete category
    if (isset($_POST['delete_category'])) {
        $category_id = (int)$_POST['category_id'];
        
        try {
            // Check if category has courses
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM courses WHERE category_id = ?");
            $stmt->execute([$category_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                $_SESSION['error'] = "Cannot delete category. It has courses associated with it.";
            } else {
                // Check if category has child categories
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM course_categories WHERE parent_id = ?");
                $stmt->execute([$category_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result['count'] > 0) {
                    $_SESSION['error'] = "Cannot delete category. It has child categories.";
                } else {
                    $stmt = $conn->prepare("DELETE FROM course_categories WHERE id = ?");
                    $stmt->execute([$category_id]);
                    $_SESSION['success'] = "Category deleted successfully.";
                    header("Location: course-categories.php");
                    exit;
                }
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error deleting category: " . $e->getMessage();
        }
    }
}

// Get all categories
$categories = [];
try {
    // Check if parent_id column exists in course_categories table
    $stmt = $conn->prepare("SHOW COLUMNS FROM course_categories LIKE 'parent_id'");
    $stmt->execute();
    $parentColumnExists = $stmt->rowCount() > 0;
    
    // Modify the query based on whether parent_id exists
    if ($parentColumnExists) {
        $stmt = $conn->prepare("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM courses WHERE category_id = c.id) as course_count,
                   p.name as parent_name
            FROM course_categories c
            LEFT JOIN course_categories p ON c.parent_id = p.id
            ORDER BY c.name
        ");
    } else {
        $stmt = $conn->prepare("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM courses WHERE category_id = c.id) as course_count,
                   NULL as parent_name
            FROM course_categories c
            ORDER BY c.name
        ");
    }
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error retrieving categories: " . $e->getMessage();
}

// Get parent categories for dropdown
$parentCategories = [];
try {
    if ($parentColumnExists) {
        $stmt = $conn->prepare("SELECT id, name FROM course_categories ORDER BY name");
        $stmt->execute();
        $parentCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $parentCategories = [];
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error retrieving parent categories: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Course Categories</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/admin.css">
    
    <style>
        .category-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .category-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .category-card .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e3e6f0;
            padding: 15px 20px;
        }
        
        .category-card .card-title {
            margin: 0;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
        }
        
        .category-card .card-title .badge {
            margin-left: 10px;
        }
        
        .category-card .card-body {
            padding: 20px;
        }
        
        .category-card .card-text {
            color: #666;
            margin-bottom: 15px;
        }
        
        .category-card .card-footer {
            background-color: #f8f9fa;
            border-top: 1px solid #e3e6f0;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .category-card .card-footer .text-muted {
            font-size: 14px;
        }
        
        .category-card .card-footer .btn-group .btn {
            padding: 5px 10px;
            font-size: 14px;
        }
        
        .category-card .parent-category {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        
        .category-card .course-count {
            font-size: 14px;
            color: #666;
            margin-top: 10px;
        }
        
        .category-card .course-count i {
            color: #ff5722;
            margin-right: 5px;
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
                    <h1 class="h3 mb-0 text-gray-800">Manage Course Categories</h1>
                    <button type="button" class="d-none d-sm-inline-block btn btn-primary shadow-sm" data-toggle="modal" data-target="#addCategoryModal">
                        <i class="fas fa-plus fa-sm text-white-50"></i> Add New Category
                    </button>
                </div>
                
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
                
                <div class="row">
                    <?php if (empty($categories)): ?>
                        <div class="col-12">
                            <div class="empty-state">
                                <i class="fas fa-tags"></i>
                                <h4>No Categories Found</h4>
                                <p>Add categories to organize your courses and make them easier to find.</p>
                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addCategoryModal">
                                    <i class="fas fa-plus"></i> Add New Category
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($categories as $category): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="category-card">
                                    <div class="card-header">
                                       
                                      
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($category['description'])): ?>
                                            <p class="card-text"><?php echo htmlspecialchars($category['description']); ?></p>
                                        <?php else: ?>
                                            <p class="card-text text-muted">No description available.</p>
                                        <?php endif; ?>
                                        
                                        <div class="course-count">
                                            <i class="fas fa-book"></i> <?php echo $category['course_count']; ?> course<?php echo $category['course_count'] !== '1' ? 's' : ''; ?>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <small class="text-muted">Slug: <?php echo htmlspecialchars($category['slug']); ?></small>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-primary edit-category" 
                                                    data-id="<?php echo $category['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                    data-description="<?php echo htmlspecialchars($category['description']); ?>"
                                                    data-slug="<?php echo htmlspecialchars($category['slug']); ?>"
                                                    data-parent="<?php echo $category['parent_id']; ?>"
                                                    data-status="<?php echo $category['status']; ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger delete-category" 
                                                    data-id="<?php echo $category['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                    data-count="<?php echo $category['course_count']; ?>">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="course-categories.php" method="POST">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="name">Category Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="slug">Slug</label>
                            <input type="text" class="form-control" id="slug" name="slug" placeholder="Leave empty to generate automatically">
                            <small class="form-text text-muted">The slug is used in URLs. Use only lowercase letters, numbers, and hyphens.</small>
                        </div>
                        
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1" role="dialog" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="course-categories.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="edit_category_id" name="category_id">
                        <div class="form-group">
                            <label for="edit_name">Category Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_description">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="edit_slug">Slug</label>
                            <input type="text" class="form-control" id="edit_slug" name="slug" placeholder="Leave empty to generate automatically">
                            <small class="form-text text-muted">The slug is used in URLs. Use only lowercase letters, numbers, and hyphens.</small>
                        </div>
                   
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_category" class="btn btn-primary">Update Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Category Modal -->
    <div class="modal fade" id="deleteCategoryModal" tabindex="-1" role="dialog" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteCategoryModalLabel">Confirm Deletion</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the category "<span id="delete_category_name"></span>"?</p>
                    <div id="delete_warning" class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i> This category has courses associated with it. You must reassign or delete these courses before deleting this category.
                    </div>
                    <div id="delete_child_warning" class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i> This category has child categories. You must reassign or delete these child categories before deleting this category.
                    </div>
                    <div id="delete_confirm" class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle mr-2"></i> This action cannot be undone.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <form action="course-categories.php" method="POST">
                        <input type="hidden" id="delete_category_id" name="category_id">
                        <button type="submit" name="delete_category" id="confirm_delete_btn" class="btn btn-danger">Delete Category</button>
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
    
    <script>
        $(document).ready(function() {
            // Auto-generate slug from name
            $('#name').on('keyup', function() {
                var name = $(this).val();
                var slug = name.toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '');
                $('#slug').val(slug);
            });
            
            // Edit Category Modal
            $('.edit-category').click(function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                var description = $(this).data('description');
                var slug = $(this).data('slug');
                var parent = $(this).data('parent');
                var status = $(this).data('status');
                
                $('#edit_category_id').val(id);
                $('#edit_name').val(name);
                $('#edit_description').val(description);
                $('#edit_slug').val(slug);
                $('#edit_parent_id').val(parent);
                $('#edit_status').val(status);
                
                // Prevent selecting itself as parent
                $('#edit_parent_id option').each(function() {
                    if ($(this).val() == id) {
                        $(this).prop('disabled', true);
                    } else {
                        $(this).prop('disabled', false);
                    }
                });
                
                $('#editCategoryModal').modal('show');
            });
            
            // Auto-generate slug from name in edit modal
            $('#edit_name').on('keyup', function() {
                var name = $(this).val();
                var slug = name.toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '');
                $('#edit_slug').val(slug);
            });
            
            // Delete Category Modal
            $('.delete-category').click(function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                var count = $(this).data('count');
                
                $('#delete_category_id').val(id);
                $('#delete_category_name').text(name);
                
                // Show/hide warnings based on course count
                if (count > 0) {
                    $('#delete_warning').show();
                    $('#confirm_delete_btn').prop('disabled', true);
                } else {
                    $('#delete_warning').hide();
                    $('#confirm_delete_btn').prop('disabled', false);
                }
                
                // Check for child categories
                $.ajax({
                    url: 'ajax/check_child_categories.php',
                    type: 'POST',
                    data: { category_id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.has_children) {
                            $('#delete_child_warning').show();
                            $('#confirm_delete_btn').prop('disabled', true);
                        } else {
                            $('#delete_child_warning').hide();
                            if (count === 0) {
                                $('#confirm_delete_btn').prop('disabled', false);
                            }
                        }
                    },
                    error: function() {
                        // If AJAX fails, assume no children for better UX
                        $('#delete_child_warning').hide();
                        if (count === 0) {
                            $('#confirm_delete_btn').prop('disabled', false);
                        }
                    }
                });
                
                $('#deleteCategoryModal').modal('show');
            });
        });
    </script>
</body>
</html>
