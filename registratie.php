<?php
include 'config.php'; // Zorgt dat de databaseconnectie wordt ingeladen
session_start();
// Databaseverbinding
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Verwerk formulier indien ingediend
$tasks = [];
$reden = [];

// Verkrijg de geselecteerde dag uit de sessie
$selected_day = $_SESSION['selected_day'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sla de geselecteerde dag op in de sessie
    if (isset($_POST['day'])) {
        $_SESSION['selected_day'] = $_POST['day'];  // Bewaar de geselecteerde dag in de sessie
        $selected_day = $_SESSION['selected_day']; // Update de lokale variabele
    }
    
    // Verwerk aanwezigheid
    if (isset($_POST['leraar_id']) && is_array($_POST['leraar_id'])) {
        $teacher_ids = $_POST['leraar_id'];
        $status = $_POST['status'] ?? [];

        // Loop door de leerkrachten
        foreach ($teacher_ids as $index => $leraar_id) {
            // Loop door de 8 lesuren (1 t/m 8)
            for ($hour = 1; $hour <= 8; $hour++) {
                // Verkrijg de status voor het huidige uur, standaard op 'present' als er geen keuze is
                $current_status = $status[$leraar_id][$hour] ?? 'present';
                
                // Verkrijg de reden en taak voor het huidige uur (null als leeg)
                $current_reden = $_POST['reden'][$leraar_id][$hour] ?? null;
                $current_tasks = $_POST['tasks'][$leraar_id][$hour] ?? null;

                // Zorg ervoor dat lege velden voor reden en taak als NULL worden opgeslagen
                if (empty($current_reden)) {
                    $current_reden = NULL;
                }

                if (empty($current_tasks)) {
                    $current_tasks = NULL;
                }
                

                // SQL-query om aanwezigheid in te voegen of bij te werken
                $sql = "INSERT INTO attendance (teacher_id, date, day, hour, status, reason, tasks) 
                VALUES (?, CURDATE(), ?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                    day = ?, status = ?, reason = ?, tasks = ?";

                $stmt = $conn->prepare($sql);

                if ($stmt === false) {
                    error_log("Prepare failed: " . $mysqli->error);
                    continue;
                }

                // Bind de parameters
                $stmt->bind_param("isssssssss", $leraar_id, $selected_day, $hour, $current_status, $current_reden, $current_tasks, $selected_day, $current_status, $current_reden, $current_tasks);

                // Voer de query uit
                if (!$stmt->execute()) {
                    error_log("Execute failed: " . $stmt->error);
                }

                $conn->close();

            }
        }
    }

    // Zoek leerkrachten
    if (isset($_POST['search'])) {
        $teacher_name = $mysqli->real_escape_string($_POST['teacher_name']);
        $sql = "SELECT * FROM teachers WHERE name LIKE '%$teacher_name%'";
        $result = $mysqli->query($sql);
    }

    $sql_reset_auto_increment = "ALTER TABLE attendance AUTO_INCREMENT = 1";
    // Voer de query uit
    if ($mysqli->query($sql_reset_auto_increment) === TRUE) {
        
    } else {
        
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta charset="UTF-8">
    <title>Aanwezigheidsdashboard</title>
    <style>
        /* General Page Styling */
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(to right, #cfe2ff, #e7f0ff);
            color: #333;
            margin: 0;
            padding: 0;
        }

        /* Main Container */
        .container {
            max-width: 950px;
            margin: 50px auto;
            padding: 25px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease-in-out;
        }

        /* Header */
        h1 {
            text-align: center;
            color: #007bff;
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        /* Selected Day */
        h2 {
            text-align: center;
            color: #555;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        /* Buttons Styling */
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease-in-out;
            font-weight: 600;
            box-shadow: 0 3px 8px rgba(0, 123, 255, 0.3);
        }

        button:hover {
            background: #0056b3;
            box-shadow: 0 5px 12px rgba(0, 123, 255, 0.4);
        }

        /* Day Selection Buttons */
        .btn-day {
            display: inline-block;
            width: 18%;
            margin: 5px;
            text-align: center;
            font-size: 14px;
            border-radius: 8px;
        }

        /* Search Input */
        input[type="text"] {
            width: calc(100% - 20px);
            padding: 12px;
            margin-top: 10px;
            border: 2px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
            transition: border 0.3s ease-in-out;
        }

        input[type="text"]:focus {
            border: 2px solid #007bff;
            outline: none;
        }

        /* Suggestions Dropdown */
        #suggestions {
            border: 1px solid #ccc;
            max-height: 150px;
            overflow-y: auto;
            position: absolute;
            background: white;
            z-index: 1000;
            width: calc(100% - 20px);
            margin-top: -60px; /* Adjust to align with input */
        }

        .suggestion-item {
            padding: 10px;
            cursor: pointer;
        }

        .suggestion-item:hover {
            background-color: #f0f0f0;
        }

        /* Search Button */
        .btn-zoeken {
            width: 100%;
            margin-top: 10px;
        }

        /* Table Styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 15px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            table-layout: fixed; /* ✅ Ensures proper width handling */
        }

        /* Table Headers */
        th {
            background: #007bff;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center; /* ✅ Centers header text */
            display:table-cell;
            vertical-align: middle; /* ✅ Ensures text is vertically centered */
        }

        /* Table Cells */
        th, td {
            padding: 14px;
            text-align: left;
            border: 1px solid #ddd;
            word-wrap: break-word;
            overflow: hidden;
            white-space: nowrap;
        }

        /* Name Column Styling */
        td {
            font-weight: bold;
            width: 15%;
            text-align: center; /* ✅ Centers text horizontally */
            vertical-align: middle; /* ✅ Centers text vertically */
        }

        /* Dropdown and Input Fields */
        select, textarea {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 2px solid #ccc;
            font-size: 14px;
            transition: border 0.3s ease-in-out;
            background: #FAFAFA;
            color: #333;
        }

        /* ✅ Ensure textareas are inside table cells properly */
        td textarea {
            width: 100%;
            min-height: 40px;
            resize: none;
            border: 2px solid #ccc;
            border-radius: 6px;
            padding: 8px;
            font-size: 14px;
            box-sizing: border-box; /* ✅ Prevents overflow */
        }

        /* Focus effect */
        select:focus, textarea:focus {
            border: 2px solid #007bff;
            outline: none;
        }

        /* Save Button */
        .btn-primary {
            width: 100%;
            margin-top: 20px;
            padding: 12px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 8px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                width: 95%;
                padding: 15px;
            }

            .btn-day {
                width: 45%;
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

    <!-- Weergave van de geselecteerde dag -->
    <?php if ($selected_day): ?>
        <h2 style="text-align: center;">Geselecteerde dag: <?php echo htmlspecialchars($selected_day); ?></h2>
    <?php endif; ?>

    <!-- Keuze voor de dag -->
    <form method="POST">
        <button type="submit" name="day" class="btn-day" value="Maandag">Maandag</button>
        <button type="submit" name="day" class="btn-day" value="Dinsdag">Dinsdag</button>
        <button type="submit" name="day" class="btn-day" value="Woensdag">Woensdag</button>
        <button type="submit" name="day" class="btn-day" value="Donderdag">Donderdag</button>
        <button type="submit" name="day" class="btn-day" value="Vrijdag">Vrijdag</button>
    </form>

    <!-- Zoekformulier (verschijnt alleen als een dag is geselecteerd) -->
    <?php if ($selected_day): ?>
        <div class="search-container show" style="position: relative;">
            <form method="POST" class="form-group">
                <input type="text" id="teacher_search" name="teacher_name" style="margin-right: 15px; width:97.2%;" placeholder="Zoek leerkracht..." required autocomplete="off">
                <button type="submit" class="btn-zoeken" name="search">Zoeken</button>
            </form>
            <div id="suggestions"></div> <!-- Suggestions dropdown -->
        </div>
    <?php endif; ?>

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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#teacher_search').on('keyup', function() {
        let searchTerm = $(this).val();
        if (searchTerm.length >= 1) {
            $.ajax({
                url: 'autocomplete.php',
                type: 'GET',
                data: { term: searchTerm },
                success: function(data) {
                    let suggestions = JSON.parse(data);
                    $('#suggestions').empty();
                    suggestions.forEach(function(name) {
                        $('#suggestions').append('<div class="suggestion-item">' + name + '</div>');
                    });
                }
            });
        } else {
            $('#suggestions').empty();
        }
    });

    $(document).on('click', '.suggestion-item', function() {
        $('#teacher_search').val($(this).text());
        $('#suggestions').empty();
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Sluit de databaseverbinding
$conn->close();

?>