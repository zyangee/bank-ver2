let isIdChecked = false;
//ID중복체크
async function checkUserID() {
  const userid = document.getElementById("userid").value;
  const feedback = document.getElementById("useridFeedback");

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
    const response = await fetch("../api/check_userid.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      credentials: "same-origin",
      body: JSON.stringify({
        userid: userid,
        csrf_token: document.querySelector('input[name="csrf_token"]').value,
      }),
    });

    if (!response.ok) {
      throw new Error("서버 응답 오류");
    }
    const data = await response.json();
    if (data.exists) {
      feedback.textContent = "이미 사용중인 아이디입니다.";
      feedback.style.color = "red";
      isIdChecked = false;
    } else {
      feedback.textContent = "사용 가능한 아이디입니다.";
      feedback.style.color = "green";
      isIdChecked = true;
    }
  } catch (error) {
    console.error("Error: ", error);
    feedback.textContent = "중복 확인 중 오류가 발생했습니다.";
    feedback.style.color = "red";
  }
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
  const re = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]}\.[a-zA-Z]{2,}$/;
  return re.test(email);
};
const validatePhone = (phone) => /^\d+$/.test(phone);
const validateBirth = (birth) => /^d{6}$/.test(birth);
