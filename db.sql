-- Tworzenie tabeli użytkowników
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(191) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `is_kid` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tworzenie gospodarstw domowych
CREATE TABLE `households` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `owner_id` INT UNSIGNED NOT NULL,
    CONSTRAINT `fk_households_owner` FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela łącząca użytkowników z domami (Relacja wiele-do-wielu + Proporcje)
CREATE TABLE `households_users` (
    `household_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `split_percentage` DECIMAL(5, 2) DEFAULT 0.00, -- Tu zapisujemy np. 60.00 lub 40.00
    PRIMARY KEY (`household_id`, `user_id`),
    CONSTRAINT `fk_hu_household` FOREIGN KEY (`household_id`) REFERENCES `households`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_hu_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Słownik kategorii
CREATE TABLE `categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `is_system` TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Główna tabela transakcji
CREATE TABLE `transactions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `household_id` INT UNSIGNED DEFAULT NULL, -- NULL oznacza wydatki ściśle prywatne
    `category_id` INT UNSIGNED NOT NULL,
    `amount` DECIMAL(15, 2) NOT NULL, -- Zawsze używaj DECIMAL dla pieniędzy
    `type` ENUM('income', 'expense', 'savings', 'pocket_money') NOT NULL,
    `description` TEXT,
    `date` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_trans_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_trans_household` FOREIGN KEY (`household_id`) REFERENCES `households`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_trans_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indeksy dla wydajności (kluczowe przy raportach)
CREATE INDEX `idx_transactions_date` ON `transactions`(`date`);
CREATE INDEX `idx_transactions_user_type` ON `transactions`(`user_id`, `type`);