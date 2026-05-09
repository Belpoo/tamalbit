# Documentacion - Sistema Tamalbit

## 1. Navegacion
- Pantalla principal: `front/index.html`
- Flujo de compra:
  - El usuario visualiza productos cargados desde base de datos.
  - Ingresa una descripcion opcional.
  - Presiona "Comprar" y envia POST a `back/comprar.php`.
  - `back/comprar.php` valida saldo en API, descuenta saldo en API y registra gasto en MySQL.
  - Redirecciona a `front/index.html` con mensaje de estado.
- Endpoint de datos del dashboard: `api/dashboard_data.php` (JSON)
- Consulta rapida de API: `api/obtener_saldo.php`

## 2. Tecnologia utilizada
- Backend: PHP (mysqli + consumo HTTP JSON)
- Base de datos: MySQL
- Frontend: HTML + CSS + JavaScript + Bootstrap 5
- API externa: bank-service 1.0 (`GET /api/account/{personId}`, `POST /api/account/{personId}/deduct`)

## 3. Reglas de negocio implementadas
- El saldo mostrado en la app proviene de la API.
- No se descuenta saldo directamente en BD.
- Antes de registrar un gasto:
  - Se consulta saldo actual en API.
  - Se valida que el precio no supere el saldo.
  - Se descuenta por API (POST).
  - Se vuelve a consultar saldo y se registra en BD el descuento real (`saldoAntes - saldoDespues`).
- Tamalbits:
  - Solo se calculan cuando la compra fue descontada correctamente por API.
  - Formula: `floor(montoDescontado / 10)` para el producto "Orejas de pollo".

## 4. Experiencia de usuario (UX)
- Interfaz responsiva (movil y desktop).
- Panel superior con metricas claras: usuario, saldo API, total gastado, tamalbits.
- Tarjetas de productos con formulario simple y descripcion opcional.
- Tabla de historial con datos de auditoria funcional.
- Alertas de exito/error para feedback inmediato.
- Animaciones suaves de entrada para mejorar percepcion visual.

## 5. Modelo Entidad-Relacion (ER)
Entidades:
- `categorias`
  - `id` (PK)
  - `nombre` (UNIQUE)
- `usuarios`
  - `id` (PK)
  - `person_id` (UNIQUE)
  - `nombre`
- `productos`
  - `id` (PK)
  - `nombre`
  - `precio`
  - `categoria_id` (FK -> `categorias.id`)
- `gastos`
  - `id` (PK)
  - `usuario_id` (FK -> `usuarios.id`)
  - `producto_id` (FK -> `productos.id`)
  - `monto`
  - `descripcion`
  - `tamalbits`
  - `fecha`

Relacion logica:
- Una categoria tiene muchos productos (1:N).
- Un usuario puede tener muchos gastos (1:N).
- Un producto puede estar en muchos gastos (1:N).
- El modelo evita duplicidad de nombre de usuario/producto/categoria en la tabla de gastos.

## 6. Notas de ejecucion
1. Ejecutar `database/crearDb.sql` en MySQL.
2. Verificar credenciales de `back/conexion.php`.
3. Levantar API bank-service en `localhost:8083`.
4. Abrir `index.php` (raiz del proyecto) o `front/index.html` desde el servidor local (XAMPP/Apache).

## 7. Estructura por carpetas
- `front/`: interfaz (HTML, CSS, JavaScript)
- `back/`: logica de negocio y acceso a BD
- `api/`: cliente de API externa y endpoints JSON auxiliares
- `database/`: scripts SQL del esquema
