-- Exécuté une seule fois, à la création du volume de données : base utilisée par les tests
-- (DB_DATABASE=niangpro_test), à côté de la base de l'application créée par MYSQL_DATABASE.
CREATE DATABASE IF NOT EXISTS niangpro_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON niangpro_test.* TO 'niangpro'@'%';
