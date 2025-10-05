<?php
require_once __DIR__ . '/db.php';
$conn = getDB();

// Обробка кнопки "Нагадати"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'] ?? '';
    $owner_id = $_POST['owner_id'] ?? '';

    if ($phone && $owner_id) {
        $stmt = $conn->prepare("INSERT INTO call_remind_1 (phone, owner_id, status, pressed_key, start_ts, end_ts) 
                                VALUES (?, ?, 'new', 'none', NULL, NULL)");
        $stmt->bind_param("si", $phone, $owner_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: remind.php");
    exit;
}

// Отримати всі колонки з numbers + ім'я власника
$sql = "SELECT n.*, p.full_name AS owner_name
        FROM numbers n
        LEFT JOIN persons p ON n.owner_id = p.id
        ORDER BY n.id DESC";
$result = $conn->query($sql);
$fields = $result->fetch_fields();
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Нагадування</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        h1 { margin-bottom: 20px; }
        .btn { min-width: 90px; }
        table th, table td { vertical-align: middle; }
    </style>
</head>
<body>

    <!-- Навбар -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light mb-3">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">CallCenter</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="index.php">Головна</a></li>
                    <li class="nav-item"><a class="nav-link" href="analytics.php">Аналітика</a></li>
                    <li class="nav-item"><a class="nav-link" href="numbers.php">Номери</a></li>
                    <li class="nav-item"><a class="nav-link" href="persons.php">Персональні дані</a></li>
                    <li class="nav-item"><a class="nav-link active" href="remind.php">Нагадування</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <h1>Список номерів для нагадування</h1>

        <table class="table table-bordered table-striped table-hover">
            <thead class="table-light">
                <tr>
                    <?php foreach ($fields as $field): ?>
                        <th><?= htmlspecialchars($field->name) ?></th>
                    <?php endforeach; ?>
                    <th>Власник</th>
                    <th>Дія</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <?php foreach ($fields as $field): ?>
                        <td><?= htmlspecialchars($row[$field->name]) ?></td>
                    <?php endforeach; ?>
                    <td><?= htmlspecialchars($row['owner_name']) ?></td>
                    <td>
                        <form method="post" action="remind.php" style="margin:0">
                            <input type="hidden" name="phone" value="<?= htmlspecialchars($row['phone']) ?>">
                            <input type="hidden" name="owner_id" value="<?= htmlspecialchars($row['owner_id']) ?>">
                            <button type="submit" class="btn btn-primary btn-sm">Нагадати</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</body>
</html>
