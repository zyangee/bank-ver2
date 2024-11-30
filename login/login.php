<?php
session_start();
include "../dbconn.php";

// X-Frame-Options 헤더 설정
header("X-Frame-Options: DENY");
// Content Security Policy 헤더 설정
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'");

//로그인 시도 횟수 제한 확인
if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= 5 && time() - $_SESSION['last_attempt'] < 300) {
    die("너무 많은 로그인 시도가 있었습니다. 5분 후에 다시 시도해주세요.");
}
//이미 로그인된 경우
if (isset($_SESSION["username"])) {
    header("Location: logout.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //CSRF 토큰 검증
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        error_log("로그인 CSRF공격 시도 감지: " . $_SERVER['REMOTE_ADDR']);
        die("보안 검증에 실패했습니다.");
    }

    // SQL Injection 방지를 위해 Prepared Statement 사용
    $userid = filter_var($_POST['userid'], FILTER_SANITIZE_STRING);
    $password = $_POST['password'];

    try {
        $sql = "SELECT * FROM users WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':userid', $userid);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC); // 사용자 정보 조회

        if ($user && password_verify($password, $user['password'])) {
            //로그인 성공
            session_regenerate_id(true); //세션 ID 재생성

            $_SESSION['userid'] = $user['userid']; // 세션에 사용자 ID 저장
            $_SESSION['username'] = $user['username']; // 세션에 사용자 이름 저장
            $_SESSION['user_num'] = $user['user_num'];
            $_SESSION['last_activity'] = time();
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];

            //로그인 시도 횟수 초기화
            unset($_SESSION['login_attempts']);
            unset($_SESSION['last_attempt']);

            // 로그인 성공 시 last_login 업데이트
            $update_sql = "UPDATE users SET last_login = NOW(), ip = :ip, login_attempts = 0 WHERE userid = :userid";
            $stmt = $conn->prepare($update_sql);
            $stmt->bindParam(':userid', $userid);
            $stmt->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
            $stmt->execute();

            //로그인 성공 로그
            error_log("로그인 성공 - User: {$userid}, IP: {$_SERVER['REMOTE_ADDR']}");
            echo '<script>alert("로그인 성공");</script>';

            header("Location: ../index.php");
            exit();
        } else {
            //로그인 실패
            $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
            $_SESSION['last_attempt'] = time();

            error_log("로그인 실패 - User: {$userid}, IP: {$_SERVER['REMOTE_ADDR']}, Attempts: {$_SESSION['login_attempts']}");

            //실패 횟수 업데이트
            $fail_sql = "UPDATE users SET login_attempts = login_attempts + 1 WHERE userid = :userid";
            $stmt = $conn->prepare($fail_sql);
            $stmt->bindParam(':userid', $userid);
            $stmt->execute();

            $error_message = "아이디 또는 비밀번호가 올바르지 않습니다.";
        }
    } catch (PDOException $e) {
        // 쿼리 실행 실패 시 오류 메시지 출력
        error_log("로그인 오류: " . $e->getMessage());
        $error_message = "로그인 처리 중 오류가 발생했습니다.";
    }
}
?>

<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">

    <title>로그인</title>
    <link rel="stylesheet" href="../css/back.css">
    <link rel="stylesheet" href="../css/input.css">
</head>

<body>
    <div class="navbar">
        <span>megabank</span>
        <ul>
            <li><a href="../index.php">홈</a></li>
            <li>|</li>
            <?php if (isset($_SESSION['username'])): ?>
                <li><a href="../account/users.php"><?php echo htmlspecialchars($_SESSION['username']); ?></a>님</li>
                <li>|</li>
                <li><a href="logout.php">로그아웃</a></li>
            <?php else: ?>
                <li><a href="login.php">로그인</a></li>
            <?php endif; ?>
        </ul>
    </div>
    <div class="container">
        <h2 class="h2_pageinfo">로그인</h2>
        <?php if (isset($error_message)): ?>
            <div class="error_message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['login_attempts'])): ?>
            <div class="warning-message">
                남은 시도 횟수: <?php echo (5 - $_SESSION['login_attempts']) ?>회
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="form_css" id="loginForm">
            <!--CSRF 토큰 추가-->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']) ?>">

            <label class="input" for="userid">사용자 ID:</label>
            <input class="input_text" type="text" id="userid" name="userid" required pattern="[A-Za-z0-9]{4,20}"
                title="4-20자의 영문, 숫자만 사용 가능합니다.">

            <label class="input" for="password">비밀번호:</label>
            <input class="input_text" type="password" id="password" name="password" required minlength="8"
                maxlength="32">

            <button class="submit_button" type="submit">로그인</button>
            <a class="register" href="register.php">회원가입</a>
        </form>
    </div>
    <script>
        //폼 유효성 검사
        document.addEventListener('DOMContentLoaded', function () {
            const loginForm = document.getElementById('loginForm');
            loginForm.addEventListener('submit', function (e) {
                const userid = document.getElementById('userid').value;
                const password = document.getElementById('password').value;

                if (!/^[A-Za-z0-9]{4,20}$/.test(userid)) {
                    alert('유효하지 않은 아이디 형식입니다.');
                    e.preventDefault();
                    return false;
                }
                if (password.length < 8) {
                    alert('비밀번호는 최소 8자 이상이어야 합니다.');
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>
</body>

</html>