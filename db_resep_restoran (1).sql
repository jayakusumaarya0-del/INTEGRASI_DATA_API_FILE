-- phpMyAdmin SQL Dump
-- version 5.1.1deb5ubuntu1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 06 Apr 2026 pada 01.21
-- Versi server: 8.0.45-0ubuntu0.22.04.1
-- Versi PHP: 8.1.2-1ubuntu2.23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_resep_restoran`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `resep_masakan`
--

CREATE TABLE `resep_masakan` (
  `id_resep` int NOT NULL,
  `nama_masakan` varchar(100) NOT NULL,
  `asal_masakan` varchar(100) DEFAULT NULL,
  `foto_masakan` varchar(255) DEFAULT NULL,
  `dokumen_resep` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `resep_masakan`
--

INSERT INTO `resep_masakan` (`id_resep`, `nama_masakan`, `asal_masakan`, `foto_masakan`, `dokumen_resep`) VALUES
(2, 'bakmi', 'jawa', 'foto_1775402202_330.png', 'dok_1775402202_329.php');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `resep_masakan`
--
ALTER TABLE `resep_masakan`
  ADD PRIMARY KEY (`id_resep`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `resep_masakan`
--
ALTER TABLE `resep_masakan`
  MODIFY `id_resep` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
