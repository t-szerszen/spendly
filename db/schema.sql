--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `is_system` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Table structure for table `households`
--

CREATE TABLE `households` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `owner_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_households_created_by` (`created_by`),
  KEY `idx_households_owner` (`owner_id`),
  CONSTRAINT `fk_households_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_households_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Table structure for table `households_users`
--

CREATE TABLE `households_users` (
  `household_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `split_percentage` decimal(5,2) DEFAULT 0.00,
  PRIMARY KEY (`household_id`,`user_id`),
  KEY `fk_hu_user` (`user_id`),
  CONSTRAINT `fk_hu_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hu_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `household_members`
--

CREATE TABLE `household_members` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `household_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `share_percent` decimal(5,2) DEFAULT 0.00,
  `role` enum('owner','member') NOT NULL DEFAULT 'member',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_household_member` (`household_id`,`user_id`),
  KEY `idx_hm_user` (`user_id`),
  CONSTRAINT `fk_hm_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `household_expenses`
--

CREATE TABLE `household_expenses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `household_id` int(10) unsigned NOT NULL,
  `paid_by_user_id` int(10) unsigned NOT NULL,
  `category_id` int(10) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` text DEFAULT NULL,
  `expense_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_household_expenses_date` (`expense_date`),
  KEY `idx_he_household` (`household_id`),
  KEY `idx_he_paid_by` (`paid_by_user_id`),
  KEY `idx_he_category` (`category_id`),
  CONSTRAINT `fk_he_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_he_paid_by` FOREIGN KEY (`paid_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_he_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `household_invitations`
--

CREATE TABLE `household_invitations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `household_id` int(10) unsigned NOT NULL,
  `invited_email` varchar(191) NOT NULL,
  `invite_token` char(64) NOT NULL,
  `invited_by` int(10) unsigned NOT NULL,
  `status` enum('pending','accepted','expired') NOT NULL DEFAULT 'pending',
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_household_invite_token` (`invite_token`),
  KEY `idx_household_invited_email` (`invited_email`),
  KEY `idx_household_invite_status` (`status`),
  CONSTRAINT `fk_hi_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hi_invited_by` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Table structure for table `schema_migrations`
--

CREATE TABLE `schema_migrations` (
  `version` varchar(128) NOT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `household_id` int(10) unsigned DEFAULT NULL,
  `category_id` int(10) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `type` enum('income','expense','savings','pocket_money') NOT NULL,
  `description` text DEFAULT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_transactions_date` (`date`),
  KEY `idx_transactions_user_type` (`user_id`,`type`),
  KEY `fk_trans_household` (`household_id`),
  KEY `fk_trans_category` (`category_id`),
  CONSTRAINT `fk_trans_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `fk_trans_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_trans_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(191) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `is_kid` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
