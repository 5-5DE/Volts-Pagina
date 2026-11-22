(function () {
    'use strict';

    const seccion = document.getElementById('paquetes');
    if (!seccion) return;

    const claveAlmacenamiento = 'volts.paquetes.v1';
    const limitePorPaquete = 10;
    const formatoMoneda = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });
    const moneda = function (precio) { return formatoMoneda.format(precio) + ' MXN'; };
    const catalogo = new Map();
    const seleccion = new Map();
    const lista = document.getElementById('paquetes-seleccionados');
    const mensaje = document.getElementById('mensaje-paquetes');
    const vaciar = document.getElementById('vaciar-paquetes');
    const resumenSolicitud = document.getElementById('solicitud-paquetes');
    const tipoServicio = document.getElementById('servicio');
    const mensajeSolicitud = document.getElementById('mensaje-contacto');
    const datosPaquetes = document.getElementById('paquetes-elegidos');
    let avisoAlmacenamiento = '';

    seccion.querySelectorAll('[data-paquete-id]').forEach(function (tarjeta) {
        const id = tarjeta.dataset.paqueteId;
        const paquete = {
            nombre: tarjeta.dataset.paqueteNombre,
            precio: Number(tarjeta.dataset.paquetePrecio),
            boton: tarjeta.querySelector('.paquete-agregar'),
            textoBoton: tarjeta.querySelector('.paquete-agregar-texto'),
        };
        const fila = document.createElement('li');
        const informacion = document.createElement('div');
        const nombre = document.createElement('strong');
        const detalle = document.createElement('span');
        const subtotal = document.createElement('strong');
        const quitar = document.createElement('button');

        nombre.textContent = paquete.nombre;
        detalle.className = 'paquete-detalle';
        subtotal.className = 'paquete-subtotal';
        quitar.type = 'button';
        quitar.className = 'paquete-quitar';
        informacion.append(nombre, detalle);
        fila.append(informacion, subtotal, quitar);
        fila.hidden = true;
        lista.append(fila);
        Object.assign(paquete, { fila: fila, detalle: detalle, subtotal: subtotal, quitar: quitar });
        catalogo.set(id, paquete);

        paquete.boton.addEventListener('click', function () {
            const cantidad = seleccion.get(id) || 0;
            if (cantidad >= limitePorPaquete) return;
            seleccion.set(id, cantidad + 1);
            actualizar('Paquete agregado: ' + paquete.nombre + '.');
        });

        quitar.addEventListener('click', function () {
            const cantidad = seleccion.get(id) || 0;
            if (cantidad > 1) {
                seleccion.set(id, cantidad - 1);
            } else {
                seleccion.delete(id);
            }
            actualizar('Se quitó una unidad de ' + paquete.nombre + '.');
            if (!seleccion.has(id)) paquete.boton.focus();
        });
    });

    // Solo se guardan identificadores y cantidades; los precios salen del catálogo.
    try {
        if (document.getElementById('solicitud-confirmada')) {
            localStorage.removeItem(claveAlmacenamiento);
        }
        const guardado = JSON.parse(datosPaquetes && datosPaquetes.dataset.restaurar === 'true'
            ? datosPaquetes.value
            : localStorage.getItem(claveAlmacenamiento) || '{}');
        if (guardado && typeof guardado === 'object' && !Array.isArray(guardado)) {
            catalogo.forEach(function (paquete, id) {
                const cantidad = guardado[id];
                if (Number.isInteger(cantidad) && cantidad > 0 && cantidad <= limitePorPaquete) {
                    seleccion.set(id, cantidad);
                }
            });
        }
    } catch (error) {
        // Una selección dañada o el almacenamiento bloqueado no impiden agregar paquetes.
    }

    function actualizar(anuncio) {
        let total = 0;
        let unidades = 0;
        const resumenConsulta = [];

        catalogo.forEach(function (paquete, id) {
            const cantidad = seleccion.get(id) || 0;
            paquete.fila.hidden = cantidad === 0;
            paquete.detalle.textContent = cantidad + ' × ' + moneda(paquete.precio);
            paquete.subtotal.textContent = moneda(cantidad * paquete.precio);
            paquete.quitar.textContent = cantidad > 1 ? 'Quitar uno' : 'Quitar';
            paquete.quitar.setAttribute('aria-label', 'Quitar una unidad de ' + paquete.nombre);
            paquete.boton.disabled = cantidad >= limitePorPaquete;
            paquete.textoBoton.textContent = cantidad >= limitePorPaquete ? 'Límite de 10 paquetes' : 'Agregar paquete';
            total += cantidad * paquete.precio;
            unidades += cantidad;
            if (cantidad) resumenConsulta.push(cantidad + ' × ' + paquete.nombre + ': ' + moneda(cantidad * paquete.precio));
        });

        document.getElementById('cantidad-paquetes').textContent = unidades + (unidades === 1 ? ' paquete' : ' paquetes');
        document.getElementById('total-paquetes').textContent = moneda(total);
        document.getElementById('paquetes-vacio').hidden = unidades > 0;
        document.getElementById('paquetes-resumen-pie').hidden = unidades === 0;
        vaciar.hidden = unidades === 0;
        if (resumenSolicitud) {
            resumenSolicitud.hidden = unidades === 0;
            resumenSolicitud.textContent = unidades ? 'Paquetes de tu solicitud:\n' + resumenConsulta.join('\n') + '\nTotal estimado: ' + moneda(total) : '';
        }
        if (datosPaquetes) {
            datosPaquetes.value = JSON.stringify(Object.fromEntries(seleccion));
        }
        if (tipoServicio) {
            if (unidades > 0) {
                tipoServicio.value = 'Paquetes de trabajo';
            } else if (tipoServicio.value === 'Paquetes de trabajo') {
                tipoServicio.value = '';
            }
            tipoServicio.dispatchEvent(new Event('input', { bubbles: true }));
        }
        if (anuncio && mensajeSolicitud) {
            mensajeSolicitud.textContent = '';
            mensajeSolicitud.classList.remove('mensaje-correcto');
            document.getElementById('error-paquetes').textContent = '';
        }

        try {
            localStorage.setItem(claveAlmacenamiento, JSON.stringify(Object.fromEntries(seleccion)));
        } catch (error) {
            avisoAlmacenamiento = ' Tu selección se conservará solo mientras esta página esté abierta.';
        }
        mensaje.textContent = anuncio ? anuncio + ' Total estimado: ' + moneda(total) + '.' + avisoAlmacenamiento : avisoAlmacenamiento.trim();
    }

    vaciar.addEventListener('click', function () {
        seleccion.clear();
        actualizar('Selección vaciada.');
        catalogo.values().next().value.boton.focus();
    });

    actualizar('');
    seccion.querySelector('.paquetes-resumen').hidden = catalogo.size === 0;
})();
