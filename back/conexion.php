<?php

$host = "localhost";
$usuario = "adminSS";
$password = "ss1234";
$bd = "tamalbit_db";

$conn = mysqli_connect($host, $usuario, $password, $bd);

if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
?>