<?php
// HTTPS 강제 사용
//if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
//    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
//    exit;

header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self'"); //CSP설정
header("X-Frame-Options: DENY"); //클릭재킹방지
?>

<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>대출</title>
    <link rel="stylesheet" href="../css/back.css">
    <link rel="stylesheet" href="../css/input.css">
    <link rel="stylesheet" href="../css/input_account.css">
    <link rel="stylesheet" href="../css/loan.css">
</head>

<body>
    <header>
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
    </header>
    <div class="container">
        <h2 class="h2_pageinfo">대출 상품</h2>
        <div class="">
            <div class="loan-product">
                <h3>신용대출</h3>
                <p>최대 금액: 3억 원</p>
                <p>최저 금리: 3.5%</p>
                <p>기간: 1년 ~ 5년</p>
                <button class="input_button" type="button" onclick="toggleDetails(this)">자세히 보기</button>
                <div class="details" style="display: none;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <th>상품 특징</th>
                            <td>직장인이라면 신청 가능<br>
                                '내맘대로 통장자동대출' 선택 시, 최초 약정한 금액 범위 내에서 사용한도의 자유로운 증액과 감액이 가능하며, 추가 우대금리가 제공됩니다.
                            </td>
                        </tr>
                        <tr>
                            <th>대출 신청 자격</th>
                            <td>재직기간 3개월 이상 당행 선정 우량 직장인 및 재직기간 6개월 이상 일반 직장인<br>
                                ☞ 단, "정규직 공무원(최종합격자 포함), 군인(중사 이상), 교사"는 재직기간 관계없음
                            </td>
                        </tr>
                        <tr>
                            <th>대출 금액</th>
                            <td>최대 3억원 이내(단, 재직기간 1년 미만 사회초년생은 최대 5천만원 이내)<br>
                                - 종합통장자동대출은 최대 1억원 이내<br>
                                - 금융소외계층(최근 2년 이내 신용카드 실적 및 최근 3년 이내 대출실적 없는 고객)은 최대 3백만원 이내 기본한도 제공<br>
                                ※ 대출한도는 신용평가결과에 따라 차등 적용됩니다.
                            </td>
                        </tr>
                        <tr>
                            <th>대출 기간 및 상환 방법</th>
                            <td>일시상환(종합통장자동대출 포함) : 1년(최장 10년 이내 기한연장 가능)<br>
                                ※ 종합통장자동대출의 경우 아래 중 선택<br>
                                ① 일반방식 한도거래대출(종합통장자동대출)<br>
                                ② 약정한도 증감방식 한도거래대출(내맘대로통장자동대출)<br>
                                원(리)금균등 분할상환<br>
                                ① CSS 1~3등급 : 최저 1년 이상 최장 10년 이내<br>
                                ② CSS 4등급 이하 : 최저 1년 이상 최장 5년 이내<br>
                                ※ 대출기간 30% 이내 최장 12개월까지 거치기간 운용 가능
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="loan-product">
                <h3>담보대출</h3>
                <p>최대 금액: 대출가능금액 이내</p>
                <p>최저 금리: 4.0%</p>
                <p>기간: 1년 ~ 10년</p>
                <button class="input_button" type="button" onclick="toggleDetails(this)">자세히 보기</button>
                <div class="details" style="display: none;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <th>대출 상품</th>
                            <td>혼합금리와 변동금리 중 선택이 가능한 주택담보대출</td>
                        </tr>
                        <tr>
                            <th>대출 신청 자격</th>
                            <td>주택을 담보로 대출 신청하는 고객<br>
                                (주택구입/신축/경락주택 구입자금대출 및 통장자동대출 포함)
                            </td>
                        </tr>
                        <tr>
                            <th>대출 금액</th>
                            <td>담보조사가격 및 소득금액, 담보물건지 지역 등에 따른 대출가능금액 이내<br>
                                (통장자동대출은 최고 3억원 이내)
                            </td>
                        </tr>
                        <tr>
                            <th>대출 기간 및 상환 방법</th>
                            <td>
                                <strong>대출기간:</strong><br>
                                (1) 일시상환(통장자동대출 포함) : 최저 1년 이상 최장 5년 이내(통장자동대출은 1년)<br>
                                (2) 분할상환(원금균등, 원리금균등, 고객원금지정, 할부금고정) : 최저 1년 이상 최장 50년 이내, 대출기간 40년 초과 대출은 만34세 이하만
                                가능<br>
                                단, 할부금고정 분할상환 방식은 최장 10년 이내<br>
                                (3) 혼합상환(일시상환+분할상환) : 최저 1년 이상 최장 20년 이내<br>
                                ※ 위의 (1)~(3) 에도 불구하고 최저 대출기간은 금리변동주기 이상, 혼합금리대출은 10년 이상으로 운용
                            </td>
                        </tr>
                        <tr>
                            <th>거치기간</th>
                            <td>총 대출기간의 30% 범위 내에서 최장 3년 이내<br>
                                단, 「여신심사 선진화를 위한 모범규준」에서 정한 경우에는 비거치 또는 거치기간 1년으로 운용
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="loan-product">
                <h3>자동차대출</h3>
                <p>최대 금액: 6,000만원</p>
                <p>최저 금리: 3.8%</p>
                <p>기간: 1년 ~ 7년</p>
                <button class="input_button" type="button" onclick="toggleDetails(this)">자세히 보기</button>
                <div class="details" style="display: none;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <th>대출 상품</th>
                            <td> -캐피탈 대비 유리한 금리조건으로 신규자동차를 구매하려는 고객을 위한 비대면 전용 대출상품<br>
                                -별도의 취급수수료, 보험료, 근저당설정 부담 없음<br>
                            </td>
                        </tr>
                        <tr>
                            <th>대출 신청 자격</th>
                            <td>신차를 구입하는 고객으로 소득 증빙이 가능한 만 19세 이상의 고객<br>
                                ※ 대출대상 : 근로소득자(6개월 이상), 사업소득자(12개월 이상), 연금소득자<br>
                                ※ 대상차종 : 승용차, 승합차, 화물차(5톤 이하) 및 대형이륜자동차(260CC 초과)<br>
                                (개인택시, 캠핑카 및 카라반, 수입차 포함)
                            </td>
                        </tr>
                        <tr>
                            <th>대출 금액</th>
                            <td>자동차판매가격 중 차량가격 이내로 서울보증보험 보증한도 이내(최저 3백만원, 최고 6천만원)<br>
                                - 단, 자동차 구매에 수반되는 부대비용은 포함 불가<br>
                                - 만 25세 미만의 경우 차량(매매)가격의 80%까지만 대출 가능<br>
                                ※ 부대비용 : 차량등록 관련비용, 취득세, 자동차세, 보험료, 특수장치 장착비용, 탁송료 등<br>
                                ※ 보증한도 : 고객님의 연소득과 신용등급 등을 기준으로 산출된 보증가능금액
                            </td>
                        </tr>
                        <tr>
                            <th>대출 기간 및 상환 방법</th>
                            <td>12개월 이상 ~ 120개월 이내 (거치기간 없음)<br>
                                원(리)금균등 분할상환<br>
                                ※ 통장자동대출 운용 불가
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="loan-product">
                <h3>사업자대출</h3>
                <p>최대 금액: 5억 원</p>
                <p>최저 금리: 4.5%</p>
                <p>기간: 1년 ~ 10년</p>
                <button class="input_button" type="button" onclick="toggleDetails(this)">자세히 보기</button>
                <div class="details" style="display: none;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <th>대출 상품</th>
                            <td>사업 운영 자금을 지원하는 대출 상품<br>
                                유연한 상환 조건과 경쟁력 있는 금리 제공
                            </td>
                        </tr>
                        <tr>
                            <th>대출 신청 자격</th>
                            <td>사업자 등록 후 최소 6개월 이상 운영 중인 사업체 소유자<br>
                                ※ 개인사업자 및 법인사업자 모두 신청 가능<br>
                                ※ 신용도 및 사업실적에 따라 대출 가능 여부 결정
                            </td>
                        </tr>
                        <tr>
                            <th>대출 금액</th>
                            <td>최소 1천만원에서 최대 5억원까지 가능<br>
                                - 대출 가능 금액은 사업 규모와 신용도에 따라 다름<br>
                                - 사업 운영에 필요한 각종 비용(재료비, 인건비 등) 포함 가능
                            </td>
                        </tr>
                        <tr>
                            <th>대출 기간 및 상환 방법</th>
                            <td>대출 기간: 1년에서 5년 이내<br>
                                원(리)금 균등 분할 상환 또는 일시 상환 방식 선택 가능<br>
                                ※ 상환 방식은 고객의 상황에 따라 조정 가능
                            </td>
                        </tr>
                        <tr>
                            <th>대출 신청 방법</th>
                            <td>온라인 및 오프라인 모두 신청 가능<br>
                                - 온라인 신청은 웹사이트 또는 앱 통해 가능<br>
                                - 서류 제출 필요: 사업자등록증, 소득증빙서류 등
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <script>
            function toggleDetails(button) {
                const details = button.nextElementSibling;
                if (details.style.display === "none") {
                    details.style.display = "block";
                } else {
                    details.style.display = "none";
                }
            }
        </script>
</body>

</html>