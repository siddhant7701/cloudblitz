<?php
// Include database connection
require_once 'includes/db_connect.php';

// Set page specific CSS - use the original CSS from the CLOUDBLITZ files
$page_specific_css = 'css/contact.css';
$page_title = 'Contact Us';

// Process form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message_content = $_POST['message'] ?? '';
    
    if (empty($name) || empty($email) || empty($subject) || empty($message_content)) {
        $message = '<div class="alert alert-danger">Please fill in all fields.</div>';
    } else {
        // Insert into database
        try {
            $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message, created_at) 
                                    VALUES (:name, :email, :subject, :message, NOW())");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':subject', $subject);
            $stmt->bindParam(':message', $message_content);
            $stmt->execute();
            
            // Send email notification (optional)
            $to = "admin@example.com";
            $email_subject = "New Contact Form Submission: $subject";
            $email_body = "You have received a new message from your website contact form.\n\n"
                        . "Name: $name\n"
                        . "Email: $email\n"
                        . "Subject: $subject\n"
                        . "Message:\n$message_content";
            $headers = "From: noreply@example.com";
            
            mail($to, $email_subject, $email_body, $headers);
            
            $message = '<div class="alert alert-success">Thank you for your message. We will get back to you soon!</div>';
            
            // Clear form data
            $name = $email = $subject = $message_content = '';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Sorry, there was an error sending your message. Please try again later.</div>';
        }
    }
}

// Include header
require_once 'includes/header.php';

// Get contact page content from database
$stmt = $conn->prepare("SELECT * FROM pages WHERE page_name = 'contact'");
$stmt->execute();
$contactPage = $stmt->fetch();
?>

<!-- Contact Hero Section - Keeping the exact HTML structure from the original -->
<section class="contact-hero">
  <div class="container">
    <div class="row">
      <div class="col-md-12 text-center">
        <h1><?php echo htmlspecialchars($contactPage['title']); ?></h1>
        <p><?php echo htmlspecialchars($contactPage['subtitle']); ?></p>
      </div>
    </div>
  </div>
</section>

<!-- Contact Content Section -->
<section class="contact-content">
  <div class="container">
    <div class="row">
      <div class="col-md-6">
        <div class="contact-info">
          <h2>Get in Touch</h2>
          <p><?php echo $contactPage['content']; ?></p>
          
          <div class="contact-details">
            <div class="contact-item">
              <i class="fa fa-map-marker-alt"></i>
              <div>
                <h4>Address</h4>
                <p><?php echo nl2br(htmlspecialchars($contactPage['address'])); ?></p>
              </div>
            </div>
            
            <div class="contact-item">
              <i class="fa fa-phone"></i>
              <div>
                <h4>Phone</h4>
                <p><?php echo htmlspecialchars($contactPage['phone']); ?></p>
              </div>
            </div>
            
            <div class="contact-item">
              <i class="fa fa-envelope"></i>
              <div>
                <h4>Email</h4>
                <p><?php echo htmlspecialchars($contactPage['email']); ?></p>
              </div>
            </div>
          </div>
          
          <div class="social-links">
            <h4>Follow Us</h4>
            <ul class="list-inline">
              <?php if (!empty($contactPage['facebook'])): ?>
                <li class="list-inline-item">
                  <a href="<?php echo htmlspecialchars($contactPage['facebook']); ?>" target="_blank">
                    <i class="fab fa-facebook-f"></i>
                  </a>
                </li>
              <?php endif; ?>
              
              <?php if (!empty($contactPage['twitter'])): ?>
                <li class="list-inline-item">
                  <a href="<?php echo htmlspecialchars($contactPage['twitter']); ?>" target="_blank">
                    <i class="fab fa-twitter"></i>
                  </a>
                </li>
              <?php endif; ?>
              
              <?php if (!empty($contactPage['instagram'])): ?>
                <li class="list-inline-item">
                  <a href="<?php echo htmlspecialchars($contactPage['instagram']); ?>" target="_blank">
                    <i class="fab fa-instagram"></i>
                  </a>
                </li>
              <?php endif; ?>
              
              <?php if (!empty($contactPage['linkedin'])): ?>
                <li class="list-inline-item">
                  <a href="<?php echo htmlspecialchars($contactPage['linkedin']); ?>" target="_blank">
                    <i class="fab fa-linkedin-in"></i>
                  </a>
                </li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="contact-form">
          <h2>Send a Message</h2>
          <?php echo $message; ?>
          
          <form action="contact.php" method="POST">
            <div class="form-group">
              <label for="name">Your Name</label>
              <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
            </div>
            
            <div class="form-group">
              <label for="email">Your Email</label>
              <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
            </div>
            
            <div class="form-group">
              <label for="subject">Subject</label>
              <input type="text" class="form-control" id="subject" name="subject" value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>" required>
            </div>
            
            <div class="form-group">
              <label for="message">Your Message</label>
              <textarea class="form-control" id="message" name="message" rows="5" required><?php echo isset($message_content) ? htmlspecialchars($message_content) : ''; ?></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Send Message</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Map Section -->
<section class="map-section">
  <div class="container-fluid p-0">
    <div class="map-container">
      <iframe src="<?php echo htmlspecialchars($contactPage['map_url']); ?>" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
    </div>
  </div>
</section>

<?php
// Include footer
require_once 'includes/footer.php';
?>
