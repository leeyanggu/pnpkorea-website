-- ============================================================
--  (주)피앤피코리아 · 그린셀 — 문의 저장 테이블
--  phpMyAdmin 또는 mysql 콘솔에서 실행하세요.
--    mysql -u USER -p DBNAME < schema.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS inquiries (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    brand       VARCHAR(20)  NOT NULL DEFAULT 'pnp',        -- pnp | greencell
    name        VARCHAR(80)  NOT NULL,
    company     VARCHAR(120) NOT NULL DEFAULT '',
    phone       VARCHAR(40)  NOT NULL DEFAULT '',
    email       VARCHAR(190) NOT NULL DEFAULT '',
    type        VARCHAR(60)  NOT NULL DEFAULT '',
    message     TEXT         NOT NULL,
    status      ENUM('new','progress','done') NOT NULL DEFAULT 'new',
    admin_memo  TEXT         NULL,
    ip          VARCHAR(45)  NOT NULL DEFAULT '',
    user_agent  VARCHAR(255) NOT NULL DEFAULT '',
    created_at  DATETIME     NOT NULL,
    updated_at  DATETIME     NULL,
    PRIMARY KEY (id),
    KEY idx_brand   (brand),
    KEY idx_status  (status),
    KEY idx_created (created_at),
    KEY idx_email   (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
