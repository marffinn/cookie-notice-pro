(function () {
  'use strict';

  function initCookieNotice() {
    var cfg = window.CNPSettings || {};
    var banner = document.getElementById('cnp-banner');
    if (!banner) return;

    var reopen = document.getElementById('cnp-reopen');
    var cookieName = cfg.cookieName || 'cnp_consent';

    function readConsent() {
      var prefix = cookieName + '=';
      var found = document.cookie.split(';').map(function (v) { return v.trim(); }).filter(function (v) { return v.indexOf(prefix) === 0; })[0];
      if (!found) return null;
      try { return JSON.parse(decodeURIComponent(found.substring(prefix.length))); } catch (e) { return null; }
    }

    function writeConsent(consent) {
      var days = Math.max(1, parseInt(cfg.days, 10) || 180);
      var secure = location.protocol === 'https:' ? '; Secure' : '';
      document.cookie = cookieName + '=' + encodeURIComponent(JSON.stringify(consent)) + '; Max-Age=' + (days * 86400) + '; Path=/; SameSite=Lax' + secure;
    }

    function activateScripts(consent) {
      ['preferences', 'analytics', 'marketing'].forEach(function (category) {
        if (!consent[category]) return;
        document.querySelectorAll('script[type="text/plain"][data-cookie-category="' + category + '"]').forEach(function (oldScript) {
          if (oldScript.dataset.cnpActivated === '1') return;
          var fresh = document.createElement('script');
          Array.prototype.forEach.call(oldScript.attributes, function (attr) {
            if (attr.name !== 'type' && attr.name !== 'data-cookie-category' && attr.name !== 'data-cnp-activated') fresh.setAttribute(attr.name, attr.value);
          });
          fresh.text = oldScript.textContent;
          oldScript.dataset.cnpActivated = '1';
          oldScript.parentNode.replaceChild(fresh, oldScript);
        });
      });
    }

    function applyConsent(consent) {
      writeConsent(consent);
      banner.hidden = true;
      if (reopen && cfg.showReopen) reopen.hidden = false;
      activateScripts(consent);
      window.dispatchEvent(new CustomEvent('cnp_consent_changed', { detail: consent }));
    }

    function openSettings() {
      banner.hidden = false;
      banner.querySelector('.cnp-main').hidden = true;
      banner.querySelector('.cnp-preferences').hidden = false;
      var current = readConsent() || {};
      ['preferences', 'analytics', 'marketing'].forEach(function (key) {
        var input = banner.querySelector('[data-cnp-category="' + key + '"]');
        if (input) input.checked = !!current[key];
      });
      if (reopen) reopen.hidden = true;
    }

    function showMain() {
      banner.hidden = false;
      banner.querySelector('.cnp-main').hidden = false;
      banner.querySelector('.cnp-preferences').hidden = true;
    }

    banner.addEventListener('click', function (event) {
      var button = event.target.closest('[data-cnp-action]');
      if (!button) return;
      var action = button.getAttribute('data-cnp-action');
      if (action === 'accept') applyConsent({ necessary: true, preferences: true, analytics: true, marketing: true, updated: Date.now() });
      if (action === 'reject') applyConsent({ necessary: true, preferences: false, analytics: false, marketing: false, updated: Date.now() });
      if (action === 'open-settings') openSettings();
      if (action === 'back') showMain();
      if (action === 'save') {
        var consent = { necessary: true, updated: Date.now() };
        ['preferences', 'analytics', 'marketing'].forEach(function (key) {
          consent[key] = !!banner.querySelector('[data-cnp-category="' + key + '"]').checked;
        });
        applyConsent(consent);
      }
    });

    if (reopen) reopen.addEventListener('click', openSettings);
    window.CNPOpenSettings = openSettings;

    var existing = readConsent();
    if (existing && existing.necessary) {
      banner.hidden = true;
      if (reopen && cfg.showReopen) reopen.hidden = false;
      activateScripts(existing);
    } else {
      banner.hidden = false;
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCookieNotice);
  } else {
    initCookieNotice();
  }
})();
