<?php
// HTTPS 강제 사용
//if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
//    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
//    exit;
//}
session_start();

// 세션 유효성 검사
if (!isset($_SESSION['user_num']) || !is_numeric($_SESSION['user_num'])) {
    header('Location: ../login/login.php');
    exit();
}

// 보안 헤더 설정
header("X-Frame-Options: DENY");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';");
header("X-Content-Type-Options: nosniff");

?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>대출 신청</title>
    <link rel="stylesheet" href="../css/back.css">
    <link rel="stylesheet" href="../css/input.css">
    <link rel="stylesheet" href="../css/input_account.css">
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
        <h2 class="h2_pageinfo">대출신청</h2>
        <form class="form_css" id="loanForm" onsubmit="return false;">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div>
                <label class="input" for="loanType">대출 종류</label>
                <select class="select" id="loanType" name="loanType" onchange="updateInterestRates()">
                    <option value="default">선택</option>
                    <option value="신용대출">신용대출</option>
                    <option value="담보대출">담보대출</option>
                    <option value="자동차대출">자동차대출</option>
                    <option value="사업자대출">사업자대출</option>
                </select>
            </div>
            <div>
                <label class="input" for="loanAmount">대출 금액</label>
                <input class="input_text" type="number" id="loanAmount" name="loanAmount"
                    placeholder="최소 금액은 1,000,000월 입니다." required min="1000000" max="1000000000" step="1000000">
            </div>
            <div>
                <label class="input" for="totalAssets">총 자산</label>
                <div id="memo">자동으로 적용됩니다.</div>
                <input class="input_text" type="text" id="totalAssets" name="totalAssets"
                    value="<?php echo number_format($totalAssets); ?>" readonly />
            </div>
            <div>
                <label class="input" for="interestRate">적용 금리 (%)</label>
                <div id="memo">자동으로 적용됩니다.</div>
                <input class="input_text" type="text" id="interestRate" name="interestRate" readonly>
            </div>
            <div>
                <label class="input" for="loanStartDate">대출 시작일</label>
                <?php
                $today = date('Y-m-d'); // 오늘 날짜를 'YYYY-MM-DD' 형식으로 가져옴
                ?>
                <input class="input_text" type="date" id="loanStartDate" name="loanStartDate"
                    min="<?php echo $today; ?>" onchange="updateEndDate()" required> <!--오늘날짜이전 선택불가-->
            </div>
            <div>
                <label class="input" for="loanEndDate">대출 종료일</label>
                <div id="memo">자동으로 적용됩니다.</div>
                <input class="input_text" type="date" id="loanEndDate" name="loanEndDate" readonly required>
            </div>
            <div>
                <label class="input" for="loanPurpose">대출 용도</label>
                <select class="select" id="loanPurpose" name="loanPurpose">
                    <option value="default">선택</option>
                    <option value="homePurchase">주택구입자금</option>
                    <option value="rentalDeposit">주거목적임차자금</option>
                    <option value="livingExpenses">생활자금</option>
                    <option value="debtRepayment">부채상환</option>
                    <option value="relocation">이주비</option>
                    <option value="interimPayment">중도금대출</option>
                    <option value="realEstatePurchase">부동산구입자금</option>
                </select>
            </div>
            <div>
                <label class="input" for="loanRepaymentIncome">대출상환소득</label>
                <select class="select" id="loanRepaymentIncome" name="loanRepaymentIncome">
                    <option value="default">선택</option>
                    <option value="salary">근로소득</option>
                    <option value="businessIncome">사업소득</option>
                    <option value="rentalIncome">임대소득</option>
                    <option value="pension">연금소득</option>
                    <option value="other">기타소득</option>
                </select>
            </div>
            <div>
                <label class="input">
                    <input type="checkbox" id="confirmationCheckbox" onclick="toggleSubmitButton()">
                    위 내용이 사실과 다름이 없음을 확인합니다.
                </label>
            </div>
            <button class="submit_button" type="submit" id="submitButton" disabled>신청하기</button> <!-- disabled 속성 추가 -->
        </form>
    </div>
    <script src="../javascript/loanApplication.js"></script>
</body>

</html>