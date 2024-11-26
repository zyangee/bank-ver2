let isIdChecked = false;
//ID중복체크
async function checkUserID() {
  const userid = document.getElementById("userid").value;
  const feedback = document.getElementById("useridFeedback");
  const csrfToken = document.querySelector('input[name="csrf_token"]').value;

  if (!userid || userid.trim() === "") {
    feedback.textContent = "아이디를 입력해주세요.";
    feedback.style.color = "red";
    return;
  }
  if (!/^[A-Za-z0-9]{4,20}$/.test(userid)) {
    feedback.textContent = "아이디는 4-20자의 영문, 숫자만 사용 가능합니다.";
    feedback.style.color = "red";
    return;
  }

  try {
    const formData = new FormData();
    formData.append("userid", userid);
    formData.append("csrf_token", csrfToken);

    const response = await fetch("../api/check_userid.php", {
      method: "POST",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
      credentials: "same-origin",
      body: formData,
    });

    let data;
    try {
      data = await response.json();
    } catch (e) {
      console.error("JSON Parse Error: ", e);
      throw new Error("서버 응답을 처리할 수 없습니다.");
    }

    if (!response.ok) {
      throw new Error(data.message || `서버오류 ${response.status}`);
    }

    if (data.error) {
      feedback.textContent =
        data.message || "중복 확인 중 오류가 발생했습니다.";
      feedback.style.color = "red";
      isIdChecked = false;
    } else {
      feedback.textContent = data.message;
      feedback.style.color = data.exists ? "red" : "green";
      isIdChecked = !data.exists;
    }
  } catch (error) {
    console.error("Error: ", error);
    feedback.textContent = error.message || "중복 확인 중 오류가 발생했습니다.";
    feedback.style.color = "red";
    isIdChecked = false;
  }
}

//회원가입 유효성 검사
function signupCheck() {
  const form = document.getElementById("signForm");
  const userid = document.getElementById("userid").value;
  const username = document.getElementById("username").value;
  const password = document.getElementById("password").value;
  const email = document.getElementById("email").value;
  const phone1 = document.getElementById("phone1").value;
  const phone2 = document.getElementById("phone2").value;
  const phone3 = document.getElementById("phone3").value;
  const birth = document.getElementById("birth").value;

  //오류 메시지 요소
  const idError = document.getElementById("idError");
  const nameError = document.getElementById("nameError");
  const passError = document.getElementById("passError");
  const emailError = document.getElementById("emailError");
  const phoneError = document.getElementById("phoneError");
  const birthError = document.getElementById("birthError");

  //오류 메시지 초기화
  idError.textContent = "";
  nameError.textContent = "";
  passError.textContent = "";
  emailError.textContent = "";
  phoneError.textContent = "";
  birthError.textContent = "";

  //ID중복 체크 확인
  if (!isIdChecked) {
    idError.textContent = "아이디 중복 확인을 해주세요.";
    return false;
  }

  //유효성 검사
  if (!validateUserID(userid)) {
    idError.textContent = "아이디는 4-20자 영문, 숫자만 사용 가능합니다.";
    return false;
  }
  if (!validateUsername(username)) {
    nameError.textContent = "이름은 2-10자의 한글 또는 영문만 사용 가능합니다.";
    return false;
  }
  if (!validatePassword(password)) {
    passError.textContent =
      "비밀번호는 8자 이상이며, 영문, 숫자, 특수문자를 포함해야 합니다.";
    return false;
  }
  if (!validateEmail(email)) {
    emailError.textContent = "유효한 이메일 주소를 입력해주세요.";
    return false;
  }
  if (
    !validatePhone(phone1) ||
    !validatePhone(phone2) ||
    !validatePhone(phone3)
  ) {
    phoneError.textContent = "올바른 전화번호를 입력해주세요.";
    return false;
  }
  if (!validateBirth(birth)) {
    birthError.textContent =
      "올바른 생년월일 6자리를 입력해주세요. (예: 000222)";
    return false;
  }
  //모든 검증 통과 시 폼 제출
  form.onsubmit = null;
  form.submit();
  return true;
}

//입력값 검증 함수들
const validateUserID = (id) => /^[A-Za-z0-9]{4,20}$/.test(id);
const validateUsername = (name) => /^[가-힣A-Za-z]{2,10}$/.test(name);
const validatePassword = (password) => {
  return (
    password.length >= 8 &&
    /[A-Za-z]/.test(password) &&
    /[0-9]/.test(password) &&
    /[!@#$%^&*(),.?":{}|<>]/.test(password)
  );
};
const validateEmail = (email) => {
  const re = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
  return re.test(email);
};
const validatePhone = (phone) => /^\d+$/.test(phone);
const validateBirth = (birth) => {
  //6자리 숫자인지 확인
  if (!/^\d{6}$/.test(birth)) {
    return false;
  }
  const year = parseInt(birth.substring(0, 2));
  const month = parseInt(birth.substring(2, 4));
  const day = parseInt(birth.substring(4, 6));

  //월이 1-12인지 확인
  if (month < 1 || month > 12) {
    return false;
  }
  //일이 1-31인지 확인
  if (day < 1 || day > 31) {
    return false;
  }
  return true;
};
