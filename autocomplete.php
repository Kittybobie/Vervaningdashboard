<?php
include 'config.php'; // Zorgt dat de databaseconnectie wordt ingeladen
session_start();

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['term'])) {
    $term = $conn->real_escape_string($_GET['term']);
    $query = "SELECT name FROM teachers WHERE name LIKE '%$term%' LIMIT 10";
    $result = $conn->query($query);

    $names = [];
    while ($row = $result->fetch_assoc()) {
        $names[] = $row['name'];
    }
    echo json_encode($names);
}

$conn->close();
?>