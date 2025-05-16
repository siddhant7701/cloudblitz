<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

require_once '../includes/db_connect.php';

// Check database schema to determine column names
$columns = [];
try {
    $stmt = $conn->prepare("DESCRIBE courses");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
    }
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic course information
    $title = trim($_POST['title']);
    $slug = trim($_POST['slug'] ?? strtolower(str_replace(' ', '-', $title)));
    $short_description = trim($_POST['short_description']);
    $description = trim($_POST['description']);
    $price = !empty($_POST['price']) ? $_POST['price'] : 0;
    $duration = trim($_POST['duration']);
    $level = $_POST['level'];
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
    $instructor_id = !empty($_POST['instructor_id']) ? $_POST['instructor_id'] : null;
    $status = $_POST['status'] ?? 'draft';

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

    // Handle image upload
    $image_path = '';

    // Process main course image
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        
        if (in_array($_FILES['image']['type'], $allowed_types)) {
            $upload_dir = '../uploads/courses/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $image_name = time() . '_' . $_FILES['image']['name'];
            $upload_path = $upload_dir . $image_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_path = 'uploads/courses/' . $image_name;
            } else {
                $error_message = "Failed to upload image.";
            }
        } else {
            $error_message = "Invalid image format. Allowed formats: JPG, JPEG, PNG, GIF.";
        }
    } else {
        $error_message = "Course image is required.";
    }

    // Process banner image if provided
    $banner_image = '';
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        
        if (in_array($_FILES['banner_image']['type'], $allowed_types)) {
            $upload_dir = '../uploads/courses/banners/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $banner_name = time() . '_banner_' . $_FILES['banner_image']['name'];
            $upload_path = $upload_dir . $banner_name;
            
            if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $upload_path)) {
                $banner_image = 'uploads/courses/banners/' . $banner_name;
            } else {
                $error_message = "Failed to upload banner image.";
            }
        } else {
            $error_message = "Invalid banner image format. Allowed formats: JPG, JPEG, PNG, GIF.";
        }
    }

    if (!isset($error_message)) {
        try {
            // Build SQL query based on existing columns
            $sql_fields = "title, slug";
            $sql_values = "?, ?";
            $params = [$title, $slug];
            
            if (in_array('short_description', $columns)) {
                $sql_fields .= ", short_description";
                $sql_values .= ", ?";
                $params[] = $short_description;
            }
            
            $sql_fields .= ", description, price, duration, level, category_id, instructor_id";
            $sql_values .= ", ?, ?, ?, ?, ?, ?";
            $params = array_merge($params, [$description, $price, $duration, $level, $category_id, $instructor_id]);
            
            if (in_array('requirements', $columns)) {
                $sql_fields .= ", requirements";
                $sql_values .= ", ?";
                $params[] = $requirements;
            }
            
            if (in_array('outcomes', $columns)) {
                $sql_fields .= ", outcomes";
                $sql_values .= ", ?";
                $params[] = $outcomes;
            }
            
            if (in_array('curriculum', $columns)) {
                $sql_fields .= ", curriculum";
                $sql_values .= ", ?";
                $params[] = $curriculum;
            }
            
            if (in_array('job_support', $columns)) {
                $sql_fields .= ", job_support";
                $sql_values .= ", ?";
                $params[] = $job_support;
            }
            
            if (in_array('certification', $columns)) {
                $sql_fields .= ", certification";
                $sql_values .= ", ?";
                $params[] = $certification;
            }
            
            if (in_array('course_duration_info', $columns)) {
                $sql_fields .= ", course_duration_info";
                $sql_values .= ", ?";
                $params[] = $course_duration_info;
            }
            
            if (in_array('learning_mode_info', $columns)) {
                $sql_fields .= ", learning_mode_info";
                $sql_values .= ", ?";
                $params[] = $learning_mode_info;
            }
            
            if (in_array('upcoming_batches', $columns)) {
                $sql_fields .= ", upcoming_batches";
                $sql_values .= ", ?";
                $params[] = $upcoming_batches;
            }
            
            // Add image_path field
            if (in_array('image_path', $columns)) {
                $sql_fields .= ", image_path";
                $sql_values .= ", ?";
                $params[] = $image_path;
            }
            
            // Add banner_image field if it exists
            if (in_array('banner_image', $columns)) {
                $sql_fields .= ", banner_image";
                $sql_values .= ", ?";
                $params[] = $banner_image;
            }
            
            $sql_fields .= ", status, created_at";
            $sql_values .= ", ?, NOW()";
            $params[] = $status;
            
            // Create course
            $stmt = $conn->prepare("
                INSERT INTO courses (
                    $sql_fields
                ) VALUES (
                    $sql_values
                )
            ");
            
            $stmt->execute($params);
            
            $success_message = "Course added successfully!";
            
            // Clear form data after successful submission
            $title = $slug = $short_description = $description = $price = $duration = $requirements = $outcomes = '';
            $curriculum = $job_support = $certification = $course_duration_info = $learning_mode_info = $upcoming_batches = '';
            $level = 'beginner';
            $category_id = $instructor_id = null;
            $status = 'draft';
            
        } catch(PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
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
    <title>Add Course - Admin Panel</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <!-- Admin CSS -->
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
            <!-- Navbar -->
            <?php include 'includes/navbar.php'; ?>
            
            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Add New Course</h1>
                    <a href="courses.php" class="d-none d-sm-inline-block btn btn-secondary shadow-sm">
                        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Courses
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
                
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Course Details</h6>
                    </div>
                    <div class="card-body">
                        <form action="add-course.php" method="POST" enctype="multipart/form-data">
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
                                        <label for="title">Course Title <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="title" name="title" value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="slug">Slug</label>
                                        <input type="text" class="form-control" id="slug" name="slug" value="<?php echo isset($slug) ? htmlspecialchars($slug) : ''; ?>">
                                        <small class="form-text text-muted">The slug is used in the URL. Leave empty to generate automatically from title.</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="short_description">Short Description <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="short_description" name="short_description" rows="2" required><?php echo isset($short_description) ? htmlspecialchars($short_description) : ''; ?></textarea>
                                        <small class="form-text text-muted">A brief description that appears in course listings.</small>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="category_id">Category</label>
                                            <select class="form-control" id="category_id" name="category_id">
                                                <option value="">Select Category</option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?php echo $category['id']; ?>" <?php echo (isset($category_id) && $category_id == $category['id']) ? 'selected' : ''; ?>>
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
                                                    <option value="<?php echo $instructor['id']; ?>" <?php echo (isset($instructor_id) && $instructor_id == $instructor['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($instructor['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group col-md-4">
                                            <label for="price">Price ($) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="price" name="price" step="0.01" value="<?php echo isset($price) ? htmlspecialchars($price) : ''; ?>" required>
                                        </div>
                                        
                                        <div class="form-group col-md-4">
                                            <label for="duration">Duration <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="duration" name="duration" value="<?php echo isset($duration) ? htmlspecialchars($duration) : ''; ?>" required>
                                            <small class="form-text text-muted">Example: 8 weeks, 3 months, etc.</small>
                                        </div>
                                        
                                        <div class="form-group col-md-4">
                                            <label for="level">Level <span class="text-danger">*</span></label>
                                            <select class="form-control" id="level" name="level" required>
                                                <option value="beginner" <?php echo (isset($level) && $level === 'beginner') ? 'selected' : ''; ?>>Beginner</option>
                                                <option value="intermediate" <?php echo (isset($level) && $level === 'intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                                <option value="advanced" <?php echo (isset($level) && $level === 'advanced') ? 'selected' : ''; ?>>Advanced</option>
                                                <option value="all-levels" <?php echo (isset($level) && $level === 'all-levels') ? 'selected' : ''; ?>>All Levels</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="status">Status</label>
                                        <select class="form-control" id="status" name="status">
                                            <option value="draft" <?php echo (isset($status) && $status === 'draft') ? 'selected' : ''; ?>>Draft</option>
                                            <option value="published" <?php echo (isset($status) && $status === 'published') ? 'selected' : ''; ?>>Published</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Course Details Tab -->
                                <div class="tab-pane fade" id="details" role="tabpanel">
                                    <div class="form-section">
                                        <h5 class="form-section-title">Course Overview</h5>
                                        <div class="form-group">
                                            <label for="description">Course Description <span class="text-danger">*</span></label>
                                            <textarea class="form-control" id="description" name="description" rows="8" required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                                            <small class="form-text text-muted">Detailed description of the course. This will appear in the Course Overview tab.</small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-section">
                                        <h5 class="form-section-title">Practical Training</h5>
                                        <div class="form-group">
                                            <label for="curriculum">Curriculum Content</label>
                                            <textarea class="form-control" id="curriculum" name="curriculum" rows="8"><?php echo isset($curriculum) ? htmlspecialchars($curriculum) : ''; ?></textarea>
                                            <small class="form-text text-muted">Course curriculum details. This will appear in the Practical Training tab.</small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-section">
                                        <h5 class="form-section-title">Job Support</h5>
                                        <div class="form-group">
                                            <label for="job_support">Job Support Details</label>
                                            <textarea class="form-control" id="job_support" name="job_support" rows="8"><?php echo isset($job_support) ? htmlspecialchars($job_support) : ''; ?></textarea>
                                            <small class="form-text text-muted">Job support information. This will appear in the Job Support tab.</small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-section">
                                        <h5 class="form-section-title">Certification</h5>
                                        <div class="form-group">
                                            <label for="certification">Certification Details</label>
                                            <textarea class="form-control" id="certification" name="certification" rows="8"><?php echo isset($certification) ? htmlspecialchars($certification) : ''; ?></textarea>
                                            <small class="form-text text-muted">Certification information. This will appear in the Certification tab.</small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-section">
                                        <h5 class="form-section-title">Course Requirements & Outcomes</h5>
                                        <div class="form-group">
                                            <label for="requirements">Requirements</label>
                                            <textarea class="form-control" id="requirements" name="requirements" rows="6"><?php echo isset($requirements) ? htmlspecialchars($requirements) : ''; ?></textarea>
                                            <small class="form-text text-muted">What students need to know before taking this course.</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="outcomes">What You'll Learn</label>
                                            <textarea class="form-control" id="outcomes" name="outcomes" rows="6"><?php echo isset($outcomes) ? htmlspecialchars($outcomes) : ''; ?></textarea>
                                            <small class="form-text text-muted">What students will gain from this course.</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Media Tab -->
                                <div class="tab-pane fade" id="media" role="tabpanel">
                                    <div class="form-group">
                                        <label for="image">Course Thumbnail Image <span class="text-danger">*</span></label>
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="image" name="image" required>
                                            <label class="custom-file-label" for="image">Choose file</label>
                                        </div>
                                        <small class="form-text text-muted">Recommended size: 800x500 pixels. This image will be used in course listings.</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="banner_image">Course Banner Image</label>
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="banner_image" name="banner_image">
                                            <label class="custom-file-label" for="banner_image">Choose file</label>
                                        </div>
                                        <small class="form-text text-muted">Recommended size: 1920x500 pixels. This image will be used as the course header banner.</small>
                                    </div>
                                </div>
                                
                                <!-- Additional Info Tab -->
                                <div class="tab-pane fade" id="metadata" role="tabpanel">
                                    <div class="form-section">
                                        <h5 class="form-section-title">Course Information Boxes</h5>
                                        <p class="text-muted">These fields will be displayed in the information boxes on the course page.</p>
                                        
                                        <div class="form-group">
                                            <label for="course_duration_info">Course Duration Information</label>
                                            <textarea class="form-control" id="course_duration_info" name="course_duration_info" rows="3"><?php echo isset($course_duration_info) ? htmlspecialchars($course_duration_info) : ''; ?></textarea>
                                            <small class="form-text text-muted">Example: 6 Months, Weekend Batches, Weekday Batches</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="learning_mode_info">Learning Mode Information</label>
                                            <textarea class="form-control" id="learning_mode_info" name="learning_mode_info" rows="3"><?php echo isset($learning_mode_info) ? htmlspecialchars($learning_mode_info) : ''; ?></textarea>
                                            <small class="form-text text-muted">Example: Online, Classroom, Self-Paced</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="upcoming_batches">Upcoming Batches</label>
                                            <textarea class="form-control" id="upcoming_batches" name="upcoming_batches" rows="3"><?php echo isset($upcoming_batches) ? htmlspecialchars($upcoming_batches) : ''; ?></textarea>
                                            <small class="form-text text-muted">Example: May 15, 2023, June 1, 2023, June 15, 2023</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus-circle"></i> Add Course
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i> Reset
                                </button>
                            </div>
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

    <!-- Admin JS -->
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
            
            // Update file input label with selected filename
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