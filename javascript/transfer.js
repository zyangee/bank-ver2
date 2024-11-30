//입력값 검증 함수들
const validateAccountNumber = (accountNumber) => {
  const regex = /^[0-9]{3}-[0-9]{4}$/;
  return regex.test(accountNumber);
};

const validateAmount = (amount) => {
  return amount > 0 && amount <= 100000000;
};
const validatePassword = (password) => {
  const regex = /^[0-9]{4}$/;
  return regex.test(password);
};

let currentBalance = 0;

//잔액 조회 함수
async function myAccount() {
  const accountNumber = document.getElementById("out_account").value;
  if (!accountNumber) {
    alert("계좌를 선택해주세요.");
    return;
  }

  //CSRF 토큰 가져오기
  const csrfToken = document.querySelector('input[name="csrf_token"]').value;

  try {
    //response
    const response = await fetch(`../api/check_account.php`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
        "X-CSRF-Token": csrfToken,
      },
      credentials: "include",
      body: JSON.stringify({
        account_number: accountNumber,
      }),
    });

    //data
    const data = await response.json();

    if (response.status === 401) {
      window.location.href = data.redirect || "../login/login.php";
      return;
    }

    if (!response.ok) {
      throw new Error(data.error || "서버 응답 오류가 발생했습니다.");
    }
    if (!data.success) {
      throw new Error(data.error || "잔액 조회 중 오류가 발생했습니다.");
    }

    if (data.success && data.balance !== undefined) {
      currentBalance = data.balance;
      const balanceElement = document.getElementById("balance");
      balanceElement.textContent = `잔액: ${Number(
        data.balance
      ).toLocaleString()}원`;
    } else {
      throw new Error("잔액 정보를 가져올 수 없습니다.");
    }
  } catch (error) {
    console.error("Error: ", error);
    alert(error.message || "잔액 조회 중 오류가 발생했습니다.");
    const balanceElement = document.getElementById("balance");
    balanceElement.textContent =
      "잔액 정보를 가져오는 중 오류가 발생하였습니다.";
  }
}

//이체 실행 함수
async function transferSubmit(event) {
  event.preventDefault();

  const accountNumber = document.getElementById("out_account").value; //출금 계좌
  const accountNumber_in = document.getElementById("in_account").value; //입금 계좌
  const transferAmount = parseFloat(
    document.getElementById("transfer_amount").value
  );
  const accountPassword = document.getElementById("input_password").value;
  const csrfToken = document.querySelector('input[name="csrf_token"]').value;

  //입력값 검증
  if (
    !validateAccountNumber(accountNumber) ||
    !validateAccountNumber(accountNumber_in)
  ) {
    alert("올바른 계좌번호 형식이 아닙니다.");
    return false;
  }
  if (!validateAmount(transferAmount)) {
    alert("올바른 이체 금액이 아닙니다.");
    return false;
  }
  if (!validatePassword(accountPassword)) {
    alert("올바른 비밀번호 형식이 아닙니다.");
    return false;
  }

  //잔액 검증
  if (transferAmount > currentBalance) {
    alert("잔액이 부족합니다.");
    return false;
  }

  try {
    const response = await fetch("../api/transfer_account.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
        "X-CSRF-Token": csrfToken,
      },
      credentials: "include",
      body: JSON.stringify({
        accountNumber,
        accountNumber_in,
        transfer_amount: transferAmount,
        accountPassword,
      }),
    });

    if (response.status === 401) {
      window.location.href = "../login/login.php";
      return;
    }

    if (!response.ok) {
      throw new Error("서버 응답 오류가 발생했습니다.");
    }

    const data = await response.json();
    if (data.success) {
      alert("이체가 완료되었습니다.");
      window.location.href = "../index.php";
    } else {
      throw new Error(data.message || "이체 처리 중 오류가 발생했습니다.");
    }
  } catch (error) {
    console.error("Error: ", error);
    alert(error.message || "이체 처리 중 오류가 발생했습니다.");
  }
  return false;
}

function updateBalance(balance) {
  const balanceElement = document.getElementById("balance");
  if (balanceElement) {
    balanceElement.textContent = balance;
  }
}
