<?php
// Include database connection
require_once '../includes/db_connect.php';

// Check if admin is logged in
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
$success = false;
$instructor = null;

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: instructors.php');
    exit;
}

$id = $_GET['id'];

// Get instructor data
$stmt = $conn->prepare("SELECT * FROM instructors WHERE id = ?");
$stmt->bindValue(1, $id, PDO::PARAM_INT);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    header('Location: instructors.php');
    exit;
}

$instructor = $stmt->fetch(PDO::FETCH_ASSOC);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Check if email already exists (excluding current instructor)
    $stmt = $conn->prepare("SELECT id FROM instructors WHERE email = ? AND id != ?");
    $stmt->bindValue(1, $email, PDO::PARAM_STR);
    $stmt->bindValue(2, $id, PDO::PARAM_INT);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        $errors[] = "Email already exists";
    }
    
    // Process image upload if provided
    $image_path = $instructor['image_path'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['image']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
        } else {
            $upload_dir = '../uploads/instructors/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['image']['name']);
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                // Delete old image if exists
                if (!empty($instructor['image_path']) && file_exists('../' . $instructor['image_path'])) {
                    unlink('../' . $instructor['image_path']);
                }
                
                $image_path = 'uploads/instructors/' . $file_name;
            } else {
                $errors[] = "Failed to upload image";
            }
        }
    }
    
    // If no errors, update database
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE instructors SET name = ?, email = ?, title = ?, bio = ?, image_path = ? WHERE id = ?");
        $stmt->bindValue(1, $name, PDO::PARAM_STR);
        $stmt->bindValue(2, $email, PDO::PARAM_STR);
        $stmt->bindValue(3, $title, PDO::PARAM_STR);
        $stmt->bindValue(4, $bio, PDO::PARAM_STR);
        $stmt->bindValue(5, $image_path, PDO::PARAM_STR);
        $stmt->bindValue(6, $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $success = true;
            // Refresh instructor data
            $stmt = $conn->prepare("SELECT * FROM instructors WHERE id = ?");
            $stmt->bindValue(1, $id, PDO::PARAM_INT);
            $stmt->execute();
            $instructor = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $errors[] = "Database error: " . implode(", ", $stmt->errorInfo());
        }
    }
}

// Set page title
$page_title = "Edit Instructor";
include 'includes/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Edit Instructor</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="instructors.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Instructors
                    </a>
                </div>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Instructor updated successfully.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <form action="edit-instructor.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required 
                               value="<?php echo htmlspecialchars($instructor['name']); ?>">
                        <div class="invalid-feedback">Name is required</div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required 
                               value="<?php echo htmlspecialchars($instructor['email']); ?>">
                        <div class="invalid-feedback">Valid email is required</div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="title" class="form-label">Title/Position</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="<?php echo htmlspecialchars($instructor['title'] ?? ''); ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label for="image" class="form-label">Profile Image</label>
                        <?php if (!empty($instructor['image_path'])): ?>
                            <div class="mb-2">
                                <img src="../<?php echo htmlspecialchars($instructor['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($instructor['name']); ?>" 
                                     width="100" class="img-thumbnail">
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="image" name="image">
                        <div class="form-text">Upload a new image to replace the current one. Max size: 2MB</div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="bio" class="form-label">Biography</label>
                    <textarea class="form-control" id="bio" name="bio" rows="5"><?php echo htmlspecialchars($instructor['bio'] ?? ''); ?></textarea>
                </div>
                
                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">Update Instructor</button>
                    <a href="instructors.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </main>
    </div>
</div>

<script>
// Form validation
(function() {
    'use strict';
    
    var forms = document.querySelectorAll('.needs-validation');
    
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

