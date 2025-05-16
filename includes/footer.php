<!-- Footer -->
<style>
    /* Footer Styling */
    .site-footer {
        background-color: #0c0c14;
        color: #fff;
        padding: 0;
        font-family: 'Arial', sans-serif;
    }
    
    .divider-line {
        height: 1px;
        background-color: #333;
        margin: 0;
        width: 100%;
    }
    
    .social-section {
        padding: 40px 0;
    }
    
    .social-section h2 {
        font-size: 22px;
        margin-bottom: 10px;
        color: #fff;
        text-transform: uppercase;
        font-weight: 600;
    }
    
    .social-section h3 {
        font-size: 38px;
        margin-bottom: 30px;
        color: #fff;
        font-weight: 600;
    }
    
    .social-icons {
        display: flex;
        gap: 20px;
        margin: 20px 0;
        align-items: center;
    }
    
    .social-icon {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #fff;
        text-decoration: none;
        transition: 0.3s;
    }
    
    .social-icon img, .social-icon .icon-container {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background-color: #fff;
        margin-right: 10px;
    }
    
    .facebook-icon {
        background-color: #1877f2;
    }
    
    .twitter-icon {
        background-color: #000;
    }
    
    .youtube-icon {
        background-color: #ff0000;
    }
    
    .instagram-icon {
        background: linear-gradient(45deg, #feda75, #fa7e1e, #d62976, #962fbf, #4f5bd5);
    }
    
    .linkedin-icon {
        background-color: #0a66c2;
    }
    
    .whatsapp-icon {
        background-color: #25d366;
    }
    
    .social-icon i {
        font-size: 28px;
        color: #fff;
    }
    
    .social-icon span {
        font-size: 18px;
        font-weight: 500;
    }
    
    .lets-talk-btn {
        background-color: #fff;
        color: #ff6b00;
        border: none;
        padding: 15px 30px;
        font-size: 16px;
        font-weight: 600;
        border-radius: 50px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        transition: 0.3s;
        float: right;
        margin-top: 30px;
    }
    
    .lets-talk-btn i {
        font-size: 18px;
        transform: rotate(45deg);
    }
    
    /* Centers Section */
    .centers-section {
        padding: 40px 0;
        background-color: #0c0c14;
    }
    
    .centers-section h2 {
        font-size: 22px;
        margin-bottom: 40px;
        color: #fff;
        text-transform: uppercase;
        font-weight: 600;
        display: flex;
        align-items: center;
    }
    
    .centers-section h2 i {
        color: #ff6b00;
        margin-left: 10px;
        font-size: 24px;
    }
    
    .center-item {
        margin-bottom: 40px;
    }
    
    .center-item h3 {
        font-size: 18px;
        color: #fff;
        margin-bottom: 5px;
        font-weight: 600;
        display: flex;
        align-items: center;
    }
    
    .center-item h3 i {
        color: #ff6b00;
        margin-right: 10px;
        font-size: 18px;
    }
    
    .center-item p {
        color: #ccc;
        font-size: 14px;
        line-height: 1.5;
        margin-left: 28px;
    }
    
    /* Courses Section */
    .courses-section {
        padding: 60px 0;
        background-color: #080810;
    }
    
    .courses-section h3, .cloudblitz-section h3 {
        font-size: 24px;
        color: #fff;
        margin-bottom: 30px;
        font-weight: 600;
    }
    
    .course-item, .cloudblitz-item {
        margin-bottom: 20px;
    }
    
    .course-item a, .cloudblitz-item a {
        color: #ccc;
        text-decoration: none;
        font-size: 16px;
        transition: 0.3s;
        line-height: 1.8;
    }
    
    .course-item a:hover, .cloudblitz-item a:hover {
        color: #ff6b00;
    }
    
    .view-more-btn {
        display: inline-block;
        padding: 10px 25px;
        border: 1px solid #ff6b00;
        color: #ff6b00;
        text-decoration: none;
        font-weight: 500;
        border-radius: 50px;
        margin-top: 20px;
        transition: 0.3s;
        float: right;
    }
    
    .view-more-btn:hover {
        background-color: #ff6b00;
        color: #fff;
    }
    
    .view-more-btn i {
        margin-left: 5px;
    }
    
    .footer-image {
        max-width: 100%;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .container {
        width: 90%;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .row {
        display: flex;
        flex-wrap: wrap;
    }
    
    .col-md-6 {
        width: 50%;
        padding: 0 15px;
    }
    
    .col-md-3 {
        width: 25%;
        padding: 0 15px;
    }
    
    @media (max-width: 768px) {
        .col-md-6, .col-md-3 {
            width: 100%;
            margin-bottom: 30px;
        }
        
        .social-icons {
            flex-wrap: wrap;
        }
        
        .lets-talk-btn {
            float: none;
            display: inline-block;
            margin-top: 20px;
        }
        
        .view-more-btn {
            float: none;
        }
    }
</style>

<footer class="site-footer">
    <div class="divider-line"></div>
    
    <!-- Social Section -->
    <div class="social-section">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h2>SOCIAL</h2>
                    <h3>Follow Us For The Latest Updates</h3>
                    
                    <div class="social-icons">
                        <a href="<?php echo !empty($site_settings['facebook_url']) ? htmlspecialchars($site_settings['facebook_url']) : '#'; ?>" class="social-icon">
                            <span class="icon-container facebook-icon"><i class="fab fa-facebook-f"></i></span>
                            <span>Facebook</span>
                        </a>
                        
                        <a href="<?php echo !empty($site_settings['twitter_url']) ? htmlspecialchars($site_settings['twitter_url']) : '#'; ?>" class="social-icon">
                            <span class="icon-container twitter-icon"><i class="fab fa-twitter"></i></span>
                            <span>X</span>
                        </a>
                        
                        <a href="<?php echo !empty($site_settings['youtube_url']) ? htmlspecialchars($site_settings['youtube_url']) : '#'; ?>" class="social-icon">
                            <span class="icon-container youtube-icon"><i class="fab fa-youtube"></i></span>
                            <span>Youtube</span>
                        </a>
                        
                        <a href="<?php echo !empty($site_settings['instagram_url']) ? htmlspecialchars($site_settings['instagram_url']) : '#'; ?>" class="social-icon">
                            <span class="icon-container instagram-icon"><i class="fab fa-instagram"></i></span>
                            <span>Instagram</span>
                        </a>
                        
                        <a href="<?php echo !empty($site_settings['linkedin_url']) ? htmlspecialchars($site_settings['linkedin_url']) : '#'; ?>" class="social-icon">
                            <span class="icon-container linkedin-icon"><i class="fab fa-linkedin-in"></i></span>
                            <span>Linkedin</span>
                        </a>
                        
                        <a href="<?php echo !empty($site_settings['whatsapp_url']) ? htmlspecialchars($site_settings['whatsapp_url']) : '#'; ?>" class="social-icon">
                            <span class="icon-container whatsapp-icon"><i class="fab fa-whatsapp"></i></span>
                            <span>Whatsapp</span>
                        </a>
                    </div>
                </div>
                <div class="col-md-6">
                    <a href="<?php echo !empty($site_settings['contact_page']) ? htmlspecialchars($site_settings['contact_page']) : 'contact.php'; ?>" class="lets-talk-btn">
                        LETS TALK <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="divider-line"></div>
    
    <!-- Centers Section -->
    <div class="centers-section">
        <div class="container">
            <h2>CENTERS <i class="fas fa-map-marker-alt"></i></h2>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="center-item">
                        <h3><i class="fas fa-map-marker-alt"></i> NAGPUR</h3>
                        <p>Plot No. 21, Public Corporation Society, Atrey Layout, Pratap Nagar, Nagpur, 440022</p>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="center-item">
                        <h3><i class="fas fa-map-marker-alt"></i> PUNE, WAKAD</h3>
                        <p>502, 5th Floor, Bhama Center, Bhujbal Chowk, Hinjewadi Road, Bhujbal Vasti, Wakad, Pimpri Chinchwad, Maharashtra</p>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="center-item">
                        <h3><i class="fas fa-map-marker-alt"></i> PUNE, KOTHRUD</h3>
                        <p>Second Floor, Kalpavruksha Building, Mayur Colony, Kothrud, Pune, Maharashtra 411038</p>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="center-item">
                        <h3><i class="fas fa-map-marker-alt"></i> INDORE</h3>
                        <p>1st Floor, IDBI BANK, Plot no 920, Sapna Sangita Road, Tower Square, Square, Indore, Madhya Pradesh 452014</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="divider-line"></div>
    
    <!-- Courses & Cloudblitz Section -->
    <div class="courses-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <img src="<?php echo !empty($site_settings['footer_image']) ? htmlspecialchars($site_settings['footer_image']) : 'images/tech-person.jpg'; ?>" alt="Technology" class="footer-image">
                </div>
                
                <div class="col-md-3">
                    <h3>Courses</h3>
                    <div class="course-item">
                        <a href="#">AIFS-CDEC AI Powered Full Stack with Cloud Devops Engineering Course</a>
                    </div>
                    <div class="course-item">
                        <a href="#">CDEC- Cloud Devops Engineering Course</a>
                    </div>
                    <div class="course-item">
                        <a href="#">X-DSAAI Expert in data science with ai</a>
                    </div>
                    <div class="course-item">
                        <a href="#">X-DMAI Expert in Digital Marketing With AI</a>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <h3>Cloudblitz</h3>
                    <div class="cloudblitz-item">
                        <a href="#">Home</a>
                    </div>
                    <div class="cloudblitz-item">
                        <a href="#">About Us</a>
                    </div>
                    <div class="cloudblitz-item">
                        <a href="#">Courses</a>
                    </div>
                    <div class="cloudblitz-item">
                        <a href="#">Blogs</a>
                    </div>
                    <div class="cloudblitz-item">
                        <a href="#">Success Stories</a>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <a href="./admin/index.php" class="view-more-btn">ADMIN LOGIN</a>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Include Font Awesome for icons if not already included in the main template -->
<?php if (!isset($font_awesome_loaded)): ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<?php endif; ?>

<!-- Back to Top Button -->
<a href="#" class="back-to-top" style="display: inline-block; position: fixed; bottom: 30px; right: 30px; background-color: #ff6b00; color: #fff; width: 40px; height: 40px; border-radius: 50%; text-align: center; line-height: 40px; z-index: 999; opacity: 0.8;">
    <i class="fa fa-angle-up"></i>
</a>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

<!-- Bootstrap JS (if needed) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script>
    // Back to top button functionality
    $(window).scroll(function() {
        if ($(this).scrollTop() > 200) {
            $('.back-to-top').fadeIn();
        } else {
            $('.back-to-top').fadeOut();
        }
    });
    
    $('.back-to-top').click(function(e) {
        e.preventDefault();
        $('html, body').animate({scrollTop: 0}, 800);
        return false;
    });
</script>