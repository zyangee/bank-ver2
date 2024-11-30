<?php
// HTTPS 강제 사용
//if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
//    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
//    exit;
//}
session_start();
//CSRF 토큰 추가
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF 토큰이 유효하지 않습니다.");
    }
} else {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 세션 유효성 검사: 세션에 `user_num`이 없으면 로그인 페이지로 리다이렉트
if (!isset($_SESSION['user_num']) || !is_numeric($_SESSION['user_num'])) {
    header('Location: login.php');
    exit();
}

//세션 관리 강화
if (!isset($_SESSION['user_ip'])) {
    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
} elseif ($_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR']) {
    session_unset();
    session_destroy();
    die("비정상적인 접근이 감지되었습니다.");
}

// 세션 ID 재생성 (세션 하이재킹 방지)
session_regenerate_id(true);
include "dbconn.php";

$user_num = $_SESSION['user_num'];
$plainPassword = "";

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

$passwordChangeMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['oldPassword'])) {
    $currentPassword = $_POST['oldPassword'];
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];

    // 새 비밀번호 강도 체크
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $newPassword)) {
        $passwordChangeMessage = "새 비밀번호는 최소 8자 이상이며, 대문자, 소문자, 숫자, 특수문자를 포함해야 합니다.";
    } else if ($newPassword !== $confirmPassword) {
        // 새 비밀번호와 확인 비밀번호 비교
        $passwordChangeMessage = "새 비밀번호와 확인 비밀번호가 일치하지 않습니다.";
    } else if (password_verify($currentPassword, $plainPassword)) {
        // 새 비밀번호 해싱
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        // 비밀번호 업데이트 쿼리
        $updateSql = "UPDATE users SET password = :password WHERE user_num = :user_num";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bindParam(':password', $newPasswordHash);
        $updateStmt->bindParam(':user_num', $user_num);

        if ($updateStmt->execute()) {
            $passwordChangeMessage = "비밀번호가 성공적으로 변경되었습니다.";
        } else {
            $passwordChangeMessage = "비밀번호 변경 중 오류가 발생했습니다.";
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
    <link rel="stylesheet" href="css/back.css">
    <link rel="stylesheet" href="css/input.css">
    <link rel="stylesheet" href="css/input_account.css">
    <script>
        window.onload = function () {
            <?php if (!empty($passwordChangeMessage)): ?>
                alert("<?php echo htmlspecialchars($passwordChangeMessage); ?>");
            <?php endif; ?>
        };

        //비밀번호 조건 추가
        document.querySelector("form").addEventListener("submit", function (event) {
            const newPassword = document.getElementById("newPassword").value;
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

            if (!passwordRegex.test(newPassword)) {
                alert("새 비밀번호는 최소 8자 이상이며, 대문자, 소문자, 숫자, 특수문자를 포함해야 합니다.");
                event.preventDefault(); // 제출 중단
            }
        });
    </script>
</head>

<body>
    <div class="navbar">
        <span>megabank</span>
        <ul>
            <li><a href="index.php">홈</a></li>
            <li>|</li>
            <?php
            include "dbconn.php";
            if (isset($_SESSION['username'])): ?>
                <li><a href="users.php"><?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></a>님
                </li>
                <li>|</li>
                <li><a href="logout.php">로그아웃</a></li>
            <?php else: ?>
                <li><a href="login.php">로그인</a></li>
            <?php endif; ?>
        </ul>
    </div>
    <div class="container">
        <h2 class="h2_pageinfo">비밀번호 변경</h2>
        <form class="form_css" method="POST" action="">
            <input type="hidden" name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>"> <!--추가-->
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