CREATE TABLE departements (
    id_departement INT PRIMARY KEY AUTO_INCREMENT,
    departement VARCHAR(100) NOT NULL
);

CREATE TABLE roles (
    id_role INT PRIMARY KEY AUTO_INCREMENT,
    nom_role VARCHAR(20) NOT NULL UNIQUE
);

INSERT INTO roles (id_role, nom_role) VALUES
(1, 'admin'),
(2, 'chef'),
(3, 'decaisseur'),
(4, 'demandeur');

le script de la bdd:
CREATE TABLE menus (
    id_menu INT PRIMARY KEY AUTO_INCREMENT,
    id_role INT NOT NULL,
    libelle VARCHAR(50) NOT NULL,
    url VARCHAR(100) NOT NULL,
    ordre INT DEFAULT 0
);

CREATE TABLE blocs (
    id_bloc INT PRIMARY KEY AUTO_INCREMENT,
    id_role INT NOT NULL,
    titre VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    ordre INT DEFAULT 0
);


INSERT INTO menus (id_role, libelle, url, ordre) VALUES
(1, 'Gestion utilisateurs', 'admin/utilisateurs.php', 1),
(1, 'Toutes les demandes', 'demandes/liste.php', 2),
(1, 'Tous les logs', 'logs/historique.php', 3);

INSERT INTO blocs (id_role, titre, description, ordre) VALUES
(1, 'Espace Administrateur', 'Accès complet', 1);

INSERT INTO menus (id_role, libelle, url, ordre) VALUES
(2, 'Demandes à valider', 'demandes/attente_validation.php', 1),
(2, 'Demandes rejetées', 'demandes/rejetees.php', 2),
(2, 'Nouvelle demande', 'demandes/creer.php', 3);

INSERT INTO blocs (id_role, titre, description, ordre) VALUES
(2, 'Espace Chef', 'Validation et suivi', 1);

CREATE TABLE adminUtilisateurs (
    id_colonne INT PRIMARY KEY AUTO_INCREMENT,
    champ VARCHAR(50) NOT NULL,
    libelle VARCHAR(50) NOT NULL,
    ordre INT DEFAULT 0
);

CREATE TABLE adminDemandes (
    id_colonne INT PRIMARY KEY AUTO_INCREMENT,
    champ VARCHAR(50) NOT NULL,
    libelle VARCHAR(50) NOT NULL,
    ordre INT DEFAULT 0
);

CREATE TABLE adminLogs (
    id_colonne INT PRIMARY KEY AUTO_INCREMENT,
    champ VARCHAR(50) NOT NULL,
    libelle VARCHAR(50) NOT NULL,
    ordre INT DEFAULT 0
);

INSERT INTO adminUtilisateurs (champ, libelle, ordre) VALUES
('id_utilisateur', 'ID', 1),
('nom', 'Nom', 2),
('prenom', 'Prénom', 3),
('email', 'Email', 4),
('role', 'Rôle', 5),
('departement', 'Département', 6),
('autoriser', 'Autorisé', 7),
('date_creation', 'Date création', 8);

INSERT INTO adminDemandes (champ, libelle, ordre) VALUES
('id_demande', 'ID', 1),
('objet', 'Objet', 2),
('montant', 'Montant', 3),
('demandeur', 'Demandeur', 4),
('departement', 'Département', 5),
('statut', 'Statut', 6),
('date_creation', 'Date création', 7);

INSERT INTO adminLogs (champ, libelle, ordre) VALUES
('id_log', 'ID', 1),
('date_action', 'Date', 2),
('utilisateur', 'Utilisateur', 3),
('action', 'Action', 4),
('ancien_statut', 'Ancien statut', 5),
('nouveau_statut', 'Nouveau statut', 6),
('justification', 'Justification', 7);
