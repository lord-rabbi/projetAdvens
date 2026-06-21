-- =============================================
-- BASE DE DONNEES - SYSTEME DE GESTION DE DEMANDES
-- =============================================

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS db_decaissement 
CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;

USE db_decaissement;

-- =============================================
-- TABLE roles
-- =============================================
DROP TABLE IF EXISTS roles;
CREATE TABLE roles (
    id_role INT PRIMARY KEY AUTO_INCREMENT,
    nom_role VARCHAR(20) UNIQUE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO roles (id_role, nom_role) VALUES 
(1, 'admin'),
(2, 'chef'),
(3, 'decaisseur'),
(4, 'demandeur');

-- =============================================
-- TABLE departements
-- =============================================
DROP TABLE IF EXISTS departements;
CREATE TABLE departements (
    id_departement INT PRIMARY KEY AUTO_INCREMENT,
    departement VARCHAR(100) UNIQUE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO departements (departement) VALUES 
('Informatique'),
('Finance'),
('Marketing'),
('Ressources Humaines'),
('Commercial'),
('Logistique');

-- =============================================
-- TABLE utilisateurs
-- =============================================
DROP TABLE IF EXISTS utilisateurs;
CREATE TABLE utilisateurs (
    id_utilisateur INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    mdp VARCHAR(255) NOT NULL,
    id_role INT NOT NULL,
    id_departement INT NULL,
    autoriser BOOLEAN DEFAULT TRUE,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_role) REFERENCES roles(id_role),
    FOREIGN KEY (id_departement) REFERENCES departements(id_departement) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- TABLE demandes
-- =============================================
DROP TABLE IF EXISTS demandes;
CREATE TABLE demandes (
    id_demande INT PRIMARY KEY AUTO_INCREMENT,
    objet TEXT NOT NULL,
    montant_demande DECIMAL(15,2) NOT NULL,
    devise VARCHAR(3) DEFAULT 'USD',
    date_creation DATETIME NOT NULL,
    id_demandeur INT NOT NULL,
    statut ENUM('pending', 'pendinglogistique', 'facturee', 'confirmee', 'rejetee', 'annulee') DEFAULT 'pending',
    piece_jointe VARCHAR(255) NULL,
    date_validation_chef DATETIME NULL,
    date_facture DATETIME NULL,
    date_decaissement DATETIME NULL,
    justification_rejet TEXT NULL,
    renvoyee BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (id_demandeur) REFERENCES utilisateurs(id_utilisateur)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- TABLE logs
-- =============================================
DROP TABLE IF EXISTS logs;
CREATE TABLE logs (
    id_log INT PRIMARY KEY AUTO_INCREMENT,
    date_action DATETIME NOT NULL,
    id_utilisateur INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    statut VARCHAR(50) NOT NULL,
    justification TEXT NULL,
    id_demande INT NULL,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur),
    FOREIGN KEY (id_demande) REFERENCES demandes(id_demande) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- INDEX pour les performances
-- =============================================
CREATE INDEX idx_demandes_statut ON demandes(statut);
CREATE INDEX idx_demandes_demandeur ON demandes(id_demandeur);
CREATE INDEX idx_demandes_date ON demandes(date_creation);
CREATE INDEX idx_logs_utilisateur ON logs(id_utilisateur);
CREATE INDEX idx_logs_demande ON logs(id_demande);
CREATE INDEX idx_logs_date ON logs(date_action);
CREATE INDEX idx_utilisateurs_role ON utilisateurs(id_role);
CREATE INDEX idx_utilisateurs_departement ON utilisateurs(id_departement);
CREATE INDEX idx_utilisateurs_email ON utilisateurs(email);
