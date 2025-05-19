
<?php
// Include database connection
require_once 'includes/db_connect.php';

// Check if job ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: careers.php');
    exit;
}

$jobId = $_GET['id'];

// Get job details
try {
    $stmt = $conn->prepare("SELECT * FROM job_listings WHERE id = :id");
    $stmt->bindParam(':id', $jobId, PDO::PARAM_INT);
    $stmt->execute();
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) {
        header('Location: careers.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    header('Location: careers.php');
    exit;
}

// Handle form submission
$formSubmitted = false;
$formError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $resume = $_FILES['resume'] ?? null;
    
    if (empty($name) || empty($email) || empty($phone) || empty($resume['name'])) {
        $formError = "All fields are required";
    } else {
        // Process file upload
        $targetDir = "uploads/resumes/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        $fileExt = strtolower(pathinfo($resume['name'], PATHINFO_EXTENSION));
        $allowedExts = ['pdf', 'doc', 'docx'];
        
        if (!in_array($fileExt, $allowedExts)) {
            $formError = "Only PDF, DOC, and DOCX files are allowed";
        } else {
            $fileName = uniqid() . '_' . $name . '.' . $fileExt;
            $targetFile = $targetDir . $fileName;
            
            if (move_uploaded_file($resume['tmp_name'], $targetFile)) {
                // Save application to database
                try {
                    $stmt = $conn->prepare("INSERT INTO job_applications (job_id, name, email, phone, resume_path, applied_at) 
                                           VALUES (:job_id, :name, :email, :phone, :resume_path, NOW())");
                    $stmt->bindParam(':job_id', $jobId, PDO::PARAM_INT);
                    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
                    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                    $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
                    $stmt->bindParam(':resume_path', $targetFile, PDO::PARAM_STR);
                    $stmt->execute();
                    
                    $formSubmitted = true;
                } catch (PDOException $e) {
                    error_log("Database error: " . $e->getMessage());
                    $formError = "An error occurred. Please try again later.";
                }
            } else {
                $formError = "Failed to upload file. Please try again.";
            }
        }
    }
}

// Page title
$pageTitle = htmlspecialchars($job['title']) . " - Careers - Cloudblitz";
include 'includes/header.php';
?>
<link rel="stylesheet" href="./css/careers.css">
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="careers.php" class="text-orange-500 hover:underline flex items-center">
            <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Careers
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h1 class="text-3xl font-bold mb-4"><?php echo htmlspecialchars($job['title']); ?></h1>
        
        <div class="flex flex-wrap gap-2 mb-6">
            <span class="inline-flex items-center gap-1 text-gray-600 border border-gray-300 rounded-full px-3 py-1 text-sm">
                <svg class="h-4 w-4 text-orange-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                </svg>
                <?php echo htmlspecialchars($job['location']); ?>
            </span>
            <span class="inline-flex items-center gap-1 text-gray-600 border border-gray-300 rounded-full px-3 py-1 text-sm">
                <svg class="h-4 w-4 text-orange-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                </svg>
                <?php echo htmlspecialchars($job['job_type']); ?>
            </span>
            <span class="inline-flex items-center gap-1 text-gray-600 border border-gray-300 rounded-full px-3 py-1 text-sm">
                <svg class="h-4 w-4 text-orange-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                </svg>
                <?php echo htmlspecialchars($job['department']); ?>
            </span>
        </div>
        
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-3">Job Description</h2>
            <div class="prose max-w-none">
                <?php echo nl2br(htmlspecialchars($job['description'])); ?>
            </div>
        </div>
        
        <?php if (isset($job['responsibilities']) && !empty($job['responsibilities'])): ?>
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-3">Responsibilities</h2>
            <div class="prose max-w-none">
                <?php echo nl2br(htmlspecialchars($job['responsibilities'])); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($job['requirements']) && !empty($job['requirements'])): ?>
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-3">Requirements</h2>
            <div class="prose max-w-none">
                <?php echo nl2br(htmlspecialchars($job['requirements'])); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($formSubmitted): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-8" role="alert">
            <p class="font-bold">Application Submitted!</p>
            <p>Thank you for applying. We will review your application and get back to you soon.</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold mb-6">Apply for this Position</h2>
            
            <?php if ($formError): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                    <p><?php echo htmlspecialchars($formError); ?></p>
                </div>
            <?php endif; ?>
            
            <form action="job-details.php?id=<?php echo $jobId; ?>" method="post" enctype="multipart/form-data">
                <div class="mb-4">
                    <label for="name" class="block text-gray-700 font-medium mb-2">Full Name</label>
                    <input type="text" id="name" name="name" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 font-medium mb-2">Email Address</label>
                    <input type="email" id="email" name="email" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                
                <div class="mb-4">
                    <label for="phone" class="block text-gray-700 font-medium mb-2">Phone Number</label>
                    <input type="tel" id="phone" name="phone" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                
                <div class="mb-6">
                    <label for="resume" class="block text-gray-700 font-medium mb-2">Resume/CV : (PDF, DOC, DOCX)</label>
                    <input type="file" id="resume" name="resume" required accept=".pdf,.doc,.docx"
                           class="category-btn">
                </div>
                
                <button type="submit" class="apply-btn">
                    Submit Application
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>


<?php include 'includes/footer.php'; ?>