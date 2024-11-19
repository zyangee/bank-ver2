let currentBalance;
//잔액 조회 함수
function myAccount() {
  const accountNumber = document.getElementById("out_account").value;
  if (accountNumber) {
    fetch(`../api/check_account.php?account_number=${accountNumber}`)
      .then((response) => {
        if (!response.ok) {
          console.log("response 오류");
        }
        return response.json(); // JSON으로 직접 변환
      })
      .then((data) => {
        console.log(data); // 데이터 로그 확인
        if (data.balance !== undefined) {
          document.getElementById(
            "balance"
          ).innerHTML = `잔액: ${data.balance}원`;
          console.log("잔액: " + data.balance);
        } else {
          document.getElementById("balance").innerHTML =
            "잔액 정보를 가져올 수 없습니다.";
          console.log("잔액 정보 오류");
        }
      })
      .catch((error) => {
        console.error("Error: ", error);
        document.getElementById("balance").innerHTML =
          "잔액 정보를 가져오는 중 오류가 발생하였습니다.";
      });
  } else {
    console.log("계좌를 선택해주세요.");
  }
}

//이체 실행 함수
function transferSubmit() {
  const account_number_out = document.getElementById("out_account").value; //출금 계좌
  const account_number_in = document.getElementById("in_account").value; //입금 계좌
  const transferAmount = parseFloat(
    document.getElementById("transfer_amount").value
  );
  const account_password = document.getElementById("input_password").value;
  console.log("출금 계좌: ", account_number_out);
  console.log("입금 계좌: ", account_number_in);
  console.log("이체 금액: ", transferAmount);
  console.log("입력된 비밀번호: ", account_password);
  //서버로 이체 요청
  fetch(`../api/transfer_account.php`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      account_number_out: account_number_out,
      account_number_in: account_number_in,
      transfer_amount: transferAmount,
      account_password: account_password,
    }),
  })
    .then((response) => response.json())
    // .then((response) => {
    //   console.log("res", response);
    // })
    .then((data) => {
      console.log("서버응답:", data);
      if (data.success) {
        alert("이체가 완료되었습니다.");
        myAccount(account_number_out);
        window.location.href = "../index.php";
      } else {
        alert("이체에 실패했습니다." + data.message);
      }
    })
    .catch((error) => {
      console.log("오류: ", error);
    });

  return false;
}
