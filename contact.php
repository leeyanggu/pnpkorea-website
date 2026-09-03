<?php
/* ============================================================
   문의 폼 처리 (피앤피코리아 · 그린셀 공용)

   - 입력값 검증 + 스팸(honeypot) 차단
   - inquiries 테이블에 저장  (DB 실패 시 contact-log.tsv 로 자동 대체)
   - config.php 의 mail.enabled = true 이면 알림 메일도 발송
   - 폼의 hidden 필드 brand=pnp|greencell 로 접수처를 구분
   ============================================================ */
declare(strict_types=1);

require __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

function respond(bool $ok, string $message, int $code = 200): void
{
    http_response_code($code);
    echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

/* 언어 (폼의 hidden lang=en 이면 영문 응답) */
$lang = (($_POST['lang'] ?? '') === 'en') ? 'en' : 'ko';
$T = $lang === 'en' ? [
    'bad_request' => 'Invalid request.',
    'received'    => 'Your inquiry has been received.',
    'name'        => 'Please enter your name.',
    'phone'       => 'Please enter your phone number.',
    'email'       => 'Please enter a valid e-mail address.',
    'message'     => 'Please enter at least 5 characters in your message.',
    'agree'       => 'Please agree to the collection and use of personal information.',
    'success'     => 'Your inquiry has been received. We will contact you shortly.',
] : [
    'bad_request' => '잘못된 요청입니다.',
    'received'    => '문의가 접수되었습니다.',
    'name'        => '이름을 입력해 주세요.',
    'phone'       => '연락처를 입력해 주세요.',
    'email'       => '올바른 이메일을 입력해 주세요.',
    'message'     => '문의 내용을 5자 이상 입력해 주세요.',
    'agree'       => '개인정보 수집·이용에 동의해 주세요.',
    'success'     => '문의가 접수되었습니다. 빠르게 연락드리겠습니다.',
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, $T['bad_request'], 405);
}

/* 스팸 봇 차단: 숨김 필드가 채워져 있으면 조용히 성공 처리 */
if (!empty($_POST['website'])) {
    respond(true, $T['received']);
}

/* ── 입력값 ── */
$brand   = in_array($_POST['brand'] ?? '', ['pnp', 'greencell'], true) ? $_POST['brand'] : 'pnp';
$name    = trim((string) ($_POST['name']    ?? ''));
$company = trim((string) ($_POST['company'] ?? ''));
$phone   = trim((string) ($_POST['phone']   ?? ''));
$email   = trim((string) ($_POST['email']   ?? ''));
$type    = trim((string) ($_POST['type']    ?? '기타'));
$message = trim((string) ($_POST['message'] ?? ''));
$agree   = in_array($_POST['agree'] ?? '', ['on', '1', 'true'], true);

/* ── 검증 ── */
$errors = [];
if ($name === '')                               $errors[] = $T['name'];
if ($phone === '')                              $errors[] = $T['phone'];
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = $T['email'];
if (mb_strlen($message) < 5)                    $errors[] = $T['message'];
if (!$agree)                                    $errors[] = $T['agree'];

if ($errors) {
    respond(false, implode("\n", $errors), 422);
}

/* 길이 제한 (컬럼에 맞춤) */
$name    = mb_substr($name, 0, 80);
$company = mb_substr($company, 0, 120);
$phone   = mb_substr($phone, 0, 40);
$email   = mb_substr($email, 0, 190);
$type    = mb_substr($type, 0, 60);

$ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
$ua = mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

$hasConfig = is_file(__DIR__ . '/config.php');

/* ── 저장 ── */
$saved = false;
if ($hasConfig) {
    try {
        $stmt = db()->prepare(
            'INSERT INTO inquiries
                (brand, name, company, phone, email, type, message, status, ip, user_agent, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, \'new\', ?, ?, NOW())'
        );
        $stmt->execute([$brand, $name, $company, $phone, $email, $type, $message, $ip, $ua]);
        $saved = true;
    } catch (Throwable $ex) {
        @error_log('[contact] DB insert failed: ' . $ex->getMessage());
    }
}
if (!$saved) {
    /* DB 미설정 / 실패해도 문의를 잃지 않도록 파일로 백업 */
    $line = date('Y-m-d H:i:s') . "\t"
        . str_replace(["\t", "\n", "\r"], ' ',
            brand_label($brand) . " | $name | $company | $phone | $email | $type | $message")
        . "\n";
    @file_put_contents(__DIR__ . '/contact-log.tsv', $line, FILE_APPEND | LOCK_EX);
}

/* ── 알림 메일 (선택) ── */
$mail = $hasConfig ? (cfg()['mail'] ?? []) : [];
if (!empty($mail['enabled'])) {
    $to      = $mail['to'][$brand] ?? ($mail['to']['pnp'] ?? '');
    $subject = '[' . brand_label($brand) . " 문의] {$type} - {$name}";
    $body = implode("\n", [
        '접수처   : ' . brand_label($brand),
        "이름     : {$name}",
        "회사명   : {$company}",
        "연락처   : {$phone}",
        "이메일   : {$email}",
        "문의유형 : {$type}",
        '',
        '── 내용 ──',
        $message,
        '',
        '접수시각 : ' . date('Y-m-d H:i:s'),
        'IP       : ' . $ip,
    ]);

    $sent = false;
    $autoload = __DIR__ . '/vendor/autoload.php';
    if ($to && is_file($autoload)) {
        require_once $autoload;
        try {
            $m = new PHPMailer\PHPMailer\PHPMailer(true);
            $s = $mail['smtp'];
            $m->isSMTP();
            $m->Host       = $s['host'];
            $m->SMTPAuth   = true;
            $m->Username   = $s['user'];
            $m->Password   = $s['pass'];
            $m->SMTPSecure = $s['secure'];
            $m->Port       = (int) $s['port'];
            $m->CharSet    = 'UTF-8';
            $m->setFrom($mail['from'], brand_label($brand) . ' 홈페이지');
            $m->addAddress($to);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $m->addReplyTo($email, $name);
            }
            $m->Subject = $subject;
            $m->Body    = $body;
            $m->send();
            $sent = true;
        } catch (Throwable $ex) {
            @error_log('[contact] mail send failed: ' . $ex->getMessage());
        }
    }
    /* PHPMailer 가 없으면 서버 기본 mail() 로 시도 */
    if (!$sent && $to) {
        $headers = 'From: ' . $mail['from'] . "\r\n"
            . 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
        @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
    }
}

respond(true, $T['success']);
