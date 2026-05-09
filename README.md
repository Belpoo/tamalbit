# Tamalbit Market

Aplicación web para gestionar gastos simulados consumiendo una API bancaria externa.

## Descripción
El sistema consulta saldo desde una API (`bank-service`) y permite registrar compras de productos cargados desde base de datos.
Cada compra válida:
- Descuenta saldo mediante API (no directamente en BD).
- Registra el gasto en MySQL.
- Calcula Tamalbits según la regla de negocio.

## Regla de Tamalbits
Por cada 10 dólares gastados en el producto `Orejas de pollo`, se otorga 1 Tamalbit.

Fórmula aplicada:

`Tamalbits = floor(monto_descontado / 10)`

Solo se otorgan Tamalbits si el descuento en API fue exitoso y validado.

## Estructura del proyecto

```text
TiendaApi/
  front/
    index.html
    scripts/script.js
    styles/styles.css
  back/
    conexion.php
    comprar.php
    index.php
  api/
    api_client.php
    dashboard_data.php
    obtener_saldo.php
  database/
    crearDb.sql
  index.php
  DOCUMENTACION.md
  README.md
```

## Tecnologías
- Frontend: HTML, CSS, JavaScript, Bootstrap 5
- Backend: PHP (mysqli)
- Base de datos: MySQL
- API externa: bank-service 1.0

## Requisitos
- XAMPP o entorno equivalente con Apache + MySQL
- API bank-service disponible en `http://localhost:8083`
- Navegador web moderno

## Instalación
1. Colocar el proyecto en la carpeta de publicación de Apache (por ejemplo, `htdocs/TiendaApi`).
2. Crear la base de datos ejecutando:
   - Archivo: `database/crearDb.sql`
3. Configurar credenciales de conexión en:
   - `back/conexion.php`
4. Levantar la API bank-service en el puerto 8083.
5. Abrir en el navegador:
   - `http://localhost/TiendaApi/index.php`

## Imágenes de productos
Las imágenes se leen desde la carpeta `images/` en la raíz del proyecto.

Nombres esperados por el seed SQL:
- `orejas-de-pollo.jpg`
- `patas-de-zancudo.jpg`
- `hamburguesa.jpg`
- `pizza.jpg`
- `gaseosa.jpg`

Si falta una imagen o el nombre no coincide, la UI muestra un placeholder automáticamente.

## Flujo funcional
1. El usuario ingresa su `personId` en la pantalla principal.
2. El frontend consulta datos a `api/dashboard_data.php`.
3. Se muestran:
   - Nombre del usuario (desde API)
   - Saldo API
   - Total gastado
   - Tamalbits acumulados
   - Productos y historial
4. Al comprar:
   - `front/scripts/script.js` envía formulario a `back/comprar.php`.
   - Backend valida saldo actual en API.
   - Backend descuenta saldo con POST en API.
   - Backend reconsulta saldo y registra el descuento real en BD.
   - Se redirige con mensaje de resultado.

## Endpoints internos
- `api/dashboard_data.php`
  - Método: GET
  - Parámetro: `personId` (opcional)
  - Respuesta: JSON con métricas, productos y gastos.

- `back/comprar.php`
  - Método: POST
  - Parámetros:
    - `person_id`
    - `producto_id`
    - `descripcion` (opcional)

## API externa utilizada
- GET `http://localhost:8083/api/account/{personId}`
- POST `http://localhost:8083/api/account/{personId}/deduct`

Referencia Swagger:
- `http://localhost:8083/swagger-ui/index.html`

## Modelo de datos (resumen)
- `categorias` (id, nombre)
- `usuarios` (id, person_id, nombre)
- `productos` (id, nombre, precio, categoria_id)
- `gastos` (id, usuario_id, producto_id, monto, descripcion, tamalbits, fecha)

Relaciones:
- categorias 1:N productos
- usuarios 1:N gastos
- productos 1:N gastos

## Notas importantes
- No se modifica saldo directamente en la base de datos.
- El saldo mostrado siempre proviene de la API.
- El monto almacenado del gasto corresponde al descuento real validado.

## Problemas comunes
- Error de conexión API:
  - Verificar que bank-service esté activo en `localhost:8083`.
- Error de conexión MySQL:
  - Revisar usuario, contraseña y nombre de BD en `back/conexion.php`.
- Pantalla sin datos:
  - Verificar ejecución de `database/crearDb.sql` y existencia de productos.

## Autoría
Proyecto académico para taller de Desarrollo Web.
