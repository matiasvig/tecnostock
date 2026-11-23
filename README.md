# TecnoStock · Sistema de inventario

Aplicación web para administrar el catálogo de accesorios tecnológicos de TecnoStock, registrar entradas y salidas de existencias y mantener actualizado el stock con alertas de reposición.

Proyecto académico de **Sistemas de Información**. Desarrollado con HTML5, Bootstrap 5, JavaScript, PHP 8 y MySQL, sin frameworks de servidor.

## Funcionalidades

- Inicio y cierre de sesión con contraseñas cifradas y protección CSRF.
- Panel con productos activos, unidades en stock, inventario valorizado y alertas.
- Registro, consulta, búsqueda, edición y desactivación de productos.
- Búsqueda por nombre, código o categoría, con filtros por estado y stock bajo.
- Registro de entradas y salidas con actualización automática del stock.
- Alerta visual cuando el stock actual es menor que el stock mínimo.
- Historial de movimientos (kardex) por producto y por usuario responsable.
- Administración de categorías con control de integridad.
- Validaciones en JavaScript y en PHP, mensajes de error y confirmaciones.
- Script SQL reproducible con datos de prueba.
- Pruebas automatizadas de integridad y casos de prueba documentados.

## Requisitos

- XAMPP con Apache, PHP 8.1 o superior y MySQL/MariaDB.
- Extensiones PHP habilitadas: `pdo_mysql`, `mbstring`, `openssl`.
- Navegador actualizado (Chrome, Edge o Firefox).

## Instalación

1. Copie la carpeta `TecnoStock` dentro de `C:\xampp\htdocs`.
2. Inicie Apache y MySQL desde el panel de control de XAMPP.
3. Abra `http://localhost/phpmyadmin`.
4. Importe el archivo `database/tecnostock.sql`. El script crea la base `tecnostock`, las tablas, restricciones y datos de prueba.
5. Si su usuario de MySQL no es `root` sin contraseña, ajuste las constantes `DB_USUARIO` y `DB_CLAVE` en `config/config.php`.
6. Abra `http://localhost/TecnoStock`.

También puede importar el script desde la consola:

```bash
mysql -u root -p < database/tecnostock.sql
```

## Acceso de demostración

| Perfil | Correo | Contraseña |
|---|---|---|
| Encargado de inventario | `admin@tecnostock.cl` | `Admin123*` |
| Encargado de inventario | `encargado@tecnostock.cl` | `Tecno2026*` |
| Usuario inactivo (rechazo de acceso) | `inactivo@tecnostock.cl` | `Tecno2026*` |

> Las credenciales anteriores son únicamente para la demostración académica del MVP. En un entorno real deben cambiarse y no deben mostrarse en pantalla.

## Estructura del proyecto

```text
TecnoStock/
├── assets/
│   ├── css/estilos.css
│   ├── fonts/                 Inter e Instrument Serif autoalojadas
│   ├── js/app.js
│   ├── js/validaciones.js
│   └── vendor/                Bootstrap 5.3.3 y Bootstrap Icons
├── categorias/                Listado, creación, edición y estado
├── config/
│   ├── config.php             Parámetros de la aplicación y de MySQL
│   └── database.php           Conexión PDO única
├── database/
│   └── tecnostock.sql         Script reproducible con datos de prueba
├── docs/                      Análisis, modelo, pruebas y guion del video
├── includes/                  Inicialización, sesión, CSRF, helpers y layout
├── movimientos/               Historial y registro de entradas/salidas
├── productos/                 Listado, detalle, creación, edición y estado
├── tests/
│   └── pruebas_integridad.php Pruebas automatizadas con rollback
├── dashboard.php
├── index.php
├── login.php
└── logout.php
```

## Reglas de negocio implementadas

1. El código de producto es único: restricción `UNIQUE` y validación en PHP.
2. El precio, el stock actual, el stock mínimo y la cantidad de un movimiento no pueden ser negativos.
3. Todo movimiento registra fecha, tipo, cantidad, producto y usuario responsable.
4. Una salida no puede dejar el stock bajo cero: se valida en PHP y se refuerza con `CHECK`.
5. Los productos no se eliminan físicamente: se desactivan y conservan su historial.
6. El stock se actualiza automáticamente después de cada entrada o salida válida, dentro de una transacción.
7. La alerta de stock bajo se activa cuando `stock_actual < stock_minimo`.

## Pruebas

Con la base de datos creada, ejecute desde la raíz del proyecto:

```bash
php tests/pruebas_integridad.php
```

El script prueba restricciones, transacciones, claves foráneas, contraseñas, inyección SQL y consistencia del stock. Todas las operaciones se ejecutan dentro de una transacción que se revierte al finalizar.

Los casos de prueba manuales y los resultados esperados están en `docs/03_Pruebas_y_Calidad.md`.

## Documentación

- `docs/01_Analisis_y_Requerimientos.md`: problema, alcance, actor, requerimientos, reglas y trazabilidad.
- `docs/02_Modelo_Base_Datos.md`: modelo relacional, diccionario de datos, normalización e integridad.
- `docs/03_Pruebas_y_Calidad.md`: estrategia, casos de prueba y evidencias.
- `docs/04_Guion_Video.md`: guion cronometrado para el video de 5 minutos.
- `docs/05_Checklist_Entrega.md`: verificación previa a la entrega.

## Seguridad

- Contraseñas almacenadas con `password_hash` (bcrypt) y verificadas con `password_verify`.
- Consultas preparadas con PDO y `ATTR_EMULATE_PREPARES => false`.
- Tokens CSRF en todos los formularios que modifican datos.
- Escape de salida con `htmlspecialchars` en todas las vistas.
- Regeneración del identificador de sesión al iniciar sesión.
- Cookies de sesión `HttpOnly` y `SameSite=Lax`.
- Validación y normalización de datos en el servidor, independiente de JavaScript.
- Registro de errores técnicos en el log de PHP, sin exponer detalles al usuario.

## Créditos tipográficos

La interfaz usa **Inter** e **Instrument Serif**, autoalojadas en `assets/fonts/` para funcionar sin conexión. Ambas se distribuyen bajo la SIL Open Font License 1.1; el texto de la licencia está en `assets/fonts/OFL-Inter.txt` y `assets/fonts/OFL-InstrumentSerif.txt`.

## Video explicativo

- **Enlace:** https://drive.google.com/file/d/1zq91pbZdaOg-5qBLMlYlKYi0LCUQCq_C/view?usp=sharing
- Duración: 4:50 (dentro del límite de 5 minutos).
- El video muestra el flujo completo del MVP y explica interfaz, PHP/PDO, JavaScript y MySQL.

## Datos del estudiante
- **Nombre:** Matias Viluñir Gonzalez
- **RUT:** 21734334-8
- **Sección:** 411
- **Fecha de entrega:** 22 de noviembre de 2026

## Autoría

Proyecto desarrollado para la evaluación del Caso 1: Sistema de inventario para TecnoStock.
