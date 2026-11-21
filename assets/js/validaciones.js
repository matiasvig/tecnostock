document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-mayusculas]').forEach((entrada) => {
        entrada.addEventListener('input', () => {
            const posicion = entrada.selectionStart;
            entrada.value = entrada.value.toUpperCase();
            if (posicion !== null) {
                entrada.setSelectionRange(posicion, posicion);
            }
        });
    });

    document.querySelectorAll('[data-enteros]').forEach((entrada) => {
        entrada.addEventListener('input', () => {
            entrada.value = entrada.value.replace(/[^\d]/g, '');
        });
    });

    document.querySelectorAll('form.form-validado').forEach((formulario) => {
        formulario.addEventListener('submit', (evento) => {
            if (!formulario.checkValidity()) {
                evento.preventDefault();
                evento.stopPropagation();
                const invalido = formulario.querySelector(':invalid');
                if (invalido) {
                    invalido.focus();
                }
            }
            formulario.classList.add('was-validated');
        });
    });

    const formMovimiento = document.getElementById('formMovimiento');
    if (formMovimiento) {
        const selectProducto = document.getElementById('producto_id');
        const entradaCantidad = document.getElementById('cantidad');
        const radiosTipo = formMovimiento.querySelectorAll('input[name="tipo"]');
        const resumenProducto = document.getElementById('resumenProducto');
        const resumenActual = document.getElementById('resumenActual');
        const resumenMinimo = document.getElementById('resumenMinimo');
        const resumenMovimiento = document.getElementById('resumenMovimiento');
        const resumenResultado = document.getElementById('resumenResultado');
        const cajaResultado = document.getElementById('resumenResultadoCaja');
        const resumenAlerta = document.getElementById('resumenAlerta');
        const formato = new Intl.NumberFormat('es-CL');

        const tipoSeleccionado = () => {
            const marcado = formMovimiento.querySelector('input[name="tipo"]:checked');
            return marcado ? marcado.value : 'entrada';
        };

        const actualizarResumen = () => {
            const opcion = selectProducto?.selectedOptions[0];
            const tipo = tipoSeleccionado();
            const cantidad = parseInt(entradaCantidad?.value || '0', 10);

            if (!opcion || opcion.value === '') {
                if (resumenProducto) resumenProducto.textContent = 'Sin seleccionar';
                if (resumenActual) resumenActual.textContent = '—';
                if (resumenMinimo) resumenMinimo.textContent = '—';
                if (resumenMovimiento) resumenMovimiento.textContent = '—';
                if (resumenResultado) resumenResultado.textContent = '—';
                cajaResultado?.classList.remove('peligro');
                resumenAlerta?.classList.add('d-none');
                entradaCantidad?.setCustomValidity('');
                return;
            }

            const stock = parseInt(opcion.dataset.stock || '0', 10);
            const minimo = parseInt(opcion.dataset.minimo || '0', 10);
            const codigo = opcion.dataset.codigo || '';
            const resultado = tipo === 'entrada' ? stock + cantidad : stock - cantidad;
            const excede = tipo === 'salida' && cantidad > stock;

            if (resumenProducto) resumenProducto.textContent = `${codigo} · ${opcion.dataset.nombre || ''}`;
            if (resumenActual) resumenActual.textContent = `${formato.format(stock)} unidades`;
            if (resumenMinimo) resumenMinimo.textContent = `${formato.format(minimo)} unidades`;
            if (resumenMovimiento) {
                resumenMovimiento.textContent = cantidad > 0
                    ? `${tipo === 'entrada' ? 'Entrada' : 'Salida'} de ${formato.format(cantidad)} unidades`
                    : '—';
            }
            if (resumenResultado) resumenResultado.textContent = `${formato.format(resultado)} unidades`;

            cajaResultado?.classList.toggle('peligro', excede || resultado < 0);

            if (excede) {
                entradaCantidad?.setCustomValidity('La cantidad supera el stock disponible.');
                if (resumenAlerta) {
                    resumenAlerta.textContent = `Solo hay ${formato.format(stock)} unidades disponibles.`;
                    resumenAlerta.className = 'small mt-2 text-danger';
                }
            } else {
                entradaCantidad?.setCustomValidity('');
                if (resumenAlerta) {
                    if (resultado < minimo) {
                        resumenAlerta.textContent = `El stock quedará bajo el mínimo definido (${formato.format(minimo)} unidades).`;
                        resumenAlerta.className = 'small mt-2 text-warning-emphasis';
                    } else {
                        resumenAlerta.textContent = '';
                        resumenAlerta.className = 'small mt-2 d-none';
                    }
                }
            }
        };

        selectProducto?.addEventListener('change', actualizarResumen);
        entradaCantidad?.addEventListener('input', actualizarResumen);
        radiosTipo.forEach((radio) => radio.addEventListener('change', actualizarResumen));
        actualizarResumen();
    }
});
