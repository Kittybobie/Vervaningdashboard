<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Aanwezigheidsdashboard</title>

<style>
        /* Algemene body-stijl */
        body {
            font-family: Arial, sans-serif; /* Standaard lettertype */
            background-color: #f0f8ff; /* Lichtblauwe achtergrond */
            color: #333; /* Donkergrijze tekstkleur */
        }

        /* Container stijl */
        .container {
            max-width: 800px; /* Maximale breedte van de container */
            margin: 20px auto; /* Centraal uitlijnen met boven- en ondermarge */
            padding: 20px; /* Binnenmarge */
            background-color: #ffffff; /* Witte achtergrond voor de container */
            border-radius: 8px; /* Ronde hoeken */
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* Schaduw effect */
            margin-top: 200px;
        }

        /* Koptekst stijl */
        h1 {
            color: #007bff; /* Blauw voor de koptekst */
            margin-bottom: 20px; /* Onder marge */
        }

        /* Formulierstijl */
        .form-group {
            margin-bottom: 15px; /* Onder marge voor formuliergroepen */
        }

        label {
            display: block; /* Label als blok element */
            margin-bottom: 5px; /* Onder marge voor labels */
            font-weight: bold; /* Vetgedrukte tekst */
        }

        input[type="text"],
        input[type="email"],
        textarea,
        select {
            width: 100%; /* Volledige breedte */
            padding: 10px; /* Binnenmarge */
            border: 1px solid #ccc; /* Grijze rand */
            border-radius: 4px; /* Ronde hoeken */
        }

        /* Knopstijl */
        button {
            background-color: #007bff; /* Blauw voor de knop */
            color: white; /* Witte tekstkleur */
            border: none; /* Geen rand */
            padding: 10px 15px; /* Binnenmarge */
            border-radius: 4px; /* Ronde hoeken */
            cursor: pointer; /* Handcursor bij hover */
            transition: background-color 0.3s; /* Vervagingseffect bij hover */
        }

        button:hover {
            background-color: #0056b3; /* Donkerder blauw bij hover */
            color:black;
        }
</style>
</head>
<body>
<?php
        // Databaseverbinding
        $mysqli = new mysqli("localhost", "root", "", "aanwezigheidsdashboard");
        if ($mysqli->connect_error) {
            die("Connection failed: " . $mysqli->connect_error);
        }

        // Verwerk formulier indien ingediend
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $leraar_id = $_POST['leraar_id'];
            $status = $_POST['status'];
            $reden = $_POST['reden'] ?? null;

            $stmt = $mysqli->prepare("INSERT INTO attendance (teacher_id, date, status, reason) VALUES (?, CURDATE(), ?, ?)");
            if (!$stmt) {
                die("Error preparing query: " . $mysqli->error);
            }
            $stmt->bind_param("sss", $leraar_id, $status, $reden);
            $stmt->execute();
        }

        // Haal leerkrachten op
        $result = $mysqli->query("SELECT * FROM teachers");
        if (!$result) {
            die("Error fetching teachers: " . $mysqli->error);
        }

        // Controleer of er leerkrachten zijn gevonden
        if ($result->num_rows > 0) {
            echo "<div class='container'>";
            echo "<h1>Aanwezigheidsregistratie</h1>";
            echo "<form method='POST'>";
            echo "<div class='form-group'>";
            echo "<label for='leraar_id'>Leerkracht:</label>";
            echo "<select name='leraar_id' class='form-control' required>";
            
            while ($row = $result->fetch_assoc()) {
                echo "<option value='" . htmlspecialchars($row['id']) . "'>" . htmlspecialchars($row['name']) . "</option>";
            }
            
            echo "</select></div>";
            echo "<div class='form-group'>";
            echo "<label>Status:</label>";
            echo "<select name='status' class='form-control' required>";
            echo "<option value='present'>Aanwezig</option>";
            echo "<option value='absent'>Afwezig</option>";
            echo "<option value='meeting'>In vergadering</option>";
            echo "</select></div>";
            echo "<div class='form-group'>";
            echo "<label for='reden'>Reden (optioneel):</label>";
            echo "<textarea name='reden' class='form-control'></textarea>";
            echo "</div>";
            echo "<button type='submit' class='btn btn-primary'>Registreren</button>";
            echo "</form></div>";
        } else {
            echo "<p>Geen leerkrachten gevonden.</p>";
        }

        // Sluit de statement en de databaseverbinding
        $mysqli->close();
?>
</body>
</html>