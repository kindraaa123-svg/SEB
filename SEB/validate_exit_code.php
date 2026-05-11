<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

function json_fail(int $status, string $message): void
{
    http_response_code($status);
    echo json_encode([
        'success' => false,
        'message' => $message,
    ]);
    exit;
}

function post_value(string ...$keys): string
{
    foreach ($keys as $key) {
        $value = trim((string)($_POST[$key] ?? ''));
        if ($value !== '') {
            return $value;
        }
    }

    return '';
}

function column_exists(mysqli $mysqli, string $table, string $column): bool
{
    $stmt = $mysqli->prepare(
        'SELECT COUNT(*)
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
           AND COLUMN_NAME = ?'
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    return (int)$count > 0;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_fail(405, 'Method tidak diizinkan');
}

$exitCode = strtoupper(post_value('exit_code', 'code'));
$nis = post_value('nis');

if ($exitCode === '' || $nis === '') {
    json_fail(422, 'Exit code dan NIS wajib diisi');
}

if (!ctype_digit($nis)) {
    json_fail(422, 'Format NIS tidak valid');
}

$studentStmt = $mysqli->prepare('SELECT studentid FROM student WHERE nis = ? LIMIT 1');
if (!$studentStmt) {
    json_fail(500, 'Gagal menyiapkan query student');
}

$nisInt = (int)$nis;
$studentStmt->bind_param('i', $nisInt);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();
$student = $studentResult ? $studentResult->fetch_assoc() : null;
$studentStmt->close();

if (!$student) {
    json_fail(401, 'NIS tidak ditemukan');
}

$studentId = (int)$student['studentid'];

$hasExpiredAt = column_exists($mysqli, 'exam_codes', 'expired_at');
$hasCodeExpiredAt = column_exists($mysqli, 'exam_codes', 'code_expired_at');
$selectExpirySql = 'NULL AS expiry_at';
if ($hasExpiredAt) {
    $selectExpirySql = 'expired_at AS expiry_at';
} elseif ($hasCodeExpiredAt) {
    $selectExpirySql = 'code_expired_at AS expiry_at';
}

$codeStmt = $mysqli->prepare(
    'SELECT codeid, ' . $selectExpirySql . '
     FROM exam_codes
     WHERE code = ?
       AND codetype = "exit"
     LIMIT 1'
);

if (!$codeStmt) {
    json_fail(500, 'Gagal menyiapkan query exit code');
}

$codeStmt->bind_param('s', $exitCode);
$codeStmt->execute();
$result = $codeStmt->get_result();
$code = $result ? $result->fetch_assoc() : null;
$codeStmt->close();

if (!$code) {
    json_fail(401, 'Exit code tidak valid');
}

$expiredAt = $code['expiry_at'] ?? null;
if (!empty($expiredAt)) {
    $now = new DateTimeImmutable('now');
    $expiry = new DateTimeImmutable((string)$expiredAt);
    if ($expiry < $now) {
        json_fail(401, 'Exit code sudah expired');
    }
}

$codeId = (int)$code['codeid'];

$insertUsageStmt = $mysqli->prepare(
    'INSERT INTO used_code (codeid, studentid) VALUES (?, ?)'
);

if (!$insertUsageStmt) {
    json_fail(500, 'Gagal menyiapkan simpan pemakaian kode: ' . $mysqli->error);
}

$insertUsageStmt->bind_param('ii', $codeId, $studentId);
$insertOk = $insertUsageStmt->execute();
$insertErrNo = $insertUsageStmt->errno;
$insertUsageStmt->close();

if (!$insertOk) {
    if ($insertErrNo === 1062) {
        json_fail(409, 'Kode ini sudah pernah dipakai oleh siswa ini');
    }
    json_fail(500, 'Gagal mencatat pemakaian kode');
}

echo json_encode([
    'success' => true,
    'message' => 'Exit code valid',
    'studentid' => $studentId,
    'codeid' => $codeId,
]);
