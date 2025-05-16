$(document).ready(function () {
    // Sidebar toggle
    $('#sidebarCollapse').on('click', function () {
        $('#sidebar, #content').toggleClass('active');
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Initialize popovers
    $('[data-toggle="popover"]').popover();

    // Auto-generate slug from title if both fields exist
    if ($('#title').length && $('#slug').length) {
        $('#title').on('keyup', function() {
            let slug = $(this).val()
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/(^-|-$)/g, '');
            $('#slug').val(slug);
        });
    }

    // Image preview for file inputs
    $('.image-upload').on('change', function() {
        const preview = $('#' + $(this).data('preview'));
        
        if (preview.length && this.files && this.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.attr('src', e.target.result);
                preview.css('display', 'block');
            }
            
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Confirm delete actions
    $('.btn-delete').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
            e.preventDefault();
        }
    });

    // Initialize WYSIWYG editors if they exist and CKEditor is loaded
    if (typeof ClassicEditor !== 'undefined') {
        $('.wysiwyg-editor').each(function() {
            ClassicEditor
                .create(this)
                .catch(error => {
                    console.error(error);
                });
        });
    }

    // Initialize datepickers if they exist and flatpickr is loaded
    if (typeof flatpickr !== 'undefined') {
        $('.datepicker').flatpickr({
            enableTime: false,
            dateFormat: 'Y-m-d'
        });
    }

    // Form validation
    $('.needs-validation').on('submit', function(event) {
        if (this.checkValidity() === false) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        $(this).addClass('was-validated');
    });
});
// CloudBlitz Admin Panel - Main JavaScript

$(document).ready(function () {
    // Toggle sidebar
    $('#sidebarCollapse').on('click', function () {
        $('#sidebar').toggleClass('active');
        $('#content').toggleClass('active');
    });

    // Close sidebar on mobile when clicking outside
    $(document).on('click', function (e) {
        const sidebar = $('#sidebar');
        const sidebarCollapse = $('#sidebarCollapse');
        
        // If sidebar is active (visible) on mobile and click is outside sidebar and not on toggle button
        if (sidebar.hasClass('active') && 
            !sidebar.is(e.target) && 
            sidebar.has(e.target).length === 0 && 
            !sidebarCollapse.is(e.target) && 
            sidebarCollapse.has(e.target).length === 0 &&
            $(window).width() <= 768) {
            
            sidebar.removeClass('active');
            $('#content').removeClass('active');
        }
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Initialize popovers
    $('[data-toggle="popover"]').popover();

    // Prevent dropdown menus from closing when clicking inside them
    $('.dropdown-menu').on('click', function (e) {
        e.stopPropagation();
    });

    // Add active class to current sidebar link
    const currentUrl = window.location.pathname;
    const filename = currentUrl.substring(currentUrl.lastIndexOf('/') + 1);
    
    $('#sidebar ul li a').each(function() {
        const href = $(this).attr('href');
        if (href === filename || (filename === '' && href === 'dashboard.php')) {
            $(this).parent().addClass('active');
        }
    });

    // Handle custom file inputs
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
    });

    // Smooth scrolling for anchor links
    $('a.smooth-scroll').on('click', function(event) {
        if (this.hash !== '') {
            event.preventDefault();
            const hash = this.hash;
            $('html, body').animate({
                scrollTop: $(hash).offset().top
            }, 800, function(){
                window.location.hash = hash;
            });
        }
    });

    // Confirm delete actions
    $('.confirm-delete').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
});

// Fix for TinyMCE in Bootstrap modal
$(document).on('focusin', function(e) {
    if ($(e.target).closest(".tox-tinymce-aux, .moxman-window, .tam-assetmanager-root").length) {
        e.stopImmediatePropagation();
    }
});