<?php
// Include database connection
include '../includes/db.php';
include '../includes/auth_check.php';

// Check if user is admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$tables = [];
$errors = [];

// Check if course_modules table exists
$checkModulesTable = $conn->query("SHOW TABLES LIKE 'course_modules'");
if ($checkModulesTable->num_rows == 0) {
    // Create course_modules table
    $createModulesTable = "CREATE TABLE course_modules (
        id INT(11) NOT NULL AUTO_INCREMENT,
        course_id INT(11) NOT NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        sort_order INT(11) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY course_id (course_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($createModulesTable) === TRUE) {
        $tables[] = "course_modules table created successfully";
    } else {
        $errors[] = "Error creating course_modules table: " . $conn->error;
    }
}

// Check if course_lessons table exists
$checkLessonsTable = $conn->query("SHOW TABLES LIKE 'course_lessons'");
if ($checkLessonsTable->num_rows == 0) {
    // Create course_lessons table
    $createLessonsTable = "CREATE TABLE course_lessons (
        id INT(11) NOT NULL AUTO_INCREMENT,
        module_id INT(11) NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        duration VARCHAR(50),
        content LONGTEXT,
        sort_order INT(11) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY module_id (module_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($createLessonsTable) === TRUE) {
        $tables[] = "course_lessons table created successfully";
    } else {
        $errors[] = "Error creating course_lessons table: " . $conn->error;
    }
}

// Check if courses table has the necessary fields
$checkCoursesFields = $conn->query("SHOW COLUMNS FROM courses LIKE 'description'");
if ($checkCoursesFields->num_rows == 0) {
    // Add missing fields to courses table
    $alterCoursesTable = "ALTER TABLE courses 
        ADD COLUMN description TEXT AFTER title,
        ADD COLUMN curriculum TEXT AFTER description,
        ADD COLUMN job_support TEXT AFTER curriculum,
        ADD COLUMN certification TEXT AFTER job_support,
        ADD COLUMN requirements TEXT AFTER certification,
        ADD COLUMN outcomes TEXT AFTER requirements";
    
    if ($conn->query($alterCoursesTable) === TRUE) {
        $tables[] = "Added missing fields to courses table";
    } else {
        $errors[] = "Error adding fields to courses table: " . $conn->error;
    }
}

// Check if course_categories table has parent_id column
$checkParentIdColumn = $conn->query("SHOW COLUMNS FROM course_categories LIKE 'parent_id'");
if ($checkParentIdColumn->num_rows == 0) {
    // Add parent_id column to course_categories table
    $alterCategoriesTable = "ALTER TABLE course_categories ADD COLUMN parent_id INT(11) NULL DEFAULT NULL AFTER name";
    
    if ($conn->query($alterCategoriesTable) === TRUE) {
        $tables[] = "Added parent_id column to course_categories table";
    } else {
        $errors[] = "Error adding parent_id column to course_categories table: " . $conn->error;
    }
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Database Setup</h1>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5>Database Tables Setup</h5>
                </div>
                <div class="card-body">
                    <?php if (count($errors) > 0): ?>
                        <div class="alert alert-danger">
                            <h5>Errors:</h5>
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (count($tables) > 0): ?>
                        <div class="alert alert-success">
                            <h5>Success:</h5>
                            <ul>
                                <?php foreach ($tables as $table): ?>
                                    <li><?php echo $table; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (count($errors) === 0 && count($tables) === 0): ?>
                        <div class="alert alert-info">
                            <p>All required tables and columns already exist.</p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <a href="course-modules.php" class="btn btn-primary">Go to Course Modules</a>
                        <a href="course-lessons.php?module_id=1" class="btn btn-secondary ms-2">Go to Course Lessons</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
