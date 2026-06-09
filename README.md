-- MySQL dump 10.13  Distrib 9.3.0, for Win64 (x86_64)
--
-- Host: localhost    Database: db_decaissement
-- ------------------------------------------------------
-- Server version	9.3.0

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
-- Current Database: `db_decaissement`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `db_decaissement` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `db_decaissement`;

--
-- Table structure for table `admindemandes`
--

DROP TABLE IF EXISTS `admindemandes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admindemandes` (
  `id_colonne` int NOT NULL AUTO_INCREMENT,
  `champ` varchar(50) NOT NULL,
  `libelle` varchar(50) NOT NULL,
  `ordre` int DEFAULT '0',
  PRIMARY KEY (`id_colonne`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admindemandes`
--

LOCK TABLES `admindemandes` WRITE;
/*!40000 ALTER TABLE `admindemandes` DISABLE KEYS */;
INSERT INTO `admindemandes` VALUES (1,'id_demande','ID',1),(2,'objet','Objet',2),(3,'montant','Montant',3),(4,'demandeur','Demandeur',4),(5,'departement','D├⌐partement',5),(6,'statut','Statut',6),(7,'date_creation','Date cr├⌐ation',7);
/*!40000 ALTER TABLE `admindemandes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `adminlogs`
--

DROP TABLE IF EXISTS `adminlogs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `adminlogs` (
  `id_colonne` int NOT NULL AUTO_INCREMENT,
  `champ` varchar(50) NOT NULL,
  `libelle` varchar(50) NOT NULL,
  `ordre` int DEFAULT '0',
  PRIMARY KEY (`id_colonne`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `adminlogs`
--

LOCK TABLES `adminlogs` WRITE;
/*!40000 ALTER TABLE `adminlogs` DISABLE KEYS */;
INSERT INTO `adminlogs` VALUES (1,'id_log','ID',1),(2,'date_action','Date',2),(3,'utilisateur','Utilisateur',3),(4,'action','Action',4),(5,'ancien_statut','Ancien statut',5),(6,'nouveau_statut','Nouveau statut',6),(7,'justification','Justification',7);
/*!40000 ALTER TABLE `adminlogs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `adminutilisateurs`
--

DROP TABLE IF EXISTS `adminutilisateurs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `adminutilisateurs` (
  `id_colonne` int NOT NULL AUTO_INCREMENT,
  `champ` varchar(50) NOT NULL,
  `libelle` varchar(50) NOT NULL,
  `ordre` int DEFAULT '0',
  PRIMARY KEY (`id_colonne`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `adminutilisateurs`
--

LOCK TABLES `adminutilisateurs` WRITE;
/*!40000 ALTER TABLE `adminutilisateurs` DISABLE KEYS */;
INSERT INTO `adminutilisateurs` VALUES (1,'id_utilisateur','ID',1),(2,'nom','Nom',2),(3,'prenom','Pr├⌐nom',3),(4,'email','Email',4),(5,'role','R├┤le',5),(6,'departement','D├⌐partement',6),(7,'autoriser','Autoris├⌐',7),(8,'date_creation','Date cr├⌐ation',8);
/*!40000 ALTER TABLE `adminutilisateurs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blocs`
--

DROP TABLE IF EXISTS `blocs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blocs` (
  `id_bloc` int NOT NULL AUTO_INCREMENT,
  `id_role` int NOT NULL,
  `titre` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `ordre` int DEFAULT '0',
  PRIMARY KEY (`id_bloc`),
  KEY `id_role` (`id_role`),
  CONSTRAINT `blocs_ibfk_1` FOREIGN KEY (`id_role`) REFERENCES `roles` (`id_role`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blocs`
--

LOCK TABLES `blocs` WRITE;
/*!40000 ALTER TABLE `blocs` DISABLE KEYS */;
INSERT INTO `blocs` VALUES (1,1,'Espace Administrateur','Acc├¿s complet',1),(2,2,'Espace Chef','Validation et suivi',1);
/*!40000 ALTER TABLE `blocs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `demandes`
--

DROP TABLE IF EXISTS `demandes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `demandes` (
  `id_demande` int NOT NULL AUTO_INCREMENT,
  `objet` text NOT NULL,
  `montant_demande` decimal(10,2) NOT NULL,
  `devise` varchar(3) NOT NULL DEFAULT 'USD',
  `date_creation` datetime NOT NULL,
  `id_demandeur` int NOT NULL,
  `statut` enum('pending','pendinglogistique','facturee','confirmee','rejetee','annulee') NOT NULL DEFAULT 'pending',
  `piece_jointe` varchar(255) DEFAULT NULL,
  `date_validation_chef` datetime DEFAULT NULL,
  `date_facture` datetime DEFAULT NULL,
  `date_decaissement` datetime DEFAULT NULL,
  `justification_rejet` text,
  `renvoyee` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id_demande`),
  KEY `id_demandeur` (`id_demandeur`),
  CONSTRAINT `demandes_ibfk_1` FOREIGN KEY (`id_demandeur`) REFERENCES `utilisateurs` (`id_utilisateur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `demandes`
--

LOCK TABLES `demandes` WRITE;
/*!40000 ALTER TABLE `demandes` DISABLE KEYS */;
/*!40000 ALTER TABLE `demandes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departements`
--

DROP TABLE IF EXISTS `departements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departements` (
  `id_departement` int NOT NULL AUTO_INCREMENT,
  `departement` varchar(100) NOT NULL,
  PRIMARY KEY (`id_departement`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departements`
--

LOCK TABLES `departements` WRITE;
/*!40000 ALTER TABLE `departements` DISABLE KEYS */;
INSERT INTO `departements` VALUES (1,'Informatique'),(2,'ama');
/*!40000 ALTER TABLE `departements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menus`
--

DROP TABLE IF EXISTS `menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `menus` (
  `id_menu` int NOT NULL AUTO_INCREMENT,
  `id_role` int NOT NULL,
  `libelle` varchar(50) NOT NULL,
  `url` varchar(100) NOT NULL,
  `ordre` int DEFAULT '0',
  PRIMARY KEY (`id_menu`),
  KEY `id_role` (`id_role`),
  CONSTRAINT `menus_ibfk_1` FOREIGN KEY (`id_role`) REFERENCES `roles` (`id_role`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menus`
--

LOCK TABLES `menus` WRITE;
/*!40000 ALTER TABLE `menus` DISABLE KEYS */;
INSERT INTO `menus` VALUES (1,1,'Gestion utilisateurs','admin/utilisateurs.php',1),(2,1,'Toutes les demandes','demandes/liste.php',2),(3,1,'Tous les logs','logs/historique.php',3),(4,2,'Demandes ├á valider','demandes/attente_validation.php',1),(5,2,'Demandes rejet├⌐es','demandes/rejetees.php',2),(6,2,'Nouvelle demande','demandes/creer.php',3),(7,3,'Demandes ├á traiter','demandes/attente_logistique.php',1),(8,3,'Facturations','demandes/facturees.php',2),(9,3,'D├⌐caissements','demandes/decaissements.php',3),(10,4,'Nouvelle demande','demandes/creer.php',1),(11,4,'Mes demandes','demandes/mes_demandes.php',2);
/*!40000 ALTER TABLE `menus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id_role` int NOT NULL AUTO_INCREMENT,
  `nom_role` varchar(20) NOT NULL,
  PRIMARY KEY (`id_role`),
  UNIQUE KEY `nom_role` (`nom_role`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin'),(2,'chef'),(3,'decaisseur'),(4,'demandeur');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `utilisateurs` (
  `id_utilisateur` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mdp` varchar(255) NOT NULL,
  `id_role` int NOT NULL,
  `autoriser` tinyint(1) DEFAULT '1',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `id_departement` int DEFAULT NULL,
  PRIMARY KEY (`id_utilisateur`),
  UNIQUE KEY `email` (`email`),
  KEY `id_role` (`id_role`),
  KEY `id_departement` (`id_departement`),
  CONSTRAINT `utilisateurs_ibfk_1` FOREIGN KEY (`id_role`) REFERENCES `roles` (`id_role`),
  CONSTRAINT `utilisateurs_ibfk_2` FOREIGN KEY (`id_departement`) REFERENCES `departements` (`id_departement`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `utilisateurs`
--

LOCK TABLES `utilisateurs` WRITE;
/*!40000 ALTER TABLE `utilisateurs` DISABLE KEYS */;
INSERT INTO `utilisateurs` VALUES (1,'Mylord','Admin','admin@gmail.com','$2y$12$7dkTEsOSdTUZ2JTrSIJ3zedvReTf51l9H5y0zHbcx1zXhuS7qV2PW',1,1,'2026-06-07 20:21:53',NULL),(3,'adom','aa','adom@gmail.com','$2y$12$N0kbos5gyvgjuFNMsbfx8.yaE9qV4WvifENWT7sRJh2dMzfheCeCi',4,1,'2026-06-07 21:23:32',1),(5,'golo','paso','golo@gmail.com','$2y$12$Z3i5c9Lw1GIbHf676QKeC.YncwlDPe6Ep5IHoM9xTDUMVG0.SviVK',2,1,'2026-06-08 20:52:56',1);
/*!40000 ALTER TABLE `utilisateurs` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-09 23:28:53
