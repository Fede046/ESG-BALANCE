-- Autenticazione/registrazione sulla piattaforma
DELIMITER //

-- PROCEDURA DI REGISTRAZIONE
CREATE PROCEDURE sp_Registrazione(
    IN p_username VARCHAR(30), 
    IN p_cf VARCHAR(30), 
    IN p_password VARCHAR(255), 
    IN p_luogo VARCHAR(30), 
    IN p_data VARCHAR(30),
    IN p_ruolo VARCHAR(30), -- 'amministratore', 'revisore', 'responsabile'
    IN p_extra VARCHAR(500) -- CV per il responsabile, stringa vuota per gli altri
)
BEGIN
    -- 1. Creazione dell'utente base
    INSERT INTO UTENTE (Username, CodiceFiscale, Password, Luogo, Data)
    VALUES (p_username, p_cf, p_password, p_luogo, p_data);

    -- 2. Assegnazione del ruolo specifico
    IF p_ruolo = 'amministratore' THEN
        INSERT INTO AMMINISTRATORE (Username) VALUES (p_username);
        
    ELSEIF p_ruolo = 'revisore' THEN
        -- Indice affidabilita' base a 10 e 0 revisioni
        INSERT INTO REVISORE_ESG (Username, IndiceAffidabilita, NumRevisioni) 
        VALUES (p_username, 10, 0); 
        
    ELSEIF p_ruolo = 'responsabile' THEN
        INSERT INTO RESPONSABILE_AZIENDALE (Username, CV) 
        VALUES (p_username, p_extra);
    END IF;
END //


-- PROCEDURA DI AUTENTICAZIONE (LOGIN)
CREATE PROCEDURE sp_Login(
    IN p_username VARCHAR(30), 
    IN p_password VARCHAR(255)
)
BEGIN
    -- Verifica le credenziali e restituisce il ruolo dell'utente
    SELECT U.Username,
           CASE
               WHEN A.Username IS NOT NULL THEN 'amministratore'
               WHEN R.Username IS NOT NULL THEN 'revisore'
               WHEN RA.Username IS NOT NULL THEN 'responsabile'
           END AS Ruolo
    FROM UTENTE U
    LEFT JOIN AMMINISTRATORE A ON U.Username = A.Username
    LEFT JOIN REVISORE_ESG R ON U.Username = R.Username
    LEFT JOIN RESPONSABILE_AZIENDALE RA ON U.Username = RA.Username
    WHERE U.Username = p_username AND U.Password = p_password;
END //

DELIMITER ;

-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

-- Popolamento della lista degli indicatori ESG
-- Questa procedura inserisce prima i dati nella tabella padre e poi, in base al tipo, nella tabella figlia corrispondente.
DELIMITER //
CREATE PROCEDURE sp_PopolaIndicatoreESG(
    IN p_nome VARCHAR(30),
    IN p_admin VARCHAR(30),
    IN p_immagine VARCHAR(500),
    IN p_rilevanza INT,
    IN p_tipo VARCHAR(20), -- Valori attesi: 'ambientale', 'sociale' o null
    IN p_cod_norm VARCHAR(30), -- Solo per ESG_AMBIENTALE
    IN p_ambito VARCHAR(30),   -- Solo per ESG_INDICATORE_SOCIALE
    IN p_frequenza INT         -- Solo per ESG_INDICATORE_SOCIALE
)
BEGIN
    -- Inserimento nella super-classe
    INSERT INTO INDICATORE_ESG (Nome, Username_Amministratore, Immagine, Rilevanza)
    VALUES (p_nome, p_admin, p_immagine, p_rilevanza);

    -- Smistamento nelle sotto-classi
    IF p_tipo = 'ambientale' THEN
        INSERT INTO ESG_AMBIENTALE (NomeEsg, cod_norm_rilevamento)
        VALUES (p_nome, p_cod_norm);
    ELSEIF p_tipo = 'sociale' THEN
        INSERT INTO ESG_INDICATORE_SOCIALE (NomeEsg, Ambito, Frequenza_rilevazione)
        VALUES (p_nome, p_ambito, p_frequenza);
    END IF;
    -- Se p_tipo e' NULL o stringa vuota, l'indicatore rimane generico (consentito dalla traccia)
END //
DELIMITER ;

-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

-- Creazione del "template" di bilancio di esercizio
-- significa semplicemente permettere all'Amministratore di definire e popolare la tabella VOCE.
DELIMITER //
CREATE PROCEDURE sp_CreaVoceTemplate(
    IN p_nome VARCHAR(30),
    IN p_descrizione VARCHAR(500),
    IN p_username_amministratore VARCHAR(30)
)
BEGIN
    INSERT INTO VOCE (Nome, Descrizione, Username_Amministratore)
    VALUES (p_nome, p_descrizione, p_username_amministratore);
END //
DELIMITER ;

-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

-- Associazione di revisore ESG ad un bilancio aziendale

DELIMITER //
CREATE PROCEDURE sp_AssociaRevisore(
    IN p_revisore VARCHAR(30),
    IN p_id_bilancio INT,
    IN p_ragione_sociale VARCHAR(30)
)
BEGIN
    INSERT INTO VALUTA_REVISORE_BILANCIO (
        Username_Revisore_ESG,
        id_bilancio,
        Ragione_sociale_bilancio
    )
    VALUES (
        p_revisore,
        p_id_bilancio,
        p_ragione_sociale
    );
END //
DELIMITER ;

-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

-- Inserimento delle proprie competenze (nome competenza + livello)
DELIMITER //
CREATE PROCEDURE sp_InserisciCompetenzaRevisore(
    IN p_username_revisore VARCHAR(30),
    IN p_nome_competenza VARCHAR(30),
    IN p_livello INT
)
BEGIN
    -- 1. Registra la competenza per il revisore (se non esiste gia')
    -- Usiamo INSERT IGNORE perche' la Primary Key e' (Username, Nome)
    INSERT IGNORE INTO COMPETENZA (Nome, Username)
    VALUES (p_nome_competenza, p_username_revisore);

    -- 2. Inserisce o aggiorna il livello della competenza dichiarata
    -- Rispettando il vincolo CHK_Valutazione (0-5) definito nella tabella
    INSERT INTO DICHIARA_COMPETENZA_REVISORE (Nome_competenza, Username_revisore, Livello)
    VALUES (p_nome_competenza, p_username_revisore, p_livello)
    ON DUPLICATE KEY UPDATE Livello = p_livello;
END //
DELIMITER ;

-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

-- Inserimento delle note su voci di bilancio
DELIMITER //
CREATE PROCEDURE sp_InserisciNotaVoce(
    IN p_testo VARCHAR(500),
    IN p_nome_voce VARCHAR(30),
    IN p_username_revisore VARCHAR(30),
    IN p_id_bilancio INT,
    IN p_ragione_sociale VARCHAR(30)
)
BEGIN
    -- Inserisce una nuova nota collegando il revisore alla voce specifica.
    -- La funzione NOW() registra automaticamente il timestamp esatto dell'operazione.
    INSERT INTO NOTA (Data, Testo, Username_Revisore_ESG, NomeVoce, id_bilancio, Ragione_sociale_bilancio)
    VALUES (NOW(), p_testo, p_username_revisore, p_nome_voce, p_id_bilancio, p_ragione_sociale);
END //
DELIMITER ;

-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

-- Inserimento del giudizio complessivo
DELIMITER //
CREATE PROCEDURE sp_InserisciGiudizioComplessivo(
    IN p_esito VARCHAR(30),
    IN p_rilievi VARCHAR(500),
    IN p_username_revisore VARCHAR(30),
    IN p_id_bilancio INT,
    IN p_ragione_sociale VARCHAR(30)
)
BEGIN
    -- Inserisce il giudizio finale del revisore per quel determinato bilancio.
    -- La data del giudizio viene registrata automaticamente al momento dell'inserimento tramite NOW().
    INSERT INTO GIUDIZIO (Esito, Data, Rilievi, Username, id_bilancio, Ragione_sociale_bilancio)
    
    VALUES (p_esito, NOW(), p_rilievi, p_username_revisore, p_id_bilancio, p_ragione_sociale);
END //
DELIMITER ;

-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

-- Registrazione di un'azienda
DELIMITER //
CREATE PROCEDURE sp_RegistraAzienda(
    IN p_ragione_sociale VARCHAR(30),
    IN p_nome VARCHAR(30),
    IN p_p_iva VARCHAR(11),
    IN p_settore VARCHAR(30),
    IN p_n_dip INT,
    IN p_logo VARCHAR(255),
    IN p_username_resp VARCHAR(30)
)
BEGIN
    -- Inserisce una nuova azienda nel sistema.
    -- Il campo 'nr_bilanci' viene inizializzato a 0 di default.
    INSERT INTO AZIENDA (
        Ragione_sociale, 
        Nome, 
        p_IVA, 
        Settore, 
        n_dip, 
        logo, 
        nr_bilanci, 
        Username_Responsabile_Aziendale
    )
    VALUES (
        p_ragione_sociale, 
        p_nome, 
        p_p_iva, 
        p_settore, 
        p_n_dip, 
        p_logo, 
        0, 
        p_username_resp
    );
END //
DELIMITER ;

-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

-- Creazione/popolamento di un nuovo bilancio di esercizio

-- 1. CREAZIONE DEL BILANCIO
DELIMITER //
CREATE PROCEDURE sp_CreaBilancioEsercizio(
    IN p_id_bilancio INT,
    IN p_ragione_sociale VARCHAR(30)
)
BEGIN
    -- Crea il record del bilancio. Lo stato iniziale di default e' 'bozza' (come da ENUM).
    -- La data di creazione viene registrata in automatico con NOW().
    INSERT INTO BILANCIO (id, Ragione_sociale_azienda, Data_creazione, Stato)
    VALUES (p_id_bilancio, p_ragione_sociale, NOW(), 'bozza');
    
    -- Aggiorna il contatore dei bilanci creati da quell'azienda
    UPDATE AZIENDA 
    SET nr_bilanci = nr_bilanci + 1 
    WHERE Ragione_sociale = p_ragione_sociale;
END //
DELIMITER ;

-- 2. POPOLAMENTO DEL BILANCIO (Associazione delle voci)

DELIMITER //
CREATE PROCEDURE sp_PopolaBilancioEsercizio(
    IN p_id_bilancio INT,
    IN p_nome_voce VARCHAR(30),
    IN p_ragione_sociale VARCHAR(30),
    IN p_valore INT
)
BEGIN
    INSERT INTO ASSOCIA_BILANCIO_VOCE (Nome_voce, id_bilancio, Ragione_sociale_bilancio, Valore)
    VALUES (p_nome_voce, p_id_bilancio, p_ragione_sociale, p_valore);
END //
DELIMITER ;

-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

-- Inserimento dei valori degli indicatori ESG per singole voci di bilancio
DELIMITER //
CREATE PROCEDURE sp_InserisciValoreESG(
    IN p_nome_voce VARCHAR(30),
    IN p_nome_esg VARCHAR(30),
    IN p_fonte VARCHAR(30),
    IN p_valore DECIMAL(10,2),
    IN p_data        DATE
)
BEGIN
    -- Inserisce il valore dell'indicatore per la specifica voce.
    -- La data viene registrata automaticamente.
    INSERT INTO COLLEGA_ESG_VOCE (NomeVoce, NomeEsg, Fonte, Valore, Data)
    VALUES (p_nome_voce, p_nome_esg, p_fonte, p_valore, p_data)
    ON DUPLICATE KEY UPDATE 
        Valore = p_valore, 
        Fonte = p_fonte, 
        Data = p_data;
END //
DELIMITER ;

-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++


-- VIEW

-- 1. Mostrare il Numero di aziende registrate in piattaforma
CREATE VIEW VISTA_NUMERO_AZIENDE AS
SELECT COUNT(*) AS Numero_Aziende 
FROM AZIENDA;

-- 2. Mostrare il Numero di revisori ESG registrati in piattaforma
CREATE VIEW VISTA_NUMERO_REVISORI AS
SELECT COUNT(*) AS Numero_Revisori 
FROM REVISORE_ESG;

-- 3. Azienda con il valore piu' alto di affidabilita'
-- (Percentuale di giudizi di "approvazione" sul totale dei giudizi ricevuti dall'azienda)
CREATE VIEW VISTA_AZIENDA_TOP_AFFIDABILITA AS
SELECT 
    B.Ragione_sociale_azienda AS Azienda,
    (COUNT(CASE WHEN G.Esito = 'approvazione' THEN 1 END) * 100.0 / COUNT(G.Id)) AS PercentualeAffidabilita
FROM BILANCIO B
JOIN GIUDIZIO G 
    ON B.id = G.id_bilancio 
    AND B.Ragione_sociale_azienda = G.Ragione_sociale_bilancio
GROUP BY B.Ragione_sociale_azienda
ORDER BY PercentualeAffidabilita DESC
LIMIT 1;

-- 4. Classifica dei bilanci aziendali per numero totale di indicatori ESG connessi
-- Uniamo BILANCIO -> ASSOCIA_BILANCIO_VOCE -> COLLEGA_ESG_VOCE
CREATE VIEW VISTA_CLASSIFICA_BILANCI AS
SELECT 
    B.id AS ID_Bilancio,
    B.Ragione_sociale_azienda AS Azienda,
    COUNT(CEV.NomeEsg) AS Totale_Indicatori_ESG
FROM BILANCIO B
LEFT JOIN ASSOCIA_BILANCIO_VOCE ABV 
    ON B.id = ABV.id_bilancio 
    AND B.Ragione_sociale_azienda = ABV.Ragione_sociale_bilancio
LEFT JOIN COLLEGA_ESG_VOCE CEV ON ABV.Nome_voce = CEV.NomeVoce
GROUP BY B.id, B.Ragione_sociale_azienda
ORDER BY Totale_Indicatori_ESG DESC;


-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++


-- TRIGGER

DELIMITER //

-- TRIGGER 1: Imposta 'in revisione' quando viene associato un revisore
CREATE TRIGGER trg_inizio_revisione
AFTER INSERT ON VALUTA_REVISORE_BILANCIO
FOR EACH ROW
BEGIN
    UPDATE BILANCIO 
    SET Stato = 'in revisione'
    WHERE id = NEW.id_bilancio
      AND Ragione_sociale_azienda = NEW.Ragione_sociale_bilancio;
END //

-- TRIGGER 2: Calcola lo stato finale ('approvato'/'respinto') al termine di tutti i giudizi
CREATE TRIGGER trg_conclusione_revisione
AFTER INSERT ON GIUDIZIO
FOR EACH ROW
BEGIN
    DECLARE total_revisori INT;
    DECLARE total_giudizi INT;
    DECLARE count_respinti INT;

    -- Conta quanti revisori sono assegnati a questo bilancio
    SELECT COUNT(*) INTO total_revisori 
    FROM VALUTA_REVISORE_BILANCIO 
    WHERE id_bilancio = NEW.id_bilancio
      AND Ragione_sociale_bilancio = NEW.Ragione_sociale_bilancio;

    -- Conta quanti giudizi sono stati inseriti finora per questo bilancio
    SELECT COUNT(*) INTO total_giudizi 
    FROM GIUDIZIO 
    WHERE id_bilancio = NEW.id_bilancio
      AND Ragione_sociale_bilancio = NEW.Ragione_sociale_bilancio;

    -- Se tutti i revisori hanno espresso un giudizio
    IF total_revisori = total_giudizi THEN
        -- Controlla se c'e' almeno un respingimento
        SELECT COUNT(*) INTO count_respinti 
        FROM GIUDIZIO 
        WHERE id_bilancio = NEW.id_bilancio
          AND Ragione_sociale_bilancio = NEW.Ragione_sociale_bilancio
          AND Esito = 'respingimento';

        IF count_respinti > 0 THEN
            UPDATE BILANCIO 
            SET Stato = 'respinto' 
            WHERE id = NEW.id_bilancio
              AND Ragione_sociale_azienda = NEW.Ragione_sociale_bilancio;
        ELSE
            UPDATE BILANCIO 
            SET Stato = 'approvato' 
            WHERE id = NEW.id_bilancio
              AND Ragione_sociale_azienda = NEW.Ragione_sociale_bilancio;
        END IF;
    END IF;
END //

DELIMITER ;
