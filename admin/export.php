<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
require_admin();

/* index.php 와 동일한 필터 */
$brand  = in_array($_GET['brand'] ?? '', ['pnp', 'greencell'], true) ? $_GET['brand'] : '';
$status = in_array($_GET['status'] ?? '', ['new', 'progress', 'done'], true) ? $_GET['status'] : '';
$q      = trim((string) ($_GET['q'] ?? ''));

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

$stmt = db()->prepare("SELECT * FROM inquiries $whereSql ORDER BY id DESC");
$stmt->execute($params);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="inquiries_' . date('Ymd_His') . '.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // Excel 한글 대응 BOM

fputcsv($out, ['번호', '접수시각', '계열사', '상태', '이름', '회사명', '연락처', '이메일', '문의유형', '내용', '담당자메모', 'IP']);
foreach ($stmt as $r) {
    fputcsv($out, [
        $r['id'],
        $r['created_at'],
        brand_label($r['brand']),
        status_label($r['status']),
        $r['name'],
        $r['company'],
        $r['phone'],
        $r['email'],
        $r['type'],
        preg_replace('/\s+/u', ' ', (string) $r['message']),
        preg_replace('/\s+/u', ' ', (string) ($r['admin_memo'] ?? '')),
        $r['ip'],
    ]);
}
fclose($out);
