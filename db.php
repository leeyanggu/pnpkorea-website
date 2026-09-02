<?php
/* ============================================================
   공용: 설정 로드 + DB 연결 + 헬퍼
   ============================================================ */
declare(strict_types=1);

/* ── mbstring 폴리필 (없는 서버 대비, UTF-8 안전) ── */
if (!function_exists('mb_strlen')) {
    function mb_strlen($s, $enc = null): int
    {
        $n = preg_match_all('/./us', (string) $s);
        return $n === false ? strlen((string) $s) : $n;
    }
    function mb_substr($s, $start, $length = null, $enc = null): string
    {
        if (preg_match_all('/./us', (string) $s, $m) === false) {
            return $length === null
                ? substr((string) $s, $start)
                : substr((string) $s, $start, $length);
        }
        $sliced = $length === null
            ? array_slice($m[0], $start)
            : array_slice($m[0], $start, $length);
        return implode('', $sliced);
    }
    function mb_strimwidth($s, $start, $width, $marker = '', $enc = null): string
    {
        $chars = mb_substr($s, $start);
        if (mb_strlen($chars) <= $width) {
            return $chars;
        }
        return mb_substr($chars, 0, max(0, $width - mb_strlen($marker))) . $marker;
    }
}

function cfg(): array
{
    static $c = null;
    if ($c === null) {
        $path = __DIR__ . '/config.php';
        if (!is_file($path)) {
            http_response_code(500);
            exit('config.php 가 없습니다. config.sample.php 를 복사해 설정하세요.');
        }
        $c = require $path;
    }
    return $c;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $d = cfg()['db'];
        $dsn = "mysql:host={$d['host']};dbname={$d['name']};charset={$d['charset']}";
        $pdo = new PDO($dsn, $d['user'], $d['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

/* HTML 이스케이프 (XSS 방지) */
function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/* 계열사 표시 이름 */
function brand_label(string $key): string
{
    return $key === 'greencell' ? '그린셀' : '피앤피코리아';
}

/* 상태 표시 이름 */
function status_label(string $key): string
{
    return ['new' => '미처리', 'progress' => '처리중', 'done' => '완료'][$key] ?? $key;
}
