(function () {
    'use strict';

    const formulario = document.getElementById('formulario-contacto');
    const mensaje = document.getElementById('mensaje-contacto');
    if (!formulario || !mensaje) return;

    prepararFormulario(formulario, mensaje, function () {
        const boton = formulario.querySelector('button[type="submit"]');
        if (boton.disabled) return;
        boton.disabled = true;
        boton.textContent = 'Enviando…';
        mensaje.textContent = 'Estamos guardando tu solicitud.';
        HTMLFormElement.prototype.submit.call(formulario);
    });
})();
