        </main>

        <footer class="app-pie">
            <span>TecnoStock · Sistema de inventario</span>
            <span><?= date('Y') ?></span>
        </footer>
    </div>
</div>

<div class="modal fade" id="modalConfirmar" tabindex="-1" aria-labelledby="modalConfirmarTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="modalConfirmarTitulo">Confirmar acción</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="modalConfirmarMensaje">¿Desea continuar?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="modalConfirmarBoton">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<script src="<?= url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= url('assets/js/app.js') ?>"></script>
<script src="<?= url('assets/js/validaciones.js') ?>"></script>
</body>
</html>
