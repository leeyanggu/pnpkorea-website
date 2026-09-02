<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);

/* ── 액션 처리 ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'status') {
        $new = in_array($_POST['status'] ?? '', ['new', 'progress', 'done'], true) ? $_POST['status'] : 'new';
        $stmt = db()->prepare('UPDATE inquiries SET status = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$new, $id]);
        redirect('view.php?id=' . $id);
    }

    if ($action === 'memo') {
        $memo = mb_substr(trim((string) ($_POST['memo'] ?? '')), 0, 5000);
        $stmt = db()->prepare('UPDATE inquiries SET admin_memo = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$memo, $id]);
        redirect('view.php?id=' . $id);
    }

    if ($action === 'delete') {
        db()->prepare('DELETE FROM inquiries WHERE id = ?')->execute([$id]);
        redirect('index.php');
    }
}

$stmt = db()->prepare('SELECT * FROM inquiries WHERE id = ?');
$stmt->execute([$id]);
$r = $stmt->fetch();

if (!$r) {
    h_layout_top('없는 문의');
    echo '<p class="adm-alert">해당 문의를 찾을 수 없습니다.</p><p><a href="index.php">목록으로</a></p>';
    h_layout_bottom();
    exit;
}

h_layout_top('문의 #' . $r['id']);
?>
<p class="adm-back"><a href="index.php">&larr; 목록</a></p>

<div class="adm-detail">
  <div class="adm-detail-head">
    <span class="adm-badge s-<?= e($r['status']) ?>"><?= e(status_label($r['status'])) ?></span>
    <span class="adm-brandtag"><?= e(brand_label($r['brand'])) ?></span>
    <span class="adm-date"><?= e($r['created_at']) ?></span>
  </div>

  <h1><?= e($r['type']) ?> &mdash; <?= e($r['name']) ?></h1>

  <table class="adm-kv">
    <tr><th>회사명</th><td><?= e($r['company']) ?: '<span class="muted">-</span>' ?></td></tr>
    <tr><th>연락처</th><td><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $r['phone'])) ?>"><?= e($r['phone']) ?></a></td></tr>
    <tr><th>이메일</th><td><a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a></td></tr>
  </table>

  <div class="adm-message"><?= nl2br(e($r['message'])) ?></div>

  <p class="adm-meta">IP <?= e($r['ip']) ?><?= $r['updated_at'] ? ' · 최종수정 ' . e($r['updated_at']) : '' ?></p>
</div>

<div class="adm-actions">
  <form method="post" action="view.php?id=<?= (int) $r['id'] ?>" class="adm-status-form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
    <input type="hidden" name="action" value="status" />
    <label>상태
      <select name="status" onchange="this.form.submit()">
        <option value="new"      <?= $r['status'] === 'new' ? 'selected' : '' ?>>미처리</option>
        <option value="progress" <?= $r['status'] === 'progress' ? 'selected' : '' ?>>처리중</option>
        <option value="done"     <?= $r['status'] === 'done' ? 'selected' : '' ?>>완료</option>
      </select>
    </label>
    <noscript><button type="submit">변경</button></noscript>
  </form>

  <form method="post" action="view.php?id=<?= (int) $r['id'] ?>" class="adm-memo-form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
    <input type="hidden" name="action" value="memo" />
    <label for="memo">담당자 메모</label>
    <textarea id="memo" name="memo" rows="4"><?= e($r['admin_memo'] ?? '') ?></textarea>
    <button type="submit">메모 저장</button>
  </form>

  <form method="post" action="view.php?id=<?= (int) $r['id'] ?>" class="adm-delete-form"
        onsubmit="return confirm('이 문의를 삭제할까요? 되돌릴 수 없습니다.');">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
    <input type="hidden" name="action" value="delete" />
    <button type="submit" class="adm-danger">삭제</button>
  </form>
</div>

<h2 class="adm-h2">같은 이메일의 이전 문의</h2>
<?php
$hist = db()->prepare('SELECT id, created_at, brand, type, status FROM inquiries WHERE email = ? AND id <> ? ORDER BY id DESC LIMIT 20');
$hist->execute([$r['email'], $r['id']]);
$hrows = $hist->fetchAll();
?>
<?php if (!$hrows): ?>
  <p class="muted">없음</p>
<?php else: ?>
<table class="adm-table">
  <thead><tr><th>번호</th><th>접수시각</th><th>계열사</th><th>유형</th><th>상태</th></tr></thead>
  <tbody>
  <?php foreach ($hrows as $h): ?>
    <tr onclick="location.href='view.php?id=<?= (int) $h['id'] ?>'">
      <td><?= (int) $h['id'] ?></td>
      <td><?= e(substr($h['created_at'], 0, 16)) ?></td>
      <td><?= e(brand_label($h['brand'])) ?></td>
      <td><?= e($h['type']) ?></td>
      <td><span class="adm-badge s-<?= e($h['status']) ?>"><?= e(status_label($h['status'])) ?></span></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
<?php
h_layout_bottom();
