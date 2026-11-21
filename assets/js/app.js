document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const fondo = document.getElementById('sidebarFondo');
    const btnMenu = document.getElementById('btnMenu');

    const cerrarMenu = () => {
        if (!sidebar) {
            return;
        }
        sidebar.classList.remove('abierto');
        fondo?.classList.remove('visible');
        btnMenu?.setAttribute('aria-expanded', 'false');
    };

    const abrirMenu = () => {
        sidebar?.classList.add('abierto');
        fondo?.classList.add('visible');
        btnMenu?.setAttribute('aria-expanded', 'true');
    };

    btnMenu?.addEventListener('click', () => {
        if (sidebar?.classList.contains('abierto')) {
            cerrarMenu();
        } else {
            abrirMenu();
        }
    });

    fondo?.addEventListener('click', cerrarMenu);

    document.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape') {
            cerrarMenu();
        }
    });

    window.matchMedia('(min-width: 992px)').addEventListener('change', (evento) => {
        if (evento.matches) {
            cerrarMenu();
        }
    });

    document.querySelectorAll('[data-ver-clave]').forEach((boton) => {
        boton.addEventListener('click', () => {
            const entrada = document.querySelector(boton.dataset.verClave);
            if (!entrada) {
                return;
            }
            const mostrar = entrada.type === 'password';
            entrada.type = mostrar ? 'text' : 'password';
            boton.innerHTML = `<i class="bi ${mostrar ? 'bi-eye-slash' : 'bi-eye'}"></i>`;
            boton.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
        });
    });

    const modalElemento = document.getElementById('modalConfirmar');
    if (modalElemento && window.bootstrap) {
        const modal = new bootstrap.Modal(modalElemento);
        const mensaje = document.getElementById('modalConfirmarMensaje');
        const titulo = document.getElementById('modalConfirmarTitulo');
        const boton = document.getElementById('modalConfirmarBoton');
        let formularioPendiente = null;

        document.querySelectorAll('form[data-confirmar]').forEach((formulario) => {
            formulario.addEventListener('submit', (evento) => {
                if (formulario.dataset.confirmado === '1') {
                    delete formulario.dataset.confirmado;
                    return;
                }
                evento.preventDefault();
                formularioPendiente = formulario;
                mensaje.textContent = formulario.dataset.confirmar || '¿Desea continuar?';
                titulo.textContent = formulario.dataset.confirmarTitulo || 'Confirmar acción';
                boton.textContent = formulario.dataset.confirmarBoton || 'Confirmar';
                boton.className = 'btn ' + (formulario.dataset.confirmarEstilo || 'btn-danger');
                modal.show();
            });
        });

        boton?.addEventListener('click', () => {
            if (!formularioPendiente) {
                return;
            }
            const formulario = formularioPendiente;
            formulario.dataset.confirmado = '1';
            modal.hide();
            formulario.requestSubmit();
            setTimeout(() => delete formulario.dataset.confirmado, 0);
        });

        modalElemento.addEventListener('hidden.bs.modal', () => {
            formularioPendiente = null;
        });
    }

    document.querySelectorAll('[title]').forEach((elemento) => {
        if (elemento.closest('.modal')) {
            return;
        }
        new bootstrap.Tooltip(elemento);
    });
});
