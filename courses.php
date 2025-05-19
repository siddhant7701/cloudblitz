<?php
// Include database connection
require_once 'includes/db_connect.php';

// Set page specific CSS
$page_specific_css = 'css/courses.css';
$page_title = 'Courses';

// Get query parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 9; // Number of courses per page
$offset = ($page - 1) * $limit;

// Build query conditions
$conditions = ["c.status = 'published'"]; 
$params = [];

if (!empty($search)) {
    $conditions[] = "(c.title LIKE ? OR c.description LIKE ? OR c.short_description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_id > 0) {
    $conditions[] = "c.category_id = ?";
    $params[] = $category_id;
}

$where_clause = implode(' AND ', $conditions);

// Set the ORDER BY clause based on sort parameter
$order_by = "c.created_at DESC"; // Default sorting (newest)
if ($sort == 'oldest') {
    $order_by = "c.created_at ASC";
} elseif ($sort == 'a-z') {
    $order_by = "c.title ASC";
} elseif ($sort == 'z-a') {
    $order_by = "c.title DESC";
} elseif ($sort == 'price-low') {
    $order_by = "c.price ASC";
} elseif ($sort == 'price-high') {
    $order_by = "c.price DESC";
}

// Get total courses count for pagination
$count_sql = "SELECT COUNT(*) FROM courses c WHERE $where_clause";
$stmt = $conn->prepare($count_sql);
$stmt->execute($params);
$total_courses = $stmt->fetchColumn();
$total_pages = ceil($total_courses / $limit);

// Get courses with pagination
$sql = "SELECT c.*, cat.name as category_name, i.name as instructor_name 
        FROM courses c 
        LEFT JOIN course_categories cat ON c.category_id = cat.id
        LEFT JOIN instructors i ON c.instructor_id = i.id
        WHERE $where_clause
        ORDER BY $order_by
        LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll();

// Get course categories
$stmt = $conn->prepare("
    SELECT cc.*, COUNT(c.id) as course_count 
    FROM course_categories cc 
    LEFT JOIN courses c ON cc.id = c.category_id AND c.status = 'published' 
    GROUP BY cc.id 
    ORDER BY course_count DESC
");
$stmt->execute();
$categories = $stmt->fetchAll();

// Get recent blog posts for sidebar
$stmt = $conn->prepare("
    SELECT id, title, image_path, created_at 
    FROM blog_posts 
    WHERE status = 'published' 
    ORDER BY created_at DESC 
    LIMIT 3
");
$stmt->execute();
$recent_posts = $stmt->fetchAll();

// Include header
require_once 'includes/header.php';
?>

<!-- Internal CSS for course page -->
<style>
    /* Orange color scheme */
    :root {
        --orange-primary: #ff7700;
        --orange-hover: #e66000;
    }
    
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
        background-color: var(--orange-primary);
        color: #fff;
    }
    
    .btn-view-program:hover {
        background-color: var(--orange-hover);
        color: #fff;
    }
    
    /* Sort Options */
    .sort-options {
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .sort-options h4 {
        margin-bottom: 0;
    }
    
    /* Sidebar */
    .course-sidebar {
        background-color: #fff;
        border-radius: 5px;
        padding: 20px;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
        border-bottom: 1px solid #eee;
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
        background-color: var(--orange-primary);
        color: #fff;
        border: none;
        z-index: 10;
    }
    
    .search-widget .btn:hover {
        background-color: var(--orange-hover);
    }
    
    .categories-widget ul {
        margin: 0;
        padding: 0;
    }
    
    .categories-widget ul li {
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid #f5f5f5;
    }
    
    .categories-widget ul li:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }
    
    .categories-widget ul li a {
        color: #555;
        text-decoration: none;
        transition: all 0.3s ease;
        display: block;
        padding: 5px 0;
    }
    
    .categories-widget ul li a:hover,
    .categories-widget ul li a.active {
        color: var(--orange-primary);
        padding-left: 5px;
    }
    
    .recent-post {
        display: flex;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #f5f5f5;
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
        color: var(--orange-primary);
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
        background-color: var(--orange-primary);
        border-color: var(--orange-primary);
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
        
        .sort-options {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .sort-options .dropdown {
            margin-top: 10px;
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
<section class="page-header" style="background-image: url('images/image0.png'); background-size: cover; background-position: center; position: relative; padding: 20vh 0;">
    <!-- Overlay -->
    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6);"></div>
    <div class="container position-relative">
        <div class="row">
            <div class="col-12 text-center">
                <h1 class="text-white">Courses</h1>
            </div>
        </div>
    </div>
</section>

<!-- Courses Content Section -->
<section class="courses-content">
  <div class="container">
    <div class="row">
      <!-- Course Cards -->
      <div class="col-lg-9">
        <!-- Sort Options -->
        <div class="sort-options">
          <h4>All Courses</h4>
          <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fas fa-sort"></i> Sort By
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="sortDropdown">
              <li><a class="dropdown-item <?php echo empty($_GET['sort']) || $_GET['sort'] == 'newest' ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'newest'])); ?>">Newest First</a></li>
              <li><a class="dropdown-item <?php echo isset($_GET['sort']) && $_GET['sort'] == 'oldest' ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'oldest'])); ?>">Oldest First</a></li>
              <li><a class="dropdown-item <?php echo isset($_GET['sort']) && $_GET['sort'] == 'a-z' ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'a-z'])); ?>">A-Z</a></li>
              <li><a class="dropdown-item <?php echo isset($_GET['sort']) && $_GET['sort'] == 'z-a' ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'z-a'])); ?>">Z-A</a></li>
              <li><a class="dropdown-item <?php echo isset($_GET['sort']) && $_GET['sort'] == 'price-low' ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price-low'])); ?>">Price: Low to High</a></li>
              <li><a class="dropdown-item <?php echo isset($_GET['sort']) && $_GET['sort'] == 'price-high' ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price-high'])); ?>">Price: High to Low</a></li>
            </ul>
          </div>
        </div>
        
        <!-- Search Results Info -->
        <?php if (!empty($search) || $category_id > 0): ?>
          <div class="alert alert-info mb-4">
            <?php if (!empty($search)): ?>
              Showing results for: <strong><?php echo htmlspecialchars($search); ?></strong>
            <?php endif; ?>
            
            <?php if ($category_id > 0): 
              $category_name = '';
              foreach ($categories as $cat) {
                if ($cat['id'] == $category_id) {
                  $category_name = $cat['name'];
                  break;
                }
              }
            ?>
              <?php if (!empty($search)): ?> in <?php endif; ?>
              Category: <strong><?php echo htmlspecialchars($category_name); ?></strong>
            <?php endif; ?>
            
            <a href="courses.php" class="float-end">Clear filters</a>
          </div>
        <?php endif; ?>
        
        <div class="row">
          <?php if (count($courses) > 0): ?>
            <?php foreach ($courses as $course): ?>
              <div class="col-md-6 col-lg-4 mb-4">
                <div class="course-card">
                  <div class="course-image">
                    <img src="<?php echo !empty($course['image_path']) ? htmlspecialchars($course['image_path']) : 'https://via.placeholder.com/400x250?text=Course+Image'; ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                  </div>
                  <div class="course-content">
                    <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                    <div class="course-meta">
                      <span class="duration">
                        <span class="badge">
                          <?php echo htmlspecialchars($course['duration'] ?? 'N/A'); ?>, 
                          <?php echo $course['level'] ? htmlspecialchars(ucfirst($course['level'])) : 'All Levels'; ?>
                        </span>
                      </span>
                    </div>
                    <p><?php echo htmlspecialchars(substr(strip_tags($course['short_description'] ?? $course['description'] ?? 'No description available.'), 0, 80)) . '...'; ?></p>
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
                No courses available matching your criteria. Please try different search terms or browse all courses.
              </div>
            </div>
          <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination-container">
          <nav aria-label="Courses pagination">
            <ul class="pagination justify-content-center">
              <?php if ($page > 1): ?>
                <li class="page-item">
                  <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_id > 0 ? '&category=' . $category_id : ''; ?><?php echo !empty($sort) ? '&sort=' . urlencode($sort) : ''; ?>" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                  </a>
                </li>
              <?php else: ?>
                <li class="page-item disabled">
                  <a class="page-link" href="#" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                  </a>
                </li>
              <?php endif; ?>
              
              <?php 
              // Show limited page numbers with ellipsis
              $start_page = max(1, min($page - 2, $total_pages - 4));
              $end_page = min($total_pages, max($page + 2, 5));
              
              if ($start_page > 1): ?>
                <li class="page-item"><a class="page-link" href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_id > 0 ? '&category=' . $category_id : ''; ?><?php echo !empty($sort) ? '&sort=' . urlencode($sort) : ''; ?>">1</a></li>
                <?php if ($start_page > 2): ?>
                  <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
              <?php endif; ?>
              
              <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                  <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_id > 0 ? '&category=' . $category_id : ''; ?><?php echo !empty($sort) ? '&sort=' . urlencode($sort) : ''; ?>">
                    <?php echo $i; ?>
                  </a>
                </li>
              <?php endfor; ?>
              
              <?php if ($end_page < $total_pages): ?>
                <?php if ($end_page < $total_pages - 1): ?>
                  <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
                <li class="page-item"><a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_id > 0 ? '&category=' . $category_id : ''; ?><?php echo !empty($sort) ? '&sort=' . urlencode($sort) : ''; ?>"><?php echo $total_pages; ?></a></li>
              <?php endif; ?>
              
              <?php if ($page < $total_pages): ?>
                <li class="page-item">
                  <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_id > 0 ? '&category=' . $category_id : ''; ?><?php echo !empty($sort) ? '&sort=' . urlencode($sort) : ''; ?>" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                  </a>
                </li>
              <?php else: ?>
                <li class="page-item disabled">
                  <a class="page-link" href="#" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                  </a>
                </li>
              <?php endif; ?>
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
            <form action="courses.php" method="GET">
              <div class="input-group">
                <input type="text" class="form-control" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
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
              <li><a href="courses.php" class="<?php echo empty($category_id) ? 'active' : ''; ?>">All Courses</a></li>
              <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $category): ?>
                  <li>
                    <a href="courses.php?category=<?php echo $category['id']; ?>" class="<?php echo $category_id == $category['id'] ? 'active' : ''; ?>">
                      <?php echo htmlspecialchars($category['name']); ?>
                      <?php if (isset($category['course_count']) && $category['course_count'] > 0): ?>
                        <span class="float-end">(<?php echo $category['course_count']; ?>)</span>
                      <?php endif; ?>
                    </a>
                  </li>
                <?php endforeach; ?>
              <?php else: ?>
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
            <?php if (!empty($recent_posts)): ?>
              <?php foreach ($recent_posts as $post): ?>
                <div class="recent-post">
                  <div class="post-image">
                    <?php if (!empty($post['image_path'])): ?>
                      <img src="images/<?php echo htmlspecialchars($post['image_path']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                    <?php else: ?>
                      <img src="https://via.placeholder.com/80x80?text=Blog+Post" alt="<?php echo htmlspecialchars($post['title']); ?>">
                    <?php endif; ?>
                  </div>
                  <div class="post-content">
                    <h4>
                      <a href="blog-post.php?id=<?php echo $post['id']; ?>">
                        <?php echo htmlspecialchars($post['title']); ?>
                      </a>
                    </h4>
                    <p class="post-date">
                      <?php 
                        $date = new DateTime($post['created_at']);
                        echo $date->format('d M Y'); 
                      ?>
                    </p>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="recent-post">
                <div class="post-image">
                  <img src="https://via.placeholder.com/80x80?text=AI+VR" alt="AI & VR">
                </div>
                <div class="post-content">
                  <h4><a href="#">How AI & VR Changing The Web App Industry</a></h4>
                  <p class="post-date">Lorem ipsum dolor sit amet, consectetur adipiscing elit</p>
                </div>
              </div>
              <div class="recent-post">
                <div class="post-image">
                  <img src="https://via.placeholder.com/80x80?text=AI+VR" alt="AI & VR">
                </div>
                <div class="post-content">
                  <h4><a href="#">How AI & VR Changing The Mobile App Industry</a></h4>
                  <p class="post-date">Lorem ipsum dolor sit amet, consectetur adipiscing elit</p>
                </div>
              </div>
              <div class="recent-post">
                <div class="post-image">
                  <img src="https://via.placeholder.com/80x80?text=AI+VR" alt="AI & VR">
                </div>
                <div class="post-content">
                  <h4><a href="#">How AI & VR Changing The Desktop App Industry</a></h4>
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

<!-- Make sure the sidebar is visible with JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add active class to current category
    const categoryId = <?php echo $category_id ?: 'null'; ?>;
    if (categoryId) {
        const categoryItem = document.querySelector(`.categories-widget a[href="courses.php?category=${categoryId}"]`);
        if (categoryItem) {
            categoryItem.classList.add('active');
        }
    }
});
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>