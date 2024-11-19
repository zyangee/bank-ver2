<?php
session_start();
include "../dbconn.php";

if (isset($_SESSION["username"])) {
    header("Location: logout.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userid = $_POST['userid'];
    $password = $_POST['password'];

    try {
        $sql = "SELECT * FROM users WHERE userid = '$userid' AND password = '$password'";
        $stmt = $conn->query($sql); // 쿼리 실행
        $user = $stmt->fetch(PDO::FETCH_ASSOC); // 사용자 정보 조회

        if ($user) {
            $_SESSION['userid'] = $user['userid']; // 세션에 사용자 ID 저장
            $_SESSION['username'] = $user['username']; // 세션에 사용자 이름 저장
            $_SESSION['user_num'] = $user['user_num'];

            // 로그인 성공 시 last_login 업데이트
            $update_sql = "UPDATE users SET last_login = NOW() WHERE userid = '$userid'";
            $conn->query($update_sql);

            echo "<script>alert('로그인 성공');</script>";
            echo "<script>location.href='../index.php';</script>";
            exit(); // 리다이렉트 후 코드 실행 방지
        } else {
            echo "<script>alert('로그인 실패: 사용자 ID 또는 비밀번호가 잘못되었습니다.');</script>";
        }

    } catch (PDOException $e) {
        // 쿼리 실행 실패 시 오류 메시지 출력
        die("로그인 중 오류가 발생했습니다: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
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
            <?php
            include "../dbconn.php";
            if (isset($_SESSION['username'])): ?>
                <li><a href="../account/users.php"><?php echo $_SESSION['username']; ?></a>님</li>
                <li>|</li>
                <li><a href="logout.php">로그아웃</a></li>
            <?php else: ?>
                <li><a href="login.php">로그인</a></li>
            <?php endif; ?>
        </ul>
    </div>
    <div class="container">
        <h2 class="h2_pageinfo">로그인</h2>
        <form method="POST" action="login.php" class="form_css">
            <label class="input">사용자 ID:</label>
            <input class="input_text" type="text" name="userid" required>
            <br>
            <label class="input">비밀번호:</label>
            <input class="input_text" type="password" name="password" required>
            <br>
            <button class="submit_button" type="submit">로그인</button>
            <a class="register" href="register.php">회원가입</a>
        </form>
    </div>
</body>

</html>
