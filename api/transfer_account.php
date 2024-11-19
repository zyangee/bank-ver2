<?php
include "../dbconn.php";

$data = json_decode(file_get_contents("php://input"), true);
$account_number_out = $data['account_number_out'] ?? null; //출금 계좌
$account_number_in = $data['account_number_in'] ?? null; //입금 계좌
$transfer_amount = $data['transfer_amount'] ?? null; //이체 금액
$input_password = $data['account_password'] ?? null; //입력된 비밀번호

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if ($account_number_out === null || $account_number_in === null) {
        echo json_encode(['success' => false, 'message' => '필수 데이터가 누락되었습니다.']);
        exit;
    }

    try {
        //트랜잭션 시작
        $conn->beginTransaction();

        //1) 출금
        $sql = "SELECT * FROM accounts WHERE account_number = :account_number_out";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":account_number_out", $account_number_out);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $current_balance = $result['balance'];
            $sender_account_id = $result['account_id'];
            $account_password = $result['account_password'];

            //비밀번호 확인 작업
            if ($input_password === $account_password) {
                if ($current_balance >= $transfer_amount) {
                    //출금 계좌 잔액에서 이체 금액 차감
                    $new_balance = $current_balance - $transfer_amount;

                    //출금 계좌 업데이트
                    $update_sql = "UPDATE accounts SET balance = :new_balance WHERE account_number = :account_number_out";
                    $stmt = $conn->prepare($update_sql);
                    $stmt->bindParam(":new_balance", $new_balance);
                    $stmt->bindParam(":account_number_out", $account_number_out);
                    $stmt->execute();

                    //2) 입금
                    $sql_in = "SELECT * FROM accounts WHERE account_number = :account_number_in";
                    $stmt_in = $conn->prepare($sql_in);
                    $stmt_in->bindParam(":account_number_in", $account_number_in);
                    $stmt_in->execute();
                    $result_in = $stmt_in->fetch(PDO::FETCH_ASSOC);
                    if ($result_in) {
                        //입금 계좌 잔액에서 이체 금액 더함
                        $new_balance_in = $result_in['balance'] + $transfer_amount;
                        $receiver_account_id = $result_in['account_id'];

                        //입금 계좌 업데이트
                        $update_sql_in = "UPDATE accounts SET balance = :new_balance_in WHERE account_number = :account_number_in";
                        $stmt = $conn->prepare($update_sql_in);
                        $stmt->bindParam(":new_balance_in", $new_balance_in);
                        $stmt->bindParam(":account_number_in", $account_number_in);
                        $stmt->execute();

                        //(출금)송금 테이블에 데이터 삽입
                        $transfer_sql = "INSERT INTO transfers(sender_account_id, receiver_account, amount, transfer_date, status_id)
                    VALUES(:sender_account_id, :receiver_account, :transfer_amount, NOW(), 2)";
                        $stmt_transfer = $conn->prepare($transfer_sql);
                        $stmt_transfer->bindParam(":sender_account_id", $sender_account_id);
                        $stmt_transfer->bindParam(":receiver_account", $account_number_in);
                        $stmt_transfer->bindParam(":transfer_amount", $transfer_amount);
                        $stmt_transfer->execute();

                        //(출금)거래내역 테이블에 데이터 삽입
                        $history_out = "INSERT INTO transactions(account_id, transaction_type_id, amount, amount_after, receiver_account, transaction_date)
                    VALUES(:sender_account_id, 2, :transfer_amount, :new_balance, :account_number_in, NOW())";
                        $stmt_history_out = $conn->prepare($history_out);
                        $stmt_history_out->bindParam(":sender_account_id", $sender_account_id);
                        $stmt_history_out->bindParam(":transfer_amount", $transfer_amount);
                        $stmt_history_out->bindParam(":new_balance", $new_balance);
                        $stmt_history_out->bindParam(":account_number_in", $account_number_in);
                        $stmt_history_out->execute();

                        //(입금)거래내역 테이블에 데이터 삽입
                        $history_in = "INSERT INTO transactions(account_id, transaction_type_id, amount, amount_after, receiver_account, transaction_date)
                    VALUES(:receiver_account_id, 1, :transfer_amount, :new_balance, :account_number_in, NOW())";
                        $stmt_history_in = $conn->prepare($history_in);
                        $stmt_history_in->bindParam(":receiver_account_id", $receiver_account_id);
                        $stmt_history_in->bindParam(":transfer_amount", $transfer_amount);
                        $stmt_history_in->bindParam(":new_balance", $new_balance_in);
                        $stmt_history_in->bindParam(":account_number_in", $account_number_in);
                        $stmt_history_in->execute();
                    } else {
                        throw new Exception("입금 계좌를 찾을 수 없습니다.");
                    }
                } else {
                    throw new Exception("잔액이 부족합니다.");
                }
            } else {
                throw new Exception("계좌 비밀번호를 확인해주세요.");
            }
        } else {
            error_log("출금 계좌를 찾을 수 없습니다. : " . $account_number_out);
            throw new Exception("출금 계좌를 찾을 수 없습니다.");
        }
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollBack();
        error_log('오류 발생: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

$conn = null;
exit();
?>