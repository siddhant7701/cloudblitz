<?php
// Include database connection
require_once 'includes/db_connect.php';

// Set page specific CSS
$page_specific_css = 'css/courses.css';
$page_title = 'Courses';

// Include header
require_once 'includes/header.php';

// Get all published courses
$stmt = $conn->prepare("SELECT c.*, cat.name as category_name, i.name as instructor_name 
                       FROM courses c 
                       LEFT JOIN course_categories cat ON c.category_id = cat.id
                       LEFT JOIN instructors i ON c.instructor_id = i.id
                       WHERE c.status = 'published'
                       ORDER BY c.created_at DESC");
$stmt->execute();
$courses = $stmt->fetchAll();

// Get course categories
$stmt = $conn->prepare("SELECT * FROM course_categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();

// Get recent blog posts for sidebar
$stmt = $conn->prepare("SELECT * FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC LIMIT 3");
$stmt->execute();
$recentPosts = $stmt->fetchAll();
?>

<!-- Internal CSS for course page -->
<style>
    /* Page Header */
    .page-header {
        background-color: #f8f9fa;
        padding: 60px 0;
        text-align: center;
        margin-bottom: 50px;
    }
    
    .page-header h1 {
        font-size: 36px;
        font-weight: 700;
        color: #333;
        margin: 0;
        text-transform: uppercase;
    }
    
    /* Course Card */
    .course-card {
        border-radius: 5px;
        overflow: hidden;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        background-color: #fff;
        margin-bottom: 20px;
    }
    
    .course-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .course-image {
        position: relative;
        overflow: hidden;
        height: 200px;
    }
    
    .course-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    
    .course-card:hover .course-image img {
        transform: scale(1.05);
    }
    
    .course-content {
        padding: 15px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    
    .course-content h3 {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 10px;
        line-height: 1.4;
        color: #333;
    }
    
    .course-meta {
        margin-bottom: 15px;
    }
    
    .course-meta .badge {
        background-color: #f0f0f0;
        color: #666;
        font-size: 12px;
        padding: 5px 10px;
        border-radius: 4px;
        font-weight: 500;
    }
    
    .course-content p {
        font-size: 14px;
        color: #666;
        margin-bottom: 15px;
        flex-grow: 1;
    }
    
    .course-actions {
        display: flex;
        justify-content: space-between;
        margin-top: auto;
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
        background-color:rgb(254, 59, 0);
        color:rgb(255, 255, 255);
    }
    
    /* Sidebar */
    .course-sidebar {
        background-color: #f9f9f9;
        border-radius: 5px;
        padding: 20px;
        margin-bottom: 30px;
    }
    
    .sidebar-widget {
        margin-bottom: 30px;
    }
    
    .sidebar-widget:last-child {
        margin-bottom: 0;
    }
    
    .sidebar-widget h3 {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #ddd;
        color: #333;
    }
    
    .search-widget .input-group {
        position: relative;
    }
    
    .search-widget .form-control {
        height: 45px;
        border-radius: 4px;
        padding-right: 45px;
    }
    
    .search-widget .btn {
        position: absolute;
        right: 0;
        top: 0;
        height: 45px;
        width: 45px;
        background-color: #ff5722;
        color: #fff;
        border: none;
        z-index: 10;
    }
    
    .categories-widget ul li {
        margin-bottom: 10px;
    }
    
    .categories-widget ul li a {
        color: #666;
        text-decoration: none;
        transition: color 0.3s ease;
        display: block;
        padding: 5px 0;
    }
    
    .categories-widget ul li a:hover,
    .categories-widget ul li a.active {
        color: #ff8a65;
    }
    
    .recent-post {
        display: flex;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }
    
    .recent-post:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }
    
    .post-image {
        width: 80px;
        height: 80px;
        margin-right: 15px;
        border-radius: 4px;
        overflow: hidden;
        flex-shrink: 0;
    }
    
    .post-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .post-content {
        flex-grow: 1;
    }
    
    .post-content h4 {
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 5px;
        line-height: 1.4;
    }
    
    .post-content h4 a {
        color: #333;
        text-decoration: none;
        transition: color 0.3s ease;
    }
    
    .post-content h4 a:hover {
        color: #ff8a65;
    }
    
    .post-content .post-date {
        font-size: 12px;
        color: #999;
        margin: 0;
    }
    
    /* Pagination */
    .pagination-container {
        margin-top: 40px;
    }
    
    .pagination .page-link {
        color: #333;
        border-color: #ddd;
        margin: 0 3px;
        border-radius: 4px;
    }
    
    .pagination .page-item.active .page-link {
        background-color: #ff8a65;
        border-color: #ff5722;
    }
    
    /* Responsive Styles */
    @media (max-width: 991px) {
        .course-sidebar {
            margin-top: 40px;
        }
    }
    
    @media (max-width: 767px) {
        .page-header {
            padding: 40px 0;
        }
        
        .page-header h1 {
            font-size: 28px;
        }
        
        .course-card {
            margin-bottom: 20px;
        }
    }
    
    @media (max-width: 575px) {
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

<!-- Page Header -->
<section class="page-header">
  <div class="container">
    <h1>COURSES</h1>
  </div>
</section>

<!-- Courses Content Section -->
<section class="courses-content">
  <div class="container">
    <div class="row">
      <!-- Course Cards -->
      <div class="col-lg-9">
        <div class="row">
          <?php if (count($courses) > 0): ?>
            <?php foreach ($courses as $course): ?>
              <div class="col-md-6 col-lg-4">
                <div class="course-card">
                  <div class="course-image">
                    <img src="<?php echo !empty($course['image_path']) ? htmlspecialchars($course['image_path']) : 'https://via.placeholder.com/400x250?text=Course+Image'; ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                  </div>
                  <div class="course-content">
                    <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                    <div class="course-meta">
                      <span class="duration">
                        <span class="badge">
                          <?php echo htmlspecialchars($course['duration']); ?>, 
                          <?php echo $course['level'] ? htmlspecialchars(ucfirst($course['level'])) : 'All Levels'; ?>
                        </span>
                      </span>
                    </div>
                    <p><?php echo htmlspecialchars(substr(strip_tags($course['short_description'] ?? $course['description']), 0, 80)) . '...'; ?></p>
                    <div class="course-actions">
                      <?php if (!empty($course['brochure_path'])): ?>
                        <a href="<?php echo htmlspecialchars($course['brochure_path']); ?>" class="btn-brochure" download><i class="fas fa-download"></i> BROCHURE</a>
                      <?php else: ?>
                        <a href="#" class="btn-brochure" onclick="alert('Brochure coming soon!'); return false;"><i class="fas fa-download"></i> BROCHURE</a>
                      <?php endif; ?>
                      <a href="course.php?id=<?php echo $course['id']; ?>" class="btn-view-program">VIEW PROGRAM</a>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="col-12">
              <div class="alert alert-info">
                No courses available at the moment. Please check back later.
              </div>
            </div>
          <?php endif; ?>
        </div>
        
        <!-- Pagination - Only show if needed -->
        <?php if (count($courses) > 12): ?>
        <div class="pagination-container">
          <nav aria-label="Courses pagination">
            <ul class="pagination justify-content-center">
              <li class="page-item disabled">
                <a class="page-link" href="#" aria-label="Previous">
                  <span aria-hidden="true">&laquo;</span>
                </a>
              </li>
              <li class="page-item active"><a class="page-link" href="#">1</a></li>
              <li class="page-item"><a class="page-link" href="#">2</a></li>
              <li class="page-item"><a class="page-link" href="#">3</a></li>
              <li class="page-item">
                <a class="page-link" href="#" aria-label="Next">
                  <span aria-hidden="true">&raquo;</span>
                </a>
              </li>
            </ul>
          </nav>
        </div>
        <?php endif; ?>
      </div>
      
      <!-- Sidebar -->
      <div class="col-lg-3">
        <div class="course-sidebar">
          <!-- Search Widget -->
          <div class="sidebar-widget search-widget">
            <h3>Search</h3>
            <form action="search-courses.php" method="GET">
              <div class="input-group">
                <input type="text" class="form-control" name="q" placeholder="Search...">
                <div class="input-group-append">
                  <button class="btn" type="submit">
                    <i class="fa fa-search"></i>
                  </button>
                </div>
              </div>
            </form>
          </div>
          
          <!-- Categories Widget -->
          <div class="sidebar-widget categories-widget">
            <h3>Popular Category</h3>
            <ul class="list-unstyled">
              <li><a href="courses.php" class="active">All Courses</a></li>
              <?php foreach ($categories as $category): ?>
                <li>
                  <a href="courses.php?category=<?php echo $category['id']; ?>">
                    <?php echo htmlspecialchars($category['name']); ?>
                  </a>
                </li>
              <?php endforeach; ?>
              <?php if (count($categories) < 1): ?>
                <li><a href="#">Learning</a></li>
                <li><a href="#">Technology</a></li>
                <li><a href="#">Careers</a></li>
                <li><a href="#">Insights</a></li>
              <?php endif; ?>
            </ul>
          </div>
          
          <!-- Recent Posts Widget -->
          <div class="sidebar-widget recent-posts-widget">
            <h3>Newer Posts</h3>
            <?php if (count($recentPosts) > 0): ?>
              <?php foreach ($recentPosts as $post): ?>
                <div class="recent-post">
                  <div class="post-image">
                    <img src="<?php echo !empty($post['image_path']) ? htmlspecialchars($post['image_path']) : 'https://via.placeholder.com/80x80?text=Blog+Post'; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                  </div>
                  <div class="post-content">
                    <h4>
                      <a href="blog-post.php?id=<?php echo $post['id']; ?>">
                        <?php echo htmlspecialchars($post['title']); ?>
                      </a>
                    </h4>
                    <p class="post-date">
                      <?php echo date('F j, Y', strtotime($post['created_at'])); ?>
                    </p>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="recent-post">
                <div class="post-image">
                  <img src="images/ai-vr.jpg" alt="AI & VR" onerror="this.src='https://via.placeholder.com/80x80?text=AI+VR'">
                </div>
                <div class="post-content">
                  <h4><a href="#">How AI & VR Changing The Web App Industry</a></h4>
                  <p class="post-date">Lorem ipsum dolor sit amet, consectetur adipiscing elit</p>
                </div>
              </div>
              <div class="recent-post">
                <div class="post-image">
                  <img src="images/ai-vr.jpg" alt="AI & VR" onerror="this.src='https://via.placeholder.com/80x80?text=AI+VR'">
                </div>
                <div class="post-content">
                  <h4><a href="#">How AI & VR Changing The Web App Industry</a></h4>
                  <p class="post-date">Lorem ipsum dolor sit amet, consectetur adipiscing elit</p>
                </div>
              </div>
              <div class="recent-post">
                <div class="post-image">
                  <img src="images/ai-vr.jpg" alt="AI & VR" onerror="this.src='https://via.placeholder.com/80x80?text=AI+VR'">
                </div>
                <div class="post-content">
                  <h4><a href="#">How AI & VR Changing The Web App Industry</a></h4>
                  <p class="post-date">Lorem ipsum dolor sit amet, consectetur adipiscing elit</p>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php
// Include footer
require_once 'includes/footer.php';
?>