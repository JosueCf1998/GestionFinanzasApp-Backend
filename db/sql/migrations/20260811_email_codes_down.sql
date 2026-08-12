DROP TABLE IF EXISTS email_codes;

ALTER TABLE usuarios
DROP COLUMN email_verified_at;
