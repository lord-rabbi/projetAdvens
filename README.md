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
