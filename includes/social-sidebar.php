<?php
// Include the config file if not already included
if (!isset($site_config)) {
    include_once '../config.php';
}
?>

<!-- Social Media Sidebar -->
<div class="social-sidebar">
    <ul class="social-icons list-unstyled">
        <?php if (isset($site_config['social_media']['facebook'])): ?>
        <li>
            <a href="<?php echo $site_config['social_media']['facebook']; ?>" target="_blank" rel="noopener" class="facebook">
                <i class="fab fa-facebook-f"></i>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (isset($site_config['social_media']['instagram'])): ?>
        <li>
            <a href="<?php echo $site_config['social_media']['instagram']; ?>" target="_blank" rel="noopener" class="instagram">
                <i class="fab fa-instagram"></i>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (isset($site_config['social_media']['youtube'])): ?>
        <li>
            <a href="<?php echo $site_config['social_media']['youtube']; ?>" target="_blank" rel="noopener" class="youtube">
                <i class="fab fa-youtube"></i>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (isset($site_config['social_media']['linkedin'])): ?>
        <li>
            <a href="<?php echo $site_config['social_media']['linkedin']; ?>" target="_blank" rel="noopener" class="linkedin">
                <i class="fab fa-linkedin-in"></i>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (isset($site_config['social_media']['whatsapp'])): ?>
        <li>
            <a href="<?php echo $site_config['social_media']['whatsapp']; ?>" target="_blank" rel="noopener" class="whatsapp">
                <i class="fab fa-whatsapp"></i>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</div>