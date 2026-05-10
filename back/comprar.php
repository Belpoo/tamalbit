<?php

include(__DIR__ . "/conexion.php");
include(__DIR__ . "/../api/api_client.php");

// Helper centralizado: redirige al frontend con estado y mensaje para mostrar alertas.
function redirect_to_index($status, $message, $personId = "")
{
    $query = "?status=" . urlencode($status) . "&msg=" . urlencode($message);

    if ($personId !== "") {
        $query .= "&personId=" . urlencode($personId);
    }

    header("Location: ../front/index.html" . $query);
    exit;
}

// Este endpoint solo acepta POST porque viene del boton "Comprar".
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect_to_index("error", "Solicitud invalida");
}

// 0) Leer y validar datos de entrada del formulario.
$personId = trim($_POST["person_id"] ?? "");
$productoId = filter_input(INPUT_POST, "producto_id", FILTER_VALIDATE_INT);
$descripcion = trim($_POST["descripcion"] ?? "");

if (!preg_match('/^\d{6,20}$/', $personId)) {
    redirect_to_index("error", "Codigo de usuario invalido");
}

if (!$productoId) {
    redirect_to_index("error", "Producto invalido", $personId);
}

if (strlen($descripcion) > 255) {
    $descripcion = substr($descripcion, 0, 255);
}

// 0.1) Cargar datos actuales del producto desde BD para usar precio/nombre reales.
$productoStmt = mysqli_prepare(
    $conn,
    "SELECT p.id, p.nombre, p.precio, c.nombre AS categoria_nombre
     FROM productos p
     INNER JOIN categorias c ON c.id = p.categoria_id
     WHERE p.id = ?"
);
mysqli_stmt_bind_param($productoStmt, "i", $productoId);
mysqli_stmt_execute($productoStmt);
$productoResult = mysqli_stmt_get_result($productoStmt);
$productoRow = mysqli_fetch_assoc($productoResult);
mysqli_stmt_close($productoStmt);

if (!$productoRow) {
    redirect_to_index("error", "El producto no existe", $personId);
}

$producto = $productoRow["nombre"];
$precio = (float)$productoRow["precio"];
$categoria = $productoRow["categoria_nombre"];


// ======================================
// 1) CONSULTAR SALDO ACTUAL EN API
// Se consulta primero para saber si el usuario puede pagar esta compra.
// ======================================

$accountBefore = api_get_account($personId);

if (!$accountBefore["ok"] || !isset($accountBefore["data"]["balance"])) {
    redirect_to_index("error", "No fue posible consultar el saldo actual", $personId);
}

$dataGet = $accountBefore["data"];

$saldoActual = (float)$dataGet["balance"];

$nombreUsuario = $dataGet["name"];


// ======================================
// 2) VALIDAR QUE ALCANCE EL SALDO
// ======================================

if($precio > $saldoActual){
    redirect_to_index("error", "Saldo insuficiente", $personId);
}


// ======================================
// 3) DESCONTAR SALDO EN API
// Aqui ocurre la operacion monetaria real (no en MySQL).
// ======================================

$responsePost = api_deduct_account($personId, $precio, "Compra de " . $producto);


// ======================================
// 4) VALIDAR QUE EL DESCUENTO SI SE APLICO
// Se vuelve a consultar saldo y se calcula el descuento real para auditar.
// ======================================

if(!$responsePost["ok"]){
    redirect_to_index("error", "Error descontando saldo en API", $personId);
}

$accountAfter = api_get_account($personId);

if (!$accountAfter["ok"] || !isset($accountAfter["data"]["balance"])) {
    redirect_to_index("error", "Descuento aplicado, pero no se pudo validar el saldo final", $personId);
}

$saldoDespues = (float)$accountAfter["data"]["balance"];
$descuentoReal = $saldoActual - $saldoDespues;

if ($descuentoReal <= 0) {
    redirect_to_index("error", "La API no reporto descuento valido", $personId);
}

$montoRegistrado = round($descuentoReal, 2);


// ======================================
// 5) CALCULAR TAMALBITS
// Regla actual: solo "orejas de pollo" suma 1 Tamalbit por cada $10 descontados.
// ======================================

$tamalbits = 0;

if(strtolower($producto) == "orejas de pollo"){

    $tamalbits = (int)floor($montoRegistrado / 10);
}


// ======================================
// 6) GUARDAR EN MYSQL
// Se guarda historial local (usuario, producto, monto real descontado y Tamalbits).
// ======================================

if ($descripcion === "") {
    $descripcion = "Compra realizada";
}

$upsertUsuarioStmt = mysqli_prepare(
    $conn,
    "INSERT INTO usuarios(person_id, nombre) VALUES(?, ?) ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)"
);
mysqli_stmt_bind_param($upsertUsuarioStmt, "ss", $personId, $nombreUsuario);
mysqli_stmt_execute($upsertUsuarioStmt);
mysqli_stmt_close($upsertUsuarioStmt);

$usuarioStmt = mysqli_prepare($conn, "SELECT id FROM usuarios WHERE person_id = ? LIMIT 1");
mysqli_stmt_bind_param($usuarioStmt, "s", $personId);
mysqli_stmt_execute($usuarioStmt);
$usuarioResult = mysqli_stmt_get_result($usuarioStmt);
$usuarioRow = mysqli_fetch_assoc($usuarioResult);
mysqli_stmt_close($usuarioStmt);

if (!$usuarioRow) {
    redirect_to_index("error", "No se pudo resolver el usuario en base de datos", $personId);
}

$usuarioId = (int)$usuarioRow["id"];

$insertStmt = mysqli_prepare(
    $conn,
    "INSERT INTO gastos(usuario_id, producto_id, monto, descripcion, tamalbits) VALUES(?, ?, ?, ?, ?)"
);

mysqli_stmt_bind_param(
    $insertStmt,
    "iidsi",
    $usuarioId,
    $productoId,
    $montoRegistrado,
    $descripcion,
    $tamalbits
);

mysqli_stmt_execute($insertStmt);
$insertOk = mysqli_stmt_affected_rows($insertStmt) > 0;
mysqli_stmt_close($insertStmt);

if (!$insertOk) {
    redirect_to_index("error", "No se pudo registrar el gasto en base de datos", $personId);
}

// ======================================
// 7) REDIRECCIONAR CON EXITO
// ======================================

redirect_to_index("ok", "Compra registrada exitosamente", $personId);

?>