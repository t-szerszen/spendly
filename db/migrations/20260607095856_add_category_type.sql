-- migrate:up

ALTER TABLE `categories`
    ADD COLUMN `type` ENUM('expense', 'income') NOT NULL DEFAULT 'expense' AFTER `name`;

UPDATE `categories`
SET `type` = 'income'
WHERE `name` IN ('Wynagrodzenie');

UPDATE `categories`
SET `type` = 'expense'
WHERE `name` IN ('Rozliczenie wspolnego budzetu');

-- migrate:down

ALTER TABLE `categories`
    DROP COLUMN `type`;
