delimiter $$

CREATE TABLE `auth_core` (
  `character_id` int(11) NOT NULL,
  `character_name` varchar(24) NOT NULL,
  `corporation_id` int(11) NOT NULL,
  `corporation_name` varchar(50) NOT NULL,
  `alliance_id` int(11) DEFAULT NULL,
  `alliance_name` varchar(50) DEFAULT NULL,
  `parent_id` int(11) NOT NULL,
  `core_token` varchar(45) DEFAULT NULL,
  `core_tags` longtext,
  `core_perms` longtext,
  `core_updated_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`character_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8$$
