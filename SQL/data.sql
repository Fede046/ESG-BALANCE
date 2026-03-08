-- Active: 1771787311626@@localhost@3308@test

-- NOTA: per campi testuali (Descrizione, CV, Immagine, ecc.) si intende un path, non un elenco di parole.

DROP DATABASE IF EXISTS TEST;
CREATE DATABASE TEST;
USE TEST;

-- 1. UTENTE  (nessuna dipendenza)
CREATE TABLE UTENTE (
    Username VARCHAR(30) PRIMARY KEY,
    CodiceFiscale VARCHAR(30),
    Password VARCHAR(30) NOT NULL,
    Luogo VARCHAR(30),
    Data VARCHAR(30)
);

-- 2. EMAIL  (dipende da UTENTE)
CREATE TABLE EMAIL (
    Username_Utente VARCHAR(30) NOT NULL,
    Indirizzo VARCHAR(255) NOT NULL,
    PRIMARY KEY (Username_Utente, Indirizzo),
    FOREIGN KEY (Username_Utente) REFERENCES UTENTE(Username)
);

-- 3. Sottotipi di UTENTE  (dipendono da UTENTE)
--    NOTA: il vincolo "un amministratore non può essere anche
--    revisore" non è esprimibile nativamente in SQL; va gestito
--    con un TRIGGER o a livello applicativo.
CREATE TABLE AMMINISTRATORE (
    Username VARCHAR(30) PRIMARY KEY,
    FOREIGN KEY (Username) REFERENCES UTENTE(Username)
);

CREATE TABLE REVISORE_ESG (
    Username VARCHAR(30) PRIMARY KEY,
    IndiceAffidabilita INT,
    NumRevisioni INT,
    FOREIGN KEY (Username) REFERENCES UTENTE(Username),
    CONSTRAINT CHK_Affidabilita CHECK (IndiceAffidabilita >= 1 AND IndiceAffidabilita <= 10)
);

CREATE TABLE RESPONSABILE_AZIENDALE (
    Username VARCHAR(30) NOT NULL PRIMARY KEY,
    CV VARCHAR(500),          -- path al file CV
    FOREIGN KEY (Username) REFERENCES UTENTE(Username)
);

-- 4. COMPETENZA  (dipende da REVISORE_ESG)
CREATE TABLE COMPETENZA (
    Nome VARCHAR(30) NOT NULL,
    Username VARCHAR(30) NOT NULL,
    PRIMARY KEY (Username, Nome),
    FOREIGN KEY (Username) REFERENCES REVISORE_ESG(Username)
);

-- 5. DICHIARA_COMPETENZA_REVISORE (dipende da COMPETENZA e REVISORE_ESG)
CREATE TABLE DICHIARA_COMPETENZA_REVISORE (
    Nome_competenza VARCHAR(30) NOT NULL,
    Username_revisore VARCHAR(30) NOT NULL,
    Livello INT,
    PRIMARY KEY (Username_revisore, Nome_competenza),
    FOREIGN KEY (Username_revisore, Nome_competenza) REFERENCES COMPETENZA(Username, Nome),
    FOREIGN KEY (Username_revisore) REFERENCES REVISORE_ESG(Username),
    CONSTRAINT CHK_Valutazione CHECK (Livello >= 0 AND Livello <= 5)
);

-- 6. INDICATORE_ESG  (dipende da AMMINISTRATORE)
CREATE TABLE INDICATORE_ESG (
    Nome VARCHAR(30) PRIMARY KEY,
    Username_Amministratore VARCHAR(30) NOT NULL,
    Immagine VARCHAR(500),   -- path all'immagine
    Rilevanza INT,
    FOREIGN KEY (Username_Amministratore) REFERENCES AMMINISTRATORE(Username),
    CONSTRAINT CHK_Rilevanza CHECK (Rilevanza >= 0 AND Rilevanza <= 10)
);

-- 7. Sottotipi di INDICATORE_ESG  (dipendono da INDICATORE_ESG)
CREATE TABLE ESG_AMBIENTALE (
    NomeEsg VARCHAR(30) PRIMARY KEY,
    cod_norm_rilevamento VARCHAR(30),
    FOREIGN KEY (NomeEsg) REFERENCES INDICATORE_ESG(Nome)
);

CREATE TABLE ESG_INDICATORE_SOCIALE (
    NomeEsg VARCHAR(30) PRIMARY KEY,
    Ambito VARCHAR(30),
    Frequenza_rilevazione INT,          -- espresso in numero di giorni
    FOREIGN KEY (NomeEsg) REFERENCES INDICATORE_ESG(Nome)
);

-- 8. VOCE  (nessuna dipendenza)
CREATE TABLE VOCE (
    Nome        VARCHAR(30) PRIMARY KEY,
    Descrizione VARCHAR(500)            -- path alla descrizione
);

-- 9. NOTA  (dipende da VOCE e REVISORE_ESG)
CREATE TABLE NOTA (
    ID INT PRIMARY KEY,   
    Data DATETIME,
    Testo VARCHAR(500),
    NomeVoce VARCHAR(30) NOT NULL,
    Username_Revisore_ESG VARCHAR(30) NOT NULL,
    FOREIGN KEY (NomeVoce) REFERENCES VOCE(Nome),
    FOREIGN KEY (Username_Revisore_ESG) REFERENCES REVISORE_ESG(Username)
);

-- 10. COLLEGA_ESG_VOCE  (dipende da VOCE e INDICATORE_ESG)
CREATE TABLE COLLEGA_ESG_VOCE (
    NomeVoce VARCHAR(30) NOT NULL,
    NomeEsg VARCHAR(30) NOT NULL,
    Fonte VARCHAR(30),
    Valore DECIMAL(10, 2), 
    Data DATETIME,
    PRIMARY KEY (NomeVoce, NomeEsg),
    FOREIGN KEY (NomeVoce) REFERENCES VOCE(Nome),
    FOREIGN KEY (NomeEsg) REFERENCES INDICATORE_ESG(Nome)
);

-- 11. AZIENDA  (dipende da RESPONSABILE_AZIENDALE)
CREATE TABLE AZIENDA (
    Ragione_sociale VARCHAR(30) PRIMARY KEY,
    Nome VARCHAR(30),
    p_IVA INT,
    Settore VARCHAR(30),
    n_dip INT,
    logo VARCHAR(30),
    nr_bilanci INT,
    Username_Responsabile_Aziendale VARCHAR(30) NOT NULL,
    FOREIGN KEY (Username_Responsabile_Aziendale) REFERENCES RESPONSABILE_AZIENDALE(Username)
);

-- 12. BILANCIO  (dipende da AZIENDA)
CREATE TABLE BILANCIO (
    id INT NOT NULL,
    Ragione_sociale_azienda VARCHAR(30) NOT NULL,
    Data_creazione DATETIME,
    Stato VARCHAR(30),
    PRIMARY KEY (id, Ragione_sociale_azienda),
    FOREIGN KEY (Ragione_sociale_azienda) REFERENCES AZIENDA(Ragione_sociale)
);

-- 13. GIUDIZIO  (dipende da REVISORE_ESG e BILANCIO)
CREATE TABLE GIUDIZIO (
    Id INT PRIMARY KEY,
    Esito VARCHAR(30),
    Data DATETIME,
    Rilievi VARCHAR(500),
    Username VARCHAR(30) NOT NULL,
    id_bilancio INT NOT NULL,
    Ragione_sociale_bilancio VARCHAR(30) NOT NULL,
    FOREIGN KEY (id_bilancio, Ragione_sociale_bilancio) REFERENCES BILANCIO(id, Ragione_sociale_azienda),
    FOREIGN KEY (Username) REFERENCES REVISORE_ESG(Username)
);

-- 14. VALUTA_REVISORE_BILANCIO (dipende da REVISORE_ESG e BILANCIO)
CREATE TABLE VALUTA_REVISORE_BILANCIO (
    Username_Revisore_ESG VARCHAR(30) NOT NULL,
    id_bilancio INT NOT NULL,
    PRIMARY KEY (Username_Revisore_ESG, id_bilancio),
    FOREIGN KEY (Username_Revisore_ESG) REFERENCES REVISORE_ESG(Username),
    FOREIGN KEY (id_bilancio) REFERENCES BILANCIO(id)
);

-- 15. ASSOCIA_BILANCIO_VOCE  (dipende da BILANCIO e VOCE)
CREATE TABLE ASSOCIA_BILANCIO_VOCE (
    Nome_voce VARCHAR(30) NOT NULL,
    id_bilancio INT NOT NULL,
    PRIMARY KEY (Nome_voce, id_bilancio),
    FOREIGN KEY (id_bilancio) REFERENCES BILANCIO(id),
    FOREIGN KEY (Nome_voce) REFERENCES VOCE(Nome)
);