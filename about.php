<?php
// Include database connection
require_once 'includes/db_connect.php';

// Set page specific CSS - use the original CSS from the CLOUDBLITZ files
$page_specific_css = 'css/about.css';
$page_title = 'About Us';

// Include header
require_once 'includes/header.php';

// Get about page content from database
$stmt = $conn->prepare("SELECT * FROM pages WHERE page_name = 'about'");
$stmt->execute();
$aboutPage = $stmt->fetch();

// Get team members
$stmt = $conn->prepare("SELECT * FROM team_members ORDER BY display_order");
$stmt->execute();
$teamMembers = $stmt->fetchAll();
?>

<!-- About Hero Section - Keeping the exact HTML structure from the original -->
<section class="about-hero">
  <div class="container">
    <div class="row">
      <div class="col-md-12 text-center">
        <h1><?php echo htmlspecialchars($aboutPage['title']); ?></h1>
      </div>
    </div>
  </div>
</section>

<!-- About Content Section -->
<section class="about-content">
  <div class="container">
    <div class="row">
      <div class="col-md-8 offset-md-2">
        <div class="about-text">
          <?php echo $aboutPage['content']; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Team Section -->
<section class="team-section">
  <div class="container">
    <h2 class="section-title text-center">Our Team</h2>
    <div class="row">
      <?php foreach ($teamMembers as $member): ?>
        <div class="col-md-4">
          <div class="team-member">
            <img src="<?php echo htmlspecialchars($member['image_path']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>" class="img-fluid rounded-circle">
            <h3><?php echo htmlspecialchars($member['name']); ?></h3>
            <p class="position"><?php echo htmlspecialchars($member['position']); ?></p>
            <p class="bio"><?php echo htmlspecialchars($member['bio']); ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php
// Include footer
require_once 'includes/footer.php';
?>
