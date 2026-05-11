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

function normalize_violation_type(string $rawViolationType): string
{
    $map = [
        'SCREEN_CAPTURE' => 'screenshot',
        'SCREEN_RECORD' => 'screen_record',
        'LEAVE_APP_ATTEMPT' => 'app_switch',
        'APP_BACKGROUND' => 'recent_apps',
        'BACK_BLOCKED' => 'back_button',
        'HOME_BUTTON' => 'home_button',
        'RECENT_APPS' => 'recent_apps',
        'SPLIT_SCREEN' => 'split_screen',
    ];

    $normalizedKey = strtoupper($rawViolationType);

    return $map[$normalizedKey] ?? 'unknown';
}

function find_student_id(mysqli $mysqli, string $nis): ?int
{
    if ($nis === '' || !ctype_digit($nis)) {
        return null;
    }

    $stmt = $mysqli->prepare('SELECT studentid FROM student WHERE nis = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }

    $nisInt = (int)$nis;
    $stmt->bind_param('i', $nisInt);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $student ? (int)$student['studentid'] : null;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_fail(405, 'Method tidak diizinkan');
}

$nis = post_value('nis');
$studentSessionIdInput = post_value('studentsessionid');
$rawViolationType = post_value('violation_type', 'violation_detail');
$detail = post_value('detail', 'description');

if ($rawViolationType === '') {
    json_fail(422, 'violation_type wajib diisi');
}

$studentId = find_student_id($mysqli, $nis);
$studentSessionId = null;

if ($studentSessionIdInput !== '' && ctype_digit($studentSessionIdInput)) {
    $candidate = (int)$studentSessionIdInput;
    if ($candidate > 0) {
        $studentSessionId = $candidate;
    }
}

if ($studentSessionId === null && $studentId !== null) {
    $studentSessionId = $studentId;
}

$violationType = normalize_violation_type($rawViolationType);
$violationDetail = $detail !== '' ? $detail : $rawViolationType;
if (strlen($violationDetail) > 255) {
    $violationDetail = substr($violationDetail, 0, 255);
}

$description = 'raw_type=' . $rawViolationType . '; detail=' . $detail;

if (column_exists($mysqli, 'violation_logs', 'studentid')) {
    if ($studentId === null || $studentId <= 0) {
        json_fail(422, 'studentid tidak ditemukan');
    }

    if (column_exists($mysqli, 'violation_logs', 'violation_type')) {
        $insertStmt = $mysqli->prepare(
            'INSERT INTO violation_logs (studentid, violation_type, violation_detail, description) VALUES (?, ?, ?, ?)'
        );

        if (!$insertStmt) {
            json_fail(500, 'Gagal menyiapkan insert violation logs');
        }

        $insertStmt->bind_param('isss', $studentId, $violationType, $violationDetail, $description);
    } else {
        $insertStmt = $mysqli->prepare(
            'INSERT INTO violation_logs (studentid, violation_detail, description) VALUES (?, ?, ?)'
        );

        if (!$insertStmt) {
            json_fail(500, 'Gagal menyiapkan insert violation logs');
        }

        $insertStmt->bind_param('iss', $studentId, $violationDetail, $description);
    }
} else {
    if ($studentSessionId === null || $studentSessionId <= 0) {
        json_fail(422, 'studentsessionid tidak ditemukan');
    }

    if (column_exists($mysqli, 'violation_logs', 'violation_type')) {
        $insertStmt = $mysqli->prepare(
            'INSERT INTO violation_logs (studentsessionid, violation_type, violation_detail, description) VALUES (?, ?, ?, ?)'
        );

        if (!$insertStmt) {
            json_fail(500, 'Gagal menyiapkan insert violation logs');
        }

        $insertStmt->bind_param('isss', $studentSessionId, $violationType, $violationDetail, $description);
    } else {
        $insertStmt = $mysqli->prepare(
            'INSERT INTO violation_logs (studentsessionid, violation_detail, description) VALUES (?, ?, ?)'
        );

        if (!$insertStmt) {
            json_fail(500, 'Gagal menyiapkan insert violation logs');
        }

        $insertStmt->bind_param('iss', $studentSessionId, $violationDetail, $description);
    }
}

$ok = $insertStmt->execute();
$insertStmt->close();

if (!$ok) {
    json_fail(500, 'Gagal menyimpan violation logs');
}

echo json_encode([
    'success' => true,
    'message' => 'Violation tercatat',
    'studentid' => $studentId,
]);
