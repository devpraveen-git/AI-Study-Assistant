// AI Study Assistant v2 - Core App JS
(function () {
  'use strict';

  // Theme Management
  const root = document.documentElement;
  const currentTheme = localStorage.getItem('v2_theme') || 'dark';
  root.setAttribute('data-bs-theme', currentTheme);

  function syncThemeIcons() {
    const isDark = root.getAttribute('data-bs-theme') === 'dark';
    document.querySelectorAll('.theme-toggle-btn i').forEach(icon => {
      icon.className = isDark ? 'fa-solid fa-moon' : 'fa-solid fa-sun';
    });
  }
  syncThemeIcons();

  document.querySelectorAll('.theme-toggle-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const nextTheme = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-bs-theme', nextTheme);
      localStorage.setItem('v2_theme', nextTheme);
      syncThemeIcons();
    });
  });

  // Mobile Sidebar Toggle
  const sidebar = document.getElementById('appSidebar');
  const sidebarToggle = document.getElementById('sidebarToggleBtn');
  if (sidebar && sidebarToggle) {
    sidebarToggle.addEventListener('click', () => {
      sidebar.classList.toggle('show');
    });

    document.addEventListener('click', (e) => {
      if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target) && sidebar.classList.contains('show')) {
        sidebar.classList.remove('show');
      }
    });
  }

  // Bootstrap Toast auto-show
  document.querySelectorAll('.toast').forEach(toastEl => {
    const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
    toast.show();
  });

  // Landing Page Interactive Demo Showcase
  const demoTabBtns = document.querySelectorAll('.demo-tab-btn');
  demoTabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const targetTab = btn.getAttribute('data-tab');
      demoTabBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      document.querySelectorAll('.demo-pane').forEach(pane => {
        pane.classList.remove('active');
      });
      const targetPane = document.getElementById(`pane-${targetTab}`);
      if (targetPane) {
        targetPane.classList.add('active');
      }
    });
  });

  // Landing Page Interactive Quiz Validation
  const demoOptions = document.querySelectorAll('#demoQuizOptions .demo-quiz-opt');
  const demoResult = document.getElementById('demoQuizResult');
  demoOptions.forEach(opt => {
    opt.addEventListener('click', () => {
      demoOptions.forEach(o => o.classList.remove('correct', 'wrong'));
      const isCorrect = opt.getAttribute('data-correct') === 'true';
      if (isCorrect) {
        opt.classList.add('correct');
      } else {
        opt.classList.add('wrong');
        const correctOpt = document.querySelector('#demoQuizOptions .demo-quiz-opt[data-correct="true"]');
        if (correctOpt) correctOpt.classList.add('correct');
      }
      if (demoResult) demoResult.classList.remove('d-none');
    });
  });

  // Landing Page Interactive 3D Flashcard Flip
  const demoFlashcard = document.getElementById('demoFlashcardInner');
  if (demoFlashcard) {
    demoFlashcard.addEventListener('click', () => {
      demoFlashcard.classList.toggle('is-flipped');
    });
  }
})();
