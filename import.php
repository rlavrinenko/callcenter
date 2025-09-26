<?php
require_once __DIR__ . '/db.php';
$conn = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['xls_file'])) {
    require_once __DIR__ . '/Excel/SpreadsheetReader.php'; // шлях до бібліотеки Spreadsheet_Excel_Reader
    $data = new Spreadsheet_Excel_Reader();
    $data->setOutputEncoding('UTF-8');
    $data->read($_FILES['xls_file']['tmp_name']);

    $imported = 0;
    for ($i = 2; $i <= $data->sheets[0]['numRows']; $i++) { // 1-й рядок – заголовки
        $full_name = trim($data->sheets[0]['cells'][$i][1]);
        $birth_date = trim($data->sheets[0]['cells'][$i][2]);
        $phones = [];
        for ($c = 3; $c <= 5; $c++) { // Тел1, Тел2, Тел3
            if (!empty($data->sheets[0]['cells'][$i][$c])) {
                $phones[] = trim($data->sheets[0]['cells'][$i][$c]);
            }
        }
        if (!$full_name) continue;

        // Перевірка на існування ПІБ + дата народження
        $stmt = $conn->prepare("SELECT id FROM persons WHERE full_name=? AND birth_date=?");
        $stmt->bind_param("ss", $full_name, $birth_date);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows) {
            $person_id = $res->fetch_assoc()['id'];
        } else {
            $stmt = $conn->prepare("INSERT INTO persons (full_name, birth_date) VALUES (?, ?)");
            $stmt->bind_param("ss", $full_name, $birth_date);
            $stmt->execute();
            $person_id = $stmt->insert_id;
        }

        // Додаємо телефони
        foreach ($phones as $phone) {
            if (!preg_match('/^(\+380\d{9}|0\d{9})$/', $phone)) continue;
            $stmt = $conn->prepare("SELECT id FROM person_phones WHERE person_id=? AND phone=?");
            $stmt->bind_param("is", $person_id, $phone);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows) continue;

            $stmt = $conn->prepare("INSERT INTO person_phones (person_id, phone) VALUES (?, ?)");
            $stmt->bind_param("is", $person_id, $phone);
            $stmt->execute();
        }
        $imported++;
    }
    $message = "Імпорт завершено. Додано або оновлено {$imported} записів.";
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<title>Імпорт Excel</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">Адмінка</a>
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link" href="index.php">Головна</a></li>
      <li class="nav-item"><a class="nav-link" href="analytics.php">Аналітика</a></li>
      <li class="nav-item"><a class="nav-link" href="numbers.php">Номери</a></li>
      <li class="nav-item"><a class="nav-link" href="persons.php">Персональні дані</a></li>
      <li class="nav-item"><a class="nav-link active" href="import_excel.php">Імпорт Excel</a></li>
    </ul>
  </div>
</nav>

<div class="container my-5">
<h1>Імпорт даних з Excel (.xls)</h1>
<?php if($message): ?>
<div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<form method="post" enctype="multipart/form-data">
    <div class="mb-3">
        <label class="form-label">Виберіть Excel-файл (.xls)</label>
        <input type="file" class="form-control" name="xls_file" accept=".xls" required>
    </div>
    <button type="submit" class="btn btn-success">Імпортувати</button>
</form>
<hr>
<p>Файл повинен містити стовпці: <strong>ПІБ, Дата народження, Тел1, Тел2, Тел3</strong></p>
</div>
</body>
</html>
