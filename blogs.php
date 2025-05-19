<?php
include('includes/header.php');
include('includes/db_connect.php');

// Initialize variables
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$tag = isset($_GET['tag']) ? trim($_GET['tag']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$limit = 4; // Number of posts per page
$offset = ($page - 1) * $limit;

// Build query conditions
$conditions = ["b.status = 'published'"]; // Specify table alias 'b' for blog_posts
$params = [];

if (!empty($search)) {
    $conditions[] = "(b.title LIKE ? OR b.content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_id > 0) {
    $conditions[] = "b.category_id = ?";
    $params[] = $category_id;
}

if (!empty($tag)) {
    $conditions[] = "b.tags LIKE ?";
    $params[] = "%$tag%";
}

$where_clause = implode(' AND ', $conditions);

// Get total posts count for pagination
$count_sql = "SELECT COUNT(*) FROM blog_posts b WHERE $where_clause";
$stmt = $conn->prepare($count_sql);
$stmt->execute($params);
$total_posts = $stmt->fetchColumn();
$total_pages = ceil($total_posts / $limit);

// Set the ORDER BY clause based on sort parameter
$order_by = "b.created_at DESC"; // Default sorting (newest)
if ($sort == 'oldest') {
    $order_by = "b.created_at ASC";
} elseif ($sort == 'a-z') {
    $order_by = "b.title ASC";
} elseif ($sort == 'z-a') {
    $order_by = "b.title DESC";
}

// Get posts with pagination
$sql = "SELECT b.*, u.username as author_name, c.name as category_name 
        FROM blog_posts b 
        LEFT JOIN users u ON b.author_id = u.id 
        LEFT JOIN blog_categories c ON b.category_id = c.id 
        WHERE $where_clause 
        ORDER BY $order_by 
        LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

// Get categories for sidebar
$stmt = $conn->prepare("
    SELECT c.*, COUNT(b.id) as post_count 
    FROM blog_categories c 
    LEFT JOIN blog_posts b ON c.id = b.category_id AND b.status = 'published' 
    GROUP BY c.id 
    ORDER BY post_count DESC
");
$stmt->execute();
$categories = $stmt->fetchAll();

// Get recent posts for sidebar
$stmt = $conn->prepare("
    SELECT id, title, image_path, created_at 
    FROM blog_posts 
    WHERE status = 'published' 
    ORDER BY created_at DESC 
    LIMIT 3
");
$stmt->execute();
$recent_posts = $stmt->fetchAll();

// Get popular posts (most viewed, if views column exists)
$popular_posts = [];
try {
    $stmt = $conn->prepare("
        SELECT id, title, image_path, created_at, views 
        FROM blog_posts 
        WHERE status = 'published' 
        ORDER BY views DESC 
        LIMIT 3
    ");
    $stmt->execute();
    $popular_posts = $stmt->fetchAll();
} catch (PDOException $e) {
    // If views column doesn't exist, just use recent posts
    $popular_posts = $recent_posts;
}
?>

<!-- Add custom CSS to ensure sidebar displays properly and use orange color scheme -->
<style>
    /* Orange color scheme */
    :root {
        --orange-primary: #ff7700;
        --orange-hover: #e66000;
    }
    
    /* Override Bootstrap primary color with orange */
    .btn-primary, .bg-primary, .badge.bg-primary {
        background-color: var(--orange-primary) !important;
        border-color: var(--orange-primary) !important;
    }
    
    .btn-primary:hover {
        background-color: var(--orange-hover) !important;
        border-color: var(--orange-hover) !important;
    }
    
    .btn-outline-primary {
        color: var(--orange-primary) !important;
        border-color: var(--orange-primary) !important;
    }
    
    .btn-outline-primary:hover {
        background-color: var(--orange-primary) !important;
        color: white !important;
    }
    
    .text-primary {
        color: var(--orange-primary) !important;
    }
    
    /* Ensure sidebar is visible */
    .sidebar {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    /* Style for blog cards */
    .blog-card .card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .blog-card .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
    
    /* Style for recent posts in sidebar */
    .recent-post {
        transition: transform 0.3s ease;
    }
    
    .recent-post:hover {
        transform: translateX(5px);
    }
    
    .recent-post-image img {
        object-fit: cover;
        width: 80px;
        height: 80px;
    }
    
    /* Active category styling */
    .list-group-item.active {
        background-color: var(--orange-primary) !important;
        border-color: var(--orange-primary) !important;
    }
    
    /* Ensure sidebar sticks on large screens */
    @media (min-width: 992px) {
        .sticky-sidebar {
            position: sticky;
            top: 20px;
        }
    }
    
    /* Ensure images in cards have consistent height */
    .blog-card .col-md-4 {
        height: 200px;
        overflow: hidden;
    }
    
    .blog-card .col-md-4 img {
        height: 100%;
        object-fit: cover;
    }
    /* Sidebar Styling */
.sidebar-container {
    padding: 20px;
}

.sidebar-widget {
    margin-bottom: 30px;
    background: #fff;
    border-radius: 5px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.sidebar-widget h3 {
    font-size: 18px;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
    color: #333;
}

.search-widget .input-group {
    position: relative;
}

.search-widget .btn-orange {
    background-color: var(--orange-primary);
    border-color: var(--orange-primary);
    color: white;
}

.search-widget .btn-orange:hover {
    background-color: var(--orange-hover);
    border-color: var(--orange-hover);
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
    margin-bottom: 20px;
    padding-bottom: 20px;
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
    flex-shrink: 0;
}

.post-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 5px;
}

.post-content {
    flex-grow: 1;
}

.post-content h4 {
    font-size: 14px;
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

.post-date {
    font-size: 12px;
    color: #888;
    margin-bottom: 0;
}
    /* Orange badge for date */
    .date-badge {
        background-color: var(--orange-primary);
        color: white;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 12px;
        display: inline-block;
    }
    
    /* Force sidebar to be visible */
    #sidebar-container {
        display: block !important;
    }
    
    /* Newer posts styling */
    .newer-posts-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 15px;
    }
    
    .newer-post-item {
        display: flex;
        margin-bottom: 15px;
    }
    
    .newer-post-image {
        width: 80px;
        height: 80px;
        margin-right: 10px;
    }
    
    .newer-post-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 4px;
    }
    
    .newer-post-content h6 {
        font-size: 14px;
        margin-bottom: 5px;
    }
    
    .newer-post-content small {
        font-size: 12px;
        color: #6c757d;
    }
</style>

<!-- Blog Header Banner (for blogs.php) -->
<section class="page-header" style="background-image: url('images/image0.png'); background-size: cover; background-position: center; position: relative; padding: 20vh 0;">
    <!-- Overlay -->
    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6);"></div>
    <div class="container position-relative">
        <div class="row">
            <div class="col-12 text-center">
                <h1 class="text-white">BLOGS</h1>
            </div>
        </div>
    </div>
</section>

<!-- Blog Content Section -->
<section class="blog-content py-5">
    <div class="container">
        <div class="row">
            <!-- Blog Posts -->
            <div class="col-lg-8">
                <!-- Search Form for Mobile -->
                <div class="d-block d-lg-none mb-4">
                    <div class="card">
                        <div class="card-body">
                            <form action="blogs.php" method="get">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="Search blogs..." value="<?php echo htmlspecialchars($search); ?>">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Sort Options -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0">All Blog Posts</h4>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-sort"></i> Sort By
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="sortDropdown">
                            <li><a class="dropdown-item <?php echo empty($_GET['sort']) || $_GET['sort'] == 'newest' ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'newest'])); ?>">Newest First</a></li>
                            <li><a class="dropdown-item <?php echo isset($_GET['sort']) && $_GET['sort'] == 'oldest' ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'oldest'])); ?>">Oldest First</a></li>
                            <li><a class="dropdown-item <?php echo isset($_GET['sort']) && $_GET['sort'] == 'a-z' ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'a-z'])); ?>">A-Z</a></li>
                            <li><a class="dropdown-item <?php echo isset($_GET['sort']) && $_GET['sort'] == 'z-a' ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'z-a'])); ?>">Z-A</a></li>
                        </ul>
                    </div>
                </div>
                
                <?php if (empty($posts)): ?>
                    <div class="alert alert-info">
                        No blog posts found. Please try a different search or browse our categories.
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): 
                        // Format the date
                        $date = new DateTime($post['created_at']);
                        $formatted_date = $date->format('d M Y');
                        
                        // Calculate read time (approx 200 words per minute)
                        $word_count = str_word_count(strip_tags($post['content']));
                        $read_time = max(1, ceil($word_count / 200));
                        
                        // Get excerpt
                        $excerpt = !empty($post['excerpt']) ? $post['excerpt'] : substr(strip_tags($post['content']), 0, 150) . '...';
                    ?>
                        <div class="blog-card mb-4">
                            <div class="card shadow-sm">
                                <div class="row g-0">
                                    <div class="col-md-4">
                                        <a href="blog-post.php?id=<?php echo $post['id']; ?>">
                                            <?php if (!empty($post['image_path'])): ?>
                                                <img src="images/<?php echo htmlspecialchars($post['image_path']); ?>" class="img-fluid rounded-start h-100 w-100" alt="<?php echo htmlspecialchars($post['title']); ?>" style="object-fit: cover;">
                                            <?php else: ?>
                                                <img src="https://via.placeholder.com/350x250?text=Blog+Image" class="img-fluid rounded-start h-100 w-100" alt="<?php echo htmlspecialchars($post['title']); ?>" style="object-fit: cover;">
                                            <?php endif; ?>
                                        </a>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card-body">
                                            <div class="blog-meta mb-2">
                                                <span class="date-badge me-2"><?php echo $formatted_date; ?></span>
                                                <span class="text-muted">By <?php echo htmlspecialchars($post['author_name'] ?? 'Admin'); ?> | </span>
                                                <span class="text-muted"><?php echo $read_time; ?> Min Read</span>
                                                <?php if (isset($post['views'])): ?>
                                                <span class="text-muted"> | <?php echo $post['views']; ?> Views</span>
                                                <?php endif; ?>
                                            </div>
                                            <h5 class="card-title">
                                                <a href="blog-post.php?id=<?php echo $post['id']; ?>" class="text-decoration-none text-dark">
                                                    <?php echo htmlspecialchars($post['title']); ?>
                                                </a>
                                            </h5>
                                            <p class="card-text"><?php echo htmlspecialchars($excerpt); ?></p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <a href="blog-post.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-primary">Read More</a>
                                                <?php if (!empty($post['category_name'])): ?>
                                                <a href="blogs.php?category=<?php echo $post['category_id']; ?>" class="text-decoration-none">
                                                    <span class="badge bg-light"><?php echo htmlspecialchars($post['category_name']); ?></span>
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Pagination -->
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_id > 0 ? '&category=' . $category_id : ''; ?><?php echo !empty($tag) ? '&tag=' . urlencode($tag) : ''; ?><?php echo !empty($sort) ? '&sort=' . urlencode($sort) : ''; ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php 
                            // Show limited page numbers with ellipsis
                            $start_page = max(1, min($page - 2, $total_pages - 4));
                            $end_page = min($total_pages, max($page + 2, 5));
                            
                            if ($start_page > 1): ?>
                                <li class="page-item"><a class="page-link" href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_id > 0 ? '&category=' . $category_id : ''; ?><?php echo !empty($tag) ? '&tag=' . urlencode($tag) : ''; ?><?php echo !empty($sort) ? '&sort=' . urlencode($sort) : ''; ?>">1</a></li>
                                <?php if ($start_page > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_id > 0 ? '&category=' . $category_id : ''; ?><?php echo !empty($tag) ? '&tag=' . urlencode($tag) : ''; ?><?php echo !empty($sort) ? '&sort=' . urlencode($sort) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($end_page < $total_pages): ?>
                                <?php if ($end_page < $total_pages - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                                <li class="page-item"><a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_id > 0 ? '&category=' . $category_id : ''; ?><?php echo !empty($tag) ? '&tag=' . urlencode($tag) : ''; ?><?php echo !empty($sort) ? '&sort=' . urlencode($sort) : ''; ?>"><?php echo $total_pages; ?></a></li>
                            <?php endif; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_id > 0 ? '&category=' . $category_id : ''; ?><?php echo !empty($tag) ? '&tag=' . urlencode($tag) : ''; ?><?php echo !empty($sort) ? '&sort=' . urlencode($sort) : ''; ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
           <!-- Sidebar -->
<div class="col-lg-4">
  <div class="sidebar-container">
    <!-- Search Widget -->
    <div class="sidebar-widget search-widget">
      <h3>Search</h3>
      <form action="blogs.php" method="GET">
        <div class="input-group">
          <input type="text" class="form-control" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
          <div class="input-group-append">
            <button class="btn btn-orange" type="submit">
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
        <li><a href="blogs.php" class="<?php echo empty($category_id) ? 'active' : ''; ?>">All Blogs</a></li>
        <?php if (!empty($categories)): ?>
          <?php foreach ($categories as $category): ?>
            <li>
              <a href="blogs.php?category=<?php echo $category['id']; ?>" class="<?php echo $category_id == $category['id'] ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($category['name']); ?>
              </a>
            </li>
          <?php endforeach; ?>
        <?php else: ?>
          <li><a href="#">Education</a></li>
          <li><a href="#">Technology</a></li>
          <li><a href="#">Career Development</a></li>
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
            <h4><a href="#">How AI & VR Changing The Web App Industry</a></h4>
            <p class="post-date">Lorem ipsum dolor sit amet, consectetur adipiscing elit</p>
          </div>
        </div>
        <div class="recent-post">
          <div class="post-image">
            <img src="https://via.placeholder.com/80x80?text=AI+VR" alt="AI & VR">
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
<!-- Make sure the sidebar is visible with JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Force sidebar to be visible
    const sidebarContainer = document.getElementById('sidebar-container');
    if (sidebarContainer) {
        sidebarContainer.style.display = 'block';
        sidebarContainer.style.visibility = 'visible';
        sidebarContainer.style.opacity = '1';
    }
    
    // Force all sidebar elements to be visible
    const sidebarElements = document.querySelectorAll('.sidebar, .sidebar *');
    sidebarElements.forEach(function(element) {
        element.style.display = element.tagName === 'SPAN' ? 'inline-block' : 'block';
        element.style.visibility = 'visible';
        element.style.opacity = '1';
    });
    
    // Add active class to current category
    const categoryId = <?php echo $category_id ?: 'null'; ?>;
    if (categoryId) {
        const categoryItem = document.querySelector(`.list-group-item a[href="blogs.php?category=${categoryId}"]`);
        if (categoryItem) {
            categoryItem.closest('.list-group-item').classList.add('active');
            categoryItem.classList.add('text-white');
        }
    }
});
</script>

<br>
            </div>
        </div>
    </div>
</section>
<?php include('includes/footer.php'); ?>