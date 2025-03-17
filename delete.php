<?php
include 'config.php'; // Load DB connection
session_start();

// **Check database connection**
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// **Process delete request**
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    $delete_sql = "DELETE FROM attendance WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    if ($delete_stmt) {
        $delete_stmt->bind_param("i", $delete_id);
        $delete_stmt->execute();
        $delete_stmt->close();
    } else {
        die("Error deleting teacher attendance: " . $conn->error);
    }
}

// **Process update request**
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $update_id = intval($_POST['update_id']);
    $reason = $_POST['reason'];
    $tasks = $_POST['tasks'];

    $update_sql = "UPDATE attendance SET reason = ?, tasks = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    if ($update_stmt) {
        $update_stmt->bind_param("ssi", $reason, $tasks, $update_id);
        $update_stmt->execute();
        $update_stmt->close();
    } else {
        die("Error updating attendance: " . $conn->error);
    }
}

// **Fetch attendance records of teachers**
$sql = "SELECT a.id, t.name, a.hour, a.status, a.reason, a.tasks
        FROM attendance a
        JOIN teachers t ON a.teacher_id = t.id"; // Join attendance with teachers
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta charset="UTF-8">
    <title>Leerkrachtenlijst</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f9fa;
            color: #333;
        }
        .container {
            max-width: 950px;
            margin: 50px auto;
            padding: 25px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        h1 {
            text-align: center;
            color: #1d3660;
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: center;
            border: 1px solid #ddd;
        }
        th {
            background: #1d3660;
            color: white;
        }
        .btn-delete, .btn-save {
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
            border: none;
        }
        .btn-delete:hover {
            background: #c82333;
        }
        .btn-save {
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
        .btn-save:hover {
            background: #218838;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Leerkrachtenlijst</h1>

    <table>
        <tr>
            <th>ID</th>
            <th>Naam</th>
            <th>Lesuur</th>
            <th>Status</th>
            <th>Reden</th>
            <th>Taken</th>
            <th>Verwijder</th>
        </tr>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['hour']); ?></td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="text" name="reason" value="<?php echo htmlspecialchars($row['reason']); ?>">
                            <input type="hidden" name="update_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                        </form>
                    </td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="update_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                            <input type="text" name="tasks" value="<?php echo htmlspecialchars($row['tasks']); ?>">
                        </form>
                    </td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                            <button type="submit" class="btn-delete">Verwijderen</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6">Geen leerkrachten gevonden.</td>
            </tr>
        <?php endif; ?>
    </table>
    <button type="submit" class="btn-save">Opslaan</button>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>