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
-- Table structure for table `shared_budgets`
--

CREATE TABLE `shared_budgets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `owner_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_shared_budgets_created_by` (`created_by`),
  KEY `idx_shared_budgets_owner` (`owner_id`),
  CONSTRAINT `fk_shared_budgets_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_shared_budgets_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Table structure for table `shared_budgets_users`
--

CREATE TABLE `shared_budgets_users` (
  `shared_budget_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `split_percentage` decimal(5,2) DEFAULT 0.00,
  PRIMARY KEY (`shared_budget_id`,`user_id`),
  KEY `fk_hu_user` (`user_id`),
  CONSTRAINT `fk_hu_shared_budget` FOREIGN KEY (`shared_budget_id`) REFERENCES `shared_budgets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hu_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `shared_budget_members`
--

CREATE TABLE `shared_budget_members` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shared_budget_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `share_percent` decimal(5,2) DEFAULT 0.00,
  `role` enum('owner','member') NOT NULL DEFAULT 'member',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_shared_budget_member` (`shared_budget_id`,`user_id`),
  KEY `idx_hm_user` (`user_id`),
  CONSTRAINT `fk_hm_shared_budget` FOREIGN KEY (`shared_budget_id`) REFERENCES `shared_budgets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `settlements`
--

CREATE TABLE `settlements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shared_budget_id` int(10) unsigned NOT NULL,
  `period_month` char(7) NOT NULL,
  `from_user_id` int(10) unsigned NOT NULL,
  `to_user_id` int(10) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `status` enum('posted','cancelled') NOT NULL DEFAULT 'posted',
  `created_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cancelled_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_settlements_shared_budget_period` (`shared_budget_id`,`period_month`),
  KEY `idx_settlements_from_user` (`from_user_id`),
  KEY `idx_settlements_to_user` (`to_user_id`),
  KEY `idx_settlements_created_by` (`created_by`),
  CONSTRAINT `fk_settlements_shared_budget` FOREIGN KEY (`shared_budget_id`) REFERENCES `shared_budgets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_settlements_from_user` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_settlements_to_user` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_settlements_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `shared_budget_invitations`
--

CREATE TABLE `shared_budget_invitations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shared_budget_id` int(10) unsigned NOT NULL,
  `invited_email` varchar(191) NOT NULL,
  `invite_token` char(64) NOT NULL,
  `invited_by` int(10) unsigned NOT NULL,
  `status` enum('pending','accepted','expired') NOT NULL DEFAULT 'pending',
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_shared_budget_invite_token` (`invite_token`),
  KEY `idx_shared_budget_invited_email` (`invited_email`),
  KEY `idx_shared_budget_invite_status` (`status`),
  CONSTRAINT `fk_hi_shared_budget` FOREIGN KEY (`shared_budget_id`) REFERENCES `shared_budgets` (`id`) ON DELETE CASCADE,
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
  `shared_budget_id` int(10) unsigned DEFAULT NULL,
  `related_user_id` int(10) unsigned DEFAULT NULL,
  `settlement_id` int(10) unsigned DEFAULT NULL,
  `category_id` int(10) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `type` enum('income','expense','savings','pocket_money') NOT NULL,
  `entry_kind` enum('standard','settlement_out','settlement_in') NOT NULL DEFAULT 'standard',
  `description` text DEFAULT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_transactions_date` (`date`),
  KEY `idx_transactions_user_type` (`user_id`,`type`),
  KEY `idx_transactions_shared_budget_month` (`shared_budget_id`,`date`),
  KEY `idx_transactions_entry_kind` (`entry_kind`),
  KEY `idx_transactions_related_user` (`related_user_id`),
  KEY `idx_transactions_settlement` (`settlement_id`),
  KEY `fk_trans_shared_budget` (`shared_budget_id`),
  KEY `fk_trans_category` (`category_id`),
  CONSTRAINT `fk_trans_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `fk_trans_shared_budget` FOREIGN KEY (`shared_budget_id`) REFERENCES `shared_budgets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_transactions_related_user` FOREIGN KEY (`related_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_transactions_settlement` FOREIGN KEY (`settlement_id`) REFERENCES `settlements` (`id`) ON DELETE SET NULL,
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
