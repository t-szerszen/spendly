-- migrate:up
SET @interval_column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'recurring_transactions'
      AND COLUMN_NAME = 'interval_type'
);

SET @relax_interval_sql = IF(
    @interval_column_exists = 1,
    'ALTER TABLE `recurring_transactions` MODIFY COLUMN `interval_type` ENUM(''weekly'', ''monthly'', ''quarterly'', ''yearly'') NULL',
    'SELECT 1'
);

PREPARE relax_interval_stmt FROM @relax_interval_sql;
EXECUTE relax_interval_stmt;
DEALLOCATE PREPARE relax_interval_stmt;

-- migrate:down
SET @interval_column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'recurring_transactions'
      AND COLUMN_NAME = 'interval_type'
);

SET @restore_interval_sql = IF(
    @interval_column_exists = 1,
    'ALTER TABLE `recurring_transactions` MODIFY COLUMN `interval_type` ENUM(''weekly'', ''monthly'', ''quarterly'', ''yearly'') NOT NULL',
    'SELECT 1'
);

PREPARE restore_interval_stmt FROM @restore_interval_sql;
EXECUTE restore_interval_stmt;
DEALLOCATE PREPARE restore_interval_stmt;
