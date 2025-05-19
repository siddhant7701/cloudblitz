<?php
// Start the session
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Include database connection
include('../includes/db_connect.php');

// Initialize variables
$name = '';
$email = '';
$title = '';
$bio = '';
$errors = [];
$success_message = '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $title = trim($_POST['title']);
    $bio = trim($_POST['bio']);
    
    // Validate form data
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM instructors WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Email already exists";
        }
    }
    
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    // Process image upload if there are no errors
    $image_path = '';
    if (empty($errors) && isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $errors[] = "Only JPG, PNG and GIF images are allowed";
        } elseif ($_FILES['image']['size'] > $max_size) {
            $errors[] = "Image size should be less than 2MB";
        } else {
            // Create upload directory if it doesn't exist
            $upload_dir = "../uploads/instructors/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $file_extension;
            $target_file = $upload_dir . $filename;
            
            // Upload file
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path = $filename;
            } else {
                $errors[] = "Failed to upload image";
            }
        }
    }
    
    // Insert data into database if there are no errors
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO instructors (name, email, title, bio, image_path) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([$name, $email, $title, $bio, $image_path]);
            
            if ($result) {
                $success_message = "Instructor added successfully!";
                // Clear form data
                $name = '';
                $email = '';
                $title = '';
                $bio = '';
            } else {
                $errors[] = "Failed to add instructor";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Instructor - CloudBlitz Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary: #ff7700;
            --primary-light: #ff9933;
            --primary-dark: #e66000;
            --secondary: #333333;
            --secondary-light: #555555;
            --secondary-dark: #222222;
            --white: #ffffff;
            --light-gray: #f5f5f5;
            --gray: #e0e0e0;
            --dark-gray: #888888;
            --danger: #dc3545;
            --success: #28a745;
            --warning: #ffc107;
            --info: #17a2b8;
        }

        /* Base Styles */
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fc;
            overflow-x: hidden;
        }

        .wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
        }

        /* Sidebar */
        #sidebar {
            min-width: 250px;
            max-width: 250px;
            background: var(--secondary-dark);
            color: #fff;
            transition: all 0.3s;
            height: 100vh;
            position: fixed;
            z-index: 999;
        }

        #sidebar.active {
            margin-left: -250px;
        }

        #sidebar .sidebar-header {
            padding: 20px;
            background: #1a1a1a;
        }

        #sidebar .sidebar-header h3 {
            color: #fff;
            margin: 0;
            font-weight: 700;
        }

        #sidebar .sidebar-header h3 span {
            color: var(--primary);
        }

        #sidebar ul.components {
            padding: 20px 0;
            border-bottom: 1px solid #444;
        }

        #sidebar ul li a {
            padding: 12px 20px;
            font-size: 1.1em;
            display: block;
            color: #fff;
            text-decoration: none;
            transition: all 0.3s;
            display: flex;
            align-items: center;
        }

        #sidebar ul li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        #sidebar ul li a:hover {
            color: var(--primary);
            background: #2c2c2c;
        }

        #sidebar ul li.active > a {
            color: #fff;
            background: var(--primary);
        }

        /* Content */
        #content {
            width: calc(100% - 250px);
            min-height: 100vh;
            transition: all 0.3s;
            position: absolute;
            top: 0;
            right: 0;
        }

        #content.active {
            width: 100%;
        }

        /* Navbar */
        .navbar {
            padding: 15px 10px;
            background: #fff;
            border: none;
            border-radius: 0;
            margin-bottom: 30px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        /* Cards */
        .card {
            margin-bottom: 24px;
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }

        .card-header h6 {
            font-weight: 700;
            color: var(--primary);
        }

        /* Buttons */
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        /* Form Styles */
        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 0.2rem rgba(255, 119, 0, 0.25);
        }

        /* Responsive */
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -250px;
            }
            #sidebar.active {
                margin-left: 0;
            }
            #content {
                width: 100%;
            }
            #content.active {
                width: calc(100% - 250px);
            }
            #sidebarCollapse span {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <?php include('includes/sidebar.php'); ?>

        <!-- Page Content -->
        <div id="content">
            <!-- Navbar -->
            <?php include('includes/navbar.php'); ?>

            <!-- Main Content -->
            <div class="container-fluid px-4">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Add New Instructor</h1>
                    <a href="instructors.php" class="d-none d-sm-inline-block btn btn-secondary shadow-sm">
                        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Instructors
                    </a>
                </div>

                <ol class="breadcrumb mb-4">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="instructors.php">Instructors</a></li>
                    <li class="breadcrumb-item active">Add New</li>
                </ol>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Error!</strong>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Instructor Information</h6>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="title">Title/Position <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                                <small class="form-text text-muted">E.g., "Frontend Developer", "Data Science Expert", "Marketing Specialist"</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="bio">Bio/Description</label>
                                <textarea class="form-control" id="bio" name="bio" rows="5"><?php echo htmlspecialchars($bio); ?></textarea>
                                <small class="form-text text-muted">Provide a brief description of the instructor's background, expertise, and experience.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="image">Profile Image</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="image" name="image">
                                    <label class="custom-file-label" for="image">Choose file</label>
                                </div>
                                <small class="form-text text-muted">Recommended image size: 300x300 pixels. Max file size: 2MB. Formats: JPG, PNG, GIF.</small>
                            </div>
                            
                            <hr>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i> Save Instructor
                                </button>
                                <a href="instructors.php" class="btn btn-secondary ml-2">
                                    <i class="fas fa-times mr-1"></i> Cancel
                                </a>
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
    
    <script>
        $(document).ready(function() {
            // Toggle sidebar
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar, #content').toggleClass('active');
            });
            
            // Show filename when file is selected
            $(".custom-file-input").on("change", function() {
                var fileName = $(this).val().split("\\").pop();
                $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
            });
        });
    </script>
</body>
</html>