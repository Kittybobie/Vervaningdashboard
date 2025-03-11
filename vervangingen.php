<?php
session_start();

// Definieer de dagen van de week
$days_of_week = ['Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag'];

// Verkrijg de geselecteerde dag uit de sessie (optioneel)
if (!isset($_SESSION['selected_day'])) {
    $_SESSION['selected_day'] = 'Maandag';  // Default naar Maandag als geen dag geselecteerd
}
$selected_day = $_SESSION['selected_day'];

// Databaseverbinding
$mysqli = new mysqli("localhost", "root", "", "aanwezigheidsdashboard");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Controleer of er een POST-verzoek is gedaan om de dag te wijzigen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['day'])) {
    $_SESSION['selected_day'] = $_POST['day']; // Update de sessie direct na de klik
    $selected_day = $_SESSION['selected_day'];  // Haal de nieuwe geselecteerde dag op uit de sessie
}

// Zoek de index van de huidige geselecteerde dag
$current_day_index = array_search($selected_day, $days_of_week);

// Bepaal de vorige en volgende dag
$previous_day = $days_of_week[($current_day_index - 1 + count($days_of_week)) % count($days_of_week)];
$next_day = $days_of_week[($current_day_index + 1) % count($days_of_week)];

// SQL-query om de vervangingen op te halen
$sql = "SELECT t.name AS teacher_name, a.day, a.hour, a.reason, a.tasks, a.status 
        FROM attendance a
        JOIN teachers t ON a.teacher_id = t.id
        WHERE a.status IN ('absent', 'meeting') AND a.day = ? 
        ORDER BY a.day, a.hour";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $selected_day);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Vervangingen - Aanwezigheidsdashboard</title>
    <style>
        /* Algemene stijlen voor de tabel en pagina */
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f8ff;
            color: #333;
        }

        .container {
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #007bff;
            text-align: center;
        }

        .day-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .day-nav button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .day-nav button:hover {
            background-color: #0056b3;
        }

        .selected-day {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Aanwezigheidsregistratie</h1>

    <!-- Navigatie voor dagen (vorige en volgende dag) -->
    <div class="day-nav">
        <form method="POST" action="">
            <button type="submit" name="day" value="<?php echo $previous_day; ?>">&#8592; Vorige Dag</button>
        </form>

        <form method="POST" action="">
            <button type="submit" name="day" value="<?php echo $next_day; ?>">Volgende Dag &#8594;</button>
        </form>
    </div>

    <!-- Tabel voor de vervangingen -->
    <h2>Vervangingen voor <?php echo htmlspecialchars($selected_day); ?></h2>
    <table>
        <tr>
            <th>Naam</th>
            <th>Lesuur</th>
            <th>Status</th>
            <th>Reden</th>
            <th>Taak</th>
        </tr>

        <?php
        // Weergeven van de vervangingen in de tabel
        if ($result->num_rows > 0) {
            $previous_teacher = ""; // Houd de vorige leerkracht bij
            $rowspan = 0; // Houd het aantal rijen bij voor rowspan

            // Eerst tellen we het aantal rijen per leerkracht
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[$row['teacher_name']][] = $row;
            }

            // Nu tonen we de gegevens met rowspan
            foreach ($data as $teacher_name => $lessons) {
                $rowspan = count($lessons); // Aantal lesuren voor deze leerkracht
                foreach ($lessons as $index => $lesson) {
                    if ($index === 0) {
                        // Eerste rij voor deze leerkracht
                        echo "<tr>";
                        echo "<td rowspan='" . $rowspan . "'>" . htmlspecialchars($teacher_name) . "</td>";
                        echo "<td>" . "Lesuur " . $lesson['hour'] . "</td>";
                        echo "<td>" . htmlspecialchars($lesson['status']) . "</td>"; // Status
                        echo "<td>" . htmlspecialchars($lesson['reason']) . "</td>"; // Reden
                        echo "<td>" . htmlspecialchars($lesson['tasks']) . "</td>"; // Taak
                        echo "</tr>";
                    } else {
                        // Volgende rijen voor dezelfde leerkracht
                        echo "<tr>";
                        echo "<td>" . "Lesuur " . $lesson['hour'] . "</td>";
                        echo "<td>" . htmlspecialchars($lesson['status']) . "</td>"; // Status
                        echo "<td>" . htmlspecialchars($lesson['reason']) . "</td>"; // Reden
                        echo "<td>" . htmlspecialchars($lesson['tasks']) . "</td>"; // Taak
                        echo "</tr>";
                    }
                }
            }
        } else {
            echo "<tr><td colspan='5'>Geen vervangingen gevonden.</td></tr>";
        }
        ?>
    </table>
</div>

</body>
</html>

<?php
// Sluit de databaseverbinding
$mysqli->close();
?>