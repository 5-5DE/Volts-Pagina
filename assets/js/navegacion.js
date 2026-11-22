(function () {
    'use strict';

    const menus = Array.from(document.querySelectorAll('.menu-explorar, .menu-perfil'));
    if (!menus.length) return;

    document.addEventListener('click', function (evento) {
        menus.forEach(function (menu) {
            if (!menu.contains(evento.target)) menu.open = false;
        });
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key !== 'Escape') return;
        const menu = menus.find(function (elemento) { return elemento.open; });
        if (menu) {
            menu.open = false;
            menu.querySelector('summary').focus();
        }
    });

    document.addEventListener('focusin', function (evento) {
        menus.forEach(function (menu) {
            if (!menu.contains(evento.target)) menu.open = false;
        });
    });

    menus.forEach(function (menu) {
        menu.addEventListener('toggle', function () {
            if (!menu.open) return;
            menus.forEach(function (otro) {
                if (otro !== menu) otro.open = false;
            });
        });
        menu.addEventListener('click', function (evento) {
            if (evento.target.closest('a')) menu.open = false;
        });
    });
})();
