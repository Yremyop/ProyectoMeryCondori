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
    initFiltroSelect();
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

  /* Buscador de opciones: <input data-filtro="idDelSelect">. Se reconstruye la lista
     (en vez de ocultar <option>) porque Safari de iPhone ignora las opciones ocultas. */
  function initFiltroSelect() {
    var inputs = document.querySelectorAll('input[data-filtro]');
    Array.prototype.forEach.call(inputs, function (input) {
      var select = document.getElementById(input.getAttribute('data-filtro'));
      if (!select) return;
      var todas = Array.prototype.slice.call(select.options);
      input.addEventListener('input', function () {
        var q = input.value.trim().toLowerCase();
        select.innerHTML = '';
        todas.forEach(function (op) {
          if (op.text.toLowerCase().indexOf(q) !== -1) select.appendChild(op);
        });
        if (select.options.length) select.selectedIndex = 0;
      });
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
