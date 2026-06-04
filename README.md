-- =========================================
-- DATABASE SETUP : db_decaissement
-- =========================================

CREATE DATABASE IF NOT EXISTS db_decaissement;
USE db_decaissement;

-- =========================================
-- TABLE : roles
-- =========================================
CREATE TABLE roles (
  id_role INT AUTO_INCREMENT PRIMARY KEY,
  nom_role VARCHAR(20) NOT NULL UNIQUE
);

INSERT INTO roles (id_role, nom_role) VALUES
(1, 'admin'),
(2, 'chef'),
(3, 'decaisseur'),
(4, 'demandeur');

-- =========================================
-- TABLE : departements
-- =========================================
CREATE TABLE departements (
  id_departement INT AUTO_INCREMENT PRIMARY KEY,
  departement VARCHAR(100) NOT NULL
);

INSERT INTO departements (departement) VALUES
('Informatique');

-- =========================================
-- TABLE : utilisateurs
-- =========================================
CREATE TABLE utilisateurs (
  id_utilisateur INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL,
  prenom VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  mdp VARCHAR(255) NOT NULL,
  id_role INT NOT NULL,
  autoriser TINYINT(1) DEFAULT 1,
  date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  id_departement INT DEFAULT NULL,
  FOREIGN KEY (id_role) REFERENCES roles(id_role),
  FOREIGN KEY (id_departement) REFERENCES departements(id_departement)
);

-- =========================================
-- TABLE : admindemandes
-- =========================================
CREATE TABLE admindemandes (
  id_colonne INT AUTO_INCREMENT PRIMARY KEY,
  champ VARCHAR(50) NOT NULL,
  libelle VARCHAR(50) NOT NULL,
  ordre INT DEFAULT 0
);

INSERT INTO admindemandes (champ, libelle, ordre) VALUES
('id_demande','ID',1),
('objet','Objet',2),
('montant','Montant',3),
('demandeur','Demandeur',4),
('departement','Département',5),
('statut','Statut',6),
('date_creation','Date création',7);

-- =========================================
-- TABLE : adminlogs
-- =========================================
CREATE TABLE adminlogs (
  id_colonne INT AUTO_INCREMENT PRIMARY KEY,
  champ VARCHAR(50) NOT NULL,
  libelle VARCHAR(50) NOT NULL,
  ordre INT DEFAULT 0
);

INSERT INTO adminlogs (champ, libelle, ordre) VALUES
('id_log','ID',1),
('date_action','Date',2),
('utilisateur','Utilisateur',3),
('action','Action',4),
('ancien_statut','Ancien statut',5),
('nouveau_statut','Nouveau statut',6),
('justification','Justification',7);

-- =========================================
-- TABLE : adminutilisateurs
-- =========================================
CREATE TABLE adminutilisateurs (
  id_colonne INT AUTO_INCREMENT PRIMARY KEY,
  champ VARCHAR(50) NOT NULL,
  libelle VARCHAR(50) NOT NULL,
  ordre INT DEFAULT 0
);

INSERT INTO adminutilisateurs (champ, libelle, ordre) VALUES
('id_utilisateur','ID',1),
('nom','Nom',2),
('prenom','Prénom',3),
('email','Email',4),
('role','Rôle',5),
('departement','Département',6),
('autoriser','Autorisé',7),
('date_creation','Date création',8);

-- =========================================
-- TABLE : blocs
-- =========================================
CREATE TABLE blocs (
  id_bloc INT AUTO_INCREMENT PRIMARY KEY,
  id_role INT NOT NULL,
  titre VARCHAR(100) NOT NULL,
  description TEXT NOT NULL,
  ordre INT DEFAULT 0,
  FOREIGN KEY (id_role) REFERENCES roles(id_role)
);

INSERT INTO blocs (id_role, titre, description, ordre) VALUES
(1,'Espace Administrateur','Accès complet',1),
(2,'Espace Chef','Validation et suivi',1);

-- =========================================
-- TABLE : menus
-- =========================================
CREATE TABLE menus (
  id_menu INT AUTO_INCREMENT PRIMARY KEY,
  id_role INT NOT NULL,
  libelle VARCHAR(50) NOT NULL,
  url VARCHAR(100) NOT NULL,
  ordre INT DEFAULT 0,
  FOREIGN KEY (id_role) REFERENCES roles(id_role)
);

INSERT INTO menus (id_role, libelle, url, ordre) VALUES
(1,'Gestion utilisateurs','admin/utilisateurs.php',1),
(1,'Toutes les demandes','demandes/liste.php',2),
(1,'Tous les logs','logs/historique.php',3),

(2,'Demandes à valider','demandes/attente_validation.php',1),
(2,'Demandes rejetées','demandes/rejetees.php',2),
(2,'Nouvelle demande','demandes/creer.php',3),

(3,'Demandes à traiter','demandes/attente_logistique.php',1),
(3,'Facturations','demandes/facturees.php',2),
(3,'Décaissements','demandes/decaissements.php',3),

(4,'Nouvelle demande','demandes/creer.php',1),
(4,'Mes demandes','demandes/mes_demandes.php',2);