document.addEventListener('DOMContentLoaded', function () {
  // Dark mode toggle
  var themeToggle = document.getElementById('themeToggle');
  if (themeToggle) {
    themeToggle.addEventListener('click', function () {
      var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
      if (isDark) {
        document.documentElement.removeAttribute('data-theme');
        try { localStorage.setItem('theme', 'light'); } catch (e) {}
      } else {
        document.documentElement.setAttribute('data-theme', 'dark');
        try { localStorage.setItem('theme', 'dark'); } catch (e) {}
      }
    });
  }

  // Mobile nav toggle
  var navToggle = document.getElementById('navToggle');
  var navLinks = document.getElementById('navLinks');
  if (navToggle && navLinks) {
    navToggle.addEventListener('click', function () {
      var open = navLinks.classList.toggle('open');
      navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  // Password visibility toggles
  document.querySelectorAll('.password-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.getElementById(btn.dataset.target);
      if (!input) return;
      var isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
      btn.textContent = isPassword ? 'Hide' : 'Show';
    });
  });

  // Confirmation dialogs for destructive actions
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('submit', function (e) {
      if (!confirm(el.dataset.confirm)) {
        e.preventDefault();
      }
    });
  });
  document.querySelectorAll('a[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!confirm(el.dataset.confirm)) {
        e.preventDefault();
      }
    });
  });

  // Client-side date validation: end date >= start date
  document.querySelectorAll('form[data-date-range]').forEach(function (form) {
    var start = form.querySelector('[name="start_date"]');
    var end = form.querySelector('[name="end_date"]');
    if (start && end) {
      function validateRange() {
        if (start.value && end.value && end.value < start.value) {
          end.setCustomValidity('End date cannot be before start date.');
        } else {
          end.setCustomValidity('');
        }
      }
      start.addEventListener('change', validateRange);
      end.addEventListener('change', validateRange);
    }
  });

  // Live expense total preview on expense form
  var expenseForm = document.getElementById('expenseForm');
  if (expenseForm) {
    var amountInput = expenseForm.querySelector('[name="amount"]');
    var preview = document.getElementById('expensePreview');
    if (amountInput && preview) {
      amountInput.addEventListener('input', function () {
        var val = parseFloat(amountInput.value);
        preview.textContent = isNaN(val) ? '' : 'Amount: ' + val.toFixed(2);
      });
    }
  }

  // Trip template quick-fill on Create Trip page
  var templateRow = document.getElementById('templateRow');
  if (templateRow) {
    templateRow.querySelectorAll('.chip').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var nameInput = document.getElementById('name');
        var descInput = document.getElementById('description');
        if (nameInput && !nameInput.value) nameInput.value = btn.dataset.name;
        if (descInput && !descInput.value) descInput.value = btn.dataset.desc;
        templateRow.querySelectorAll('.chip').forEach(function (c) { c.classList.remove('chip-active'); });
        btn.classList.add('chip-active');
      });
    });
  }

  // Required-field basic client-side validation feedback (HTML5 handles most;
  // this just ensures novalidate isn't silently skipping required attrs)
  document.querySelectorAll('form[data-validate]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      var invalid = form.querySelector(':invalid');
      if (invalid) {
        invalid.focus();
      }
    });
  });
});
