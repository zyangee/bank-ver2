<?php
session_start();
include "../dbconn.php";

$user_num = $_SESSION['user_num'];
$plainPassword = ""; // 초기화

// 사용자 정보를 가져오는 SQL 쿼리
$sql = "SELECT password FROM users WHERE user_num = :user_num";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_num', $user_num);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $plainPassword = $user['password'];
    } else {
        die("사용자 정보를 찾을 수 없습니다.");
    }
} catch (Exception $e) {
    echo "쿼리 오류: " . $e->getMessage();
    exit;
}

// 비밀번호 변경 처리
$passwordChangeMessage = ""; // 기본값 설정

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['oldPassword'])) {
    $currentPassword = $_POST['oldPassword'];
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];

    // 현재 비밀번호 확인
    if ($currentPassword === $plainPassword) {
        // 새 비밀번호와 확인 비밀번호가 일치하는지 확인
        if ($newPassword !== $confirmPassword) {
            $passwordChangeMessage = "새 비밀번호와 확인 비밀번호가 일치하지 않습니다.";
        } else {
            // 비밀번호 업데이트 쿼리
            $updateSql = "UPDATE users SET password = :password WHERE user_num = :user_num";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bindParam(':password', $newPassword);
            $updateStmt->bindParam(':user_num', $user_num);

            if ($updateStmt->execute()) {
                $passwordChangeMessage = "비밀번호가 성공적으로 변경되었습니다."; // 메시지 설정
            } else {
                $passwordChangeMessage = "비밀번호 변경 중 오류가 발생했습니다.";
            }
        }
    } else {
        $passwordChangeMessage = "현재 비밀번호가 올바르지 않습니다.";
    }
}
?>

<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>비밀번호 변경</title>
    <link rel="stylesheet" href="../css/back.css">
    <link rel="stylesheet" href="../css/input.css">
    <link rel="stylesheet" href="../css/input_account.css">
    <script>
        window.onload = function () {
            <?php if (!empty($passwordChangeMessage)): ?>
                alert("<?php echo $passwordChangeMessage; ?>");
            <?php endif; ?>
        };
    </script>
</head>

<body>
    <div class="navbar">
        <span>megabank</span>
        <ul>
            <li><a href="../index.php">홈</a></li>
            <li>|</li>
            <?php
            include "../dbconn.php";
            if (isset($_SESSION['username'])): ?>
                <li><a href="../account/users.php"><?php echo $_SESSION['username']; ?></a>님</li>
                <li>|</li>
                <li><a href="../login/logout.php">로그아웃</a></li>
            <?php else: ?>
                <li><a href="../login/login.php">로그인</a></li>
            <?php endif; ?>
        </ul>
    </div>
    <div class="container">
        <h2 class="h2_pageinfo">비밀번호 변경</h2>
        <form class="form_css" method="POST" action="">
            <label class="input" for="oldPassword">현재 비밀번호:</label><br>
            <input class="input_text" type="password" id="oldPassword" name="oldPassword" required><br><br>
            <label class="input" for="newPassword">새 비밀번호:</label><br>
            <input class="input_text" type="password" id="newPassword" name="newPassword" required><br><br>
            <label class="input" for="confirmPassword">새 비밀번호 확인:</label><br>
            <input class="input_text" type="password" id="confirmPassword" name="confirmPassword" required><br><br>
            <button class="submit_button" type="submit">비밀번호 변경</button>
        </form>
    </div>
</body>

</html>