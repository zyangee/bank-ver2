<?php
include "../dbconn.php";

header('Content-Type: application/json; charset=utf-8');

// AJAX 요청인지 확인
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['error' => true, 'message' => '직접적인 접근이 금지되었습니다.']);
    exit;
}

try {
    //POST 데이터 검증
    if (!isset($_POST['userid'])) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }
    // CSRF 토큰 검증
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        error_log("ID 중복체크 CSRF 공격 시도 감지: " . $_SERVER['REMOTE_ADDR']);
        throw new Exception('보안 검증에 실패했습니다.');
    }

    //입력값 검증
    $userid = trim(filter_var($_POST['userid'], FILTER_SANITIZE_STRING));

    if (empty($userid)) {
        throw new Exception('유효하지 않은 아이디입니다.');
    }
    if (!preg_match('/^[A-Za-z0-9]{4,20}$/', $userid)) {
        throw new Exception('유효하지 않은 아이디 형식입니다.');
    }

    //SQL Injection 방지를 위한 Prepared Statement 사용
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE userid=?");
    $stmt->execute([$userid]);
    $count = $stmt->fetchColumn();

    echo json_encode([
        'error' => false,
        'exists' => $count > 0,
        'message' => $count > 0 ? '이미 사용중인 아이디 입니다.' : '사용 가능한 아이디 입니다.'
    ]);

    //로그 기록
    error_log("ID 중복체크 수행 - ID: {$userid}, IP: {$_SERVER['REMOTE_ADDR']}");
} catch (Exception $e) {
    error_log("ID 중복체크 오류 - " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn = null;
    }
}
?>