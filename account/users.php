<?php
session_start();
include "../dbconn.php";

//세션 체크 및 권한 검증
if (!isset($_SESSION['user_num'])) {
    header("Location: ../login/login.php");
    exit();
}

//세션 하이재킹 방지
session_regenerate_id(true);

//비활성 세션 처리
$inactive = 1800; //30분
if (isset($_SESSION['timeout']) && (time() - $_SESSION['timeout'] > $inactive)) {
    session_unset();
    session_destroy();
    header("Location: ../login/login.php");
    exit();
}
$_SESSION['timeout'] = time();

// 사용자 정보를 가져오는 SQL 쿼리
$sql = "SELECT username, phone_number, userid, email, date_of_birth, account_created_at, last_login 
        FROM users 
        WHERE user_num = :user_num";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_num', $user_num);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        error_log("유저 정보를 찾을 수 없습니다. : " . $_SESSION['user_num']);
        header("Location: ../login/logout.php");
        exit();
    }

    //계좌 정보를 가져오는 SQL 쿼리
    $sqlAccounts = "SELECT account_number, balance, created_at FROM accounts WHERE user_num = :user_num";
    $stmtAccounts = $conn->prepare($sqlAccounts);
    $stmtAccounts->bindParam(':user_num', $user_num);
    $stmtAccounts->execute();

    $accounts = $stmtAccounts->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    die("시스템 오류가 발생했습니다. 나중에 다시 시도해주세요.");
}
?>

<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-Content-Security-Policy" content="default-src 'self'">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; style-src 'self' 'unsafe-inline';">

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
                <?php if (isset($_SESSION['username'])): ?>
                    <li><a href="../account/users.php"><?php echo htmlspecialchars($_SESSION['username']); ?></a>님</li>
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
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <th>핸드폰 번호</th>
                        <td><?php echo htmlspecialchars($user['phone_number']); ?></td>
                    </tr>
                    <tr>
                        <th>사용자 ID</th>
                        <td><?php echo htmlspecialchars($_SESSION['user_num']); ?></td>
                        <th>비밀번호 변경</th>
                        <td><a
                                href="change_password.php?user_num=<?php echo htmlspecialchars($_SESSION['user_num']); ?>">비밀번호
                                변경</a></td>
                    </tr>
                    <tr>
                        <th>이메일</th>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <th>생년월일</th>
                        <td><?php echo htmlspecialchars($user['date_of_birth']); ?></td>
                    </tr>
                    <tr>
                        <th>회원가입일</th>
                        <td><?php echo htmlspecialchars($user['account_created_at']); ?></td>
                        <th>마지막 로그인</th>
                        <td><?php echo htmlspecialchars($user['last_login']); ?></td>
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
                            <td><?php echo htmlspecialchars($account['account_number']); ?></td>
                            <td><?php echo number_format($account['balance']); ?> 원</td>
                            <td><?php echo htmlspecialchars($account['created_at']); ?></td>
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