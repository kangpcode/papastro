/**
 * Papastro Official Landing Page & Documentation Scripts
 * @author kangpcode (Dhafa Nazula Permadi)
 * License: MIT
 */

document.addEventListener('DOMContentLoaded', () => {
  // Mobile Navigation Toggle
  const mobileToggle = document.querySelector('.mobile-toggle');
  const navMenu = document.querySelector('.nav-menu');

  if (mobileToggle && navMenu) {
    mobileToggle.addEventListener('click', () => {
      navMenu.classList.toggle('open');
    });

    // Close menu when clicking links
    navMenu.querySelectorAll('.nav-link').forEach(link => {
      link.addEventListener('click', () => {
        navMenu.classList.remove('open');
      });
    });
  }

  // Toast Notification System
  const toastContainer = document.querySelector('.toast-container');
  window.showToast = function(message, duration = 3000) {
    if (!toastContainer) return;
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = `
      <span class="toast-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
      </span>
      <span class="toast-text">${message}</span>
    `;
    toastContainer.appendChild(toast);

    // Trigger animate
    setTimeout(() => toast.classList.add('show'), 10);

    setTimeout(() => {
      toast.classList.remove('show');
      setTimeout(() => toast.remove(), 300);
    }, duration);
  };

  // Copy to Clipboard Buttons
  document.querySelectorAll('[data-copy]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const targetText = btn.getAttribute('data-copy');
      if (!targetText) return;

      navigator.clipboard.writeText(targetText).then(() => {
        const label = btn.getAttribute('data-copy-label') || 'Copied to clipboard!';
        window.showToast(label);

        // Feedback icon or text change if present
        const origHtml = btn.innerHTML;
        btn.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg> Copied!`;
        setTimeout(() => {
          btn.innerHTML = origHtml;
        }, 2000);
      }).catch(err => {
        console.error('Failed to copy', err);
      });
    });
  });

  // Documentation Sidebar Tabs
  const docTabs = document.querySelectorAll('.doc-tab-btn');
  const docPanes = document.querySelectorAll('.doc-pane');

  if (docTabs.length && docPanes.length) {
    docTabs.forEach(tab => {
      tab.addEventListener('click', () => {
        const targetId = tab.getAttribute('data-target');
        
        docTabs.forEach(t => t.classList.remove('active'));
        docPanes.forEach(p => p.classList.remove('active'));

        tab.classList.add('active');
        const targetPane = document.getElementById(targetId);
        if (targetPane) {
          targetPane.classList.add('active');
        }
      });
    });
  }

  // Active Link on Scroll
  const sections = document.querySelectorAll('section[id]');
  const navLinks = document.querySelectorAll('.nav-menu .nav-link');

  window.addEventListener('scroll', () => {
    let current = '';
    const scrollY = window.pageYOffset;

    sections.forEach(section => {
      const sectionHeight = section.offsetHeight;
      const sectionTop = section.offsetTop - 120;
      const sectionId = section.getAttribute('id');

      if (scrollY > sectionTop && scrollY <= sectionTop + sectionHeight) {
        current = sectionId;
      }
    });

    navLinks.forEach(link => {
      link.classList.remove('active');
      if (link.getAttribute('href') === `#${current}`) {
        link.classList.add('active');
      }
    });
  });

  // Number Counter Animation for Stats
  const statValues = document.querySelectorAll('.stat-value[data-target]');
  let counted = false;

  const countUp = () => {
    statValues.forEach(el => {
      const target = parseFloat(el.getAttribute('data-target'));
      const suffix = el.getAttribute('data-suffix') || '';
      const prefix = el.getAttribute('data-prefix') || '';
      const duration = 1200;
      const stepTime = 20;
      const steps = duration / stepTime;
      const inc = target / steps;
      let current = 0;

      const timer = setInterval(() => {
        current += inc;
        if (current >= target) {
          current = target;
          clearInterval(timer);
        }
        el.textContent = `${prefix}${current.toLocaleString()}${suffix}`;
      }, stepTime);
    });
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting && !counted) {
        counted = true;
        countUp();
      }
    });
  }, { threshold: 0.3 });

  const statsBar = document.querySelector('.hero-stats-bar');
  if (statsBar) {
    observer.observe(statsBar);
  }
});

