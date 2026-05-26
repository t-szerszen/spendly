-- migrate:up
ALTER TABLE `households`
    ADD COLUMN `created_by` INT UNSIGNED NULL AFTER `name`,
    ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `created_by`;

UPDATE `households`
SET `created_by` = `owner_id`
WHERE `created_by` IS NULL;

ALTER TABLE `households`
    MODIFY `created_by` INT UNSIGNED NOT NULL,
    ADD CONSTRAINT `fk_households_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE;

CREATE TABLE IF NOT EXISTS `household_members` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `household_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `share_percent` DECIMAL(5, 2) DEFAULT 0.00,
    `role` ENUM('owner', 'member') NOT NULL DEFAULT 'member',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_household_member` (`household_id`, `user_id`),
    KEY `idx_hm_user` (`user_id`),
    CONSTRAINT `fk_hm_household` FOREIGN KEY (`household_id`) REFERENCES `households`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_hm_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `household_expenses` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `household_id` INT UNSIGNED NOT NULL,
    `paid_by_user_id` INT UNSIGNED NOT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    `amount` DECIMAL(15, 2) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `expense_date` DATE NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_household_expenses_date` (`expense_date`),
    KEY `idx_he_household` (`household_id`),
    KEY `idx_he_paid_by` (`paid_by_user_id`),
    KEY `idx_he_category` (`category_id`),
    CONSTRAINT `fk_he_household` FOREIGN KEY (`household_id`) REFERENCES `households`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_he_paid_by` FOREIGN KEY (`paid_by_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_he_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `household_invitations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `household_id` INT UNSIGNED NOT NULL,
    `invited_email` VARCHAR(191) NOT NULL,
    `invite_token` CHAR(64) NOT NULL,
    `invited_by` INT UNSIGNED NOT NULL,
    `status` ENUM('pending', 'accepted', 'expired') NOT NULL DEFAULT 'pending',
    `expires_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_household_invite_token` (`invite_token`),
    KEY `idx_household_invited_email` (`invited_email`),
    KEY `idx_household_invite_status` (`status`),
    CONSTRAINT `fk_hi_household` FOREIGN KEY (`household_id`) REFERENCES `households`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_hi_invited_by` FOREIGN KEY (`invited_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- migrate:down
DROP TABLE IF EXISTS `household_invitations`;
DROP TABLE IF EXISTS `household_expenses`;
DROP TABLE IF EXISTS `household_members`;

ALTER TABLE `households`
    DROP FOREIGN KEY `fk_households_created_by`,
    DROP COLUMN `created_at`,
    DROP COLUMN `created_by`;
