/**
 * Main JavaScript file for CloudBlitz Education website
 */

document.addEventListener("DOMContentLoaded", () => {
  // Show/hide back to top button based on scroll position
  const backToTopButton = document.querySelector(".back-to-top")
  if (backToTopButton) {
    window.addEventListener("scroll", () => {
      if (window.scrollY > 300) {
        backToTopButton.classList.add("active")
      } else {
        backToTopButton.classList.remove("active")
      }
    })

    // Smooth scroll to top when button is clicked
    backToTopButton.addEventListener("click", (e) => {
      e.preventDefault()
      window.scrollTo({
        top: 0,
        behavior: "smooth",
      })
    })
  }

  // Smooth scroll for the navigation menu and links with .scrollto classes
  document.addEventListener("click", (e) => {
    if (e.target.matches("a.scrollto")) {
      if (
        location.pathname.replace(/^\//, "") == e.target.pathname.replace(/^\//, "") &&
        location.hostname == e.target.hostname
      ) {
        e.preventDefault()
        var targetId = e.target.hash
        var targetElement = document.querySelector(targetId)

        if (targetElement) {
          var scrollto = targetElement.offsetTop
          window.scrollTo({
            top: scrollto,
            behavior: "smooth",
          })
        }
      }
    }
  })

  // Activate smooth scroll on page load with hash links in the url
  if (window.location.hash) {
    var initial_nav = window.location.hash
    var targetElement = document.querySelector(initial_nav)

    if (targetElement) {
      var scrollto = targetElement.offsetTop
      window.scrollTo({
        top: scrollto,
        behavior: "smooth",
      })
    }
  }

  // Mobile Navigation
  const navbar = document.querySelector(".navbar")
  if (navbar) {
    const $mobile_nav = navbar.cloneNode(true)
    $mobile_nav.className = "mobile-nav d-lg-none"
    document.body.appendChild($mobile_nav)

    const toggleButton = document.createElement("button")
    toggleButton.type = "button"
    toggleButton.className = "mobile-nav-toggle d-lg-none"
    toggleButton.innerHTML = '<i class="fa fa-bars"></i>'
    document.body.prepend(toggleButton)

    const overlyDiv = document.createElement("div")
    overlyDiv.className = "mobile-nav-overly"
    document.body.appendChild(overlyDiv)

    toggleButton.addEventListener("click", (e) => {
      document.body.classList.toggle("mobile-nav-active")
      toggleButton.querySelector("i").classList.toggle("fa-times")
      toggleButton.querySelector("i").classList.toggle("fa-bars")
      overlyDiv.style.display = document.body.classList.contains("mobile-nav-active") ? "block" : "none"
    })

    const dropDownLinks = document.querySelectorAll(".mobile-nav .drop-down > a")
    dropDownLinks.forEach((link) => {
      link.addEventListener("click", function (e) {
        e.preventDefault()
        const nextElement = this.nextElementSibling
        if (nextElement) {
          nextElement.style.display = nextElement.style.display === "block" ? "none" : "block"
        }
        this.parentNode.classList.toggle("active")
      })
    })

    document.addEventListener("click", (e) => {
      const container = document.querySelector(".mobile-nav")
      if (container && !container.contains(e.target) && e.target !== toggleButton && !toggleButton.contains(e.target)) {
        if (document.body.classList.contains("mobile-nav-active")) {
          document.body.classList.remove("mobile-nav-active")
          toggleButton.querySelector("i").classList.toggle("fa-times")
          toggleButton.querySelector("i").classList.toggle("fa-bars")
          overlyDiv.style.display = "none"
        }
      }
    })
  }

  // Course modules accordion functionality
  const moduleButtons = document.querySelectorAll(".course-modules .btn-link")
  if (moduleButtons.length > 0) {
    moduleButtons.forEach((button) => {
      button.addEventListener("click", function () {
        // Toggle aria-expanded attribute for accessibility
        const expanded = this.getAttribute("aria-expanded") === "true"
        this.setAttribute("aria-expanded", !expanded)
      })
    })
  }

  // Form validation for contact form
  const contactForm = document.querySelector(".contact-form form")
  if (contactForm) {
    contactForm.addEventListener("submit", (e) => {
      let isValid = true
      const nameInput = document.getElementById("name")
      const emailInput = document.getElementById("email")
      const messageInput = document.getElementById("message")

      // Simple validation - check if fields are empty
      if (!nameInput.value.trim()) {
        isValid = false
        highlightField(nameInput)
      } else {
        resetField(nameInput)
      }

      if (!emailInput.value.trim()) {
        isValid = false
        highlightField(emailInput)
      } else if (!isValidEmail(emailInput.value)) {
        isValid = false
        highlightField(emailInput)
      } else {
        resetField(emailInput)
      }

      if (!messageInput.value.trim()) {
        isValid = false
        highlightField(messageInput)
      } else {
        resetField(messageInput)
      }

      if (!isValid) {
        e.preventDefault()
        displayFormError("Please fill in all fields correctly.")
      }
    })
  }

  // Form validation for enrollment form
  const enrollmentForm = document.querySelector(".enrollment-form form")
  if (enrollmentForm) {
    enrollmentForm.addEventListener("submit", (e) => {
      let isValid = true
      const nameInput = document.getElementById("name")
      const emailInput = document.getElementById("email")
      const phoneInput = document.getElementById("phone")

      // Simple validation - check if fields are empty
      if (!nameInput.value.trim()) {
        isValid = false
        highlightField(nameInput)
      } else {
        resetField(nameInput)
      }

      if (!emailInput.value.trim()) {
        isValid = false
        highlightField(emailInput)
      } else if (!isValidEmail(emailInput.value)) {
        isValid = false
        highlightField(emailInput)
      } else {
        resetField(emailInput)
      }

      if (!phoneInput.value.trim()) {
        isValid = false
        highlightField(phoneInput)
      } else {
        resetField(phoneInput)
      }

      if (!isValid) {
        e.preventDefault()
        displayFormError("Please fill in all fields correctly.")
      }
    })
  }

  // Helper function to validate email format
  function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return emailRegex.test(email)
  }

  // Helper function to highlight invalid fields
  function highlightField(field) {
    field.classList.add("is-invalid")
  }

  // Helper function to reset field styling
  function resetField(field) {
    field.classList.remove("is-invalid")
  }

  // Helper function to display form error message
  function displayFormError(message) {
    // Check if error message already exists
    const existingError = document.querySelector(".form-error")
    if (existingError) {
      existingError.textContent = message
    } else {
      const errorDiv = document.createElement("div")
      errorDiv.className = "alert alert-danger form-error mt-3"
      errorDiv.textContent = message

      // Insert after the form
      const form = document.querySelector("form")
      form.parentNode.insertBefore(errorDiv, form.nextSibling)
    }
  }

  // Initialize tooltips
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-toggle="tooltip"]'))
  if (tooltipTriggerList.length > 0) {
    // Check if bootstrap is defined
    let bootstrap // Declare bootstrap variable
    if (typeof bootstrap !== "undefined") {
      tooltipTriggerList.map((tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl))
    } else {
      console.error("Bootstrap is not defined. Ensure Bootstrap is properly loaded.")
    }
  }

  // Social sharing functionality
  const shareButtons = document.querySelectorAll(".social-share a")
  if (shareButtons.length > 0) {
    shareButtons.forEach((button) => {
      button.addEventListener("click", function (e) {
        e.preventDefault()
        window.open(this.href, "_blank", "width=600,height=400")
      })
    })
  }
})
// Add this to your footer.php or a separate JS file
document.addEventListener('DOMContentLoaded', function() {
    // Fix for Bootstrap tabs
    var tabLinks = document.querySelectorAll('.nav-tabs .nav-link');
    
    tabLinks.forEach(function(tabLink) {
        tabLink.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all tabs
            tabLinks.forEach(function(link) {
                link.classList.remove('active');
                var tabContent = document.querySelector(link.getAttribute('href'));
                if (tabContent) {
                    tabContent.classList.remove('show', 'active');
                }
            });
            
            // Add active class to clicked tab
            this.classList.add('active');
            var targetTab = document.querySelector(this.getAttribute('href'));
            if (targetTab) {
                targetTab.classList.add('show', 'active');
            }
        });
    });
});