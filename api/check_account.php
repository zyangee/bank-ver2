<?php
require_once "../dbconn.php";

// AJAX 요청 확인
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    die('직접적인 접근이 금지되었습니다.');
}

//세션 검증
if (!isset($_SESSION['userid']) || !isset($_SESSION['user_num'])) {
    http_response_code(401);
    die(json_encode(['error' => '인증이 필요합니다', 'redirect' => '../login/login.php']));
}

// 세션 타임아웃 체크
$inactive = 1800; // 30분
if (isset($_SESSION['timeout']) && (time() - $_SESSION['timeout'] > $inactive)) {
    session_unset();
    session_destroy();
    http_response_code(401);
    die(json_encode([
        'error' => '세션이 만료되었습니다',
        'redirect' => '../login/login.php'
    ]));
}
$_SESSION['timeout'] = time();

//POST 요청 확인
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => '잘못된 요청 메소드입니다.']));
}

//JSON 요청 처리
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    die(json_encode(['error' => '잘못된 요청 형식입니다.']));
}

//CSRF 토큰 검증
$requestToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $requestToken)) {
    http_response_code(403);
    die(json_encode(['error' => '유효하지 않은 보안 토큰입니다.']));
}

header('Content-Type: application/json; charset=utf-8');

try {
    //입력값 검증
    $account_number = filter_var($data['account_number'] ?? '', FILTER_SANITIZE_STRING);

    if (empty($account_number) || !preg_match('/^[0-9]{10,14}$/', $account_number)) {
        throw new Exception('유효하지 않은 계좌번호입니다.');
    }
    $sql = "SELECT balance FROM accounts WHERE account_number = :account_number AND user_num=:user_num";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":account_number", $account_number);
    $stmt->bindParam(":user_num", $_SESSION['user_num']);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        //로그 기록
        error_log("Balance check - User: {$_SESSION['user_num']}, Account: {$account_number}, IP: {$_SERVER['REMOTE_ADDR']}");
        echo json_encode(['success' => true, 'balance' => $result['balance']]);
    } else {
        throw new Exception('계좌 정보를 찾을 수 없습니다.');
    }
} catch (Exception $e) {
    error_log("계좌 잔액 조회 오류 - User: {$_SESSION['user_num']}, Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    $conn = null;
}
?>