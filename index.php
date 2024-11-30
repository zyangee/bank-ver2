<?php
require_once "dbconn.php";

//세션 상태 검증
if (!isset($_SESSION['last_activity']) || (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header("Location: login/login.php");
    exit();
}
$_SESSION['last_activity'] = time();

//로그인 성공 메시지 출력
if (isset($_SESSION['login_message'])) {
    echo '<script>alert("' . $_SESSION['login_message'] . '");<script>';
    unset($_SESSION['login_message']);
}

//보안 헤더 설정
header("X-Frame-Options: DENY");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';");

?>

<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">

    <title>main</title>
    <link rel="stylesheet" href="css/back.css">
    <!--CSP 강화-->
    <style>
        .category-container {
            display: flex;
            justify-content: center;
            margin-top: 100px;
            flex-wrap: wrap;
        }

        .category {
            margin: 20px;
            padding: 20px;
            border: 2px solid #ccc;
            border-radius: 15px;
            width: 250px;
            text-align: center;
            background-color: #fff;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease-in-out;
            /*마우스 올렸을 때 애니메이션 효과 */
        }

        .category:hover {
            transform: translateY(-10px);
            box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
        }

        .category ul {
            list-style: none;
            padding: 0;
            margin-top: 20px;
        }

        .category ul li {
            margin: 10px 0;
            font-size: 1em;
            color: #333;
            text-decoration: none;
        }

        .category ul li a {
            text-decoration: none;
            color: black;
        }

        .category ul a:hover {
            color: #ffb900;
            cursor: pointer;

        }

        .category-header {
            font-size: 1.5em;
            font-weight: bold;
            color: #444;
            margin-bottom: 10px;
            padding: 10px 0;
            border-bottom: 2px solid #ccc;
        }
    </style>
</head>

<body>
    <div class="navbar">
        <span>megabank</span>
        <ul>
            <?php
            if (isset($_SESSION['username'])): ?>
                <li><a href="account/users.php"><?php echo htmlspecialchars($_SESSION['username']); ?></a>님</li>
                <li>|</li>
                <li><a href="login/logout.php">로그아웃</a></li>
            <?php else: ?>
                <li><a href="login/login.php">로그인</a></li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="category-container" id="category-container">
        <?php if (isset($_SESSION['userid'])): ?>
            <div class="category">
                <div class="category-header">계좌</div>
                <ul>
                    <li><a href="account/account_add.php">계좌 생성</a></li>
                    <li><a href="account/transfer.php">송금</a></li>
                    <li><a href="account/transactions.php">거래 내역</a></li>
                </ul>
            </div>
            <div class="category">
                <div class="category-header">대출</div>
                <ul>
                    <li><a href="loans/loan_application.php">대출 신청</a></li>
                    <li><a href="loans/loan_history.php">대출 조회</a></li>
                    <li><a href="loans/loan_product.php">대출 상품 조회</a></li>
                </ul>
            </div>
            <div class="category">
                <div class="category-header">계정</div>
                <ul>
                    <li><a href="account/users.php">계좌 정보</a></li>
                    <li><a href="account/change_password.php">비밀번호 변경</a></li>
                </ul>
            </div>
        <?php else: ?>
            <div class="category">
                <div class="category-header">서비스 이용 안내</div>
                <li>서비스 이용을 위해서는 로그인이 필요합니다.</li>
                <li><a href="login/login.php">로그인 하러가기</a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>