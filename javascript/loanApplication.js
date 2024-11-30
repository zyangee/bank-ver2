//API 통신 설정
const API_URL = "../api/API_application.php";

//전역변수로 데이터 저장
let globalData = null;

//초기 데이터 로드 함수
async function loadInitialData() {
  try {
    const response = await fetch(API_URL);
    const data = await response.json();

    if (data.success) {
      globalData = data.data;

      //총 자산 표시
      document.getElementById("totalAssets").value = new Intl.NumberFormat(
        "ko-KR"
      ).format(data.data.totalAssets);
      //초기 이자율 계산
      updateInterestRates();
      //날짜 제한 설정
      setupDateLimits();
    } else {
      alert("데이터 로드 중 오류가 발생했습니다.");
    }
  } catch (error) {
    console.error("Error: ", error);
    alert("서버 통신 중 오류가 발생했습니다.");
  }
}

//날짜 관련 설정
function setupDateLimits() {
  const today = new Date();
  const nextYear = new Date();
  nextYear.setFullYear(today.getFullYear() + 1); // 현재 날짜로부터 1년 후 날짜

  const todayString = today.toISOString().split("T")[0]; // yyyy-mm-dd 형식
  const nextYearString = nextYear.toISOString().split("T")[0]; // 1년 후 날짜

  // 대출 시작일 input의 최소, 최대 날짜 설정
  const loanStartDate = document.getElementById("loanStartDate");
  loanStartDate.setAttribute("min", todayString);
  loanStartDate.setAttribute("max", nextYearString);

  // 대출 시작일 변경 이벤트 리스너
  loanStartDate.addEventListener("change", function () {
    const today = new Date();
    today.setHours(0, 0, 0, 0); // 오늘 날짜의 시간을 초기화
    const selectedDate = new Date(this.value);
    const nextYear = new Date(today);
    nextYear.setFullYear(today.getFullYear() + 1); // 현재 날짜로부터 1년 후

    // 선택된 날짜가 오늘보다 이전이거나 1년 이후일 경우 알림
    if (selectedDate < today || selectedDate > nextYear) {
      alert("대출 시작일은 오늘부터 1년 이내의 날짜만 선택할 수 있습니다.");
      this.value = ""; // 잘못된 날짜를 선택한 경우 초기화
    } else {
      updateEndDate();
    }
  });
}

//만기일 업데이트
function updateEndDate() {
  if (!globalData) return;

  const startDate = new Date(document.getElementById("loanStartDate").value);

  if (!isNaN(startDate.getTime())) {
    startDate.setFullYear(startDate.getFullYear() + globalData.loanDuration);
    document.getElementById("loanEndDate").value = startDate
      .toISOString()
      .split("T")[0];
  }
}

//제출 버튼 토글
function toggleSubmitButton() {
  const checkbox = document.getElementById("confirmationCheckbox");
  document.getElementById("submitButton").disabled = !checkbox.checked;
}

// 이자율 업데이트 함수
function updateInterestRates() {
  if (!globalData) return;

  const loanType = document.getElementById("loanType").value;
  const totalAssets = globalData.totalAssets;
  let interestRate = "N/A";

  if (loanType && loanType !== "default") {
    for (const [limit, rate] of Object.entries(
      globalData.interestRates[loanType]
    )) {
      if (totalAssets >= limit) {
        interestRate = rate;
        break;
      }
    }
  }
  document.getElementById("interestRate").value = interestRate;
}

//대출 신청 제출 처리
async function submitLoanApplication(formData) {
  try {
    const response = await fetch(API_URL, {
      method: "POST",
      body: formData,
    });

    const result = await response.json();

    if (result.success) {
      alert(result.message);
      window.location.href = "../loans/loan_history.php";
    } else {
      alert(result.message);
    }
  } catch (error) {
    console.log("Error: ", error);
    alert("대출 신청 중 오류가 발생했습니다.");
  }
}

//이벤트 리스너 설정
document.addEventListener("DOMContentLoaded", function () {
  //초기 데이터 로드
  loadInitialData();

  //폼 제출 이벤트 처리
  document.querySelector("form").addEventListener("submit", function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    submitLoanApplication(formData);
  });
  //대출 종류 변경 시 이자율 업데이트
  document
    .getElementById("loanType")
    .addEventListener("change", updateInterestRates);
});
