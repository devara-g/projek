function toggleMenu() {
  const navLinks = document.querySelector(".nav-links");
  const mobileMenu = document.querySelector(".mobile-menu");

  navLinks.classList.toggle("active");
  mobileMenu.classList.toggle("active");
}

// Scroll header effect
window.addEventListener("scroll", function () {
  const header = document.querySelector("header");
  if (window.scrollY > 50) {
    header.classList.add("scrolled");
  } else {
    header.classList.remove("scrolled");
  }
});

document.addEventListener("DOMContentLoaded", function () {
  // Mobile menu toggle
  const mobileMenu = document.querySelector(".mobile-menu");
  if (mobileMenu) {
    mobileMenu.addEventListener("click", toggleMenu);
  }

  // Close mobile menu when clicking outside
  document.addEventListener("click", function (event) {
    const navLinks = document.querySelector(".nav-links");
    const mobileMenuBtn = document.querySelector(".mobile-menu");
    const nav = document.querySelector("nav");

    if (navLinks && navLinks.classList.contains("active")) {
      if (!nav.contains(event.target)) {
        navLinks.classList.remove("active");
        mobileMenuBtn.classList.remove("active");
      }
    }
  });

  // Close mobile menu when clicking a link
  const navLinksItems = document.querySelectorAll(".nav-links a");
  navLinksItems.forEach((link) => {
    link.addEventListener("click", function (e) {
      const navLinks = document.querySelector(".nav-links");
      const mobileMenuBtn = document.querySelector(".mobile-menu");

      // Don't close menu if it's a dropdown parent link on mobile
      const isDropdownParent =
        this.parentElement.classList.contains("dropdown") &&
        this.nextElementSibling &&
        this.nextElementSibling.classList.contains("dropdown-menu") &&
        window.innerWidth <= 768;

      if (!isDropdownParent && navLinks.classList.contains("active")) {
        navLinks.classList.remove("active");
        mobileMenuBtn.classList.remove("active");

        // Close all dropdowns
        document.querySelectorAll(".dropdown").forEach((dropdown) => {
          dropdown.classList.remove("active");
        });
      }
    });
  });

  // Dropdown functionality
  const dropdowns = document.querySelectorAll(".dropdown");
  dropdowns.forEach((dropdown) => {
    const dropdownLink = dropdown.querySelector("a");
    const dropdownMenu = dropdown.querySelector(".dropdown-menu");

    if (dropdownLink && dropdownMenu) {
      // For mobile - click to toggle
      dropdownLink.addEventListener("click", function (e) {
        // Only prevent default on mobile or when menu is present
        if (window.innerWidth <= 768) {
          e.preventDefault();
          e.stopPropagation();

          // Close other dropdowns
          dropdowns.forEach((otherDropdown) => {
            if (otherDropdown !== dropdown) {
              otherDropdown.classList.remove("active");
            }
          });

          // Toggle current dropdown
          dropdown.classList.toggle("active");
        }
      });

      // For desktop - hover
      if (window.innerWidth > 768) {
        dropdown.addEventListener("mouseenter", () => {
          dropdownMenu.style.opacity = "1";
          dropdownMenu.style.visibility = "visible";
          dropdownMenu.style.transform = "translateY(0)";
        });
        dropdown.addEventListener("mouseleave", () => {
          dropdownMenu.style.opacity = "0";
          dropdownMenu.style.visibility = "hidden";
          dropdownMenu.style.transform = "translateY(10px)";
        });
      }
    }
  });

  // Parallax effect and logo rotation for hero section
  const heroSection = document.querySelector(".hero");
  const heroLogo = document.querySelector(".principal-photo");

  if (heroSection) {
    window.addEventListener("scroll", () => {
      const scrollPosition = window.scrollY;

      // background parallax
      heroSection.style.backgroundPositionY = scrollPosition * 0.3 + "px";
    });
  }

  // Smooth scroll for anchor links
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute("href"));
      if (target) {
        target.scrollIntoView({
          behavior: "smooth",
          block: "start",
        });
      }
    });
  });

  // Animation on scroll
  const observerOptions = {
    threshold: 0.1,
    rootMargin: "0px 0px -50px 0px",
  };

  const observer = new IntersectionObserver(function (entries) {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.style.opacity = "1";
        entry.target.style.transform = "translateY(0)";
      }
    });
  }, observerOptions);

  // Observe elements for animation
  const animatedElements = document.querySelectorAll(
    ".news-card, .about-card, .gallery-item, .structure-card, .contact-info-card, .contact-form-card, .map-wrapper, .contact-item",
  );
  animatedElements.forEach((el) => {
    el.style.opacity = "0";
    el.style.transform = "translateY(20px)";
    const delay = el.dataset.delay || "0s";
    el.style.transition = `opacity 0.6s ease ${delay}, transform 0.6s ease ${delay}`;
    observer.observe(el);
  });



  // Admin button
  const adminBtn = document.querySelector(".admin-btn");
  if (adminBtn) {
    adminBtn.addEventListener("click", () => {
      console.log("Redirecting to admin panel...");
    });
  }

  // Counter Animation
  const counters = document.querySelectorAll(".counter");
  const counterObserverOptions = {
    threshold: 0.5,
    rootMargin: "0px",
  };

  const counterObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const counter = entry.target;
        const target = +counter.getAttribute("data-target");
        const duration = 2000; // 2 seconds
        const increment = target / (duration / 16); // 60fps

        let currentCount = 0;
        const updateCount = () => {
          if (currentCount < target) {
            currentCount += increment;
            counter.innerText = Math.ceil(currentCount);
            requestAnimationFrame(updateCount);
          } else {
            counter.innerText = target;
          }
        };

        updateCount();
        observer.unobserve(counter);
      }
    });
  }, counterObserverOptions);

  counters.forEach((counter) => {
    counterObserver.observe(counter);
  });

  // Timeline Animation (Visi Misi)
  const timelineObserverOptions = {
    threshold: 0.2,
    rootMargin: "0px",
  };

  const timelineObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.style.opacity = "1";
        entry.target.style.transform = "";
        observer.unobserve(entry.target);
      }
    });
  }, timelineObserverOptions);

  const timelineCards = document.querySelectorAll(".timeline-card");
  const timelineNumbers = document.querySelectorAll(".timeline-number");

  timelineCards.forEach((card) => {
    // Check if it's left or right
    const col = card.closest(".timeline-col");
    if (!col) return;

    const isLeft = col.classList.contains("left");

    card.style.opacity = "0";
    card.style.transition = "all 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275)"; // bouncy

    if (isLeft) {
      card.style.transform = "translateX(-50px)";
    } else {
      card.style.transform = "translateX(50px)";
    }

    timelineObserver.observe(card);
  });

  timelineNumbers.forEach((num) => {
    num.style.opacity = "0";
    num.style.transform = "scale(0.5)";
    num.style.transition = "all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275)";
    num.style.transitionDelay = "0.2s";
    timelineObserver.observe(num);
  });

  // Animate Timeline Line (Vertical)
  const timelineLine = document.querySelector(".timeline-line");
  if (timelineLine) {
    timelineLine.style.transform = "translateX(-50%) scaleY(0)";
    timelineLine.style.transformOrigin = "top";
    timelineLine.style.transition = "transform 1.5s ease-out";

    const lineObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.style.transform = "translateX(-50%) scaleY(1)";
            lineObserver.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.1 },
    );

    lineObserver.observe(timelineLine);
  }

  // Animate List Items in Timeline Cards (staggered)
  const timelineListItems = document.querySelectorAll(".timeline-card li");
  timelineListItems.forEach((item) => {
    item.style.opacity = "0";
    item.style.transform = "translateX(20px)";
    item.style.transition = "all 0.5s ease";

    // Calculate delay based on index within its own list
    const index = Array.from(item.parentNode.children).indexOf(item);
    item.style.transitionDelay = `${index * 0.1 + 0.3}s`; // Start after card animates

    timelineObserver.observe(item);
  });
});
