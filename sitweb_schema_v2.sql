-- ============================================================
-- MPD (Modèle Physique de Données) - E-commerce pièces auto
-- Base : sitweb - VERSION 2
-- Ajouts par rapport à la v1 :
--   - PRODUITS : colonne reference_oem (recherche par réf. constructeur)
--   - IMAGES_PRODUIT : plusieurs photos par produit
--   - FAVORIS : liste de favoris client
--   - PROMOTIONS : réductions temporaires sur un produit
--   - Toutes les tables passent en ENGINE=InnoDB (FK réellement
--     appliquées + transactions, au lieu de MyISAM)
--
-- Utilisation phpMyAdmin :
--   Onglet SQL de la base "sitweb" -> coller tout -> Exécuter
--
-- Utilisation PowerAMC (pour régénérer le MCD depuis ce script) :
--   Fichier > Reverse Engineer > Database...
--   -> "A l'aide d'un fichier script" -> sélectionner ce fichier -> OK
-- ============================================================

CREATE DATABASE IF NOT EXISTS sitweb
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sitweb;

-- ------------------------------------------------------------
-- 1) Suppression de toutes les tables existantes
-- ------------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS PROMOTIONS;
DROP TABLE IF EXISTS FAVORIS;
DROP TABLE IF EXISTS IMAGES_PRODUIT;
DROP TABLE IF EXISTS AVIS;
DROP TABLE IF EXISTS LIVRAISONS;
DROP TABLE IF EXISTS PAIEMENTS;
DROP TABLE IF EXISTS DETAILS_COMMANDE;
DROP TABLE IF EXISTS COMMANDES;
DROP TABLE IF EXISTS LIGNE_PANIER;
DROP TABLE IF EXISTS PANIER;
DROP TABLE IF EXISTS COMPATIBILITE;
DROP TABLE IF EXISTS VEHICULES;
DROP TABLE IF EXISTS PRODUITS;
DROP TABLE IF EXISTS FOURNISSEURS;
DROP TABLE IF EXISTS MARQUES;
DROP TABLE IF EXISTS CATEGORIES;
DROP TABLE IF EXISTS ADRESSES;
DROP TABLE IF EXISTS CLIENTS;

-- Sécurité : anciens noms possibles de tests précédents
DROP TABLE IF EXISTS UTILISATEUR;
DROP TABLE IF EXISTS UTILISATEURS;
DROP TABLE IF EXISTS PRODUIT;
DROP TABLE IF EXISTS CATEGORIE;
DROP TABLE IF EXISTS COMPATIBLE;
DROP TABLE IF EXISTS COMPATIBLE2;
DROP TABLE IF EXISTS CONVIENT;
DROP TABLE IF EXISTS CONVIENT2;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- 2) Création des tables (ordre respectant les dépendances FK)
-- ------------------------------------------------------------

CREATE TABLE CLIENTS (
    id_clients      INT             NOT NULL AUTO_INCREMENT,
    nom_client      VARCHAR(100)    NOT NULL,
    prenom_client   VARCHAR(100)    NOT NULL,
    email           VARCHAR(150)    NOT NULL,
    telephone       VARCHAR(30)     NULL,
    mot_de_passe    VARCHAR(255)    NOT NULL,
    role            VARCHAR(20)     NOT NULL DEFAULT 'client',
    date_creation   DATE            NOT NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_clients),
    UNIQUE (email),
    KEY idx_clients_role (role)
) ENGINE=InnoDB;

CREATE TABLE ADRESSES (
    id_adresse      INT             NOT NULL AUTO_INCREMENT,
    id_clients      INT             NOT NULL,
    type_adresse    VARCHAR(20)     NOT NULL,
    rue             VARCHAR(150)    NOT NULL,
    ville           VARCHAR(100)    NOT NULL,
    code_postal     VARCHAR(10)     NOT NULL,
    pays            VARCHAR(100)    NOT NULL,
    PRIMARY KEY (id_adresse),
    CONSTRAINT fk_adresses_clients FOREIGN KEY (id_clients)
        REFERENCES CLIENTS(id_clients)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE CATEGORIES (
    id_categories   INT             NOT NULL AUTO_INCREMENT,
    nom_categories  VARCHAR(100)    NOT NULL,
    parent_id       INT             NULL,
    PRIMARY KEY (id_categories),
    CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id)
        REFERENCES CATEGORIES(id_categories)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE MARQUES (
    id_marques      INT             NOT NULL AUTO_INCREMENT,
    nom_marques     VARCHAR(100)    NOT NULL,
    PRIMARY KEY (id_marques),
    UNIQUE (nom_marques)
) ENGINE=InnoDB;

CREATE TABLE FOURNISSEURS (
    id_fournisseur  INT             NOT NULL AUTO_INCREMENT,
    nom_fournisseur VARCHAR(150)    NOT NULL,
    contact         VARCHAR(150)    NULL,
    telephone       VARCHAR(20)     NULL,
    PRIMARY KEY (id_fournisseur)
) ENGINE=InnoDB;

CREATE TABLE PRODUITS (
    id_produit          INT             NOT NULL AUTO_INCREMENT,
    nom_produit         VARCHAR(150)    NOT NULL,
    description_produit TEXT            NULL,
    reference_oem       VARCHAR(100)    NULL,
    prix_produit        DECIMAL(10,2)   NOT NULL,
    stock_produit       INT             NOT NULL DEFAULT 0,
    image_produit       VARCHAR(255)    NULL,
    id_categories       INT             NOT NULL,
    id_marques          INT             NOT NULL,
    id_fournisseur      INT             NOT NULL,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
                                        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_produit),
    KEY idx_produits_reference_oem (reference_oem),
    KEY idx_produits_nom (nom_produit),
    CONSTRAINT fk_produits_categories FOREIGN KEY (id_categories)
        REFERENCES CATEGORIES(id_categories),
    CONSTRAINT fk_produits_marques FOREIGN KEY (id_marques)
        REFERENCES MARQUES(id_marques),
    CONSTRAINT fk_produits_fournisseurs FOREIGN KEY (id_fournisseur)
        REFERENCES FOURNISSEURS(id_fournisseur),
    CONSTRAINT chk_produits_prix CHECK (prix_produit >= 0),
    CONSTRAINT chk_produits_stock CHECK (stock_produit >= 0)
) ENGINE=InnoDB;

CREATE TABLE VEHICULES (
    id_vehicules    INT             NOT NULL AUTO_INCREMENT,
    model_vehicules VARCHAR(100)    NOT NULL,
    annee           DATE            NOT NULL,
    serie           VARCHAR(50)     NULL,
    id_marques      INT             NOT NULL,
    PRIMARY KEY (id_vehicules),
    CONSTRAINT fk_vehicules_marques FOREIGN KEY (id_marques)
        REFERENCES MARQUES(id_marques)
) ENGINE=InnoDB;

-- Table d'association (many-to-many) produits <-> véhicules
CREATE TABLE COMPATIBILITE (
    id_produit      INT NOT NULL,
    id_vehicules    INT NOT NULL,
    PRIMARY KEY (id_produit, id_vehicules),
    CONSTRAINT fk_compat_produit FOREIGN KEY (id_produit)
        REFERENCES PRODUITS(id_produit)
        ON DELETE CASCADE,
    CONSTRAINT fk_compat_vehicule FOREIGN KEY (id_vehicules)
        REFERENCES VEHICULES(id_vehicules)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE PANIER (
    id_panier       INT             NOT NULL AUTO_INCREMENT,
    id_clients      INT             NOT NULL,
    PRIMARY KEY (id_panier),
    CONSTRAINT fk_panier_clients FOREIGN KEY (id_clients)
        REFERENCES CLIENTS(id_clients)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE LIGNE_PANIER (
    id_ligne_panier INT             NOT NULL AUTO_INCREMENT,
    id_panier       INT             NOT NULL,
    id_produit      INT             NOT NULL,
    quantite        INT             NOT NULL DEFAULT 1,
    PRIMARY KEY (id_ligne_panier),
    CONSTRAINT fk_lignepanier_panier FOREIGN KEY (id_panier)
        REFERENCES PANIER(id_panier)
        ON DELETE CASCADE,
    CONSTRAINT fk_lignepanier_produit FOREIGN KEY (id_produit)
        REFERENCES PRODUITS(id_produit),
    CONSTRAINT chk_lignepanier_qte CHECK (quantite > 0)
) ENGINE=InnoDB;

CREATE TABLE COMMANDES (
    id_commandes    INT             NOT NULL AUTO_INCREMENT,
    id_clients      INT             NOT NULL,
    date_commandes  DATE            NOT NULL,
    statut_commandes VARCHAR(30)    NOT NULL DEFAULT 'en attente',
    total_commandes DECIMAL(10,2)   NOT NULL,
        code_paiement   VARCHAR(32)     NOT NULL,
        confirmee_at    DATETIME        NULL,
        payee_at        DATETIME        NULL,
        validee_par     INT             NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_commandes),
    KEY idx_commandes_statut (statut_commandes),
    CONSTRAINT fk_commandes_clients FOREIGN KEY (id_clients)
        REFERENCES CLIENTS(id_clients),
    CONSTRAINT fk_commandes_valideur FOREIGN KEY (validee_par)
        REFERENCES CLIENTS(id_clients)
        ON DELETE SET NULL,
    CONSTRAINT chk_commandes_total CHECK (total_commandes >= 0),
    UNIQUE KEY uq_commandes_code_paiement (code_paiement)
) ENGINE=InnoDB;

CREATE TABLE DETAILS_COMMANDE (
    id              INT             NOT NULL AUTO_INCREMENT,
    id_commandes    INT             NOT NULL,
    id_produit      INT             NOT NULL,
    quantite        INT             NOT NULL,
    prix_unitaire   DECIMAL(10,2)   NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_details_commandes FOREIGN KEY (id_commandes)
        REFERENCES COMMANDES(id_commandes)
        ON DELETE CASCADE,
    CONSTRAINT fk_details_produit FOREIGN KEY (id_produit)
        REFERENCES PRODUITS(id_produit),
    CONSTRAINT chk_details_qte CHECK (quantite > 0),
    CONSTRAINT chk_details_prix CHECK (prix_unitaire >= 0)
) ENGINE=InnoDB;

CREATE TABLE PAIEMENTS (
    id_paiement     INT             NOT NULL AUTO_INCREMENT,
    id_commandes    INT             NOT NULL,
    montant         DECIMAL(10,2)   NOT NULL,
    methode_paiement VARCHAR(50)    NOT NULL,
    reference_paiement VARCHAR(100) NULL,
    date_paiement   DATE            NOT NULL,
    statut_paiement VARCHAR(30)     NOT NULL DEFAULT 'en attente',
    PRIMARY KEY (id_paiement),
    UNIQUE (id_commandes),
    CONSTRAINT fk_paiements_commandes FOREIGN KEY (id_commandes)
        REFERENCES COMMANDES(id_commandes)
        ON DELETE CASCADE,
    CONSTRAINT chk_paiements_montant CHECK (montant >= 0)
) ENGINE=InnoDB;

CREATE TABLE LIVRAISONS (
    id_livraison    INT             NOT NULL AUTO_INCREMENT,
    id_commandes    INT             NOT NULL,
    id_adresse      INT             NOT NULL,
    date_livraison  DATE            NULL,
    statut_livraison VARCHAR(30)    NOT NULL DEFAULT 'en preparation',
    transporteur    VARCHAR(100)    NULL,
    PRIMARY KEY (id_livraison),
    UNIQUE (id_commandes),
    KEY idx_livraisons_statut (statut_livraison),
    CONSTRAINT fk_livraisons_commandes FOREIGN KEY (id_commandes)
        REFERENCES COMMANDES(id_commandes)
        ON DELETE CASCADE,
    CONSTRAINT fk_livraisons_adresse FOREIGN KEY (id_adresse)
        REFERENCES ADRESSES(id_adresse)
) ENGINE=InnoDB;

CREATE TABLE AVIS (
    id_avis         INT             NOT NULL AUTO_INCREMENT,
    id_clients      INT             NOT NULL,
    id_produit      INT             NOT NULL,
    note            INT             NOT NULL,
    commentaire     TEXT            NULL,
    date_avis       DATE            NOT NULL,
    PRIMARY KEY (id_avis),
    CONSTRAINT fk_avis_clients FOREIGN KEY (id_clients)
        REFERENCES CLIENTS(id_clients)
        ON DELETE CASCADE,
    CONSTRAINT fk_avis_produit FOREIGN KEY (id_produit)
        REFERENCES PRODUITS(id_produit)
        ON DELETE CASCADE,
    CONSTRAINT chk_avis_note CHECK (note BETWEEN 1 AND 5)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Nouvelles tables v2
-- ------------------------------------------------------------

CREATE TABLE IMAGES_PRODUIT (
    id_image        INT             NOT NULL AUTO_INCREMENT,
    id_produit      INT             NOT NULL,
    url_image       VARCHAR(255)    NOT NULL,
    ordre           INT             NOT NULL DEFAULT 0,
    PRIMARY KEY (id_image),
    CONSTRAINT fk_images_produit FOREIGN KEY (id_produit)
        REFERENCES PRODUITS(id_produit)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE FAVORIS (
    id_favori       INT             NOT NULL AUTO_INCREMENT,
    id_clients      INT             NOT NULL,
    id_produit      INT             NOT NULL,
    date_ajout      DATE            NOT NULL,
    PRIMARY KEY (id_favori),
    UNIQUE KEY uniq_favori (id_clients, id_produit),
    CONSTRAINT fk_favoris_clients FOREIGN KEY (id_clients)
        REFERENCES CLIENTS(id_clients)
        ON DELETE CASCADE,
    CONSTRAINT fk_favoris_produit FOREIGN KEY (id_produit)
        REFERENCES PRODUITS(id_produit)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE PROMOTIONS (
    id_promotion        INT             NOT NULL AUTO_INCREMENT,
    nom_promotion       VARCHAR(150)    NOT NULL,
    id_produit          INT             NULL,
    id_categories       INT             NULL,
    type_reduction      VARCHAR(20)     NOT NULL,
    valeur              DECIMAL(10,2)   NOT NULL,
    date_debut          DATE            NOT NULL,
    date_fin             DATE            NOT NULL,
    actif               TINYINT(1)      NOT NULL DEFAULT 1,
    created_at           TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                             ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_promotion),
    KEY idx_promotions_actif (actif),
    CONSTRAINT fk_promotions_produit FOREIGN KEY (id_produit)
        REFERENCES PRODUITS(id_produit)
        ON DELETE CASCADE,
    CONSTRAINT fk_promotions_categorie FOREIGN KEY (id_categories)
        REFERENCES CATEGORIES(id_categories)
        ON DELETE CASCADE,
    CONSTRAINT chk_promotions_type CHECK (type_reduction IN ('pourcentage', 'montant')),
    CONSTRAINT chk_promotions_valeur CHECK (valeur > 0 AND (type_reduction <> 'pourcentage' OR valeur <= 100)),
    CONSTRAINT chk_promotions_dates CHECK (date_fin >= date_debut),
    CONSTRAINT chk_promotions_cible CHECK (
        (id_produit IS NOT NULL AND id_categories IS NULL)
        OR (id_produit IS NULL AND id_categories IS NOT NULL)
    )
) ENGINE=InnoDB;

-- ============================================================
-- 3) Compte administrateur initial
-- Le mot de passe en clair ne doit jamais être stocké en base.
-- Hash bcrypt de : Admin2026
-- ============================================================

INSERT INTO CATEGORIES (nom_categories, parent_id) VALUES
    ('Injection Diesel', NULL),
    ('Suralimentation', NULL),
    ('Filtration', NULL),
    ('Pièces pour poids lourds', NULL),
    ('Électricité et démarrage', NULL),
    ('Moteurs diesel', NULL),
    ('Pompes et alimentation', NULL),
    ('Refroidissement', NULL),
    ('Transmission', NULL),
    ('Entretien moteur', NULL),
    ('Freinage', NULL),
    ('Suspension', NULL);

INSERT INTO MARQUES (nom_marques) VALUES
    ('Bosch'), ('Garrett'), ('MANN-FILTER'), ('Denso'), ('Cummins'),
    ('Perkins'), ('Delphi'), ('Valeo'), ('Sachs'), ('Mahle'), ('Mitsubishi');

INSERT INTO FOURNISSEURS (nom_fournisseur, contact, telephone) VALUES
    ('DCD Import', 'DUTCH COMPANY DIESEL GABON', '+241 07 45 88 99');

INSERT INTO PRODUITS (
    nom_produit, description_produit, reference_oem, prix_produit, stock_produit,
    image_produit, id_categories, id_marques, id_fournisseur
)
SELECT 'Injecteur Common Rail', 'Injecteur diesel haute précision pour moteurs utilitaires.',
    '0445110153', 185000, 12, 'Unlocking Efficiency_ The Fuel Injection Pump Explained.jpg',
       (SELECT id_categories FROM CATEGORIES WHERE nom_categories = 'Injection Diesel'),
       (SELECT id_marques FROM MARQUES WHERE nom_marques = 'Bosch'), id_fournisseur
FROM FOURNISSEURS WHERE nom_fournisseur = 'DCD Import';

INSERT INTO PRODUITS (
    nom_produit, description_produit, reference_oem, prix_produit, stock_produit,
    image_produit, id_categories, id_marques, id_fournisseur
)
SELECT 'Moteur diesel complet 4 cylindres', 'Bloc moteur diesel complet pour utilitaires et groupes électrogènes.',
    '4D34-ENGINE', 2850000, 2, 'téléchargement (1).jpg',
       (SELECT id_categories FROM CATEGORIES WHERE nom_categories = 'Moteurs diesel'),
       (SELECT id_marques FROM MARQUES WHERE nom_marques = 'Mitsubishi'), id_fournisseur
FROM FOURNISSEURS WHERE nom_fournisseur = 'DCD Import';

INSERT INTO PRODUITS (
    nom_produit, description_produit, reference_oem, prix_produit, stock_produit,
    image_produit, id_categories, id_marques, id_fournisseur
)
SELECT 'Moteur Perkins 1104 reconditionné', 'Moteur diesel reconditionné, contrôlé et prêt à monter.',
    '1104C-44T', 3400000, 1, 'téléchargement (1).jpg',
       (SELECT id_categories FROM CATEGORIES WHERE nom_categories = 'Moteurs diesel'),
       (SELECT id_marques FROM MARQUES WHERE nom_marques = 'Perkins'), id_fournisseur
FROM FOURNISSEURS WHERE nom_fournisseur = 'DCD Import';

INSERT INTO PRODUITS (
    nom_produit, description_produit, reference_oem, prix_produit, stock_produit,
    image_produit, id_categories, id_marques, id_fournisseur
)
SELECT 'Pompe haute pression diesel', 'Pompe d’injection haute pression pour système Common Rail.',
    '0445010126', 675000, 4, 'Unlocking Efficiency_ The Fuel Injection Pump Explained.jpg',
       (SELECT id_categories FROM CATEGORIES WHERE nom_categories = 'Pompes et alimentation'),
       (SELECT id_marques FROM MARQUES WHERE nom_marques = 'Delphi'), id_fournisseur
FROM FOURNISSEURS WHERE nom_fournisseur = 'DCD Import';

INSERT INTO PRODUITS (
    nom_produit, description_produit, reference_oem, prix_produit, stock_produit,
    image_produit, id_categories, id_marques, id_fournisseur
)
SELECT 'Alternateur poids lourd 24V', 'Alternateur renforcé 24 volts pour camions et engins.',
    'AAN8621', 295000, 7, 'default.jpg.jpg',
       (SELECT id_categories FROM CATEGORIES WHERE nom_categories = 'Électricité et démarrage'),
       (SELECT id_marques FROM MARQUES WHERE nom_marques = 'Valeo'), id_fournisseur
FROM FOURNISSEURS WHERE nom_fournisseur = 'DCD Import';

INSERT INTO PRODUITS (
    nom_produit, description_produit, reference_oem, prix_produit, stock_produit,
    image_produit, id_categories, id_marques, id_fournisseur
)
SELECT 'Démarreur diesel renforcé', 'Démarreur haute puissance pour moteurs industriels et poids lourds.',
    'M8T60371', 240000, 6, 'Starter Motor.jpg',
       (SELECT id_categories FROM CATEGORIES WHERE nom_categories = 'Électricité et démarrage'),
       (SELECT id_marques FROM MARQUES WHERE nom_marques = 'Denso'), id_fournisseur
FROM FOURNISSEURS WHERE nom_fournisseur = 'DCD Import';

INSERT INTO PRODUITS (
    nom_produit, description_produit, reference_oem, prix_produit, stock_produit,
    image_produit, id_categories, id_marques, id_fournisseur
)
SELECT 'Radiateur moteur poids lourd', 'Radiateur haute capacité pour refroidissement moteur diesel.',
    'RD-TRUCK-280', 385000, 3, 'default.jpg.jpg',
       (SELECT id_categories FROM CATEGORIES WHERE nom_categories = 'Refroidissement'),
       (SELECT id_marques FROM MARQUES WHERE nom_marques = 'Mahle'), id_fournisseur
FROM FOURNISSEURS WHERE nom_fournisseur = 'DCD Import';

INSERT INTO PRODUITS (
    nom_produit, description_produit, reference_oem, prix_produit, stock_produit,
    image_produit, id_categories, id_marques, id_fournisseur
)
SELECT 'Kit embrayage poids lourd', 'Kit complet embrayage avec disque, mécanisme et butée.',
       'KDP-4521', 510000, 4, 'default.jpg.jpg',
       (SELECT id_categories FROM CATEGORIES WHERE nom_categories = 'Transmission'),
       (SELECT id_marques FROM MARQUES WHERE nom_marques = 'Sachs'), id_fournisseur
FROM FOURNISSEURS WHERE nom_fournisseur = 'DCD Import';

INSERT INTO PRODUITS (
    nom_produit, description_produit, reference_oem, prix_produit, stock_produit,
    image_produit, id_categories, id_marques, id_fournisseur
)
SELECT 'Boîte de vitesses manuelle', 'Boîte de vitesses contrôlée pour véhicule utilitaire diesel.',
       'GEARBOX-6S', 1950000, 1, 'default.jpg.jpg',
       (SELECT id_categories FROM CATEGORIES WHERE nom_categories = 'Transmission'),
       (SELECT id_marques FROM MARQUES WHERE nom_marques = 'Cummins'), id_fournisseur
FROM FOURNISSEURS WHERE nom_fournisseur = 'DCD Import';

INSERT INTO PRODUITS (
    nom_produit, description_produit, reference_oem, prix_produit, stock_produit,
    image_produit, id_categories, id_marques, id_fournisseur
)
SELECT 'Compresseur d’air moteur', 'Compresseur pour circuit pneumatique de poids lourd.',
       'LP4865', 455000, 3, 'default.jpg.jpg',
       (SELECT id_categories FROM CATEGORIES WHERE nom_categories = 'Pièces pour poids lourds'),
       (SELECT id_marques FROM MARQUES WHERE nom_marques = 'Cummins'), id_fournisseur
FROM FOURNISSEURS WHERE nom_fournisseur = 'DCD Import';

INSERT INTO PRODUITS (
    nom_produit, description_produit, reference_oem, prix_produit, stock_produit,
    image_produit, id_categories, id_marques, id_fournisseur
)
SELECT 'Kit filtres entretien moteur', 'Kit de filtres air, huile et carburant pour entretien courant.',
    'KIT-SERVICE-01', 95000, 15, 'The Premium Guard Extended Life filter is ideal for drivers with high mileage vehicles or those who drive in heavy traffic or in extreme weather_.jpg',
       (SELECT id_categories FROM CATEGORIES WHERE nom_categories = 'Entretien moteur'),
       (SELECT id_marques FROM MARQUES WHERE nom_marques = 'MANN-FILTER'), id_fournisseur
FROM FOURNISSEURS WHERE nom_fournisseur = 'DCD Import';

INSERT INTO PRODUITS (
    nom_produit, description_produit, reference_oem, prix_produit, stock_produit,
    image_produit, id_categories, id_marques, id_fournisseur
)
SELECT 'Kit freinage complet', 'Disques ventilés et plaquettes pour freinage fiable et endurant.',
       'ATEC-BRAKE-300', 275000, 5, 'ATEC Germany Kit de freinage incluant disques de frein avant Ø 300 mm ventilés + arrière Ø 300 mm pleins + plaquettes de frein avant arrière.jpg',
       (SELECT id_categories FROM CATEGORIES WHERE nom_categories = 'Freinage'),
       (SELECT id_marques FROM MARQUES WHERE nom_marques = 'Bosch'), id_fournisseur
FROM FOURNISSEURS WHERE nom_fournisseur = 'DCD Import';

INSERT INTO PRODUITS (
    nom_produit, description_produit, reference_oem, prix_produit, stock_produit,
    image_produit, id_categories, id_marques, id_fournisseur
)
SELECT 'Jambe de suspension complète', 'Ensemble amortisseur et ressort pour train avant.',
       'STRUT-PRIUS-FWD', 320000, 3, 'CCIYU Complete Struts Shock Absorbers Fits for 2010 2011 2012 2013 2014 2015 for Toyota Prius 172689 172688 Quick Struts Assembly Front Pair Struts FWD.jpg',
       (SELECT id_categories FROM CATEGORIES WHERE nom_categories = 'Suspension'),
       (SELECT id_marques FROM MARQUES WHERE nom_marques = 'Denso'), id_fournisseur
FROM FOURNISSEURS WHERE nom_fournisseur = 'DCD Import';

INSERT INTO CLIENTS (
    nom_client, prenom_client, email, telephone, mot_de_passe, role, date_creation
)
VALUES (
    'Yan David',
    'SOH SOH',
    'davidyan763@gmail.com',
    NULL,
    '$2y$12$kBejgOfEiyF2ZbnHAnidlOXGLbUjHthQztqr3t7ECI179jtKy45Ce',
    'admin',
    CURDATE()
);

-- ============================================================
-- Fin du script - 18 tables créées + 1 compte admin.
-- ============================================================
