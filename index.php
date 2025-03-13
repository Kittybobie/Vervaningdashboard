<?php
include 'config.php';
session_start();

// Haal een standaard teacher_id uit de database
$result = $conn->query("SELECT id FROM teachers LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $_SESSION['teacher_id'] = $row['id'];
}
$teacher_id = $_SESSION['teacher_id'] ?? null;

if (!$teacher_id) {
    die("Fout: Geen leerkracht gevonden in de database.");
}

// Definieer de dagen van de week
$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

// Verkrijg de geselecteerde dag uit de sessie en valideer deze
if (!isset($_SESSION['selected_day']) || !in_array($_SESSION['selected_day'], $days_of_week)) {
    $_SESSION['selected_day'] = 'Monday'; // Standaardwaarde instellen als de dag ongeldig is
}
$selected_day = $_SESSION['selected_day'];

// Controleer of er een POST-verzoek is gedaan om de dag te wijzigen en valideer deze
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['day']) && in_array($_POST['day'], $days_of_week)) {
    $_SESSION['selected_day'] = $_POST['day'];
    $selected_day = $_POST['day'];
}

// Zoek de index van de huidige geselecteerde dag
$current_day_index = array_search($selected_day, $days_of_week);

// Bereken de correcte datum
$selected_date = date('Y-m-d', strtotime("Monday this week +{$current_day_index} days"));

// Debug: Controleer de geselecteerde datum
error_log("Geselecteerde datum: " . $selected_date);

// SQL-query om leerkrachten en hun aanwezigheid op te halen
$sql = "SELECT t.name AS teacher_name, a.date, a.hour, a.status, a.reason, a.tasks 
        FROM attendance a
        JOIN teachers t ON a.teacher_id = t.id
        WHERE a.date = ? AND a.status IN ('aanwezig', 'in vergadering')
        ORDER BY a.hour ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $selected_date);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[$row['date']][$row['teacher_name']][] = $row;
}
$stmt->close();
?>



<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Vervangingen - Aanwezigheidsdashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
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
        .empty-message {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            color: #666;
            padding: 20px;
        }
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

    <div class="day-nav">
        <form method="POST" action="">
            <button type="submit" name="day" value="<?php echo $previous_day; ?>" class="btn btn-outline-primary btn-day">&#8592; Vorige Dag</button>
        </form>

        <h2><?php echo htmlspecialchars($selected_day); ?></h2>

        <form method="POST" action="">
            <button type="submit" name="day" value="<?php echo $next_day; ?>" class="btn btn-outline-primary btn-day">Volgende Dag &#8594;</button>
        </form>
    </div>

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
                <?php if (empty($data)) : ?>
                    <tr>
                        <td colspan="5" class="empty-message">Geen vervangingen vandaag</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($data as $day => $teachers) : ?>
                        <?php foreach ($teachers as $teacher_name => $lessons) : ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($teacher_name); ?></td>
                                <?php if (empty($lessons)) : ?>
                                    <td colspan="4" class="text-success">Geen gegevens beschikbaar</td>
                                <?php else : ?>
                                    <?php foreach ($lessons as $lesson) : ?>
                                        <td>Lesuur <?php echo htmlspecialchars($lesson['hour'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($lesson['status'] ?? 'Onbekend'); ?></td>
                                        <td><?php echo htmlspecialchars($lesson['reason'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($lesson['tasks'] ?? '-'); ?></td>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

<?php $conn->close(); ?>
