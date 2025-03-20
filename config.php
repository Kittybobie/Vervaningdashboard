<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";  // of je Proxmox IP
$db   = "aanwezigheden";  // de database die je hebt aangemaakt
$user = "root";  // of de juiste gebruiker
$pass = "MySQL-Alessandro";  // het juiste wachtwoord voor de gebruiker
$port = "3306";  // standaard MySQL-poort

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Verbinding mislukt: " . $conn->connect_error);
}
?>
