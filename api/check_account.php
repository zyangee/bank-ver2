<?php
require_once "../dbconn.php";

// AJAX 요청 확인
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    die('직접적인 접근이 금지되었습니다.');
}

//세션 검증
if (!isset($_SESSION['userid'])) {
    http_response_code(401);
    die(json_encode(['error' => '인증이 필요합니다']));
}

header('Content-Type: application/json; charset=utf-8');

try {
    //입력값 검증
    $account_number = filter_var($_GET['account_number'] ?? '', FILTER_SANITIZE_STRING);

    if (empty($account_number) || !preg_match('/^[0-9]{10,14}$/', $account_number)) {
        throw new Exception('유효하지 않은 계좌번호입니다.');
    }
    $sql = "SELECT balance FROM accounts WHERE account_number = :account_number AND user_num=:user_num";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":account_number", $account_number);
    $stmt->bindParam(":user_num", $_SESSION['user_num']);
    $stmt->execute();

    $result = $stmt->fetch();

    if ($result) {
        //로그 기록
        error_log("Balance check - User: {$_SESSION['user_num']}, Account: {$account_number}, IP: {$_SERVER['REMOTE_ADDR']}");
        echo json_encode(['balance' => $result['balance']]);
    } else {
        throw new Exception('계좌 정보를 찾을 수 없습니다.');
    }
} catch (Exception $e) {
    error_log("계좌 잔액 조회 오류 - User: {$_SESSION['user_num']}, Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    $conn = null;
}
?>