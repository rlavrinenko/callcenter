<?php
function getDB() {
    static $conn;
    if ($conn === null) {
        $conn = new mysqli('localhost', 'callcenter', 'xxxl13gb', 'callcenter');
        if ($conn->connect_error) {
            die('Помилка підключення: ' . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
    }
    return $conn;
}
