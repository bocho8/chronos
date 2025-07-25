document.addEventListener('DOMContentLoaded', function() {
  const menuIcon = document.querySelector('.menu-icon');
  const sidebar = document.querySelector('.sidebar');
  if (menuIcon && sidebar) {
    menuIcon.addEventListener('click', () => {
      sidebar.classList.toggle('open');
    });
  }
}); 