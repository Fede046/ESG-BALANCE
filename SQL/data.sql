-- Active: 1771787311626@@localhost@3308@test

DROP DATABASE IF EXISTS TEST;
CREATE DATABASE TEST;

USE TEST;

-- CREAZIONE DELLE TABLE

-- UTENTE
CREATE TABLE UTENTE (
    Username VARCHAR(30) PRIMARY KEY,
    CodiceFiscale VARCHAR(30),
    Password VARCHAR(30) NOT NULL,
    Luogo VARCHAR(30),
    Data VARCHAR(30)
);

CREATE TABLE EMAIL (
    Username VARCHAR(30) NOT NULL,
    Indirizzo VARCHAR(255),
    PRIMARY KEY(Username,Indirizzo),
    FOREIGN KEY(Username) REFERENCES UTENTE(Username)
);
#Il fatto che un amministratore non può essere anche revisore quando lo metto e dove lo metto?
CREATE TABLE AMMINISTRATORE(
    Username VARCHAR(30) NOT NULL,
    FOREIGN KEY(Username) REFERENCES UTENTE(Username)
);
CREATE TABLE REVISORE_ESG(
    Username VARCHAR(30) NOT NULL,
    IndiceAffidabilita INT,
    NumRevisioni INT,
    FOREIGN KEY(Username) REFERENCES UTENTE(Username),
    CONSTRAINT CHK_Valutazione CHECK (IndiceAffidabilita >= 1 AND IndiceAffidabilita <= 10)
);
CREATE TABLE RESPONSABILE_AZIENDALE(
    Username VARCHAR(30) NOT NULL,
    FOREIGN KEY(Username) REFERENCES UTENTE(Username)
);

CREATE TABLE C

SELECT * FROM UTENTE;
SELECT Password FROM UTENTE WHERE Username = '814';

