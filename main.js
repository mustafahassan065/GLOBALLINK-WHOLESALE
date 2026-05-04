// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function () {
  const toggle = document.getElementById('hamburger');
  const mobileNav = document.getElementById('mobileNav');
  if (toggle && mobileNav) {
    toggle.addEventListener('click', function () {
      mobileNav.classList.toggle('open');
    });
  }

  // Newsletter form
  const nlBtn = document.getElementById('nlBtn');
  if (nlBtn) {
    nlBtn.addEventListener('click', function () {
      const input = document.getElementById('nlEmail');
      const msg   = document.getElementById('nlMsg');
      const email = input.value.trim();

      if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        msg.textContent = 'Please enter a valid email.';
        msg.style.display = 'block';
        msg.style.color = '#fc8181';
        return;
      }

      nlBtn.disabled = true;
      nlBtn.textContent = '...';

      const data = new FormData();
      data.append('email', email);

      fetch('newsletter.php', { method: 'POST', body: data })
        .then(function(r){ return r.json(); })
        .then(function(res){
          msg.textContent = res.message;
          msg.style.display = 'block';
          msg.style.color = res.success ? '#9ae6b4' : '#fc8181';
          if (res.success) input.value = '';
        })
        .catch(function(){
          msg.textContent = 'Error. Please try again.';
          msg.style.display = 'block';
          msg.style.color = '#fc8181';
        })
        .finally(function(){
          nlBtn.disabled = false;
          nlBtn.textContent = 'SIGN UP';
        });
    });
  }
});
