<?php
include '../dbconn.php';

//회원가입 처리
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userid = $_POST['userid'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone1'] . '-' . $_POST['phone2'] . '-' . $_POST['phone3'];
    $birth = $_POST['birth'];

    try {
        $sql = "INSERT INTO users(userid, username, password, email, phone_number, date_of_birth) VALUES('$userid', '$username', '$password', '$email', '$phone_number', '$birth')";

        //쿼리 실행
        $conn->exec($sql);

        echo "회원가입이 완료되었습니다."; //성공 메시지 출력
        header('Location: login.php'); //로그인 페이지 이동
    } catch (PDOException $e) {
        //쿼리 실행 실패 시 오류 메시지 출력
        die("회원가입 중 오류가 발생했습니다: " . $e->getMessage());
    }
}
?>
<html>

<head>
    <script src="../javascript/register.js"></script>
    <link rel="stylesheet" href="../css/input.css">
    <link rel="stylesheet" href="../css/back.css">
    <style>
        .auth_id input {
            width: calc(100% - 140px);
            /* 버튼 너비만큼 줄임 */
            padding: 10px;
            margin-bottom: 30px;
            border: 1px solid #cccccc;
            border-radius: 5px;
            font-size: 16px;
            display: inline-block;
            box-sizing: border-box;
        }

        .auth_id button {
            padding: 10px 15px;
            background-color: #003366;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            display: inline-block;
            margin-left: 10px;
        }

        #phoneNum {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 300px;
            margin-top: 10px;
        }

        #phoneNum input[type="text"] {
            padding: 10px;
            margin: 0 auto;
            margin-bottom: 20px;
            border: 1px solid #cccccc;
            border-radius: 5px;
            font-size: 16px;
            text-align: center;
            width: 35%;
            box-sizing: border-box;
        }

        #phoneNum span {
            margin: 0 10px;
            font-size: 18px;
        }
    </style>
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
        <h2 class="h2_pageinfo">계좌 생성</h2>
        <!--회원가입 폼-->
        <form class="form_css" id="signForm" method="POST">
            <div class="auth_id"> <!--아이디 입력-->
                <label class="input">사용자 ID:</label>
                <div id="idError" class="error"></div> <!--입력 값 확인 메시지 출력-->
                <span id="useridFeedback"></span> <!--중복체크 메시지 출력-->
                <input type="text" name="userid" maxlength="20" id="userid" placeholder="아이디를 입력해주세요." required>
                <button type="button" onclick="checkUserID()">중복체크</button>
            </div>
            <div> <!--이름 입력-->
                <label class="input">사용자 이름:</label>
                <div id="nameError" class="error"></div> <!--입력 값 확인 메시지 출력-->
                <input class="input_text" type="text" name="username" maxlength="10" id="username"
                    placeholder="이름을 입력해주세요." required>
            </div>
            <div> <!--비밀번호 입력-->
                <label class="input">비밀번호:</label>
                <div id="passError" class="error"></div> <!--입력 값 확인 메시지 출력-->
                <input class="input_text" type="password" name="password" maxlength="20" id="password"
                    placeholder="영문, 숫자, 특수문자 포함 8자리 이상 입력" required>
            </div>
            <div> <!--이메일 입력-->
                <label class="input">이메일:</label>
                <div id="emailError" class="error"></div> <!--입력 값 확인 메시지 출력-->
                <input class="input_text" type="text" name="email" maxlength="20" id="email" placeholder="이메일을 입력해주세요."
                    required>
            </div>
            <div> <!--핸드폰 번호 입력-->
                <label class="input">핸드폰 번호:</label>
                <div id="phoneError" class="error"></div> <!--입력 값 확인 메시지 출력-->
                <div id="phoneNum">
                    <input type="text" name="phone1" size="3" id="phone1" maxlength="3" oninput="nextPhone1()" required>
                    <span>-</span>
                    <input type="text" name="phone2" size="6" id="phone2" maxlength="4" oninput="nextPhone2()" required>
                    <span>-</span>
                    <input type="text" name="phone3" size="6" id="phone3" maxlength="4" required>
                </div>
            </div>
            <div> <!--생년월일 입력-->
                <label class="input">생년월일:</label>
                <div id="birthError" class="error"></div> <!--입력 값 확인 메시지 출력-->
                <input class="input_text" type="text" name="birth" id="birth" maxlength="6" placeholder="주민번호 앞 6자리 입력"
                    required>
            </div>
            <button class="submit_button" type="button" id="signUp" onclick="signupCheck()">회원가입</button>
        </form>
    </div>
</body>

</html>