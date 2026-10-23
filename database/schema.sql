-- =====================================================================
--  E-ARSIP PNM - Sistem Penyimpanan File Kantor
--  Skema Database
--  Import melalui phpMyAdmin atau: mysql -u root < database/schema.sql
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `db_earsip_pnm`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `db_earsip_pnm`;

-- ---------------------------------------------------------------------
-- Tabel: users
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `username`      VARCHAR(50)  NOT NULL,
  `password`      VARCHAR(255) NOT NULL,
  `nama_lengkap`  VARCHAR(100) NOT NULL,
  `role`          ENUM('superadmin','user') NOT NULL DEFAULT 'user',
  `unit`          VARCHAR(100) DEFAULT NULL,
  `status`        ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Tabel: categories (kategori pada menu Master)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `kode`       VARCHAR(50)  NOT NULL,
  `nama`       VARCHAR(100) NOT NULL,
  `deskripsi`  VARCHAR(255) DEFAULT NULL,
  `urutan`     INT NOT NULL DEFAULT 0,
  UNIQUE KEY `uq_categories_kode` (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`kode`, `nama`, `deskripsi`, `urutan`) VALUES
  ('daily_closing', 'Daily Closing', 'Dokumen laporan penutupan harian (daily closing)', 1),
  ('denom',         'Denom',         'Dokumen denom / pecahan uang',                    2),
  ('fp4',           'FP4',           'Dokumen FP4',                                     3)
ON DUPLICATE KEY UPDATE `nama` = VALUES(`nama`), `deskripsi` = VALUES(`deskripsi`), `urutan` = VALUES(`urutan`);

-- ---------------------------------------------------------------------
-- Tabel: files (metadata file yang di-upload)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `files` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `category_id`   INT NOT NULL,
  `user_id`       INT DEFAULT NULL,
  `uploader_name` VARCHAR(100) NOT NULL,
  `nama_file`     VARCHAR(255) NOT NULL,
  `stored_name`   VARCHAR(255) NOT NULL,
  `ukuran`        INT NOT NULL DEFAULT 0,
  `tipe`          VARCHAR(120) DEFAULT NULL,
  `keterangan`    VARCHAR(255) DEFAULT NULL,
  `uploaded_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_files_category` (`category_id`),
  KEY `idx_files_user` (`user_id`),
  CONSTRAINT `fk_files_category` FOREIGN KEY (`category_id`)
      REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_files_user` FOREIGN KEY (`user_id`)
      REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Akun default
--   superadmin / admin123   (Super Administrator)
--   user       / user123    (Pengguna contoh)
-- Hash dibuat dengan password_hash(..., PASSWORD_DEFAULT)
-- ---------------------------------------------------------------------
INSERT INTO `users` (`username`, `password`, `nama_lengkap`, `role`, `unit`, `status`) VALUES
  ('superadmin', '$2y$10$aXS3hyofjJVYM5Do0hKl6uogsaNdVLIYEIaMjRjSxI8VUrkxOUMie', 'Super Administrator', 'superadmin', 'Kantor Pusat', 'aktif'),
  ('user',       '$2y$10$0C7NjSwb3dpRRTgToZ8SWuc6kLPzu.LDw4RMwTIteIQ2q4Rrc0i3G', 'Pengguna Contoh',     'user',       'Unit Contoh',  'aktif')
ON DUPLICATE KEY UPDATE `nama_lengkap` = VALUES(`nama_lengkap`);
