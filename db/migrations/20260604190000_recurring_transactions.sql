-- migrate:up
CREATE TABLE IF NOT EXISTS `recurring_transactions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `shared_budget_id` INT UNSIGNED DEFAULT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    `amount` DECIMAL(15, 2) NOT NULL,
    `type` ENUM('income', 'expense', 'savings', 'pocket_money') NOT NULL,
    `description` TEXT DEFAULT NULL,
    `frequency` ENUM('weekly', 'monthly', 'quarterly', 'yearly') NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE DEFAULT NULL,
    `next_due_date` DATE NOT NULL,
    `status` ENUM('active', 'cancelled', 'completed') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_recurring_user_status_due` (`user_id`, `status`, `next_due_date`),
    INDEX `idx_recurring_shared_budget` (`shared_budget_id`),
    INDEX `idx_recurring_category` (`category_id`),
    CONSTRAINT `fk_recurring_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_recurring_shared_budget` FOREIGN KEY (`shared_budget_id`) REFERENCES `shared_budgets`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_recurring_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @recurring_column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'transactions'
      AND COLUMN_NAME = 'recurring_transaction_id'
);

SET @add_recurring_column_sql = IF(
    @recurring_column_exists = 0,
    'ALTER TABLE `transactions` ADD COLUMN `recurring_transaction_id` INT UNSIGNED DEFAULT NULL AFTER `shared_budget_id`',
    'SELECT 1'
);

PREPARE add_recurring_column_stmt FROM @add_recurring_column_sql;
EXECUTE add_recurring_column_stmt;
DEALLOCATE PREPARE add_recurring_column_stmt;

SET @recurring_index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'transactions'
      AND INDEX_NAME = 'idx_transactions_recurring_date'
);

SET @add_recurring_index_sql = IF(
    @recurring_index_exists = 0,
    'ALTER TABLE `transactions` ADD INDEX `idx_transactions_recurring_date` (`recurring_transaction_id`, `date`)',
    'SELECT 1'
);

PREPARE add_recurring_index_stmt FROM @add_recurring_index_sql;
EXECUTE add_recurring_index_stmt;
DEALLOCATE PREPARE add_recurring_index_stmt;

SET @fk_exists = (
    SELECT COUNT(*)
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND CONSTRAINT_NAME = 'fk_transactions_recurring'
);

SET @add_fk_sql = IF(
    @fk_exists = 0,
    'ALTER TABLE `transactions` ADD CONSTRAINT `fk_transactions_recurring` FOREIGN KEY (`recurring_transaction_id`) REFERENCES `recurring_transactions`(`id`) ON DELETE SET NULL',
    'SELECT 1'
);

PREPARE add_fk_stmt FROM @add_fk_sql;
EXECUTE add_fk_stmt;
DEALLOCATE PREPARE add_fk_stmt;

-- migrate:down
ALTER TABLE `transactions`
    DROP FOREIGN KEY `fk_transactions_recurring`,
    DROP INDEX `idx_transactions_recurring_date`,
    DROP COLUMN `recurring_transaction_id`;

DROP TABLE IF EXISTS `recurring_transactions`;
