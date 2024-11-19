<?php
function randomAccountNumber($conn)
{
    $max = 1000;
    $tryagain = 0;
    do {
        $random_part1 = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        $random_part2 = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $account_number = "{$random_part1}-{$random_part2}";

        //DB에서 중복 확인
        $query = "SELECT count(*) FROM accounts WHERE account_number = :account_number";
        $stmt_random = $conn->prepare($query);
        $stmt_random->bindParam(":account_number", $account_number);
        $stmt_random->execute();
        $count = $stmt_random->fetchColumn(); //행의 개수 가져오기(account_number가 같은게 있는지 확인 작업)
        $tryagain++;
        if ($tryagain >= $max) {
            throw new Exception("계좌 생성번호 실패");
        }
    } while ($count > 0);
    return $account_number;
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
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $resident_num) {
        //post요청 들어오는 값 저장
        $resident_number1 = $_POST['resident-number1'];
        $resident_number2 = $_POST['resident-number2'];
        $full_resident_number = $resident_number1 . '-' . $resident_number2;
        $balance = $_POST['balance'];
        $account_password = $_POST['account-password'];

        //account_number 랜덤지정
        $account_number = randomAccountNumber($conn);

        //값 insert
        $sql_insert = 'INSERT INTO accounts(user_num, account_number, balance, created_at, account_password)
        VALUES(:user_num, :account_number, :balance, NOW(), :account_password)';

        //bind parameter 사용
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bindParam(':user_num', $user_num);
        $stmt_insert->bindParam(':account_number', $account_number);
        $stmt_insert->bindParam(":balance", $balance);
        $stmt_insert->bindParam(":account_password", $account_password);

        //입력된 값이 있을 경우
        if ($stmt_insert->execute() === TRUE) {
            $sql_update = 'UPDATE users SET date_of_birth = :resident_number WHERE user_num = :user_num';
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bindParam(':resident_number', $full_resident_number);
            $stmt_update->bindParam(':user_num', $user_num);
            if ($stmt_update->execute() === TRUE) {
                echo "<script>alert('계좌가 생성되었습니다.');</script>";
                echo "<script>location.href = '../users.php';</script>";
                exit;
            } else {
                echo "주민번호 업데이트 실패: " . implode(", ", $stmt_update->errorInfo()) . "";
            }
        } else {
            echo "계좌생성 실패: " . implode(", ", $stmt_insert->errorInfo());
        }
    } else {
        echo "<script>console.log('POST로 넘어온 값이 없음');</script>";
    }
} else {
    echo "<script>console.log('회원 정보가 조회되지 않았습니다.');</script>";
}
?>
