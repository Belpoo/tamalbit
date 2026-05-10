<?php

// Script de prueba manual: consulta una cuenta fija y muestra respuesta simple en HTML.
include("api_client.php");

$personId = "240420241052";

function e($value)
{
    // Escape básico para evitar inyección HTML al imprimir datos remotos.
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$response = api_get_account($personId);

if (!$response["ok"] || !isset($response["data"]["balance"])) {
    die("Error consumiendo API");
}

$data = $response["data"];

echo "<h1>Datos de la cuenta</h1>";

echo "Nombre: " . e($data["name"] ?? "N/A") . "<br>";
echo "Saldo: $" . e(number_format((float)($data["balance"] ?? 0), 2)) . "<br>";
echo "Person ID: " . e($data["personId"] ?? $personId);

?>