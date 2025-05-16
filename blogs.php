<?php
// Include database connection
require_once 'includes/db_connect.php';

// Set page specific CSS
$page_specific_css = 'css/blogs.css';
$page_title = 'Blog';

// Include header
require_once 'includes/header.php';

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 4;
$offset = ($page - 1) * $perPage;

// Get total number of blog posts
$stmt = $conn->prepare("SELECT COUNT(*) FROM blog_posts");
$stmt->execute();
$totalPosts = $stmt->fetchColumn();
$totalPages = ceil($totalPosts / $perPage);

// Get blog posts for current page
$stmt = $conn->prepare("SELECT p.*, c.name as category_name, a.name as author_name 
                       FROM blog_posts p 
                       LEFT JOIN blog_categories c ON p.category_id = c.id 
                       LEFT JOIN admin_users a ON p.author_id = a.id 
                       ORDER BY p.created_at DESC LIMIT :offset, :perPage");
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':perPage', $perPage, PDO::PARAM_INT);
$stmt->execute();
$blogPosts = $stmt->fetchAll();

// Get blog categories
$stmt = $conn->prepare("SELECT * FROM blog_categories");
$stmt->execute();
$categories = $stmt->fetchAll();
?>

<!-- Page Header -->
<section class="page-header">
  <div class="container">
    <h1>BLOGS</h1>
  </div>
</section>

<!-- Blog Content Section -->
<section class="blog-content">
  <div class="container">
    <div class="row">
      <!-- Blog Posts -->
      <div class="col-lg-8">
        <?php foreach ($blogPosts as $post): ?>
          <div class="blog-card">
            <div class="row">
              <div class="col-md-5">
                <div class="blog-image">
                  <img src="<?php echo !empty($post['image_path']) ? htmlspecialchars($post['image_path']) : 'https://via.placeholder.com/400x300?text=Blog+Image'; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                </div>
              </div>
              <div class="col-md-7">
                <div class="blog-content">
                  <div class="blog-date">
                    <span class="date-badge"><?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                  </div>
                  <h3><a href="blog-post.php?id=<?php echo $post['id']; ?>"><?php echo htmlspecialchars($post['title']); ?></a></h3>
                  <div class="blog-meta">
                    <span class="author">By <?php echo !empty($post['author_name']) ? htmlspecialchars($post['author_name']) : 'Admin'; ?></span>
                    <span class="category"><?php echo !empty($post['category_name']) ? htmlspecialchars($post['category_name']) : 'Uncategorized'; ?></span>
                    <span class="read-time">3 Min Read</span>
                  </div>
                  <p><?php echo htmlspecialchars(substr(strip_tags($post['content']), 0, 150)) . '...'; ?></p>
                  <a href="blog-post.php?id=<?php echo $post['id']; ?>" class="read-more">Read More</a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
        
        <!-- Pagination -->
        <div class="pagination-container">
          <nav aria-label="Blog pagination">
            <ul class="pagination justify-content-center">
              <?php if ($page > 1): ?>
                <li class="page-item">
                  <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                  </a>
                </li>
              <?php endif; ?>
              
              <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                  <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
              <?php endfor; ?>
              
              <?php if ($page < $totalPages): ?>
                <li class="page-item">
                  <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                  </a>
                </li>
              <?php endif; ?>
            </ul>
          </nav>
        </div>
      </div>
      
      <!-- Sidebar -->
      <div class="col-lg-4">
        <div class="blog-sidebar">
          <!-- Search Widget -->
          <div class="sidebar-widget search-widget">
            <h3>Search</h3>
            <form action="search.php" method="GET">
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
              <li><a href="#" class="active">Learning</a></li>
              <li><a href="#">Technology</a></li>
              <li><a href="#">Careers</a></li>
              <li><a href="#">Insights</a></li>
              <li><a href="#">Trends</a></li>
              <li><a href="#">Guides</a></li>
              <li><a href="#">Tools</a></li>
              <li><a href="#">Success</a></li>
            </ul>
          </div>
          
          <!-- Recent Posts Widget -->
          <div class="sidebar-widget recent-posts-widget">
            <h3>Newer Posts</h3>
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
