/**
 * Main JavaScript for CloudBlitz Academy
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80, // Offset for fixed header
                    behavior: 'smooth'
                });
            }
        });
    });

    // Navbar active state based on scroll position
    const sections = document.querySelectorAll('section[id]');
    
    function navHighlighter() {
        // Get current scroll position
        let scrollY = window.pageYOffset;
        
        // Loop through sections to get height, top and ID values for each
        sections.forEach(current => {
            const sectionHeight = current.offsetHeight;
            const sectionTop = current.offsetTop - 100;
            const sectionId = current.getAttribute('id');
            
            if (scrollY > sectionTop && scrollY <= sectionTop + sectionHeight) {
                document.querySelector('.main-menu a[href*=' + sectionId + ']')?.classList.add('active');
            } else {
                document.querySelector('.main-menu a[href*=' + sectionId + ']')?.classList.remove('active');
            }
        });
    }
    
    // Add event listener for scroll if there are sections
    if (sections.length > 0) {
        window.addEventListener('scroll', navHighlighter);
    }
    
    // Counter animation for statistics
    const counters = document.querySelectorAll('.counter');
    
    function runCounter() {
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target'));
            const count = parseInt(counter.innerText);
            const increment = target / 200; // Adjust for animation speed
            
            if (count < target) {
                counter.innerText = Math.ceil(count + increment);
                setTimeout(runCounter, 10);
            } else {
                counter.innerText = target;
            }
        });
    }
    
    // Run counter animation when elements are in viewport
    const counterSection = document.querySelector('.statistics-section');
    if(counterSection) {
        const observer = new IntersectionObserver((entries) => {
            if(entries[0].isIntersecting) {
                runCounter();
                observer.unobserve(counterSection);
            }
        }, { threshold: 0.5 });
        
        observer.observe(counterSection);
    }
    
    // Form validation and submission
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            } else {
                // If form is valid, you can add AJAX submission here
                if(!form.classList.contains('newsletter-form')) {
                    event.preventDefault();
                    
                    // Show loading state
                    const submitBtn = form.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';
                    
                    // Simulate form submission (replace with actual AJAX)
                    setTimeout(() => {
                        form.reset();
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                        
                        // Show success message
                        const successMessage = document.createElement('div');
                        successMessage.className = 'alert alert-success mt-3';
                        successMessage.innerText = 'Your message has been sent successfully!';
                        form.appendChild(successMessage);
                        
                        // Remove success message after 5 seconds
                        setTimeout(() => {
                            successMessage.remove();
                        }, 5000);
                    }, 1500);
                }
            }
            
            form.classList.add('was-validated');
        }, false);
    });
    
    // Mobile navigation toggle
    const menuToggle = document.querySelector('.navbar-toggler');
    if(menuToggle) {
        menuToggle.addEventListener('click', function() {
            document.body.classList.toggle('menu-open');
        });
    }
    
    // Image lazy loading
    const lazyImages = document.querySelectorAll('img.lazy-load');
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy-load');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        lazyImages.forEach(img => {
            imageObserver.observe(img);
        });
    } else {
        // Fallback for browsers that don't support IntersectionObserver
        lazyImages.forEach(img => {
            img.src = img.dataset.src;
            img.classList.remove('lazy-load');
        });
    }
    
    // Handle testimonial carousel (if exists)
    const testimonialCarousel = document.getElementById('testimonialCarousel');
    if (testimonialCarousel) {
        new bootstrap.Carousel(testimonialCarousel, {
            interval: 5000,
            wrap: true
        });
    }
    
    // Add animation classes when elements come into view
    const animatedElements = document.querySelectorAll('.animate-on-scroll');
    
    if (animatedElements.length > 0) {
        const elementObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                    elementObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        
        animatedElements.forEach(element => {
            elementObserver.observe(element);
        });
    }
});