/**
 * loader.js — Global loading system
 *
 * Exports:
 *   showLoading(msg?)       – Show fullscreen overlay with optional message
 *   hideLoading()           – Hide fullscreen overlay
 *   withLoading(fn, msg?)   – Wrap async fn, auto show/hide loading
 *   showButtonLoading(btn)  – Replace button content with inline spinner
 *   hideButtonLoading(btn, html) – Restore button content
 *
 * Auto-initialises: page-load detection with 300 ms grace delay.
 */
(function () {
  'use strict';

  var overlay = null;
  var loadTimer = null;

  /* ── Create the overlay DOM once ── */
  function ensureOverlay() {
    if (overlay) return;
    overlay = document.createElement('div');
    overlay.className = 'loader-overlay';
    overlay.innerHTML =
      '<div class="loader-card">' +
        '<div class="loader-spinner"></div>' +
        '<p id="loaderMsg">Loading...</p>' +
      '</div>';
    // Click-to-dismiss disabled: overlay.addEventListener('click', hideLoading);
    document.body.appendChild(overlay);
  }

  /* ── Show fullscreen loading overlay ── */
  window.showLoading = function (msg) {
    ensureOverlay();
    var el = document.getElementById('loaderMsg');
    if (el) el.textContent = msg || 'Loading...';
    overlay.classList.add('active');
  };

  /* ── Hide fullscreen loading overlay ── */
  window.hideLoading = function () {
    if (overlay) overlay.classList.remove('active');
  };

  /* ── Wrap an async function with loading overlay ── */
  window.withLoading = function (asyncFn, msg) {
    return function () {
      var args = arguments;
      showLoading(msg);
      var result = asyncFn.apply(this, args);
      if (result && typeof result.then === 'function') {
        return result.then(function (val) {
          hideLoading();
          return val;
        }).catch(function (err) {
          hideLoading();
          throw err;
        });
      }
      // Synchronous fallback
      hideLoading();
      return result;
    };
  };

  /* ── Show inline spinner on a button (disable + spinner) ── */
  window.showButtonLoading = function (btn, loadingText) {
    if (!btn || btn.disabled) return;
    btn._origHTML = btn.innerHTML;
    btn._origDisabled = btn.disabled;
    btn.disabled = true;
    btn.classList.add('loader-btn-loading');
    btn.innerHTML = '<span class="loader-inline"></span>' + (loadingText || 'Please wait...');
  };

  /* ── Restore button after loading ── */
  window.hideButtonLoading = function (btn, html) {
    if (!btn) return;
    btn.disabled = false;
    btn.classList.remove('loader-btn-loading');
    btn.innerHTML = html || btn._origHTML || btn.innerHTML;
  };

  /* ── Auto page-load detection ── */
  function onPageReady() {
    if (loadTimer) { clearTimeout(loadTimer); loadTimer = null; }
    hideLoading();
  }

  // Wait 300 ms before showing loading; if page ready before that, never show
  loadTimer = setTimeout(function () {
    showLoading();
  }, 300);

  if (document.readyState === 'complete') {
    onPageReady();
  } else {
    window.addEventListener('load', onPageReady);
    // Also hide on DOMContentLoaded in case load fires late
    document.addEventListener('DOMContentLoaded', function () {
      if (document.readyState === 'complete') onPageReady();
    });
  }

  // Safety: hide after 10 s max
  setTimeout(function () {
    if (overlay && overlay.classList.contains('active')) {
      hideLoading();
    }
  }, 10000);

})();
