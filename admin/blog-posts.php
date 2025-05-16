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

// Function to create a slug from title
function create_slug($string) {
    $slug = preg_replace('/[^A-Za-z0-9-]+/', '-', strtolower($string));
    return trim($slug, '-');
}

// Check the structure of the blog_posts table
try {
    $stmt = $conn->prepare("DESCRIBE blog_posts");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Convert to lowercase for case-insensitive comparison
    $columns = array_map('strtolower', $columns);
    
    // Check if author_id and category_id columns exist
    $has_author_id = in_array('author_id', $columns);
    $has_category_id = in_array('category_id', $columns);
    
} catch (PDOException $e) {
    $error_message = "Error checking table structure: " . $e->getMessage();
}

// Handle blog post deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $post_id = $_GET['delete'];
    
    try {
        // Get image filename before deleting the post
        $stmt = $conn->prepare("SELECT image_path FROM blog_posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && !empty($result['image_path'])) {
            $image_file = $result['image_path'];
            
            // Delete the image file if it exists
            if (file_exists("../images/{$image_file}")) {
                unlink("../images/{$image_file}");
            }
        }
        
        // Delete the blog post
        $stmt = $conn->prepare("DELETE FROM blog_posts WHERE id = ?");
        $stmt->execute([$post_id]);
        
        $success_message = "Blog post deleted successfully!";
    } catch (PDOException $e) {
        $error_message = "Error deleting blog post: " . $e->getMessage();
    }
    
    // Redirect to avoid resubmission on refresh
    header("Location: blog-posts.php");
    exit;
}

// Get blog post data for editing
$edit_data = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $post_id = $_GET['edit'];
    
    try {
        $stmt = $conn->prepare("SELECT * FROM blog_posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$edit_data) {
            $error_message = "Blog post not found.";
        }
    } catch (PDOException $e) {
        $error_message = "Error fetching blog post: " . $e->getMessage();
    }
}

// Handle form submission for adding/editing blog post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $post_id = isset($_POST['post_id']) ? $_POST['post_id'] : null;
    $title = trim($_POST['title']);
    $excerpt = trim($_POST['excerpt']);
    $content = trim($_POST['content']);
    $slug = isset($_POST['slug']) ? trim($_POST['slug']) : create_slug($title);
    
    // Get category_id if it exists
    $category_id = isset($_POST['category_id']) ? $_POST['category_id'] : null;
    
    // Get author_id - use from session if available
    $author_id = isset($_POST['author_id']) ? $_POST['author_id'] : (isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null);
    
    // Post status (defaulting to published)
    $status = isset($_POST['status']) ? $_POST['status'] : 'published';
    
    // Validate required fields
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Title is required.";
    }
    
    if (empty($content)) {
        $errors[] = "Content is required.";
    }
    
    // Handle image upload
    $image_name = '';
    
    // If editing, get current image
    if ($post_id) {
        $stmt = $conn->prepare("SELECT image_path FROM blog_posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $current_post = $stmt->fetch(PDO::FETCH_ASSOC);
        $image_name = $current_post['image_path'] ?? '';
    }
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        
        if (in_array($_FILES['image']['type'], $allowed_types)) {
            $image_name = time() . '_' . $_FILES['image']['name'];
            $upload_path = "../images/" . $image_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Image uploaded successfully
                
                // If editing and new image uploaded, delete old image
                if ($post_id && !empty($current_post['image_path'])) {
                    $old_image = $current_post['image_path'];
                    if (file_exists("../images/{$old_image}")) {
                        unlink("../images/{$old_image}");
                    }
                }
            } else {
                $errors[] = "Failed to upload image.";
            }
        } else {
            $errors[] = "Invalid image format. Allowed formats: JPG, JPEG, PNG, GIF.";
        }
    } elseif (empty($image_name) && !$post_id) {
        $errors[] = "Featured image is required for new posts.";
    }
    
    if (empty($errors)) {
        try {
            $date_updated = date('Y-m-d H:i:s');
            
            if ($post_id) {
                // Update existing blog post
                $sql = "UPDATE blog_posts SET 
                       title = ?, 
                       slug = ?,
                       content = ?, 
                       excerpt = ?, 
                       image_path = ?, 
                       updated_at = ?,
                       status = ?";
                
                $params = [$title, $slug, $content, $excerpt, $image_name, $date_updated, $status];
                
                // Add category_id if it exists
                if ($has_category_id && $category_id) {
                    $sql .= ", category_id = ?";
                    $params[] = $category_id;
                }
                
                // Add author_id if it exists
                if ($has_author_id && $author_id) {
                    $sql .= ", author_id = ?";
                    $params[] = $author_id;
                }
                
                $sql .= " WHERE id = ?";
                $params[] = $post_id;
                
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                
                $success_message = "Blog post updated successfully!";
            } else {
                // Add new blog post
                $date_created = date('Y-m-d H:i:s');
                
                $sql = "INSERT INTO blog_posts (
                        title, 
                        slug,
                        content, 
                        excerpt, 
                        image_path, 
                        created_at, 
                        updated_at,
                        status";
                        
                $values = "?, ?, ?, ?, ?, ?, ?, ?";
                $params = [$title, $slug, $content, $excerpt, $image_name, $date_created, $date_updated, $status];
                
                // Add category_id if it exists
                if ($has_category_id && $category_id) {
                    $sql .= ", category_id";
                    $values .= ", ?";
                    $params[] = $category_id;
                }
                
                // Add author_id if it exists
                if ($has_author_id && $author_id) {
                    $sql .= ", author_id";
                    $values .= ", ?";
                    $params[] = $author_id;
                }
                
                $sql .= ") VALUES (" . $values . ")";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                
                $success_message = "Blog post added successfully!";
            }
            
            // Redirect to blog posts list to avoid form resubmission
            header("Location: blog-posts.php");
            exit;
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Fetch all blog posts for display
try {
    $stmt = $conn->prepare("SELECT * FROM blog_posts ORDER BY created_at DESC");
    $stmt->execute();
    $blog_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching blog posts: " . $e->getMessage();
    $blog_posts = [];
}

// Fetch categories if we have category_id
$categories = [];
if ($has_category_id) {
    try {
        $stmt = $conn->prepare("SELECT id, name FROM blog_categories ORDER BY name");
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Just continue if categories can't be fetched
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Posts - CloudBlitz Admin</title>
    
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
        /* Custom styles for action buttons */
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-view {
            background-color: #17a2b8;
            color: white;
        }
        
        .btn-view:hover {
            background-color: #138496;
            color: white;
        }
        
        .btn-edit {
            background-color: #ff7700;
            color: white;
        }
        
        .btn-edit:hover {
            background-color: #e66000;
            color: white;
        }
        
        .btn-delete {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background-color: #c82333;
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
            <!-- Top Navbar -->
            <?php include 'includes/navbar.php'; ?>
            
            <!-- Main Content -->
            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">
                        <?php echo isset($_GET['edit']) || isset($_GET['add']) ? (isset($_GET['edit']) ? 'Edit Blog Post' : 'Add New Blog Post') : 'Blog Posts'; ?>
                    </h1>
                    <?php if (isset($_GET['edit']) || isset($_GET['add'])): ?>
                        <a href="blog-posts.php" class="d-none d-sm-inline-block btn btn-secondary shadow-sm">
                            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to All Posts
                        </a>
                    <?php else: ?>
                        <a href="blog-posts.php?add=1" class="d-none d-sm-inline-block btn btn-primary shadow-sm">
                            <i class="fas fa-plus fa-sm text-white-50"></i> Add New Post
                        </a>
                    <?php endif; ?>
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
                
                <?php if (isset($_GET['edit']) || isset($_GET['add'])): ?>
                    <!-- Add/Edit Blog Post Form -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <?php echo isset($_GET['edit']) ? 'Edit Blog Post' : 'Add New Blog Post'; ?>
                            </h6>
                        </div>
                        <div class="card-body">
                            <form action="blog-posts.php" method="post" enctype="multipart/form-data">
                                <?php if (isset($_GET['edit'])): ?>
                                    <input type="hidden" name="post_id" value="<?php echo $_GET['edit']; ?>">
                                <?php endif; ?>
                                
                                <div class="form-group">
                                    <label for="title">Post Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($edit_data['title'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="slug">URL Slug</label>
                                    <input type="text" class="form-control" id="slug" name="slug" value="<?php echo htmlspecialchars($edit_data['slug'] ?? ''); ?>" placeholder="Auto-generated from title if left empty">
                                    <small class="form-text text-muted">The URL-friendly version of the title. Leave blank to auto-generate.</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="excerpt">Excerpt (Short Description)</label>
                                    <textarea class="form-control" id="excerpt" name="excerpt" rows="3"><?php echo htmlspecialchars($edit_data['excerpt'] ?? ''); ?></textarea>
                                    <small class="form-text text-muted">A brief summary of the post that appears on blog listing pages.</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="content">Full Content <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="content" name="content" rows="10" required><?php echo htmlspecialchars($edit_data['content'] ?? ''); ?></textarea>
                                </div>
                                
                                <?php if ($has_category_id && !empty($categories)): ?>
                                <div class="form-group">
                                    <label for="category_id">Category</label>
                                    <select class="form-control" id="category_id" name="category_id">
                                        <option value="">-- Select Category --</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" <?php echo (isset($edit_data['category_id']) && $edit_data['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($has_author_id): ?>
                                <input type="hidden" name="author_id" value="<?php echo $_SESSION['admin_id'] ?? ''; ?>">
                                <?php endif; ?>
                                
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="published" <?php echo (isset($edit_data['status']) && $edit_data['status'] == 'published') ? 'selected' : ''; ?>>Published</option>
                                        <option value="draft" <?php echo (isset($edit_data['status']) && $edit_data['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="image">Featured Image <?php echo isset($_GET['edit']) ? '' : '<span class="text-danger">*</span>'; ?></label>
                                    <?php if (isset($edit_data['image_path']) && !empty($edit_data['image_path'])): ?>
                                        <div class="mb-3">
                                            <p class="mb-1">Current image:</p>
                                            <img src="../images/<?php echo htmlspecialchars($edit_data['image_path']); ?>" alt="Current featured image" class="img-thumbnail" style="max-width: 200px;">
                                        </div>
                                    <?php endif; ?>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="image" name="image" <?php echo isset($_GET['edit']) ? '' : 'required'; ?>>
                                        <label class="custom-file-label" for="image">Choose file</label>
                                    </div>
                                    <small class="form-text text-muted">Recommended size: 800x500 pixels. Allowed formats: JPG, JPEG, PNG, GIF.</small>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> <?php echo isset($_GET['edit']) ? 'Update Post' : 'Add Post'; ?>
                                </button>
                                
                                <?php if (isset($_GET['edit'])): ?>
                                    <a href="blog-posts.php" class="btn btn-secondary">Cancel</a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Blog Posts List -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">All Blog Posts</h6>
                            <a href="blog-posts.php?add=1" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus fa-sm"></i> Add New Post
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th width="5%">ID</th>
                                            <th width="15%">Image</th>
                                            <th width="40%">Title</th>
                                            <?php if ($has_author_id): ?>
                                            <th width="15%">Author</th>
                                            <?php endif; ?>
                                            <?php if ($has_category_id): ?>
                                            <th width="15%">Category</th>
                                            <?php endif; ?>
                                            <th width="15%">Date Posted</th>
                                            <th width="10%">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($blog_posts)): ?>
                                            <?php foreach ($blog_posts as $post): ?>
                                                <tr>
                                                    <td><?php echo $post['id']; ?></td>
                                                    <td>
                                                        <?php if (!empty($post['image_path'])): ?>
                                                            <img src="../images/<?php echo htmlspecialchars($post['image_path']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" style="width: 80px; height: 60px; object-fit: cover;">
                                                        <?php else: ?>
                                                            <span class="text-muted">No image</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($post['title']); ?>
                                                        <span class="badge badge-<?php echo $post['status'] == 'published' ? 'success' : 'secondary'; ?>">
                                                            <?php echo ucfirst($post['status']); ?>
                                                        </span>
                                                    </td>
                                                    <?php if ($has_author_id): ?>
                                                    <td>
                                                        <?php 
                                                        if (isset($post['author_id']) && !empty($post['author_id'])) {
                                                            // You would typically fetch the author name here
                                                            echo "Author ID: " . htmlspecialchars($post['author_id']);
                                                        } else {
                                                            echo '<span class="text-muted">Not set</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <?php endif; ?>
                                                    <?php if ($has_category_id): ?>
                                                    <td>
                                                        <?php 
                                                        if (isset($post['category_id']) && !empty($post['category_id'])) {
                                                            // You would typically fetch the category name here
                                                            echo "Category ID: " . htmlspecialchars($post['category_id']);
                                                        } else {
                                                            echo '<span class="text-muted">Not set</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <?php endif; ?>
                                                    <td><?php echo date('M d, Y', strtotime($post['created_at'])); ?></td>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <a href="../blog-post.php?id=<?php echo $post['id']; ?>" class="btn btn-view btn-sm" target="_blank" title="View Post">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="blog-posts.php?edit=<?php echo $post['id']; ?>" class="btn btn-edit btn-sm" title="Edit Post">
                                                                <i class="fas fa-pencil-alt"></i>
                                                            </a>
                                                            <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $post['id']; ?>, '<?php echo addslashes($post['title']); ?>')" class="btn btn-delete btn-sm" title="Delete Post">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="<?php echo 4 + ($has_author_id ? 1 : 0) + ($has_category_id ? 1 : 0); ?>" class="text-center">No blog posts found. <a href="blog-posts.php?add=1">Add your first post</a>.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
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
    
    <!-- CKEditor CDN -->
    <!-- <script src="https://cdn.ckeditor.com/ckeditor5/35.0.1/classic/ckeditor.js"></script> -->
    
    <!-- Custom JS -->
    <script src="js/admin.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#dataTable').DataTable({
                "order": [[0, "desc"]], // Sort by ID descending by default
                "pageLength": 10,
                "language": {
                    "lengthMenu": "Show _MENU_ posts per page",
                    "zeroRecords": "No posts found",
                    "info": "Showing page _PAGE_ of _PAGES_",
                    "infoEmpty": "No posts available",
                    "infoFiltered": "(filtered from _MAX_ total posts)"
                }
            });
            
            // Update custom file input label with selected filename
            $('.custom-file-input').on('change', function() {
                var fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').html(fileName);
            });
            
            // Initialize CKEditor for content if available
            if (typeof ClassicEditor !== 'undefined') {
                ClassicEditor
                    .create(document.querySelector('#content'))
                    .catch(error => {
                        console.error(error);
                    });
            }
            
            // Auto-generate slug from title
            $('#title').on('blur', function() {
                var slug = $('#slug');
                if (slug.val() === '') {
                    var title = $(this).val();
                    // Convert to lowercase, replace spaces with hyphens, remove special characters
                    var slugValue = title.toLowerCase()
                                         .replace(/[^a-z0-9 -]/g, '')
                                         .replace(/\s+/g, '-')
                                         .replace(/-+/g, '-');
                    slug.val(slugValue);
                }
            });
        });
        
        function confirmDelete(id, title) {
            if (confirm('Are you sure you want to delete the blog post "' + title + '"?\n\nThis action cannot be undone!')) {
                window.location.href = 'blog-posts.php?delete=' + id;
            }
        }
    </script>
</body>
</html>