<?php
$host = "bkqrr4fpabrirltwrq38-mysql.services.clever-cloud.com"; 
$user = "u9pgwjuq27e3npxv";
$password = "xqwOSonP0LnDFUMcqZDN";
$database = "bkqrr4fpabrirltwrq38";
$port = 3306;

$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    die("Verbinding mislukt: " . $conn->connect_error);
}

// Zet de juiste tekenset voor de verbinding
$conn->set_charset("utf8mb4");
?>
