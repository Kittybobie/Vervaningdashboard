<?php
include 'config.php'; // Zorgt dat de databaseconnectie wordt ingeladen
session_start();
// Databaseverbinding
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
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

    foreach ($teacher_ids as $index => $leraar_id) {
        for ($hour = 1; $hour <= 8; $hour++) {
            $current_status = $status[$leraar_id][$hour] ?? 'aanwezig';
            $current_reden = $_POST['reden'][$leraar_id][$hour] ?? null;
            $current_tasks = $_POST['tasks'][$leraar_id][$hour] ?? null;

            if (empty($current_reden)) {
                $current_reden = NULL;
            }
            if (empty($current_tasks)) {
                $current_tasks = NULL;
            }

            // ✅ Verwijder enkel het specifieke lesuur op de geselecteerde dag, andere dagen blijven staan
            if ($current_status === 'aanwezig') {
                $delete_sql = "DELETE FROM attendance WHERE teacher_id = ? AND day = ? AND hour = ? AND date = CURDATE()";
                $delete_stmt = $conn->prepare($delete_sql);
                if ($delete_stmt) {
                    $delete_stmt->bind_param("isi", $leraar_id, $selected_day, $hour);
                    $delete_stmt->execute();
                    $delete_stmt->close();
                } else {
                    error_log("Delete failed: " . $conn->error);
                }
            } else {
                // ✅ Controleer of het record al bestaat voor deze leraar, dag en lesuur
                $check_sql = "SELECT id FROM attendance WHERE teacher_id = ? AND day = ? AND hour = ? AND date = CURDATE()";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("isi", $leraar_id, $selected_day, $hour);
                $check_stmt->execute();
                $check_stmt->store_result();
                $record_exists = $check_stmt->num_rows > 0;
                $check_stmt->close();

                if ($record_exists) {
                    // ✅ Update bestaande entry
                    $update_sql = "UPDATE attendance 
                                   SET status = ?, reason = ?, tasks = ? 
                                   WHERE teacher_id = ? AND day = ? AND hour = ? AND date = CURDATE()";
                    $update_stmt = $conn->prepare($update_sql);
                    if ($update_stmt) {
                        $update_stmt->bind_param("sssssi", $current_status, $current_reden, $current_tasks, $leraar_id, $selected_day, $hour);
                        $update_stmt->execute();
                        $update_stmt->close();
                    } else {
                        error_log("Update failed: " . $conn->error);
                    }
                } else {
                    // ✅ Voeg een nieuwe entry toe
                    $insert_sql = "INSERT INTO attendance (teacher_id, date, day, hour, status, reason, tasks) 
                                   VALUES (?, CURDATE(), ?, ?, ?, ?, ?)";
                    $insert_stmt = $conn->prepare($insert_sql);
                    if ($insert_stmt) {
                        $insert_stmt->bind_param("isisss", $leraar_id, $selected_day, $hour, $current_status, $current_reden, $current_tasks);
                        $insert_stmt->execute();
                        $insert_stmt->close();
                    } else {
                        error_log("Insert failed: " . $conn->error);
                    }
                }
            }
        }
    }
}


    // Zoek leerkrachten
    if (isset($_POST['search'])) {
        $teacher_name = $conn->real_escape_string($_POST['teacher_name']);
        $sql = "SELECT * FROM teachers WHERE name LIKE '%$teacher_name%'";
        $result = $conn->query($sql);
    }

    $sql_reset_auto_increment = "ALTER TABLE attendance AUTO_INCREMENT = 1";
    // Voer de query uit
    if ($conn->query($sql_reset_auto_increment) === TRUE) {
        // success
    } else {
        // failure
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
            table-layout: fixed;
        }
        /* Table Headers */
        th {
            background: #007bff;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            display: table-cell;
            vertical-align: middle;
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
            text-align: center;
            vertical-align: middle;
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
        td textarea {
            width: 100%;
            min-height: 40px;
            resize: none;
            border: 2px solid #ccc;
            border-radius: 6px;
            padding: 8px;
            font-size: 14px;
            box-sizing: border-box;
        }
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

    <!-- Keuze voor de dag -->
    <form method="POST">
        <button type="submit" name="day" class="btn-day" value="Maandag">Maandag</button>
        <button type="submit" name="day" class="btn-day" value="Dinsdag">Dinsdag</button>
        <button type="submit" name="day" class="btn-day" value="Woensdag">Woensdag</button>
        <button type="submit" name="day" class="btn-day" value="Donderdag">Donderdag</button>
        <button type="submit" name="day" class="btn-day" value="Vrijdag">Vrijdag</button>
    </form>

    <!-- Zoekformulier -->
    <div class="search-container show" style="position: relative;">
        <form method="POST" class="form-group">
            <input type="text" id="teacher_search" name="teacher_name" placeholder="Zoek leerkracht..." required autocomplete="off">
            <button type="submit" class="btn-zoeken" name="search">Zoeken</button>
        </form>
        <div id="suggestions"></div>
    </div>

    <!-- Zoekresultaten -->
    <?php if (isset($result) && $result->num_rows > 0): ?>
        <form method="POST">
            <table>
            <tr>
                <th style="text-align: center; vertical-align: middle;">NAAM</th>
                <th style="text-align: center; vertical-align: middle;">LESUUR</th>
                <th style="text-align: center; vertical-align: middle;">STATUS</th>
                <th style="text-align: center; vertical-align: middle;">REDEN</th>
                <th style="text-align: center; vertical-align: middle;">TAAK</th>
            </tr>

            <!-- Extra rij onder de header voor globale instellingen -->
            <tr style="background-color: #f0f0f0;">
                <td colspan="2" style="text-align: center; font-weight: bold;">Globale instellingen:</td>
                <td style="text-align: center;">
                    <label for="setAllAbsent" style="display: inline-flex; align-items: center; gap: 5px; font-size: 14px; cursor: pointer;">
                        <input type="checkbox" id="setAllAbsent" style="transform: scale(1.2); cursor: pointer; text-align: center; vertical-align: middle;">
                        Volledige dag afwezig
                    </label>
                </td>
                <td style="text-align: center;">
                    <textarea id="globalReason" placeholder="Reden voor iedereen..." style="width: 90%; resize: none; "></textarea>
                </td>
                <td style="text-align: center;">
                    <textarea id="globalTask" placeholder="Taak voor iedereen..." style="width: 90%; resize: none;"></textarea>
                </td>
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
                                <!-- Aangepaste code: class "status-dropdown" toegevoegd -->
                                <select name='status[<?php echo $teacher['id']; ?>][<?php echo $hour; ?>]' class='status-dropdown' required>
                                    <option value='aanwezig'>Aanwezig</option>
                                    <option value='afwezig'>Afwezig</option>
                                    <option value='in vergadering'>In vergadering</option>
                                </select>
                            </td>
                            <td>
                                <textarea name='reden[<?php echo $teacher['id']; ?>][<?php echo $hour; ?>]' class='reason-field' placeholder='Reden (optioneel)'></textarea>
                            </td>
                            <td>
                                <textarea name='tasks[<?php echo $teacher['id']; ?>][<?php echo $hour; ?>]' class='task-field' placeholder='Taak (optioneel)'></textarea>
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

<script>
document.getElementById("setAllAbsent").addEventListener("change", function() {
    let statusDropdowns = document.querySelectorAll(".status-dropdown");
    let setToAbsent = this.checked;
    statusDropdowns.forEach(dropdown => {
        dropdown.value = setToAbsent ? "afwezig" : "aanwezig";
    });
});

// Vul alle "Reden" velden met de invoer van het globale tekstvak
document.getElementById("globalReason").addEventListener("input", function() {
    let allReasonFields = document.querySelectorAll(".reason-field");
    let reasonText = this.value;
    allReasonFields.forEach(field => {
        field.value = reasonText;
    });
});

// Vul alle "Taak" velden met de invoer van het globale tekstvak
document.getElementById("globalTask").addEventListener("input", function() {
    let allTaskFields = document.querySelectorAll(".task-field");
    let taskText = this.value;
    allTaskFields.forEach(field => {
        field.value = taskText;
    });
});
</script>


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
