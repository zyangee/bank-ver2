let add_auth_code = "";
let authCodeExpireTime;
let timerInterval;
//인증번호 생성 함수
function addAuthCode() {
  const EXPIRE_TIME = 180;
  const chars =
    "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
  const authentication_code = document.getElementById("authentication-code");
  const auth_code = Array.from(crypto.getRandomValues(new Uint32Array(6)))
    .map((n) => chars[n % chars.length])
    .join("");

  add_auth_code = auth_code; //생성된 인증번호 저장
  authCodeExpireTime = Date.now() + EXPIRE_TIME * 1000;
  document.getElementById("authentication").style.display = "block";
  authentication_code.textContent =
    "인증번호가 발급되었습니다: " + add_auth_code;
  document.getElementById("memo").style.display = "none";

  //타이머 시작
  startTimer(EXPIRE_TIME);
}
function startTimer(duration) {
  if (timerInterval) {
    clearInterval(timerInterval);
  }
  const timerDisplay = document.getElementById("auth-timer");
  let timer = duration;

  timerInterval = setInterval(() => {
    const minutes = Math.floor(timer / 60);
    const seconds = timer % 60;
    timerDisplay.textContent = `남은시간: ${minutes}:${
      seconds < 10 ? "0" : ""
    }${seconds}`;

    if (--timer < 0) {
      clearInterval(timerInterval);
      add_auth_code = "";
      timerDisplay.textContent = "인증시간이 만료되었습니다.";
      document.getElementById("create-account").disabled = true;
    }
  }, 1000);
}

//인증번호 검증 함수
function validAuthCode() {
  const auth_code = document.getElementById("auth-code").value.trim();
  const error = document.getElementById("auth-error");

  //인증번호가 입력되지 않았을 경우
  if (!auth_code) {
    error.textContent = "인증번호를 입력해주세요.";
    error.style.color = "red";
    return false;
  }
  if (Date.now() > authCodeExpireTime) {
    error.textContent = "인증번호가 만료되었습니다. 재발급해주세요.";
    error.style.color = "red";
    return false;
  }
  //인증번호가 맞지 않을 경우
  if (auth_code === add_auth_code) {
    error.textContent = "인증이 완료되었습니다.";
    error.style.color = "green";
    document.getElementById("create-account").disabled = false;
    return true;
  } else {
    error.textContent = "인증번호가 올바르지 않습니다. 다시 시도해주세요.";
    error.style.color = "red";
    addAuthCode();
    return false;
  }
}

//주민번호 뒷자리 7자리 검증
function validResident() {
  const resident_number2 = document.getElementById("resident-number2");
  const error = document.getElementById("resident-error");

  if (resident_number2.hasAttribute("readonly")) {
    error.textContent = "";
    return true;
  }

  const resident = /^\d{7}$/;
  if (!resident.test(resident_number2.value)) {
    error.textContent = "7자리 숫자로 입력해주세요.";
    return false;
  }
  error.textContent = "";
  return true;
}

//초기금액 0원 이상
function validBalance() {
  const balance = document.getElementById("balance").value;
  const error = document.getElementById("balance-error");

  if (isNaN(balance) || balance < 0) {
    error.textContent = "0원 이상 입력해주세요.";
    return false;
  }
  error.textContent = "";
  return true;
}

//통장비밀번호 숫자 4자리만 허용
function validPassword() {
  const account_password = document.getElementById("account-password").value;
  const error = document.getElementById("password-error");

  if (!/^\d{4}$/.test(account_password)) {
    error.textContent = "4자리 숫자로 입력해주세요.";
    return false;
  }
  error.textContent = "";
  return true;
}

//select 옵션 검증
function validSelect() {
  const select = document.getElementById("purpose").value;
  const error = document.getElementById("select-error");
  if (!select) {
    error.textContent = "계좌 사용 용도를 선택해주세요.";
    return false;
  }
  error.textContent = "";
  return true;
}

//체크 옵션 검증
function validCheck() {
  const check1 = document.querySelector('input[name="check1"]:checked');
  const check2 = document.querySelector('input[name="check2"]:checked');
  const error = document.getElementById("check-error");

  if (!check1 || !check2) {
    error.textContent = "모두 체크 바랍니다.";
    return false;
  }
  if (check1.value === "예" || check2.value === "예") {
    error.textContent = "한번 더 확인 부탁드립니다.";
    return false;
  }
  error.textContent = "";
  return true;
}

function submitForm(event) {
  event.preventDefault();
  const validations = [
    validAuthCode(),
    validResident(),
    validBalance(),
    validPassword(),
    validSelect(),
    validCheck(),
  ];
  if (validations.every(Boolean)) {
    const formInputs = event.target.getElementsByTagName("input");
    for (let input of formInputs) {
      if (input.type !== "submit" && input.type !== "radio") {
        input.value = escapeHtml(input.value);
      }
    }
    event.target.submit();
  } else {
    alert("모든 필수 항목을 올바르게 입력해주세요.");
  }
}

function escapeHtml(unsafe) {
  return unsafe
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

document.addEventListener("DOMContentLoaded", function () {
  document
    .getElementById("resident-number2")
    .addEventListener("input", validResident);
  document.getElementById("balance").addEventListener("input", validBalance);
  document
    .getElementById("account-password")
    .addEventListener("input", validPassword);
  document.getElementById("purpose").addEventListener("change", validSelect);
});
