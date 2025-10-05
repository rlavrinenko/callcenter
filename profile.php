<?php
require_once __DIR__ . '/db.php';
$conn = getDB();

// Отримуємо ID користувача
$id = intval($_GET['id'] ?? 0);

// Отримуємо дані користувача
$stmt = $conn->prepare("SELECT * FROM persons WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    die("Користувач не знайдений.");
}

// Додавання телефону
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_phone'])) {
    $phone = trim($_POST['phone']);
    if (!empty($phone)) {
        $stmt = $conn->prepare("INSERT INTO person_phones (person_id, phone) VALUES (?, ?)");
        $stmt->bind_param("is", $id, $phone);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: profile.php?id=$id");
    exit;
}

// Редагування телефону
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_phone'])) {
    $phone_id = intval($_POST['phone_id']);
    $phone = trim($_POST['phone']);
    if (!empty($phone)) {
        $stmt = $conn->prepare("UPDATE person_phones SET phone = ? WHERE id = ? AND person_id = ?");
        $stmt->bind_param("sii", $phone, $phone_id, $id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: profile.php?id=$id");
    exit;
}

// Видалення телефону
if (isset($_GET['delete_phone'])) {
    $phone_id = intval($_GET['delete_phone']);
    $stmt = $conn->prepare("DELETE FROM person_phones WHERE id = ? AND person_id = ?");
    $stmt->bind_param("ii", $phone_id, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: profile.php?id=$id");
    exit;
}

// Телефони користувача
$phones = $conn->query("SELECT * FROM person_phones WHERE person_id = $id");

// Масив телефонів для історії та результатів дзвінків
$phoneNumbers = [];
while ($row = $phones->fetch_assoc()) {
    $phoneNumbers[] = "'" . $conn->real_escape_string($row['phone']) . "'";
}
$phones->data_seek(0); // повертаємось для відображення

// Історія дзвінків
$callHistory = [];
if (!empty($phoneNumbers)) {
    $sql = "SELECT * FROM call_log WHERE phone IN (" . implode(",", $phoneNumbers) . ") ORDER BY start_ts DESC LIMIT 50";
    $callHistory = $conn->query($sql);
}

// Результати дзвінків
$completedCalls = [];
if (!empty($phoneNumbers)) {
    $sql = "SELECT * FROM completed_calls WHERE phone IN (" . implode(",", $phoneNumbers) . ") ORDER BY completed_at DESC LIMIT 50";
    $completedCalls = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Профіль користувача</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f6fa; }
        .profile-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 20px;
        }
        h2, h4 { color: #2c3e50; }
        table { border-radius: 10px; overflow: hidden; }
        .table th { background: #34495e; color: #fff; }
        .btn { border-radius: 10px; }
        .form-control { border-radius: 10px; border: 1px solid #ccc; }
        .back-btn { background: #95a5a6; color: #fff; }
        .back-btn:hover { background: #7f8c8d; color: #fff; }
    </style>
</head>
<body class="p-4">
<div class="container">
    <a href="visitors.php" class="btn back-btn mb-3">&larr; Назад</a>

    <!-- Профіль користувача -->
    <div class="profile-card">
        <h2 class="mb-3"><?= htmlspecialchars($user['full_name']) ?></h2>
        <p><b>Дата народження:</b> <?= htmlspecialchars($user['birth_date']) ?></p>
        <p><b>Створений:</b> <?= htmlspecialchars($user['created_at']) ?></p>
    </div>

    <!-- Телефони -->
    <div class="profile-card">
        <h4>Телефони</h4>
        <table class="table table-bordered table-striped">
            <tr><th>Телефон</th><th>Дії</th></tr>
            <?php while ($p = $phones->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($p['phone']) ?></td>
                    <td>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="phone_id" value="<?= $p['id'] ?>">
                            <input type="text" name="phone" value="<?= htmlspecialchars($p['phone']) ?>" class="form-control d-inline w-50" required>
                            <button type="submit" name="edit_phone" class="btn btn-sm btn-warning">✏ Змінити</button>
                        </form>
                        <a href="?id=<?= $id ?>&delete_phone=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Видалити телефон?')">🗑 Видалити</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>

        <h5 class="mt-3">➕ Додати телефон</h5>
        <form method="post" class="mt-2">
            <div class="input-group w-50">
                <input type="text" name="phone" class="form-control" placeholder="Введіть телефон" required>
                <button type="submit" name="add_phone" class="btn btn-success">Додати</button>
            </div>
        </form>
    </div>

    <!-- Історія дзвінків -->
    <div class="profile-card">
        <h4>📞 Історія дзвінків</h4>
        <?php if (!empty($callHistory) && $callHistory->num_rows > 0): ?>
            <table class="table table-hover">
                <tr>
                    <th>Телефон</th><th>Дія</th><th>Кнопка</th>
                    <th>Початок</th><th>Кінець</th><th>Тривалість</th>
                </tr>
                <?php while ($c = $callHistory->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['phone']) ?></td>
                        <td><?= htmlspecialchars($c['action']) ?></td>
                        <td><?= htmlspecialchars($c['pressed_key']) ?></td>
                        <td><?= htmlspecialchars($c['start_ts']) ?></td>
                        <td><?= htmlspecialchars($c['end_ts']) ?></td>
                        <td><?= htmlspecialchars($c['duration']) ?> сек</td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p class="text-muted">Історія дзвінків відсутня.</p>
        <?php endif; ?>
    </div>

    <!-- Результати дзвінків -->
    <div class="profile-card">
        <h4>✅ Результати дзвінків</h4>
        <?php if (!empty($completedCalls) && $completedCalls->num_rows > 0): ?>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Телефон</th>
                        <th>Внутрішній номер</th>
                        <th>Повідомлення</th>
                        <th>Дата завершення</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($c = $completedCalls->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['phone']) ?></td>
                            <td><?= htmlspecialchars($c['extension']) ?></td>
                            <td><?= htmlspecialchars($c['msg']) ?></td>
                            <td><?= htmlspecialchars($c['completed_at']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted">Немає інформації про завершені дзвінки.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
