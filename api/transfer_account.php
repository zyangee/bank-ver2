<?php
include "../dbconn.php";

// AJAX 요청인지 확인
if (
    !isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest'
) {
    http_response_code(403);
    die('직접적인 접근이 금지되었습니다.');
}

// 세션 검증
if (!isset($_SESSION['userid'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => '인증이 필요합니다.']));
}

header('Content-Type: application/json; charset=utf-8');

// JSON 입력 검증
$input = json_decode(file_get_contents("php://input"), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("JSON 파싱 오류: " . json_last_error_msg());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '잘못된 요청 형식입니다.']);
    exit();
}
try {
    //입력값 검증
    $account_number_out = filter_var($input['account_number_out'] ?? '', FILTER_SANITIZE_STRING); //출금 계좌
    $account_number_in = filter_var($input['account_number_in'] ?? '', FILTER_SANITIZE_STRING); //입금 계좌
    $transfer_amount = filter_var($input['transfer_amount'] ?? 0, FILTER_VALIDATE_FLOAT); //이체 금액
    $input_password = $input['account_password'] ?? ''; //입력된 비밀번호

    //기본 유효성 검사
    if (!$account_number_out || !$account_number_in || !$transfer_amount || !empty($input_password)) {
        throw new Exception('필수 입력값이 누락되었습니다.');
    }

    if (
        !preg_match('/^[0-9]{10,14}$/', $account_number_out) ||
        !preg_match('/^[0-9]{10,14}$/', $account_number_in)
    ) {
        throw new Exception('유효하지 않은 계좌번호 형식입니다.');
    }

    if ($account_number_out === $account_number_in) {
        throw new Exception('출금계좌와 입금계좌가 동일합니다.');
    }

    if ($transfer_amount <= 0 || $transfer_amount > 100000000) {
        throw new Exception('유효하지 않은 이체 금액입니다.');
    }

    if (!preg_match('/^[0-9]{4}$/', $input_password)) {
        throw new Exception('유효하지 않은 비밀번호 형식입니다.');
    }
    //트랜잭션 시작
    $conn->beginTransaction();

    //1) 출금
    $sql = "SELECT * FROM accounts WHERE account_number = :account_number_out AND user_num = :user_num FOR UPDATE";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":account_number_out", $account_number_out);
    $stmt->bindParam(":user_num", $_SESSION['user_num']);
    $stmt->execute();
    $result = $stmt->fetch();

    if (!$result) {
        throw new Exception('출금 계좌 정보가 올바르지 않습니다.');
    }

    //비밀번호 검증
    if (!password_verify($input_password, $result['account_password'])) {
        throw new Exception('계좌 비밀번호가 올바르지 않습니다.');
    }

    $current_balance = $result['balance'];
    $sender_account_id = $result['account_id'];

    if ($current_balance < $transfer_amount) {
        throw new Exception('잔액이 부족합니다.');
    }

    //입금 계좌 조회 및 잠금
    $sql_in = "SELECT * FROM accounts WHERE account_number = :account_number_in FOR UPDATE";
    $stmt_in = $conn->prepare($sql_in);
    $stmt_in->bindParam(":account_number_in", $account_number_in);
    $stmt_in->execute();
    $result_in = $stmt_in->fetch();

    if (!$result_in) {
        throw new Exception('입금 계좌 정보가 올바르지 않습니다.');
    }
    $receiver_account_id = $result_in['account_id'];

    //출금 계좌 잔액 갱신
    $new_balance = $current_balance - $transfer_amount;
    $update_sql = "UPDATE accounts SET balance = :new_balance WHERE account_number = :account_number_out";
    $stmt = $conn->prepare($update_sql);
    $stmt->bindParam(":new_balance", $new_balance);
    $stmt->bindParam(":account_number_out", $account_number_out);
    $stmt->execute();

    //입금 계좌 잔액 갱신
    $new_balance_in = $result_in['balance'] + $transfer_amount;
    $update_sql_in = "UPDATE accounts SET balance = :new_balance_in WHERE account_number = :account_number_in";
    $stmt = $conn->prepare($update_sql_in);
    $stmt->bindParam(":new_balance_in", $new_balance_in);
    $stmt->bindParam(":account_number_in", $account_number_in);
    $stmt->execute();

    //거래기록
    $transfer_sql = "INSERT INTO transfers (sender_account_id, receiver_account, amount, transfer_date, status_id, ip) VALUES (:sender_account_id, :receiver_account, :transfer_amount, NOW(), 2, :ip_address)";
    $stmt_transfer = $conn->prepare($transfer_sql);
    $stmt_transfer->bindParam(":sender_account_id", $sender_account_id);
    $stmt_transfer->bindParam(":receiver_account", $account_number_in);
    $stmt_transfer->bindParam(":transfer_amount", $transfer_amount);
    $stmt_transfer->bindParam(":ip_address", $_SERVER['REMOTE_ADDR']);
    $stmt_transfer->execute();

    //출금내역 기록
    $history_out = "INSERT INTO transactions(account_id, transaction_type_id, amount, amount_after, receiver_account, transaction_date, ip) VALUES (:sender_account_id, 2, :transfer_amount, :new_balance, :account_number_in, NOW(), :ip_address)";
    $stmt_history_out = $conn->prepare($history_out);
    $stmt_history_out->bindParam(":sender_account_id", $sender_account_id);
    $stmt_history_out->bindParam(":transfer_amount", $transfer_amount);
    $stmt_history_out->bindParam(":new_balance", $new_balance);
    $stmt_history_out->bindParam(":account_number_in", $account_number_in);
    $stmt_history_out->bindParam(":ip_address", $_SERVER['REMOTE_ADDR']);
    $stmt_history_out->execute();

    //입금내역 기록
    $history_in = "INSERT INTO transactions(account_id, transaction_type_id, amount, amount_after, receiver_account, transaction_date, ip) VALUES(:receiver_account_id, 1, :transfer_amount, :new_balance, :account_number_in, NOW(), :ip_address)";
    $stmt_history_in = $conn->prepare($history_in);
    $stmt_history_in->bindParam(":receiver_account_id", $receiver_account_id);
    $stmt_history_in->bindParam(":transfer_amount", $transfer_amount);
    $stmt_history_in->bindParam(":new_balance", $new_balance_in);
    $stmt_history_in->bindParam(":account_number_in", $account_number_in);
    $stmt_history_in->bindParam(":ip_address", $_SERVER['REMOTE_ADDR']);
    $stmt_history_in->execute();

    //로그 기록
    error_log("이체 성공 - From: {$account_number_out}, To: {$account_number_in}, Amount: {$transfer_amount}, User: {$_SESSION['user_num']}, IP: {$_SERVER['REMOTE_ADDR']}");

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("이체 실패 - User: {$_SESSION['user_num']}, Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $conn = null;
}
?>