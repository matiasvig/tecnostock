# Guion del video explicativo

**Duración objetivo:** 4 minutos 50 segundos  
**Límite de la evaluación:** 5 minutos  
**Formato:** video individual, pantalla compartida con audio  
**Resolución sugerida:** 1920 × 1080, navegador al 110 % de zoom

## 1. Preparación previa

1. Reiniciar Apache y MySQL en XAMPP.
2. Reimportar `database/tecnostock.sql` para dejar los datos en el estado inicial.
3. Abrir `http://localhost/TecnoStock` y comprobar el acceso con `admin@tecnostock.cl` / `Admin123*`.
4. Cerrar notificaciones, correo y pestañas ajenas al proyecto.
5. Abrir en pestañas separadas:
   - La aplicación.
   - `config/database.php`.
   - `login.php`.
   - `movimientos/registrar.php`.
   - `database/tecnostock.sql`.
   - `assets/js/validaciones.js`.
   - `productos/index.php`.
6. Tener una terminal ubicada en la raíz del proyecto para ejecutar las pruebas.
7. Probar el micrófono y grabar un clip de 10 segundos para revisar audio y legibilidad.
8. Ensayar una vez con cronómetro. Si se pasa de 4:55, acortar la sección de código, no la demostración.

> El video se evalúa solo entre 0:00 y 5:00. No es necesario mostrar el rostro si la voz y la pantalla son claras.

## 2. Estructura cronometrada

| Tiempo | Sección | Pantalla | Acción clave |
|---|---|---|---|
| 0:00–0:18 | Presentación | Diapositiva o pantalla de inicio | Nombre, asignatura, caso y objetivo. |
| 0:18–0:42 | Problema y solución | Panel de la aplicación | Explicar el dolor y las tres decisiones centrales. |
| 0:42–1:05 | Acceso y panel | Login y dashboard | Iniciar sesión, mostrar indicadores y alerta. |
| 1:05–1:50 | Productos | Listado y formulario | Buscar, crear un producto y mostrar validaciones. |
| 1:50–2:15 | Edición y desactivación | Detalle y modal | Cambiar stock mínimo, desactivar sin borrar. |
| 2:15–3:00 | Movimientos | Formulario de movimiento | Entrada, salida inválida y alerta de stock bajo. |
| 3:00–3:45 | Código backend | `registrar.php`, `database.php`, `login.php` | PDO, transacción, bloqueo y validación. |
| 3:45–4:15 | Código frontend y SQL | `validaciones.js`, `tecnostock.sql`, `index.php` | Validación en cliente, restricciones y búsqueda preparada. |
| 4:15–4:35 | Pruebas | Terminal y documento de pruebas | Ejecutar pruebas y mostrar resultado. |
| 4:35–4:50 | Cierre | Panel o diapositiva final | Sintetizar el valor y agradecer. |

## 3. Guion literal

### 0:00–0:18 · Presentación

> «Hola. Soy [nombre], estudiante de Sistemas de Información. Les presento TecnoStock, el sistema de inventario que desarrollé para el Caso 1. La aplicación permite administrar productos, registrar entradas y salidas y mantener el stock actualizado con alertas de reposición.»

**En pantalla:** nombre, asignatura y título del proyecto. Luego cambiar a la aplicación.

### 0:18–0:42 · Problema y solución

> «TecnoStock controlaba su catálogo en una planilla. Eso generaba códigos duplicados, cantidades desactualizadas y ninguna trazabilidad de quién movía el inventario. La solución centraliza productos, categorías y movimientos en MySQL, y se apoya en tres decisiones: el código es único, el stock solo cambia mediante movimientos transaccionales y los productos nunca se eliminan: se desactivan.»

**En pantalla:** panel con los indicadores y la alerta de reposición.

### 0:42–1:05 · Acceso y panel

> «El acceso usa usuarios registrados previamente. La contraseña se guarda con bcrypt y se verifica con `password_verify`; la sesión se regenera al iniciar. En el panel veo productos activos, unidades disponibles, inventario valorizado y las alertas de stock bajo. Esta alerta se calcula comparando el stock actual con el mínimo de cada producto.»

**Acción:** iniciar sesión con `admin@tecnostock.cl` / `Admin123*`. Recorrer los cuatro indicadores y la tabla de reposición.

### 1:05–1:50 · Productos

> «En Productos puedo buscar por nombre, código o categoría, filtrar por estado y ver solo los que están bajo el mínimo. El listado está paginado y ordena primero los productos críticos. Voy a crear uno nuevo. Ingreso el código TEC-016, un nombre, la categoría, el precio y un stock inicial de seis unidades con mínimo dos.»

**Acción:** abrir Productos, buscar «cable», mostrar resultados. Luego «Nuevo producto» y completar:
- Código: `TEC-016`
- Nombre: `Audífonos deportivos RunFit`
- Categoría: `Audífonos`
- Precio: `21990`
- Stock inicial: `6`
- Stock mínimo: `2`

> «El formulario valida en JavaScript, pero el servidor repite todas las reglas: formato del código, precio no negativo, cantidades enteras y categoría activa. Si el código ya existe, la base de datos también lo impide con una restricción unique. Al guardar, el stock inicial genera automáticamente un movimiento de entrada, para que el kardex explique siempre el stock.»

**Acción:** guardar y mostrar el detalle con el historial.

### 1:50–2:15 · Edición y desactivación

> «Puedo editar precio, descripción, categoría y stock mínimo. El stock actual no se escribe directamente: se muestra como solo lectura y se modifica con entradas o salidas, para no perder trazabilidad. Si subo el mínimo a ocho, el producto queda bajo el mínimo y la alerta aparece de inmediato.»

**Acción:** editar TEC-016, cambiar stock mínimo a `8`, guardar. Mostrar la alerta.

> «Para descontinuar un producto no lo borro: lo desactivo. El sistema pide confirmación, deja de admitir movimientos nuevos y conserva todo el historial. Puedo reactivarlo cuando sea necesario.»

**Acción:** desactivar TEC-016, confirmar el modal, mostrar el mensaje y el filtro «Desactivados». Reactivarlo para continuar la demostración.

### 2:15–3:00 · Movimientos y alerta

> «Este es el flujo central. Registro una entrada para TEC-006, que está sin stock: diez unidades. El sistema bloquea la fila del producto, actualiza el stock y guarda el movimiento con fecha, usuario, cantidad y stock resultante. El producto deja de estar agotado.»

**Acción:** Movimientos → Registrar. Producto `TEC-006`, tipo Entrada, cantidad `10`, observación «Reposición de bodega». Guardar y mostrar el detalle.

> «Ahora intento una salida de quince unidades. El sistema la rechaza porque solo hay diez: una salida no puede dejar el stock bajo cero. Esta regla se valida en el navegador y otra vez en PHP, dentro de la transacción.»

**Acción:** registrar salida de `15` para TEC-006. Mostrar el error con el stock disponible.

> «Registro una salida válida de tres unidades. El stock queda en siete, por debajo del mínimo de ocho, y la alerta de reposición se activa. El movimiento aparece en el kardex con mi usuario y el stock resultante.»

**Acción:** cambiar cantidad a `3`, guardar. Mostrar el mensaje de advertencia y el kardex.

### 3:00–3:45 · Código del backend

> «Paso al código. La conexión está en `config/database.php`: PDO con excepciones, sin emulación de consultas preparadas y con fetch asociativo. Eso obliga a usar parámetros reales y evita la inyección SQL.»

**Pantalla:** `config/database.php`, resaltar las opciones de PDO.

> «En `login.php`, la consulta es preparada, la clave se verifica con `password_verify` y la sesión se regenera con `session_regenerate_id` para prevenir fijación de sesión.»

**Pantalla:** `login.php`, resaltar la consulta y la verificación.

> «La parte más importante está en `movimientos/registrar.php`. Aquí comienza una transacción, se lee el producto con `SELECT ... FOR UPDATE` para bloquear la fila, se valida que esté activo y que la salida no supere el stock. Luego se actualiza el stock con una condición sobre el valor anterior y se verifica que se haya modificado exactamente una fila. Finalmente se inserta el movimiento y se confirma. Si algo falla, se revierte todo.»

**Pantalla:** `movimientos/registrar.php`, recorrer el bloque de transacción sin leer cada línea.

> «Esto garantiza que el stock y el movimiento siempre estén sincronizados, incluso si dos usuarios registran a la vez.»

### 3:45–4:15 · Código frontend y base de datos

> «En el navegador, `validaciones.js` entrega retroalimentación inmediata: muestra el stock disponible, calcula el stock resultante y bloquea una salida mayor al stock. Aun así, el servidor repite la validación, porque JavaScript puede desactivarse.»

**Pantalla:** `assets/js/validaciones.js`, resaltar `setCustomValidity` y el cálculo del resumen.

> «La base de datos refuerza las reglas con `UNIQUE` en el código, `CHECK` para precios y cantidades no negativas, claves foráneas y el tipo `ENUM` para entrada o salida. Y la búsqueda del listado usa `LIKE` con parámetros preparados, nunca concatenación de texto.»

**Pantalla:** `database/tecnostock.sql`, resaltar restricciones; luego `productos/index.php`, resaltar la consulta preparada.

### 4:15–4:35 · Pruebas

> «El proyecto incluye pruebas automatizadas de integridad. Este script intenta duplicar un código, ingresar precios y stocks negativos, crear movimientos inválidos y verificar que el stock coincida con la suma de movimientos. Todo se ejecuta dentro de una transacción que se revierte al final, así no deja datos residuales.»

**Acción:** en la terminal, ejecutar:

```bash
php tests/pruebas_integridad.php
```

> «Las veinte pruebas terminan sin fallas. Además, documenté casos manuales de flujo normal, errores y límites, con resultados y evidencias.»

**Pantalla:** mostrar el resumen de la terminal y, brevemente, la tabla de `docs/03_Pruebas_y_Calidad.md`.

### 4:35–4:50 · Cierre

> «En resumen, TecnoStock cumple el flujo completo: autenticación, catálogo, búsqueda, edición, desactivación, movimientos, stock automático y alertas, con una base relacional íntegra y validaciones en cliente y servidor. Gracias por su atención.»

**Pantalla:** panel con los indicadores actualizados.

## 4. Recorrido de código sugerido

| Archivo | Qué mostrar | Qué decir |
|---|---|---|
| `config/database.php` | Bloque de opciones de PDO | Conexión única, excepciones, consultas preparadas reales. |
| `login.php` | Consulta preparada, `password_verify`, `session_regenerate_id` | Credenciales seguras y sesión renovada. |
| `includes/csrf.php` | `token_csrf`, `hash_equals` | Protección de formularios. |
| `movimientos/registrar.php` | `beginTransaction`, `FOR UPDATE`, validación, `UPDATE` condicionado, `commit` | Atomicidad y control de concurrencia. |
| `database/tecnostock.sql` | `UNIQUE`, `CHECK`, `FOREIGN KEY` | Reglas reforzadas en la base. |
| `productos/index.php` | Consulta con `LIKE` y parámetros | Búsqueda segura y paginada. |
| `assets/js/validaciones.js` | Resumen de stock y `setCustomValidity` | Validación inmediata en el navegador. |

## 5. Qué evitar

- Leer el código línea por línea: explicar la intención y las reglas.
- Mostrar errores de configuración o depurar en vivo.
- Superar los 5 minutos: el exceso no se evalúa.
- Usar un volumen bajo o una resolución que impida leer la interfaz.
- Saltarse la alerta de stock bajo o la validación de salida: son evidencia directa de la rúbrica.
- Decir «esto lo hice con IA» o improvisar funciones que no existen.

## 6. Plan de respaldo

| Situación | Acción |
|---|---|
| MySQL no responde | Reiniciar el servicio, recargar la página y continuar; no mostrar la instalación. |
| Un formulario falla | Explicar la validación esperada y continuar con el siguiente flujo; no depurar en vivo. |
| Se acaba el tiempo en productos | Acortar la edición y pasar directo a movimientos y código. |
| Se acaba el tiempo en código | Mostrar solo la transacción y las restricciones SQL, y cerrar con las pruebas. |
| El audio falla | Regrabar solo esa sección si es posible; si no, priorizar una demostración clara sobre una explicación extensa. |

## 7. Cobertura de la rúbrica en el video

| Criterio | Momento del video |
|---|---|
| Análisis y requerimientos | 0:18–0:42, problema, alcance y decisiones. |
| Base de datos e integridad | 3:45–4:15, restricciones y modelo; 3:00–3:45, transacción. |
| Funcionalidades del MVP | 0:42–3:00, flujo completo de extremo a extremo. |
| Interfaz, validaciones y usabilidad | 1:05–1:50 y 2:15–3:00, mensajes, alertas y validación. |
| Pruebas y calidad | 4:15–4:35, pruebas automatizadas y documentación. |
| Video | Estructura cronometrada, exposición autónoma y cierre. |

## 8. Lista de verificación antes de publicar

- [ ] El video dura 5:00 o menos.
- [ ] Se ve el inicio de sesión y el nombre del sistema.
- [ ] Se muestra crear, buscar, editar y desactivar un producto.
- [ ] Se muestra una entrada y una salida válidas.
- [ ] Se muestra el rechazo de una salida mayor al stock.
- [ ] Se muestra la alerta de stock bajo.
- [ ] Se explica al menos un archivo PHP, uno JS y el script SQL.
- [ ] Se muestra la ejecución de las pruebas.
- [ ] El audio es claro y la interfaz legible.
- [ ] El enlace del video es accesible sin solicitar permiso.
