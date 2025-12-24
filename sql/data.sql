/*AUTO_INCREMENT: Es werden automatisch fortlaufende IDs erzeugt*/
/*VARCHAR(n): Bis zu n Zeichen, also 1 Wertebereich, ist möglich zu speichern*/
/*REFERENCES sollte eigentlich selbsterklärend sein, verknüpft 2 Tabellen*/
CREATE TABLE Benutzer(
    benutzer_id INT AUTO_INCREMENT PRIMARY KEY
);
CREATE TABLE Medikament(
    medikament_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    atc_code VARCHAR(50),
    form VARCHAR(50),
    beschreibuung TEXT
);
CREATE TABLE Wirkstoff(
    wirkstoff_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    beschreibunng TEXT,
    wirkstoffgruppe VARCHAR(100)
);
CREATE TABLE Medikament_Wirkstoff(
    medikament_id INT,
    wirkstoff_id INT,
    PRIMARY KEY (medikament_id, wirkstoff_id),
    FOREIGN KEY (medikament_id) REFERENCES Medikament(medikament_id),
    FOREIGN KEY (wirkstoff_id) REFERENCES Wirkstoff(wirkstoff_id)
);
CREATE TABLE Nebenwirkung (
  nebenwirkung_id INT AUTO_INCREMENT PRIMARY KEY,
  bezeichnung VARCHAR(255),
  beschreibung TEXT,
  haeufigkeit VARCHAR(50),
  schweregrad VARCHAR(50),
  medikament_id INT,
  FOREIGN KEY (medikament_id) REFERENCES Medikament(medikament_id)
);
CREATE TABLE Wechselwirkung (
  wechselwirkung_id INT AUTO_INCREMENT PRIMARY KEY,
  beschreibung TEXT,
  empfehlung TEXT,
  schweregrad VARCHAR(50)
);
CREATE TABLE Medikament_Wechselwirkung (
  medikament_id INT,
  wechselwirkung_id INT,
  PRIMARY KEY (medikament_id, wechselwirkung_id),
  FOREIGN KEY (medikament_id) REFERENCES Medikament(medikament_id),
  FOREIGN KEY (wechselwirkung_id) REFERENCES Wechselwirkung(wechselwirkung_id)
);
CREATE TABLE Einnahmeplan (
  plan_id INT AUTO_INCREMENT PRIMARY KEY,
  benutzer_id INT,
  dosierung VARCHAR(100),
  haeufigkeit VARCHAR(100),
  uhrzeiten VARCHAR(255),
  startdatum DATE,
  enddatum DATE,
  aktiv BOOLEAN DEFAULT TRUE,
  FOREIGN KEY (benutzer_id) REFERENCES Benutzer(benutzer_id)
);
CREATE TABLE Einnahmeereignis (
  einnahme_id INT AUTO_INCREMENT PRIMARY KEY,
  plan_id INT,
  datum_plan DATE,
  uhrzeit_plan TIME,
  datum_ist DATE,
  status VARCHAR(50),
  kommentar TEXT,
  FOREIGN KEY (plan_id) REFERENCES Einnahmeplan(plan_id)
);
CREATE TABLE Benachrichtigung (
  benachrichtigungs_id INT AUTO_INCREMENT PRIMARY KEY,
  benutzer_id INT,
  kanal VARCHAR(50),
  text TEXT,
  typ VARCHAR(50),
  gelesen BOOLEAN DEFAULT FALSE,
  gelesen_am TIMESTAMP NULL,
  erzeugt_am TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (benutzer_id) REFERENCES Benutzer(benutzer_id)
);
CREATE TABLE Stimmungseintrag (
  stimmungseintrag_id INT AUTO_INCREMENT PRIMARY KEY,
  benutzer_id INT,
  datum DATE,
  uhrzeit TIME,
  stimmungswert INT,
  notiz TEXT,
  FOREIGN KEY (benutzer_id) REFERENCES Benutzer(benutzer_id)
);
CREATE TABLE Aktivitaet (
  aktivitaet_id INT AUTO_INCREMENT PRIMARY KEY,
  benutzer_id INT,
  beschreibung TEXT,
  datum DATE,
  FOREIGN KEY (benutzer_id) REFERENCES Benutzer(benutzer_id)
);
CREATE TABLE Vertrauensperson (
  vertrauensperson_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255),
  email VARCHAR(255),
  benutzer_beziehung VARCHAR(100),
  benutzer_id INT,
  FOREIGN KEY (benutzer_id) REFERENCES Benutzer(benutzer_id)
);
CREATE TABLE Contentseite (
  seite_id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(150) UNIQUE,
  titel VARCHAR(255),
  inhalt TEXT
);