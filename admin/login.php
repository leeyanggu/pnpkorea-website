<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

if (admin_logged_in()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    usleep(300000); // 무차별 대입 완화
    $pw   = (string) ($_POST['password'] ?? '');
    $hash = (string) (cfg()['admin']['password_hash'] ?? '');

    if ($hash !== '' && password_verify($pw, $hash)) {
        session_regenerate_id(true);
        $_SESSION['admin']        = true;
        $_SESSION['admin_expire'] = time() + (int) (cfg()['admin']['session_ttl'] ?? 28800);
        redirect('index.php');
    }
    $error = '비밀번호가 올바르지 않습니다.';
}

h_layout_top('로그인');
?>
<div class="adm-login">
  <h1>문의 관리</h1>
  <?php if ($error): ?><p class="adm-alert"><?= e($error) ?></p><?php endif; ?>
  <form method="post" action="login.php">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
    <label for="pw">관리자 비밀번호</label>
    <input type="password" id="pw" name="password" autocomplete="current-password" autofocus required />
    <button type="submit">로그인</button>
  </form>
</div>
<?php
h_layout_bottom();
