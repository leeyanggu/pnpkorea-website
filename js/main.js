// 모바일 내비게이션 토글 + 헤더 스크롤 상태 + 스크롤 리빌 애니메이션 + 연도 표시
document.addEventListener("DOMContentLoaded", () => {
  const navToggle = document.getElementById("navToggle");
  const nav = document.getElementById("primaryNav");
  const header = document.getElementById("siteHeader");

  // 모바일 메뉴
  if (navToggle && nav) {
    navToggle.addEventListener("click", () => {
      const isOpen = nav.classList.toggle("is-open");
      navToggle.classList.toggle("is-open", isOpen);
      navToggle.setAttribute("aria-expanded", String(isOpen));
    });

    nav.querySelectorAll("a").forEach((link) => {
      link.addEventListener("click", () => {
        nav.classList.remove("is-open");
        navToggle.classList.remove("is-open");
        navToggle.setAttribute("aria-expanded", "false");
      });
    });
  }

  // 헤더 배경.
  // - 전체 화면 히어로(.hero)가 있는 페이지(메인·그린셀): 상단은 투명, 스크롤하면 배경 표시.
  // - 서브 페이지(.page-hero): HTML의 is-scrolled 를 그대로 두어 항상 배경 유지.
  //   (JS가 로드 시 is-scrolled 를 껐다 켜면서 배경이 깜빡이던 문제 방지)
  if (header && document.querySelector(".hero")) {
    const toggleHeaderState = () => {
      header.classList.toggle("is-scrolled", window.scrollY > 40);
    };
    toggleHeaderState();
    window.addEventListener("scroll", toggleHeaderState, { passive: true });
  }

  // 스크롤 리빌 애니메이션
  const revealEls = document.querySelectorAll(".reveal");
  if (revealEls.length && "IntersectionObserver" in window) {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.15, rootMargin: "0px 0px -60px 0px" }
    );
    revealEls.forEach((el) => observer.observe(el));
  } else {
    revealEls.forEach((el) => el.classList.add("is-visible"));
  }

  // 연도 표시
  const yearEl = document.getElementById("year");
  if (yearEl) {
    yearEl.textContent = new Date().getFullYear();
  }
});
