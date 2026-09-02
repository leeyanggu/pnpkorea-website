# 문의 저장 · 관리자 페이지 설치

문의 폼(`contact.php`)이 접수 내용을 **MySQL 에 저장**하고,
`/admin` 에서 로그인해 문의를 조회·관리합니다.

파일 구성

| 파일 | 역할 |
|---|---|
| `config.sample.php` → `config.php` | DB 접속정보 · 관리자 비밀번호 (git 제외) |
| `schema.sql` | `inquiries` 테이블 생성 SQL |
| `db.php` | 설정 로드 + DB 연결 공용 |
| `contact.php` | 폼 접수 → DB 저장 (+ 선택: 알림 메일) |
| `admin/` | 로그인 · 목록 · 상세 · CSV 내보내기 |

---

## 1. 로컬 (Laragon) 에서 먼저 테스트

1. 프로젝트를 `C:\laragon\www\pnpkorea` 로 옮기거나 심볼릭 링크 → Laragon 재시작
2. **DB 생성**: Laragon 메뉴 → MySQL → phpMyAdmin
   - 데이터베이스 `pnpkorea` 생성 (utf8mb4)
   - `schema.sql` 내용을 SQL 탭에 붙여넣고 실행
3. **설정 파일**: `config.sample.php` 를 `config.php` 로 복사 후 수정
   ```php
   'db' => ['host'=>'localhost','name'=>'pnpkorea','user'=>'root','pass'=>'','charset'=>'utf8mb4'],
   ```
4. **관리자 비밀번호 해시 생성** (Laragon 터미널):
   ```
   php -r "echo password_hash('원하는비밀번호', PASSWORD_DEFAULT), PHP_EOL;"
   ```
   출력값을 `config.php` 의 `admin.password_hash` 에 붙여넣기
5. 브라우저에서
   - `http://pnpkorea.test/contact.html` → 폼 제출 테스트
   - `http://pnpkorea.test/admin/` → 관리자 로그인 → 접수된 문의 확인

---

## 2. 카페24 웹호스팅에 배포

1. **DB 신청**: 카페24 나의서비스관리 → MySQL DB 신청 (DB명·아이디·비번 발급)
2. **테이블 생성**: 카페24 phpMyAdmin 접속 → `schema.sql` 실행
3. **파일 업로드**: FTP 로 프로젝트 전체를 웹 루트(`/www`)에 업로드
   - `config.php` 는 로컬 값이 아닌 **카페24 DB 정보**로 다시 작성
   - PHP 버전은 카페24 관리자에서 8.0 이상 권장
4. **권한**: `config.php` 는 소스가 노출되지 않지만, `.htaccess` 가 이미 직접 접근을 차단함
5. 확인
   - `https://도메인/contact.html` 제출
   - `https://도메인/admin/` 로그인

> `/admin` 경로를 더 숨기고 싶으면 폴더명을 바꾸고(예: `manage-9f2`) 북마크만 사용하세요.

---

## 3. 알림 메일 켜기 (선택)

`config.php` 에서:
```php
'mail' => [
    'enabled' => true,
    'to' => ['pnp' => 'pnp7751@naver.com', 'greencell' => 'pnp7751@naver.com'],
    ...
    'smtp' => ['host'=>'smtp.naver.com','port'=>465,'secure'=>'ssl',
               'user'=>'pnp7751@naver.com','pass'=>'네이버_SMTP_비밀번호'],
],
```
- 네이버 메일 → 환경설정 → **POP3/SMTP 사용** 켜기
- SMTP 인증 발송을 쓰려면 PHPMailer 가 필요합니다:
  - SSH 가능: `composer require phpmailer/phpmailer`
  - SSH 불가(카페24 일반 웹호스팅): [PHPMailer](https://github.com/PHPMailer/PHPMailer/releases) 압축을 풀어
    `vendor/PHPMailer/...` 가 아니라 Composer 구조(`vendor/autoload.php`)가 되도록 올리거나,
    간단히 서버 기본 `mail()` 로도 발송을 시도합니다 (스팸 분류 가능성 있음)
- DB 저장은 메일과 무관하게 항상 동작합니다.

---

## 백업

- 문의 데이터는 DB `inquiries` 테이블에 있습니다. 정기적으로 phpMyAdmin → 내보내기 로 백업하세요.
- `/admin` 목록 화면의 **CSV 내보내기** 로 엑셀 백업도 가능합니다.
