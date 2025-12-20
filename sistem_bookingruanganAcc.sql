/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-12.1.2-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: sistem_bookingruangan
-- ------------------------------------------------------
-- Server version	12.1.2-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Current Database: `sistem_bookingruangan`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `sistem_bookingruangan` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `sistem_bookingruangan`;

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `room_id` int(11) NOT NULL,
  `nama_kelas` varchar(100) NOT NULL,
  `nama_peminjam` varchar(100) NOT NULL,
  `keperluan` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `tanggal` date NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `peminjam` varchar(100) DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `status` enum('pending','disetujui','ditolak','dibatalkan') NOT NULL DEFAULT 'pending',
  `approved_at` datetime DEFAULT NULL,
  `approved_by` varchar(100) DEFAULT NULL,
  `canceled_at` datetime DEFAULT NULL,
  `canceled_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `room_id` (`room_id`),
  KEY `idx_room_tanggal` (`room_id`,`tanggal`),
  KEY `idx_tanggal` (`tanggal`),
  KEY `idx_status` (`status`),
  KEY `idx_bookings_room_date_time_status` (`room_id`,`tanggal`,`jam_mulai`,`jam_selesai`,`status`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bookings_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `bookings` VALUES
(2,NULL,6,'KAKAKa','admin','HAHAHA','','2025-12-09','12:35:00','12:40:00',NULL,NULL,'disetujui','2025-12-10 00:35:20',NULL,NULL,NULL,'2025-12-09 17:35:20'),
(3,NULL,7,'KAKAKa','admin','HAHAHA','','2025-12-10','12:44:00','12:50:00',NULL,NULL,'disetujui','2025-12-10 00:44:35',NULL,NULL,NULL,'2025-12-09 17:44:35'),
(4,NULL,7,'4s5','KWALKAW','HAHAHA','dfsd','2025-12-10','13:08:00','17:08:00',NULL,NULL,'disetujui','2025-12-10 13:09:30',NULL,NULL,NULL,'2025-12-10 06:09:30'),
(5,NULL,6,'inf c','ar','matkul ilkom','','2025-12-11','09:05:00','09:15:00',NULL,NULL,'disetujui','2025-12-11 21:06:39',NULL,NULL,NULL,'2025-12-11 14:06:39'),
(6,NULL,6,'inf c','ar','maykul','','2025-12-11','21:07:00','21:15:00',NULL,NULL,'disetujui','2025-12-11 21:07:31',NULL,NULL,NULL,'2025-12-11 14:07:31'),
(7,NULL,6,'hjbn','hlb','hlhkj','','2025-12-12','14:13:00','14:30:00',NULL,NULL,'disetujui','2025-12-12 14:13:55',NULL,NULL,NULL,'2025-12-12 07:13:55'),
(8,2,6,'3 inf c','fiki','matkul','','2025-12-13','20:30:00','20:35:00',NULL,NULL,'disetujui','2025-12-13 20:30:42',NULL,NULL,NULL,'2025-12-13 13:30:42'),
(9,1,6,'3 inf c','vq','matjuk','','2025-12-13','20:35:00','20:40:00',NULL,NULL,'disetujui','2025-12-13 20:36:00',NULL,NULL,NULL,'2025-12-13 13:36:00'),
(10,NULL,6,'ret','et','ewr','','2025-12-13','20:54:00','21:00:00',NULL,NULL,'disetujui','2025-12-13 20:56:54',NULL,NULL,NULL,'2025-12-13 13:56:54'),
(11,1,6,'inf c','vq','matkul','','2025-12-14','10:58:00','11:00:00',NULL,NULL,'disetujui','2025-12-14 10:58:30',NULL,NULL,NULL,'2025-12-14 03:58:30'),
(12,1,6,'inf c','vq','matkul','','2025-12-14','11:05:00','11:10:00',NULL,NULL,'disetujui','2025-12-14 11:05:57',NULL,NULL,NULL,'2025-12-14 04:05:57'),
(13,1,6,'inf c','vq','matkul','','2025-12-15','14:01:00','14:05:00',NULL,NULL,'disetujui','2025-12-15 14:01:49',NULL,NULL,NULL,'2025-12-15 07:01:49'),
(14,NULL,6,'inf c','askjld','matkul','','2025-12-16','13:55:00','14:00:00',NULL,NULL,'disetujui','2025-12-16 13:56:31',NULL,NULL,NULL,'2025-12-16 06:56:31'),
(15,NULL,34,'agksdn','afsnmd','matkul','','2025-12-16','13:57:00','14:00:00',NULL,NULL,'disetujui','2025-12-16 13:57:51',NULL,NULL,NULL,'2025-12-16 06:57:51'),
(16,2,6,'inf c','fiki','matkul','','2025-12-16','14:08:00','14:20:00',NULL,NULL,'disetujui','2025-12-16 14:08:59',NULL,NULL,NULL,'2025-12-16 07:08:59'),
(17,NULL,6,'inf a','rusdi','matkul','','2025-12-16','14:21:00','14:30:00',NULL,NULL,'disetujui','2025-12-16 14:10:10',NULL,NULL,NULL,'2025-12-16 07:10:10'),
(18,2,7,'3 inf c','fiki','matakuliah','','2025-12-16','14:29:00','15:00:00',NULL,NULL,'disetujui','2025-12-16 14:30:06',NULL,NULL,NULL,'2025-12-16 07:30:06'),
(19,NULL,6,'jafkak','asfjkjaskl','asfkldjafs','','2025-12-16','15:26:00','16:00:00',NULL,NULL,'disetujui','2025-12-16 15:26:36',NULL,NULL,NULL,'2025-12-16 08:26:36'),
(20,NULL,7,'asmnfkanms','asfnajskl','asfnjhaiojasf','','2025-12-16','15:26:00','16:00:00',NULL,NULL,'disetujui','2025-12-16 15:26:51',NULL,NULL,NULL,'2025-12-16 08:26:51'),
(21,1,6,'iinf c','vq','matkkul','','2025-12-17','12:30:00','15:00:00',NULL,NULL,'disetujui','2025-12-17 13:38:20',NULL,NULL,NULL,'2025-12-17 06:38:20'),
(22,1,6,'inf c','vq','matkul','','2025-12-18','06:45:00','11:00:00',NULL,'Dibatalkan oleh admin vq pada 2025-12-18 01:59:35','dibatalkan','2025-12-18 01:37:19',NULL,NULL,NULL,'2025-12-17 18:37:19'),
(23,2,7,'inf c','fiki','matkul','','2025-12-18','06:45:00','09:00:00',NULL,NULL,'disetujui','2025-12-18 02:06:28',NULL,NULL,NULL,'2025-12-17 19:06:28'),
(24,1,6,'inf c','vq','matkul','','2025-12-18','11:10:00','11:55:00',NULL,NULL,'disetujui','2025-12-18 11:10:39',NULL,NULL,NULL,'2025-12-18 04:10:39'),
(25,1,6,'inf c','vq','matkul','','2025-12-18','12:30:00','13:00:00',NULL,NULL,'disetujui','2025-12-18 12:12:04',NULL,NULL,NULL,'2025-12-18 05:12:04'),
(26,2,7,'inf c','fiki','matkul','','2025-12-18','12:30:00','13:00:00',NULL,NULL,'disetujui','2025-12-18 12:26:52',NULL,NULL,NULL,'2025-12-18 05:26:52'),
(36,2,7,'inf c','fiki','matkul','','2025-12-18','13:17:00','14:02:00',NULL,NULL,'disetujui','2025-12-18 13:19:56',NULL,NULL,NULL,'2025-12-18 06:19:56'),
(37,1,34,'inf','vq','matkul','','2025-12-18','13:55:00','14:40:00',NULL,NULL,'disetujui','2025-12-18 13:55:58',NULL,NULL,NULL,'2025-12-18 06:55:58'),
(38,2,6,'inf c','fiki','matkul','','2025-12-18','15:30:00','16:00:00',NULL,NULL,'disetujui','2025-12-18 15:05:16',NULL,NULL,NULL,'2025-12-18 08:05:16'),
(39,NULL,6,'IF-2A','SISTEM','Jadwal Kuliah','Basis Data - Dosen A','2025-12-15','08:00:00','09:40:00',NULL,'AUTO-JADWAL (2025-12-15 s/d 2025-12-21)','disetujui','2025-12-18 15:11:47','system_jadwal',NULL,NULL,'2025-12-18 08:11:47'),
(40,NULL,6,'IF-2A','SISTEM','Jadwal Kuliah','Struktur Data - Dosen B','2025-12-16','10:00:00','11:40:00',NULL,'AUTO-JADWAL (2025-12-15 s/d 2025-12-21)','disetujui','2025-12-18 15:11:47','system_jadwal',NULL,NULL,'2025-12-18 08:11:47'),
(41,NULL,7,'IF-2A','SISTEM','Jadwal Kuliah','Pemrograman Web - Dosen C','2025-12-17','08:00:00','09:40:00',NULL,'AUTO-JADWAL (2025-12-15 s/d 2025-12-21)','disetujui','2025-12-18 15:11:47','system_jadwal',NULL,NULL,'2025-12-18 08:11:47'),
(42,NULL,6,'IF-2A','SISTEM','Jadwal Kuliah','Sistem Operasi - Dosen D','2025-12-18','13:00:00','14:40:00',NULL,'AUTO-JADWAL (2025-12-15 s/d 2025-12-21)','disetujui','2025-12-18 15:11:47','system_jadwal',NULL,NULL,'2025-12-18 08:11:47'),
(43,NULL,7,'IF-2A','SISTEM','Jadwal Kuliah','Jaringan Komputer - Dosen E','2025-12-19','10:00:00','11:40:00',NULL,'AUTO-JADWAL (2025-12-15 s/d 2025-12-21)','disetujui','2025-12-18 15:11:47','system_jadwal',NULL,NULL,'2025-12-18 08:11:47'),
(44,NULL,34,'IF-2A','SISTEM','Jadwal Kuliah','web - dosen y','2025-12-19','13:30:00','13:40:00',NULL,'AUTO-JADWAL (2025-12-15 s/d 2025-12-21)','disetujui','2025-12-18 15:11:47','system_jadwal',NULL,NULL,'2025-12-18 08:11:47'),
(45,NULL,7,'IF-2B','SISTEM','Jadwal Kuliah','Basis Data - Dosen A','2025-12-15','10:00:00','11:40:00',NULL,'AUTO-JADWAL (2025-12-15 s/d 2025-12-21)','disetujui','2025-12-18 15:11:47','system_jadwal',NULL,NULL,'2025-12-18 08:11:47'),
(46,NULL,7,'IF-2B','SISTEM','Jadwal Kuliah','Struktur Data - Dosen B','2025-12-16','08:00:00','09:40:00',NULL,'AUTO-JADWAL (2025-12-15 s/d 2025-12-21)','disetujui','2025-12-18 15:11:47','system_jadwal',NULL,NULL,'2025-12-18 08:11:47'),
(47,NULL,6,'IF-2B','SISTEM','Jadwal Kuliah','Pemrograman Web - Dosen C','2025-12-17','10:00:00','11:40:00',NULL,'AUTO-JADWAL (2025-12-15 s/d 2025-12-21)','disetujui','2025-12-18 15:11:47','system_jadwal',NULL,NULL,'2025-12-18 08:11:47'),
(48,NULL,6,'IF-2B','SISTEM','Jadwal Kuliah','Jaringan Komputer - Dosen E','2025-12-19','13:00:00','14:40:00',NULL,'AUTO-JADWAL (2025-12-15 s/d 2025-12-21)','disetujui','2025-12-18 15:11:47','system_jadwal',NULL,NULL,'2025-12-18 08:11:47'),
(49,NULL,6,'IF-2A','SISTEM','Jadwal Kuliah','Basis Data - Dosen A','2025-12-22','08:00:00','09:40:00',NULL,'AUTO-JADWAL (2025-12-22 s/d 2025-12-28)','disetujui','2025-12-18 15:17:47','system_jadwal',NULL,NULL,'2025-12-18 08:17:47'),
(50,NULL,6,'IF-2A','SISTEM','Jadwal Kuliah','Struktur Data - Dosen B','2025-12-23','10:00:00','11:40:00',NULL,'AUTO-JADWAL (2025-12-22 s/d 2025-12-28)','disetujui','2025-12-18 15:17:47','system_jadwal',NULL,NULL,'2025-12-18 08:17:47'),
(51,NULL,7,'IF-2A','SISTEM','Jadwal Kuliah','Pemrograman Web - Dosen C','2025-12-24','08:00:00','09:40:00',NULL,'AUTO-JADWAL (2025-12-22 s/d 2025-12-28)','disetujui','2025-12-18 15:17:47','system_jadwal',NULL,NULL,'2025-12-18 08:17:47'),
(52,NULL,6,'IF-2A','SISTEM','Jadwal Kuliah','Sistem Operasi - Dosen D','2025-12-25','13:00:00','14:40:00',NULL,'AUTO-JADWAL (2025-12-22 s/d 2025-12-28)','disetujui','2025-12-18 15:17:47','system_jadwal',NULL,NULL,'2025-12-18 08:17:47'),
(53,NULL,7,'IF-2A','SISTEM','Jadwal Kuliah','Jaringan Komputer - Dosen E','2025-12-26','10:00:00','11:40:00',NULL,'AUTO-JADWAL (2025-12-22 s/d 2025-12-28)','disetujui','2025-12-18 15:17:47','system_jadwal',NULL,NULL,'2025-12-18 08:17:47'),
(54,NULL,34,'IF-2A','SISTEM','Jadwal Kuliah','web - dosen y','2025-12-26','13:30:00','13:40:00',NULL,'AUTO-JADWAL (2025-12-22 s/d 2025-12-28)','disetujui','2025-12-18 15:17:47','system_jadwal',NULL,NULL,'2025-12-18 08:17:47'),
(55,NULL,7,'IF-2B','SISTEM','Jadwal Kuliah','Basis Data - Dosen A','2025-12-22','10:00:00','11:40:00',NULL,'AUTO-JADWAL (2025-12-22 s/d 2025-12-28)','disetujui','2025-12-18 15:17:47','system_jadwal',NULL,NULL,'2025-12-18 08:17:47'),
(56,NULL,7,'IF-2B','SISTEM','Jadwal Kuliah','Struktur Data - Dosen B','2025-12-23','08:00:00','09:40:00',NULL,'AUTO-JADWAL (2025-12-22 s/d 2025-12-28)','disetujui','2025-12-18 15:17:47','system_jadwal',NULL,NULL,'2025-12-18 08:17:47'),
(57,NULL,6,'IF-2B','SISTEM','Jadwal Kuliah','Pemrograman Web - Dosen C','2025-12-24','10:00:00','11:40:00',NULL,'AUTO-JADWAL (2025-12-22 s/d 2025-12-28)','disetujui','2025-12-18 15:17:47','system_jadwal',NULL,NULL,'2025-12-18 08:17:47'),
(58,NULL,7,'IF-2B','SISTEM','Jadwal Kuliah','Sistem Operasi - Dosen D','2025-12-25','08:00:00','09:40:00',NULL,'AUTO-JADWAL (2025-12-22 s/d 2025-12-28)','disetujui','2025-12-18 15:17:47','system_jadwal',NULL,NULL,'2025-12-18 08:17:47'),
(59,NULL,6,'IF-2B','SISTEM','Jadwal Kuliah','Jaringan Komputer - Dosen E','2025-12-26','13:00:00','14:40:00',NULL,'AUTO-JADWAL (2025-12-22 s/d 2025-12-28)','disetujui','2025-12-18 15:17:47','system_jadwal',NULL,NULL,'2025-12-18 08:17:47'),
(60,2,7,'inf c','fiki','matkul','','2025-12-19','15:30:00','16:00:00',NULL,NULL,'disetujui','2025-12-18 15:22:33',NULL,NULL,NULL,'2025-12-18 08:22:33'),
(61,2,6,'inf C','fiki','matkul','','2025-12-19','09:00:00','10:00:00',NULL,'Dibatalkan oleh mahasiswa (fiki) 2025-12-18 20:39:58','dibatalkan','2025-12-18 20:06:04','vq',NULL,NULL,'2025-12-18 13:05:16'),
(62,2,12,'inf c','fiki','matkul','','2025-12-19','06:45:00','07:00:00',NULL,'Dibatalkan oleh mahasiswa (fiki) 2025-12-18 20:21:17','dibatalkan','2025-12-18 20:11:28','vq',NULL,NULL,'2025-12-18 13:09:07'),
(63,2,9,'inf c','fiki','matkul','','2025-12-20','08:00:00','09:00:00',NULL,'| Disetujui admin vq 2025-12-20 12:52:58','disetujui','2025-12-20 12:52:58','vq',NULL,NULL,'2025-12-18 13:20:26'),
(64,2,12,'inf c','fiki','matkul','','2025-12-18','15:30:00','16:00:00',NULL,'| Disetujui admin vq 2025-12-20 12:53:36','disetujui','2025-12-20 12:53:36','vq',NULL,NULL,'2025-12-18 13:23:45'),
(65,2,6,'INF B','fiki','KERKOM','','2025-12-19','06:45:00','07:00:00',NULL,NULL,'pending',NULL,NULL,NULL,NULL,'2025-12-18 13:25:02'),
(66,2,6,'INFOR C','fiki','KARKOM','','2025-12-22','06:45:00','07:00:00',NULL,'Pending dibatalkan oleh admin (vq) 2025-12-20 11:34:37','dibatalkan',NULL,NULL,NULL,NULL,'2025-12-18 13:44:07'),
(67,2,6,'INFOR C','fiki','MATKUL','','2025-12-19','15:30:00','16:00:00',NULL,'| Disetujui admin vq 2025-12-18 20:57:27','disetujui','2025-12-18 20:57:27','vq',NULL,NULL,'2025-12-18 13:56:41'),
(68,1,6,'INFCCCCC','vq','MATKUL BOSSSSSS','','2025-12-18','07:00:00','07:15:00',NULL,NULL,'pending',NULL,NULL,NULL,NULL,'2025-12-18 14:11:11'),
(69,2,6,'INF ABC','fiki','MATKUL BOSSSS','','2025-12-20','07:00:00','07:15:00',NULL,'Pending dibatalkan oleh mahasiswa (fiki) 2025-12-18 21:13:28','dibatalkan',NULL,NULL,NULL,NULL,'2025-12-18 14:12:08'),
(70,2,6,'INFFFF','fiki','MTKKKK','','2025-12-20','07:00:00','07:15:00',NULL,'| Disetujui admin vq 2025-12-18 21:16:08','disetujui','2025-12-18 21:16:08','vq',NULL,NULL,'2025-12-18 14:15:30'),
(71,2,6,'inf c','fiki','matkul','','2025-12-20','12:52:00','13:37:00',NULL,'| Disetujui admin vq 2025-12-20 12:53:42','disetujui','2025-12-20 12:53:42','vq',NULL,NULL,'2025-12-20 05:52:22');
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `jadwal_mingguan`
--

DROP TABLE IF EXISTS `jadwal_mingguan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jadwal_mingguan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kelas_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `matkul` varchar(100) NOT NULL,
  `dosen` varchar(100) DEFAULT NULL,
  `day_of_week` tinyint(4) NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_jm_kelas` (`kelas_id`),
  KEY `fk_jm_room` (`room_id`),
  CONSTRAINT `fk_jm_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`),
  CONSTRAINT `fk_jm_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jadwal_mingguan`
--

LOCK TABLES `jadwal_mingguan` WRITE;
/*!40000 ALTER TABLE `jadwal_mingguan` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `jadwal_mingguan` VALUES
(1,1,6,'Basis Data','Dosen A',1,'08:00:00','09:40:00','2025-12-18 11:34:33'),
(2,1,6,'Struktur Data','Dosen B',2,'10:00:00','11:40:00','2025-12-18 11:34:33'),
(3,1,7,'Pemrograman Web','Dosen C',3,'08:00:00','09:40:00','2025-12-18 11:34:33'),
(4,1,6,'Sistem Operasi','Dosen D',4,'13:00:00','14:40:00','2025-12-18 11:34:33'),
(5,1,7,'Jaringan Komputer','Dosen E',5,'10:00:00','11:40:00','2025-12-18 11:34:33'),
(6,2,7,'Basis Data','Dosen A',1,'10:00:00','11:40:00','2025-12-18 11:34:48'),
(7,2,7,'Struktur Data','Dosen B',2,'08:00:00','09:40:00','2025-12-18 11:34:48'),
(8,2,6,'Pemrograman Web','Dosen C',3,'10:00:00','11:40:00','2025-12-18 11:34:48'),
(9,2,7,'Sistem Operasi','Dosen D',4,'08:00:00','09:40:00','2025-12-18 11:34:48'),
(10,2,6,'Jaringan Komputer','Dosen E',5,'13:00:00','14:40:00','2025-12-18 11:34:48'),
(11,1,34,'web','dosen y',5,'13:30:00','13:40:00','2025-12-18 13:28:15');
/*!40000 ALTER TABLE `jadwal_mingguan` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `kelas`
--

DROP TABLE IF EXISTS `kelas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `kelas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(50) NOT NULL,
  `prodi` varchar(50) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kelas`
--

LOCK TABLES `kelas` WRITE;
/*!40000 ALTER TABLE `kelas` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `kelas` VALUES
(1,'IF-2A','Informatika',1),
(2,'IF-2B','Informatika',1);
/*!40000 ALTER TABLE `kelas` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `rooms`
--

DROP TABLE IF EXISTS `rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_ruang` varchar(20) NOT NULL,
  `gedung` enum('D','S') NOT NULL,
  `lantai` int(11) NOT NULL,
  `nama_ruang` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `fasilitas` text DEFAULT NULL,
  `kapasitas` int(11) DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_kode_ruang` (`kode_ruang`),
  KEY `idx_gedung_lantai` (`gedung`,`lantai`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rooms`
--

LOCK TABLES `rooms` WRITE;
/*!40000 ALTER TABLE `rooms` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `rooms` VALUES
(6,'1D.1','D',1,'Ruang 1D.1','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(7,'1D.2','D',1,'Ruang 1D.2','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(8,'1D.3','D',1,'Ruang 1D.3','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(9,'1D.4','D',1,'Ruang 1D.4','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(10,'1D.5','D',1,'Ruang 1D.5','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(11,'1D.6','D',1,'Ruang 1D.6','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(12,'1D.7','D',1,'Ruang 1D.7','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(13,'2D.1','D',2,'Ruang 2D.1','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(14,'2D.2','D',2,'Ruang 2D.2','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(15,'2D.3','D',2,'Ruang 2D.3','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(16,'2D.4','D',2,'Ruang 2D.4','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(17,'2D.5','D',2,'Ruang 2D.5','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(18,'2D.6','D',2,'Ruang 2D.6','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(19,'2D.7','D',2,'Ruang 2D.7','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(20,'3D.1','D',3,'Ruang 3D.1','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(21,'3D.2','D',3,'Ruang 3D.2','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(22,'3D.3','D',3,'Ruang 3D.3','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(23,'3D.4','D',3,'Ruang 3D.4','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(24,'3D.5','D',3,'Ruang 3D.5','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(25,'3D.6','D',3,'Ruang 3D.6','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(26,'3D.7','D',3,'Ruang 3D.7','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(27,'4D.1','D',4,'Ruang 4D.1','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(28,'4D.2','D',4,'Ruang 4D.2','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(29,'4D.3','D',4,'Ruang 4D.3','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(30,'4D.4','D',4,'Ruang 4D.4','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(31,'4D.5','D',4,'Ruang 4D.5','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(32,'4D.6','D',4,'Ruang 4D.6','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(33,'4D.7','D',4,'Ruang 4D.7','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(34,'1S.1','S',1,'Ruang 1S.1','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(35,'1S.2','S',1,'Ruang 1S.2','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(36,'1S.3','S',1,'Ruang 1S.3','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(37,'1S.4','S',1,'Ruang 1S.4','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(38,'1S.5','S',1,'Ruang 1S.5','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(39,'1S.6','S',1,'Ruang 1S.6','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(40,'1S.7','S',1,'Ruang 1S.7','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(41,'2S.1','S',2,'Ruang 2S.1','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(42,'2S.2','S',2,'Ruang 2S.2','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(43,'2S.3','S',2,'Ruang 2S.3','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(44,'2S.4','S',2,'Ruang 2S.4','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(45,'2S.5','S',2,'Ruang 2S.5','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(46,'2S.6','S',2,'Ruang 2S.6','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(47,'2S.7','S',2,'Ruang 2S.7','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(48,'3S.1','S',3,'Ruang 3S.1','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(49,'3S.2','S',3,'Ruang 3S.2','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(50,'3S.3','S',3,'Ruang 3S.3','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(51,'3S.4','S',3,'Ruang 3S.4','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(52,'3S.5','S',3,'Ruang 3S.5','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(53,'3S.6','S',3,'Ruang 3S.6','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(54,'3S.7','S',3,'Ruang 3S.7','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(55,'4S.1','S',4,'Ruang 4S.1','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(56,'4S.2','S',4,'Ruang 4S.2','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(57,'4S.3','S',4,'Ruang 4S.3','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(58,'4S.4','S',4,'Ruang 4S.4','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(59,'4S.5','S',4,'Ruang 4S.5','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(60,'4S.6','S',4,'Ruang 4S.6','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1),
(61,'4S.7','S',4,'Ruang 4S.7','Ruang kelas besar, akses mudah, dekat lobby utama.','AC,Whiteboard,WiFi',40,1);
/*!40000 ALTER TABLE `rooms` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `level` enum('admin','mahasiswa') NOT NULL,
  `kelas_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_users_kelas` (`kelas_id`),
  CONSTRAINT `fk_users_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `users` VALUES
(1,'vq','$2y$12$Txcl3slq7f0O/OjdjHZtcu3kgF0P5/OpJUm5SPu.9NSTGAVfgRhO2','admin',NULL),
(2,'fiki','$2y$12$d3TTCYlKLMIXFMyKHmZg9Of8tcFzeSkrg./sdhCmoveW2aPELTjL.','mahasiswa',2);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
commit;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2025-12-20 13:37:08
