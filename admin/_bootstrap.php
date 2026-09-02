<?php
/* ============================================================
   관리자 공용: 세션 · 인증 · CSRF · 헬퍼
   ============================================================ */
declare(strict_types=1);

require __DIR__ . '/../db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('pnpadmin');
    session_start();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(419);
        exit('세션이 만료되었습니다. 새로고침 후 다시 시도해 주세요.');
    }
}

function admin_logged_in(): bool
{
    return !empty($_SESSION['admin']) && ($_SESSION['admin_expire'] ?? 0) > time();
}

function require_admin(): void
{
    if (!admin_logged_in()) {
        redirect('login.php');
    }
    // 활동 시 만료 연장
    $_SESSION['admin_expire'] = time() + (int) (cfg()['admin']['session_ttl'] ?? 28800);
}

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

function h_layout_top(string $title): void
{
    ?><!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="robots" content="noindex, nofollow" />
<title><?= e($title) ?> · 문의 관리</title>
<link rel="stylesheet" href="../css/admin.css" />
</head>
<body>
<?php if (admin_logged_in()): ?>
<header class="adm-header">
  <a class="adm-brand" href="index.php">문의 관리</a>
  <nav class="adm-nav">
    <a href="index.php">목록</a>
    <a href="logout.php">로그아웃</a>
  </nav>
</header>
<?php endif; ?>
<main class="adm-main">
<?php
}

function h_layout_bottom(): void
{
    ?>
</main>
</body>
</html>
<?php
}
