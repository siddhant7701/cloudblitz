<nav id="sidebar">
    <div class="sidebar-header">
        <h3>Cloud<span>Blitz</span></h3>
    </div>

    <ul class="list-unstyled components">
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
            <a href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
        </li>
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'courses.php' || basename($_SERVER['PHP_SELF']) == 'add-course.php' || basename($_SERVER['PHP_SELF']) == 'edit-course.php') ? 'active' : ''; ?>">
            <a href="courses.php">
                <i class="fas fa-book"></i>
                Courses
            </a>
</li> <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'instructors.php' || basename($_SERVER['PHP_SELF']) == 'add-instructor') ? 'active' : ''; ?>">
            <a href="instructors.php">
                <i class="fas fa-graduation-cap"></i>
                Instructors
            </a>
        </li>
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'blog-posts.php' || basename($_SERVER['PHP_SELF']) == 'blog-post-add.php' || basename($_SERVER['PHP_SELF']) == 'blog-post-edit.php') ? 'active' : ''; ?>">
            <a href="blog-posts.php">
                <i class="fas fa-blog"></i>
                Blog
            </a>
        </li>
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'messages.php' || basename($_SERVER['PHP_SELF']) == 'view-message.php') ? 'active' : ''; ?>">
            <a href="messages.php">
                <i class="fas fa-envelope"></i>
                Messages
            </a>
        </li>
         <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'counselling-requests.php' || basename($_SERVER['PHP_SELF']) == 'counselling-requests.php') ? 'active' : ''; ?>">
            <a href="counselling-requests.php">
                <i class="fas fa-comments"></i>
                Councelling Requests
            </a>
        </li>
                <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'manage-careers.php' || basename($_SERVER['PHP_SELF']) == 'add-job.php' || basename($_SERVER['PHP_SELF']) == 'edit-job.php' || basename($_SERVER['PHP_SELF']) == 'view-applications.php') ? 'active' : ''; ?>">
            <a href="manage-careers.php">
                <i class="fas fa-user"></i>
                Career Management
            </a>
        </li>

        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'active' : ''; ?>">
            <a href="settings.php">
                <i class="fas fa-cog"></i>
                Settings
            </a>
        </li>
         <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'users.php' || basename($_SERVER['PHP_SELF']) == 'edit-admin-user.php') ? 'active' : ''; ?>">
            <a href="users.php">
                <i class="fas fa-users"></i>
                Users
            </a>
        </li>
    </ul>
</nav>
