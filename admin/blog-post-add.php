<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

// Include database connection
require_once "../config/db_connect.php";

// Define variables and initialize with empty values
$title = $slug = $content = $excerpt = $category_id = $image_path = $status = "";
$title_err = $slug_err = $content_err = $category_id_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate title
    if (empty(trim($_POST["title"]))) {
        $title_err = "Please enter a title.";
    } else {
        $title = trim($_POST["title"]);
    }
    
    // Validate slug
    if (empty(trim($_POST["slug"]))) {
        $slug_err = "Please enter a slug.";
    } else {
        // Check if slug exists
        $sql = "SELECT id FROM blog_posts WHERE slug = ?";
        
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("s", $param_slug);
            
            $param_slug = trim($_POST["slug"]);
            
            if ($stmt->execute()) {
                $stmt->store_result();
                
                if ($stmt->num_rows > 0) {
                    $slug_err = "This slug is already taken.";
                } else {
                    $slug = trim($_POST["slug"]);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            
            $stmt->close();
        }
    }
    
    // Validate content
    if (empty(trim($_POST["content"]))) {
        $content_err = "Please enter content.";
    } else {
        $content = trim($_POST["content"]);
    }
    
    // Validate category
    if (empty(trim($_POST["category_id"]))) {
        $category_id_err = "Please select a category.";
    } else {
        $category_id = trim($_POST["category_id"]);
    }
    
    // Get other form data
    $excerpt = trim($_POST["excerpt"]);
    $status = trim($_POST["status"]);
    
    // Handle image upload
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
        $target_dir = "../uploads/blog/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $new_filename = uniqid() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Check if image file is an actual image
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check !== false) {
            // Upload file
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_path = "uploads/blog/" . $new_filename;
            } else {
                echo "Sorry, there was an error uploading your file.";
            }
        } else {
            echo "File is not an image.";
        }
    }
    
    // Check input errors before inserting in database
    if (empty($title_err) && empty($slug_err) && empty($content_err) && empty($category_id_err)) {
        
        // Prepare an insert statement
        $sql = "INSERT INTO blog_posts (title, slug, content, excerpt, category_id, author_id, image_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("ssssssss", $param_title, $param_slug, $param_content, $param_excerpt, $param_category_id, $param_author_id, $param_image_path, $param_status);
            
            // Set parameters
            $param_title = $title;
            $param_slug = $slug;
            $param_content = $content;
            $param_excerpt = $excerpt;
            $param_category_id = $category_id;
            $param_author_id = $_SESSION["id"]; // Assuming user ID is stored in session
            $param_image_path = $image_path;
            $param_status = $status;
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Redirect to blog posts page
                header("location: blog-posts.php");
                exit();
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            
            $stmt->close();
        }
    }
    
    $mysqli->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Blog Post - CloudBlitz Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <!-- CKEditor CDN -->
    <script src="https://cdn.ckeditor.com/ckeditor5/35.0.1/classic/ckeditor.js"></script>
</head>
<body>
    <div class="wrapper">
        <!-- Include Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="content-area">
            <!-- Include Navbar -->
            <?php include 'includes/navbar.php'; ?>
            
            <div class="main-content">
                <div class="card">
                    <div class="card-header">
                        <h4>Add New Blog Post</h4>
                        <a href="blog-posts.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Posts
                        </a>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data" class="needs-validation">
                            <div class="form-group">
                                <label for="title">Title</label>
                                <input type="text" name="title" id="title" class="form-control <?php echo (!empty($title_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $title; ?>" required>
                                <span class="text-danger"><?php echo $title_err; ?></span>
                            </div>
                            
                            <div class="form-group">
                                <label for="slug">Slug</label>
                                <input type="text" name="slug" id="slug" class="form-control <?php echo (!empty($slug_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $slug; ?>" required>
                                <span class="text-danger"><?php echo $slug_err; ?></span>
                            </div>
                            
                            <div class="form-group">
                                <label for="category_id">Category</label>
                                <select name="category_id" id="category_id" class="form-control <?php echo (!empty($category_id_err)) ? 'is-invalid' : ''; ?>" required>
                                    <option value="">Select Category</option>
                                    <?php
                                    // Fetch all categories
                                    $sql = "SELECT id, name FROM blog_categories";
                                    $result = $mysqli->query($sql);
                                    
                                    if ($result->num_rows > 0) {
                                        while ($category = $result->fetch_assoc()) {
                                            $selected = ($category["id"] == $category_id) ? "selected" : "";
                                            echo "<option value='" . $category["id"] . "' " . $selected . ">" . $category["name"] . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                                <span class="text-danger"><?php echo $category_id_err; ?></span>
                            </div>
                            
                            <div class="form-group">
                                <label for="content">Content</label>
                                <textarea name="content" id="content" class="form-control wysiwyg-editor <?php echo (!empty($content_err)) ? 'is-invalid' : ''; ?>" rows="10" required><?php echo $content; ?></textarea>
                                <span class="text-danger"><?php echo $content_err; ?></span>
                            </div>
                            
                            <div class="form-group">
                                <label for="excerpt">Excerpt</label>
                                <textarea name="excerpt" id="excerpt" class="form-control" rows="3"><?php echo $excerpt; ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="image">Featured Image</label>
                                <div class="mb-2">
                                    <img src="/placeholder.svg" alt="Image Preview" id="image-preview" style="max-width: 200px; max-height: 200px; display: none;">
                                </div>
                                <input type="file" name="image" id="image" class="form-control image-upload" data-preview="image-preview" accept="image/*">
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="draft" <?php echo ($status == "draft") ? "selected" : ""; ?>>Draft</option>
                                    <option value="published" <?php echo ($status == "published") ? "selected" : ""; ?>>Published</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus-circle"></i> Add Post
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/admin.js"></script>
    <script>
        // Auto-generate slug from title
        document.getElementById('title').addEventListener('keyup', function() {
            let slug = this.value.toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/(^-|-$)/g, '');
            document.getElementById('slug').value = slug;
        });
    </script>
</body>
</html>