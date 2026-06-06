-- migrate:up
ALTER TABLE `shared_budgets`
    ADD COLUMN `created_by` INT UNSIGNED NULL AFTER `name`,
    ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `owner_id`;

UPDATE `shared_budgets`
SET `created_by` = `owner_id`
WHERE `created_by` IS NULL;

ALTER TABLE `shared_budgets`
    MODIFY `created_by` INT UNSIGNED NOT NULL,
    ADD CONSTRAINT `fk_shared_budgets_created_by`
        FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE;

CREATE TABLE IF NOT EXISTS `shared_budget_members` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `shared_budget_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `share_percent` DECIMAL(5, 2) DEFAULT 0.00,
    `role` ENUM('owner', 'member') NOT NULL DEFAULT 'member',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY `uq_shared_budget_member` (`shared_budget_id`, `user_id`),
    KEY `idx_hm_user` (`user_id`),

    CONSTRAINT `fk_hm_shared_budget`
        FOREIGN KEY (`shared_budget_id`) REFERENCES `shared_budgets`(`id`) ON DELETE CASCADE,

    CONSTRAINT `fk_hm_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shared_budget_invitations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `shared_budget_id` INT UNSIGNED NOT NULL,
    `invited_email` VARCHAR(191) NOT NULL,
    `invite_token` CHAR(64) NOT NULL,
    `invited_by` INT UNSIGNED NOT NULL,
    `status` ENUM('pending', 'accepted', 'expired') NOT NULL DEFAULT 'pending',
    `expires_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY `uq_shared_budget_invite_token` (`invite_token`),
    KEY `idx_shared_budget_invited_email` (`invited_email`),
    KEY `idx_shared_budget_invite_status` (`status`),

    CONSTRAINT `fk_hi_shared_budget`
        FOREIGN KEY (`shared_budget_id`) REFERENCES `shared_budgets`(`id`) ON DELETE CASCADE,

    CONSTRAINT `fk_hi_invited_by`
        FOREIGN KEY (`invited_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `settlements` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `shared_budget_id` INT UNSIGNED NOT NULL,
    `period_month` CHAR(7) NOT NULL,
    `from_user_id` INT UNSIGNED NOT NULL,
    `to_user_id` INT UNSIGNED NOT NULL,
    `amount` DECIMAL(15, 2) NOT NULL,
    `status` ENUM('posted', 'cancelled') NOT NULL DEFAULT 'posted',
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `cancelled_at` TIMESTAMP NULL DEFAULT NULL,

    KEY `idx_settlements_shared_budget_period` (`shared_budget_id`, `period_month`),
    KEY `idx_settlements_from_user` (`from_user_id`),
    KEY `idx_settlements_to_user` (`to_user_id`),
    KEY `idx_settlements_created_by` (`created_by`),

    CONSTRAINT `fk_settlements_shared_budget`
        FOREIGN KEY (`shared_budget_id`) REFERENCES `shared_budgets` (`id`) ON DELETE CASCADE,

    CONSTRAINT `fk_settlements_from_user`
        FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,

    CONSTRAINT `fk_settlements_to_user`
        FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,

    CONSTRAINT `fk_settlements_created_by`
        FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`name`, `is_system`)
SELECT 'Rozliczenie wspolnego budzetu', 1
WHERE NOT EXISTS (
    SELECT 1
    FROM `categories`
    WHERE `name` = 'Rozliczenie wspolnego budzetu'
);

ALTER TABLE `transactions`
    ADD COLUMN `entry_kind`
        ENUM('standard', 'settlement_out', 'settlement_in')
        NOT NULL DEFAULT 'standard' AFTER `type`,

    ADD COLUMN `related_user_id`
        INT UNSIGNED NULL AFTER `shared_budget_id`,

    ADD COLUMN `settlement_id`
        INT UNSIGNED NULL AFTER `related_user_id`,

    ADD INDEX `idx_transactions_shared_budget_month`
        (`shared_budget_id`, `date`),

    ADD INDEX `idx_transactions_entry_kind`
        (`entry_kind`),

    ADD INDEX `idx_transactions_related_user`
        (`related_user_id`),

    ADD INDEX `idx_transactions_settlement`
        (`settlement_id`),

    ADD CONSTRAINT `fk_transactions_related_user`
        FOREIGN KEY (`related_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,

    ADD CONSTRAINT `fk_transactions_settlement`
        FOREIGN KEY (`settlement_id`) REFERENCES `settlements` (`id`) ON DELETE SET NULL;

-- migrate:down

ALTER TABLE `transactions`
    DROP FOREIGN KEY `fk_transactions_settlement`,
    DROP FOREIGN KEY `fk_transactions_related_user`,
    DROP INDEX `idx_transactions_settlement`,
    DROP INDEX `idx_transactions_related_user`,
    DROP INDEX `idx_transactions_entry_kind`,
    DROP INDEX `idx_transactions_shared_budget_month`,
    DROP COLUMN `settlement_id`,
    DROP COLUMN `related_user_id`,
    DROP COLUMN `entry_kind`;

DROP TABLE IF EXISTS `settlements`;

DROP TABLE IF EXISTS `shared_budget_invitations`;

DROP TABLE IF EXISTS `shared_budget_members`;

ALTER TABLE `shared_budgets`
    DROP FOREIGN KEY `fk_shared_budgets_created_by`,
    DROP COLUMN `created_at`,
    DROP COLUMN `created_by`;
