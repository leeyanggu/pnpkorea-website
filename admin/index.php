<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
require_admin();

/* ── 필터 ── */
$brand  = in_array($_GET['brand'] ?? '', ['pnp', 'greencell'], true) ? $_GET['brand'] : '';
$status = in_array($_GET['status'] ?? '', ['new', 'progress', 'done'], true) ? $_GET['status'] : '';
$q      = trim((string) ($_GET['q'] ?? ''));
$page   = max(1, (int) ($_GET['page'] ?? 1));
$per    = 20;

$where  = [];
$params = [];
if ($brand !== '')  { $where[] = 'brand = ?';  $params[] = $brand; }
if ($status !== '') { $where[] = 'status = ?'; $params[] = $status; }
if ($q !== '') {
    $where[] = '(name LIKE ? OR company LIKE ? OR email LIKE ? OR phone LIKE ? OR message LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like, $like);
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

/* ── 개수 ── */
$stmtC = db()->prepare("SELECT COUNT(*) c FROM inquiries $whereSql");
$stmtC->execute($params);
$total = (int) $stmtC->fetchColumn();
$pages = max(1, (int) ceil($total / $per));
$page  = min($page, $pages);
$offset = ($page - 1) * $per;

/* 상태별 전체 카운트(필터 무시) */
$counts = ['all' => 0, 'new' => 0, 'progress' => 0, 'done' => 0];
foreach (db()->query("SELECT status, COUNT(*) c FROM inquiries GROUP BY status") as $r) {
    $counts[$r['status']] = (int) $r['c'];
    $counts['all'] += (int) $r['c'];
}

/* ── 목록 ── */
$sql = "SELECT * FROM inquiries $whereSql ORDER BY id DESC LIMIT $per OFFSET $offset";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

function qs(array $over = []): string
{
    return '?' . http_build_query(array_merge([
        'brand'  => $_GET['brand']  ?? '',
        'status' => $_GET['status'] ?? '',
        'q'      => $_GET['q']      ?? '',
        'page'   => $_GET['page']   ?? 1,
    ], $over));
}

h_layout_top('문의 목록');
?>
<div class="adm-toolbar">
  <div class="adm-tabs">
    <a href="<?= e(qs(['status' => '', 'page' => 1])) ?>" class="<?= $status === '' ? 'on' : '' ?>">전체 <b><?= $counts['all'] ?></b></a>
    <a href="<?= e(qs(['status' => 'new', 'page' => 1])) ?>" class="<?= $status === 'new' ? 'on' : '' ?>">미처리 <b><?= $counts['new'] ?></b></a>
    <a href="<?= e(qs(['status' => 'progress', 'page' => 1])) ?>" class="<?= $status === 'progress' ? 'on' : '' ?>">처리중 <b><?= $counts['progress'] ?></b></a>
    <a href="<?= e(qs(['status' => 'done', 'page' => 1])) ?>" class="<?= $status === 'done' ? 'on' : '' ?>">완료 <b><?= $counts['done'] ?></b></a>
  </div>

  <form class="adm-filter" method="get" action="index.php">
    <input type="hidden" name="status" value="<?= e($status) ?>" />
    <select name="brand">
      <option value="">전체 계열사</option>
      <option value="pnp"       <?= $brand === 'pnp' ? 'selected' : '' ?>>피앤피코리아</option>
      <option value="greencell" <?= $brand === 'greencell' ? 'selected' : '' ?>>그린셀</option>
    </select>
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="회사명·이메일·내용 검색" />
    <button type="submit">검색</button>
    <a class="adm-btn-plain" href="export.php<?= e(qs()) ?>">CSV 내보내기</a>
  </form>
</div>

<p class="adm-count">총 <?= $total ?>건<?= ($brand || $status || $q !== '') ? ' (필터 적용됨)' : '' ?></p>

<table class="adm-table">
  <thead>
    <tr>
      <th>번호</th><th>접수시각</th><th>계열사</th><th>상태</th>
      <th>회사명</th><th>이름</th><th>유형</th><th>연락처</th><th>내용</th>
    </tr>
  </thead>
  <tbody>
  <?php if (!$rows): ?>
    <tr><td colspan="9" class="adm-empty">문의가 없습니다.</td></tr>
  <?php else: foreach ($rows as $r): ?>
    <tr onclick="location.href='view.php?id=<?= (int) $r['id'] ?>'">
      <td><?= (int) $r['id'] ?></td>
      <td><?= e(substr($r['created_at'], 0, 16)) ?></td>
      <td><?= e(brand_label($r['brand'])) ?></td>
      <td><span class="adm-badge s-<?= e($r['status']) ?>"><?= e(status_label($r['status'])) ?></span></td>
      <td><?= e($r['company']) ?></td>
      <td><?= e($r['name']) ?></td>
      <td><?= e($r['type']) ?></td>
      <td><?= e($r['phone']) ?></td>
      <td class="adm-preview"><?= e(mb_strimwidth(preg_replace('/\s+/u', ' ', $r['message']), 0, 46, '…')) ?></td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table>

<?php if ($pages > 1): ?>
<nav class="adm-pager">
  <?php for ($i = 1; $i <= $pages; $i++): ?>
    <a href="<?= e(qs(['page' => $i])) ?>" class="<?= $i === $page ? 'on' : '' ?>"><?= $i ?></a>
  <?php endfor; ?>
</nav>
<?php endif; ?>
<?php
h_layout_bottom();
