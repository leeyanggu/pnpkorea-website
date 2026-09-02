// 문의 폼 비동기 전송 (contact.html)
document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("contactForm");
  if (!form) return;

  const statusEl = document.getElementById("formStatus");
  const submitBtn = form.querySelector('button[type="submit"]');
  const defaultLabel = submitBtn.textContent;

  const setStatus = (msg, kind) => {
    statusEl.textContent = msg;
    statusEl.className = "form-status" + (kind ? " is-" + kind : "");
  };

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    setStatus("");

    // 기본 검증
    if (!form.checkValidity()) {
      setStatus("필수 항목을 모두 입력해 주세요.", "error");
      form.reportValidity();
      return;
    }

    submitBtn.disabled = true;
    submitBtn.textContent = "전송 중…";

    try {
      const res = await fetch(form.action, {
        method: "POST",
        body: new FormData(form),
        headers: { "X-Requested-With": "fetch" },
      });
      const data = await res
        .json()
        .catch(() => ({ ok: false, message: "서버 응답을 처리하지 못했습니다." }));

      if (res.ok && data.ok) {
        form.reset();
        setStatus(data.message || "문의가 접수되었습니다. 빠르게 연락드리겠습니다.", "success");
      } else {
        setStatus(data.message || "전송에 실패했습니다. 잠시 후 다시 시도해 주세요.", "error");
      }
    } catch (err) {
      setStatus("네트워크 오류로 전송하지 못했습니다. 전화(031-692-7751)로 문의해 주세요.", "error");
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = defaultLabel;
    }
  });
});
