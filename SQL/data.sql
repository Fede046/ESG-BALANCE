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
    CONSTRAINT CHK_Affidabilita CHECK (IndiceAffidabilita >= 1 AND IndiceAffidabilita <= 10)
);
CREATE TABLE RESPONSABILE_AZIENDALE(
    Username VARCHAR(30) NOT NULL,
    FOREIGN KEY(Username) REFERENCES UTENTE(Username)
);

#Relazione Dichiara -> Decido di fare una tabella unica con livello perchè, dovrei creare una tabella 
#                      separata con livello e le stesse chiavi Dichiara(livello,nome, username). Faccio una tabella unica.
#                       Di conseguenza sto considerando livello come attibuto di comptetenza. Non della relazione dichiara.
CREATE TABLE COMPETENZA(
    Nome VARCHAR(30) NOT NULL,
    Username VARCHAR(30) NOT NULL,
    Livello INT,
    PRIMARY KEY(Username,Nome),
    FOREIGN KEY(Username) REFERENCES REVISORE_ESG(Username),
    CONSTRAINT CHK_Valutazione CHECK (IndiceAffidabilita >= 0 AND IndiceAffidabilita <= 5)
);

# Per Inserire l'immagine copio l'src uso VARCHAR(500)
CREATE TABLE PRIMARY KEY(
    Nome VARCHAR(30) PRIMARY KEY,
    Username VARCHAR(30) NOT NULL,
    Immagine VARCHAR(500), 
    Rilevanza INT,
    FOREIGN KEY(Username) REFERENCES AMMINISTRATORE(Username),
    CONSTRAINT CHK_Rilevanza CHECK (Rilevanza >= 0 AND Rilevanza <= 10)
);

CREATE TABLE ESG_AMBIENTALE(
    Nome VARCHAR(30),
    cod_norm_rilevamento VARCHAR(30),
    FOREIGN KEY(Nome) REFERENCES PRIMARY KEY(Nome)
);



SELECT * FROM UTENTE;
SELECT Password FROM UTENTE WHERE Username = '814';

