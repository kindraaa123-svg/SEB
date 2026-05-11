<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

function json_fail(int $status, string $message, ?string $reason = null): void
{
    http_response_code($status);
    $payload = [
        'success' => false,
        'message' => $message,
    ];

    if ($reason !== null) {
        $payload['reason'] = $reason;
    }

    echo json_encode($payload);
    exit;
}

function request_payload(): array
{
    $payload = $_POST;

    $raw = file_get_contents('php://input');
    if (is_string($raw) && trim($raw) !== '') {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $payload = array_merge($payload, $json);
        }
    }

    return $payload;
}

function pick_value(array $payload, string ...$keys): string
{
    foreach ($keys as $key) {
        if (!array_key_exists($key, $payload)) {
            continue;
        }

        $value = trim((string)$payload[$key]);
        if ($value !== '') {
            return $value;
        }
    }

    return '';
}

function normalize_code(string $code): string
{
    // Keep only letters and numbers to avoid mismatch from spaces/dashes.
    $clean = preg_replace('/[^A-Za-z0-9]/', '', $code);
    return strtoupper((string)$clean);
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
    json_fail(405, 'Method tidak diizinkan', 'invalid_method');
}

$payload = request_payload();
$examCode = normalize_code(pick_value($payload, 'exam_code', 'code', 'entry_code', 'entryCode', 'examCode'));
$nis = pick_value($payload, 'nis', 'NIS');

if ($examCode === '' || $nis === '') {
    json_fail(422, 'Exam code dan NIS wajib diisi', 'missing_fields');
}

if (!ctype_digit($nis)) {
    json_fail(422, 'Format NIS tidak valid', 'invalid_nis_format');
}

$studentStmt = $mysqli->prepare('SELECT studentid FROM student WHERE nis = ? LIMIT 1');
if (!$studentStmt) {
    json_fail(500, 'Gagal menyiapkan query student', 'student_query_prepare_failed');
}

$nisInt = (int)$nis;
$studentStmt->bind_param('i', $nisInt);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();
$student = $studentResult ? $studentResult->fetch_assoc() : null;
$studentStmt->close();

if (!$student) {
    json_fail(401, 'NIS tidak ditemukan', 'student_not_found');
}

$studentId = (int)$student['studentid'];

$hasCodeType = column_exists($mysqli, 'exam_codes', 'codetype');
$hasExpiredAt = column_exists($mysqli, 'exam_codes', 'expired_at');
$hasCodeExpiredAt = column_exists($mysqli, 'exam_codes', 'code_expired_at');

$selectExpirySql = 'NULL AS expiry_at';
if ($hasExpiredAt) {
    $selectExpirySql = 'expired_at AS expiry_at';
} elseif ($hasCodeExpiredAt) {
    $selectExpirySql = 'code_expired_at AS expiry_at';
}

$query = 'SELECT codeid, code, ' . $selectExpirySql . ', ';
$query .= $hasCodeType ? 'codetype ' : 'NULL AS codetype ';
$query .= 'FROM exam_codes WHERE UPPER(code) = ? LIMIT 1';

$codeStmt = $mysqli->prepare($query);
if (!$codeStmt) {
    json_fail(500, 'Gagal menyiapkan query exam code: ' . $mysqli->error, 'code_query_prepare_failed');
}

$codeStmt->bind_param('s', $examCode);
$codeStmt->execute();
$codeResult = $codeStmt->get_result();
$code = $codeResult ? $codeResult->fetch_assoc() : null;
$codeStmt->close();

if (!$code) {
    json_fail(401, 'Exam code entry tidak valid', 'code_not_found');
}

$codeType = strtolower(trim((string)($code['codetype'] ?? '')));
if ($hasCodeType && $codeType !== '' && $codeType !== 'entry') {
    json_fail(401, 'Kode ini bukan entry code', 'wrong_code_type');
}

$expiredAt = $code['expiry_at'] ?? null;
if (!empty($expiredAt)) {
    $now = new DateTimeImmutable('now');
    $expiry = new DateTimeImmutable((string)$expiredAt);
    if ($expiry < $now) {
        json_fail(401, 'Exam code sudah expired', 'code_expired');
    }
}

$codeId = (int)$code['codeid'];

$insertUsageStmt = $mysqli->prepare(
    'INSERT INTO used_code (codeid, studentid) VALUES (?, ?)'
);

if (!$insertUsageStmt) {
    json_fail(500, 'Gagal menyiapkan simpan pemakaian kode: ' . $mysqli->error, 'usage_insert_prepare_failed');
}

$insertUsageStmt->bind_param('ii', $codeId, $studentId);
$insertOk = $insertUsageStmt->execute();
$insertErrNo = $insertUsageStmt->errno;
$insertUsageStmt->close();

if (!$insertOk) {
    if ($insertErrNo === 1062) {
        json_fail(409, 'Kode ini sudah pernah dipakai oleh siswa ini', 'code_already_used_by_student');
    }
    json_fail(500, 'Gagal mencatat pemakaian kode', 'usage_insert_failed');
}

echo json_encode([
    'success' => true,
    'message' => 'Validasi berhasil',
    'studentid' => $studentId,
    'codeid' => $codeId,
]);
