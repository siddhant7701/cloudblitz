<?php
// Include database connection
require_once 'includes/db_connect.php';

// Get course ID from URL
$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get course details
$stmt = $conn->prepare("SELECT c.*, cat.name as category_name 
                       FROM courses c 
                       LEFT JOIN course_categories cat ON c.category_id = cat.id 
                       WHERE c.id = :id");
$stmt->bindParam(':id', $courseId, PDO::PARAM_INT);
$stmt->execute();
$course = $stmt->fetch(PDO::FETCH_ASSOC);

// If course doesn't exist, redirect to courses page
if (!$course) {
   header('Location: courses.php');
   exit;
}

// Set page specific CSS
$page_specific_css = 'css/course-single.css';
$page_title = $course['title'];

// Include header
require_once 'includes/header.php';

// Get course instructor
$instructor = null;
if (!empty($course['instructor_id'])) {
    $stmt = $conn->prepare("SELECT * FROM instructors WHERE id = :instructor_id");
    $stmt->bindParam(':instructor_id', $course['instructor_id'], PDO::PARAM_INT);
    $stmt->execute();
    $instructor = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get related courses (other courses in the same category)
$relatedCourses = [];
if (!empty($course['category_id'])) {
    $stmt = $conn->prepare("SELECT * FROM courses 
                           WHERE category_id = :category_id 
                           AND id != :id 
                           ORDER BY created_at DESC LIMIT 4");
    $stmt->bindParam(':category_id', $course['category_id'], PDO::PARAM_INT);
    $stmt->bindParam(':id', $courseId, PDO::PARAM_INT);
    $stmt->execute();
    $relatedCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get course modules if they exist
$modules = [];
try {
    $stmt = $conn->prepare("SELECT * FROM course_modules WHERE course_id = :course_id ORDER BY display_order");
    $stmt->bindParam(':course_id', $courseId, PDO::PARAM_INT);
    $stmt->execute();
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table might not exist, continue without modules
}

// Get course categories
$categories = [];
try {
    $stmt = $conn->prepare("SELECT * FROM course_categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table might not exist, continue without categories
}

// Get testimonials
$testimonials = [];
try {
    $stmt = $conn->prepare("SELECT * FROM testimonials WHERE status = 'active' ORDER BY RAND() LIMIT 3");
    $stmt->execute();
    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table might not exist, continue without testimonials
}

// Check if user is admin
$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Get ratings and reviews
$avgRating = 0;
$ratingCount = 0;
$reviewCount = 0;

try {
    $stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM course_ratings WHERE course_id = :course_id");
    $stmt->bindParam(':course_id', $courseId, PDO::PARAM_INT);
    $stmt->execute();
    $ratingData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($ratingData && $ratingData['count'] > 0) {
        $avgRating = round($ratingData['avg_rating'], 1);
        $ratingCount = $ratingData['count'];
    }
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM course_reviews WHERE course_id = :course_id AND status = 'approved'");
    $stmt->bindParam(':course_id', $courseId, PDO::PARAM_INT);
    $stmt->execute();
    $reviewData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($reviewData) {
        $reviewCount = $reviewData['count'];
    }
} catch (PDOException $e) {
    // Table might not exist, continue with default values
}

// Get upcoming batches
$upcomingBatches = [];
if (empty($course['upcoming_batches'])) {
    try {
        $stmt = $conn->prepare("SELECT batch_date, batch_time, batch_type FROM course_batches 
                               WHERE course_id = :course_id AND batch_date >= CURDATE() 
                               ORDER BY batch_date LIMIT 3");
        $stmt->bindParam(':course_id', $courseId, PDO::PARAM_INT);
        $stmt->execute();
        $upcomingBatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Table might not exist, continue without batches
    }
}

// Get learning outcomes
$learningOutcomes = [];
if (empty($course['outcomes'])) {
    try {
        $stmt = $conn->prepare("SELECT title, description FROM course_learning_outcomes 
                               WHERE course_id = :course_id ORDER BY display_order");
        $stmt->bindParam(':course_id', $courseId, PDO::PARAM_INT);
        $stmt->execute();
        $learningOutcomes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Table might not exist, continue without learning outcomes
    }
}

// Get curriculum items
$curriculumItems = [];
if (count($modules) === 0) {
    try {
        $stmt = $conn->prepare("SELECT title, description FROM course_curriculum_items 
                               WHERE course_id = :course_id ORDER BY display_order");
        $stmt->bindParam(':course_id', $courseId, PDO::PARAM_INT);
        $stmt->execute();
        $curriculumItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Table might not exist, continue without curriculum items
    }
}
?>

<!-- Course Styles -->
<link rel="stylesheet" href="css/course-styles.css">

<!-- Admin Controls - Only visible to admins -->
<?php if ($isAdmin): ?>
<div class="admin-controls">
    <div class="container">
        <div class="admin-panel">
            <h4>Admin Controls</h4>
            <div class="admin-buttons">
                <a href="admin/edit-course.php?id=<?php echo $courseId; ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit Course
                </a>
                <a href="admin/course-modules.php?course_id=<?php echo $courseId; ?>" class="btn btn-info">
                    <i class="fas fa-list"></i> Manage Modules
                </a>
                
                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteCourseModal">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Course Modal -->
<div class="modal fade" id="deleteCourseModal" tabindex="-1" role="dialog" aria-labelledby="deleteCourseModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCourseModalLabel">Confirm Deletion</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete the course "<?php echo htmlspecialchars($course['title']); ?>"?
                <p class="text-danger mt-3"><strong>Warning:</strong> This action cannot be undone. All course data, including modules and lessons, will be permanently deleted.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form action="admin/delete-course.php" method="POST">
                    <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">
                    <button type="submit" class="btn btn-danger">Delete Course</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Course Hero Section -->
<section class="course-hero" style="background-image: url('<?php echo !empty($course['banner_image']) ? htmlspecialchars($course['banner_image']) : 'images/default-banner.jpg'; ?>');">
  <div class="container">
    <div class="row">
      <div class="col-md-12">
        <h1><?php echo htmlspecialchars($course['title']); ?></h1>
        <p class="course-subtitle">
          <?php if (!empty($course['category_name'])): ?>
            EXPERT IN <?php echo htmlspecialchars(strtoupper($course['category_name'])); ?>
            <?php if (stripos($course['title'], 'AI') !== false || stripos($course['description'], 'AI') !== false): ?>
              WITH AI
            <?php endif; ?>
          <?php else: ?>
            PROFESSIONAL TRAINING PROGRAM
          <?php endif; ?>
        </p>
      </div>
    </div>
  </div>
</section>

<!-- Course Details Section -->
<div class="container mt-4">
  <div class="row">
    <!-- Main Content -->
    <div class="col-lg-8">
      <!-- Course Tabs -->
      <div class="course-tabs">
        <ul class="nav nav-tabs" id="courseTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab" aria-controls="overview" aria-selected="true">Course Overview</button>
            </li>
            <!-- <li class="nav-item" role="presentation">
                <button class="nav-link" id="curriculum-tab" data-bs-toggle="tab" data-bs-target="#curriculum" type="button" role="tab" aria-controls="curriculum" aria-selected="false">Practical Training</button>
            </li> -->
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="job-support-tab" data-bs-toggle="tab" data-bs-target="#job-support" type="button" role="tab" aria-controls="job-support" aria-selected="false">Job Support</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="certification-tab" data-bs-toggle="tab" data-bs-target="#certification" type="button" role="tab" aria-controls="certification" aria-selected="false">Certification</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="requirements-tab" data-bs-toggle="tab" data-bs-target="#requirements" type="button" role="tab" aria-controls="requirements" aria-selected="false">Requirements</button>
            </li>
        </ul>
        <div class="tab-content" id="courseTabsContent">
            <!-- Overview Tab -->
            <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                <!-- Ratings Section -->
                <div class="ratings-container">
                    <div class="rating-box">
                        <div class="rating-number"><?php echo $avgRating; ?></div>
                        <div class="rating-stars">
                            <?php
                            $fullStars = floor($avgRating);
                            $halfStar = $avgRating - $fullStars >= 0.5;
                            
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $fullStars) {
                                    echo '<i class="fas fa-star"></i>';
                                } elseif ($i == $fullStars + 1 && $halfStar) {
                                    echo '<i class="fas fa-star-half-alt"></i>';
                                } else {
                                    echo '<i class="far fa-star"></i>';
                                }
                            }
                            ?>
                        </div>
                        <div class="rating-label"><?php echo $ratingCount; ?> Ratings</div>
                    </div>
                    <div class="rating-box">
                        <div class="rating-number"><?php echo $reviewCount; ?></div>
                        <div class="rating-stars">
                            <i class="fas fa-comment"></i>
                        </div>
                        <div class="rating-label">Reviews</div>
                    </div>
                </div>
                
                <!-- Course Info Boxes -->
                <div class="course-info-boxes">
                    <div class="info-box">
                        <h3>COURSE DURATION</h3>
                        <ul>
                            <?php if (!empty($course['course_duration_info'])): ?>
                                <?php 
                                $duration_items = explode("\n", $course['course_duration_info']);
                                foreach ($duration_items as $item): 
                                    if (trim($item)): 
                                ?>
                                    <li><?php echo htmlspecialchars(trim($item)); ?></li>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            <?php elseif (!empty($course['duration'])): ?>
                                <li><?php echo htmlspecialchars($course['duration']); ?></li>
                                <li>Weekend Batches</li>
                                <li>Weekday Batches</li>
                            <?php else: ?>
                                <li>Duration not specified</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="info-box">
                        <h3>LEARNING MODE</h3>
                        <ul>
                            <?php if (!empty($course['learning_mode_info'])): ?>
                                <?php 
                                $learning_mode_items = explode("\n", $course['learning_mode_info']);
                                foreach ($learning_mode_items as $item): 
                                    if (trim($item)): 
                                ?>
                                    <li><?php echo htmlspecialchars(trim($item)); ?></li>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            <?php else: ?>
                                <li>Online</li>
                                <li>Classroom</li>
                                <li>Self-Paced</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="info-box">
                        <h3>UPCOMING BATCHES</h3>
                        <ul>
                            <?php 
                            if (!empty($course['upcoming_batches'])) {
                                $batch_items = explode("\n", $course['upcoming_batches']);
                                foreach ($batch_items as $item) {
                                    if (trim($item)) {
                                        echo '<li>' . htmlspecialchars(trim($item)) . '</li>';
                                    }
                                }
                            } elseif (count($upcomingBatches) > 0) {
                                foreach ($upcomingBatches as $batch) {
                                    $batchInfo = date('F j, Y', strtotime($batch['batch_date']));
                                    if (!empty($batch['batch_time'])) {
                                        $batchInfo .= ' at ' . $batch['batch_time'];
                                    }
                                    if (!empty($batch['batch_type'])) {
                                        $batchInfo .= ' (' . $batch['batch_type'] . ')';
                                    }
                                    echo '<li>' . htmlspecialchars($batchInfo) . '</li>';
                                }
                            } else {
                                echo '<li>No upcoming batches scheduled</li>';
                            }
                            ?>
                        </ul>
                    </div>
                </div>
                
                <h3>Course Description</h3>
                <?php if (!empty($course['description'])): ?>
                    <div class="course-description">
                        <?php echo $course['description']; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-message">No description available.</p>
                <?php endif; ?>
            </div>

            <!-- Curriculum Tab -->
            <div class="tab-pane fade" id="curriculum" role="tabpanel" aria-labelledby="curriculum-tab">
                <h3>Curriculum Content</h3>
                <?php if (!empty($course['curriculum'])): ?>
                    <div class="course-curriculum">
                        <?php echo $course['curriculum']; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-message">No curriculum content available.</p>
                <?php endif; ?>
                
                <!-- Display Modules and Lessons -->
                <?php if (count($modules) > 0): ?>
                    <div class="course-modules-list mt-4">
                        <h4>Course Modules</h4>
                        <div class="accordion" id="moduleAccordion">
                            <?php 
                            $moduleCount = 1;
                            foreach ($modules as $module): 
                                // Fetch lessons for this module
                                $lessons = [];
                                try {
                                    $stmt = $conn->prepare("SELECT * FROM course_lessons WHERE module_id = :module_id ORDER BY display_order");
                                    $stmt->bindParam(':module_id', $module['id'], PDO::PARAM_INT);
                                    $stmt->execute();
                                    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                } catch (PDOException $e) {
                                    // Table might not exist or other error
                                }
                            ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading<?php echo $module['id']; ?>">
                                        <button class="accordion-button <?php echo ($moduleCount > 1) ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $module['id']; ?>" aria-expanded="<?php echo ($moduleCount == 1) ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $module['id']; ?>">
                                            Module <?php echo $moduleCount; ?>: <?php echo htmlspecialchars($module['title']); ?>
                                        </button>
                                    </h2>
                                    <div id="collapse<?php echo $module['id']; ?>" class="accordion-collapse collapse <?php echo ($moduleCount == 1) ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $module['id']; ?>" data-bs-parent="#moduleAccordion">
                                        <div class="accordion-body">
                                            <?php if (!empty($module['description'])): ?>
                                                <p><?php echo htmlspecialchars($module['description']); ?></p>
                                            <?php endif; ?>
                                            
                                            <?php if (count($lessons) > 0): ?>
                                                <ul class="lessons-list">
                                                    <?php 
                                                    $lessonCount = 1;
                                                    foreach ($lessons as $lesson): 
                                                    ?>
                                                        <li>
                                                            <div class="lesson-item">
                                                                <span class="lesson-number"><?php echo $lessonCount; ?>.</span>
                                                                <span class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></span>
                                                                <?php if (!empty($lesson['duration'])): ?>
                                                                    <span class="lesson-duration"><?php echo htmlspecialchars($lesson['duration']); ?></span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </li>
                                                    <?php 
                                                    $lessonCount++;
                                                    endforeach; 
                                                    ?>
                                                </ul>
                                            <?php else: ?>
                                                <p class="empty-message">No lessons available for this module.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php 
                            $moduleCount++;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                <?php else: ?>
                    <?php if (empty($course['curriculum'])): ?>
                        <p class="empty-message">No curriculum available.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Job Support Tab -->
            <div class="tab-pane fade" id="job-support" role="tabpanel" aria-labelledby="job-support-tab">
    <h3>Job Support Details</h3>
    <?php if (!empty($course['job_support'])): ?>
        <div class="job-support-details">
            <?php echo nl2br(htmlspecialchars($course['job_support'])); ?>
        </div>
    <?php else: ?>
        <p class="empty-message">No job support details available.</p>
    <?php endif; ?>
</div>


            <!-- Certification Tab -->
            <div class="tab-pane fade" id="certification" role="tabpanel" aria-labelledby="certification-tab">
    <h3>Certification Details</h3>
    <?php if (!empty($course['certification'])): ?>
        <div class="certification-details">
            <?php echo nl2br(htmlspecialchars($course['certification'])); ?>
        </div>
    <?php else: ?>
        <p class="empty-message">No certification details available.</p>
    <?php endif; ?>
</div>


            <!-- Requirements & Outcomes Tab -->
            <div class="tab-pane fade" id="requirements" role="tabpanel" aria-labelledby="requirements-tab">
                <div class="row">
                    <div class="col-md-6">
                        <h3>Requirements</h3>
                        <?php if (!empty($course['requirements'])): ?>
                <div class="course-requirements">
                    <ul class="list-unstyled">
                        <?php 
                        $lines = explode("\n", $course['requirements']);
                        foreach ($lines as $line): 
                            if (trim($line) !== ''):
                        ?>
                            <li>
                                <i class="fas fa-check-circle" style="color: orange; margin-right: 5px;"></i>
                                <?php echo htmlspecialchars($line); ?>
                            </li>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </ul>
                            </div>
                        <?php else: ?>
                            <p class="empty-message">No requirements specified.</p>
                        <?php endif; ?>
                    </div>
                    <!-- <div class="col-md-6">
                        <h3>What You'll Learn</h3>
                        <?php if (!empty($course['outcomes'])): ?>
                            <div class="course-outcomes">
                                <?php echo $course['outcomes']; ?>
                            </div>
                        <?php else: ?>
                            <p class="empty-message">No learning outcomes specified.</p>
                        <?php endif; ?>
                    </div> -->
                </div>
            </div>
        </div>
      </div>
      
       <!-- <div class="learning-outcomes-section">
          <h3>What You'll Learn:</h3>
          <div class="outcomes-content">
            <?php if (!empty($course['outcomes'])): ?>
              <div class="formatted-outcomes">
                <?php echo $course['outcomes']; ?>
              </div>
            <?php elseif (count($learningOutcomes) > 0): ?>
              <ul class="outcomes-list">
                <?php foreach ($learningOutcomes as $outcome): ?>
                  <li>
                    <i class="fas fa-check-circle"></i>
                    <div class="outcome-text">
                      <strong><?php echo htmlspecialchars($outcome['title']); ?></strong>
                      <?php if (!empty($outcome['description'])): ?>
                        <span class="outcome-description"><?php echo htmlspecialchars($outcome['description']); ?></span>
                      <?php endif; ?>
                    </div>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <div class="empty-content">
                <i class="fas fa-graduation-cap empty-icon"></i>
                <p>Learning outcomes will be available soon.</p>
                <?php if ($isAdmin): ?>
                  <a href="admin/edit-course.php?id=<?php echo $courseId; ?>#details" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Add Learning Outcomes
                  </a>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </div> -->



      <!-- Specialization Section -->
      <div class="specialization">
        <h2>Specialization: 10 course series</h2>
        <p>Build expertise with <?php echo htmlspecialchars($course['title']); ?> - Specialization Overview</p>
        
        <div class="specialization-info">
          <h4 style="color:#ff5722;">Master <?php echo htmlspecialchars($course['title'] ?? 'data skills'); ?> with 10 power-packed modules designed with industry & academic experts</h4>
          <div class="curriculum-section">
        <h2>What You'll Learn</h2>
        <?php if (count($modules) > 0): ?>
          <?php foreach ($modules as $module): ?>
            <div class="curriculum-item">
              <div class="curriculum-icon"><i class="fas fa-check"></i></div>
              <div class="curriculum-title"><?php echo htmlspecialchars($module['title']); ?></div>
            </div>
          <?php endforeach; ?>
        <?php elseif (count($curriculumItems) > 0): ?>
          <?php foreach ($curriculumItems as $item): ?>
            <div class="curriculum-item">
              <div class="curriculum-icon"><i class="fas fa-check"></i></div>
              <div class="curriculum-title"><?php echo htmlspecialchars($item['title']); ?></div>
              <?php if (!empty($item['description'])): ?>
                <div class="curriculum-description"><?php echo htmlspecialchars($item['description']); ?></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-content">
            <i class="fas fa-graduation-cap empty-icon"></i>
            <p>No curriculum items available yet. Please check back later for updates.</p>
            <?php if ($isAdmin): ?>
              <a href="admin/course-curriculum.php?course_id=<?php echo $courseId; ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add Curriculum Items
              </a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>

         
        </div>
      </div>
      
      <!-- Testimonials Section -->
      <div class="testimonials-section">
        <h2>Here's what our students say about CloudBlitz</h2>
        
        <div class="row">
          <?php if (count($testimonials) > 0): ?>
            <?php foreach ($testimonials as $testimonial): ?>
              <div class="col-md-4">
                <div class="testimonial-card">
                  <div class="student-info">
                    <img src="<?php echo !empty($testimonial['image_path']) ? htmlspecialchars($testimonial['image_path']) : 'images/testimonials/default.jpg'; ?>" alt="<?php echo htmlspecialchars($testimonial['name']); ?>" onerror="this.src='images/testimonials/default.jpg'">
                    <div>
                      <h4><?php echo htmlspecialchars($testimonial['name']); ?></h4>
                      <div class="company-logo">
                        <img src="<?php echo !empty($testimonial['company_logo']) ? htmlspecialchars($testimonial['company_logo']) : 'images/companies/default.png'; ?>" alt="<?php echo htmlspecialchars($testimonial['company'] ?? 'Company'); ?>" onerror="this.src='images/companies/default.png'">
                      </div>
                    </div>
                  </div>
                  <p class="testimonial-text">"<?php echo htmlspecialchars($testimonial['testimonial']); ?>"</p>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <!-- Default testimonials if none in database -->
            <div class="col-md-4">
              <div class="testimonial-card">
                <div class="student-info">
                  <img src="images/testimonials/student1.png" alt="Student" onerror="this.src='images/testimonials/default.jpg'">
                  <div>
                    <h4>Eliza</h4>
                    <div class="company-logo">
                      <img src="images/companies/ibm.png" alt="IBM" onerror="this.src='images/companies/default.png'">
                    </div>
                  </div>
                </div>
                <p class="testimonial-text">"The Data Science course was exactly what I needed to transition into a data role. The instructors were knowledgeable and the hands-on projects gave me practical experience I could showcase in interviews."</p>
              </div>
            </div>
            
            <div class="col-md-4">
              <div class="testimonial-card">
                <div class="student-info">
                  <img src="images/testimonials/student1.png" alt="Student" onerror="this.src='images/testimonials/default.jpg'">
                  <div>
                    <h4>Rahul</h4>
                    <div class="company-logo">
                      <img src="images/companies/cognizant.png" alt="Cognizant" onerror="this.src='images/companies/default.png'">
                    </div>
                  </div>
                </div>
                <p class="testimonial-text">"CloudBlitz's AI course helped me understand complex concepts in a simple way. The curriculum is well-structured and the instructors are always available to clear doubts."</p>
              </div>
            </div>
            
            <div class="col-md-4">
              <div class="testimonial-card">
                <div class="student-info">
                  <img src="images/testimonials/student1.png" alt="Student" onerror="this.src='images/testimonials/default.jpg'">
                  <div>
                    <h4>Michael</h4>
                    <div class="company-logo">
                      <img src="images/companies/google.png" alt="Google" onerror="this.src='images/companies/default.png'">
                    </div>
                  </div>
                </div>
                <p class="testimonial-text">"I was able to land a job at Google after completing the Machine Learning course. The practical approach and industry-relevant curriculum made all the difference."</p>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    
    <!-- Sidebar -->
    <div class="col-lg-4">
      <!-- Contact Form (replacing enrollment form as requested) -->
      <div class="card mb-4 contact-form-card">
        <div class="card-body">
          <h3 class="card-title text-center mb-4">Get Free Counselling</h3>
          <form action="process-counselling.php" method="POST" id="counsellingForm">
            <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
            <div class="form-group">
              <input type="text" class="form-control" name="name" placeholder="Full Name" required>
            </div>
            <div class="form-group">
              <input type="email" class="form-control" name="email" placeholder="Email Address" required>
            </div>
            <div class="form-group">
              <input type="tel" class="form-control" name="phone" placeholder="Phone Number" required>
            </div>
            <button type="submit" class="btn btn-success btn-block">GET FREE COUNSELLING</button>
          </form>
          <div id="formSuccess" class="alert alert-success mt-3" style="display: none;">
            <i class="fas fa-check-circle mr-2"></i> Thank you! Your counselling request has been submitted successfully. We'll contact you shortly.
          </div>
        </div>
      </div>
      
      <!-- Instructor Profile -->
      <?php if ($instructor): ?>
      <div class="instructor-profile">
        <h3>MENTOR PROFILE INFORMATION</h3>
        <div class="instructor-info">
          <?php 
            // Properly format the image path
            $imagePath = !empty($instructor['image_path']) 
              ? './uploads/instructors/' . htmlspecialchars($instructor['image_path']) 
              : './uploads/instructors/default.png'; 
          ?>
          <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($instructor['name']); ?>" onerror="this.src='./uploads/instructors/default.jpg'">
          <h4><?php echo htmlspecialchars($instructor['name']); ?></h4>
          
          <!-- Display instructor title -->
          <p class="instructor-title"><?php echo htmlspecialchars($instructor['title'] ?? 'Course Instructor'); ?></p>
          
          <!-- Display instructor email -->
          <div class="instructor-contact">
            <i class="fas fa-envelope"></i>
            <span><?php echo htmlspecialchars($instructor['email'] ?? 'No email available'); ?></span>
          </div>
          
          <!-- Display instructor rating -->
          <?php if (isset($instructor['rating']) && $instructor['rating'] > 0): ?>
          <div class="instructor-rating">
            <span class="rating-text"><?php echo number_format((float)$instructor['rating'], 1); ?> out of 5</span>
            <div class="rating-stars">
              <?php
                $rating = (float)$instructor['rating'];
                for ($i = 1; $i <= 5; $i++) {
                  if ($i <= floor($rating)) {
                    // Full star
                    echo '<i class="fas fa-star"></i>';
                  } elseif ($i == ceil($rating) && $rating != floor($rating)) {
                    // Half star
                    echo '<i class="fas fa-star-half-alt"></i>';
                  } else {
                    // Empty star
                    echo '<i class="far fa-star"></i>';
                  }
                }
              ?>
            </div>
          </div>
          <?php else: ?>
          <div class="instructor-rating">
            <span class="rating-text">Not yet rated</span>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php else: ?>
      <div class="instructor-profile">
        <h3>MENTOR PROFILE INFORMATION</h3>
        <div class="instructor-info">
          <img src="./uploads/instructors/default.png" alt="Instructor">
          <h4>Expert Instructor</h4>
          <p class="instructor-title">Senior Data Scientist</p>
          <div class="instructor-contact">
            <i class="fas fa-envelope"></i>
            <span>contact@example.com</span>
          </div>
          <div class="instructor-rating">
            <span class="rating-text">Not yet rated</span>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <!-- Key Features Card -->
      <div class="key-features-card">
        <h3>Key Features</h3>
        <ul class="feature-list">
          <li><i class="fas fa-check-circle"></i> Hands-on practical training</li>
          <li><i class="fas fa-check-circle"></i> Industry-relevant curriculum</li>
          <li><i class="fas fa-check-circle"></i> Expert instructors</li>
          <li><i class="fas fa-check-circle"></i> Job placement assistance</li>
          <li><i class="fas fa-check-circle"></i> Flexible learning options</li>
          <li><i class="fas fa-check-circle"></i> Certification upon completion</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- Other Courses Section -->
<?php if (count($relatedCourses) > 0): ?>
<section class="other-courses">
  <div class="container">
    <h2>Other Courses You Might Like</h2>
    
    <div class="row">
      <?php foreach ($relatedCourses as $relatedCourse): ?>
        <div class="col-md-6 col-lg-3 mb-4">
          <div class="course-card">
            <div class="course-image">
              <img src="<?php echo !empty($relatedCourse['image_path']) ? htmlspecialchars($relatedCourse['image_path']) : 'images/courses/default.jpg'; ?>" alt="<?php echo htmlspecialchars($relatedCourse['title']); ?>" onerror="this.src='images/courses/default.jpg'">
            </div>
            <div class="course-content">
              <h3><?php echo htmlspecialchars($relatedCourse['title']); ?></h3>
              <div class="course-actions">
                <?php if (!empty($relatedCourse['brochure_path'])): ?>
                  <a href="<?php echo htmlspecialchars($relatedCourse['brochure_path']); ?>" class="btn-brochure" download><i class="fas fa-download"></i> BROCHURE</a>
                <?php else: ?>
                  <a href="#" class="btn-brochure" onclick="alert('Brochure coming soon!'); return false;"><i class="fas fa-download"></i> BROCHURE</a>
                <?php endif; ?>
                <a href="course.php?id=<?php echo $relatedCourse['id']; ?>" class="btn-view-program">VIEW PROGRAM</a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Certificate Section -->
<section class="certificate-section">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-6">
        <h2>Earn A Career Certificate</h2>
        <p>Add a valuable credential to your LinkedIn profile, resume, or CV</p>
        <p>Continue to build your skills with additional courses</p>
      </div>
      <div class="col-md-6">
        <?php
        // Get certificate image from database if available
        $certificateImage = 'images/certificate.png';
        try {
            $stmt = $conn->prepare("SELECT certificate_image FROM courses WHERE id = :id AND certificate_image IS NOT NULL");
            $stmt->bindParam(':id', $courseId, PDO::PARAM_INT);
            $stmt->execute();
            $certData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($certData && !empty($certData['certificate_image'])) {
                $certificateImage = $certData['certificate_image'];
            }
        } catch (PDOException $e) {
            // Continue with default image
        }
        ?>
        <img src="<?php echo htmlspecialchars($certificateImage); ?>" alt="Certificate Sample" class="img-fluid" onerror="this.src='images/certificate.png'">
      </div>
    </div>
  </div>
</section>

<!-- Course Styles -->
<style>
/* Admin Controls */
.admin-controls {
  background-color: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
  padding: 15px 0;
  margin-bottom: 20px;
  transition: all 0.3s ease;
}

.admin-controls:hover {
  background-color: #edf2f7;
}

.admin-panel {
  background-color: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  padding: 20px;
}

.admin-panel h4 {
  margin-bottom: 15px;
  color: #333;
  font-size: 18px;
  font-weight: 600;
  position: relative;
  padding-bottom: 10px;
}

.admin-panel h4:after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 0;
  width: 40px;
  height: 2px;
  background-color: #ff5722;
}

.admin-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.admin-buttons .btn {
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 8px 15px;
  font-size: 14px;
  font-weight: 500;
  border-radius: 4px;
  transition: all 0.3s ease;
}

.admin-buttons .btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Hero Section */
.course-hero {
  background-color: #0a1a2a;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  padding: 100px 0;
  position: relative;
  color: #fff;
  transition: all 0.5s ease;
}

.course-hero::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(to right, rgba(10, 26, 42, 0.9), rgba(10, 26, 42, 0.7));
  transition: all 0.5s ease;
}

.course-hero:hover::before {
  background: linear-gradient(to right, rgba(10, 26, 42, 0.8), rgba(10, 26, 42, 0.6));
}

.course-hero .container {
  position: relative;
  z-index: 1;
}

.course-hero h1 {
  font-size: 42px;
  font-weight: 700;
  margin-bottom: 15px;
  text-transform: uppercase;
  color: #fff;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  animation: fadeInDown 1s ease;
}

.course-subtitle {
  font-size: 18px;
  font-weight: 500;
  margin: 0;
  color: #fff;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
  animation: fadeInUp 1s ease;
}

/* Course Tabs */
.course-tabs {
  margin-top: 20px;
  margin-bottom: 30px;
  background-color: #fff;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.course-tabs:hover {
  box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15);
}

.nav-tabs {
  border-bottom: 1px solid #dee2e6;
  background-color: #fff;
}

.nav-tabs .nav-link {
  border: none;
  border-radius: 0;
  padding: 15px 20px;
  font-weight: 600;
  color: #333;
  transition: all 0.3s ease;
  position: relative;
}

.nav-tabs .nav-link:hover {
  color: #ff5722;
  background-color: #f9f9f9;
}

.nav-tabs .nav-link.active {
  color: #fff;
  background-color: #ff5722;
  border-bottom: none;
}

.nav-tabs .nav-link.active::after {
  content: "";
  position: absolute;
  bottom: -1px;
  left: 0;
  width: 100%;
  height: 3px;
  background-color: #ff5722;
}

.tab-content {
  padding: 25px;
}

/* Empty Content Styling */
.empty-content {
    text-align: center;
    padding: 30px;
    background-color: #f9f9f9;
    border-radius: 8px;
    margin: 20px 0;
}

.empty-content .empty-icon {
    font-size: 48px;
    color: #ccc;
    margin-bottom: 15px;
}

.empty-content p {
    color: #666;
    margin-bottom: 20px;
}

.empty-message {
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 5px;
    color: #6c757d;
    font-style: italic;
    text-align: center;
    margin: 15px 0;
}

.course-content-text {
    white-space: pre-line;
    line-height: 1.8;
    color: #333;
}

/* Ratings Section */
.ratings-container {
  display: flex;
  justify-content: space-between;
  margin-bottom: 30px;
  border-bottom: 1px solid #eee;
  padding-bottom: 25px;
}

.rating-box {
  text-align: center;
  padding: 20px;
  background-color: #f9f9f9;
  border-radius: 8px;
  width: 48%;
  transition: all 0.3s ease;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.rating-box:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

.rating-number {
  font-size: 32px;
  font-weight: 700;
  color: #ff5722;
  margin-bottom: 5px;
}

.rating-stars {
  color: #ffc107;
  margin: 5px 0;
  font-size: 20px;
  letter-spacing: 2px;
}

.rating-label {
  font-size: 14px;
  color: #666;
  font-weight: 500;
  margin-top: 5px;
}

/* Course Info Boxes */
.course-info-boxes {
  display: flex;
  flex-wrap: wrap;
  margin-bottom: 40px;
  gap: 20px;
}

.info-box {
  flex: 1;
  min-width: 200px;
  padding: 25px;
  background-color: #f9f9f9;
  border-radius: 8px;
  transition: all 0.3s ease;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.info-box:hover {
  background-color: #f5f5f5;
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
  transform: translateY(-5px);
}

.info-box h3 {
  font-size: 16px;
  font-weight: 600;
  margin-bottom: 15px;
  color: #333;
  border-bottom: 2px solid #ff5722;
  padding-bottom: 8px;
  display: inline-block;
}

.info-box ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.info-box ul li {
  padding: 8px 0;
  font-size: 14px;
  color: #666;
  position: relative;
  padding-left: 25px;
  transition: all 0.2s ease;
}

.info-box ul li:hover {
  color: #333;
  transform: translateX(5px);
}

.info-box ul li:before {
  content: "•";
  color: #ff5722;
  position: absolute;
  left: 0;
  font-size: 20px;
  line-height: 1;
}

/* Course Description */
.course-description h2 {
  font-size: 28px;
  font-weight: 600;
  margin-bottom: 25px;
  color: #333;
  position: relative;
  padding-bottom: 12px;
}

.course-description h2:after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 0;
  width: 60px;
  height: 3px;
  background-color: #ff5722;
}

.description-content {
  font-size: 16px;
  line-height: 1.8;
  color: #666;
}

.description-content p {
  margin-bottom: 15px;
}

/* Curriculum Section */
.curriculum-section {
  margin: 50px 0;
  padding: 35px;
  background-color: #f9f9f9;
  border-radius: 8px;
  box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
}

.curriculum-section h2 {
  font-size: 28px;
  font-weight: 600;
  margin-bottom: 30px;
  color: #333;
  position: relative;
  padding-bottom: 12px;
}

.curriculum-section h2:after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 0;
  width: 60px;
  height: 3px;
  background-color: #ff5722;
}

.curriculum-item {
  display: flex;
  align-items: center;
  margin-bottom: 15px;
  background-color: #fff;
  padding: 18px;
  border-radius: 8px;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.curriculum-item:hover {
  transform: translateX(8px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.curriculum-icon {
  width: 35px;
  height: 35px;
  background-color: #ff5722;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 18px;
  color: white;
  font-size: 14px;
  transition: all 0.3s ease;
}

.curriculum-item:hover .curriculum-icon {
  transform: scale(1.1);
}

.curriculum-title {
  font-weight: 600;
  color: #333;
  font-size: 16px;
  transition: all 0.3s ease;
}

.curriculum-item:hover .curriculum-title {
  color: #ff5722;
}

.curriculum-description {
  margin-top: 8px;
  font-size: 14px;
  color: #666;
  padding-left: 53px;
}

/* Modules Tab */
.course-modules-list {
  margin-top: 25px;
}

.accordion-item {
  margin-bottom: 20px;
  border: none;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  border-radius: 8px;
  overflow: hidden;
  transition: all 0.3s ease;
}

.accordion-item:hover {
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
}

.accordion-header {
  background-color: #f8f9fa;
  border-bottom: none;
  padding: 0;
}

.accordion-button {
  color: #333;
  font-weight: 600;
  text-decoration: none;
  padding: 18px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  transition: all 0.3s ease;
  background-color: #f8f9fa;
}

.accordion-button:not(.collapsed) {
  color: #fff;
  background-color: #ff5722;
}

.accordion-button:focus {
  box-shadow: none;
  border-color: rgba(0,0,0,.125);
}

.accordion-body {
  padding: 20px;
}

.lessons-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.lesson-item {
  display: flex;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid #eee;
}

.lesson-item:last-child {
  border-bottom: none;
}

.lesson-number {
  font-weight: 600;
  color: #ff5722;
  margin-right: 10px;
  min-width: 25px;
}

.lesson-title {
  flex: 1;
  font-weight: 500;
}

.lesson-duration {
  color: #666;
  font-size: 14px;
}

/* Specialization Section */
.specialization-section {
  margin: 50px 0;
  padding: 35px;
  background-color: #f9f9f9;
  border-radius: 8px;
  box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
}

.specialization-section h2 {
  font-size: 28px;
  font-weight: 600;
  margin-bottom: 15px;
  color: #333;
  position: relative;
  padding-bottom: 12px;
}

.specialization-section h2:after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 0;
  width: 60px;
  height: 3px;
  background-color: #ff5722;
}

.specialization-section > p {
  font-size: 18px;
  color: #666;
  margin-bottom: 25px;
}

.specialization-info {
  background-color: #fff;
  padding: 25px;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
}

.specialization-info:hover {
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
  transform: translateY(-5px);
}

.specialization-info p {
  margin-bottom: 15px;
  font-size: 16px;
  color: #666;
  line-height: 1.7;
}

.specialization-info h3 {
  font-size: 20px;
  font-weight: 600;
  margin: 25px 0 20px;
  color: #333;
  position: relative;
  padding-bottom: 10px;
}

.specialization-info h3:after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 0;
  width: 40px;
  height: 2px;
  background-color: #ff5722;
}

.specialization-info ul {
  padding-left: 20px;
  margin-bottom: 25px;
}

.specialization-info ul li {
  margin-bottom: 12px;
  font-size: 15px;
  color: #666;
  position: relative;
  padding-left: 5px;
  line-height: 1.6;
}

.specialization-info ul li strong {
  color: #333;
  font-weight: 600;
}

/* Testimonials Section */
.testimonials-section {
  margin: 50px 0;
  padding: 0;
}

.testimonials-section h2 {
  font-size: 28px;
  font-weight: 600;
  margin-bottom: 35px;
  color: #333;
  text-align: center;
  position: relative;
  padding-bottom: 15px;
}

.testimonials-section h2:after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 60px;
  height: 3px;
  background-color: #ff5722;
}

.testimonial-card {
  padding: 30px;
  background-color: #fff;
  border-radius: 8px;
  margin-bottom: 20px;
  height: 100%;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.testimonial-card:hover {
  transform: translateY(-10px);
  box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
}

.student-info {
  display: flex;
  align-items: center;
  margin-bottom: 20px;
}

.student-info img {
  width: 70px;
  height: 70px;
  border-radius: 50%;
  object-fit: cover;
  margin-right: 15px;
  border: 3px solid #f5f5f5;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.testimonial-card:hover .student-info img {
  transform: scale(1.1);
  border-color: #ff5722;
}

.student-info h4 {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 5px;
  color: #333;
}

.company-logo img {
  height: 20px;
  width: auto;
}

.testimonial-text {
  font-size: 16px;
  color: #666;
  line-height: 1.8;
  font-style: italic;
  position: relative;
  padding-left: 20px;
}

.testimonial-text:before {
  content: '"';
  font-size: 40px;
  color: #ff5722;
  position: absolute;
  left: 0;
  top: -10px;
  font-family: Georgia, serif;
  opacity: 0.5;
}

/* Instructor Profile */
.instructor-profile {
  background-color: #fff;
  padding: 30px;
  border-radius: 8px;
  margin-bottom: 30px;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.instructor-profile:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
}

.instructor-profile h3 {
  font-size: 20px;
  font-weight: 600;
  margin-bottom: 25px;
  color: #333;
  text-align: center;
  position: relative;
  padding-bottom: 12px;
}

.instructor-profile h3:after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 40px;
  height: 2px;
  background-color: #ff5722;
}

.instructor-info {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.instructor-info img {
  width: 150px;
  height: 150px;
  border-radius: 50%;
  object-fit: cover;
  margin-bottom: 20px;
  border: 5px solid #f5f5f5;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.instructor-profile:hover .instructor-info img {
  transform: scale(1.05);
  border-color: #ff5722;
}

.instructor-info h4 {
  font-size: 22px;
  font-weight: 600;
  margin-bottom: 5px;
  color: #333;
}

.instructor-title {
  font-size: 16px;
  color: #666;
  margin-bottom: 15px;
}

.instructor-contact {
  margin-bottom: 15px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.instructor-contact i {
  color: #ff5722;
}

.instructor-rating {
  margin-top: 15px;
  text-align: center;
}

.rating-text {
  font-size: 14px;
  display: block;
  margin-bottom: 5px;
  color: #555;
}

.rating-stars {
  color: #ffc107;
  font-size: 18px;
  letter-spacing: 2px;
}

/* Contact Form Card */
.contact-form-card {
  background-color: #fff;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
  margin-bottom: 30px;
  transition: all 0.3s ease;
}

.contact-form-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
}

.contact-form-card .card-title {
  color: #333;
  font-weight: 600;
  position: relative;
  padding-bottom: 12px;
  margin-bottom: 20px;
}

.contact-form-card .card-title:after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 40px;
  height: 2px;
  background-color: #ff5722;
}

.contact-form-card .form-control {
  border-radius: 4px;
  padding: 12px 15px;
  margin-bottom: 15px;
  border: 1px solid #ddd;
  transition: all 0.3s ease;
}

.contact-form-card .form-control:focus {
  border-color: #ff5722;
  box-shadow: 0 0 0 0.2rem rgba(255, 87, 34, 0.25);
}

.contact-form-card .btn {
  background-color: #ff5722;
  border-color: #ff5722;
  padding: 12px;
  font-weight: 600;
  letter-spacing: 0.5px;
  transition: all 0.3s ease;
}

.contact-form-card .btn:hover {
  background-color: #e64a19;
  border-color: #e64a19;
  transform: translateY(-2px);
  box-shadow: 0 5px 10px rgba(230, 74, 25, 0.3);
}

/* Key Features Card */
.key-features-card {
  background-color: #fff;
  border-radius: 8px;
  padding: 30px;
  margin-bottom: 30px;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.key-features-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
}

.key-features-card h3 {
  font-size: 20px;
  font-weight: 600;
  margin-bottom: 25px;
  color: #333;
  position: relative;
  padding-bottom: 12px;
  text-align: center;
}

.key-features-card h3:after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 40px;
  height: 2px;
  background-color: #ff5722;
}

.feature-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.feature-list li {
  padding: 12px 0;
  border-bottom: 1px solid #f5f5f5;
  display: flex;
  align-items: center;
  gap: 12px;
  transition: all 0.3s ease;
}

.feature-list li:last-child {
  border-bottom: none;
}

.feature-list li:hover {
  transform: translateX(5px);
}

.feature-list li i {
  color: #ff5722;
  font-size: 18px;
}

/* Other Courses Section */
.other-courses {
  padding: 70px 0;
  background-color: #f9f9f9;
}

.other-courses h2 {
  font-size: 32px;
  font-weight: 600;
  margin-bottom: 50px;
  color: #333;
  text-align: center;
  position: relative;
  padding-bottom: 15px;
}

.other-courses h2:after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 60px;
  height: 3px;
  background-color: #ff5722;
}

.course-card {
  background-color: #fff;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
  height: 100%;
  transition: all 0.3s ease;
}

.course-card:hover {
  transform: translateY(-15px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.course-image {
  height: 200px;
  overflow: hidden;
  position: relative;
}

.course-image::after {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(to bottom, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.3));
  opacity: 0;
  transition: opacity 0.3s ease;
}

.course-card:hover .course-image::after {
  opacity: 1;
}

.course-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s ease;
}

.course-card:hover .course-image img {
  transform: scale(1.1);
}

.course-content {
  padding: 25px;
}

.course-content h3 {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 20px;
  color: #333;
  height: 50px;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  transition: color 0.3s ease;
}

.course-card:hover .course-content h3 {
  color: #ff5722;
}

.course-actions {
  display: flex;
  justify-content: space-between;
  margin-top: 25px;
}

.btn-brochure,
.btn-view-program {
  padding: 10px 15px;
  font-size: 13px;
  font-weight: 600;
  text-transform: uppercase;
  border-radius: 4px;
  text-decoration: none;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 5px;
}

.btn-brochure {
  background-color: #f5f5f5;
  color: #333;
  border: 1px solid #ddd;
}

.btn-brochure:hover {
  background-color: #e9e9e9;
  text-decoration: none;
  color: #333;
  transform: translateY(-2px);
  box-shadow: 0 5px 10px rgba(0, 0, 0, 0.05);
}

.btn-view-program {
  background-color: #ff5722;
  color: #fff;
}

.btn-view-program:hover {
  background-color: #e64a19;
  text-decoration: none;
  color: #fff;
  transform: translateY(-2px);
  box-shadow: 0 5px 10px rgba(230, 74, 25, 0.3);
}

/* Certificate Section */
.certificate-section {
  padding: 70px 0;
  background-color: #fff;
}

.certificate-section h2 {
  font-size: 32px;
  font-weight: 600;
  margin-bottom: 25px;
  color: #333;
  position: relative;
  padding-bottom: 12px;
}

.certificate-section h2:after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 0;
  width: 60px;
  height: 3px;
  background-color: #ff5722;
}

.certificate-section p {
  font-size: 16px;
  color: #666;
  margin-bottom: 15px;
  line-height: 1.7;
}

/* Animations */
@keyframes fadeInDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive Styles */
@media (max-width: 991px) {
  .course-info-boxes {
    flex-direction: column;
  }

  .info-box {
    margin-bottom: 20px;
  }

  .admin-buttons {
    flex-direction: column;
  }

  .admin-buttons .btn {
    width: 100%;
    margin-bottom: 10px;
  }

  .course-hero h1 {
    font-size: 36px;
  }
}

@media (max-width: 767px) {
  .course-hero {
    padding: 60px 0;
  }

  .course-hero h1 {
    font-size: 32px;
  }

  .course-subtitle {
    font-size: 16px;
  }

  .nav-tabs .nav-link {
    padding: 10px 15px;
    font-size: 14px;
  }

  .ratings-container {
    flex-direction: column;
  }

  .rating-box {
    width: 100%;
    margin-bottom: 20px;
  }

  .curriculum-item {
    flex-direction: column;
    align-items: flex-start;
  }

  .curriculum-icon {
    margin-bottom: 10px;
  }

  .module-title {
    flex-wrap: wrap;
  }

  .testimonial-card {
    padding: 20px;
  }

  .student-info img {
    width: 50px;
    height: 50px;
  }

  .other-courses h2,
  .certificate-section h2,
  .course-description h2 {
    font-size: 24px;
  }
}

@media (max-width: 575px) {
  .course-hero h1 {
    font-size: 28px;
  }

  .course-actions {
    flex-direction: column;
    gap: 10px;
  }

  .btn-brochure,
  .btn-view-program {
    width: 100%;
    text-align: center;
    justify-content: center;
  }

  .action-bar {
    flex-direction: column;
    gap: 15px;
  }

  .action-bar .action-buttons {
    width: 100%;
    justify-content: space-between;
  }

  .instructor-info img {
    width: 120px;
    height: 120px;
  }
}
</style>

<!-- JavaScript for Form Handling -->
<script>
$(document).ready(function() {
  // Handle counselling form submission
  $('#counsellingForm').on('submit', function(e) {
    e.preventDefault();
    
    $.ajax({
      type: 'POST',
      url: 'process-counselling.php',
      data: $(this).serialize(),
      success: function(response) {
        $('#counsellingForm').hide();
        $('#formSuccess').show();
      },
      error: function() {
        alert('There was an error submitting the form. Please try again.');
      }
    });
  });
  
  // Initialize tooltips
  $('[data-toggle="tooltip"]').tooltip();
  
  // Make all tabs accessible even if content is empty
  $('#courseTabs a').on('click', function(e) {
    e.preventDefault();
    $(this).tab('show');
  });
});

// Initialize Bootstrap tabs
document.addEventListener('DOMContentLoaded', function() {
    // Check if Bootstrap 5 is being used
    if (typeof bootstrap !== 'undefined') {
        var tabElms = document.querySelectorAll('button[data-bs-toggle="tab"]');
        tabElms.forEach(function(tabElm) {
            new bootstrap.Tab(tabElm);
        });
    } else {
        // For Bootstrap 4 or jQuery
        $('#courseTabs button').on('click', function(e) {
            e.preventDefault();
            $(this).tab('show');
        });
    }
});
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>