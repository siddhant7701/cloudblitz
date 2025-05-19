<?php
// Include header
include('includes/navbar.php');
include('includes/sidebar.php');
include('includes/db_connect.php');

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: instructors.php");
    exit;
}

$instructor_id = (int)$_GET['id'];

// Get instructor data
try {
    $stmt = $conn->prepare("
        SELECT i.*, COUNT(c.id) as course_count 
        FROM instructors i 
        LEFT JOIN courses c ON i.id = c.instructor_id 
        WHERE i.id = ? 
        GROUP BY i.id
    ");
    $stmt->execute([$instructor_id]);
    $instructor = $stmt->fetch();

    if (!$instructor) {
        header("Location: instructors.php");
        exit;
    }

} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// Get instructor's courses
try {
    $stmt = $conn->prepare("
        SELECT c.*, COUNT(e.id) as enrollment_count 
        FROM courses c 
        LEFT JOIN enrollments e ON c.id = e.course_id 
        WHERE c.instructor_id = ? 
        GROUP BY c.id 
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$instructor_id]);
    $courses = $stmt->fetchAll();
} catch (PDOException $e) {
    $courses = [];
}
?>

<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Instructor Profile</h1>
                <div>
                    <a href="edit-instructors.php?id=<?php echo $instructor_id; ?>" class="btn btn-warning shadow-sm mr-2">
                        <i class="fas fa-edit fa-sm text-white-50"></i> Edit Instructor
                    </a>
                    <a href="instructors.php" class="btn btn-secondary shadow-sm">
                        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Instructors
                    </a>
                </div>
            </div>

            <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="instructors.php">Instructors</a></li>
                <li class="breadcrumb-item active">View Instructor</li>
            </ol>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-12">
                    <!-- Profile Header -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <div class="row profile-header align-items-center">
                                <div class="col-md-3 text-center">
                                    <?php if (!empty($instructor['image_path'])): ?>
                                        <img src="../images/instructors/<?php echo htmlspecialchars($instructor['image_path']); ?>" alt="<?php echo htmlspecialchars($instructor['name']); ?>" class="profile-image">
                                    <?php else: ?>
                                        <div class="profile-image bg-secondary d-flex align-items-center justify-content-center text-white">
                                            <i class="fas fa-user fa-5x"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-9 profile-info">
                                    <h2><?php echo htmlspecialchars($instructor['name']); ?></h2>
                                    <p class="text-muted"><?php echo htmlspecialchars($instructor['title']); ?></p>
                                    
                                    <div class="mb-3">
                                        <strong>Email:</strong> <?php echo htmlspecialchars($instructor['email']); ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Status:</strong> 
                                        <?php if (isset($instructor['status']) && $instructor['status'] == 'active'): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Rating:</strong> 
                                        <span class="text-warning">
                                            <?php 
                                            $rating = isset($instructor['rating']) ? $instructor['rating'] : 0;
                                            echo number_format($rating, 1);
                                            ?>
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php if ($i <= $rating): ?>
                                                    <i class="fas fa-star"></i>
                                                <?php elseif ($i <= $rating + 0.5): ?>
                                                    <i class="fas fa-star-half-alt"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star"></i>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Courses:</strong> <?php echo $instructor['course_count']; ?>
                                    </div>
                                    
                                    <?php if (!empty($instructor['social_links'])): ?>
                                        <div class="social-links mb-3">
                                            <?php 
                                            $links = explode("\n", $instructor['social_links']);
                                            foreach ($links as $link):
                                                $link = trim($link);
                                                if (empty($link)) continue;
                                                
                                                $icon = 'fas fa-globe'; // Default icon
                                                
                                                if (strpos($link, 'linkedin.com') !== false) {
                                                    $icon = 'fab fa-linkedin';
                                                } elseif (strpos($link, 'twitter.com') !== false || strpos($link, 'x.com') !== false) {
                                                    $icon = 'fab fa-twitter';
                                                } elseif (strpos($link, 'facebook.com') !== false) {
                                                    $icon = 'fab fa-facebook';
                                                } elseif (strpos($link, 'instagram.com') !== false) {
                                                    $icon = 'fab fa-instagram';
                                                } elseif (strpos($link, 'github.com') !== false) {
                                                    $icon = 'fab fa-github';
                                                } elseif (strpos($link, 'youtube.com') !== false) {
                                                    $icon = 'fab fa-youtube';
                                                }
                                            ?>
                                                <a href="<?php echo htmlspecialchars($link); ?>" target="_blank" title="<?php echo htmlspecialchars($link); ?>">
                                                    <i class="<?php echo $icon; ?>"></i>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Expertise -->
                    <?php if (!empty($instructor['expertise'])): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Areas of Expertise</h6>
                            </div>
                            <div class="card-body">
                                <?php 
                                $expertise_areas = explode(',', $instructor['expertise']);
                                foreach ($expertise_areas as $area):
                                    $area = trim($area);
                                    if (empty($area)) continue;
                                ?>
                                    <span class="expertise-tag"><?php echo htmlspecialchars($area); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Biography -->
                    <?php if (!empty($instructor['bio'])): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Biography</h6>
                            </div>
                            <div class="card-body">
                                <p><?php echo nl2br(htmlspecialchars($instructor['bio'])); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Courses -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Courses (<?php echo count($courses); ?>)</h6>
                            <a href="add-course.php?instructor_id=<?php echo $instructor_id; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Add Course
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($courses)): ?>
                                <p class="text-center">No courses found for this instructor.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Category</th>
                                                <th>Price</th>
                                                <th>Status</th>
                                                <th>Enrollments</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($courses as $course): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if (!empty($course['image_path'])): ?>
                                                                <img src="../images/courses/<?php echo htmlspecialchars($course['image_path']); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>" class="mr-2" width="50" height="30" style="object-fit: cover;">
                                                            <?php endif; ?>
                                                            <?php echo htmlspecialchars($course['title']); ?>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($course['category_id'] ?? 'Uncategorized'); ?></td>
                                                    <td>
                                                        <?php if (isset($course['price'])): ?>
                                                            <?php if ($course['price'] == 0): ?>
                                                                <span class="badge badge-success">Free</span>
                                                            <?php else: ?>
                                                                $<?php echo number_format($course['price'], 2); ?>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="badge badge-secondary">Not set</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (isset($course['status'])): ?>
                                                            <?php if ($course['status'] == 'published'): ?>
                                                                <span class="badge badge-success">Published</span>
                                                            <?php elseif ($course['status'] == 'draft'): ?>
                                                                <span class="badge badge-warning">Draft</span>
                                                            <?php else: ?>
                                                                <span class="badge badge-info"><?php echo ucfirst($course['status']); ?></span>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="badge badge-secondary">Unknown</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo $course['enrollment_count'] ?? 0; ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="view-course.php?id=<?php echo $course['id']; ?>" class="btn btn-info btn-sm" title="View">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="edit-course.php?id=<?php echo $course['id']; ?>" class="btn btn-warning btn-sm" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
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
            $('#sidebar').toggleClass('active');
        });
    });
</script>

<style>
    .profile-image {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 50%;
        border: 5px solid #fff;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    .profile-header {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    .profile-info {
        padding-left: 20px;
    }
    .social-links a {
        display: inline-block;
        margin-right: 10px;
        font-size: 20px;
        color: #6c757d;
    }
    .social-links a:hover {
        color: #007bff;
    }
    .expertise-tag {
        display: inline-block;
        background-color: #e9ecef;
        padding: 5px 10px;
        border-radius: 20px;
        margin-right: 5px;
        margin-bottom: 5px;
        font-size: 0.85rem;
    }
</style>
