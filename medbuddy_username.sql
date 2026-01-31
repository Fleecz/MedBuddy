CREATE TABLE IF NOT EXISTS benutzer (
  benutzer_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  passwort_hash VARCHAR(255) NOT NULL,
  geburtsdatum DATE NULL,
  rolle VARCHAR(50) NULL,
  konto_aktiv BOOLEAN NOT NULL DEFAULT TRUE,
  letzte_aktualisierung TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP
);
CREATE TABLE vertrauensperson (
    vertrauensperson_id INT AUTO_INCREMENT PRIMARY KEY,
    benutzer_id INT NOT NULL,
    username VARCHAR(100),
    email VARCHAR(150),
    benutzer_beziehung VARCHAR(100),
    FOREIGN KEY (benutzer_id) REFERENCES benutzer(benutzer_id)
);
CREATE TABLE einnahmeform (
    einnahmeform_id INT AUTO_INCREMENT PRIMARY KEY,
    ename VARCHAR(50)
);
CREATE TABLE wirkstoff (
    wirkstoff_id INT AUTO_INCREMENT PRIMARY KEY,
    wname VARCHAR(100),
    wirkstoffgruppe VARCHAR(100),
    beschreibung TEXT
);
CREATE TABLE medikament (
    medikament_id INT AUTO_INCREMENT PRIMARY KEY,
    mname VARCHAR(50) NOT NULL,
    benutzer_id VARCHAR(100),
    atc_code VARCHAR(20),
    form VARCHAR(50),
    dosis VARCHAR(50),
    beschreibung TEXT,
    einnahmeform_id INT,
    FOREIGN KEY (einnahmeform_id) REFERENCES einnahmeform(einnahmeform_id)
);
CREATE TABLE medikament_wirkstoff (
    medikament_id INT,
    wirkstoff_id INT,
    PRIMARY KEY (medikament_id, wirkstoff_id),
    FOREIGN KEY (medikament_id) REFERENCES medikament(medikament_id),
    FOREIGN KEY (wirkstoff_id) REFERENCES wirkstoff(wirkstoff_id)
);
CREATE TABLE nebenwirkung (
    nebenwirkung_id INT AUTO_INCREMENT PRIMARY KEY,
    bezeichnung VARCHAR(100),
    beschreibung TEXT,
    häufigkeit VARCHAR(50),
    schweregrad VARCHAR(50)
);
CREATE TABLE nebenwirkung_wirkstoff (
    wirkstoff_id INT,
    nebenwirkung_id INT,
    PRIMARY KEY (wirkstoff_id, nebenwirkung_id),
    FOREIGN KEY (wirkstoff_id) REFERENCES wirkstoff(wirkstoff_id),
    FOREIGN KEY (nebenwirkung_id) REFERENCES nebenwirkung(nebenwirkung_id)
);
CREATE TABLE wechselwirkung (
    wechselwirkung_id INT AUTO_INCREMENT PRIMARY KEY,
    bezeichnung VARCHAR(100),
    beschreibung TEXT,
    empfehlung TEXT,
    schweregrad VARCHAR(50)
);
CREATE TABLE wechselwirkung_wirkstoff (
    wechselwirkung_id INT,
    wirkstoff_id INT,
    PRIMARY KEY (wechselwirkung_id, wirkstoff_id),
    FOREIGN KEY (wechselwirkung_id) REFERENCES wechselwirkung(wechselwirkung_id),
    FOREIGN KEY (wirkstoff_id) REFERENCES wirkstoff(wirkstoff_id)
);
CREATE TABLE einnahmeplan (
    plan_id INT AUTO_INCREMENT PRIMARY KEY,
    benutzer_id INT NOT NULL,
    medikament_id INT NOT NULL,
    p_dosierung VARCHAR(50),
    häufigkeit INT,
    startdatum DATE,
    enddatum DATE,
    aktiv BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (benutzer_id) REFERENCES benutzer(benutzer_id),
    FOREIGN KEY (medikament_id) REFERENCES medikament(medikament_id)
);

CREATE TABLE einnahmeplan_uhrzeit (
    plan_uhrzeit_id INT AUTO_INCREMENT PRIMARY KEY,
    plan_id INT NOT NULL,
    uhrzeit TIME NOT NULL,
    FOREIGN KEY (plan_id) REFERENCES einnahmeplan(plan_id)
    ON DELETE CASCADE
);
CREATE TABLE einnahmeereignis (
    einnahme_id INT AUTO_INCREMENT PRIMARY KEY,
    plan_id INT NOT NULL,
    datum_plan DATE,
    uhrzeit_plan TIME,
    datum_ist DATE,
    status VARCHAR(50),
    kommentar TEXT,
    FOREIGN KEY (plan_id) REFERENCES einnahmeplan(plan_id)
);
CREATE TABLE benachrichtigung (
    benachrichtigungs_id INT AUTO_INCREMENT PRIMARY KEY,
    benutzer_id INT,
    einnahme_id INT,
    vertrauensperson_id INT,
    typ VARCHAR(50),
    kanal VARCHAR(50),
    text TEXT,
    gelesen BOOLEAN DEFAULT FALSE,
    gelesen_am TIMESTAMP NULL,
    erzeugt_am TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (benutzer_id) REFERENCES benutzer(benutzer_id),
    FOREIGN KEY (einnahme_id) REFERENCES einnahmeereignis(einnahme_id),
    FOREIGN KEY (vertrauensperson_id) REFERENCES vertrauensperson(vertrauensperson_id)
);
CREATE TABLE stimmungseintrag (
    stimmungseintrag_id INT AUTO_INCREMENT PRIMARY KEY,
    benutzer_id INT NOT NULL,
    datum DATE,
    uhrzeit TIME,
    stimmungswert INT,
    notiz TEXT,
    punkte INT,
    FOREIGN KEY (benutzer_id) REFERENCES benutzer(benutzer_id)
);
CREATE TABLE aktivität (
  aktivität_id INT AUTO_INCREMENT PRIMARY KEY,
  stimmungseintrag_id INT NULL,
  benutzer_id INT NOT NULL,
  titel VARCHAR(255) NOT NULL,
  beschreibung TEXT,
  datum DATE NOT NULL,
  category ENUM('Bewegung','Entspannung','Soziales','Selbstfürsorge') NOT NULL,
  CONSTRAINT fk_aktivitaet_stimmung
    FOREIGN KEY (stimmungseintrag_id)
    REFERENCES stimmungseintrag(stimmungseintrag_id)
    ON DELETE SET NULL,
  CONSTRAINT fk_aktivitaet_benutzer
    FOREIGN KEY (benutzer_id)
    REFERENCES benutzer(benutzer_id)
    ON DELETE CASCADE
);

START TRANSACTION;

INSERT INTO einnahmeform (einnahmeform_id, ename) VALUES
(1, 'Tablette'),
(2, 'Kapsel'),
(3, 'Saft/Sirup'),
(4, 'Tropfen'),
(5, 'Inhalation'),
(6, 'Injektion');

INSERT INTO wirkstoff (wirkstoff_id, wname, wirkstoffgruppe, beschreibung) VALUES
(1,  'Paracetamol',  'Analgetikum/Antipyretikum', 'Schmerzlindernd und fiebersenkend.'),
(2,  'Ibuprofen',    'NSAR', 'Entzündungshemmendes Schmerzmittel.'),
(3,  'Amoxicillin',  'Antibiotikum (Penicillin)', 'Breitbandantibiotikum.'),
(4,  'Metformin',    'Antidiabetikum', 'Verbessert Insulinempfindlichkeit.'),
(5,  'Amlodipin',    'Calciumkanalblocker', 'Blutdrucksenkend.'),
(6,  'Omeprazol',    'PPI', 'Senkt Magensäureproduktion.'),
(7,  'Sertralin',    'SSRI', 'Antidepressivum.'),
(8,  'Salbutamol',   'Beta-2-Sympathomimetikum', 'Bronchienerweiternd.'),
(9,  'Lisinopril',   'ACE-Hemmer', 'Blutdrucksenkend.'),
(10, 'Atorvastatin', 'Statin', 'Cholesterinsenkend.');

INSERT INTO medikament (medikament_id, mname, atc_code, form, dosis, beschreibung, einnahmeform_id) VALUES
(1,  'Paracetamol 500',  'N02BE01', 'Tablette',  '500 mg', 'Schmerz/Fieber', 1),
(2,  'Ibuprofen 400',    'M01AE01', 'Tablette',  '400 mg', 'Schmerz/Entzündung', 1),
(3,  'Amoxicillin 500',  'J01CA04', 'Kapsel',    '500 mg', 'Antibiotikum', 2),
(4,  'Metformin 500',    'A10BA02', 'Tablette',  '500 mg', 'Diabetes Typ 2', 1),
(5,  'Amlodipin 5',      'C08CA01', 'Tablette',  '5 mg',   'Bluthochdruck', 1),
(6,  'Omeprazol 20',     'A02BC01', 'Kapsel',    '20 mg',  'Sodbrennen/Reflux', 2),
(7,  'Sertralin 50',     'N06AB06', 'Tablette',  '50 mg',  'Depression/Angst', 1),
(8,  'Salbutamol',       'R03AC02', 'Inhalation','100 µg', 'Asthma', 5),
(9,  'Lisinopril 10',    'C09AA03', 'Tablette',  '10 mg',  'Bluthochdruck', 1),
(10, 'Atorvastatin 20',  'C10AA05', 'Tablette',  '20 mg',  'Cholesterin', 1);

INSERT INTO medikament_wirkstoff (medikament_id, wirkstoff_id) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 4),
(5, 5),
(6, 6),
(7, 7),
(8, 8),
(9, 9),
(10,10);

INSERT INTO nebenwirkung (nebenwirkung_id, bezeichnung, beschreibung, häufigkeit, schweregrad) VALUES
(1,  'Übelkeit', 'Übelkeit/Magenbeschwerden', 'gelegentlich', 'leicht'),
(2,  'Magenschmerzen', 'Reizung der Magenschleimhaut', 'häufig', 'mittel'),
(3,  'Durchfall', 'Gastrointestinale Beschwerden', 'gelegentlich', 'leicht'),
(4,  'Bauchschmerzen', 'Gastrointestinale Beschwerden', 'gelegentlich', 'leicht'),
(5,  'Knöchelödeme', 'Wassereinlagerungen', 'gelegentlich', 'mittel'),
(6,  'Kopfschmerzen', 'Kopfschmerzen', 'gelegentlich', 'leicht'),
(7,  'Schlafstörungen', 'Einschlaf-/Durchschlafprobleme', 'gelegentlich', 'mittel'),
(8,  'Zittern', 'Tremor', 'gelegentlich', 'leicht'),
(9,  'Husten', 'Trockener Reizhusten', 'gelegentlich', 'mittel'),
(10, 'Muskelschmerzen', 'Myalgien', 'gelegentlich', 'mittel');

INSERT INTO nebenwirkung_wirkstoff (wirkstoff_id, nebenwirkung_id) VALUES
(1, 1),   -- Paracetamol -> Übelkeit
(2, 2),   -- Ibuprofen -> Magenschmerzen
(3, 3),   -- Amoxicillin -> Durchfall
(4, 4),   -- Metformin -> Bauchschmerzen
(5, 5),   -- Amlodipin -> Ödeme
(6, 6),   -- Omeprazol -> Kopfschmerzen
(7, 7),   -- Sertralin -> Schlafstörungen
(8, 8),   -- Salbutamol -> Zittern
(9, 9),   -- Lisinopril -> Husten
(10,10);  -- Atorvastatin -> Muskelschmerzen

INSERT INTO wechselwirkung (wechselwirkung_id, bezeichnung, beschreibung, empfehlung, schweregrad) VALUES
(1, 'Ibuprofen + Lisinopril', 'NSAR können die blutdrucksenkende Wirkung abschwächen und die Nieren belasten.', 'Nur nach Rücksprache; auf Flüssigkeit/Nierenwerte achten.', 'hoch'),
(2, 'Sertralin + Ibuprofen',  'Erhöhtes Risiko für Magen-Darm-Blutungen bei Kombination SSRI + NSAR.', 'Vorsicht; ggf. Magenschutz; Arzt/Apotheke fragen.', 'mittel');

INSERT INTO wechselwirkung_wirkstoff (wechselwirkung_id, wirkstoff_id) VALUES
(1, 2),  -- Ibuprofen
(1, 9),  -- Lisinopril
(2, 7),  -- Sertralin
(2, 2);  -- Ibuprofen

COMMIT;