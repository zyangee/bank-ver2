function nextPhone1() {
  const phone1 = document.getElementById("phone1").value;
  if (phone1.length === 3) {
    document.getElementById("phone2").focus();
  }
}
function nextPhone2() {
  const phone2 = document.getElementById("phone2").value;
  if (phone2.length === 4) {
    document.getElementById("phone3").focus();
  }
}

let isIdChecked = false;
//ID중복체크
function checkUserID() {
  const userid = document.getElementById("userid").value;
  const feedback = document.getElementById("useridFeedback");

  if (userid.trim() === "") {
    feedback.textContent = "아이디를 입력해주세요.";
    return;
  }
  fetch("../api/check_userid.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ userid: userid }),
  })
    .then((response) => response.text())
    .then((text) => {
      console.log(text);
      const data = JSON.parse(text);
      if (data.exists) {
        feedback.textContent = "이미 사용 중인 아이디입니다.";
        feedback.style.color = "red";
        isIdChecked = false;
      } else {
        feedback.textContent = "사용 가능한 아이디입니다.";
        feedback.style.color = "green";
        isIdChecked = true;
      }
    })
    .catch((error) => {
      console.error("Error:", error);
    });
}

//회원가입 버튼 클릭 시 입력값 검증
function signupCheck() {
  let id = document.getElementById("userid").value;
  let name = document.getElementById("username").value;
  let password = document.getElementById("password").value;
  let email = document.getElementById("email").value;
  let birth = document.getElementById("birth").value;
  // let phone1 = document.getElementById("phone1").value;
  let check = true;

  //아이디확인
  if (id.trim() === "") {
    document.getElementById("idError").innerHTML = "아이디를 입력해주세요.";
    check = false;
  } else if (id.includes(" ")) {
    document.getElementById("idError").innerHTML =
      "아이디를 다시 입력해주세요.";
    check = false;
  } else {
    document.getElementById("idError").innerHTML = "";
  }
  if (!isIdChecked) {
    document.getElementById("idError").innerHTML =
      "아이디 중복체크를 해주세요.";
    check = false;
  } else {
    document.getElementById("idError").innerHTML = "";
  }

  //이름확인
  if (name.includes(" ")) {
    document.getElementById("nameError").innerHTML =
      "공백이 포함되어 있습니다. 공백을 제거해주세요.";
    check = false;
  } else if (name === "") {
    document.getElementById("nameError").innerHTML =
      "이름이 올바르지 않습니다. 다시 입력해주세요.";
    check = false;
  } else {
    document.getElementById("nameError").innerHTML = "";
  }

  //비밀번호확인
  let hasLetter = /[a-zA-Z]/.test(password); //영문 확인(T/F 반환)
  let hasNumber = /[0-9]/.test(password); //숫자 확인(T/F 반환)
  let hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password); //특수문자 확인(T/F 반환)
  if (!hasLetter) {
    document.getElementById("passError").innerHTML =
      "비밀번호에 영문을 포함해주세요.";
    check = false;
  } else if (!hasNumber) {
    document.getElementById("passError").innerHTML =
      "비밀번호에 숫자를 포함해주세요.";
    check = false;
  } else if (!hasSpecial) {
    document.getElementById("passError").innerHTML =
      "비밀번호에 특수문자를 포함해주세요.";
    check = false;
  } else if (password.length < 8) {
    document.getElementById("passError").innerHTML =
      "비밀번호를 8자리 이상 입력해주세요.";
    check = false;
  } else {
    document.getElementById("passError").innerHTML = "";
  }

  //이메일확인
  if (email.includes("@")) {
    let emailID = email.split("@")[0];
    let emailServer = email.split("@")[1];
    if (emailID === "" || emailServer === "") {
      document.getElementById("emailError").innerHTML =
        "이메일을 알맞은 형식으로 입력해주세요.";
      check = false;
    } else if (!emailServer.includes(".")) {
      document.getElementById("emailError").innerHTML =
        "이메일을 알맞은 형식으로 입력해주세요.";
      check = false;
    } else {
      document.getElementById("emailError").innerHTML = "";
    }
  } else {
    document.getElementById("emailError").innerHTML =
      "이메일을 다시 입력해주세요.";
    check = false;
  }

  //생년월일확인
  let birthNumber = /[0-9]/.test(birth);
  if (!birthNumber) {
    document.getElementById("birthError").innerHTML =
      "주민번호 앞 6자리를 숫자로 입력해주세요.";
    check = false;
  } else {
    document.getElementById("birthError").innerHTML = "";
  }

  //모든 값 true일 경우
  if (check) {
    document.getElementById("idError").innerHTML = "";
    document.getElementById("nameError").innerHTML = "";
    document.getElementById("passError").innerHTML = "";
    document.getElementById("emailError").innerHTML = "";
    document.getElementById("birthError").innerHTML = "";
    document.getElementById("signForm").submit();

    setTimeout(function () {
      alert("가입이 완료되었습니다.");
    }, 0);
  }
}
