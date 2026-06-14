-- Cookify Database Schema
-- Import this file via phpMyAdmin

CREATE DATABASE IF NOT EXISTS `cookify`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `cookify`;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE `users` (
    `id`               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `name`             VARCHAR(100)     NOT NULL,
    `email`            VARCHAR(150)     NOT NULL UNIQUE,
    `password_hash`    VARCHAR(255)     NOT NULL,
    `role`             ENUM('user','admin') NOT NULL DEFAULT 'user',
    `activation_token` VARCHAR(64)      DEFAULT NULL,
    `is_active`        TINYINT(1)       NOT NULL DEFAULT 0,
    `failed_attempts`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `last_failed_at`   DATETIME         DEFAULT NULL,
    `is_locked`        TINYINT(1)       NOT NULL DEFAULT 0,
    `created_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_login_at`    DATETIME         DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: categories
-- --------------------------------------------------------
CREATE TABLE `categories` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(100) NOT NULL,
    `description` TEXT         DEFAULT NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: recipes  (1:n with users, 1:n with categories)
-- --------------------------------------------------------
CREATE TABLE `recipes` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`      INT UNSIGNED NOT NULL,
    `category_id`  INT UNSIGNED NOT NULL,
    `title`        VARCHAR(200) NOT NULL,
    `description`  TEXT         NOT NULL,
    `prep_time`    SMALLINT UNSIGNED NOT NULL COMMENT 'minutes',
    `difficulty`   ENUM('easy','medium','hard') NOT NULL DEFAULT 'medium',
    `image_original` VARCHAR(255) DEFAULT NULL,
    `image_thumb`    VARCHAR(255) DEFAULT NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`)     REFERENCES `users`(`id`)      ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: ingredients
-- --------------------------------------------------------
CREATE TABLE `ingredients` (
    `id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `unit` VARCHAR(30)  NOT NULL COMMENT 'e.g. g, ml, pcs, tbsp',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: recipe_ingredients  (m:n between recipes and ingredients)
-- --------------------------------------------------------
CREATE TABLE `recipe_ingredients` (
    `recipe_id`     INT UNSIGNED   NOT NULL,
    `ingredient_id` INT UNSIGNED   NOT NULL,
    `quantity`      DECIMAL(8,2)   NOT NULL,
    PRIMARY KEY (`recipe_id`, `ingredient_id`),
    FOREIGN KEY (`recipe_id`)     REFERENCES `recipes`(`id`)     ON DELETE CASCADE,
    FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: comments  (1:n with recipes, 1:n with users)
-- --------------------------------------------------------
CREATE TABLE `comments` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `recipe_id`  INT UNSIGNED NOT NULL,
    `user_id`    INT UNSIGNED NOT NULL,
    `body`       TEXT         NOT NULL,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`recipe_id`) REFERENCES `recipes`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: ratings  (1:n with recipes, 1:n with users)
-- --------------------------------------------------------
CREATE TABLE `ratings` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `recipe_id`  INT UNSIGNED NOT NULL,
    `user_id`    INT UNSIGNED NOT NULL,
    `score`      TINYINT UNSIGNED NOT NULL CHECK (`score` BETWEEN 1 AND 5),
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_recipe_user` (`recipe_id`, `user_id`),
    FOREIGN KEY (`recipe_id`) REFERENCES `recipes`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Seed data
-- --------------------------------------------------------

-- Admin: password = Admin123!
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `is_active`) VALUES
('Admin User',  'admin@cookify.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1),
('John Baker',  'john@example.com',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user',  1),
('Sarah Cook',  'sarah@example.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user',  1);

INSERT INTO `categories` (`name`, `description`) VALUES
('Breakfast',  'Morning meals and brunch recipes'),
('Lunch',      'Midday meals and light dishes'),
('Dinner',     'Evening meals and hearty dishes'),
('Desserts',   'Sweet treats and baked goods'),
('Vegetarian', 'Plant-based recipes');

INSERT INTO `ingredients` (`name`, `unit`) VALUES
('Flour',          'g'),
('Sugar',          'g'),
('Butter',         'g'),
('Eggs',           'pcs'),
('Milk',           'ml'),
('Salt',           'tsp'),
('Olive Oil',      'ml'),
('Garlic',         'cloves'),
('Tomatoes',       'pcs'),
('Pasta',          'g'),
('Chicken Breast', 'g'),
('Onion',          'pcs'),
('Baking Powder',  'tsp'),
('Vanilla Extract','tsp'),
('Cheese',         'g');

INSERT INTO `recipes` (`user_id`, `category_id`, `title`, `description`, `prep_time`, `difficulty`) VALUES
(2, 1, 'Classic Pancakes',       'Fluffy and golden pancakes perfect for a lazy Sunday morning. Serve with maple syrup and fresh berries.', 20, 'easy'),
(2, 3, 'Spaghetti Bolognese',    'A rich and hearty Italian meat sauce served over al dente spaghetti. A timeless family favourite.', 45, 'medium'),
(3, 4, 'Chocolate Lava Cake',    'Decadent individual chocolate cakes with a molten centre. Best served warm with vanilla ice cream.', 30, 'medium'),
(3, 5, 'Margherita Pizza',       'Simple and delicious pizza with tomato sauce, fresh mozzarella and basil leaves. Baked in a very hot oven.', 40, 'medium'),
(1, 2, 'Caesar Salad',           'Crisp romaine lettuce tossed in a tangy Caesar dressing with croutons and parmesan shavings.', 15, 'easy'),
(2, 3, 'Grilled Chicken Breast', 'Juicy and tender grilled chicken marinated in herbs and olive oil. Great with roasted vegetables.', 35, 'easy');

INSERT INTO `recipe_ingredients` (`recipe_id`, `ingredient_id`, `quantity`) VALUES
(1, 1, 200), (1, 2, 30),  (1, 4, 2),   (1, 5, 250), (1, 13, 2), (1, 6, 1),
(2, 10, 400),(2, 9, 3),   (2, 12, 1),  (2, 8, 3),   (2, 7, 30), (2, 6, 1),
(3, 2, 150), (3, 3, 100), (3, 4, 3),   (3, 13, 1),
(4, 1, 250), (4, 9, 2),   (4, 15, 150),(4, 7, 20),  (4, 6, 1),
(5, 8, 2),   (5, 7, 30),  (5, 15, 50), (5, 6, 1),
(6, 11, 400),(6, 8, 4),   (6, 7, 40),  (6, 6, 1);

INSERT INTO `comments` (`recipe_id`, `user_id`, `body`) VALUES
(1, 3, 'Made these this morning, absolutely delicious! Added a pinch of cinnamon too.'),
(1, 1, 'My kids love this recipe every weekend.'),
(2, 3, 'Best bolognese recipe I have tried. The key is slow cooking the sauce.'),
(3, 2, 'Wow, the molten centre was perfect. Will definitely make again!'),
(4, 2, 'Even better than a restaurant pizza. Simple and flavourful.');

INSERT INTO `ratings` (`recipe_id`, `user_id`, `score`) VALUES
(1, 3, 5), (1, 1, 4),
(2, 3, 5), (2, 1, 5),
(3, 2, 5), (3, 1, 4),
(4, 2, 4), (4, 3, 5),
(5, 2, 4), (5, 3, 3),
(6, 3, 4), (6, 1, 5);
