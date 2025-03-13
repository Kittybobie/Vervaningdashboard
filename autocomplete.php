<?php
include 'config.php'; // Zorgt dat de databaseconnectie wordt ingeladen
?>
<?php
session_start();
$host = "bkqrr4fpabrirltwrq38-mysql.services.clever-cloud.com";
$db   = "bkqrr4fpabrirltwrq38";
$user = "u9pgwjuq27e3npxv";
$pass = "xqwOSonP0LnDFUMcqZDN";
$port = "3306";

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

if (isset($_GET['term'])) {
    $term = $mysqli->real_escape_string($_GET['term']);
    $query = "SELECT name FROM teachers WHERE name LIKE '%$term%' LIMIT 10";
    $result = $mysqli->query($query);

    $names = [];
    while ($row = $result->fetch_assoc()) {
        $names[] = $row['name'];
    }
    echo json_encode($names);
}

$mysqli->close();
?>