<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "bkqrr4fpabrirltwrq38-mysql.services.clever-cloud.com";
$db   = "bkqrr4fpabrirltwrq38";
$user = "u9pgwjuq27e3npxv";
$pass = "xqwOSonP0LnDFUMcqZDN";
$port = "3306";

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Verbinding mislukt: " . $conn->connect_error);
} else {
    echo "Databaseverbinding geslaagd!";
}
?>
