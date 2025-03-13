<?php
$host = "bkqrr4fpabrirltwrq38-mysql.services.clever-cloud.com"; // MYSQL_ADDON_HOST
$user = "u9pgwjuq27e3npxv"; // MYSQL_ADDON_USER
$pass = "xqwOSonP0LnDFUMcqZDN"; // MYSQL_ADDON_PASSWORD
$dbname = "aanwezigheidsdashboard"; // <-- Hier zet je jouw database naam
$port = 3306; // MYSQL_ADDON_PORT

$conn = new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
    die("Database verbinding mislukt: " . $conn->connect_error);
}
?>
