# Análisis y requerimientos

**Proyecto:** Sistema de inventario para TecnoStock  
**Asignatura:** Sistemas de Información  
**Caso:** Caso 1 · Proyecto web centrado en operaciones CRUD y control de existencias  
**Versión del documento:** 1.0

## 1. Resumen ejecutivo

TecnoStock comercializa accesorios tecnológicos y actualmente controla su catálogo en una planilla. Esa forma de trabajo produce códigos duplicados, diferencias entre las cantidades registradas y el stock real, y poca visibilidad para decidir cuándo reponer un producto.

La solución desarrollada es una aplicación web local que centraliza el catálogo, registra cada entrada y salida con su responsable, actualiza el stock de forma automática y alerta cuando un producto queda bajo su punto de reposición. El sistema separa la interfaz (HTML5 y Bootstrap), la lógica de negocio (PHP 8) y la persistencia (MySQL mediante PDO), y entrega un script SQL reproducible junto con pruebas verificables.

## 2. Contexto y problema

### 2.1 Situación actual

| Dolor identificado | Consecuencia para el negocio |
|---|---|
| Registro manual en planilla | Errores de digitación y retrabajo |
| Códigos de producto repetidos | Productos ambiguos y movimientos asociados a la referencia equivocada |
| Cantidades desactualizadas | Ventas de productos sin stock o compras innecesarias |
| Sin historial de cambios | No se sabe quién movió una cantidad ni cuándo |
| Sin punto de reposición | Quiebres de stock y pérdida de ventas |

### 2.2 Problema

TecnoStock no cuenta con una fuente única de verdad para su inventario. La planilla no impide duplicados ni cantidades inválidas, no relaciona los movimientos con un usuario y no permite conocer con rapidez qué productos necesitan reposición.

### 2.3 Objetivo general

Construir una aplicación web conectada a una base de datos relacional que permita registrar, consultar, actualizar y desactivar productos, además de controlar sus movimientos de inventario, conservando la integridad del stock.

### 2.4 Objetivos específicos

1. Centralizar productos, categorías y movimientos en una base de datos normalizada.
2. Impedir códigos duplicados y valores negativos mediante restricciones y validaciones.
3. Actualizar el stock de forma automática y transaccional después de cada movimiento válido.
4. Alertar visualmente cuando el stock actual sea menor que el stock mínimo.
5. Mantener trazabilidad de cada movimiento (fecha, tipo, cantidad, producto y usuario).
6. Ofrecer una interfaz comprensible y responsiva para un usuario no técnico.
7. Entregar un script SQL reproducible y pruebas que permitan verificar el comportamiento.

## 3. Alcance

### 3.1 Incluido en el MVP

- Inicio y cierre de sesión con usuario previamente registrado.
- Panel con indicadores y alertas de reposición.
- Registro de productos con código, nombre, categoría, descripción, precio, stock inicial y stock mínimo.
- Listado paginado de productos.
- Búsqueda por nombre, código o categoría.
- Filtros por categoría, estado y condición de stock bajo.
- Edición de datos comerciales y del stock mínimo.
- Desactivación y reactivación de productos sin eliminación física.
- Registro de entradas y salidas de existencias.
- Actualización automática del stock y almacenamiento del stock resultante.
- Alerta de stock bajo en panel, listado y detalle.
- Historial de movimientos por producto (kardex).
- Administración de categorías con control de integridad.
- Validaciones en cliente y servidor, mensajes de error y confirmaciones.
- Script SQL con estructura, restricciones y datos de prueba.
- Pruebas automatizadas de integridad y casos de prueba documentados.

### 3.2 Excluido del MVP

- Facturación, punto de venta y cálculo de impuestos.
- Gestión de proveedores, órdenes de compra y costos.
- Múltiples bodegas o sucursales.
- Roles y permisos diferenciados.
- Recuperación de contraseña y registro público de usuarios.
- Exportación de reportes a Excel o PDF.
- API pública, aplicación móvil o integración con servicios externos.
- Despliegue en hosting con dominio y certificado SSL.

Estas exclusiones no afectan el flujo crítico solicitado y se documentan para delimitar el alcance.

## 4. Actor del sistema

| Actor | Responsabilidad | Acceso |
|---|---|---|
| Encargado de inventario | Administra productos, categorías y movimientos de existencias. Consulta indicadores y alertas. | Mediante credenciales registradas previamente en la base de datos. |

El caso define un único actor operativo. Por eso no se implementaron roles diferenciados: todos los usuarios activos tienen las mismas capacidades sobre el inventario. La base de datos sí distingue usuarios y registra quién realiza cada movimiento.

## 5. Requerimientos funcionales

| ID | Requerimiento | Prioridad | Implementación |
|---|---|---|---|
| RF-01 | Iniciar sesión con un usuario previamente registrado y activo. | Alta | `login.php`, `includes/auth.php` |
| RF-02 | Cerrar sesión de forma segura mediante POST y token CSRF. | Alta | `logout.php` |
| RF-03 | Registrar productos con código, nombre, categoría, descripción, precio, stock inicial y stock mínimo. | Alta | `productos/crear.php` |
| RF-04 | Consultar el listado de productos con paginación. | Alta | `productos/index.php` |
| RF-05 | Buscar productos por nombre, código o categoría. | Alta | `productos/index.php` |
| RF-06 | Filtrar por categoría, estado y productos con stock bajo. | Media | `productos/index.php` |
| RF-07 | Modificar precio, descripción, categoría, nombre, código, stock mínimo y ajustar el stock mediante un movimiento. | Alta | `productos/editar.php` |
| RF-08 | Desactivar productos que ya no se comercializan y permitir reactivarlos. | Alta | `productos/estado.php` |
| RF-09 | Registrar entradas de existencias. | Alta | `movimientos/registrar.php` |
| RF-10 | Registrar salidas de existencias con validación de stock disponible. | Alta | `movimientos/registrar.php` |
| RF-11 | Actualizar el stock automáticamente después de cada movimiento válido. | Alta | Transacción en `movimientos/registrar.php` |
| RF-12 | Mostrar una alerta cuando el stock actual sea menor que el stock mínimo. | Alta | `dashboard.php`, `productos/index.php`, `productos/ver.php` |
| RF-13 | Consultar el historial de movimientos de un producto. | Media | `productos/ver.php` |
| RF-14 | Administrar categorías y evitar su desactivación si tienen productos activos. | Media | `categorias/` |
| RF-15 | Visualizar indicadores de inventario en un panel. | Media | `dashboard.php` |
| RF-16 | Registrar fecha, tipo, cantidad, producto, usuario y observación en cada movimiento. | Alta | Tabla `movimiento` |
| RF-17 | Confirmar acciones destructivas o de impacto antes de ejecutarlas. | Media | `assets/js/app.js` |
| RF-18 | Comunicar errores y confirmaciones mediante mensajes claros. | Alta | `includes/header.php`, `includes/funciones.php` |

## 6. Requerimientos no funcionales

| ID | Requerimiento | Criterio verificable |
|---|---|---|
| RNF-01 | Usabilidad | Un usuario no técnico completa el flujo crítico sin ayuda, con etiquetas, mensajes y estados visibles. |
| RNF-02 | Responsividad | La interfaz se adapta a escritorio, tablet y móvil; el menú lateral se convierte en panel deslizante. |
| RNF-03 | Seguridad de acceso | Contraseñas con bcrypt, sesión regenerada al iniciar, cierre por POST y CSRF en formularios. |
| RNF-04 | Seguridad de datos | Consultas preparadas con PDO, escape de salida y validación de tipos en servidor. |
| RNF-05 | Integridad | Claves primarias, foráneas, `UNIQUE`, `CHECK` y transacciones con bloqueo de fila. |
| RNF-06 | Portabilidad | Funciona en XAMPP local cambiando solo las constantes de `config/config.php`. |
| RNF-07 | Mantenibilidad | Separación en configuración, utilidades, layout, controladores y vistas. |
| RNF-08 | Rendimiento | Índices en búsquedas y claves foráneas; listados paginados. |
| RNF-09 | Auditabilidad | Cada movimiento conserva usuario, fecha, cantidad y stock resultante. |
| RNF-10 | Accesibilidad | Etiquetas asociadas a campos, textos alternativos, foco visible y contraste adecuado. |
| RNF-11 | Recuperación ante errores | Errores técnicos al log; mensajes funcionales al usuario; transacciones revertidas ante fallas. |

## 7. Reglas de negocio

| ID | Regla del caso | Implementación | Evidencia |
|---|---|---|---|
| RN-01 | El código de producto no puede repetirse. | Índice `UNIQUE uq_producto_codigo` y validación previa en PHP. | `database/tecnostock.sql`, `productos/crear.php` |
| RN-02 | El precio, el stock actual, el stock mínimo y la cantidad de un movimiento no pueden ser negativos. | Restricciones `CHECK` y validación de servidor. | Tablas `producto` y `movimiento` |
| RN-03 | Todo movimiento debe registrar fecha, tipo, cantidad, producto y usuario. | Columnas `NOT NULL` y claves foráneas. | Tabla `movimiento` |
| RN-04 | Una salida no puede dejar el stock bajo cero. | Verificación con `SELECT ... FOR UPDATE` y `CHECK (stock_actual >= 0)`. | `movimientos/registrar.php` |
| RN-05 | Un producto con movimientos se desactiva; no se elimina físicamente. | No existe endpoint de borrado; solo cambio de estado. | `productos/estado.php` |
| RN-06 | El stock se actualiza automáticamente tras cada movimiento válido. | `UPDATE` y `INSERT` dentro de una misma transacción. | `movimientos/registrar.php` |
| RN-07 | Se alerta cuando el stock actual es menor que el stock mínimo. | Función `estado_stock()` y consultas de alerta. | `includes/funciones.php`, vistas |
| RN-08 | Una categoría con productos activos no puede desactivarse. | Conteo previo y bloqueo de la acción en PHP. | `categorias/estado.php` |
| RN-09 | Un producto desactivado no admite movimientos nuevos. | Verificación de `estado` dentro de la transacción. | `movimientos/registrar.php` |
| RN-10 | El stock inicial debe quedar respaldado por un movimiento. | Si el stock inicial es mayor que cero, se crea una entrada automática. | `productos/crear.php` |

### 7.1 Decisiones de diseño asociadas a las reglas

**El stock no se edita directamente.** El formulario de edición permite cambiar precio, descripción, categoría y stock mínimo, pero muestra el stock actual como solo lectura. Para modificarlo se registra una entrada, una salida o un ajuste con observación. Esta decisión evita que el stock cambie sin un movimiento que lo respalde y mantiene el kardex confiable.

**El stock inicial genera un movimiento.** Si al crear un producto se indica una cantidad inicial mayor que cero, el sistema registra una entrada con la observación «Stock inicial del producto» dentro de la misma transacción. Así, la suma de movimientos siempre explica el stock actual.

**Los productos no se eliminan.** El sistema no expone ninguna acción de borrado. Desactivar un producto impide nuevos movimientos, pero conserva su historial, sus referencias y la posibilidad de reactivarlo. Esto cumple la regla del caso y protege la integridad histórica.

**Se almacena el stock resultante.** Cada movimiento guarda el stock que quedó después de aplicarlo. Es un dato de auditoría que no puede reconstruirse con certeza si posteriormente se corrigen movimientos, y permite revisar el kardex sin recalcular toda la secuencia.

**La categoría se protege.** Desactivar una categoría con productos activos dejaría productos operando en una línea supuestamente cerrada. Por eso la acción se bloquea hasta reasignar o desactivar esos productos.

## 8. Casos de uso

### CU-01 · Iniciar sesión

- **Actor:** Encargado de inventario.
- **Precondición:** Usuario registrado y activo.
- **Flujo principal:** Ingresa correo y contraseña; el sistema valida formato, busca al usuario, verifica el hash y el estado; regenera la sesión y muestra el panel.
- **Flujo alterno:** Credenciales incorrectas o usuario inactivo; se muestra un mensaje genérico sin revelar cuál dato falló.
- **Postcondición:** Sesión iniciada y `ultimo_acceso` actualizado.

### CU-02 · Registrar producto

- **Actor:** Encargado de inventario.
- **Precondición:** Sesión iniciada y al menos una categoría activa.
- **Flujo principal:** Completa el formulario; el sistema valida campos, unicidad del código y categoría; inserta el producto; si hay stock inicial, registra la entrada; redirige al detalle.
- **Flujo alterno:** Código duplicado, precio negativo, cantidad inválida o categoría inactiva; se muestran errores junto al campo y no se guarda nada.
- **Postcondición:** Producto activo con stock y historial coherentes.

### CU-03 · Buscar y consultar productos

- **Actor:** Encargado de inventario.
- **Flujo principal:** Ingresa un texto y/o selecciona filtros; el sistema consulta con `LIKE` parametrizado, ordena primero los productos con stock bajo y muestra el listado paginado.
- **Flujo alterno:** Sin resultados; se muestra un estado vacío con opción de limpiar filtros.
- **Postcondición:** Listado consistente con los filtros aplicados.

### CU-04 · Editar producto

- **Actor:** Encargado de inventario.
- **Precondición:** Producto existente.
- **Flujo principal:** Modifica datos; el sistema valida y actualiza; redirige al detalle con confirmación.
- **Flujo alterno:** Código duplicado en otro producto o categoría inactiva; se rechaza la operación.
- **Postcondición:** Datos actualizados y fecha de actualización renovada.

### CU-05 · Desactivar o reactivar producto

- **Actor:** Encargado de inventario.
- **Flujo principal:** Selecciona la acción, confirma en el modal y el sistema cambia `estado`.
- **Flujo alterno:** El producto ya está en ese estado; se informa sin modificar.
- **Postcondición:** El producto deja o vuelve a admitir movimientos, sin perder historial.

### CU-06 · Registrar entrada o salida

- **Actor:** Encargado de inventario.
- **Precondición:** Producto activo.
- **Flujo principal:** Selecciona producto, tipo y cantidad; el sistema bloquea la fila, valida stock si es salida, actualiza el stock, registra el movimiento y muestra el nuevo stock.
- **Flujo alterno:** Salida mayor al stock, producto desactivado o cantidad inválida; se revierte la transacción y se informa.
- **Postcondición:** Stock y kardex actualizados de forma atómica.

### CU-07 · Consultar kardex

- **Actor:** Encargado de inventario.
- **Flujo principal:** Abre el detalle de un producto y revisa el historial ordenado por fecha, con tipo, cantidad, stock resultante, usuario y observación.
- **Postcondición:** Trazabilidad visible.

### CU-08 · Gestionar categorías

- **Actor:** Encargado de inventario.
- **Flujo principal:** Crea o edita categorías; puede desactivar una categoría sin productos activos.
- **Flujo alterno:** Nombre duplicado o categoría con productos activos; se rechaza con mensaje.
- **Postcondición:** Catálogo organizado y consistente.

## 9. Trazabilidad

### 9.1 Del caso a los requerimientos

| Funcionalidad mínima del caso | Requerimiento | Implementación | Prueba |
|---|---|---|---|
| Iniciar sesión con usuario registrado | RF-01 | `login.php` | CP-01, CP-02, CP-03 |
| Registrar productos | RF-03 | `productos/crear.php` | CP-04 a CP-07 |
| Consultar listado | RF-04 | `productos/index.php` | CP-09 |
| Buscar por nombre, código o categoría | RF-05 | `productos/index.php` | CP-10 |
| Modificar precio, descripción, categoría y cantidades | RF-07 | `productos/editar.php` | CP-11 |
| Desactivar productos | RF-08 | `productos/estado.php` | CP-12, CP-13 |
| Registrar entradas y salidas | RF-09, RF-10 | `movimientos/registrar.php` | CP-14 a CP-17 |
| Alerta de stock bajo | RF-12 | Vistas y `estado_stock()` | CP-18, CP-19 |
| Actualización automática del stock | RF-11 | Transacción de movimientos | CP-15, CP-20 |
| Base relacionada con PK, FK y restricciones | RNF-05 | `database/tecnostock.sql` | CP-22 a CP-26 |

### 9.2 De la rúbrica a la evidencia

| Criterio de la rúbrica | Evidencia principal | Documento |
|---|---|---|
| Análisis y requerimientos | Este documento, matriz de trazabilidad y casos de uso | `01_Analisis_y_Requerimientos.md` |
| Base de datos e integridad | Modelo, diccionario, restricciones y script ejecutable | `02_Modelo_Base_Datos.md` |
| Funcionalidades del MVP | Flujo completo implementado y video | `04_Guion_Video.md` |
| Interfaz, validaciones y usabilidad | Diseño responsivo, validaciones JS/PHP, mensajes | `01` y `03` |
| Pruebas y calidad | Casos de prueba, pruebas automatizadas y git | `03_Pruebas_y_Calidad.md` |
| Video | Guion cronometrado y recorrido de código | `04_Guion_Video.md` |

### 9.3 Trazabilidad regla de negocio → prueba

| Regla | Descripción breve | Prueba que la verifica |
|---|---|---|
| RN-01 | Código único | CP-05, CP-14, prueba automatizada "código duplicado" |
| RN-02 | Valores no negativos | CP-06, CP-07, pruebas automatizadas de precio/stock/cantidad |
| RN-03 | Movimiento completo | CP-17, CP-18, prueba automatizada "movimiento con cantidad cero" |
| RN-04 | Salida no deja stock bajo cero | CP-19, prueba automatizada "actualización que deja stock negativo" |
| RN-05 | Desactivación sin borrado | CP-15, CP-21, revisión de ausencia de endpoint DELETE |
| RN-06 | Stock automático | CP-16, CP-17, CP-18, prueba automatizada "entrada atómica" |
| RN-07 | Alerta de stock bajo | CP-22, CP-23, verificación en panel, listado y detalle |
| RN-08 | Categoría con productos activos | CP-26, prueba de bloqueo |
| RN-09 | Producto inactivo sin movimientos | CP-21, verificación en `movimientos/registrar.php` |
| RN-10 | Stock inicial respaldado | CP-04, verificación del kardex tras crear producto |

## 10. Criterios de aceptación

| ID | Escenario | Resultado esperado |
|---|---|---|
| CA-01 | Un usuario activo ingresa credenciales correctas. | Accede al panel y se actualiza su último acceso. |
| CA-02 | Un usuario inactivo intenta ingresar con la contraseña correcta. | El acceso se rechaza con un mensaje genérico. |
| CA-03 | Se intenta registrar un código ya existente. | El sistema impide el registro y señala el campo código. |
| CA-04 | Se intenta registrar un precio negativo. | El formulario se rechaza en cliente y en servidor. |
| CA-05 | Se registra una salida mayor al stock disponible. | La operación se revierte y se informa el stock disponible. |
| CA-06 | Se registra una salida válida. | El stock disminuye y el movimiento queda en el kardex con el usuario. |
| CA-07 | El stock queda bajo el mínimo. | Aparece la alerta en panel, listado y detalle. |
| CA-08 | Se desactiva un producto con movimientos. | El producto deja de admitir movimientos y conserva su historial. |
| CA-09 | Se intenta desactivar una categoría con productos activos. | La acción se bloquea con un mensaje explicativo. |
| CA-10 | Se busca un texto con comillas o signos SQL. | La consulta se ejecuta sin errores y sin alterar resultados. |

## 11. Supuestos y restricciones

1. Se administra una sola bodega; el stock es único por producto.
2. Los precios se expresan en pesos chilenos y se muestran sin decimales.
3. La zona horaria del sistema es `America/Santiago`.
4. El sistema se ejecuta en un servidor local XAMPP; no se contempla alta disponibilidad.
5. Los usuarios se crean directamente en la base de datos; no existe registro público.
6. No se permite eliminar productos ni categorías con historia asociada.
7. La búsqueda no distingue mayúsculas de minúsculas por la colación `utf8mb4_unicode_ci`.
8. Los datos de prueba son ficticios y no corresponden a información real de la empresa.
9. El sistema no reemplaza un ERP: cubre el alcance académico solicitado.

## 12. Limitaciones y trabajo futuro

- Incorporar roles (administrador y operador) y registro de usuarios.
- Añadir ajustes de inventario con motivo obligatorio y autorización.
- Exportar reportes de stock y movimientos a PDF/Excel.
- Implementar pruebas end-to-end automatizadas en navegador.
- Añadir recuperación de contraseña y política de bloqueo por intentos.
- Gestionar múltiples bodegas y transferencias entre ellas.
- Incorporar proveedores y órdenes de compra.

## 13. Conclusión

El análisis traduce el problema de TecnoStock en un alcance acotado y verificable. Cada regla del caso tiene una implementación concreta y una prueba asociada. La decisión de mover el stock solo mediante movimientos transaccionales es el eje de la integridad del sistema y permite demostrar, con evidencia, que el inventario no depende de ediciones manuales ni de controles informales.
