<?php
include __DIR__ . '/config.php';
include 'config.php';
session_start();

// Haal een standaard teacher_id uit de database
$result = $conn->query("SELECT id FROM teachers LIMIT 1");
$row = $result->fetch_assoc();
$_SESSION['teacher_id'] = $row['id'] ?? null;

$teacher_id = $_SESSION['teacher_id'];

if (!$teacher_id) {
    die("Fout: Geen leerkracht gevonden in de database.");
}

// Definieer de dagen van de week
$days_of_week = ['Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag'];

// Verkrijg de geselecteerde dag uit de sessie
if (!isset($_SESSION['selected_day'])) {
    $_SESSION['selected_day'] = 'Maandag';
}
$selected_day = $_SESSION['selected_day'];

// Databaseverbinding
if ($conn->connect_error) {
    die("Verbindingsfout: " . $conn->connect_error);
}


// Controleer of er een POST-verzoek is gedaan om de dag te wijzigen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['day'])) {
    $_SESSION['selected_day'] = $_POST['day'];
    $selected_day = $_SESSION['selected_day'];
}

// Zoek de index van de huidige geselecteerde dag
$current_day_index = array_search($selected_day, $days_of_week);

// Bepaal de vorige en volgende dag
$previous_day = $days_of_week[($current_day_index - 1 + count($days_of_week)) % count($days_of_week)];
$next_day = $days_of_week[($current_day_index + 1) % count($days_of_week)];

// SQL-query om de vervangingen op te halen
$sql = "INSERT INTO attendance (teacher_id, date, day, hour, status, reason, tasks) 
        VALUES (?, CURDATE(), ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        day = VALUES(day), 
        status = VALUES(status), 
        reason = VALUES(reason), 
        tasks = VALUES(tasks)";

var_dump($teacher_id);
$stmt = $conn->prepare($sql);
$stmt->bind_param("isssss", $teacher_id, $selected_day, $hour, $status, $reason, $tasks);
$stmt->execute();
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Vervangingen - Aanwezigheidsdashboard</title>
    <!-- ✅ Bootstrap toegevoegd -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Algemene stijlen */
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(to right, #cfe2ff, #e7f0ff);
            color: #333;
            display: flex;
            justify-content: center;
            align-items: top;
            margin-top:50px;

        }

        .container {
            max-width: 950px;
            background-color: #ffffff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #007bff;
            font-size: 28px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 10px;
        }

        h2 {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #444;
        }

        /* ✅ Duidelijkere dag navigatie */
        .day-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .btn-day {
            font-size: 16px;
            padding: 10px 15px;
            font-weight: bold;
            border-radius: 8px;
            transition: all 0.3s ease-in-out;
        }

        .btn-day:hover {
            background-color: #0056b3;
            color: white;
        }

        /* ✅ Tabelstijl */
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }

        /* ✅ Koptekst centreren en visueel aantrekkelijk maken */
        th {
            background: #007bff;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            vertical-align: middle;
            padding: 12px;
            font-size: 14px;
        }

        th, td {
            text-align: center;
            padding: 12px;
            border: 1px solid #ddd;
        }

        /* ✅ Leeg bericht visueel opvallend maken */
        .empty-message {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            color: #666;
            padding: 20px;
        }

        /* ✅ Responsief maken */
        @media (max-width: 768px) {
            .container {
                width: 95%;
                padding: 15px;
            }
            table {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Aanwezigheidsregistratie</h1>

    <!-- ✅ Navigatie voor dagen -->
    <div class="day-nav">
        <form method="POST" action="">
            <button type="submit" name="day" value="<?php echo $previous_day; ?>" class="btn btn-outline-primary btn-day">&#8592; Vorige Dag</button>
        </form>

        <h2><?php echo htmlspecialchars($selected_day); ?></h2>

        <form method="POST" action="">
            <button type="submit" name="day" value="<?php echo $next_day; ?>" class="btn btn-outline-primary btn-day">Volgende Dag &#8594;</button>
        </form>
    </div>

    <!-- ✅ Tabel voor de vervangingen -->
    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th>Naam</th>
                    <th>Lesuur</th>
                    <th>Status</th>
                    <th>Reden</th>
                    <th>Taak</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    $data = [];
                    while ($row = $result->fetch_assoc()) {
                        $data[$row['teacher_name']][] = $row;
                    }

                    foreach ($data as $teacher_name => $lessons) {
                        $rowspan = count($lessons);
                        foreach ($lessons as $index => $lesson) {
                            echo "<tr>";
                            if ($index === 0) {
                                echo "<td rowspan='$rowspan' class='fw-bold'>" . htmlspecialchars($teacher_name) . "</td>";
                            }
                            echo "<td>Lesuur " . $lesson['hour'] . "</td>";
                            echo "<td>" . htmlspecialchars($lesson['status']) . "</td>";
                            echo "<td>" . htmlspecialchars($lesson['reason']) . "</td>";
                            echo "<td>" . htmlspecialchars($lesson['tasks']) . "</td>";
                            echo "</tr>";
                        }
                    }
                } else {
                    echo "<tr><td colspan='5' class='empty-message'>Geen vervangingen gevonden.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ✅ Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

<?php
$conn->close();
?>
