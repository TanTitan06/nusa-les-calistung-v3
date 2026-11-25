DROP DATABASE IF EXISTS nusa_calistung_v3;
CREATE DATABASE nusa_calistung_v3 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nusa_calistung_v3;

-- 1. TABEL USERS (Autentikasi Role-Based)
CREATE TABLE USERS (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'orang_tua') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. TABEL ORANG_TUA
CREATE TABLE ORANG_TUA (
    id_orang_tua INT AUTO_INCREMENT PRIMARY KEY,
    nama_orang_tua VARCHAR(100) NOT NULL,
    no_telepon VARCHAR(20) NOT NULL,
    id_user INT NULL UNIQUE,
    CONSTRAINT fk_ortu_user FOREIGN KEY (id_user) REFERENCES USERS(id_user) ON DELETE SET NULL
);

-- 3. TABEL MURID
CREATE TABLE MURID (
    id_murid INT AUTO_INCREMENT PRIMARY KEY,
    nama_murid VARCHAR(100) NOT NULL,
    tanggal_lahir DATE NOT NULL,
    alamat_murid TEXT NOT NULL,
    id_orang_tua INT NOT NULL,
    CONSTRAINT fk_murid_ortu FOREIGN KEY (id_orang_tua) REFERENCES ORANG_TUA(id_orang_tua) ON DELETE CASCADE
);

-- 4. TABEL JURNAL_PEMBELAJARAN
CREATE TABLE JURNAL_PEMBELAJARAN (
    id_jurnal INT AUTO_INCREMENT PRIMARY KEY,
    id_murid INT NOT NULL,
    tanggal DATE NOT NULL,
    jilid VARCHAR(30) NOT NULL,
    halaman INT NOT NULL CHECK (halaman > 0),
    catatan_pengajar TEXT NULL,
    CONSTRAINT fk_jurnal_murid FOREIGN KEY (id_murid) REFERENCES MURID(id_murid) ON DELETE CASCADE
);

-- 5. TABEL PEMBAYARAN
CREATE TABLE PEMBAYARAN (
    id_pembayaran INT AUTO_INCREMENT PRIMARY KEY,
    id_murid INT NOT NULL,
    tanggal DATE NOT NULL,
    jenis_pembayaran VARCHAR(50) NOT NULL,
    jumlah DECIMAL(12, 2) NOT NULL CHECK (jumlah >= 0),
    status_pembayaran ENUM('Lunas', 'Belum Lunas') NOT NULL DEFAULT 'Lunas',
    no_kuitansi VARCHAR(30) NULL UNIQUE,
    CONSTRAINT fk_bayar_murid FOREIGN KEY (id_murid) REFERENCES MURID(id_murid) ON DELETE CASCADE
);

-- 6. TABEL KEHADIRAN
CREATE TABLE KEHADIRAN (
    id_kehadiran INT AUTO_INCREMENT PRIMARY KEY,
    id_murid INT NOT NULL,
    tanggal_pertemuan DATE NOT NULL,
    status_kehadiran ENUM('Hadir', 'Tidak Hadir') NOT NULL,
    CONSTRAINT uq_absen_harian UNIQUE (id_murid, tanggal_pertemuan),
    CONSTRAINT fk_hadir_murid FOREIGN KEY (id_murid) REFERENCES MURID(id_murid) ON DELETE CASCADE
);

-- 7. TABEL PENDAFTARAN_ONLINE
CREATE TABLE PENDAFTARAN_ONLINE (
    id_daftar INT AUTO_INCREMENT PRIMARY KEY,
    nama_calon_murid VARCHAR(100) NOT NULL,
    tanggal_lahir DATE NOT NULL,
    alamat_murid TEXT NOT NULL,
    nama_orang_tua VARCHAR(100) NOT NULL,
    no_telepon VARCHAR(20) NOT NULL,
    tanggal_daftar DATE NOT NULL,
    status_verifikasi ENUM('Menunggu', 'Diterima', 'Ditolak') NOT NULL DEFAULT 'Menunggu'
);

-- 8. TABEL PENGATURAN_WEB
CREATE TABLE PENGATURAN_WEB (
    id_pengaturan INT PRIMARY KEY,
    nama_bimbel VARCHAR(100) NOT NULL,
    profil_singkat TEXT NOT NULL,
    lokasi_alamat TEXT NOT NULL,
    link_gmaps TEXT NOT NULL,
    no_whatsapp VARCHAR(20) NOT NULL,
    jadwal_belajar VARCHAR(150) NOT NULL,
    rincian_program TEXT NOT NULL,
    rincian_biaya TEXT NOT NULL
);

-- 9. TABEL TESTIMONI
CREATE TABLE TESTIMONI (
    id_testimoni INT AUTO_INCREMENT PRIMARY KEY,
    nama_orang_tua VARCHAR(100) NOT NULL,
    isi_testimoni TEXT NOT NULL,
    status_tampil ENUM('Tampil', 'Sembunyikan') NOT NULL DEFAULT 'Tampil',
    tanggal_dibuat DATE NOT NULL
);

-- ==========================================
-- SEED DATA AWAL (Password semua akun: password)
-- ==========================================
INSERT INTO USERS (id_user, username, password_hash, role) VALUES
(1, 'admin_difa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
(2, 'ortu_lisa',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'orang_tua'),
(3, 'ortu_rubi',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'orang_tua');

INSERT INTO ORANG_TUA (id_orang_tua, nama_orang_tua, no_telepon, id_user) VALUES
(1, 'Ibu Lisa', '081254321001', 2),
(2, 'Ibu Tri Rubi', '081254321002', 3);

INSERT INTO MURID (id_murid, nama_murid, tanggal_lahir, alamat_murid, id_orang_tua) VALUES
(1, 'Arzan', '2019-05-14', 'Jl. Pendidikan, Sangatta Selatan', 1),
(2, 'Aqila Sabrina', '2018-09-22', 'Jl. Poros Kabo, Sangatta Selatan', 2);

INSERT INTO JURNAL_PEMBELAJARAN (id_murid, tanggal, jilid, halaman, catatan_pengajar) VALUES
(1, '2026-11-18', 'Jilid 3', 55, 'Sudah lancar membaca dua suku kata tanpa mengeja.'),
(2, '2026-11-18', 'Jilid 4', 40, 'Sangat baik dalam membaca kalimat panjang dan berhitung dasar.');

INSERT INTO PEMBAYARAN (id_murid, tanggal, jenis_pembayaran, jumlah, status_pembayaran, no_kuitansi) VALUES
(1, '2026-11-01', 'Bulanan', 100000, 'Lunas', 'KWT-202611-A101'),
(2, '2026-11-02', 'Bulanan', 100000, 'Lunas', 'KWT-202611-A102');

INSERT INTO KEHADIRAN (id_murid, tanggal_pertemuan, status_kehadiran) VALUES
(1, '2026-11-18', 'Hadir'),
(2, '2026-11-18', 'Hadir');

INSERT INTO PENDAFTARAN_ONLINE (nama_calon_murid, tanggal_lahir, alamat_murid, nama_orang_tua, no_telepon, tanggal_daftar, status_verifikasi) VALUES
('Rafa Alfarizi', '2019-11-10', 'Jl. Kampung Kajang, Sangatta Selatan', 'Bapak Hendra', '081347890123', '2026-11-19', 'Menunggu');

INSERT INTO PENGATURAN_WEB VALUES (
    1,
    'Nusa Les Calistung',
    'Bimbingan belajar membaca, menulis, berhitung, dan mengaji dengan pendampingan personal untuk anak usia dini dan sekolah dasar.',
    'Sangatta Selatan, Kabupaten Kutai Timur, Kalimantan Timur',
    'https://maps.google.com/?q=Sangatta+Selatan+Kutai+Timur',
    '6281234567890',
    'Selasa, Rabu, Kamis | Pukul 15.00 - 16.00 WITA',
    '1. Membaca (Metode Jilid 1 - 7)\n2. Menulis Rapi & Dikte\n3. Berhitung Dasar (Penjumlahan & Pengurangan)\n4. Mengaji Iqra / Al-Quran',
    'Biaya Pendaftaran Awal: Rp 150.000 (Gratis Paket Buku & Pensil)\nIuran Bulanan: Rp 100.000 / bulan'
);

INSERT INTO TESTIMONI (nama_orang_tua, isi_testimoni, status_tampil, tanggal_dibuat) VALUES
('Ibu Lisa (Orang Tua Arzan)', 'Alhamdulillah dalam 3 bulan anak saya sudah lancar membaca tanpa mengeja dan sangat senang belajar di sini.', 'Tampil', '2026-11-10'),
('Ibu Tri Rubi (Orang Tua Aqila)', 'Pengajarnya sangat sabar membimbing anak-anak, laporan perkembangan juga selalu jelas.', 'Tampil', '2026-11-12');