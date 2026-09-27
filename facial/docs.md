```sql
-- Core users table (Auth & Credentials)
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `organisation_id` bigint(20) unsigned DEFAULT NULL,
  `role_id` tinyint(3) unsigned NOT NULL DEFAULT 3,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `unique_org_email` (`organisation_id`, `email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Extension 1: Corporate Employee Profile
CREATE TABLE `employee_profiles` (
  `user_id` bigint(20) unsigned NOT NULL,
  `employee_id` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `unique_emp_id_per_org` (`employee_id`),
  CONSTRAINT `fk_emp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Extension 2: Academic Student Profile
CREATE TABLE `student_profiles` (
  `user_id` bigint(20) unsigned NOT NULL,
  `roll_number` varchar(100) NOT NULL,
  `batch` varchar(50) DEFAULT NULL,
  `semester` tinyint(2) unsigned DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `unique_roll_per_org` (`roll_number`),
  CONSTRAINT `fk_student_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### updated users

```sql
Create Table: CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `organisation_id` bigint(20) unsigned NOT NULL,
  `role_id` tinyint(3) unsigned NOT NULL DEFAULT 3,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `employee_id` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `uq_org_email` (`organisation_id`,`email`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_org_employee_id` (`organisation_id`,`employee_id`),
  KEY `idx_organisation_id` (`organisation_id`),
  KEY `idx_status` (`status`),
  KEY `fk_users_role` (`role_id`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
1 row in set (0.000 sec)
```

### for verson 1 we will go with student_profiles 