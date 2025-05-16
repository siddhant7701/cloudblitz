<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $page_title === 'Dashboard' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($page_title, 'Course') !== false ? 'active' : ''; ?>" href="courses.php">
                    <i class="fas fa-book me-2"></i>
                    Courses
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($page_title, 'Instructor') !== false ? 'active' : ''; ?>" href="instructors.php">
                    <i class="fas fa-chalkboard-teacher me-2"></i>
                    Instructors
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($page_title, 'User') !== false ? 'active' : ''; ?>" href="users.php">
                    <i class="fas fa-users me-2"></i>
                    Users
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($page_title, 'Blog') !== false ? 'active' : ''; ?>" href="blog-posts.php">
                    <i class="fas fa-newspaper me-2"></i>
                    Blog
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $page_title === 'Messages' ? 'active' : ''; ?>" href="messages.php">
                    <i class="fas fa-envelope me-2"></i>
                    Messages
                    <?php
                    // Count unread messages
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0");
                    $stmt->execute();
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $unread = $result['count'];
                    
                    if ($unread > 0):
                    ?>
                    <span class="badge bg-danger rounded-pill ms-1"><?php echo $unread; ?></span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>
        
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Administration</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?php echo $page_title === 'Settings' ? 'active' : ''; ?>" href="settings.php">
                    <i class="fas fa-cog me-2"></i>
                    Settings
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $page_title === 'Change Password' ? 'active' : ''; ?>" href="change-password.php">
                    <i class="fas fa-key me-2"></i>
                    Change Password
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    Logout
                </a>
            </li>
        </ul>
    </div>
</nav>