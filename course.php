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
$stmt = $conn->prepare("SELECT * FROM courses 
                       WHERE category_id = :category_id 
                       AND id != :id 
                       ORDER BY created_at DESC LIMIT 4");
$stmt->bindParam(':category_id', $course['category_id'], PDO::PARAM_INT);
$stmt->bindParam(':id', $courseId, PDO::PARAM_INT);
$stmt->execute();
$relatedCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

// Get testimonials
$testimonials = [];
try {
    // Assuming you have a testimonials table or similar
    $stmt = $conn->prepare("SELECT * FROM testimonials WHERE status = 'active' ORDER BY RAND() LIMIT 3");
    $stmt->execute();
    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table might not exist, continue without testimonials
}
?>

<style>
/* Hero Section */
.course-hero {
    background-color: #0a1a2a;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    padding: 60px 0;
    position: relative;
    color: #fff;
}

.course-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(10, 26, 42, 0.7);
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
}

.course-subtitle {
    font-size: 18px;
    font-weight: 500;
    margin: 0;
    color: #fff;
}

/* Course Tabs */
.course-tabs {
    margin-top: 20px;
    margin-bottom: 30px;
    background-color: #fff;
    border-radius: 0;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
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
}

.nav-tabs .nav-link:hover {
    color:rgb(166, 47, 0);
}

.nav-tabs .nav-link.active {
    color: #fff;
    background-color: #ff5722;
    border-bottom: none;
}

.tab-content {
    padding: 20px;
}

/* Ratings Section */
.ratings-container {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
    border-bottom: 1px solid #eee;
    padding-bottom: 20px;
}

.rating-box {
    text-align: center;
    padding: 10px;
    background-color: #f9f9f9;
    border-radius: 5px;
    width: 48%;
}

.rating-number {
    font-size: 24px;
    font-weight: 700;
    color: #ff5722;
}

.rating-stars {
    color: #ffc107;
    margin: 5px 0;
}

.rating-label {
    font-size: 14px;
    color: #666;
}

/* Course Info Boxes */
.course-info-boxes {
    display: flex;
    flex-wrap: wrap;
    margin-bottom: 30px;
    gap: 15px;
}

.info-box {
    flex: 1;
    min-width: 200px;
    padding: 15px;
    background-color: #f9f9f9;
    border-radius: 5px;
}

.info-box h3 {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 10px;
    color: #333;
}

.info-box ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.info-box ul li {
    padding: 3px 0;
    font-size: 14px;
    color: #666;
}

/* Course Description */
.course-description h2 {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 20px;
    color: #333;
}

.description-content {
    font-size: 15px;
    line-height: 1.6;
    color: #666;
}

/* Curriculum Section */
.curriculum-section {
    margin-top: 30px;
}

.curriculum-item {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.curriculum-icon {
    width: 24px;
    height: 24px;
    background-color: #ff5722;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    color: white;
    font-size: 12px;
}

.curriculum-title {
    font-weight: 600;
    color: #333;
    font-size: 16px;
}

/* Specialization Section */
.specialization-section {
    margin: 40px 0;
    padding: 0;
}

.specialization-section h2 {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 10px;
    color: #333;
}

.specialization-section > p {
    font-size: 15px;
    color: #666;
    margin-bottom: 20px;
}

.specialization-info {
    background-color: #fff;
    padding: 0;
}

.specialization-info p {
    margin-bottom: 15px;
    font-size: 15px;
    color: #666;
}

.specialization-info h3 {
    font-size: 18px;
    font-weight: 600;
    margin: 20px 0 10px;
    color: #333;
}

.specialization-info ul {
    padding-left: 20px;
    margin-bottom: 20px;
}

.specialization-info ul li {
    margin-bottom: 8px;
    font-size: 15px;
    color: #666;
}

.specialization-info ul li strong {
    color: #333;
}

/* Testimonials Section */
.testimonials-section {
    margin: 40px 0;
    padding: 0;
}

.testimonials-section h2 {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 30px;
    color: #333;
}

.testimonial-card {
    padding: 20px;
    background-color: #f9f9f9;
    border-radius: 5px;
    margin-bottom: 20px;
    height: 100%;
}

.student-info {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.student-info img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 15px;
}

.student-info h4 {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 5px;
    color: #333;
}

.company-logo img {
    height: 20px;
    width: auto;
}

.testimonial-text {
    font-size: 14px;
    color: #666;
    line-height: 1.6;
}

/* Instructor Profile */
.instructor-profile {
    background-color: #f9f9f9;
    padding: 20px;
    border-radius: 5px;
    margin-bottom: 30px;
}

.instructor-profile h3 {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 20px;
    color: #333;
    text-align: center;
}

.instructor-info {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.instructor-info img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 15px;
}

.instructor-info h4 {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 5px;
    color: #333;
}

.instructor-title {
    font-size: 14px;
    color: #666;
    margin-bottom: 10px;
}

/* Other Courses Section */
.other-courses {
    padding: 40px 0;
    background-color: #f9f9f9;
}

.other-courses h2 {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 30px;
    color: #333;
    text-align: center;
}

.course-card {
    background-color: #fff;
    border-radius: 5px;
    overflow: hidden;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    height: 100%;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.course-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.course-image {
    height: 180px;
    overflow: hidden;
}

.course-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.course-card:hover .course-image img {
    transform: scale(1.05);
}

.course-content {
    padding: 15px;
}

.course-content h3 {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 10px;
    color: #333;
    height: 40px;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.course-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 15px;
}

.btn-brochure, .btn-view-program {
    padding: 8px 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    border-radius: 3px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-brochure {
    background-color: #f5f5f5;
    color: #333;
    border: 1px solid #ddd;
}

.btn-brochure:hover {
    background-color: #e9e9e9;
}

.btn-view-program {
    background-color: #ff5722;
    color: #fff;
}

.btn-view-program:hover {
    background-color: #ff5722;
}

/* Certificate Section */
.certificate-section {
    padding: 40px 0;
    background-color: #fff;
}

.certificate-section h2 {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 20px;
    color: #333;
}

.certificate-section p {
    font-size: 15px;
    color: #666;
    margin-bottom: 10px;
}

/* Responsive Styles */
@media (max-width: 991px) {
    .course-info-boxes {
        flex-direction: column;
    }
    
    .info-box {
        margin-bottom: 15px;
    }
}

@media (max-width: 767px) {
    .course-hero {
        padding: 40px 0;
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
        margin-bottom: 15px;
    }
    
    .curriculum-item {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .curriculum-icon {
        margin-bottom: 10px;
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
    
    .btn-brochure, .btn-view-program {
        width: 100%;
        text-align: center;
    }
}
</style>

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
        <ul class="nav nav-tabs" id="courseTab" role="tablist">
          <li class="nav-item">
            <a class="nav-link active" id="overview-tab" data-toggle="tab" href="#overview" role="tab">Course Overview</a>
          </li>
          <?php if (!empty($course['curriculum'])): ?>
          <li class="nav-item">
            <a class="nav-link" id="practical-tab" data-toggle="tab" href="#practical" role="tab">Practical Training</a>
          </li>
          <?php endif; ?>
          <?php if (!empty($course['job_support'])): ?>
          <li class="nav-item">
            <a class="nav-link" id="job-support-tab" data-toggle="tab" href="#job-support" role="tab">Job Support</a>
          </li>
          <?php endif; ?>
          <?php if (!empty($course['certification'])): ?>
          <li class="nav-item">
            <a class="nav-link" id="certification-tab" data-toggle="tab" href="#certification" role="tab">Certification</a>
          </li>
          <?php endif; ?>
        </ul>
        
        <div class="tab-content" id="courseTabContent">
          <!-- Overview Tab -->
          <div class="tab-pane fade show active" id="overview" role="tabpanel">
            <!-- Ratings Section -->
            <div class="ratings-container">
              <?php
              // Get actual ratings from database if available
              $avgRating = 4.8; // Default value
              $ratingCount = 51; // Default value
              $reviewCount = 140; // Default value
              
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
              ?>
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
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
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
                  <?php else: ?>
                    <li><?php echo htmlspecialchars($course['duration'] ?? '3 Months'); ?></li>
                    <li>Weekend Batches</li>
                    <li>Weekday Batches</li>
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
                  } else {
                      // Get upcoming batches from database if available
                      try {
                          $stmt = $conn->prepare("SELECT batch_date FROM course_batches WHERE course_id = :course_id AND batch_date >= CURDATE() ORDER BY batch_date LIMIT 3");
                          $stmt->bindParam(':course_id', $courseId, PDO::PARAM_INT);
                          $stmt->execute();
                          $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                          
                          if (count($batches) > 0) {
                              foreach ($batches as $batch) {
                                  echo '<li>' . date('F j, Y', strtotime($batch['batch_date'])) . '</li>';
                              }
                          } else {
                              echo '<li>May 15, 2023</li>';
                     
                          }
                      } catch (PDOException $e) {
                          // Table might not exist, use default values
                          echo '<li>May 15, 2023</li>';
                     
                      }
                  }
                  ?>
                </ul>
              </div>
            </div>
            
            <!-- Course Description -->
            <div class="course-description">
              <h2>Course Description</h2>
              <div class="description-content">
                <?php echo $course['description']; ?>
              </div>
            </div>
          </div>
          
          <!-- Practical Training Tab -->
          <?php if (!empty($course['curriculum'])): ?>
          <div class="tab-pane fade" id="practical" role="tabpanel">
            <div class="course-curriculum">
              <h2>Course Curriculum</h2>
              <?php echo $course['curriculum']; ?>
            </div>
          </div>
          <?php endif; ?>
          
          <!-- Job Support Tab -->
          <?php if (!empty($course['job_support'])): ?>
          <div class="tab-pane fade" id="job-support" role="tabpanel">
            <div class="course-job-support">
              <h2>Job Support</h2>
              <?php echo $course['job_support']; ?>
            </div>
          </div>
          <?php endif; ?>
          
          <!-- Certification Tab -->
          <?php if (!empty($course['certification'])): ?>
          <div class="tab-pane fade" id="certification" role="tabpanel">
            <div class="course-certification">
              <h2>Certification</h2>
              <?php echo $course['certification']; ?>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
      
      <!-- Curriculum Section -->
      <div class="curriculum-section">
        <?php if (count($modules) > 0): ?>
          <?php foreach ($modules as $module): ?>
            <div class="curriculum-item">
              <div class="curriculum-icon"><i class="fas fa-check"></i></div>
              <div class="curriculum-title"><?php echo htmlspecialchars($module['title']); ?></div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <?php
          // Try to get curriculum items from a different table if modules are not available
          $curriculumItems = [];
          try {
              $stmt = $conn->prepare("SELECT title FROM course_curriculum_items WHERE course_id = :course_id ORDER BY display_order");
              $stmt->bindParam(':course_id', $courseId, PDO::PARAM_INT);
              $stmt->execute();
              $curriculumItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
          } catch (PDOException $e) {
              // Table might not exist, use default items
              $curriculumItems = [
                  ['title' => 'Core Partner'],
                  ['title' => 'Advanced Python'],
                  ['title' => 'Structured Query Language'],
                  ['title' => 'Statistics'],
                  ['title' => 'Machine Learning'],
                  ['title' => 'Deep Learning'],
                  ['title' => 'Artificial Intelligence'],
                  ['title' => 'Business Intelligence'],
                  ['title' => 'R Programming']
              ];
          }
          
          foreach ($curriculumItems as $item):
          ?>
            <div class="curriculum-item">
              <div class="curriculum-icon"><i class="fas fa-check"></i></div>
              <div class="curriculum-title"><?php echo htmlspecialchars($item['title']); ?></div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      
      <!-- Specialization Section -->
      <div class="specialization-section">
        <h2>Specialization: 10 course series</h2>
        <p>Build expertise with <?php echo htmlspecialchars($course['title']); ?> - Specialization Overview</p>
        
        <div class="specialization-info">
          <p>Master <?php echo htmlspecialchars($course['category_name'] ?? 'data skills'); ?> with 10 power-packed modules designed with industry & academic experts</p>
          
          <h3>What You'll Learn:</h3>
          <?php if (!empty($course['outcomes'])): ?>
            <?php echo $course['outcomes']; ?>
          <?php else: ?>
            <?php
            // Try to get learning outcomes from database
            $learningOutcomes = [];
            try {
                $stmt = $conn->prepare("SELECT title, description FROM course_learning_outcomes WHERE course_id = :course_id ORDER BY display_order");
                $stmt->bindParam(':course_id', $courseId, PDO::PARAM_INT);
                $stmt->execute();
                $learningOutcomes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // Table might not exist, use default outcomes
                $learningOutcomes = [
                    ['title' => 'Introduction to Data Science', 'description' => 'Understand the fundamentals of data science'],
                    ['title' => 'Python for Data Science', 'description' => 'Code with Python, the #1 AI language'],
                    ['title' => 'Data Analysis & Visualization', 'description' => 'Clean, analyze, and visualize data'],
                    ['title' => 'Statistics Essentials', 'description' => 'Understand key statistical concepts'],
                    ['title' => 'Machine Learning Basics', 'description' => 'Train models to predict and classify'],
                    ['title' => 'Deep Learning Fundamentals', 'description' => 'Build neural networks'],
                    ['title' => 'Data Wrangling & Databases', 'description' => 'Organize and manage data like a pro'],
                    ['title' => 'Big Data & Cloud Tools', 'description' => 'Learn how big data & AWS Cloud'],
                    ['title' => 'AI Applications', 'description' => 'Apply AI to real-world problems'],
                    ['title' => 'Capstone Project', 'description' => 'Real-use case learnings with machine learning']
                ];
            }
            ?>
            <ul>
              <?php foreach ($learningOutcomes as $outcome): ?>
                <li><strong><?php echo htmlspecialchars($outcome['title']); ?></strong> - <?php echo htmlspecialchars($outcome['description']); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
          
          <p><strong>Hands-on practice + real-world tools</strong></p>
          <p><strong>Pathway to AWS & CloudBlitz Credentials</strong></p>
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
                        <img src="<?php echo !empty($testimonial['company_logo']) ? htmlspecialchars($testimonial['company_logo']) : 'images/companies/default.png'; ?>" alt="<?php echo htmlspecialchars($testimonial['company']); ?>" onerror="this.src='images/companies/default.png'">
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
                  <img src="images/testimonials/student1.jpg" alt="Student" onerror="this.src='images/testimonials/default.jpg'">
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
                  <img src="images/testimonials/student2.jpg" alt="Student" onerror="this.src='images/testimonials/default.jpg'">
                  <div>
                    <h4>Rahul</h4>
                    <div class="company-logo">
                      <img src="images/companies/microsoft.png" alt="Microsoft" onerror="this.src='images/companies/default.png'">
                    </div>
                  </div>
                </div>
                <p class="testimonial-text">"CloudBlitz's AI course helped me understand complex concepts in a simple way. The curriculum is well-structured and the instructors are always available to clear doubts."</p>
              </div>
            </div>
            
            <div class="col-md-4">
              <div class="testimonial-card">
                <div class="student-info">
                  <img src="images/testimonials/student3.jpg" alt="Student" onerror="this.src='images/testimonials/default.jpg'">
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
      <!-- Instructor Profile -->
      <?php if ($instructor): ?>
      <div class="instructor-profile">
        <h3>MENTOR PROFILE INFORMATION</h3>
        <div class="instructor-info">
          <img src="<?php echo !empty($instructor['image_path']) ? htmlspecialchars($instructor['image_path']) : './images/instructors/default.png'; ?>" alt="<?php echo htmlspecialchars($instructor['name']); ?>" onerror="this.src='images/instructors/default.jpg'">
          <h4><?php echo htmlspecialchars($instructor['name']); ?></h4>
          <p class="instructor-title"><?php echo htmlspecialchars($instructor['title'] ?? 'Course Instructor'); ?></p>
        </div>
      </div>
      <?php else: ?>
      <div class="instructor-profile">
        <h3>MENTOR PROFILE INFORMATION</h3>
        <div class="instructor-info">
          <img src="images/instructors/default.png" alt="Instructor">
          <h4>Expert Instructor</h4>
          <p class="instructor-title">Senior Data Scientist</p>
        </div>
      </div>
      <?php endif; ?>
      
      <!-- Contact Form (replacing enrollment form as requested) -->
      <div class="card mb-4">
        <div class="card-body">
          <h3 class="card-title text-center mb-4" style="color: #ff5722;">Get Free Counselling</h3>
          <form action="process-counselling.php" method="POST">
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
            <button type="submit" class="btn btn-success btn-block" style="background-color: #ff5722;">GET FREE COUNSELLING</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Other Courses Section -->
<?php if (count($relatedCourses) > 0): ?>
<section class="other-courses">
  <div class="container">
    <h2>Other Courses</h2>
    
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

<?php
// Include footer
require_once 'includes/footer.php';
?>