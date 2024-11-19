<?php
include "../dbconn.php";
include "../api/random_account.php";
?>

<!DOCTYPE html>
<html>

<head>
    <script src="../javascript/accountAdd.js"></script>
    <link rel="stylesheet" href="../css/back.css">
    <link rel="stylesheet" href="../css/input.css">
    <link rel="stylesheet" href="../css/input_account.css">
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
        <h2 class="h2_pageinfo">계좌 생성</h2>
        <form class="form_css" action="" onsubmit="submitForm(event)" method="POST">
            <div id="section">
                <div>
                    <label class="input">이름</label> <!--DB에 있는 이름 그대로 가져오기-->
                    <input class="input_text" type="text" id="username" name="username"
                        value="<?php echo $_SESSION['username'] ?>" readonly>
                </div>

                <label class="input">주민번호</label>
                <div class="align-right-input">
                    <input type="text" id="resident-number1" name="resident-number1"
                        value="<?php echo ($resident_number1) ?>">
                    <span>-</span>
                    <input type="text" id="resident-number2" name="resident-number2" maxlength="7"
                        value="<?php echo !empty($resident_number2) ? htmlspecialchars($resident_number2) : ''; ?>"
                        <?php echo !empty($resident_number2) ? 'readonly' : ''; ?> required>
                    <div id="resident-error" class="error"></div><!--주민번호 에러메시지-->
                </div>
                <div>
                    <label class="input">초기 금액</label>
                    <input class="input_text" type="number" id="balance" name="balance" required>
                    <div id="balance-error" class="error"></div><!--초기금액 에러메시지-->
                </div>
                <div class="auth_num"><!--인증번호-->
                    <label class="input">인증번호</label>
                    <div id="memo">인증번호 발급받은 후 인증하기 버튼을 눌러야 계좌 생성이 가능합니다</div>
                    <input class="input_text next_button" type="text" id="auth-code" name="auth-code" maxlength="6">
                    <button class="input_button" type="button" onclick="addAuthCode()">인증번호 발급</button>

                    <div id="authentication" style="display:none;"> <!--발급 버튼을 눌러야 보임-->
                        <div id="authentication-code"></div><!--인증번호 보이는 부분-->
                        <button type="button" onclick="validAuthCode()">인증하기</button>
                    </div>
                    <div id="auth-error"></div>
                </div>

                <div> <!--숫자만 입력되게, 자리수는 4자리-->
                    <label class="input">통장 비밀번호</label>
                    <input class="input_text" type="password" id="account-password" name="account-password"
                        maxlength="4" required>
                    <div id="password-error" class="error"></div><!--통장 비밀번호 에러메시지-->
                </div>
                <div><!--계좌 사용용도-->
                    <label class="input">계좌 사용용도</label>
                    <select id="purpose" class="select" required>
                        <option>선택해주세요.</option>
                        <option>급여 및 아르바이트</option>
                        <option>생활비 관리</option>
                        <option>적금 자동이체</option>
                        <option>예금 가입</option>
                        <option>대출신청</option>
                    </select>
                    <div id="select-error" class="error"></div><!--select 옵션 선택 에러메시지-->
                </div>
                <div><!--체크 옵션-->
                    <div class="radio_check"> <!--체크옵션 1-->
                        <label class="input">타인으로부터 통장대여 요청을 받은 사실이 있나요?</label>
                        <div>
                            <input type="radio" name="check1" value="예">예
                            <input type="radio" name="check1" value="아니오">아니오
                        </div>
                    </div>
                    <div class="radio_check"> <!--체크옵션 2-->
                        <label class="input">타인으로부터 통장개설을 요청받은 사실이 있나요?</label>
                        <div>
                            <input type="radio" name="check2" value="예">예
                            <input type="radio" name="check2" value="아니오">아니오
                        </div>
                    </div>
                    <div id="check-error" class="error"></div><!--check 옵션 선택 에러메시지-->
                </div><!--체크 옵션 div-->
            </div>
            <input class="submit_button" type="submit" id="create-account" value="계좌 생성" disabled>
        </form>
    </div>
</body>

</html>
