// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function () {
  const toggle = document.getElementById('hamburger');
  const mobileNav = document.getElementById('mobileNav');
  if (toggle && mobileNav) {
    toggle.addEventListener('click', function () {
      mobileNav.classList.toggle('open');
    });
  }
});
