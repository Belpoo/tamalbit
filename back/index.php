<?php
// Entrada auxiliar de /back: redirige al frontend y conserva query params.
$queryString = $_SERVER["QUERY_STRING"] ?? "";
$target = "../front/index.html";

if ($queryString !== "") {
    $target .= "?" . $queryString;
}

header("Location: " . $target);
exit;