/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.13-MariaDB, for Linux (x86_64)
--
-- Host: 127.0.0.1    Database: sbiblioteca
-- ------------------------------------------------------
-- Server version	10.11.13-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `emprestimos`
--

DROP TABLE IF EXISTS `emprestimos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `emprestimos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `livro_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `data_emprestimo` date DEFAULT NULL,
  `data_devolucao` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `livro_id` (`livro_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `emprestimos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `emprestimos`
--

LOCK TABLES `emprestimos` WRITE;
/*!40000 ALTER TABLE `emprestimos` DISABLE KEYS */;
INSERT INTO `emprestimos` VALUES
(1,1,7,'2025-01-30','2025-01-31'),
(2,2,7,'2025-01-29','2025-01-07'),
(3,3,6,'2025-01-06','2025-02-07'),
(4,5,7,'2025-01-07','2025-01-30'),
(5,7,8,'2025-01-01','2025-01-30'),
(6,7,7,'2025-01-06','2025-01-30'),
(7,7,8,'2025-01-28','2025-02-01'),
(8,1,8,'2025-01-28','2025-02-01'),
(9,7,8,'2025-01-09','2025-02-03'),
(10,7,8,'2025-01-08','2025-02-03'),
(11,7,8,'2025-02-02','2025-02-11'),
(12,1,5,'2025-02-04','2025-02-12'),
(13,7,8,'2025-02-21','2025-02-22'),
(14,1,9,'2025-02-20','2025-02-23'),
(15,7,9,'2025-02-11','2025-02-20'),
(16,7,10,'2025-03-05','2025-03-15'),
(17,7,9,'2025-03-01','2025-03-03'),
(18,1,9,'2025-03-13','2025-03-13'),
(19,9,7,'2025-03-13','2025-03-13'),
(20,7,8,'2025-03-13','2025-03-13'),
(21,7,8,'2025-03-06','2025-03-15'),
(22,1,5,'2025-03-16','2025-03-16'),
(23,7,8,'2025-03-08','2025-03-16'),
(24,7,8,'2025-03-16','2025-03-16'),
(25,7,9,'2025-03-17','2025-03-17'),
(26,9,11,'2025-05-16','2025-05-18'),
(27,18,8,'2026-02-16','2026-02-16'),
(28,7,8,'2026-02-17','2026-02-17');
/*!40000 ALTER TABLE `emprestimos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `livros`
--

DROP TABLE IF EXISTS `livros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `livros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `autor` varchar(255) NOT NULL,
  `ano_publicacao` int(11) DEFAULT NULL,
  `disponivel` tinyint(1) DEFAULT 1,
  `ativo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `livros`
--

LOCK TABLES `livros` WRITE;
/*!40000 ALTER TABLE `livros` DISABLE KEYS */;
INSERT INTO `livros` VALUES
(1,'Biblia','Deus',1600,1,0),
(7,'Livro de Engenharia do 1ª Edição','Engenheiro Cliris j. Pucuta',2016,1,0),
(9,'Biblia','Deus',1000,1,0),
(18,'Harry Potter e a pedra filosofal','J.S rollings',2004,1,1),
(20,'Biblia','Deus',2024,1,1);
/*!40000 ALTER TABLE `livros` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `solicitacoes_emprestimo`
--

DROP TABLE IF EXISTS `solicitacoes_emprestimo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `solicitacoes_emprestimo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `livro_id` int(11) DEFAULT NULL,
  `data_solicitacao` date DEFAULT NULL,
  `status` enum('pendente','aprovado','rejeitado') DEFAULT 'pendente',
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `livro_id` (`livro_id`),
  CONSTRAINT `solicitacoes_emprestimo_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `solicitacoes_emprestimo_ibfk_2` FOREIGN KEY (`livro_id`) REFERENCES `livros` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `solicitacoes_emprestimo`
--

LOCK TABLES `solicitacoes_emprestimo` WRITE;
/*!40000 ALTER TABLE `solicitacoes_emprestimo` DISABLE KEYS */;
INSERT INTO `solicitacoes_emprestimo` VALUES
(1,7,1,'2025-02-01','pendente'),
(2,7,7,'2025-02-23','pendente'),
(3,5,7,'2025-02-23','pendente'),
(4,5,7,'2025-03-13','pendente'),
(5,5,1,'2025-03-13','pendente'),
(6,5,1,'2025-03-13','pendente'),
(7,5,1,'2025-03-13','pendente'),
(8,5,1,'2025-03-13','pendente'),
(9,5,1,'2025-03-13','pendente'),
(10,5,1,'2025-03-13','pendente');
/*!40000 ALTER TABLE `solicitacoes_emprestimo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `nivel_acesso` enum('admin','bibliotecario','usuario') DEFAULT 'usuario',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES
(5,'Admin','admin@example.com','senha123','admin'),
(6,'Bibliot','bibliot@example.com','senha1234','bibliotecario'),
(7,'User','user@example.com','senha12345','usuario'),
(8,'Mario Cambambe','mario@gmail.com','MARIO','usuario'),
(9,'Edson André ','edson@example.com','edson123','bibliotecario'),
(10,'Professora Marliés','Marlis@example.com','senha123','admin'),
(11,'Hélio Caldeira','helio@example.com','$2y$10$VTEAZLtcawpBDQOs8.HoqOgXjUcFWgktkpgMG5W9ZJpgqSyRVMCae','admin');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-14  8:21:17
