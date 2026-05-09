<?php
$queryString = $_SERVER["QUERY_STRING"] ?? "";
$target = "front/index.html";

if ($queryString !== "") {
    $target .= "?" . $queryString;
}

header("Location: " . $target);
exit;
