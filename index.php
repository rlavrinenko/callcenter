<?php
require_once __DIR__ . '/db.php';
$conn = getDB();
?>
<!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<title>Головна</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Адмінка</a>
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link active" href="index.php">Головна</a></li>
      <li class="nav-item"><a class="nav-link" href="analytics.php">Аналітика</a></li>
      <li class="nav-item"><a class="nav-link" href="numbers.php">Номери</a></li>
      <li class="nav-item"><a class="nav-link" href="visitors.php">Персональні дані</a></li>
    </ul>
  </div>
</nav>

<div class="container my-5">
  <h1>Ласкаво просимо в адмінку</h1>
  <p>Використовуйте меню для переходу між сторінками.</p>
  <div class="list-group mt-4">
    <a href="persons.php" class="list-group-item list-group-item-action">Персональні дані</a>
    <a href="numbers.php" class="list-group-item list-group-item-action">Номери</a>
    <a href="analytics.php" class="list-group-item list-group-item-action">Аналітика</a>
  </div>
</div>
</body>
</html>
