-- migrate:up
ALTER TABLE `recurring_transactions`
    ADD COLUMN IF NOT EXISTS `frequency` ENUM('weekly', 'monthly', 'quarterly', 'yearly') NULL AFTER `description`,
    ADD COLUMN IF NOT EXISTS `next_due_date` DATE NULL AFTER `end_date`,
    ADD COLUMN IF NOT EXISTS `status` ENUM('active', 'cancelled', 'completed') NOT NULL DEFAULT 'active' AFTER `next_due_date`;

UPDATE `recurring_transactions`
SET `frequency` = COALESCE(`frequency`, `interval_type`)
WHERE `frequency` IS NULL;

UPDATE `recurring_transactions`
SET `next_due_date` = COALESCE(`next_due_date`, `start_date`)
WHERE `next_due_date` IS NULL;

UPDATE `recurring_transactions`
SET `status` = CASE
    WHEN `is_active` = 1 THEN 'active'
    ELSE 'cancelled'
END
WHERE `status` = 'active';

ALTER TABLE `recurring_transactions`
    MODIFY COLUMN `frequency` ENUM('weekly', 'monthly', 'quarterly', 'yearly') NOT NULL,
    MODIFY COLUMN `next_due_date` DATE NOT NULL,
    ADD INDEX IF NOT EXISTS `idx_recurring_user_status_due` (`user_id`, `status`, `next_due_date`),
    ADD INDEX IF NOT EXISTS `idx_recurring_shared_budget` (`shared_budget_id`),
    ADD INDEX IF NOT EXISTS `idx_recurring_category` (`category_id`);

-- migrate:down
ALTER TABLE `recurring_transactions`
    DROP INDEX IF EXISTS `idx_recurring_user_status_due`,
    DROP INDEX IF EXISTS `idx_recurring_shared_budget`,
    DROP INDEX IF EXISTS `idx_recurring_category`,
    DROP COLUMN IF EXISTS `status`,
    DROP COLUMN IF EXISTS `next_due_date`,
    DROP COLUMN IF EXISTS `frequency`;
