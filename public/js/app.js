/**
 * assets/app.js
 * Comportamientos de interfaz. Va en un archivo externo porque la CSP
 * del sistema usa script-src 'self' y bloquea scripts y handlers inline.
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    initRail();
    initConfirm();
    initSearchShortcut();
    initPrint();
    initAutoSubmit();
    initAutoPrint();
  });

  /* Riel lateral en pantallas chicas */
  function initRail() {
    var rail = document.getElementById('rail');
    var burger = document.querySelector('.appbar-burger');
    if (!rail || !burger) return;

    var backdrop = null;

    function close() {
      rail.classList.remove('is-open');
      burger.setAttribute('aria-expanded', 'false');
      if (backdrop) {
        backdrop.remove();
        backdrop = null;
      }
    }

    function open() {
      rail.classList.add('is-open');
      burger.setAttribute('aria-expanded', 'true');
      backdrop = document.createElement('div');
      backdrop.className = 'rail-backdrop no-print';
      backdrop.addEventListener('click', close);
      document.body.appendChild(backdrop);
    }

    burger.addEventListener('click', function () {
      if (rail.classList.contains('is-open')) { close(); } else { open(); }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') close();
    });

    window.addEventListener('resize', function () {
      if (window.innerWidth > 900) close();
    });
  }

  /* Confirmación antes de acciones destructivas: data-confirm="mensaje" */
  function initConfirm() {
    document.addEventListener('click', function (e) {
      var el = e.target.closest('[data-confirm]');
      if (!el) return;
      if (!window.confirm(el.getAttribute('data-confirm'))) {
        e.preventDefault();
      }
    });
  }

  /* Ctrl/Cmd + K enfoca el buscador global */
  function initSearchShortcut() {
    var input = document.querySelector('.appbar-search input');
    if (!input) return;
    document.addEventListener('keydown', function (e) {
      if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
        e.preventDefault();
        input.focus();
        input.select();
      }
    });
  }

  /* Botones de impresión: data-print */
  function initPrint() {
    document.addEventListener('click', function (e) {
      if (e.target.closest('[data-print]')) {
        e.preventDefault();
        window.print();
      }
    });
  }

  /* Vistas que se abren para imprimir: <div data-autoprint hidden> */
  function initAutoPrint() {
    if (!document.querySelector('[data-autoprint]')) return;
    window.addEventListener('load', function () {
      window.print();
    });
  }

  /* Selects que envían su formulario al cambiar: data-autosubmit */
  function initAutoSubmit() {
    var selects = document.querySelectorAll('select[data-autosubmit]');
    Array.prototype.forEach.call(selects, function (select) {
      select.addEventListener('change', function () {
        if (select.form) select.form.submit();
      });
    });
  }
})();
