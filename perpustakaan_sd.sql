-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 04, 2026 at 01:03 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `perpustakaan_sd`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `no_telp` varchar(15) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `jabatan_guru` varchar(100) DEFAULT NULL,
  `poto_profil` varchar(255) DEFAULT 'default.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id_admin`, `id_user`, `nama_lengkap`, `no_telp`, `alamat`, `jabatan_guru`, `poto_profil`) VALUES
(1, 1, '', '', '', '', 'admin_1_1777802520.png');

-- --------------------------------------------------------

--
-- Table structure for table `buku`
--

CREATE TABLE `buku` (
  `id_buku` int(11) NOT NULL,
  `kode_buku` varchar(50) NOT NULL,
  `judul_buku` varchar(200) NOT NULL,
  `penerbit` varchar(100) DEFAULT NULL,
  `tahun_terbit` year(4) DEFAULT NULL,
  `id_kategori` int(11) NOT NULL,
  `id_tema` int(11) DEFAULT NULL,
  `jumlah_buku` int(11) NOT NULL DEFAULT 0,
  `lokasi_rak` varchar(50) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `status` enum('tersedia','tidak tersedia') DEFAULT 'tersedia',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_featured` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buku`
--

INSERT INTO `buku` (`id_buku`, `kode_buku`, `judul_buku`, `penerbit`, `tahun_terbit`, `id_kategori`, `id_tema`, `jumlah_buku`, `lokasi_rak`, `gambar`, `status`, `created_at`, `is_featured`) VALUES
(2, 'B-1777810013', 'Energi dan Evolusi', 'Erlangga', '2023', 1, 3, 4, 'A1', 'book_1777810013.png', 'tersedia', '2026-05-03 12:06:53', 1),
(3, 'B-1777810117', 'Peristiwa Alam (Buku Siswa SD/MI Kelas 1) Tema 8', 'CV Arya Duta', '2017', 2, 3, 2, 'B1', 'book_1777810117.jpg', 'tersedia', '2026-05-03 12:08:37', 1),
(4, 'PEM-1777810118', 'Bahasa Arab Untuk Madrasah Ibtidaiah Kelas 5', 'PT Shaf Media Indonesia', '2024', 2, 5, 6, 'B2', 'book_1777811883.png', 'tersedia', '2026-05-03 12:38:03', 1);

-- --------------------------------------------------------

--
-- Table structure for table `detail_peminjaman`
--

CREATE TABLE `detail_peminjaman` (
  `id_detail` int(11) NOT NULL,
  `id_peminjaman` int(11) NOT NULL,
  `id_buku` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detail_peminjaman`
--

INSERT INTO `detail_peminjaman` (`id_detail`, `id_peminjaman`, `id_buku`, `jumlah`) VALUES
(1, 1, 2, 1),
(2, 2, 3, 1),
(3, 3, 4, 1),
(4, 4, 3, 1);

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id_kategori` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`) VALUES
(3, 'Islami'),
(2, 'Pembelajaran'),
(1, 'Umum');

-- --------------------------------------------------------

--
-- Table structure for table `peminjaman`
--

CREATE TABLE `peminjaman` (
  `id_peminjaman` int(11) NOT NULL,
  `kode_peminjaman` varchar(50) NOT NULL,
  `id_siswa` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `tanggal_pinjam` date NOT NULL,
  `tanggal_jatuh_tempo` date NOT NULL,
  `status` enum('diajukan','dipinjam','dikembalikan') DEFAULT 'diajukan',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `peminjaman`
--

INSERT INTO `peminjaman` (`id_peminjaman`, `kode_peminjaman`, `id_siswa`, `id_user`, `tanggal_pinjam`, `tanggal_jatuh_tempo`, `status`, `created_at`) VALUES
(1, 'REQ-69F73E2C105B0', 2, 2, '2026-05-03', '2026-05-10', 'dipinjam', '2026-05-03 12:23:08'),
(2, 'REQ-69F73E2D8A42A', 2, 2, '2026-05-03', '2026-05-10', 'dipinjam', '2026-05-03 12:23:09'),
(3, 'REQ-69F7CB4A0BC79', 3, 2, '2026-05-04', '2026-05-11', 'dipinjam', '2026-05-03 22:25:14'),
(4, 'REQ-69F7CB4E6FD86', 3, 2, '2026-05-04', '2026-05-11', 'dipinjam', '2026-05-03 22:25:18');

-- --------------------------------------------------------

--
-- Table structure for table `pengembalian`
--

CREATE TABLE `pengembalian` (
  `id_pengembalian` int(11) NOT NULL,
  `id_peminjaman` int(11) NOT NULL,
  `tanggal_kembali` date NOT NULL,
  `status_kembali` enum('tepat waktu','terlambat') DEFAULT 'tepat waktu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan`
--

CREATE TABLE `pengaturan` (
  `kunci` varchar(100) NOT NULL,
  `nilai` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengaturan`
--

INSERT INTO `pengaturan` (`kunci`, `nilai`) VALUES
('tentang_buku_ids', ''),
('tentang_deskripsi', 'Perpustakaan kami bukan sekadar tempat penyimpanan buku, melainkan ekosistem pembelajaran yang dirancang untuk membangkitkan rasa ingin tahu.'),
('tentang_judul', 'Pusat Literasi dan Inovasi Akademik');

-- --------------------------------------------------------

--
-- Table structure for table `pustakawan`
--

CREATE TABLE `pustakawan` (
  `id_pustakawan` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `guru` varchar(100) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `no_hp` varchar(15) DEFAULT NULL,
  `poto_profil` varchar(255) DEFAULT 'default.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pustakawan`
--

INSERT INTO `pustakawan` (`id_pustakawan`, `id_user`, `guru`, `alamat`, `no_hp`, `poto_profil`) VALUES
(1, 2, 'Komputer', 'Metland Cileungsi', '08111222111', 'pustakawan_2_1777847836.png');

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `id_siswa` int(11) NOT NULL,
  `nis` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_siswa` varchar(100) NOT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL,
  `kelas` varchar(10) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `no_hp` varchar(15) DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `nama_orangtua` varchar(100) NOT NULL,
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`id_siswa`, `nis`, `password`, `nama_siswa`, `jenis_kelamin`, `kelas`, `username`, `alamat`, `no_hp`, `status`, `created_at`, `nama_orangtua`, `foto`) VALUES
(2, '111222333', '123', 'Andini Hermawan', 'P', '6A', '111222333', 'Bogor', '1111111', 'aktif', '2026-05-03 12:09:37', 'Saipul', 'siswa_2_1777811474.png'),
(3, '123123123', '123', 'Asepudin', 'L', '3B', '123123123', 'Bogor', '0888888', 'aktif', '2026-05-03 22:23:37', 'Puci', 'siswa_3_1777847094.png');

-- --------------------------------------------------------

--
-- Table structure for table `tema`
--

CREATE TABLE `tema` (
  `id_tema` int(11) NOT NULL,
  `nama_tema` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tema`
--

INSERT INTO `tema` (`id_tema`, `nama_tema`) VALUES
(1, 'Fiksi'),
(2, 'Non-Fiksi'),
(5, 'Religi'),
(3, 'Sains'),
(4, 'Sejarah');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `nama_user` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `level` enum('admin','pustakawan','siswa') NOT NULL DEFAULT 'pustakawan',
  `id_siswa` int(11) DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id_user`, `nama_user`, `username`, `password`, `level`, `id_siswa`, `status`, `created_at`) VALUES
(1, 'Administrator', 'admin', '123', 'admin', NULL, 'aktif', '2026-04-22 13:33:14'),
(2, 'jajang jelani', 'jajangpustakawan', '111', 'pustakawan', NULL, 'aktif', '2026-05-03 10:03:21'),
(3, 'Andini Hermawan', '111222333', '123', 'siswa', 2, 'aktif', '2026-05-03 12:09:37'),
(4, 'Asepudin', '123123123', '123', 'siswa', 3, 'aktif', '2026-05-03 22:23:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `id_user` (`id_user`);

--
-- Indexes for table `buku`
--
ALTER TABLE `buku`
  ADD PRIMARY KEY (`id_buku`),
  ADD UNIQUE KEY `kode_buku` (`kode_buku`),
  ADD KEY `fk_kategori_buku` (`id_kategori`),
  ADD KEY `idx_judul_buku` (`judul_buku`);

--
-- Indexes for table `detail_peminjaman`
--
ALTER TABLE `detail_peminjaman`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `fk_peminjaman_detail` (`id_peminjaman`),
  ADD KEY `fk_buku_detail` (`id_buku`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id_kategori`),
  ADD UNIQUE KEY `nama_kategori` (`nama_kategori`);

--
-- Indexes for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD PRIMARY KEY (`id_peminjaman`),
  ADD UNIQUE KEY `kode_peminjaman` (`kode_peminjaman`),
  ADD KEY `fk_siswa_peminjaman` (`id_siswa`),
  ADD KEY `fk_user_peminjaman` (`id_user`);

--
-- Indexes for table `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD PRIMARY KEY (`id_pengembalian`),
  ADD UNIQUE KEY `id_peminjaman` (`id_peminjaman`);

--
-- Indexes for table `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`kunci`);

--
-- Indexes for table `pustakawan`
--
ALTER TABLE `pustakawan`
  ADD PRIMARY KEY (`id_pustakawan`),
  ADD UNIQUE KEY `id_user` (`id_user`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id_siswa`),
  ADD UNIQUE KEY `nis` (`nis`),
  ADD KEY `idx_nama_siswa` (`nama_siswa`);

--
-- Indexes for table `tema`
--
ALTER TABLE `tema`
  ADD PRIMARY KEY (`id_tema`),
  ADD UNIQUE KEY `nama_tema` (`nama_tema`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `buku`
--
ALTER TABLE `buku`
  MODIFY `id_buku` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `detail_peminjaman`
--
ALTER TABLE `detail_peminjaman`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id_kategori` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `peminjaman`
--
ALTER TABLE `peminjaman`
  MODIFY `id_peminjaman` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pengembalian`
--
ALTER TABLE `pengembalian`
  MODIFY `id_pengembalian` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pustakawan`
--
ALTER TABLE `pustakawan`
  MODIFY `id_pustakawan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id_siswa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tema`
--
ALTER TABLE `tema`
  MODIFY `id_tema` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `fk_user_admin` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `buku`
--
ALTER TABLE `buku`
  ADD CONSTRAINT `fk_kategori_buku` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON UPDATE CASCADE;

--
-- Constraints for table `detail_peminjaman`
--
ALTER TABLE `detail_peminjaman`
  ADD CONSTRAINT `fk_buku_detail` FOREIGN KEY (`id_buku`) REFERENCES `buku` (`id_buku`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_peminjaman_detail` FOREIGN KEY (`id_peminjaman`) REFERENCES `peminjaman` (`id_peminjaman`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD CONSTRAINT `fk_siswa_peminjaman` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_peminjaman` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON UPDATE CASCADE;

--
-- Constraints for table `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD CONSTRAINT `fk_peminjaman_pengembalian` FOREIGN KEY (`id_peminjaman`) REFERENCES `peminjaman` (`id_peminjaman`) ON UPDATE CASCADE;

--
-- Constraints for table `pustakawan`
--
ALTER TABLE `pustakawan`
  ADD CONSTRAINT `fk_user_pustakawan` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
