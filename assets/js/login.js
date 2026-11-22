const formularioLogin = document.querySelector('.acceso-formulario form');
const mensajeLogin = document.getElementById('mensaje-login');
const passwordRegistro = document.querySelector('input[autocomplete="new-password"]');
const confirmacionPassword = document.getElementById('confirmar-password');

if (passwordRegistro && confirmacionPassword) {
    function comprobarPasswords() {
        const demasiadoLarga = new TextEncoder().encode(passwordRegistro.value).length > 72;
        passwordRegistro.setCustomValidity(demasiadoLarga ? 'La contraseña es demasiado larga. Usa menos caracteres.' : '');
        confirmacionPassword.setCustomValidity(
            confirmacionPassword.value !== passwordRegistro.value ? 'Las contraseñas no coinciden.' : ''
        );
        if (confirmacionPassword.getAttribute('aria-invalid') === 'true') {
            validarCampo(confirmacionPassword);
        }
    }

    passwordRegistro.addEventListener('input', comprobarPasswords);
    confirmacionPassword.addEventListener('input', comprobarPasswords);
    formularioLogin.addEventListener('submit', comprobarPasswords);
}

prepararFormulario(formularioLogin, mensajeLogin, function () {
    formularioLogin.submit();
});
