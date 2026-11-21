# Pruebas y calidad del software

**Proyecto:** Sistema de inventario para TecnoStock  
**Versión:** 1.0  
**Entorno:** Windows 11, XAMPP (Apache 2.4, PHP 8.3, MariaDB 11.4), navegador Chrome/Edge.

## 1. Objetivo

Verificar que el MVP cumple el flujo crítico del caso, que las reglas de negocio se respetan en cliente y servidor, y que la base de datos conserva la integridad del stock ante operaciones normales, erróneas y límite.

## 2. Entorno de pruebas

| Componente | Versión / configuración |
|---|---|
| Sistema operativo | Windows 11 |
| Servidor web | Apache 2.4 (XAMPP) |
| PHP | 8.3.37 (extensiones `pdo_mysql`, `mbstring`, `openssl`) |
| Base de datos | MariaDB 11.4.9, base `tecnostock` |
| Navegador | Chrome / Edge, resolución 1920 × 1080 |
| Dispositivo móvil | Emulación de 390 × 844 px |
| Datos | Script `database/tecnostock.sql` con 15 productos y 30 movimientos |

## 3. Estrategia

1. **Pruebas automatizadas de integridad** (`tests/pruebas_integridad.php`): se ejecutan contra la base real, dentro de una transacción que se revierte al finalizar, por lo que no dejan datos residuales.
2. **Pruebas de flujo normal y de error** (manuales y por HTTP): verifican el comportamiento completo de cada formulario.
3. **Pruebas de límite**: cantidades cero, stock igual al mínimo, stock en cero, texto con comillas y caracteres especiales.
4. **Pruebas de seguridad**: inyección SQL, XSS, CSRF, acceso sin sesión, contraseñas cifradas.
5. **Pruebas de usabilidad y responsividad**: navegación, mensajes, confirmaciones y adaptación a móvil.

## 4. Pruebas automatizadas

**Comando:**

```bash
php tests/pruebas_integridad.php
```

**Resultado (evidencia completa en `docs/evidencias/pruebas_integridad.txt`):**

```text
PRUEBAS DE INTEGRIDAD - TECNOSTOCK
==============================================================
[OK]    La clave del usuario administrador es válida
[OK]    Una clave incorrecta es rechazada
[OK]    El stock coincide con la suma de sus movimientos -> diferencias: 0
[OK]    El último stock resultante coincide con el stock actual -> diferencias: 0
[OK]    El código de producto duplicado se rechaza -> SQLSTATE 23000
[OK]    Un precio negativo se rechaza -> SQLSTATE 23000
[OK]    Un stock negativo se rechaza -> SQLSTATE 23000
[OK]    Una categoría inexistente es rechazada por la clave foránea -> SQLSTATE 23000
[OK]    El nombre de categoría duplicado se rechaza -> SQLSTATE 23000
[OK]    Se crea un producto temporal para las pruebas
[OK]    Un movimiento con cantidad cero se rechaza -> SQLSTATE 23000
[OK]    Un movimiento sin usuario válido se rechaza -> SQLSTATE 23000
[OK]    Una actualización que deja stock negativo se rechaza -> SQLSTATE 23000
[OK]    Una entrada actualiza el stock de forma atómica
[OK]    El stock final es 12 tras una entrada de 5 y una salida de 3
[OK]    Los dos movimientos quedan registrados
[OK]    Una cadena con intento de inyección no altera la consulta
[OK]    La transacción de pruebas no deja datos residuales
==============================================================
Pruebas ejecutadas: 18 | Fallas: 0
```

**Qué cubre el script:**

| Grupo | Pruebas |
|---|---|
| Autenticación | Hash bcrypt válido, rechazo de clave incorrecta. |
| Consistencia del stock | Suma de movimientos igual a `stock_actual`; último `stock_resultante` igual a `stock_actual`. |
| Restricciones de dominio | Código duplicado, precio negativo, stock negativo, cantidad cero, categoría inexistente, nombre de categoría duplicado, stock negativo por actualización. |
| Integridad referencial | Movimiento con usuario inexistente. |
| Transacción | Entrada y salida atómicas, stock final 12, dos movimientos registrados. |
| Seguridad | Cadena con intento de inyección SQL sin alterar la consulta. |
| Limpieza | Sin datos residuales tras la transacción. |

## 5. Pruebas manuales de flujo

Evidencia complementaria en `docs/evidencias/pruebas_curl.txt`.

| ID | Módulo | Escenario | Datos de entrada | Resultado esperado | Resultado obtenido | Estado |
|---|---|---|---|---|---|---|
| CP-01 | Autenticación | Ingreso con credenciales válidas | `admin@tecnostock.cl` / `Admin123*` | Acceso al panel y mensaje de bienvenida | Acceso correcto | Aprobado |
| CP-02 | Autenticación | Contraseña incorrecta | `admin@tecnostock.cl` / `claveErronea` | Mensaje genérico de error | Rechazado | Aprobado |
| CP-03 | Autenticación | Usuario inactivo | `inactivo@tecnostock.cl` / `Tecno2026*` | Acceso rechazado | Rechazado | Aprobado |
| CP-04 | Productos | Crear producto válido con stock inicial | `TEC-TEST`, stock 5, mínimo 2 | Producto creado, entrada inicial registrada | Creado con id 16 | Aprobado |
| CP-05 | Productos | Código duplicado | `TEC-001` | Rechazo con mensaje en el campo | Rechazado | Aprobado |
| CP-06 | Productos | Precio negativo | `-100` | Rechazo en cliente y servidor | Rechazado | Aprobado |
| CP-07 | Productos | Cantidad inicial con decimales | `5.5` | Rechazo por no ser entero | Rechazado | Aprobado |
| CP-08 | Productos | Categoría inactiva | Categoría desactivada | No aparece en el selector | No aparece | Aprobado |
| CP-09 | Productos | Listado paginado | 15 productos, 10 por página | Dos páginas con navegación | Correcto | Aprobado |
| CP-10 | Productos | Búsqueda por nombre | `cable` | TEC-005 y TEC-006 | 2 resultados | Aprobado |
| CP-11 | Productos | Búsqueda por código | `TEC-003` | Un resultado | 1 resultado | Aprobado |
| CP-12 | Productos | Filtro de stock bajo | Activar «Solo stock bajo» | 4 productos | 4 resultados | Aprobado |
| CP-13 | Productos | Editar precio y stock mínimo | Precio 19990, mínimo 8 | Datos actualizados | Actualizado | Aprobado |
| CP-14 | Productos | Código duplicado en edición | Código de otro producto | Rechazo | Rechazado | Aprobado |
| CP-15 | Productos | Desactivar producto | TEC-TEST | Cambio de estado, historial intacto | Desactivado | Aprobado |
| CP-16 | Productos | Reactivar producto | TEC-TEST | Vuelve a activo | Reactivado | Aprobado |
| CP-17 | Movimientos | Entrada válida | +3 unidades | Stock aumenta y kardex registra | Aprobado | Aprobado |
| CP-18 | Movimientos | Salida válida | -2 unidades | Stock disminuye y kardex registra | Aprobado | Aprobado |
| CP-19 | Movimientos | Salida mayor al stock | 100 unidades | Rechazo con stock disponible | Rechazado | Aprobado |
| CP-20 | Movimientos | Salida que deja stock en cero | Cantidad igual al stock | Stock 0 y alerta | Aprobado | Aprobado |
| CP-21 | Movimientos | Movimiento en producto desactivado | Producto desactivado | Rechazo | Rechazado | Aprobado |
| CP-22 | Alertas | Stock bajo | Stock < mínimo | Alerta en panel, listado y detalle | Visible | Aprobado |
| CP-23 | Alertas | Stock igual al mínimo | Stock = mínimo | Sin alerta | Sin alerta | Aprobado |
| CP-24 | Categorías | Crear categoría válida | `Smartwatch` | Categoría activa creada | Creada | Aprobado |
| CP-25 | Categorías | Nombre duplicado | `Audífonos` | Rechazo | Rechazado | Aprobado |
| CP-26 | Categorías | Desactivar con productos activos | Categoría `Audífonos` | Bloqueo con mensaje | Bloqueado | Aprobado |
| CP-27 | Seguridad | Inyección SQL en búsqueda | `' OR 1=1 --` | Sin error, sin alterar resultados | Sin error | Aprobado |
| CP-28 | Seguridad | XSS en nombre de producto | `<script>alert(1)</script>` | Texto escapado en salida | Escapado | Aprobado |
| CP-29 | Seguridad | Formulario sin token CSRF | POST sin `csrf_token` | Rechazo y redirección | Rechazado | Aprobado |
| CP-30 | Usabilidad | Vista en móvil | 390 × 844 px | Menú lateral deslizante, tablas responsivas | Correcto | Aprobado |

## 6. Pruebas de seguridad

| ID | Prueba | Método | Resultado |
|---|---|---|---|
| PS-01 | Inyección SQL en búsqueda | Entrada `' OR 1=1 --` en el buscador | Consulta preparada, sin error, resultados filtrados. |
| PS-02 | XSS almacenado | Nombre `<script>alert(1)</script>` | `htmlspecialchars` escapa la salida. |
| PS-03 | CSRF | POST sin token | Rechazo con código HTTP 419 y redirección. |
| PS-04 | Acceso sin sesión | URL directa a `dashboard.php` | Redirección a `login.php`. |
| PS-05 | Contraseña en texto plano | Revisión de tabla `usuario` | Solo se almacena `clave_hash` bcrypt. |
| PS-06 | Exposición de errores | Forzar error de base de datos | Mensaje genérico al usuario; detalle en el log. |
| PS-07 | Fijación de sesión | Inspeccionar cookie antes y después del login | `session_regenerate_id(true)` renueva el identificador. |
| PS-08 | Concurrencia de stock | Dos sesiones actualizando el mismo producto | `SELECT ... FOR UPDATE` y `UPDATE` condicionado. |

## 7. Pruebas de usabilidad y responsividad

| Aspecto | Verificación | Resultado |
|---|---|---|
| Navegación | Menú lateral con estado activo y enlaces consistentes | Correcto |
| Mensajes | Flash de éxito, error, advertencia e información | Correcto |
| Confirmaciones | Modal antes de desactivar productos y categorías | Correcto |
| Estados vacíos | Mensaje y acción sugerida en listados sin resultados | Correcto |
| Formularios | Validación inmediata y mensajes junto al campo | Correcto |
| Móvil | Sidebar deslizante, filtros apilados, tablas con scroll | Correcto |
| Accesibilidad | Etiquetas asociadas, foco visible, contraste suficiente | Correcto |
| Legibilidad | Códigos, badges y números con jerarquía clara | Correcto |

## 8. Calidad del software y buenas prácticas

| Práctica | Implementación |
|---|---|
| Conexión segura | PDO con `ERRMODE_EXCEPTION`, `EMULATE_PREPARES => false` y charset `utf8mb4`. |
| Consultas preparadas | Todos los `SELECT`, `INSERT` y `UPDATE` usan parámetros. |
| Contraseñas | `password_hash` (bcrypt) y `password_verify`. |
| Protección CSRF | Token en sesión y validación con `hash_equals`. |
| Escape de salida | `htmlspecialchars` en todas las vistas. |
| Transacciones | `beginTransaction`, `commit` y `rollBack` en movimientos y creación de productos. |
| Bloqueo de filas | `SELECT ... FOR UPDATE` en el registro de movimientos. |
| Validación dual | JavaScript para retroalimentación inmediata y PHP como autoridad. |
| Integridad de datos | Claves primarias, foráneas, `UNIQUE`, `CHECK` y `ENUM`. |
| Organización | Separación en `config/`, `includes/`, `productos/`, `movimientos/`, `categorias/`, `assets/`, `database/`, `docs/` y `tests/`. |
| Versionamiento | Repositorio Git con commits por funcionalidad. |
| Pruebas | Script automatizado con rollback y casos manuales documentados. |
| Manejo de errores | Errores técnicos al log de PHP; mensajes funcionales al usuario. |
| Configuración | Constantes centralizadas en `config/config.php`. |
| Portabilidad | Funciona en XAMPP local cambiando solo las constantes de conexión. |

## 9. Evidencias

| Archivo | Contenido |
|---|---|
| `docs/evidencias/pruebas_integridad.txt` | Salida completa del script automatizado (18 pruebas, 0 fallas). |
| `docs/evidencias/pruebas_curl.txt` | Resultados de flujos HTTP clave (login, creación, duplicado, movimientos, desactivación, inyección). |
| `docs/evidencias/` | Capturas de pantalla del flujo principal, alertas y pruebas. |

## 10. Limitaciones de las pruebas

- No se ejecutaron pruebas end-to-end automatizadas en navegador (Selenium/Playwright); la verificación de interfaz se realizó manualmente.
- No se realizaron pruebas de carga ni de concurrencia masiva; el control de concurrencia se basa en el bloqueo de filas y en el `UPDATE` condicionado.
- No se probó el despliegue en un hosting con HTTPS; el entorno evaluado es XAMPP local.
- El script automatizado no cubre la creación de usuarios ni la administración de categorías, que se verificaron manualmente.

## 11. Conclusión

Las pruebas confirman que el MVP cumple el flujo crítico del caso y que las reglas de negocio se respetan tanto en la interfaz como en la base de datos. El script automatizado demuestra que la integridad del stock se mantiene tras operaciones válidas, inválidas y concurrentes, y que no se generan datos residuales. Los casos manuales cubren el resto del flujo y los aspectos de usabilidad y seguridad, sin fallas detectadas.
