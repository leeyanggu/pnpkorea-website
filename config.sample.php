<?php
/* ============================================================
   설정 파일 템플릿

   1) 이 파일을 config.php 로 복사하세요.
      (config.php 는 git 에 올라가지 않습니다)
   2) 아래 값을 실제 환경에 맞게 채우세요.
   3) 관리자 비밀번호 해시 만들기 (터미널):
        php -r "echo password_hash('원하는비밀번호', PASSWORD_DEFAULT), PHP_EOL;"
      출력된 문자열을 admin.password_hash 에 붙여넣기.
   ============================================================ */

return [

    /* ── 데이터베이스 ── */
    'db' => [
        'host'    => 'localhost',
        'name'    => 'pnpkorea',       // 카페24: 신청한 DB 이름
        'user'    => 'root',           // 카페24: DB 아이디
        'pass'    => '',               // 카페24: DB 비밀번호
        'charset' => 'utf8mb4',
    ],

    /* ── 관리자 (/admin) ── */
    'admin' => [
        // password_hash() 로 생성한 해시 (위 설명 참고)
        'password_hash' => '$2y$10$xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        'session_ttl'   => 28800,      // 로그인 유지(초) — 8시간
    ],

    /* ── 문의 접수 시 알림 메일 (선택) ── */
    'mail' => [
        'enabled' => false,            // true 로 바꾸면 문의가 올 때 알림 메일 발송

        // 계열사별 수신 주소
        'to' => [
            'pnp'       => 'pnp7751@naver.com',
            'greencell' => 'pnp7751@naver.com',
        ],
        'from' => 'pnp7751@naver.com',

        // SMTP (PHPMailer 사용 시) — vendor/ 에 PHPMailer 를 올린 경우에만 동작
        'smtp' => [
            'host'   => 'smtp.naver.com',
            'port'   => 465,
            'secure' => 'ssl',
            'user'   => 'pnp7751@naver.com',
            'pass'   => '',            // 네이버 메일 → 환경설정 → POP3/SMTP 사용
        ],
    ],
];
