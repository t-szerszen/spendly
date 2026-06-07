-- migrate:up

ALTER TABLE `categories`
    DROP COLUMN `is_system`;

-- migrate:down

ALTER TABLE `categories`
    ADD COLUMN `is_system` TINYINT(1) DEFAULT 0 AFTER `type`;
