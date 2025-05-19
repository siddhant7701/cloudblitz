<?php
// Include database connection
require_once 'includes/db_connect.php';

// Set page specific CSS
$page_specific_css = 'css/home.css';
$page_title = 'Home';

// Include header
require_once 'includes/header.php';

// Get featured courses
$stmt = $conn->prepare("SELECT * FROM courses ORDER BY created_at DESC LIMIT 3");
$stmt->execute();
$featuredCourses = $stmt->fetchAll();

// Get recent blog posts
$stmt = $conn->prepare("SELECT * FROM blog_posts ORDER BY created_at DESC LIMIT 3");
$stmt->execute();
$recentPosts = $stmt->fetchAll();
?>

<!-- Hero Section -->
<section class="hero-section">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-6">
        <div class="hero-content">
          <h1>Unlock Your Potential with <span>Cutting-Edge Tech</span> Skills!</h1>
          <p>Learn from industry experts and advance your career with our comprehensive courses designed to help you master the latest technologies.</p>
          
          <div class="hero-stats">
            <div class="stat-item">
              <span class="stat-number">75+</span>
              <span class="stat-label">Expert Instructors</span>
            </div>
            <div class="stat-item">
              <span class="stat-number">8000+</span>
              <span class="stat-label">Students Enrolled</span>
            </div>
            <div class="stat-item">
              <span class="stat-number">95%</span>
              <span class="stat-label">Success Rate</span>
            </div>
          </div>
          
          <div class="hero-cta">
            <a href="courses.php" class="btn btn-primary">Explore Courses</a>
            <a href="contact.php" class="btn btn-outline">Contact Us</a>
          </div>
          
            <div class="partner-logo" style="width: 100%;"></div>
            <div class="partner-content" style="position: relative; display: flex; align-items: right; justify-content: right; margin: 20px 0;">
              <div style="width: 0; height: 0; border-top: 25px solid transparent; border-bottom: 25px solid transparent; border-right: 25px solid #ff6b00;"></div>
              <div style="display: flex; align-items: center; background: #ff6b00; padding: 10px 30px; border-radius: 0 5px 5px 0;">
              <span class="partner-text" style="color: white; margin-right: 10px;">We are official partners with</span>
              <img src="images/company-logo0.svg" alt="Microsoft" style="height: 30px;" onerror="this.src='https://via.placeholder.com/120x30?text=Microsoft'">
              </div>
            </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="hero-image">
          <img src="images/hero.png"  style="width: 109%; height: 200%;"alt="Student learning online" onerror="this.src='https://via.placeholder.com/600x400?text=Student+Learning'">
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Programs Section -->
<section class="programs-section">
  <div class="container">
    <h2 class="section-title">Our Programs</h2>
    <p class="section-subtitle">Discover our wide range of courses designed to help you achieve your career goals</p>
    
    
    
    <div class="row">
      <?php foreach ($featuredCourses as $course): ?>
        <div class="col-md-4 mb-4">
          <div class="program-card">
            <img src="<?php echo !empty($course['image_path']) ? htmlspecialchars($course['image_path']) : 'https://via.placeholder.com/400x200?text=Course+Image'; ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
            <div class="program-content">
              <h3><?php echo htmlspecialchars($course['title']); ?></h3>
              <p><?php echo htmlspecialchars(substr(strip_tags($course['description']), 0, 100)) . '...'; ?></p>
              <div class="program-meta">
                <span class="program-price"><?php echo htmlspecialchars($course['price']); ?></span>
                <a href="course.php?id=<?php echo $course['id']; ?>" class="btn btn-primary">Learn More</a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    
    <div class="text-center mt-5">
      <a href="courses.php" class="btn btn-outline">View All Courses</a>
    </div>
  </div>
</section>

<!-- Why Choose Us Section -->
<section class="why-choose-section">
  <div class="container">
    <h2 class="section-title">Why Choose CloudBlitz?</h2>
    <p class="section-subtitle">We bridge the gap between education and employment with practical skills, real projects, and career-focused learning</p>
    
    <div class="row">
      <div class="col-md-4">
        <div class="feature-box">
          <div class="feature-icon">
            <i class="fas fa-chalkboard-teacher"></i>
          </div>
          <h3 class="feature-title">Expert-Led Training</h3>
          <p class="feature-text">Learn from experienced professionals with strong field and industry insights.</p>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="feature-box">
          <div class="feature-icon">
            <i class="fas fa-laptop-code"></i>
          </div>
          <h3 class="feature-title">100% Live Online Classes</h3>
          <p class="feature-text">Interactive learning model enables students to choose between video lectures and in-person engagement.</p>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="feature-box">
          <div class="feature-icon">
            <i class="fas fa-project-diagram"></i>
          </div>
          <h3 class="feature-title">Hands-On Projects</h3>
          <p class="feature-text">Gain practical experience by working on real-world projects and case studies.</p>
          <ul class="feature-list">
            <li><i class="fas fa-check"></i> Implement multiple projects</li>
            <li><i class="fas fa-check"></i> Hands-On Real Experience</li>
          </ul>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="feature-box">
          <div class="feature-icon">
            <i class="fas fa-graduation-cap"></i>
          </div>
          <h3 class="feature-title">Career-Focused Curriculum</h3>
          <p class="feature-text">Stay updated with the latest tools, trends and technologies.</p>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="feature-box">
          <div class="feature-icon">
            <i class="fas fa-users"></i>
          </div>
          <h3 class="feature-title">Supportive Learning Environment</h3>
          <p class="feature-text">Be part of a community that motivates, inspires and moves you forward.</p>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="feature-box">
          <div class="feature-icon">
            <i class="fas fa-certificate"></i>
          </div>
          <h3 class="feature-title">Industry Recognized Certification</h3>
          <p class="feature-text">Receive certificates that are valued by top employers in the industry.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Student Map Section -->
<section class="student-map-section">
  <div class="container text-center">
    <h2 class="section-title">Join More Than 8000+ Students All Over India</h2>
    
    <div class="map-container">
      <img src="images/map.png" alt="Map of India" class="map-image" onerror="this.src='https://via.placeholder.com/800x500?text=Map+of+India'">
      
      
      <!-- Map points - adjust positions as needed -->
      <!-- <div class="map-point" style="top: 25%; left: 30%;" title="Delhi"></div>
      <div class="map-point" style="top: 40%; left: 20%;" title="Mumbai"></div>
      <div class="map-point" style="top: 60%; left: 30%;" title="Bangalore"></div>
      <div class="map-point" style="top: 45%; left: 45%;" title="Hyderabad"></div>
      <div class="map-point" style="top: 35%; left: 70%;" title="Kolkata"></div> -->
    </div>
  </div>
</section>

<!-- Recent Blog Posts Section -->
<!-- <section class="recent-posts">
  <div class="container">
    <h2 class="section-title">Recent Blog Posts</h2>
    <p class="section-subtitle">Latest news, updates, and insights from our team</p>
    
    <div class="row">
      <?php foreach ($recentPosts as $post): ?>
        <div class="col-md-4">
          <div class="program-card">
            <img src="<?php echo !empty($post['image_path']) ? htmlspecialchars($post['image_path']) : 'https://via.placeholder.com/400x200?text=Blog+Image'; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
            <div class="program-content">
              <h3><?php echo htmlspecialchars($post['title']); ?></h3>
              <p class="blog-date"><?php echo date('F j, Y', strtotime($post['created_at'])); ?></p>
              <p><?php echo htmlspecialchars(substr(strip_tags($post['content']), 0, 100)) . '...'; ?></p>
              <a href="blog-post.php?id=<?php echo $post['id']; ?>" class="btn btn-outline">Read More</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    
    <div class="text-center mt-5">
      <a href="blogs.php" class="btn btn-outline">View All Posts</a>
    </div>
  </div>
</section> -->

<!-- Call to Action Section -->
<!-- <section class="cta-section">
  <div class="container text-center">
    <h2>Ready to Start Learning?</h2>
    <p>Join thousands of students who are already advancing their careers.</p>
    <a href="register.php" class="btn btn-primary">Get Started Today</a>
  </div>
</section> -->

<?php
// Include footer
require_once 'includes/footer.php';
?>