<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

require_once '../includes/db_connect.php';

// Get module ID from URL
$moduleId = isset($_GET['module_id']) ? (int)$_GET['module_id'] : 0;

// Check if module exists
try {
    $stmt = $conn->prepare("SELECT m.*, c.id as course_id, c.title as course_title 
                           FROM course_modules m 
                           JOIN courses c ON m.course_id = c.id 
                           WHERE m.id = ?");
    $stmt->execute([$moduleId]);
    $module = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$module) {
        $_SESSION['error'] = "Module not found.";
        header('Location: courses.php');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header('Location: courses.php');
    exit;
}

// Check if lessons table exists, create if not
try {
    $stmt = $conn->prepare("SHOW TABLES LIKE 'course_lessons'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        $sql = "CREATE TABLE course_lessons (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            module_id INT(11) NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            content TEXT,
            type ENUM('video', 'text', 'quiz', 'assignment') DEFAULT 'text',
            video_url VARCHAR(255),
            duration VARCHAR(50),
            is_free TINYINT(1) DEFAULT 0,
            display_order INT(11) DEFAULT 0,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (module_id)
        )";
        $conn->exec($sql);
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new lesson
    if (isset($_POST['add_lesson'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $content = trim($_POST['content']);
        $type = $_POST['type'];
        $video_url = trim($_POST['video_url']);
        $duration = trim($_POST['duration']);
        $is_free = isset($_POST['is_free']) ? 1 : 0;
        $display_order = (int)$_POST['display_order'];
        $status = $_POST['status'];
        
        if (empty($title)) {
            $_SESSION['error'] = "Lesson title is required.";
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO course_lessons (module_id, title, description, content, type, video_url, duration, is_free, display_order, status) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$moduleId, $title, $description, $content, $type, $video_url, $duration, $is_free, $display_order, $status]);
                $_SESSION['success'] = "Lesson added successfully.";
                header("Location: course-lessons.php?module_id=$moduleId");
                exit;
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error adding lesson: " . $e->getMessage();
            }
        }
    }
    
    // Update lesson
    if (isset($_POST['update_lesson'])) {
        $lesson_id = (int)$_POST['lesson_id'];
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $content = trim($_POST['content']);
        $type = $_POST['type'];
        $video_url = trim($_POST['video_url']);
        $duration = trim($_POST['duration']);
        $is_free = isset($_POST['is_free']) ? 1 : 0;
        $display_order = (int)$_POST['display_order'];
        $status = $_POST['status'];
        
        if (empty($title)) {
            $_SESSION['error'] = "Lesson title is required.";
        } else {
            try {
                $stmt = $conn->prepare("UPDATE course_lessons SET title = ?, description = ?, content = ?, type = ?, video_url = ?, duration = ?, is_free = ?, display_order = ?, status = ? WHERE id = ? AND module_id = ?");
                $stmt->execute([$title, $description, $content, $type, $video_url, $duration, $is_free, $display_order, $status, $lesson_id, $moduleId]);
                $_SESSION['success'] = "Lesson updated successfully.";
                header("Location: course-lessons.php?module_id=$moduleId");
                exit;
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error updating lesson: " . $e->getMessage();
            }
        }
    }
    
    // Delete lesson
    if (isset($_POST['delete_lesson'])) {
        $lesson_id = (int)$_POST['lesson_id'];
        
        try {
            $stmt = $conn->prepare("DELETE FROM course_lessons WHERE id = ? AND module_id = ?");
            $stmt->execute([$lesson_id, $moduleId]);
            $_SESSION['success'] = "Lesson deleted successfully.";
            header("Location: course-lessons.php?module_id=$moduleId");
            exit;
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error deleting lesson: " . $e->getMessage();
        }
    }
    
    // Reorder lessons
    if (isset($_POST['reorder_lessons'])) {
        $lesson_ids = $_POST['lesson_ids'];
        $orders = $_POST['orders'];
        
        try {
            $conn->beginTransaction();
            
            for ($i = 0; $i < count($lesson_ids); $i++) {
                $stmt = $conn->prepare("UPDATE course_lessons SET display_order = ? WHERE id = ? AND module_id = ?");
                $stmt->execute([$orders[$i], $lesson_ids[$i], $moduleId]);
            }
            
            $conn->commit();
            $_SESSION['success'] = "Lessons reordered successfully.";
            header("Location: course-lessons.php?module_id=$moduleId");
            exit;
        } catch (PDOException $e) {
            $conn->rollBack();
            $_SESSION['error'] = "Error reordering lessons: " . $e->getMessage();
        }
    }
}

// Get lessons
$lessons = [];
try {
    $stmt = $conn->prepare("SELECT * FROM course_lessons WHERE module_id = ? ORDER BY display_order, id");
    $stmt->execute([$moduleId]);
    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error retrieving lessons: " . $e->getMessage();
}

// Get next display order
$nextOrder = 1;
if (!empty($lessons)) {
    $maxOrder = max(array_column($lessons, 'display_order'));
    $nextOrder = $maxOrder + 1;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Lessons - <?php echo htmlspecialchars($module['title']); ?></title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/admin.css">
    
    <style>
        .lesson-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .lesson-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .lesson-card .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e3e6f0;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .lesson-card .lesson-title {
            margin: 0;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
        }
        
        .lesson-card .lesson-number {
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
        
        .lesson-card .card-body {
            padding: 20px;
        }
        
        .lesson-card .card-text {
            color: #666;
            margin-bottom: 15px;
        }
        
        .lesson-card .card-footer {
            background-color: #f8f9fa;
            border-top: 1px solid #e3e6f0;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .lesson-card .badge {
            font-size: 85%;
        }
        
        .lesson-card .btn-group .btn {
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
        
        .lesson-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }
        
        .lesson-meta-item {
            display: flex;
            align-items: center;
            font-size: 14px;
            color: #666;
        }
        
        .lesson-meta-item i {
            margin-right: 5px;
            color: #ff5722;
        }
        
        .lesson-type-icon {
            margin-right: 10px;
            font-size: 18px;
        }
        
        .lesson-type-video { color: #e53935; }
        .lesson-type-text { color: #1e88e5; }
        .lesson-type-quiz { color: #43a047; }
        .lesson-type-assignment { color: #fb8c00; }
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
                    <h1 class="h3 mb-0 text-gray-800">Manage Lessons</h1>
                    <div class="d-flex">
                        <a href="course-modules.php?course_id=<?php echo $module['course_id']; ?>" class="d-none d-sm-inline-block btn btn-secondary shadow-sm mr-2">
                            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Modules
                        </a>
                        <a href="../course.php?id=<?php echo $module['course_id']; ?>" class="d-none d-sm-inline-block btn btn-info shadow-sm" target="_blank">
                            <i class="fas fa-eye fa-sm text-white-50"></i> Preview Course
                        </a>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    Course: <?php echo htmlspecialchars($module['course_title']); ?> | 
                                    Module: <?php echo htmlspecialchars($module['title']); ?>
                                </h6>
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
                                
                                <!-- Add Lesson Form -->
                                <div class="card mb-4">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">Add New Lesson</h6>
                                    </div>
                                    <div class="card-body">
                                        <form action="course-lessons.php?module_id=<?php echo $moduleId; ?>" method="POST">
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label for="title">Lesson Title <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="title" name="title" required>
                                                </div>
                                                <div class="form-group col-md-3">
                                                    <label for="type">Lesson Type</label>
                                                    <select class="form-control" id="type" name="type">
                                                        <option value="text">Text</option>
                                                        <option value="video">Video</option>
                                                        <option value="quiz">Quiz</option>
                                                        <option value="assignment">Assignment</option>
                                                    </select>
                                                </div>
                                                <div class="form-group col-md-3">
                                                    <label for="display_order">Display Order</label>
                                                    <input type="number" class="form-control" id="display_order" name="display_order" value="<?php echo $nextOrder; ?>" min="1">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="description">Short Description</label>
                                                <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                                            </div>
                                            <div class="form-group video-url-group">
                                                <label for="video_url">Video URL</label>
                                                <input type="text" class="form-control" id="video_url" name="video_url" placeholder="YouTube or Vimeo URL">
                                                <small class="form-text text-muted">Only required for video lessons.</small>
                                            </div>
                                            <div class="form-group">
                                                <label for="content">Lesson Content</label>
                                                <textarea class="form-control" id="content" name="content" rows="5"></textarea>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-4">
                                                    <label for="duration">Duration</label>
                                                    <input type="text" class="form-control" id="duration" name="duration" placeholder="e.g., 10 minutes">
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label for="status">Status</label>
                                                    <select class="form-control" id="status" name="status">
                                                        <option value="active">Active</option>
                                                        <option value="inactive">Inactive</option>
                                                    </select>
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <div class="custom-control custom-checkbox mt-4">
                                                        <input type="checkbox" class="custom-control-input" id="is_free" name="is_free">
                                                        <label class="custom-control-label" for="is_free">Free Preview</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="submit" name="add_lesson" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Add Lesson
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                
                                <!-- Lessons List -->
                                <div class="card">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">Module Lessons</h6>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($lessons)): ?>
                                            <div class="empty-state">
                                                <i class="fas fa-book"></i>
                                                <h4>No Lessons Found</h4>
                                                <p>Add lessons to provide content for your students.</p>
                                            </div>
                                        <?php else: ?>
                                            <form id="reorderForm" action="course-lessons.php?module_id=<?php echo $moduleId; ?>" method="POST">
                                                <div id="sortable-list">
                                                    <?php foreach ($lessons as $index => $lesson): ?>
                                                        <div class="lesson-card" data-id="<?php echo $lesson['id']; ?>">
                                                            <div class="card-header">
                                                                <h5 class="lesson-title">
                                                                    <div class="drag-handle">
                                                                        <i class="fas fa-grip-vertical"></i>
                                                                    </div>
                                                                    <div class="lesson-number"><?php echo $lesson['display_order']; ?></div>
                                                                    <div class="lesson-type-icon lesson-type-<?php echo $lesson['type']; ?>">
                                                                        <?php if ($lesson['type'] === 'video'): ?>
                                                                            <i class="fas fa-play-circle"></i>
                                                                        <?php elseif ($lesson['type'] === 'quiz'): ?>
                                                                            <i class="fas fa-question-circle"></i>
                                                                        <?php elseif ($lesson['type'] === 'assignment'): ?>
                                                                            <i class="fas fa-tasks"></i>
                                                                        <?php else: ?>
                                                                            <i class="fas fa-file-alt"></i>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <?php echo htmlspecialchars($lesson['title']); ?>
                                                                    <?php if ($lesson['is_free']): ?>
                                                                        <span class="badge badge-success ml-2">Free</span>
                                                                    <?php endif; ?>
                                                                    <?php if ($lesson['status'] === 'inactive'): ?>
                                                                        <span class="badge badge-secondary ml-2">Inactive</span>
                                                                    <?php endif; ?>
                                                                </h5>
                                                                <div class="btn-group">
                                                                    <button type="button" class="btn btn-sm btn-primary edit-lesson" 
                                                                            data-id="<?php echo $lesson['id']; ?>"
                                                                            data-title="<?php echo htmlspecialchars($lesson['title']); ?>"
                                                                            data-description="<?php echo htmlspecialchars($lesson['description']); ?>"
                                                                            data-content="<?php echo htmlspecialchars($lesson['content']); ?>"
                                                                            data-type="<?php echo $lesson['type']; ?>"
                                                                            data-video-url="<?php echo htmlspecialchars($lesson['video_url']); ?>"
                                                                            data-duration="<?php echo htmlspecialchars($lesson['duration']); ?>"
                                                                            data-is-free="<?php echo $lesson['is_free']; ?>"
                                                                            data-order="<?php echo $lesson['display_order']; ?>"
                                                                            data-status="<?php echo $lesson['status']; ?>">
                                                                        <i class="fas fa-edit"></i> Edit
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-danger delete-lesson" 
                                                                            data-id="<?php echo $lesson['id']; ?>"
                                                                            data-title="<?php echo htmlspecialchars($lesson['title']); ?>">
                                                                        <i class="fas fa-trash"></i> Delete
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <div class="card-body">
                                                                <?php if (!empty($lesson['description'])): ?>
                                                                    <p class="card-text"><?php echo nl2br(htmlspecialchars($lesson['description'])); ?></p>
                                                                <?php endif; ?>
                                                                
                                                                <div class="lesson-meta">
                                                                    <div class="lesson-meta-item">
                                                                        <i class="fas fa-layer-group"></i> Type: <?php echo ucfirst($lesson['type']); ?>
                                                                    </div>
                                                                    <?php if (!empty($lesson['duration'])): ?>
                                                                        <div class="lesson-meta-item">
                                                                            <i class="far fa-clock"></i> Duration: <?php echo htmlspecialchars($lesson['duration']); ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <?php if ($lesson['type'] === 'video' && !empty($lesson['video_url'])): ?>
                                                                        <div class="lesson-meta-item">
                                                                            <i class="fas fa-link"></i> Video URL: 
                                                                            <a href="<?php echo htmlspecialchars($lesson['video_url']); ?>" target="_blank">
                                                                                <?php echo htmlspecialchars(substr($lesson['video_url'], 0, 30) . (strlen($lesson['video_url']) > 30 ? '...' : '')); ?>
                                                                            </a>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <input type="hidden" name="lesson_ids[]" value="<?php echo $lesson['id']; ?>">
                                                            <input type="hidden" name="orders[]" value="<?php echo $lesson['display_order']; ?>" class="order-input">
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <div class="mt-3">
                                                    <button type="submit" name="reorder_lessons" class="btn btn-success">
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
    
    <!-- Edit

```typescriptreact file="admin/course-categories.php"
[v0-no-op-code-block-prefix]<?php
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
    // Check if parent_id column exists in the categories table
    $checkColumnQuery = "SHOW COLUMNS FROM course_categories LIKE 'parent_id'";
    $columnResult = $conn->query($checkColumnQuery);
    $hasParentId = $columnResult->num_rows > 0;

    // Adjust the query based on whether parent_id exists
    if ($hasParentId) {
        $sql = "SELECT c.*, COUNT(co.id) as course_count, p.name as parent_name 
                FROM course_categories c 
                LEFT JOIN courses co ON c.id = co.category_id 
                LEFT JOIN course_categories p ON c.parent_id = p.id 
                GROUP BY c.id 
                ORDER BY c.name";
    } else {
        $sql = "SELECT c.*, COUNT(co.id) as course_count 
                FROM course_categories c 
                LEFT JOIN courses co ON c.id = co.category_id 
                GROUP BY c.id 
                ORDER BY c.name";
        
        // Add parent_id column if it doesn't exist
        $alterTableQuery = "ALTER TABLE course_categories ADD COLUMN parent_id INT NULL DEFAULT NULL";
        $conn->query($alterTableQuery);
        echo "<div class='alert alert-info'>Parent category feature has been added. Please refresh the page.</div>";
    }

    $result = $conn->query($sql);
    $categories = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error retrieving categories: " . $e->getMessage();
}

// Get parent categories for dropdown
$parentCategories = [];
try {
    if ($hasParentId) {
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
                                        <h5 class="card-title">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                            <?php if ($category['status'] === 'active'): ?>
                                                <span class="badge badge-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </h5>
                                        <?php if (!empty($category['parent_name'])): ?>
                                            <div class="parent-category">
                                                <i class="fas fa-level-up-alt fa-rotate-90 mr-1"></i> Parent: <?php echo htmlspecialchars($category['parent_name']); ?>
                                            </div>
                                        <?php endif; ?>
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
                        <div class="form-group">
                            <label for="parent_id">Parent Category</label>
                            <select class="form-control" id="parent_id" name="parent_id">
                                <option value="">None (Top Level Category)</option>
                                <?php foreach ($parentCategories as $parent): ?>
                                    <option value="<?php echo $parent['id']; ?>"><?php echo htmlspecialchars($parent['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
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
                        <div class="form-group">
                            <label for="edit_parent_id">Parent Category</label>
                            <select class="form-control" id="edit_parent_id" name="parent_id">
                                <option value="">None (Top Level Category)</option>
                                <?php foreach ($parentCategories as $parent): ?>
                                    <option value="<?php echo $parent['id']; ?>"><?php echo htmlspecialchars($parent['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_status">Status</label>
                            <select class="form-control" id="edit_status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
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
