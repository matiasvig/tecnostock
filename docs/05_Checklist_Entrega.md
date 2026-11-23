# Checklist de entrega

Use esta lista antes de subir el repositorio y el video.

## 1. Ejecución del MVP

- [ ] Apache y MySQL están activos en XAMPP.
- [ ] La base `tecnostock` se importó desde `database/tecnostock.sql` sin errores.
- [x] `config/config.php` tiene el usuario y la clave correctos de MySQL.
- [x] `http://localhost/TecnoStock` abre el login.
- [x] El usuario `admin@tecnostock.cl` / `Admin123*` inicia sesión.
- [x] El usuario `inactivo@tecnostock.cl` es rechazado.
- [x] El panel muestra indicadores y la alerta de reposición.
- [x] Se puede crear un producto con stock inicial.
- [ ] El código duplicado se rechaza.
- [x] El precio negativo y las cantidades negativas se rechazan.
- [ ] La búsqueda por nombre, código y categoría funciona.
- [ ] La edición guarda precio, descripción, categoría y stock mínimo.
- [ ] Desactivar un producto pide confirmación y conserva el historial.
- [x] Reactivar un producto funciona.
- [x] Una entrada aumenta el stock y aparece en el kardex.
- [ ] Una salida válida disminuye el stock y aparece en el kardex.
- [x] Una salida mayor al stock se rechaza con el stock disponible.
- [x] La alerta aparece cuando el stock queda bajo el mínimo.
- [ ] Un producto con stock igual al mínimo no genera alerta.
- [ ] No existe ninguna opción para eliminar productos o categorías.
- [ ] Una categoría con productos activos no se puede desactivar.
- [x] La interfaz se ve correctamente en móvil y escritorio.

## 2. Pruebas

- [x] `php tests/pruebas_integridad.php` termina con 0 fallas.
- [ ] El resultado se guardó en `docs/evidencias/`.
- [x] Los casos manuales de `docs/03_Pruebas_y_Calidad.md` están completos.
- [x] Se probó una salida que deja el stock en cero.
- [x] Se probó un producto desactivado intentando recibir un movimiento.
- [ ] Se probó una búsqueda con comillas y caracteres especiales.
- [x] Se verificó que el stock coincide con la suma de movimientos.

## 3. Documentación

- [ ] `README.md` explica instalación, credenciales y estructura.
- [x] `docs/01_Analisis_y_Requerimientos.md` tiene alcance, actor, requerimientos y reglas.
- [x] `docs/02_Modelo_Base_Datos.md` tiene diagrama, diccionario y normalización.
- [x] `docs/03_Pruebas_y_Calidad.md` tiene casos, resultados y evidencias.
- [x] `docs/04_Guion_Video.md` está cronometrado y cubre la rúbrica.
- [x] Los documentos no tienen datos personales inventados ni afirmaciones no verificadas.
- [x] El nombre, sección y fecha se agregaron en el README. RUT por completar si el docente lo exige.

## 4. Repositorio

- [ ] El repositorio incluye todo el código y la carpeta `database/`.
- [x] No se subieron credenciales reales ni archivos de configuración privados.
- [ ] El repositorio es público o tiene acceso para el docente.
- [x] El historial de commits muestra avances comprensibles.
- [ ] El README indica la URL del video.
- [ ] Se verificó que el proyecto se descarga y ejecuta en otra ruta.

## 5. Video

- [ ] Dura 5 minutos o menos.
- [ ] Se ve y se escucha con claridad.
- [ ] Muestra el flujo completo solicitado.
- [ ] Explica interfaz, PHP/PDO, JavaScript y MySQL.
- [ ] Muestra la ejecución de las pruebas.
- [ ] El enlace es accesible sin solicitar permiso.
- [ ] El video no muestra notificaciones, correo ni datos personales.

## 6. Autoevaluación con la rúbrica

| Criterio | Puntaje máximo | Evidencia en la entrega | Puntaje esperado |
|---|---|---|---|
| Análisis y requerimientos | 15 | Documento 01, trazabilidad, casos de uso | 15 |
| Base de datos e integridad | 25 | Documento 02, script SQL, pruebas | 25 |
| Funcionalidades del MVP | 30 | Flujo completo y video | 30 |
| Interfaz, validaciones y usabilidad | 10 | Diseño responsivo, JS + PHP, mensajes | 10 |
| Pruebas y calidad | 10 | Pruebas automatizadas, casos manuales, git | 10 |
| Video | 10 | Guion y ejecución | 10 |
| **Total** | **100** | | **100** |

> La autoevaluación es una referencia. El puntaje final depende de la verificación del docente sobre el MVP ejecutable.

## 7. Publicación sugerida

### GitHub

```bash
git init
git add .
git commit -m "Versión inicial del sistema de inventario TecnoStock"
git branch -M main
git remote add origin https://github.com/USUARIO/tecnostock.git
git push -u origin main
```

### Video

- Subir a YouTube como «no listado» o a Google Drive con permiso de lectura.
- Copiar el enlace en el README y en la entrega.
- Verificar el enlace desde una ventana de incógnito.

## 8. Errores frecuentes que debe evitar

1. Importar el SQL sin haber iniciado MySQL.
2. Dejar `DB_USUARIO` o `DB_CLAVE` con valores distintos a los de su XAMPP.
3. Grabar el video con la base ya modificada y sin datos de alerta.
4. Mostrar solo el listado y no el flujo de movimientos.
5. Explicar el código sin vincularlo con las reglas de negocio.
6. Subir el video con acceso restringido.
7. Incluir contraseñas reales o datos personales en el repositorio.
8. Superar los 5 minutos.
