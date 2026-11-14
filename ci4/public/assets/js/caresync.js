/**
 * CareSync UI behaviour.
 * Replaces ui-consistency.js. Plain ES5-safe script, no build step, no module.
 */
(function () {
  'use strict';

  var body = document.body;

  /* ---------------------------------------------------------------- cookies */
  function setPref(name, value) {
    document.cookie = name + '=' + encodeURIComponent(value) + ';path=/;max-age=31536000;samesite=lax';
  }

  function getPref(name) {
    var match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
    return match ? decodeURIComponent(match[1]) : null;
  }

  /* ------------------------------------------------------- sidebar collapse */
  var collapseBtn = document.querySelector('[data-cs-collapse]');
  if (collapseBtn) {
    collapseBtn.addEventListener('click', function () {
      var collapsed = body.classList.toggle('cs-nav-collapsed');
      setPref('cs_nav', collapsed ? 'collapsed' : 'open');
      collapseBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    });
  }

  /* --------------------------------------------------------- mobile drawer */
  var navToggle = document.querySelector('[data-cs-navtoggle]');
  var scrim = document.querySelector('[data-cs-scrim]');

  function closeDrawer() {
    body.classList.remove('cs-nav-open');
    if (navToggle) navToggle.setAttribute('aria-expanded', 'false');
  }

  if (navToggle) {
    navToggle.addEventListener('click', function () {
      var open = body.classList.toggle('cs-nav-open');
      navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  if (scrim) scrim.addEventListener('click', closeDrawer);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && body.classList.contains('cs-nav-open')) closeDrawer();
  });

  /* ------------------------------------------------------ active nav link */
  // Server-side marking wins; this is the fallback for pages that don't pass
  // an active key. Longest matching path wins so /staff/payments/create still
  // highlights /staff/payments.
  (function markActive() {
    if (document.querySelector('.cs-nav__link.is-active')) return;

    var here = window.location.pathname.replace(/\/+$/, '') || '/';
    var links = document.querySelectorAll('.cs-nav__link[href]');
    var best = null;
    var bestLen = -1;

    Array.prototype.forEach.call(links, function (link) {
      if (link.classList.contains('cs-nav__link--exit')) return;

      var path;
      try {
        path = new URL(link.getAttribute('href'), window.location.origin).pathname;
      } catch (err) {
        return;
      }
      path = path.replace(/\/+$/, '') || '/';

      var hit = here === path || (path !== '/' && here.indexOf(path + '/') === 0);
      if (hit && path.length > bestLen) {
        best = link;
        bestLen = path.length;
      }
    });

    if (best) {
      best.classList.add('is-active');
      best.setAttribute('aria-current', 'page');
    }
  })();

  /* ---------------------------------------------------- table density mode */
  var densityBtn = document.querySelector('[data-cs-density]');
  if (densityBtn) {
    var applyDensity = function (compact) {
      Array.prototype.forEach.call(document.querySelectorAll('.cs-table'), function (t) {
        t.classList.toggle('cs-table--compact', compact);
      });
      densityBtn.setAttribute('aria-pressed', compact ? 'true' : 'false');
    };

    applyDensity(getPref('cs_density') === 'compact');

    densityBtn.addEventListener('click', function () {
      var compact = getPref('cs_density') !== 'compact';
      setPref('cs_density', compact ? 'compact' : 'roomy');
      applyDensity(compact);
    });
  }

  /* --------------------------------------------------------------- toasts */
  function toastHost() {
    var host = document.querySelector('.cs-toasts');
    if (!host) {
      host = document.createElement('div');
      host.className = 'cs-toasts';
      host.setAttribute('role', 'status');
      host.setAttribute('aria-live', 'polite');
      document.body.appendChild(host);
    }
    return host;
  }

  window.csToast = function (message, kind) {
    var icons = { ok: 'ti-circle-check', stop: 'ti-alert-circle', info: 'ti-info-circle' };
    var type = kind || 'info';

    var el = document.createElement('div');
    el.className = 'cs-toast cs-toast--' + type;
    el.innerHTML = '<i class="ti ' + (icons[type] || icons.info) + '"></i><span></span>';
    el.querySelector('span').textContent = message;

    toastHost().appendChild(el);
    setTimeout(function () {
      el.style.transition = 'opacity 180ms linear';
      el.style.opacity = '0';
      setTimeout(function () { el.remove(); }, 200);
    }, 4200);
  };

  // Flash messages promote themselves to toasts, then stop stacking up the page.
  Array.prototype.forEach.call(document.querySelectorAll('[data-cs-flash]'), function (el) {
    window.csToast(el.textContent.trim(), el.getAttribute('data-cs-flash'));
    el.remove();
  });

  /* ----------------------------------------------- destructive confirmation */
  // Money and member records get deleted here. Ask first, and say what will go.
  // Anything with data-cs-confirm="message" asks through the shared dialog
  // (partials/confirm_dialog.php). Optional: data-cs-confirm-title,
  // data-cs-confirm-ok, data-cs-confirm-danger (red style; btn-danger buttons
  // get it automatically). After OK the form is submitted or the link is
  // followed exactly once.
  var confirmDialog = null;

  function getConfirmDialog() {
    if (confirmDialog) return confirmDialog;

    var dlg = document.getElementById('cs-confirm');
    if (!dlg) {
      dlg = document.createElement('dialog');
      dlg.className = 'cs-confirm';
      dlg.id = 'cs-confirm';
      dlg.setAttribute('aria-labelledby', 'cs-confirm-title');
      dlg.setAttribute('aria-describedby', 'cs-confirm-message');
      dlg.innerHTML =
        '<form method="dialog" class="cs-confirm__box">' +
        '<div class="cs-confirm__icon" data-cs-confirm-icon aria-hidden="true"><i class="ti ti-help-circle"></i></div>' +
        '<h2 class="cs-confirm__title" id="cs-confirm-title" data-cs-confirm-title>Please confirm</h2>' +
        '<p class="cs-confirm__message" id="cs-confirm-message" data-cs-confirm-message></p>' +
        '<div class="cs-confirm__actions">' +
        '<button type="button" class="btn btn-ghost" data-cs-confirm-cancel>Cancel</button>' +
        '<button type="button" class="btn btn-primary" data-cs-confirm-accept>Confirm</button>' +
        '</div></form>';
      document.body.appendChild(dlg);
    }

    confirmDialog = dlg;
    return dlg;
  }

  // Opens the dialog and calls onOk once if the person accepts. Cancel, Esc
  // and a click on the backdrop all just close it.
  function askConfirm(opts, onOk) {
    var dlg = getConfirmDialog();
    var okBtn = dlg.querySelector('[data-cs-confirm-accept]');
    var cancelBtn = dlg.querySelector('[data-cs-confirm-cancel]');
    var accepted = false;

    dlg.querySelector('[data-cs-confirm-title]').textContent = opts.title || 'Please confirm';
    dlg.querySelector('[data-cs-confirm-message]').textContent = opts.message;
    okBtn.textContent = opts.ok || 'Confirm';
    okBtn.className = 'btn ' + (opts.danger ? 'btn-danger' : 'btn-primary');
    dlg.classList.toggle('cs-confirm--danger', !!opts.danger);
    dlg.querySelector('[data-cs-confirm-icon] i').className = 'ti ' + (opts.danger ? 'ti-alert-triangle' : 'ti-help-circle');

    function cleanup() {
      okBtn.removeEventListener('click', onAccept);
      cancelBtn.removeEventListener('click', onCancel);
      dlg.removeEventListener('close', onClose);
      dlg.removeEventListener('click', onBackdrop);
    }
    function onAccept() { accepted = true; dlg.close(); }
    function onCancel() { dlg.close(); }
    function onBackdrop(ev) { if (ev.target === dlg) dlg.close(); }
    function onClose() {
      cleanup();
      if (accepted) onOk();
    }

    okBtn.addEventListener('click', onAccept);
    cancelBtn.addEventListener('click', onCancel);
    dlg.addEventListener('click', onBackdrop);
    dlg.addEventListener('close', onClose);

    if (typeof dlg.showModal === 'function') {
      dlg.showModal();
      // The browser keeps focus inside the dialog. For a destructive action
      // it starts on Cancel, the safe choice.
      (opts.danger ? cancelBtn : okBtn).focus();
    } else {
      // A browser without <dialog>: go ahead rather than block the action.
      cleanup();
      onOk();
    }
  }

  function confirmOptions(el, submitter) {
    var danger = el.hasAttribute('data-cs-confirm-danger')
      || el.classList.contains('btn-danger')
      || (submitter && submitter.classList.contains('btn-danger'))
      || (el.tagName === 'FORM' && !!el.querySelector('[type="submit"].btn-danger'));

    return {
      message: el.getAttribute('data-cs-confirm') || 'Are you sure?',
      title: el.getAttribute('data-cs-confirm-title') || '',
      ok: el.getAttribute('data-cs-confirm-ok') || '',
      danger: danger
    };
  }

  var confirmedForms = new WeakSet();
  var confirmedClicks = new WeakSet();

  // Links, submit buttons and plain buttons.
  document.addEventListener('click', function (e) {
    var trigger = e.target.closest('[data-cs-confirm]');
    if (!trigger || trigger.tagName === 'FORM') return; // forms are handled on submit

    if (confirmedClicks.has(trigger)) {
      confirmedClicks.delete(trigger);
      return; // already confirmed: let this one click through
    }

    e.preventDefault();
    e.stopPropagation();

    askConfirm(confirmOptions(trigger, null), function () {
      var form = trigger.form || trigger.closest('form');
      var isSubmit = (trigger.tagName === 'BUTTON' || trigger.tagName === 'INPUT')
        && (trigger.type === 'submit' || trigger.type === 'image');

      if (isSubmit && form) {
        confirmedForms.add(form);
        if (typeof form.requestSubmit === 'function') form.requestSubmit(trigger);
        else form.submit();
      } else if (trigger.tagName === 'A' && trigger.href) {
        if (trigger.target && trigger.target !== '_self') window.open(trigger.href, trigger.target);
        else window.location.href = trigger.href;
      } else {
        confirmedClicks.add(trigger);
        trigger.click();
      }
    });
  });

  // A form that carries data-cs-confirm itself (this also covers Enter-key
  // submits). Capture phase, so it runs before the double-submit guard below.
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form || !form.hasAttribute || !form.hasAttribute('data-cs-confirm')) return;

    if (confirmedForms.has(form)) {
      confirmedForms.delete(form);
      return;
    }

    e.preventDefault();
    e.stopImmediatePropagation();

    var submitter = e.submitter || null;
    askConfirm(confirmOptions(form, submitter), function () {
      confirmedForms.add(form);
      if (typeof form.requestSubmit === 'function') form.requestSubmit(submitter || undefined);
      else form.submit();
    });
  }, true);

  /* ------------------------------------------------- double-submit guarding */
  // A resubmitted payment form is a duplicate ledger entry. Lock on submit.
  Array.prototype.forEach.call(document.querySelectorAll('form[data-cs-once]'), function (form) {
    form.addEventListener('submit', function (e) {
      if (e.defaultPrevented) return;
      var btn = form.querySelector('[type="submit"]');
      if (!btn) return;
      setTimeout(function () {
        btn.disabled = true;
        btn.innerHTML = '<i class="ti ti-loader-2"></i> Saving…';
      }, 0);
    });
  });

  /* ------------------------------------------------------- age from DOB */
  // Age is always derived from date of birth, never typed - mirrors
  // cs_age_from_dob() server-side (caresync_helper.php), the real source
  // of truth. This just keeps an on-screen Age field in sync live as the
  // date is picked, purely for display; the server recomputes it again on
  // submit regardless of whatever this field's value ends up being.
  function csComputeAge(dobValue) {
    if (!dobValue) return '';
    var dob = new Date(dobValue + 'T00:00:00');
    if (isNaN(dob.getTime())) return '';
    var today = new Date();
    today.setHours(0, 0, 0, 0);
    if (dob > today) return '';

    var age = today.getFullYear() - dob.getFullYear();
    var hadBirthdayThisYear = (today.getMonth() > dob.getMonth())
      || (today.getMonth() === dob.getMonth() && today.getDate() >= dob.getDate());
    if (!hadBirthdayThisYear) age -= 1;

    return age >= 0 ? String(age) : '';
  }

  Array.prototype.forEach.call(document.querySelectorAll('[data-age-source]'), function (ageField) {
    var sourceField = document.querySelector(ageField.getAttribute('data-age-source'));
    if (!sourceField) return;

    var sync = function () { ageField.value = csComputeAge(sourceField.value); };

    sourceField.addEventListener('input', sync);
    sourceField.addEventListener('change', sync);
    sync();
  });
})();
