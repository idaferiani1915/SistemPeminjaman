-- Hapus database lama jika ada
CREATE DATABASE IF NOT EXISTS db_peminjaman_aset CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_peminjaman_aset;

-- 1. Tabel users (Pengguna sistem)
CREATE TABLE IF NOT EXISTS users (
  id         INT PRIMARY KEY AUTO_INCREMENT,
  nama       VARCHAR(100) NOT NULL,
  email      VARCHAR(100) UNIQUE NOT NULL,
  password   VARCHAR(255) NOT NULL,          -- selalu bcrypt hash
  role       ENUM('admin', 'peminjam') NOT NULL DEFAULT 'peminjam',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabel aset (Data aset / alat lab)
CREATE TABLE IF NOT EXISTS aset (
  id         INT PRIMARY KEY AUTO_INCREMENT,
  kode_qr    VARCHAR(50) UNIQUE NOT NULL,
  nama_alat  VARCHAR(100) NOT NULL,
  kategori   VARCHAR(50),
  kondisi    ENUM('Baik', 'Rusak', 'Maintenance') DEFAULT 'Baik',
  status     ENUM('Tersedia', 'Dipinjam') DEFAULT 'Tersedia',
  foto       VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabel peminjaman (Transaksi peminjaman)
CREATE TABLE IF NOT EXISTS peminjaman (
  id                  INT PRIMARY KEY AUTO_INCREMENT,
  user_id             INT NOT NULL,
  aset_id             INT NOT NULL,
  tgl_pinjam          DATETIME NOT NULL,
  tgl_kembali_rencana DATE NOT NULL,
  tgl_kembali_aktual  DATETIME NULL,
  status              ENUM('Aktif', 'Dikembalikan', 'Terlambat') DEFAULT 'Aktif',
  created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
  FOREIGN KEY (aset_id) REFERENCES aset(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabel log_activity (Log audit trail)
CREATE TABLE IF NOT EXISTS log_activity (
  id           INT PRIMARY KEY AUTO_INCREMENT,
  user_id      INT NULL,
  ip_address   VARCHAR(45),
  action       VARCHAR(50) NOT NULL,
  tabel_target VARCHAR(50),
  record_id    INT NULL,
  keterangan   TEXT,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Data untuk Pengujian
-- Hash password:
-- admin123 -> $2y$12$Dag1VtqQoj/QsJnRoIaWCOz16ZpDqDFkBylMlK8NeJ8qJomGrRRQi
-- peminjam123 -> $2y$12$P8h7tKSQ822GIEDmonraXe3UBY4uY62JVEGdiUV7EVz8a2RWwG1/G

INSERT INTO users (nama, email, password, role) VALUES
('Administrator Lab', 'admin@lab.com', '$2y$12$Dag1VtqQoj/QsJnRoIaWCOz16ZpDqDFkBylMlK8NeJ8qJomGrRRQi', 'admin'),
('Peminjam Mahasiswa', 'peminjam@lab.com', '$2y$12$P8h7tKSQ822GIEDmonraXe3UBY4uY62JVEGdiUV7EVz8a2RWwG1/G', 'peminjam');

INSERT INTO aset (kode_qr, nama_alat, kategori, kondisi, status, foto) VALUES
('LAB-001', 'Mikroskop Binokuler Olympus CX23', 'Optik', 'Baik', 'Tersedia', NULL),
('LAB-002', 'Solder Listrik Dekko 40W', 'Elektronik', 'Baik', 'Tersedia', NULL),
('LAB-003', 'Oscilloscope Rigol DS1054Z 50MHz', 'Alat Ukur', 'Maintenance', 'Tersedia', NULL),
('LAB-004', 'Multimeter Digital Fluke 17B+', 'Alat Ukur', 'Rusak', 'Tersedia', NULL);
