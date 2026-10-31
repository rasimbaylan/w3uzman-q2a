(function () {
  'use strict';
  var form = document.getElementById('seo-form');
  if (!form) return;
  var button = document.getElementById('seo-submit');
  form.addEventListener('submit', function () {
    if (!form.checkValidity()) return;
    button.disabled = true;
    form.setAttribute('aria-busy', 'true');
    document.getElementById('seo-busy').hidden = false;
  });
  window.addEventListener('pageshow', function () {
    button.disabled = false;
    form.removeAttribute('aria-busy');
    document.getElementById('seo-busy').hidden = true;
  });
}());
