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

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "No course ID provided.";
    header('Location: courses.php');
    exit;
}

$id = $_GET['id'];

// Get course data
try {
    $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        $_SESSION['error'] = "Course not found.";
        header('Location: courses.php');
        exit;
    }
    
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    header('Location: courses.php');
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic course information
    $title = trim($_POST['title']);
    $slug = trim($_POST['slug']);
    $short_description = trim($_POST['short_description']);
    $description = trim($_POST['description']);
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
    $instructor_id = !empty($_POST['instructor_id']) ? $_POST['instructor_id'] : null;
    $price = !empty($_POST['price']) ? $_POST['price'] : 0;
    $duration = trim($_POST['duration']);
    $level = $_POST['level'];
    $status = $_POST['status'];
    
    // Course sections
    $requirements = trim($_POST['requirements']);
    $outcomes = trim($_POST['outcomes']);
    $curriculum = trim($_POST['curriculum']);
    $job_support = trim($_POST['job_support']);
    $certification = trim($_POST['certification']);
    
    // Course metadata
    $course_duration_info = trim($_POST['course_duration_info']);
    $learning_mode_info = trim($_POST['learning_mode_info']);
    $upcoming_batches = trim($_POST['upcoming_batches']);
    
    // Validate required fields
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Title is required.";
    }
    
    if (empty($slug)) {
        $errors[] = "Slug is required.";
    }
    
    // Check if slug is unique (excluding current course)
    try {
        $stmt = $conn->prepare("SELECT id FROM courses WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $id]);
        
        if ($stmt->rowCount() > 0) {
            $errors[] = "Slug already exists. Please choose a different one.";
        }
    } catch (PDOException $e) {
        $errors[] = "Error checking slug: " . $e->getMessage();
    }
    
    // Handle image upload if provided
    $image_path = $course['image_path'] ?? ''; // Keep existing image by default
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = '../uploads/courses/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $upload_dir . $new_filename;
        
        // Check if file is an actual image
        $check = getimagesize($_FILES['image']['tmp_name']);
        if ($check !== false) {
            // Upload file
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path = 'uploads/courses/' . $new_filename;
                
                // Delete old image if it exists
                if (!empty($course['image_path']) && file_exists('../' . $course['image_path'])) {
                    unlink('../' . $course['image_path']);
                }
            } else {
                $errors[] = "Sorry, there was an error uploading your file.";
            }
        } else {
            $errors[] = "File is not an image.";
        }
    }
    
    // Handle banner image upload if provided
    $banner_image = $course['banner_image'] ?? ''; // Keep existing banner by default
    
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === 0) {
        $upload_dir = '../uploads/courses/banners/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $upload_dir . $new_filename;
        
        // Check if file is an actual image
        $check = getimagesize($_FILES['banner_image']['tmp_name']);
        if ($check !== false) {
            // Upload file
            if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $target_file)) {
                $banner_image = 'uploads/courses/banners/' . $new_filename;
                
                // Delete old banner image if it exists
                if (!empty($course['banner_image']) && file_exists('../' . $course['banner_image'])) {
                    unlink('../' . $course['banner_image']);
                }
            } else {
                $errors[] = "Sorry, there was an error uploading your banner image.";
            }
        } else {
            $errors[] = "Banner file is not an image.";
        }
    }
    
    // If no errors, update course
    if (empty($errors)) {
        try {
            // Check which columns exist in the database
            $columns = [];
            $stmt = $conn->prepare("DESCRIBE courses");
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $columns[] = $row['Field'];
            }
            
            // Build the SQL query based on existing columns
            $sql = "UPDATE courses SET title = ?, slug = ?";
            $params = [$title, $slug];
            
            if (in_array('short_description', $columns)) {
                $sql .= ", short_description = ?";
                $params[] = $short_description;
            }
            
            $sql .= ", description = ?, category_id = ?, instructor_id = ?, price = ?, duration = ?, level = ?, requirements = ?, outcomes = ?";
            $params = array_merge($params, [$description, $category_id, $instructor_id, $price, $duration, $level, $requirements, $outcomes]);
            
            if (in_array('curriculum', $columns)) {
                $sql .= ", curriculum = ?";
                $params[] = $curriculum;
            }
            
            if (in_array('job_support', $columns)) {
                $sql .= ", job_support = ?";
                $params[] = $job_support;
            }
            
            if (in_array('certification', $columns)) {
                $sql .= ", certification = ?";
                $params[] = $certification;
            }
            
            if (in_array('course_duration_info', $columns)) {
                $sql .= ", course_duration_info = ?";
                $params[] = $course_duration_info;
            }
            
            if (in_array('learning_mode_info', $columns)) {
                $sql .= ", learning_mode_info = ?";
                $params[] = $learning_mode_info;
            }
            
            if (in_array('upcoming_batches', $columns)) {
                $sql .= ", upcoming_batches = ?";
                $params[] = $upcoming_batches;
            }
            
            // Add image_path field
            if (in_array('image_path', $columns)) {
                $sql .= ", image_path = ?";
                $params[] = $image_path;
            }
            
            // Add banner_image field if it exists
            if (in_array('banner_image', $columns)) {
                $sql .= ", banner_image = ?";
                $params[] = $banner_image;
            }
            
            $sql .= ", updated_at = NOW(), status = ? WHERE id = ?";
            $params[] = $status;
            $params[] = $id;
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            $_SESSION['success'] = "Course updated successfully.";
            header('Location: courses.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = "Error updating course: " . $e->getMessage();
        }
    }
}

// Get all categories
try {
    $stmt = $conn->prepare("SELECT id, name FROM course_categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}

// Get all instructors
try {
    $stmt = $conn->prepare("SELECT id, name FROM instructors ORDER BY name");
    $stmt->execute();
    $instructors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $instructors = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Course - CloudBlitz Admin</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/admin.css">
    
    <style>
        .tab-content {
            padding: 20px;
            border: 1px solid #dee2e6;
            border-top: none;
            border-radius: 0 0 0.25rem 0.25rem;
        }
        
        .nav-tabs .nav-link {
            font-weight: 500;
        }
        
        .nav-tabs .nav-link.active {
            background-color: #f8f9fa;
            border-bottom-color: #f8f9fa;
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .form-section-title {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e3e6f0;
            font-weight: 600;
        }
        
        textarea {
            min-height: 120px;
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
                    <h1 class="h3 mb-0 text-gray-800">Edit Course</h1>
                    <a href="courses.php" class="d-none d-sm-inline-block btn btn-secondary shadow-sm">
                        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Courses
                    </a>
                </div>
                
                <?php if (isset($errors) && !empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Course Information</h6>
                    </div>
                    <div class="card-body">
                        <form action="edit-course.php?id=<?php echo $id; ?>" method="post" enctype="multipart/form-data">
                            <!-- Nav tabs -->
                            <ul class="nav nav-tabs" id="courseTab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="basic-tab" data-toggle="tab" href="#basic" role="tab">Basic Info</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="details-tab" data-toggle="tab" href="#details" role="tab">Course Details</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="media-tab" data-toggle="tab" href="#media" role="tab">Media</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="metadata-tab" data-toggle="tab" href="#metadata" role="tab">Additional Info</a>
                                </li>
                            </ul>
                            
                            <!-- Tab content -->
                            <div class="tab-content" id="courseTabContent">
                                <!-- Basic Info Tab -->
                                <div class="tab-pane fade show active" id="basic" role="tabpanel">
                                    <div class="form-group">
                                        <label for="title">Title <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($course['title']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="slug">Slug <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="slug" name="slug" value="<?php echo htmlspecialchars($course['slug'] ?? ''); ?>" required>
                                        <small class="form-text text-muted">The slug is used in the URL. Use only lowercase letters, numbers, and hyphens.</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="short_description">Short Description <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="short_description" name="short_description" rows="2" required><?php echo htmlspecialchars($course['short_description'] ?? ''); ?></textarea>
                                        <small class="form-text text-muted">A brief description that appears in course listings.</small>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="category_id">Category</label>
                                            <select class="form-control" id="category_id" name="category_id">
                                                <option value="">Select Category</option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?php echo $category['id']; ?>" <?php echo ($course['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($category['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group col-md-6">
                                            <label for="instructor_id">Instructor</label>
                                            <select class="form-control" id="instructor_id" name="instructor_id">
                                                <option value="">Select Instructor</option>
                                                <?php foreach ($instructors as $instructor): ?>
                                                    <option value="<?php echo $instructor['id']; ?>" <?php echo ($course['instructor_id'] == $instructor['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($instructor['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group col-md-4">
                                            <label for="price">Price</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">$</span>
                                                </div>
                                                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($course['price']); ?>">
                                            </div>
                                            <small class="form-text text-muted">Leave 0 for free courses.</small>
                                        </div>
                                        
                                        <div class="form-group col-md-4">
                                            <label for="duration">Duration</label>
                                            <input type="text" class="form-control" id="duration" name="duration" value="<?php echo htmlspecialchars($course['duration']); ?>" placeholder="e.g., 10 hours">
                                        </div>
                                        
                                        <div class="form-group col-md-4">
                                            <label for="level">Level</label>
                                            <select class="form-control" id="level" name="level">
                                                <option value="beginner" <?php echo ($course['level'] == 'beginner') ? 'selected' : ''; ?>>Beginner</option>
                                                <option value="intermediate" <?php echo ($course['level'] == 'intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                                <option value="advanced" <?php echo ($course['level'] == 'advanced') ? 'selected' : ''; ?>>Advanced</option>
                                                <option value="all-levels" <?php echo ($course['level'] == 'all-levels') ? 'selected' : ''; ?>>All Levels</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="status">Status</label>
                                        <select class="form-control" id="status" name="status">
                                            <option value="draft" <?php echo ($course['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                                            <option value="published" <?php echo ($course['status'] == 'published') ? 'selected' : ''; ?>>Published</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Course Details Tab -->
                                <div class="tab-pane fade" id="details" role="tabpanel">
                                    <div class="form-section">
                                        <h5 class="form-section-title">Course Overview</h5>
                                        <div class="form-group">
                                            <label for="description">Course Description</label>
                                            <textarea class="form-control" id="description" name="description" rows="8"><?php echo htmlspecialchars($course['description']); ?></textarea>
                                            <small class="form-text text-muted">Detailed description of the course. This will appear in the Course Overview tab.</small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-section">
                                        <h5 class="form-section-title">Practical Training</h5>
                                        <div class="form-group">
                                            <label for="curriculum">Curriculum Content</label>
                                            <textarea class="form-control" id="curriculum" name="curriculum" rows="8"><?php echo htmlspecialchars($course['curriculum'] ?? ''); ?></textarea>
                                            <small class="form-text text-muted">Course curriculum details. This will appear in the Practical Training tab.</small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-section">
                                        <h5 class="form-section-title">Job Support</h5>
                                        <div class="form-group">
                                            <label for="job_support">Job Support Details</label>
                                            <textarea class="form-control" id="job_support" name="job_support" rows="8"><?php echo htmlspecialchars($course['job_support'] ?? ''); ?></textarea>
                                            <small class="form-text text-muted">Job support information. This will appear in the Job Support tab.</small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-section">
                                        <h5 class="form-section-title">Certification</h5>
                                        <div class="form-group">
                                            <label for="certification">Certification Details</label>
                                            <textarea class="form-control" id="certification" name="certification" rows="8"><?php echo htmlspecialchars($course['certification'] ?? ''); ?></textarea>
                                            <small class="form-text text-muted">Certification information. This will appear in the Certification tab.</small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-section">
                                        <h5 class="form-section-title">Course Requirements & Outcomes</h5>
                                        <div class="form-group">
                                            <label for="requirements">Requirements</label>
                                            <textarea class="form-control" id="requirements" name="requirements" rows="6"><?php echo htmlspecialchars($course['requirements']); ?></textarea>
                                            <small class="form-text text-muted">What students need to know before taking this course.</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="outcomes">What You'll Learn</label>
                                            <textarea class="form-control" id="outcomes" name="outcomes" rows="6"><?php echo htmlspecialchars($course['outcomes']); ?></textarea>
                                            <small class="form-text text-muted">What students will gain from this course.</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Media Tab -->
                                <div class="tab-pane fade" id="media" role="tabpanel">
                                    <div class="form-group">
                                        <label for="image">Course Thumbnail Image</label>
                                        <?php if (!empty($course['image_path'])): ?>
                                            <div class="mb-2">
                                                <img src="../<?php echo htmlspecialchars($course['image_path']); ?>" alt="Course Image" class="img-thumbnail" style="max-width: 200px;">
                                            </div>
                                        <?php endif; ?>
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="image" name="image" accept="image/*">
                                            <label class="custom-file-label" for="image">Choose file</label>
                                        </div>
                                        <small class="form-text text-muted">Leave empty to keep current image. Recommended size: 800x500 pixels.</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="banner_image">Banner Image</label>
                                        <?php if (!empty($course['banner_image'])): ?>
                                            <div class="mb-2">
                                                <img src="../<?php echo htmlspecialchars($course['banner_image']); ?>" alt="Banner Image" class="img-thumbnail" style="max-width: 200px;">
                                            </div>
                                        <?php endif; ?>
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="banner_image" name="banner_image" accept="image/*">
                                            <label class="custom-file-label" for="banner_image">Choose file</label>
                                        </div>
                                        <small class="form-text text-muted">Leave empty to keep current banner image. Recommended size: 1920x500 pixels.</small>
                                    </div>
                                </div>
                                
                                <!-- Additional Info Tab -->
                                <div class="tab-pane fade" id="metadata" role="tabpanel">
                                    <div class="form-section">
                                        <h5 class="form-section-title">Course Information Boxes</h5>
                                        <p class="text-muted">These fields will be displayed in the information boxes on the course page.</p>
                                        
                                        <div class="form-group">
                                            <label for="course_duration_info">Course Duration Information</label>
                                            <textarea class="form-control" id="course_duration_info" name="course_duration_info" rows="3"><?php echo htmlspecialchars($course['course_duration_info'] ?? ''); ?></textarea>
                                            <small class="form-text text-muted">Example: 6 Months, Weekend Batches, Weekday Batches</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="learning_mode_info">Learning Mode Information</label>
                                            <textarea class="form-control" id="learning_mode_info" name="learning_mode_info" rows="3"><?php echo htmlspecialchars($course['learning_mode_info'] ?? ''); ?></textarea>
                                            <small class="form-text text-muted">Example: Online, Classroom, Self-Paced</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="upcoming_batches">Upcoming Batches</label>
                                            <textarea class="form-control" id="upcoming_batches" name="upcoming_batches" rows="3"><?php echo htmlspecialchars($course['upcoming_batches'] ?? ''); ?></textarea>
                                            <small class="form-text text-muted">Example: May 15, 2023, June 1, 2023, June 15, 2023</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary mt-4">
                                <i class="fas fa-save"></i> Update Course
                            </button>
                        </form>
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
    
    <script>
        $(document).ready(function() {
            // Auto-generate slug from title
            $('#title').on('keyup', function() {
                if (!$('#slug').data('manually-changed')) {
                    var slug = $(this).val()
                        .toLowerCase()
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/-+/g, '-')
                        .replace(/^-|-$/g, '');
                    $('#slug').val(slug);
                }
            });
            
            // Mark slug as manually changed when user edits it
            $('#slug').on('keyup', function() {
                $(this).data('manually-changed', true);
            });
            
            // Update custom file input label with selected filename
            $('.custom-file-input').on('change', function() {
                var fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').html(fileName);
            });
            
            // Form validation before submit
            $('form').on('submit', function(e) {
                // Ensure at least one tab is shown to the user if there are validation errors
                if (!this.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Find the first invalid element
                    var firstInvalid = $(this).find(':invalid').first();
                    
                    // Find which tab pane it's in
                    var tabPane = firstInvalid.closest('.tab-pane');
                    
                    // Activate that tab
                    $('#courseTab a[href="#' + tabPane.attr('id') + '"]').tab('show');
                    
                    // Focus the first invalid element
                    firstInvalid.focus();
                }
                
                $(this).addClass('was-validated');
            });
        });
    </script>
</body>
</html>