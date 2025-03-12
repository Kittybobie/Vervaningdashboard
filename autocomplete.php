<?php
session_start();
$mysqli = new mysqli("localhost", "root", "", "aanwezigheidsdashboard");

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