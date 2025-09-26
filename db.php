<?php
// =========================
// Параметри бази даних
// =========================
define('DB_HOST', 'localhost');
define('DB_USER', 'callcenter');   // замініть на свого користувача
define('DB_PASS', 'xxxl13gb');   // замініть на свій пароль
define('DB_NAME', 'callcenter');     // замініть на назву бази
define('DB_PORT', 3306);

// Назва сайту
define('SITE_TITLE', 'Автообдзвон');

/**
 * Повертає підключення до бази даних MySQL
 *
 * @return mysqli
 */
function getDB() {
    static $conn;

    if (!isset($conn)) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        if ($conn->connect_error) {
            die("Помилка підключення до бази даних: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
    }

    return $conn;
}
