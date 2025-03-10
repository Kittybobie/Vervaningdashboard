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
    $host = 'localhost';
    $db = 'aanwezigheidsdashboard';
    $user = 'root';
    $pass = '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }

    // Verwerk formulier indien ingediend
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $leraar_id = $_POST['leraar_id'];
        $status = $_POST['status'];
        $reden = $_POST['reden'] ?? null;

        $stmt = $pdo->prepare("INSERT INTO attendance (teacher_id, date, status, reason) VALUES (?, CURDATE(), ?, ?)");
        $stmt->execute([$leraar_id, $status, $reden]);
    }

    // Haal leerkrachten op
    $leraren = $pdo->query("SELECT * FROM teachers")->fetchAll();

    // Debug: Controleer of leerkrachten zijn opgehaald
    if (empty($leraren)) {
        echo "<p>Geen leerkrachten gevonden.</p>";
    }
?>
    <div class="container">
        <h1>Aanwezigheidsregistratie</h1>
        <form method="POST">
            <div class="form-group">
                <label for="leraar_id">Leerkracht:</label>
                <select name="leraar_id" class="form-control" required>
                    <?php foreach ($leraren as $leraar): ?>
                        <option value="<?php echo $leraar['id']; ?>"><?php echo $leraar['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Status:</label>
                <select name="status" class="form-control" required>
                    <option value="present">Aanwezig</option>
                    <option value="absent">Afwezig</option>
                    <option value="meeting">In vergadering</option>
                </select>
            </div>
            <div class="form-group">
                <label for="reden">Reden (optioneel):</label>
                <textarea name="reden" class="form-control"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Registreren</button>
        </form>
    </div>
</body>
</html>