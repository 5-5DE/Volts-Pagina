// Validación para contacto e inicio de sesión.
function validarCampo(campo) {
    let error = '';
    const valor = campo.type === 'password' ? campo.value : campo.value.trim();
    if (!valor) {
        error = 'Completa este campo.';
    } else if (campo.type === 'email' && campo.validity.typeMismatch) {
        error = 'Escribe un correo válido, por ejemplo nombre@ejemplo.com.';
    } else if (campo.type === 'tel' && (campo.validity.patternMismatch || valor.replace(/\D/g, '').length < 10 || valor.replace(/\D/g, '').length > 15)) {
        error = 'Escribe un teléfono válido con 10 a 15 dígitos.';
    } else if (campo.minLength > 0 && valor.length < campo.minLength) {
        error = 'Escribe al menos ' + campo.minLength + ' caracteres.';
    } else if (campo.validity.customError) {
        error = campo.validationMessage;
    } else if (!campo.validity.valid) {
        error = 'Revisa el valor de este campo.';
    }
    document.getElementById('error-' + campo.id).textContent = error;
    campo.setAttribute('aria-invalid', error ? 'true' : 'false');
    return error === '';
}

function prepararFormulario(formulario, mensaje, alCompletar) {
    const campos = formulario.querySelectorAll('input:not([type="hidden"]), select, textarea');

    formulario.noValidate = true;
    campos.forEach(function (campo) {
        campo.addEventListener('blur', function () {
            validarCampo(campo);
        });
        campo.addEventListener('input', function () {
            mensaje.textContent = '';
            mensaje.classList.remove('mensaje-correcto');
            if (campo.getAttribute('aria-invalid') === 'true') {
                validarCampo(campo);
            }
        });
    });
    formulario.addEventListener('submit', function (evento) {
        evento.preventDefault();
        let primerError = null;
        campos.forEach(function (campo) {
            if (!validarCampo(campo) && !primerError) {
                primerError = campo;
            }
        });
        mensaje.classList.remove('mensaje-correcto');
        if (primerError) {
            mensaje.textContent = 'Revisa los campos marcados antes de continuar.';
            primerError.focus();
            return;
        }
        alCompletar();
    });
}
