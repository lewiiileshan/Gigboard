/* GigBoard KE - small behaviours shared by every page. Plain JavaScript, no libraries.
   Everything here is an extra: the forms still work, and the server still checks every field, without it. */
(function () {
  'use strict';

  /* 1. Stop double-submits: once a form is sent, its button is disabled and says "Working...". */
  document.addEventListener('submit', function (e) {
    if (e.defaultPrevented) return;
    var b = e.target.querySelector('button[type=submit]:not([data-label])');
    if (!b || e.target.method.toLowerCase() !== 'post') return;
    setTimeout(function () { b.disabled = true; b.dataset.label = b.textContent; b.textContent = 'Working…'; }, 0);
  });
  window.addEventListener('pageshow', function () {   // coming back with the Back button
    document.querySelectorAll('button[data-label]').forEach(function (b) { b.disabled = false; b.textContent = b.dataset.label; delete b.dataset.label; });
  });

  /* 2. Named confirmation for consequential actions.
     A form with data-confirm-title opens a dialog that names the thing and uses the real verb as its button.
     The safe choice ("Go back") has focus, Escape closes it, and focus returns to the button that opened it. */
  var dlg, pending, opener;
  function buildDialog() {
    dlg = document.createElement('dialog');
    dlg.className = 'confirm';
    dlg.setAttribute('aria-labelledby', 'confirm-title');
    dlg.innerHTML = '<h2 id="confirm-title"></h2><p class="muted" id="confirm-text"></p>' +
      '<div class="actions"><button type="button" class="btn ghost" data-back autofocus></button><button type="button" class="btn" data-go></button></div>';
    document.body.appendChild(dlg);
    dlg.querySelector('[data-back]').addEventListener('click', function () { dlg.close(); });
    dlg.querySelector('[data-go]').addEventListener('click', function () {
      var f = pending; f.dataset.confirmed = '1'; dlg.close();
      f.requestSubmit ? f.requestSubmit() : f.submit();
    });
    dlg.addEventListener('close', function () { if (opener && opener.focus) opener.focus(); });
    dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });
  }
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (!f.dataset || !f.dataset.confirmTitle || f.dataset.confirmed === '1') return;
    if (typeof HTMLDialogElement === 'undefined') {            // very old browsers: plain confirm
      if (!window.confirm(f.dataset.confirmTitle)) e.preventDefault();
      return;
    }
    e.preventDefault(); e.stopImmediatePropagation();
    if (!dlg) buildDialog();
    pending = f; opener = document.activeElement;
    dlg.querySelector('#confirm-title').textContent = f.dataset.confirmTitle;
    dlg.querySelector('#confirm-text').textContent = f.dataset.confirmText || '';
    dlg.querySelector('[data-back]').textContent = f.dataset.confirmBack || 'Go back';
    var go = dlg.querySelector('[data-go]');
    go.textContent = f.dataset.confirmAction || 'Continue';
    go.className = 'btn' + (f.dataset.confirmTone === 'primary' ? '' : ' danger');
    dlg.showModal();
  }, true);

  /* 3. Validation that waits until you leave a field, and clears as soon as you fix it.
     Forms opt in with data-validate. Rules come from ordinary attributes: required, minlength, type, pattern. */
  function label(el) { var l = el.id && document.querySelector('label[for="' + el.id + '"]'); return l ? l.textContent.replace(/\(.*?\)/, '').trim() : 'this field'; }
  function message(el) {
    if (el.dataset.msg) return el.dataset.msg;
    var v = el.validity, n = label(el).toLowerCase();
    if (v.customError) return el.validationMessage;
    if (v.valueMissing) return el.type === 'checkbox' ? 'Accept the terms of service to continue.' : (el.type === 'radio' ? 'Choose an option.' : 'Enter ' + n + '.');
    if (v.typeMismatch) return el.type === 'email' ? 'Enter an email address like name@example.com.' : 'Enter a valid ' + n + '.';
    if (v.tooShort) return 'Add at least ' + el.minLength + ' characters to ' + n + '.';
    if (v.patternMismatch) return 'Check the format of ' + n + '.';
    if (v.rangeUnderflow || v.rangeOverflow) return 'Enter a value between ' + el.min + ' and ' + el.max + '.';
    return 'Check ' + n + '.';
  }
  function holder(el) { return el.type === 'radio' ? el.closest('fieldset') : el; }
  function showError(el) {
    var h = holder(el), id = (el.id || el.name) + '-err', p = document.getElementById(id);
    if (!p) { p = document.createElement('p'); p.id = id; p.className = 'field-error'; p.setAttribute('role', 'alert'); h.insertAdjacentElement('afterend', p); }
    p.textContent = message(el); el.setAttribute('aria-invalid', 'true'); el.setAttribute('aria-describedby', id);
  }
  function clearError(el) {
    var p = document.getElementById((el.id || el.name) + '-err');
    if (p) p.remove(); el.removeAttribute('aria-invalid'); el.removeAttribute('aria-describedby');
  }
  function matchPasswords(f) {
    var a = f.querySelector('#password'), b = f.querySelector('#confirm');
    if (a && b) b.setCustomValidity(b.value && a.value !== b.value ? 'The two passwords do not match. Retype them.' : '');
  }
  document.querySelectorAll('form[data-validate]').forEach(function (f) {
    var fields = Array.prototype.slice.call(f.querySelectorAll('input:not([type=hidden]):not([type=file]), select, textarea'));
    fields.forEach(function (el) {
      el.addEventListener('blur', function () { matchPasswords(f); if (el.type !== 'radio' && !el.checkValidity()) showError(el); });
      el.addEventListener('focus', function () { clearError(el); });
      el.addEventListener('input', function () { clearError(el); matchPasswords(f); });
      el.addEventListener('change', function () { clearError(el); });
    });
    f.addEventListener('submit', function (e) {       // the button is never disabled: clicking it shows what is missing
      matchPasswords(f);
      var bad = fields.filter(function (el) { return !el.checkValidity(); });
      if (!bad.length) return;
      e.preventDefault(); e.stopImmediatePropagation();
      bad.forEach(showError); bad[0].focus();
    }, true);
  });

  /* 4. Draft saving for long forms: text is kept in this browser until the form is sent. Passwords and files are never saved. */
  document.querySelectorAll('form[data-draft]').forEach(function (f) {
    var key = 'gb-draft:' + f.dataset.draft, fields = f.querySelectorAll('input[type=text], input:not([type]), input[type=date], textarea, select'), timer;
    try {
      var saved = JSON.parse(localStorage.getItem(key) || 'null'), any = false;
      if (saved && !f.querySelector('[data-has-old]')) {
        fields.forEach(function (el) { if (el.name && saved[el.name] && !el.value) { el.value = saved[el.name]; any = true; } });
        if (any) {
          var note = document.createElement('p'); note.className = 'muted small draft-note';
          note.innerHTML = 'Draft restored from this device. <button type="button" class="linkish">Discard draft</button>';
          f.insertBefore(note, f.firstChild.nextSibling);
          note.querySelector('button').addEventListener('click', function () { try { localStorage.removeItem(key); } catch (x) {} fields.forEach(function (el) { el.value = el.tagName === 'SELECT' ? '' : ''; }); note.remove(); });
        }
      }
    } catch (x) {}
    f.addEventListener('input', function () {
      clearTimeout(timer);
      timer = setTimeout(function () {
        var data = {}; fields.forEach(function (el) { if (el.name && el.value) data[el.name] = el.value; });
        try { localStorage.setItem(key, JSON.stringify(data)); } catch (x) {}
      }, 400);
    });
    f.addEventListener('submit', function (e) { if (!e.defaultPrevented) { try { localStorage.removeItem(key); } catch (x) {} } });
  });

  /* 5. The account menu closes with Escape or a click elsewhere, and focus goes back to its button. */
  document.querySelectorAll('details.menu-wrap').forEach(function (d) {
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && d.open) { d.open = false; d.querySelector('summary').focus(); } });
    document.addEventListener('click', function (e) { if (d.open && !d.contains(e.target)) d.open = false; });
  });
})();
