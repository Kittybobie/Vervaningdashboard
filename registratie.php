<?php
include 'config.php'; // Laad databaseverbinding
session_start();

// **Controleer databaseverbinding**
if ($conn->connect_error) {
    die("Verbinding mislukt: " . $conn->connect_error);
}

// **Definieer de dagen correct**
$days_of_week = ['Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag']; // ✅ Toegevoegd
$dagen_mapping = [
    'Maandag' => 'Monday',
    'Dinsdag' => 'Tuesday',
    'Woensdag' => 'Wednesday',
    'Donderdag' => 'Thursday',
    'Vrijdag' => 'Friday'
];

// **Haal de geselecteerde dag op**
$selected_day = $_SESSION['selected_day'] ?? 'Maandag';

// **Als er een POST-verzoek is om de dag te wijzigen**
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['day'])) {
    $_SESSION['selected_day'] = $_POST['day'];
    $selected_day = $_POST['day'];
}

// **Zet de dag om naar een geldige datum van deze week**
if (isset($dagen_mapping[$selected_day])) {
    $selected_date = new DateTime('this week ' . $dagen_mapping[$selected_day]);
    $selected_date_formatted = $selected_date->format('Y-m-d');
} else {
    die("Ongeldige dag geselecteerd.");
}

// ✅ **Verwerk aanwezigheid**
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leraar_id'])) {
    $teacher_ids = $_POST['leraar_id'];
    $status = $_POST['status'] ?? [];

    foreach ($teacher_ids as $leraar_id) {
        // **Verwijder bestaande records voor deze leraar op deze dag**
        $delete_sql = "DELETE FROM attendance WHERE teacher_id = ? AND day = ? AND record_date = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        if ($delete_stmt) {
            $delete_stmt->bind_param("iss", $leraar_id, $selected_day, $selected_date_formatted);
            $delete_stmt->execute();
            $delete_stmt->close();
        }

        // **Loop door de lesuren en sla alleen records op als nodig**
        for ($hour = 1; $hour <= 8; $hour++) {
            $current_status = $status[$leraar_id][$hour] ?? 'aanwezig';
            $current_reden = $_POST['reden'][$leraar_id][$hour] ?? null;
            $current_tasks = $_POST['tasks'][$leraar_id][$hour] ?? null;
            $current_class = $_POST['class'][$leraar_id][$hour] ?? null;

            // **Sla alleen op als de status 'afwezig' of 'in vergadering' is**
            if ($current_status !== 'aanwezig') {
                $insert_sql = "INSERT INTO attendance (teacher_id, date, record_date, day, hour, status, reason, tasks, class) 
                               VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
            
                if ($insert_stmt) {
                    $insert_stmt->bind_param("ississss", $leraar_id, $selected_date_formatted, $selected_day, $hour, $current_status, $current_reden, $current_tasks, $current_class);
                    $insert_stmt->execute();
                    $insert_stmt->close();
                } else {
                    error_log("Insert failed: " . $conn->error);
                }
            }
        }
    }
}

// ✅ **Zoekfunctie (resultaten alleen tonen als er gezocht is)**
$result = null;
if (isset($_POST['search']) && !empty($_POST['teacher_name'])) {
    $teacher_name = $conn->real_escape_string($_POST['teacher_name']);
    $sql = "SELECT * FROM teachers WHERE name LIKE '%$teacher_name%'";
    $result = $conn->query($sql);
}

// ✅ **AUTO_INCREMENT resetten als de tabel leeg is**
$sql_check_empty = "SELECT COUNT(*) as total FROM attendance";
$empty_result = $conn->query($sql_check_empty);
$row = $empty_result->fetch_assoc();

if ($row['total'] == 0) {
    $sql_reset_auto_increment = "ALTER TABLE attendance AUTO_INCREMENT = 1";
    $conn->query($sql_reset_auto_increment);
}

?>


<!DOCTYPE html>
<html lang="nl">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta charset="UTF-8">
    <title>Aanwezigheidsdashboard</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(to right, rgba(29, 54, 96, 0.4), rgba(50, 90, 160, 0.4)), 
                        url('pop-bg.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #333;
            margin: 0;
            padding: 0;
        }


        /* Container */
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
            color: #1d3660; /* Nieuwe kleur */
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 15px;
            display:inline;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center; /* Centraal uitlijnen */
        }

        .header-title {
            margin-left: 175px;
            text-align: center;
            flex-grow: 1; /* Zorgt ervoor dat deze ruimte opvult */
        }

        .header-button {
            margin-left: 20px; /* Ruimte tussen de titel en de knop */
        }

        /* Dag selectie knoppen */
        .day-selection {
            display: flex;
            justify-content: center; /* Centraal uitlijnen */
            gap: 10px; /* Ruimte tussen de knoppen */
            margin-bottom: 15px;
        }

        .btn-day {
            background: #1d3660; /* Nieuwe kleur */
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 6px;
            transition: all 0.3s ease-in-out;
        }

        .btn-day:hover {
            background: #14284b; /* Iets donkerdere variant */
            color: white;
            box-shadow: 0 5px 12px rgba(29, 54, 96, 0.4);
        }

        /* Actieve dag knop */
        .active-day {
            background-color: #00fffb !important;
            color: black !important;
            font-weight: bold;
            border: 2px solid #00fffb;
            box-shadow: 0 3px 12px rgba(0, 255, 255, 0.6);
        }

        /* Zoekveld */
        input[type="text"] {
            width: calc(100% - 20px);
            padding: 12px;
            margin-top: 10px;
            border: 2px solid #1d3660; /* Nieuwe kleur */
            border-radius: 8px;
            font-size: 14px;
            transition: border 0.3s ease-in-out;
        }

        input[type="text"]:focus {
            border: 2px solid #1d3660; /* Nieuwe kleur */
            outline: none;
        }

        /* Zoekknop */
        .btn-zoeken {
            width: 100%;
            margin-top: 10px;
            background: #1d3660; /* Nieuwe kleur */
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease-in-out;
        }

        #suggestions {
            border: 1px solid #ccc;
            max-height: 200px;
            overflow-y: auto;
            position: absolute;
            background: white;
            z-index: 1000;
            width: 100%;
            margin-top: 2px; /* Zorgt voor een kleine scheiding */
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 6px;
        }

        .suggestion-item {
            padding: 12px;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out;
        }

        .suggestion-item:hover {
            background-color: #1d3660; /* Donkere kleur voor hover */
            color: white;
        }

        #teacher_search {
            width: 100%;
            padding: 10px;
            border: 2px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            transition: border 0.3s ease-in-out;
        }

        .btn-zoeken:hover {
            background: #14284b; /* Donkerdere tint */
        }

        /* Tabel */
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

        /* Tabel headers */
        th {
            background: #1d3660; /* Nieuwe kleur */
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            vertical-align: middle;
            padding: 12px;
            font-size: 14px;
        }

        /* Algemene tabel styling */
        th, td {
            padding: 14px;
            text-align: center;
            border: 1px solid #ddd;
        }

        select{
            width: 100%; /* Volledige breedte van de cel */
            border-radius: 8px; /* Afronding */
            border: 2px solid #ccc; /* Zachte rand */
            box-sizing: border-box; /* Zorgt ervoor dat padding en border niet de breedte beïnvloeden */
            background: #FAFAFA; /* Lichtgrijze achtergrond */
        }

        select:focus {
            border: 2px solid #1d3660; /* Donkerblauwe rand bij focus */
            outline: none; /* Verwijdert de standaard blauwe highlight */
        }

        textarea {
            width: 100%; /* Volledige breedte van de cel */
            min-height: 40px; /* Minimale hoogte */
            border-radius: 8px; /* Afronding */
            border: 2px solid #ccc; /* Zachte rand */
            padding: 8px; /* Ruimte binnenin */
            font-size: 14px;
            resize: none; /* Gebruiker kan de grootte niet aanpassen */
            box-sizing: border-box; /* Zorgt ervoor dat padding en border niet de breedte beïnvloeden */
            background: #FAFAFA; /* Lichtgrijze achtergrond */
        }

        textarea:focus {
            border: 2px solid #1d3660; /* Donkerblauwe rand bij focus */
            outline: none; /* Verwijdert de standaard blauwe highlight */
        }

        /* Opslaan knop */
        .btn-primary {
            width: 100%;
            margin-top: 20px;
            background: #1d3660; /* Nieuwe kleur */
            color: white;
            border: none;
            padding: 12px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease-in-out;
        }

        .btn-primary:hover {
            background: #14284b; /* Donkerdere tint */
        }

        /* Responsiviteit */
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
<div class="header-container">
    <h1 class="header-title">Aanwezigheidsregistratie</h1>
    <p class="header-button">
        <a href="delete.php" class="btn btn-day">Wijzigen</a>
    </p>
</div>

    <div class="day-selection">
        <form method="POST">
            <?php foreach ($days_of_week as $day): ?>
                <button type="submit" name="day" value="<?php echo $day; ?>"
                    class="btn btn-day <?php echo ($selected_day === $day) ? 'active-day' : ''; ?>">
                    <?php echo $day; ?>
                </button>
            <?php endforeach; ?>
        </form>
    </div>

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
                <th style="text-align: center; vertical-align: middle;">Klas</th>
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
                <td style="text-align: center; font-weight: bold;">-</td>
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
                                <textarea name='class[<?php echo $teacher['id']; ?>][<?php echo $hour; ?>]' class='class-field' placeholder='Klas (optioneel)'></textarea>
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
