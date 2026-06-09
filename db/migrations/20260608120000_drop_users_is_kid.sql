-- migrate:up

ALTER TABLE `users`
    DROP COLUMN `is_kid`;

-- migrate:down

ALTER TABLE `users`
    ADD COLUMN `is_kid` TINYINT(1) DEFAULT 0 AFTER `last_name`;
