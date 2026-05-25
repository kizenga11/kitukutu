/**
 * Universal form loading-state handler.
 * Disables the submit button and shows a spinner on form submission.
 * Add data-loading-text="Saving..." to a button for a custom label.
 */
(function () {
  'use strict';

  var style = document.createElement('style');
  style.textContent = [
    '@keyframes _fsp{to{transform:rotate(360deg)}}',
    '._fspin{display:inline-block;width:1em;height:1em;border:2px solid currentColor;',
    'border-top-color:transparent;border-radius:50%;',
    'animation:_fsp .65s linear infinite;vertical-align:-.15em;margin-right:.4em;}'
  ].join('');
  document.head.appendChild(style);

  function attachForm(form) {
    form.addEventListener('submit', function (e) {
      // Skip if already submitted (prevent double-fire)
      if (form.dataset.submitting) return;

      // Find which button triggered this (set in mousedown/keydown)
      var btn = form._triggerBtn || form.querySelector('button[type="submit"], input[type="submit"]');
      if (!btn || btn.disabled) return;

      form.dataset.submitting = '1';

      // Preserve button name/value before disabling (disabled controls aren't submitted)
      if (btn.name) {
        var hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name  = btn.name;
        hidden.value = btn.value || '';
        form.appendChild(hidden);
      }

      var originalHTML = btn.innerHTML;
      var loadingText  = btn.getAttribute('data-loading-text') || 'Please wait...';

      btn.disabled = true;
      btn.innerHTML = '<span class="_fspin"></span>' + loadingText;

      // Safety valve: re-enable after 15 s in case navigation never happens
      setTimeout(function () {
        btn.disabled = false;
        btn.innerHTML = originalHTML;
        delete form.dataset.submitting;
      }, 15000);
    });

    // Track which submit button was clicked (multi-button forms)
    form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (btn) {
      btn.addEventListener('mousedown', function () { form._triggerBtn = btn; });
      btn.addEventListener('keydown',   function () { form._triggerBtn = btn; });
    });
  }

  function init() {
    document.querySelectorAll('form').forEach(attachForm);

    // Support forms added dynamically (modals, etc.)
    if (window.MutationObserver) {
      new MutationObserver(function (mutations) {
        mutations.forEach(function (m) {
          m.addedNodes.forEach(function (node) {
            if (node.nodeType !== 1) return;
            if (node.tagName === 'FORM') { attachForm(node); }
            node.querySelectorAll && node.querySelectorAll('form').forEach(attachForm);
          });
        });
      }).observe(document.body, { childList: true, subtree: true });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
