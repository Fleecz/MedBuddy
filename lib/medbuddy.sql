 CREATE TABLE IF NOT EXISTS benutzer (
  benutzer_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
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
    name VARCHAR(100),
    email VARCHAR(150),
    benutzer_beziehung VARCHAR(100),
    FOREIGN KEY (benutzer_id) REFERENCES benutzer(benutzer_id)
);
CREATE TABLE einnahmeform (
    einnahmeform_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50)
);
CREATE TABLE wirkstoff (
    wirkstoff_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    wirkstoffgruppe VARCHAR(100),
    beschreibung TEXT
);
CREATE TABLE medikament (
    medikament_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
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
    häufigkeit VARCHAR(50),
    uhrzeit TIME,
    startdatum DATE,
    enddatum DATE,
    aktiv BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (benutzer_id) REFERENCES benutzer(benutzer_id),
    FOREIGN KEY (medikament_id) REFERENCES medikament(medikament_id)
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
