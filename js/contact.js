// 문의 폼 비동기 전송 (contact.html / contact.en.html / greencell-contact*.html)
document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("contactForm");
  if (!form) return;

  const EN = document.documentElement.lang === "en";
  const t = EN
    ? {
        required: "Please fill in all required fields.",
        sending: "Sending…",
        badResponse: "Could not process the server response.",
        success: "Your inquiry has been received. We will contact you shortly.",
        failed: "Submission failed. Please try again in a moment.",
        network:
          "A network error occurred. Please call us at +82-31-692-7751.",
      }
    : {
        required: "필수 항목을 모두 입력해 주세요.",
        sending: "전송 중…",
        badResponse: "서버 응답을 처리하지 못했습니다.",
        success: "문의가 접수되었습니다. 빠르게 연락드리겠습니다.",
        failed: "전송에 실패했습니다. 잠시 후 다시 시도해 주세요.",
        network:
          "네트워크 오류로 전송하지 못했습니다. 전화(031-692-7751)로 문의해 주세요.",
      };

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

    if (!form.checkValidity()) {
      setStatus(t.required, "error");
      form.reportValidity();
      return;
    }

    submitBtn.disabled = true;
    submitBtn.textContent = t.sending;

    try {
      const res = await fetch(form.action, {
        method: "POST",
        body: new FormData(form),
        headers: { "X-Requested-With": "fetch" },
      });
      const data = await res
        .json()
        .catch(() => ({ ok: false, message: t.badResponse }));

      if (res.ok && data.ok) {
        form.reset();
        setStatus(data.message || t.success, "success");
      } else {
        setStatus(data.message || t.failed, "error");
      }
    } catch (err) {
      setStatus(t.network, "error");
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = defaultLabel;
    }
  });
});
