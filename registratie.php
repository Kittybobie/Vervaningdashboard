<?php
session_start();
// Databaseverbinding
$mysqli = new mysqli("localhost", "root", "", "aanwezigheidsdashboard");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Verwerk formulier indien ingediend
$selected_day = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sla de geselecteerde dag op
    $selected_day = $_POST['day'] ?? '';

// Verwerk aanwezigheid
if (isset($_POST['leraar_id']) && is_array($_POST['leraar_id'])) {
    $teacher_ids = $_POST['leraar_id'];
    $status = $_POST['status'] ?? [];
    $reden = $_POST['reden'] ?? [];
    $tasks = $_POST['tasks'] ?? [];

    // Loop door de leerkrachten
    foreach ($teacher_ids as $index => $leraar_id) {
        // Loop door de 8 lesuren (1 t/m 8)
        for ($hour = 1; $hour <= 8; $hour++) {
            // Verkrijg de status voor het huidige uur, standaard op 'present' als er geen keuze is
            $current_status = $status[$leraar_id][$hour] ?? 'present';
            
            // Verkrijg de reden en taak voor het huidige uur (null als leeg)
            $current_reden = $reden[$leraar_id][$hour] ?? null;
            $current_tasks = $tasks[$leraar_id][$hour] ?? null;

            // Debugging: Log de waarden
            error_log("Teacher ID: $leraar_id, Day: $selected_day, Hour: $hour, Status: $current_status, Reason: $current_reden, Tasks: $current_tasks");

            // Zorg ervoor dat lege velden voor reden en taak als NULL worden opgeslagen
            $current_reden = $current_reden ?: NULL;
            $current_tasks = $current_tasks ?: NULL;

            // Als de status niet 'afwezig' is, wordt de leerkracht als 'aanwezig' beschouwd
            if ($current_status === 'present') {
                // In dit geval moet de reden en taak ook NULL zijn, omdat er geen afwezigheid is
                $current_reden = NULL;
                $current_tasks = NULL;
            }

            // SQL-query om aanwezigheid in te voegen of bij te werken
            $sql = "INSERT INTO attendance (teacher_id, date, day, hour, status, reason, tasks) 
                    VALUES (?, CURDATE(), ?, ?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE status = ?, reason = ?, tasks = ?";

            $stmt = $mysqli->prepare($sql);
            if ($stmt === false) {
                error_log("Prepare failed: " . $mysqli->error);
                continue; // Ga verder met de volgende iteratie als prepare mislukt
            }

            // Bind de parameters (waarbij $selected_day wordt gebruikt voor de 'day' kolom)
            $stmt->bind_param("issssssss", $leraar_id, $selected_day, $hour, $current_status, $current_reden, $current_tasks, $current_status, $current_reden, $current_tasks);

            // Voer de query uit
            if (!$stmt->execute()) {
                error_log("Execute failed: " . $stmt->error);
            }

            $stmt->close();
        }
    }
}

    // Zoek leerkrachten
    if (isset($_POST['search'])) {
        $teacher_name = $mysqli->real_escape_string($_POST['teacher_name']);
        $sql = "SELECT * FROM teachers WHERE name LIKE '%$teacher_name%'";
        $result = $mysqli->query($sql);
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Aanwezigheidsdashboard</title>
    <style>
        /* Algemene body-stijl */
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f8ff;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-top: 200px;
        }

        h1 {
            color: #007bff;
            margin-bottom: 20px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-day{
            margin-left:52px;
            margin-bottom: 15px;
        }

        .btn-primary{
            margin-top: 20px;
            width: 100%;
            display:block;
        }

        .btn-zoeken{
            width: 100%;
            display:block;
            margin-top: 20px;
        }

        button:hover {
            background-color: #0056b3;
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

    <!-- Weergave van de geselecteerde dag -->
    <?php if ($selected_day): ?>
        <h2 style="text-align: center;">Geselecteerde dag: <?php echo htmlspecialchars($selected_day); ?></h2>
    <?php endif; ?>

    <!-- Keuze voor de dag -->
    <form method="POST">
        <button type="submit" name="day" class ="btn-day" value="Maandag">Maandag</button>
        <button type="submit" name="day" class ="btn-day" value="Dinsdag">Dinsdag</button>
        <button type="submit" name="day" class ="btn-day" value="Woensdag">Woensdag</button>
        <button type="submit" name="day" class ="btn-day" value="Donderdag">Donderdag</button>
        <button type="submit" name="day" class ="btn-day" value="Vrijdag">Vrijdag</button>
    </form>

    <!-- Zoekformulier -->
    <form method="POST" class="form-group">
        <input type="text" name="teacher_name" placeholder="Zoek leerkracht..." required>
        <button type="submit" class ="btn-zoeken" name="search">Zoeken</button>
    </form>

    <!-- Zoekresultaten -->
    <?php if (isset($result) && $result->num_rows > 0): ?>
        <form method="POST">
            <table>
                <tr>
                    <th>Naam</th>
                    <th>Lesuur</th>
                    <th>Status</th>
                    <th>Reden</th>
                    <th>Taak</th>
                </tr>
                <?php while ($teacher = $result->fetch_assoc()): ?>
                    <?php for ($hour = 1; $hour <= 8; $hour++): ?>
                        <tr>
                            <?php if ($hour == 1): ?>
                                <td rowspan="8"><?php echo htmlspecialchars($teacher['name']); ?></td>
                            <?php endif; ?>
                            <td><?php echo "Lesuur $hour"; ?></td>
                            <input type='hidden' name='leraar_id[]' value='<?php echo htmlspecialchars($teacher['id']); ?>'>
                            <td>
                                <select name='status[<?php echo $teacher['id']; ?>][<?php echo $hour; ?>]' required>
                                    <option value='present'>Aanwezig</option>
                                    <option value='absent'>Afwezig</option>
                                    <option value='meeting'>In vergadering</option>
                                </select>
                            </td>
                            <td>
                                <textarea name='reden[<?php echo $teacher['id']; ?>][<?php echo $hour; ?>]' placeholder='Reden (optioneel)'></textarea>
                            </td>
                            <td>
                                <textarea name='tasks[<?php echo $teacher['id']; ?>][<?php echo $hour; ?>]' placeholder='Taak (optioneel)'></textarea>
                            </td>
                        </tr>
                    <?php endfor; ?>
                <?php endwhile; ?>
            </table>
            <button type="submit" class="btn btn-primary">Opslaan</button>
        </form>
    <?php elseif (isset($result)): ?>
        <p>Geen leerkrachten gevonden.</p>
    <?php endif; ?>
</div>

</body>
</html>

<?php
// Sluit de databaseverbinding
$mysqli->close();
?>
