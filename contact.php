<?php
// Include database connection
require_once 'includes/db_connect.php';

// Set page specific CSS - use the original CSS from the CLOUDBLITZ files
$page_specific_css = 'css/contact.css';
$page_title = 'Contact Us';

// Process form submission
$message = '';
$form_submitted = false;
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
            // $to = "admin@example.com";
            // $email_subject = "New Contact Form Submission: $subject";
            // $email_body = "You have received a new message from your website contact form.\n\n"
            //             . "Name: $name\n"
            //             . "Email: $email\n"
            //             . "Subject: $subject\n"
            //             . "Message:\n$message_content";
            // $headers = "From: noreply@example.com";
            
            // mail($to, $email_subject, $email_body, $headers);
            
            // Instead of showing message in same page, we'll display success page
            $form_submitted = true;
            
            // Clear form data
            $name = $email = $subject = $message_content = '';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Sorry, there was an error sending your message. Please try again later.</div>';
        }
    }
}

// If form is successfully submitted, show success page with redirect
if ($form_submitted) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Form Submitted - <?php echo htmlspecialchars($page_title); ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            .success-container {
                text-align: center;
                padding: 50px 20px;
                max-width: 600px;
                margin: 50px auto;
            }
            .checkmark {
                width: 100px;
                margin: 0 auto 30px;
            }
            .success-title {
                color: #8BC34A;
                font-size: 32px;
                margin-bottom: 20px;
            }
            .success-message {
                color: #888;
                font-size: 18px;
                margin-bottom: 30px;
            }
            .go-back-btn {
                background-color: #FF5722;
                color: white;
                padding: 10px 30px;
                border: none;
                border-radius: 5px;
                font-size: 18px;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
            }
            .go-back-btn:hover {
                background-color: #E64A19;
                color: white;
            }
        </style>
        <meta http-equiv="refresh" content="3;url=index.php">
    </head>
    <body>
        <div class="container">
            <div class="success-container">
                <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                    <circle cx="26" cy="26" r="25" fill="#8BC34A"/>
                    <path fill="none" stroke="#FFFFFF" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                </svg>
                <h1 class="success-title">Form Submitted Successfully!</h1>
                <p class="success-message">Thank you! The form has been submitted successfully.<br>We will reply to you soon!</p>
                <a href="index.php" class="go-back-btn">Go Back</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit; // Stop execution here
}

// If not submitted or there was an error, show the contact form
// Include header
require_once 'includes/header.php';

// Get contact page content from database
$stmt = $conn->prepare("SELECT * FROM pages WHERE page_name = 'contact'");
$stmt->execute();
$contactPage = $stmt->fetch();
?>

<style>
/* Custom CSS specific to contact page */
.page-header {
    position: relative;
    padding: 120px 0;
    color: white;
    text-align: center;
}

.page-header h1 {
    font-size: 42px;
    font-weight: 700;
    position: relative;
    z-index: 2;
}

.page-header::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    z-index: 1;
}

.contact-content {
    padding: 60px 0;
}

/* Contact Form Container */
.contact-form-container {
    background-color: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 5px 30px rgba(0, 0, 0, 0.1);
    margin-bottom: 50px;
}

/* Contact Form Left Side - Image */
.contact-image-container {
    background: linear-gradient(to right, #FF5722, #FFC107);
    padding: 0;
    height: 100%;
    position: relative;
}

.contact-image-container img {
    width: 100%;
    height: 100%; 
    object-fit: cover;
    object-position: center;
}

/* Contact Form Right Side - Form */
.contact-form {
    padding: 40px 30px;
}

.contact-form h2 {
    font-size: 28px;
    font-weight: 600;
    margin-bottom: 25px;
}

.contact-form .text-orange {
    color: #FF5722;
}

.contact-form label {
    font-weight: 500;
    margin-bottom: 5px;
    display: block;
}

.contact-form .form-control,
.contact-form .form-select {
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    margin-bottom: 10px;
    width: 100%;
}

.contact-form textarea.form-control {
    height: 120px;
    resize: none;
}

.btn-orange {
    background-color: #FF5722;
    color: white;
    padding: 12px 30px;
    border: none;
    border-radius: 5px;
    font-weight: 500;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-block;
    text-align: center;
}

.btn-orange:hover {
    background-color: #E64A19;
    color: white;
}

/* Map Section */
.map-section {
    margin-bottom: 60px;
}

/* Alerts */
.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.alert-danger {
    background-color: #F2DEDE;
    color: #A94442;
    border: 1px solid #EBCCD1;
}

/* Media Queries */
@media (max-width: 991px) {
    .contact-image-container {
        height: 300px;
    }
}

@media (max-width: 767px) {
    .contact-form {
        padding: 25px 20px;
    }
    
    .contact-image-container {
        height: 250px;
    }
    
    .page-header {
        padding: 80px 0;
    }
    
    .page-header h1 {
        font-size: 32px;
    }
}
</style>

<!-- Page Header -->
<section class="page-header" style="background-image: url('images/image0.png'); background-size: cover; background-position: center; position: relative; padding: 20vh 0;">
    <!-- Overlay -->
    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></div>
    <div class="container position-relative">
        <div class="row">
            <div class="col-12 text-center">
                <h1 class="text-white">Contact Us</h1>
            </div>
        </div>
    </div>
</section>

<!-- Contact Content Section -->
<section class="contact-content">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="contact-form-container">
                    <div class="row g-0">
                        <!-- Contact Image -->
                        <div class="col-lg-6 contact-image-container">
                            <img src="images/contactus.png" alt="Contact Support">
                        </div>
                        
                        <!-- Contact Form -->
                        <div class="col-lg-6">
                            <div class="contact-form">
                                <h2>Get Your <span class="text-orange">Free Quote</span> Today!</h2>
                                <?php echo $message; ?>
                                
                                <form action="contact.php" method="POST">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="name">Full Name*</label>
                                            <input type="text" class="form-control" id="name" name="name" placeholder="Full Name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="email">Email*</label>
                                            <input type="email" class="form-control" id="email" name="email" placeholder="Email Address" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-8 mb-3">
                                            <label for="phone">Phone Number*</label>
                                            <input type="text" class="form-control" id="phone" name="subject" placeholder="Phone Number" value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>" required>
                                        </div>
                                        
                                       
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="message">Your Message*</label>
                                        <textarea class="form-control" id="message" name="message" placeholder="Enter here..."><?php echo isset($message_content) ? htmlspecialchars($message_content) : ''; ?></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn-orange">Get Free Counseling</button>
                                </form>
                            </div>
                        </div>
                    </div>
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