<?php
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

    // Controleer of er een POST-verzoek is gedaan om de dag te wijzigen
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['day'])) {
        $_SESSION['selected_day'] = $_POST['day'];
        $selected_day = $_POST['day'];
    }

    // Zoek de index van de huidige geselecteerde dag
    $current_day_index = array_search($selected_day, $days_of_week);
    $previous_day = $days_of_week[($current_day_index - 1 + count($days_of_week)) % count($days_of_week)];
    $next_day = $days_of_week[($current_day_index + 1) % count($days_of_week)];
    

    $sql = "SELECT t.name AS teacher_name, a.date, a.hour, a.status, a.reason, a.tasks 
    FROM attendance a
    JOIN teachers t ON a.teacher_id = t.id
    WHERE a.date = ?
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
                <?php foreach ($data as $day => $teachers) : ?>
                    <?php foreach ($teachers as $teacher_name => $lessons) : ?>
                        <?php 
                            $filtered_lessons = array_filter($lessons, function ($lesson) {
                                return in_array($lesson['status'], ['absent', 'meeting']);
                            });

                            if (!empty($filtered_lessons)) {
                                $rowspan = count($filtered_lessons);
                                $first_row = true;
                                foreach ($filtered_lessons as $lesson) {
                                    echo "<tr>";
                                    if ($first_row) {
                                        echo "<td rowspan='$rowspan' class='fw-bold'>" . htmlspecialchars($teacher_name ?? '') . "</td>";
                                        $first_row = false;
                                    }
                                    echo "<td>Lesuur " . htmlspecialchars($lesson['hour'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($lesson['status'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($lesson['reason'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($lesson['tasks'] ?? '') . "</td>";
                                    echo "</tr>";
                                }
                            }
                        ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

<?php $conn->close(); ?>
