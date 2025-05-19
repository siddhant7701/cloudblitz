$(document).ready(function() {
    // Enhanced sidebar toggle with animation
    $('#sidebarCollapse').on('click', function() {
        // Toggle active class on sidebar and content
        $('#sidebar, #content').toggleClass('active');
        
        // Toggle active class on the button itself for hamburger animation
        $(this).toggleClass('active');
        
        // Store sidebar state in local storage
        localStorage.setItem('sidebarState', $('#sidebar').hasClass('active') ? 'collapsed' : 'expanded');
    });
    
    // Check local storage for sidebar state on page load
    if (localStorage.getItem('sidebarState') === 'collapsed') {
        $('#sidebar, #content').addClass('active');
        $('#sidebarCollapse').addClass('active');
    }
    
    // Close sidebar on mobile when clicking outside
    $(document).on('click', function(e) {
        const sidebar = $('#sidebar');
        const sidebarCollapse = $('#sidebarCollapse');
        
        // If sidebar is active on mobile and click is outside sidebar and toggle button
        if (sidebar.hasClass('active') && 
            !sidebar.is(e.target) && 
            sidebar.has(e.target).length === 0 && 
            !sidebarCollapse.is(e.target) && 
            sidebarCollapse.has(e.target).length === 0 &&
            $(window).width() <= 768) {
            
            sidebar.removeClass('active');
            $('#content').removeClass('active');
            $('#sidebarCollapse').removeClass('active');
        }
    });
    
    // Handle window resize to reset sidebar state on larger screens
    $(window).resize(function() {
        if ($(window).width() > 768) {
            if (localStorage.getItem('sidebarState') === 'expanded') {
                $('#sidebar, #content').removeClass('active');
                $('#sidebarCollapse').removeClass('active');
            }
        } else {
            if (!$('#sidebar').hasClass('active')) {
                $('#sidebar, #content').addClass('active');
                $('#sidebarCollapse').addClass('active');
            }
        }
    });
});