// app.js — SAGMS Frontend JS

// Close sidebar when clicking outside on mobile
document.addEventListener('click', (e) => {
  const sidebar = document.getElementById('sidebar');
  const toggle  = document.querySelector('.menu-toggle');
  if (sidebar && sidebar.classList.contains('open')) {
    if (!sidebar.contains(e.target) && e.target !== toggle) {
      sidebar.classList.remove('open');
    }
  }
});

// Auto-dismiss alerts after 5s
document.querySelectorAll('.alert').forEach(el => {
  setTimeout(() => {
    el.style.transition = 'opacity .4s';
    el.style.opacity = '0';
    setTimeout(() => el.remove(), 400);
  }, 5000);
});

// Animate progress bars on load
const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      const fill = entry.target;
      const width = fill.style.width;
      fill.style.width = '0';
      requestAnimationFrame(() => {
        fill.style.transition = 'width .7s cubic-bezier(.4,0,.2,1)';
        fill.style.width = width;
      });
      observer.unobserve(fill);
    }
  });
}, { threshold: 0.1 });

document.querySelectorAll('.pbar-fill').forEach(el => observer.observe(el));

// Table row highlight on click (for selection feedback)
document.querySelectorAll('.data-table tbody tr').forEach(row => {
  row.style.cursor = 'default';
  row.addEventListener('click', function(e) {
    if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON' ||
        e.target.tagName === 'INPUT') return;
    document.querySelectorAll('.data-table tbody tr.selected')
            .forEach(r => r.classList.remove('selected'));
    this.classList.add('selected');
  });
});

// Add selected style dynamically
const style = document.createElement('style');
style.textContent = `
  .data-table tbody tr.selected { background: var(--accent-dim) !important; }
  .data-table tbody tr.selected td { color: var(--text) !important; }
`;
document.head.appendChild(style);

