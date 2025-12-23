-- MySQL dump 10.13  Distrib 8.0.44, for Linux (x86_64)
--
-- Host: localhost    Database: osAllocation
-- ------------------------------------------------------
-- Server version	8.0.44-0ubuntu0.24.04.2

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `personalInfo`
--

DROP TABLE IF EXISTS `personalInfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personalInfo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `firstname` varchar(30) DEFAULT NULL,
  `lastname` varchar(40) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_user` (`user_id`),
  CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personalInfo`
--

LOCK TABLES `personalInfo` WRITE;
/*!40000 ALTER TABLE `personalInfo` DISABLE KEYS */;
INSERT INTO `personalInfo` VALUES (1,'ANJARAVONJISOA','Mandresy Clemence','mandresyclemenceanjaravonjisoa@gmail.com',1),(2,'HARITSARA','Lucas','lucas@gmail.com',2),(3,'RAZAKAMANANA','Heriniaina','nasa@gmail.com',4),(4,'mit','MISA','mit@gmail.com',5);
/*!40000 ALTER TABLE `personalInfo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resources`
--

DROP TABLE IF EXISTS `resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `resources` (
  `id` int NOT NULL AUTO_INCREMENT,
  `os_name` varchar(30) DEFAULT NULL,
  `cpu` int DEFAULT NULL,
  `ram` int DEFAULT NULL,
  `disk` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_user1` (`user_id`),
  CONSTRAINT `fk_user1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resources`
--

LOCK TABLES `resources` WRITE;
/*!40000 ALTER TABLE `resources` DISABLE KEYS */;
INSERT INTO `resources` VALUES (1,'ubuntu22',4,8,55,4),(2,'fedora',5,6,17,4),(3,'ubuntu22',4,8,55,NULL),(4,'ubuntu22',7,8,20,1),(5,'ubuntu22',7,8,20,1),(6,'fedora',2,4,10,1),(7,'ubuntu24',4,4,100,1),(8,'ubuntu24',2,4,12,1),(9,'ubuntu24',3,12,12,1),(10,'ubuntu24',12,12,12,1),(11,'ubuntu24',12,12,12,1),(12,'ubuntu24',12,12,12,1),(13,'ubuntu22',12,12,12,1),(14,'ubuntu24',4,4,128,2),(15,'ubuntu24',2,4,20,NULL),(16,'ubuntu22',4,4,12,2),(17,'ubuntu24',4,4,30,2),(18,'ubuntu24',4,4,30,2),(19,'ubuntu22',2,4,13,2);
/*!40000 ALTER TABLE `resources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `templates`
--

DROP TABLE IF EXISTS `templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `templates` (
  `vmid` int NOT NULL,
  `os_name` varchar(30) DEFAULT NULL,
  `os_version` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`vmid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `templates`
--

LOCK TABLES `templates` WRITE;
/*!40000 ALTER TABLE `templates` DISABLE KEYS */;
INSERT INTO `templates` VALUES (102,'Ubuntu','Noble Numbat LTS 24.04.3');
/*!40000 ALTER TABLE `templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(30) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','$2y$10$piXOpCnMPPMgAxn/M9KP8ej8PAIZWxYjm0CuFvlCeE.aGZCnAKgk6'),(2,'Lucas','$2y$10$nX5SZPLB8lwJnvNssidTpu.LiIrBN9ui/VWn3Y6MpRWmBZ4r7bkFC'),(4,'nasandratriniavo','$2y$10$du6qdKRFzur7of0YRgBILOtNErmunZ/7tHT3s3/46jHJBWSZnXEZe'),(5,'mit','$2y$12$g/fSmv0hQ777puzR1mpzwOfysWqT2tHoht1KKC9vJvfyBTsIFSnJ.');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `virtualMachine`
--

DROP TABLE IF EXISTS `virtualMachine`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `virtualMachine` (
  `vmid` int NOT NULL AUTO_INCREMENT,
  `date_start` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int DEFAULT NULL,
  `hostname` varchar(40) DEFAULT NULL,
  `cpu` int DEFAULT NULL,
  `ram` int DEFAULT NULL,
  `disk` int DEFAULT NULL,
  `ip_address` varchar(16) DEFAULT NULL,
  `template_id` int DEFAULT NULL,
  `root_passwd` varchar(255) DEFAULT NULL,
  `data` int DEFAULT NULL,
  `status` varchar(20) DEFAULT 'stop',
  PRIMARY KEY (`vmid`)
) ENGINE=InnoDB AUTO_INCREMENT=142 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `virtualMachine`
--

LOCK TABLES `virtualMachine` WRITE;
/*!40000 ALTER TABLE `virtualMachine` DISABLE KEYS */;
INSERT INTO `virtualMachine` VALUES (101,'2025-12-22 06:27:39',1,'ecs2',4,4,30,'192.168.11.151',102,'geek',NULL,'stop'),(103,'2025-12-22 05:33:41',1,'Server',8,8,40,'192.168.11.152',102,'root',NULL,'stop'),(104,'2025-12-19 14:54:35',5,'MIT',8,4,20,'192.168.11.153',102,'mit',NULL,'running');
/*!40000 ALTER TABLE `virtualMachine` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-23 10:36:30
