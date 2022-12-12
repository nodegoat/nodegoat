DROP TABLE IF EXISTS `user_details`;

CREATE TABLE `user_details` (
  `user_id` int NOT NULL,
  `clearance` tinyint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `user_details`
  ADD PRIMARY KEY (`user_id`);
