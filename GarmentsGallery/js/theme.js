/**
 * Garments Gallery — Dark / Light Theme Toggle
 */
(function () {
  const STORAGE_KEY = 'gg-theme';
  const root = document.documentElement;

  function getPreferredTheme() {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored === 'light' || stored === 'dark') return stored;
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }

  function applyTheme(theme) {
    root.setAttribute('data-bs-theme', theme);
    localStorage.setItem(STORAGE_KEY, theme);
    document.querySelectorAll('.theme-toggle').forEach(function (btn) {
      btn.setAttribute('aria-label', theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
    });
  }

  function toggleTheme() {
    const current = root.getAttribute('data-bs-theme') || 'light';
    applyTheme(current === 'dark' ? 'light' : 'dark');
  }

  applyTheme(getPreferredTheme());

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.theme-toggle').forEach(function (btn) {
      btn.addEventListener('click', toggleTheme);
    });
  });

  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
    if (!localStorage.getItem(STORAGE_KEY)) {
      applyTheme(e.matches ? 'dark' : 'light');
    }
  });
})();
