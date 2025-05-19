<?php
// Include database connection
require_once 'includes/db_connect.php';

// Function to get all job listings or filtered by category
function getJobs($db, $category = null) {
    try {
        $sql = "SELECT * FROM job_listings WHERE 1=1";
        
        if ($category && $category != 'ALL') {
            $sql .= " AND department = :category";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $db->prepare($sql);
        
        if ($category && $category != 'ALL') {
            $stmt->bindParam(':category', $category, PDO::PARAM_STR);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log error
        error_log("Database error: " . $e->getMessage());
        return [];
    }
}

// Get the selected category from URL parameter
$selectedCategory = $_GET['category'] ?? 'ALL';

// Get job listings
$jobs = getJobs($conn, $selectedCategory);

// Define available categories
$categories = ['ALL', 'SALES', 'MARKETING', 'OPERATION', 'ACCOUNT & FINANCE', 'TRAINERS'];

// Page title
$pageTitle = "Careers - Cloudblitz";
include 'includes/header.php';
?>
<link rel="stylesheet" href="./css/careers.css">
<div class="container mx-auto px-4 py-8">
    <div class="mb-12">
        <div class="inline-block mb-6">
            <div class="category-btn active">
                We Are Hiring
            </div>
        </div>

        <h1 class="text-4xl font-bold mb-4">Be A Part Of Our Cloudblitz Family</h1>
        <p class="text-lg mb-2">We're Looking For Passionate People To Join Us On Our Family. We Value</p>
        <p class="text-lg mb-8">Flat Hierarchies, Clear Communication & Full Ownership & Responsibility.</p>

        <div class="flex flex-wrap gap-2 mb-8">
            <?php foreach ($categories as $category): ?>
           <a href="?category=<?php echo urlencode($category); ?>" 
   class="category-btn <?php echo $selectedCategory === $category ? 'active' : ''; ?>">
   <?php echo htmlspecialchars($category); ?>
</a>


            <?php endforeach; ?>
        </div>
    </div>

    <div class="border-t border-gray-200 pt-8">
        <?php if (count($jobs) > 0): ?>
            <?php foreach ($jobs as $job): ?>
            <div class="job-card">
    <div class="job-header">
        <h2 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h2>
        <a href="job-details.php?id=<?php echo $job['id']; ?>" class="apply-btn">
            APPLY NOW
            <svg class="arrow-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
            </svg>
        </a>
    </div>

    <p class="job-desc"><?php echo htmlspecialchars($job['description']); ?></p>

    <div class="job-tags">
        <span class="job-tag">
            <svg class="tag-icon" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
            </svg>
            <?php echo htmlspecialchars($job['location']); ?>
        </span>
        <span class="job-tag">
            <svg class="tag-icon" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
            </svg>
            <?php echo htmlspecialchars($job['job_type']); ?>
        </span>
    </div>
</div>

            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-10">
                <p class="text-lg text-gray-600">No job openings available at the moment. Please check back later.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>