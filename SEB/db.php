<?php

declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_OFF);

header('Content-Type: application/json; charset=utf-8');

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbName = 'seb';
$port = 3306;

$mysqli = new mysqli($host, $user, $pass, $dbName, $port);
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Koneksi database gagal',
    ]);
    exit;
}

$mysqli->set_charset('utf8mb4');
