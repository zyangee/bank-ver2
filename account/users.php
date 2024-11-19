<?php
session_start();
include "../dbconn.php";
// 사용자 ID는 세션에서 가져온다고 가정합니다.
$user_num = $_SESSION['user_num']; // 여기 user_num으로 수정하면서 24, 87, 91, 157 수정함. 157이 id일떄 안됨

// 사용자 정보를 가져오는 SQL 쿼리
$sql = "SELECT username, phone_number, userid, email, date_of_birth, account_created_at, last_login, password 
        FROM users 
        WHERE user_num = :user_num";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_num', $user_num);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // 사용자 정보 변수 설정
        $username = $user['username'];
        $phone_number = $user['phone_number'];
        $email = $user['email'];
        $dob = $user['date_of_birth'];
        $join_date = $user['account_created_at'];
        $last_login = $user['last_login'];
        $plainPassword = $user['password']; // 저장된 평문 비밀번호
    } else {
        die("사용자 정보를 찾을 수 없습니다.");
    }
} catch (Exception $e) {
    echo "쿼리 오류: " . $e->getMessage();
    exit;
}

?>

<?php
// 계좌 정보를 가져오는 SQL 쿼리
$sqlAccounts = "SELECT account_number, balance, created_at FROM accounts WHERE user_num = :user_num";

try {
    $stmtAccounts = $conn->prepare($sqlAccounts);
    $stmtAccounts->bindParam(':user_num', $user_num); // user_id를 user_num으로 사용
    $stmtAccounts->execute();

    $accounts = $stmtAccounts->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo "계좌 조회 오류: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>계정 관리</title>
    <link rel="stylesheet" href="../css/back.css">
    <link rel="stylesheet" href="../css/input.css">
    <link rel="stylesheet" href="../css/user.css">
</head>

<body>
    <header>
        <div class="navbar">
            <span>megabank</span>
            <ul>
                <li><a href="../index.php">홈</a></li>
                <li>|</li>
                <?php
                include "dbconn.php";
                if (isset($_SESSION['username'])): ?>
                    <li><a href="../account/users.php"><?php echo $_SESSION['username']; ?></a>님</li>
                    <li>|</li>
                    <li><a href="../login/logout.php">로그아웃</a></li>
                <?php else: ?>
                    <li><a href="../login/login.php">로그인</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </header>
    <div class="container">
        <div class="main">
            <h2 class="h2_pageinfo">계정 관리</h2>
            <div class="form_css">
                <table class="info-table">
                    <tr>
                        <th>이름</th>
                        <td><?php echo ($username); ?></td>
                        <th>핸드폰 번호</th>
                        <td><?php echo ($phone_number); ?></td>
                    </tr>
                    <tr>
                        <th>사용자 ID</th>
                        <td><?php echo ($user_num); ?></td>
                        <th>비밀번호 변경</th>
                        <td><a href="change_password.php?user_num=<?php echo $user_num; ?>">비밀번호 변경</a></td>
                    </tr>
                    <tr>
                        <th>이메일</th>
                        <td><?php echo ($email); ?></td>
                        <th>생년월일</th>
                        <td><?php echo ($dob); ?></td>
                    </tr>
                    <tr>
                        <th>회원가입일</th>
                        <td><?php echo ($join_date); ?></td>
                        <th>마지막 로그인</th>
                        <td><?php echo ($last_login); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="account-info">
            <table class="account-table">
                <tr>
                    <th>계좌번호</th>
                    <th>잔액</th>
                    <th>생성일</th>
                </tr>
                <?php if ($accounts): ?>
                    <?php foreach ($accounts as $account): ?>
                        <tr>
                            <td><?php echo ($account['account_number']); ?></td>
                            <td><?php echo ($account['balance']); ?> 원</td>
                            <td><?php echo ($account['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">계좌 정보가 없습니다.</td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

</body>

</html>
