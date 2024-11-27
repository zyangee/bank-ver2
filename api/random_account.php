<?php
include "../dbconn.php";

$inactive = 1800;
if (isset($_SESSION['timeout'])) {
    if (time() - $_SESSION['timeout'] > $inactive) {
        session_unset();
        session_destroy();
        header("Location: ../login/login.php");
        exit;
    }
}
$_SESSION['timeout'] = time();
session_regenerate_id(true);

if (!isset($_SESSION['user_num'])) {
    header("Location: ../login/login.php");
    exit();
}
function checkRateLimit()
{
    if (!isset($_SESSION['account_attempts'])) {
        $_SESSION['account_attempts'] = 1;
        $_SESSION['last_attempt'] = time();
        return true;
    }
    if (time() - $_SESSION['last_attempt'] > 3600) {
        $_SESSION['account_attempts'] = 1;
        $_SESSION['last_attempt'] = time();
        return true;
    }
    if ($_SESSION['account_attempts'] >= 3) {
        return false;
    }
    $_SESSION['account_attempts']++;
    return true;
}
function randomAccountNumber($conn)
{
    $max = 1000;
    $tryagain = 0;
    try {
        do {
            $random_part1 = str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);
            $random_part2 = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $account_number = "{$random_part1}-{$random_part2}";

            //DB에서 중복 확인
            $query = "SELECT count(*) FROM accounts WHERE account_number = :account_number";
            $stmt_random = $conn->prepare($query);
            $stmt_random->bindParam(":account_number", $account_number, PDO::PARAM_STR);
            $stmt_random->execute();
            $count = $stmt_random->fetchColumn(); //행의 개수 가져오기(account_number가 같은게 있는지 확인 작업)

            if ($tryagain++ >= $max) {
                throw new Exception("계좌 생성번호 실패");
            }
        } while ($count > 0);

        return $account_number;
    } catch (Exception $e) {
        error_log("계좌생성 오류: " . $e->getMessage());
        throw new Exception("계좌 생성 처리 중 오류가 발생했습니다.");
    }
}

$user_num = $_SESSION['user_num'];

$sql = "SELECT * FROM users WHERE user_num = :user_num";
$stmt = $conn->prepare($sql);
$stmt->bindParam(":user_num", $user_num);
$stmt->execute();

$row = $stmt->fetch(PDO::FETCH_ASSOC);
$resident_num = $row['date_of_birth'];

//주민번호가 6자리 일 때와 아닐 때의 if-else문
if (strpos($resident_num, '-') !== false) {
    $resident_number1 = substr($resident_num, 0, 6);
    $resident_number2 = substr($resident_num, strpos($resident_num, '-') + 1);
} else {
    $resident_number1 = $resident_num;
    $resident_number2 = '';
}

if ($row > 0) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            if (!checkRateLimit()) {
                throw new Exception("계좌 생성 요청이 너무 많습니다. 잠시 후 다시 시도해주세요.");
            }
            error_log("POST Data: " . print_r($_POST, true));
            error_log("Session CSRF Token: " . $_SESSION['csrf_token']);
            error_log("Posted CSRF Token: " . $_POST['csrf_token']);

            if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
                throw new Exception("CSRF 토큰이 없습니다.");
            }

            if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                error_log("CSRF Token Mismatch - Session: " . $_SESSION['csrf_token'] . ", Post: " . $_POST['csrf_token']);
                throw new Exception("보안 검증에 실패했습니다. (CSRF 토큰 불일치)");
            }

            //입력값 검증
            $resident_number1 = filter_input(INPUT_POST, 'resident-number1', FILTER_SANITIZE_STRING);
            $resident_number2 = filter_input(INPUT_POST, 'resident-number2', FILTER_SANITIZE_STRING);
            $full_resident_number = $resident_number1 . '-' . $resident_number2;
            $balance = filter_input(INPUT_POST, 'balance', FILTER_VALIDATE_INT);
            $account_password = filter_input(INPUT_POST, 'account-password', FILTER_SANITIZE_STRING);

            if (!preg_match('/^\d{6}-\d{7}$/', $full_resident_number)) {
                throw new Exception("올바른 주민번호 형식이 아닙니다.");
            }
            if ($balance < 0) {
                throw new Exception("잔액은 0원 이상이어야 합니다.");
            }
            if (!preg_match('/^\d{4}$/', $account_password)) {
                throw new Exception("비밀번호는 4자리 숫자여야 합니다.");
            }
            $conn->beginTransaction();

            //account_number 랜덤지정
            $account_number = randomAccountNumber($conn);
            $account_password_hash = password_hash($account_password, PASSWORD_DEFAULT);

            //값 insert
            $sql_insert = 'INSERT INTO accounts(user_num, account_number, balance, created_at, account_password)
        VALUES(:user_num, :account_number, :balance, NOW(), :account_password_hash)';

            //bind parameter 사용
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bindParam(':user_num', $user_num);
            $stmt_insert->bindParam(':account_number', $account_number);
            $stmt_insert->bindParam(":balance", $balance);
            $stmt_insert->bindParam(":account_password_hash", $account_password_hash);

            //입력된 값이 있을 경우
            if ($stmt_insert->execute() === TRUE) {
                $sql_update = 'UPDATE users SET date_of_birth = :resident_number WHERE user_num = :user_num';
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bindParam(':resident_number', $full_resident_number);
                $stmt_update->bindParam(':user_num', $user_num);
                if ($stmt_update->execute() === TRUE) {
                    $conn->commit();
                    echo "<script>alert('계좌가 생성되었습니다.');</script>";
                    echo "<script>location.href = '../users.php';</script>";
                    exit;
                }
            }
            throw new Exception("계좌 생성에 실패하였습니다.");
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("계좌생성 에러 - Message: " . $e->getMessage() . ", POST data: " . print_r($_POST, true) . ", Session data: " . print_r($_SESSION, true));
            echo "<script>alert('" . htmlspecialchars($e->getMessage()) . "');</script>";
        }
    }
}
?>