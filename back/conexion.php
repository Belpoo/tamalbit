<?php

// Configuración base de conexión MySQL usada por los scripts de backend/API.
$host = "localhost";
$usuario = "adminSS";
$password = "ss1234";
$bd = "tamalbit_db";

// Apertura de conexión; si falla, se corta la ejecución para evitar operaciones inconsistentes.
$conn = mysqli_connect($host, $usuario, $password, $bd);

if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Charset recomendado para textos con acentos y caracteres UTF-8.
mysqli_set_charset($conn, "utf8mb4");
?>