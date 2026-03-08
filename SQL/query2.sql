--• Autenticazione/registrazione	sulla	piattaforma	
DELIMITER //

-- PROCEDURA DI REGISTRAZIONE
CREATE PROCEDURE sp_Registrazione(
    IN p_username VARCHAR(30), 
    IN p_cf VARCHAR(30), 
    IN p_password VARCHAR(30), 
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
        -- Indice affidabilità base a 10 e 0 revisioni
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
    IN p_password VARCHAR(30)
)
BEGIN
    -- Verifica le credenziali e restituisce il ruolo dell'utente
    SELECT U.Username,
           CASE
               WHEN A.Username IS NOT NULL THEN 'amministratore'
               WHEN R.Username IS NOT NULL THEN 'revisore'
               WHEN RA.Username IS NOT NULL THEN 'responsabile'
               ELSE 'sconosciuto'
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

--• Popolamento	della	lista	degli	indicatori	ESG	
-- Questa procedura inserisce prima i dati nella tabella padre e poi, in base al tipo, nella tabella figlia corrispondente.
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
END //
-- DA RIVEDEREEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEE CIUPPA
-- DA RIVEDEREEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEE CIUPPA
-- DA RIVEDEREEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEE CIUPPA
-- DA RIVEDEREEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEE CIUPPA
--• Creazione	del	“template”	di	bilancio	di	esercizio	DA RIVEDEREEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEE CIUPPA
CREATE PROCEDURE sp_CreaVoceTemplate(
    IN p_nome VARCHAR(30),
    IN p_descrizione VARCHAR(500)
)
BEGIN
    INSERT INTO VOCE (Nome, Descrizione)
    VALUES (p_nome, p_descrizione);
END //

--• Associazione	di	revisore	ESG	ad	un	bilancio	aziendale
-- DA RIVEDEREEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEE CIUPPA
-- DA RIVEDEREEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEE CIUPPA
CREATE PROCEDURE sp_AssociaRevisore(
    IN p_revisore VARCHAR(30),
    IN p_id_bilancio INT
)
BEGIN
    INSERT INTO VALUTA_REVISORE_BILANCIO (Username_Revisore_ESG, id_bilancio)
    VALUES (p_revisore, p_id_bilancio);
END //