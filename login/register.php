<?php
include '../dbconn.php';

//CSRF 검증을 위한 토큰 생성
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

//회원가입 처리
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    //CSRF 토큰 검증
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        error_log("회원가입 CSRF 공격 시도 감지: " . $_SERVER['REMOTE_ADDR']);
        die("보안 검증에 실패했습니다.");
    }

    //입력값 검증 및 필터링
    $userid = filter_var($_POST['userid'], FILTER_SANITIZE_STRING);
    $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $phone1 = filter_var($_POST['phone1'], FILTER_SANITIZE_NUMBER_INT);
    $phone2 = filter_var($_POST['phone2'], FILTER_SANITIZE_NUMBER_INT);
    $phone3 = filter_var($_POST['phone3'], FILTER_SANITIZE_NUMBER_INT);
    $birth = filter_var($_POST['birth'], FILTER_SANITIZE_NUMBER_INT);

    //유효성 검사
    if (!preg_match('/^[A-Za-z0-9]{4,20}$/', $userid)) {
        die("유효하지 않는 아이디 형식입니다.");
    }
    if (!preg_match('/^[가-힣A-Za-z]{2,10}$/', $username)) {
        die("유효하지 않은 이름 형식입니다.");
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("유효하지 않은 이메일 형식입니다.");
    }
    if (!preg_match('/^\d{3}$/', $phone1) || !preg_match('/^\d{4}$/', $phone2) || !preg_match('/^\d{4}$/', $phone3)) {
        die("유효하지 않은 전화번호 형식입니다.");
    }
    if (!preg_match('/^\d{6}$/', $birth)) {
        die("유효하지 않은 생년월일 형식입니다.");
    }

    try {
        //비밀번호 정책 검증
        if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password) || !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            die("비밀번호는 8자 이상이며, 영문, 숫자, 특수문자를 포함해야 합니다.");
        }

        //비밀번호 해싱
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        //전화번호 조합
        $phone_number = $phone1 . '-' . $phone2 . '-' . $phone3;

        //먼저 해당 아이디가 존재하는지 확인
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE userid = ?");
        $check_stmt->execute([$userid]);
        if ($check_stmt->fetchColumn() > 0) {
            throw new Exception("이미 존재하는 아이디입니다.");
        }

        //SQL Injection 방지를 위한 Prepared Statement 사용
        $stmt = $conn->prepare("INSERT INTO users (userid, username, password, email, phone_number, date_of_birth, login_attempts) VALUES(?, ?, ?, ?, ?, ?, 0)");
        $stmt->execute([
            $userid,
            $username,
            $hashed_password,
            $email,
            $phone_number,
            $birth
        ]);

        //로그 기록
        error_log("새 회원가입 완료 - User: {$userid}, IP: {$_SERVER['REMOTE_ADDR']}");

        //성공 메시지 및 리다이렉트
        echo "<script>alert('회원가입이 완료되었습니다.');</script>";
        echo "<script>location.href='login.php';</script>";
        exit();
    } catch (PDOException $e) {
        //쿼리 실행 실패 시 오류 메시지 출력
        error_log("회원가입 오류 - " . $e->getMessage() . "\nSQL: " . $sql . "\nParameters: " . print_r([$userid, $username, $email, $phone_number, $birth], true));
        if ($e->getCode() == 23000) {
            die("이미 사용중인 아이디입니다.");
        } else {
            die("회원가입 처리 중 오류가 발생했습니다.");
        }
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
            if (isset($_SESSION['username'])): ?>
                <li><a href="../account/users.php"><?php echo htmlspecialchars($_SESSION['username']); ?></a>님</li>
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
        <form class="form_css" id="signForm" method="POST" onsubmit="return false;">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <div class="auth_id"> <!--아이디 입력-->
                <label class="input" for="userid">사용자 ID:</label>
                <div id="idError" class="error"></div> <!--입력 값 확인 메시지 출력-->
                <span id="useridFeedback"></span> <!--중복체크 메시지 출력-->
                <input type="text" name="userid" maxlength="20" id="userid" pattern="[A-Za-z0-9]{4,20}"
                    title="4-20자의 영문, 숫자만 사용 가능합니다." placeholder="아이디를 입력해주세요." required>
                <button type="button" onclick="checkUserID()">중복체크</button>
            </div>

            <div> <!--이름 입력-->
                <label class="input" for="username">사용자 이름:</label>
                <div id="nameError" class="error"></div> <!--입력 값 확인 메시지 출력-->
                <input class="input_text" type="text" name="username" maxlength="10" id="username"
                    pattern="[가-힣A-Za-z]{2,10}" title="2-10자의 한글 또는 영문만 사용 가능합니다." placeholder="이름을 입력해주세요." required>
            </div>

            <div> <!--비밀번호 입력-->
                <label class="input" for="password">비밀번호:</label>
                <div id="passError" class="error"></div> <!--입력 값 확인 메시지 출력-->
                <input class="input_text" type="password" name="password" maxlength="20" id="password"
                    pattern="(?=.*\d)(?=.*[a-zA-Z)(?=.*[!@#$%^&*(),.?\ :{}|<>]).{8,}" title="영문, 숫자, 특수문자 포함 8자리 이상"
                    placeholder="영문, 숫자, 특수문자 포함 8자리 이상 입력" required>
            </div>

            <div> <!--이메일 입력-->
                <label class="input" for="email">이메일:</label>
                <div id="emailError" class="error"></div> <!--입력 값 확인 메시지 출력-->
                <input class="input_text" type="text" name="email" maxlength="20" id="email" placeholder="이메일을 입력해주세요."
                    required>
            </div>

            <div> <!--핸드폰 번호 입력-->
                <label class="input">핸드폰 번호:</label>
                <div id="phoneError" class="error"></div> <!--입력 값 확인 메시지 출력-->
                <div id="phoneNum">
                    <input type="text" name="phone1" size="3" id="phone1" maxlength="3" pattern="\d{3}" title="3자리 숫자"
                        oninput="this.value=this.value.replace(/[^0-9]/g,'');" required>
                    <span>-</span>
                    <input type="text" name="phone2" size="4" id="phone2" maxlength="4" pattern="\d{4}" title="4자리 숫자"
                        oninput="this.value=this.value.replace(/[^0-9]/g,'');" required>
                    <span>-</span>
                    <input type="text" name="phone3" size="4" id="phone3" maxlength="4" pattern="\d{4}" title="4자리 숫자"
                        oninput="this.value=this.value.replace(/[^0-9]/g,'');" required>
                </div>
            </div>

            <div> <!--생년월일 입력-->
                <label class="input" for="birth">생년월일:</label>
                <div id="birthError" class="error"></div> <!--입력 값 확인 메시지 출력-->
                <input class="input_text" type="text" name="birth" id="birth" maxlength="6" pattern="\d{6}"
                    title="주민번호 앞 6자리" placeholder="주민번호 앞 6자리 입력"
                    oninput="this.value=this.value.replace(/[^0-9]/g,'');" required>
            </div>
            <button class="submit_button" type="button" id="signUp" onclick="signupCheck()">회원가입</button>
        </form>
    </div>
</body>

</html>