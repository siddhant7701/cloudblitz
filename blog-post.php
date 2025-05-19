<?php
$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

?>
<style>
    :root {
        --orange-primary: #ff7700;
        --orange-hover: #e66000;
    }
    
    .btn-warning, .bg-warning {
        background-color: var(--orange-primary) !important;
        border-color: var(--orange-primary) !important;
    }
    
    .btn-warning:hover {
        background-color: var(--orange-hover) !important;
        border-color: var(--orange-hover) !important;
    }
    
    .btn-outline-warning {
        color: var(--orange-primary);
        border-color: var(--orange-primary);
    }
    
    .btn-outline-warning:hover {
        background-color: var(--orange-primary);
        border-color: var(--orange-primary);
        color: white;
    }
    
    .text-warning {
        color: var(--orange-primary) !important;
    }
    
    .badge.bg-warning {
        color: white !important;
    }
    
    .blog-date-badge {
        background-color: var(--orange-primary);
        color: white;
        padding: 5px 10px;
        position: absolute;
        top: 10px;
        left: 10px;
        font-size: 0.8rem;
        border-radius: 4px;
    }
    
    /* Fix for blog content area */
    .blog-post-content {
        max-height: 800px;  /* Limit height of content area */
        overflow-y: auto;   /* Add scrolling for long content */
        margin-bottom: 20px;
    }
    
    /* Fix for sidebar display */
    @media (min-width: 992px) {
        .sidebar {
            position: sticky;
            top: 20px;
        }
    }
    
    /* Fix for Related Posts and Also Read sections */
    .related-posts, .also-read {
        margin-top: 30px !important;
        padding-top: 30px !important;
        border-top: 1px solid #dee2e6 !important;
        display: block !important;
        clear: both !important;
    }
    
    /* Fix for card display in related posts */
    .card {
        margin-bottom: 15px;
    }
    
    /* Fix for vertical spacing */
    .mb-10 {
        margin-bottom: 10px !important;
    }
</style>
<?php
include('includes/header.php');
include('includes/db_connect.php');

// Check if post ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: blogs.php");
    exit();
}

$post_id = (int)$_GET['id'];

// Get post details
// Changed u.name to u.username to match the actual column name in the users table
$stmt = $conn->prepare("
    SELECT b.*, u.name as author_name, c.name as category_name 
    FROM blog_posts b 
    LEFT JOIN admin_users u ON b.author_id = u.id 
    LEFT JOIN blog_categories c ON b.category_id = c.id 
    WHERE b.id = ? AND b.status = 'published'
");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    header("Location: blogs.php");
    exit();
}

// Format the date
$date = new DateTime($post['created_at']);
$formatted_date = $date->format('d M Y');

// Calculate read time (approx 200 words per minute)
$word_count = str_word_count(strip_tags($post['content']));
$read_time = max(1, ceil($word_count / 200));

// Get related posts (same category)
$stmt = $conn->prepare("
    SELECT id, title, image_path, created_at 
    FROM blog_posts 
    WHERE category_id = ? AND id != ? AND status = 'published' 
    ORDER BY created_at DESC 
    LIMIT 3
");
$stmt->execute([$post['category_id'], $post_id]);
$related_posts = $stmt->fetchAll();

// Get next and previous posts
$stmt = $conn->prepare("
    SELECT id, title FROM blog_posts 
    WHERE id > ? AND status = 'published' 
    ORDER BY id ASC LIMIT 1
");
$stmt->execute([$post_id]);
$next_post = $stmt->fetch();

$stmt = $conn->prepare("
    SELECT id, title FROM blog_posts 
    WHERE id < ? AND status = 'published' 
    ORDER BY id DESC LIMIT 1
");
$stmt->execute([$post_id]);
$prev_post = $stmt->fetch();

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
    WHERE status = 'published' AND id != ?
    ORDER BY created_at DESC 
    LIMIT 3
");
$stmt->execute([$post_id]);
$recent_posts = $stmt->fetchAll();

// Get "also read" posts (random selection excluding current, related, and recent)
$excluded_ids = [$post_id];
foreach ($related_posts as $rp) $excluded_ids[] = $rp['id'];
foreach ($recent_posts as $rp) $excluded_ids[] = $rp['id'];

$placeholders = implode(',', array_fill(0, count($excluded_ids), '?'));
$stmt = $conn->prepare("
    SELECT id, title, image_path, created_at 
    FROM blog_posts 
    WHERE id NOT IN ($placeholders) AND status = 'published' 
    ORDER BY RAND() 
    LIMIT 3
");
$stmt->execute($excluded_ids);
$also_read_posts = $stmt->fetchAll();

// Update view count if the column exists
try {
    $stmt = $conn->prepare("UPDATE blog_posts SET views = views + 1 WHERE id = ?");
    $stmt->execute([$post_id]);
} catch (PDOException $e) {
    // If the column doesn't exist, just continue
}
?>


<!-- Blog Post Header (for blog-post.php) -->
<section class="page-header" style="background-image: url('images/image0.png'); background-size: cover; background-position: center; position: relative; padding: 20vh 0;">
    <!-- Reduced padding from 20vh to 10vh to save vertical space -->
    <!-- Overlay -->
    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6);"></div>
    <div class="container position-relative">
        <div class="row">
            <div class="col-12 text-center">
                <h1 class="text-white"><?php echo htmlspecialchars($post['title']); ?></h1>
            </div>
        </div>
    </div>
</section>

<!-- Blog Post Content -->
<section class="blog-single-content py-4">
    <!-- Reduced padding from py-5 to py-4 -->
    <div class="container">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-12">
                <article class="blog-post">
                    <!-- Featured Image -->
                    <div class="mb-10">
                        <!-- Changed from mb-10 to mb-4 -->
                        <?php if (!empty($post['image_path'])): ?>
                            <img src="images/<?php echo htmlspecialchars($post['image_path']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="img-fluid rounded shadow" style="max-height: 800px; width: 100%; object-fit: cover;">
                            <!-- Added max-height and object-fit to control image size -->
                        <?php else: ?>
                            <img src="https://via.placeholder.com/800x400?text=Blog+Post+Image" alt="<?php echo htmlspecialchars($post['title']); ?>" class="img-fluid rounded shadow">
                        <?php endif; ?>
                    </div>
                    
                    <!-- Post Meta -->
                    <div class="blog-post-meta mb-3">
                        <!-- Changed from mb-4 to mb-3 -->
                        <div class="d-flex align-items-center">
                            <div class="author-image me-3">
                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <i class="fas fa-user text-muted"></i>
                                </div>
                            </div>
                            <div class="author-info">
                                <h6 class="mb-0"><?php echo htmlspecialchars($post['author_name'] ?? 'Admin'); ?></h6>
                                <div class="text-muted small">
                                    <span><?php echo $formatted_date; ?></span> • 
                                    <span><?php echo $read_time; ?> min read</span>
                                    <?php if (isset($post['views'])): ?>
                                    • <span><?php echo $post['views']; ?> views</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2">
                            <!-- Changed from mt-3 to mt-2 -->
                            <?php if (!empty($post['category_name'])): ?>
                            <a href="blogs.php?category=<?php echo $post['category_id']; ?>" class="badge bg-warning text-white text-decoration-none"><?php echo htmlspecialchars($post['category_name']); ?></a>
                            <?php endif; ?>
                            
                            <?php if (!empty($post['tags'])): 
                                $tags = explode(',', $post['tags']);
                                foreach ($tags as $tag): 
                                    $tag = trim($tag);
                                    if (!empty($tag)):
                            ?>
                                <a href="blogs.php?tag=<?php echo urlencode($tag); ?>" class="badge bg-secondary text-decoration-none ms-1"><?php echo htmlspecialchars($tag); ?></a>
                            <?php 
                                    endif;
                                endforeach; 
                            endif; ?>
                        </div>
                    </div>
                    
                    <!-- Post Content -->
                   <div class="blog-post-content">
                    <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                </div>
<style>
    .blog-post-content {
    font-size: 16px;
    line-height: 1.8;
    color: #333;
    padding: 20px;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    font-family: sans-serif;

}
/* .blog-post-content blockquote {
    font-style: italic;
    border-left: 4px solid #ff6b00;
    padding-left: 15px;
    margin: 20px 0;
    color: #555;
} */

</style>
                    
                    <!-- Next/Previous Post Navigation -->
                    <div class="blog-post-navigation mt-4 pt-3 border-top">
                        <!-- Changed from mt-5 pt-4 to mt-4 pt-3 -->
                        <div class="row">
                            <div class="col-6">
                                <?php if ($prev_post): ?>
                                <a href="blog-post.php?id=<?php echo $prev_post['id']; ?>" class="text-decoration-none">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-arrow-left me-2"></i>
                                        <div>
                                            <small class="text-muted d-block">Previous Post</small>
                                            <span class="text-primary"><?php echo htmlspecialchars(substr($prev_post['title'], 0, 30) . (strlen($prev_post['title']) > 30 ? '...' : '')); ?></span>
                                        </div>
                                    </div>
                                </a>
                                <?php endif; ?>
                            </div>
                            <div class="col-6 text-end">
                                <?php if ($next_post): ?>
                                <a href="blog-post.php?id=<?php echo $next_post['id']; ?>" class="text-decoration-none">
                                    <div class="d-flex align-items-center justify-content-end">
                                        <div>
                                            <small class="text-muted d-block">Next Post</small>
                                            <span class="text-primary"><?php echo htmlspecialchars(substr($next_post['title'], 0, 30) . (strlen($next_post['title']) > 30 ? '...' : '')); ?></span>
                                        </div>
                                        <i class="fas fa-arrow-right ms-2"></i>
                                    </div>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Share Buttons -->
                    <div class="blog-post-share mt-4">
                        <!-- Changed from mt-5 to mt-4 -->
                        <h5>Share This Post</h5>
                        <div class="d-flex gap-2 mt-2">
                            <!-- Changed from mt-3 to mt-2 -->
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="btn btn-warning text-white">
                                <i class="fab fa-facebook-f"></i> Facebook
                            </a>
                            <!-- <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($post['title']); ?>" target="_blank" class="btn btn-warning text-white">
                                <i class="fab fa-twitter"></i> Twitter
                            </a> -->
                            <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="btn btn-warning text-white">
                                <i class="fab fa-linkedin-in"></i> LinkedIn
                            </a>
                            <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($post['title'] . ' - https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="btn btn-warning text-white">
                                <i class="fab fa-whatsapp"></i> WhatsApp
                            </a>
                        </div>
                    </div>
                    
        </div>
    </div>
</section>

<?php include('includes/footer.php'); ?>