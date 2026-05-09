<?php

header("Content-Type: application/json; charset=UTF-8");

include(__DIR__ . "/../back/conexion.php");
include(__DIR__ . "/api_client.php");

$personIdInput = trim($_GET["personId"] ?? "");
$personId = "";
$personIdError = null;
$apiError = null;
$saldo = 0.0;
$nombre = "No disponible";
$totalTamalbits = 0;
$totalGastado = 0.0;
$gastos = [];

if ($personIdInput !== "") {
    if (preg_match('/^\d{6,20}$/', $personIdInput)) {
        $personId = $personIdInput;
    } else {
        $personIdError = "El codigo debe tener solo numeros (6 a 20 digitos).";
    }
}

if ($personId !== "") {
    $accountResponse = api_get_account($personId);

    if ($accountResponse["ok"] && isset($accountResponse["data"]["balance"])) {
        $saldo = (float)$accountResponse["data"]["balance"];
        $nombre = $accountResponse["data"]["name"] ?? $nombre;
    } else {
        $apiError = "No fue posible leer el saldo desde la API. Verifica que bank-service este activo.";
    }

    $gastosStmt = mysqli_prepare(
        $conn,
        "SELECT
            u.nombre AS nombre_usuario,
            p.nombre AS producto,
            c.nombre AS categoria,
            g.monto,
            g.descripcion,
            g.tamalbits,
            g.fecha
        FROM gastos g
        INNER JOIN usuarios u ON u.id = g.usuario_id
        INNER JOIN productos p ON p.id = g.producto_id
        INNER JOIN categorias c ON c.id = p.categoria_id
        WHERE u.person_id = ?
        ORDER BY g.fecha DESC"
    );
    mysqli_stmt_bind_param($gastosStmt, "s", $personId);
    mysqli_stmt_execute($gastosStmt);
    $gastosResult = mysqli_stmt_get_result($gastosStmt);

    while ($row = mysqli_fetch_assoc($gastosResult)) {
        $gastos[] = [
            "nombre_usuario" => $row["nombre_usuario"],
            "producto" => $row["producto"],
            "categoria" => $row["categoria"],
            "monto" => (float)$row["monto"],
            "descripcion" => $row["descripcion"],
            "tamalbits" => (int)$row["tamalbits"],
            "fecha" => $row["fecha"],
        ];
    }

    mysqli_stmt_close($gastosStmt);

    $totalesStmt = mysqli_prepare(
        $conn,
        "SELECT
            COALESCE(SUM(g.tamalbits), 0) AS total_tamalbits,
            COALESCE(SUM(g.monto), 0) AS total_gastado
        FROM gastos g
        INNER JOIN usuarios u ON u.id = g.usuario_id
        WHERE u.person_id = ?"
    );
    mysqli_stmt_bind_param($totalesStmt, "s", $personId);
    mysqli_stmt_execute($totalesStmt);
    $totalesResult = mysqli_stmt_get_result($totalesStmt);
    $totalesData = mysqli_fetch_assoc($totalesResult);
    mysqli_stmt_close($totalesStmt);

    $totalTamalbits = (int)($totalesData["total_tamalbits"] ?? 0);
    $totalGastado = (float)($totalesData["total_gastado"] ?? 0);
}

$saldoInicialEstimado = $saldo + $totalGastado;
$productos = [];
$productosResult = mysqli_query(
    $conn,
    "SELECT p.id, p.nombre, p.precio, p.imagen_producto, c.nombre AS categoria
     FROM productos p
     INNER JOIN categorias c ON c.id = p.categoria_id
     ORDER BY c.nombre, p.nombre"
);

while ($producto = mysqli_fetch_assoc($productosResult)) {
    $productos[] = [
        "id" => (int)$producto["id"],
        "nombre" => $producto["nombre"],
        "precio" => (float)$producto["precio"],
        "imagen_producto" => $producto["imagen_producto"],
        "categoria" => $producto["categoria"],
    ];
}

echo json_encode([
    "personId" => $personId,
    "personIdError" => $personIdError,
    "apiError" => $apiError,
    "metrics" => [
        "nombre" => $nombre,
        "saldo" => $saldo,
        "totalGastado" => $totalGastado,
        "totalTamalbits" => $totalTamalbits,
        "saldoInicialEstimado" => $saldoInicialEstimado,
    ],
    "productos" => $productos,
    "gastos" => $gastos,
], JSON_UNESCAPED_UNICODE);
