USE TEST
--- Implementare	tutte	le	operazioni	sui	dati	(ove	possibile)	attraverso	stored	procedure.	
DELIMITER //

-- Popolamento degli indicatori ESG
CREATE PROCEDURE sp_CreaIndicatoreESG(IN p_nome VARCHAR(30), IN p_admin VARCHAR(30), IN p_rilevanza INT)
BEGIN
    INSERT INTO INDICATORE_ESG (Nome, Username_Amministratore, Rilevanza) 
    VALUES (p_nome, p_admin, p_rilevanza);
END //

-- Associazione di un revisore a un bilancio (attiva il trigger di stato)
CREATE PROCEDURE sp_AssegnaRevisore(IN p_revisore VARCHAR(30), IN p_id_bilancio INT)
BEGIN
    INSERT INTO VALUTA_REVISORE_BILANCIO (Username_Revisore_ESG, id_bilancio)
    VALUES (p_revisore, p_id_bilancio);
END //
-----------------------------------------------------------------------------------------------
-- Creazione di un nuovo bilancio
CREATE PROCEDURE sp_CreaBilancio(IN p_id INT, IN p_ragione_sociale VARCHAR(30))
BEGIN
    -- Stato iniziale predefinito: 'Creato'
    INSERT INTO BILANCIO (id, Ragione_sociale_azienda, Data_creazione, Stato)
    VALUES (p_id, p_ragione_sociale, NOW(), 'Creato');
END //

-- Inserimento competenze del revisore
CREATE PROCEDURE sp_AggiungiCompetenza(IN p_revisore VARCHAR(30), IN p_competenza VARCHAR(30), IN p_livello INT)
BEGIN
    -- Inseriamo prima nella tabella COMPETENZA se non esiste
    INSERT IGNORE INTO COMPETENZA (Nome, Username) VALUES (p_competenza, p_revisore);
    -- Poi registriamo il livello
    INSERT INTO DICHIARA_COMPETENZA_REVISORE (Nome_competenza, Username_revisore, Livello)
    VALUES (p_competenza, p_revisore, p_livello);
END //
-----------------------------------------------------------------------------------------------
-- Inserimento del Giudizio Finale
CREATE PROCEDURE sp_InserisciGiudizio(
    IN p_id_giudizio INT,
    IN p_esito VARCHAR(30), -- 'approvazione', 'approvazione con rilievi' o 'respingimento'
    IN p_revisore VARCHAR(30),
    IN p_id_bilancio INT,
    IN p_ragione_sociale VARCHAR(30)
)
BEGIN
    INSERT INTO GIUDIZIO (Id, Esito, Data, Username, id_bilancio, Ragione_sociale_bilancio)
    VALUES (p_id_giudizio, p_esito, NOW(), p_revisore, p_id_bilancio, p_ragione_sociale);
END //

DELIMITER ;

-------------------------------------------------------------------------------------------
DELIMITER //

/* --- OPERAZIONI SOLO PER REVISORI ESG --- */

-- 1. Inserimento delle note su voci di bilancio
CREATE PROCEDURE sp_InserisciNotaVoce(
    IN p_id_nota INT,
    IN p_testo VARCHAR(500),
    IN p_nome_voce VARCHAR(30),
    IN p_username_revisore VARCHAR(30)
)
BEGIN
    INSERT INTO NOTA (ID, Data, Testo, NomeVoce, Username_Revisore_ESG)
    VALUES (p_id_nota, NOW(), p_testo, p_nome_voce, p_username_revisore);
END //

-- 2. Inserimento del giudizio complessivo (Attiva il trigger per chiudere il bilancio)
CREATE PROCEDURE sp_InserisciGiudizioComplessivo(
    IN p_id_giudizio INT,
    IN p_esito ENUM(
        'approvazione',
        'approvazione con rilievi',
        'respingimento'
    ),
    IN p_rilievi VARCHAR(500),
    IN p_username_revisore VARCHAR(30),
    IN p_id_bilancio INT,
    IN p_ragione_sociale VARCHAR(30)
)
BEGIN
    INSERT INTO GIUDIZIO (Id, Esito, Data, Rilievi, Username, id_bilancio, Ragione_sociale_bilancio)
    VALUES (p_id_giudizio, p_esito, NOW(), p_rilievi, p_username_revisore, p_id_bilancio, p_ragione_sociale);
END //


/* --- OPERAZIONI SOLO PER RESPONSABILI AZIENDALI --- */

-- 3. Registrazione di un'azienda
CREATE PROCEDURE sp_RegistrazioneAzienda(
    IN p_ragione_sociale VARCHAR(30),
    IN p_nome VARCHAR(30),
    IN p_piva INT,
    IN p_settore VARCHAR(30),
    IN p_n_dip INT,
    IN p_logo VARCHAR(30),
    IN p_username_resp VARCHAR(30)
)
BEGIN
    INSERT INTO AZIENDA (Ragione_sociale, Nome, p_IVA, Settore, n_dip, logo, nr_bilanci, Username_Responsabile_Aziendale)
    VALUES (p_ragione_sociale, p_nome, p_piva, p_settore, p_n_dip, p_logo, 0, p_username_resp);
END //

-- 4. Creazione/Popolamento di un nuovo bilancio di esercizio
CREATE PROCEDURE sp_CreaBilancioEsercizio(
    IN p_id_bilancio INT,
    IN p_ragione_sociale VARCHAR(30)
)
BEGIN
    -- Lo stato iniziale è 'Creato' (diventerà 'In Revisione' tramite trigger quando si assegna un revisore)
    INSERT INTO BILANCIO (id, Ragione_sociale_azienda, Data_creazione, Stato)
    VALUES (p_id_bilancio, p_ragione_sociale, NOW(), 'Creato');
    
    -- Aggiorna il contatore bilanci nell'anagrafica azienda
    UPDATE AZIENDA SET nr_bilanci = nr_bilanci + 1 WHERE Ragione_sociale = p_ragione_sociale;
END //

-- 5. Associazione Voce a Bilancio (per il popolamento)
CREATE PROCEDURE sp_AssociaVoceBilancio(
    IN p_nome_voce VARCHAR(30),
    IN p_id_bilancio INT
)
BEGIN
    INSERT INTO ASSOCIA_BILANCIO_VOCE (Nome_voce, id_bilancio)
    VALUES (p_nome_voce, p_id_bilancio);
END //

-- 6. Inserimento dei valori degli indicatori ESG per singole voci
CREATE PROCEDURE sp_InserisciValoreESG(
    IN p_nome_voce VARCHAR(30),
    IN p_nome_esg VARCHAR(30),
    IN p_fonte VARCHAR(30),
    IN p_valore DECIMAL(10,2)
)
BEGIN
    INSERT INTO COLLEGA_ESG_VOCE (NomeVoce, NomeEsg, Fonte, Valore, Data)
    VALUES (p_nome_voce, p_nome_esg, p_fonte, p_valore, NOW())
    ON DUPLICATE KEY UPDATE Valore = p_valore, Data = NOW(), Fonte = p_fonte;
END //

DELIMITER ;

---------------------------------------------------------------------
--- Implementare	le	statistiche	menzionate	in	precedenza	mediante	viste.	
-- Numero di aziende e revisori
CREATE VIEW VISTA_CONTEGGI_GENERALI AS
SELECT 
    (SELECT COUNT(*) FROM AZIENDA) AS TotaleAziende,
    (SELECT COUNT(*) FROM REVISORE_ESG) AS TotaleRevisori;

-- Azienda con il valore più alto di affidabilità
CREATE VIEW VISTA_AZIENDA_TOP_AFFIDABILITA AS
SELECT Ragione_sociale, 
       (COUNT(CASE WHEN B.Stato = 'Approvato' THEN 1 END) * 100.0 / COUNT(B.id)) AS PercentualeApprovazione
FROM AZIENDA A
JOIN BILANCIO B ON A.Ragione_sociale = B.Ragione_sociale_azienda
GROUP BY A.Ragione_sociale
ORDER BY PercentualeApprovazione DESC
LIMIT 1;

-- Classifica bilanci per numero di indicatori ESG connessi
CREATE VIEW VISTA_CLASSIFICA_BILANCI_ESG AS
SELECT B.id, B.Ragione_sociale_azienda, COUNT(CEV.NomeEsg) AS NumIndicatori
FROM BILANCIO B
JOIN ASSOCIA_BILANCIO_VOCE ABV ON B.id = ABV.id_bilancio
JOIN COLLEGA_ESG_VOCE CEV ON ABV.Nome_voce = CEV.NomeVoce
GROUP BY B.id, B.Ragione_sociale_azienda
ORDER BY NumIndicatori DESC;

--------------------------------------------------------------------------------------
DELIMITER //

-- TRIGGER 1: Imposta "In Revisione" quando viene associato un revisore
CREATE TRIGGER trg_inizio_revisione
AFTER INSERT ON VALUTA_REVISORE_BILANCIO
FOR EACH ROW
BEGIN
    UPDATE BILANCIO 
    SET Stato = 'In Revisione'
    WHERE id = NEW.id_bilancio;
END //

--Utilizzare	un	trigger	per	cambiare	lo	stato	di	un	bilancio.	Il	trigger	viene	attivato	nel	
--momento	in	cui	un	revisore	inserisce	un	giudizio	su	un	bilancio.	Se	tutti	i	revisori	ESG	
--associati	a	quel	bilancio	hanno	inserito	i	loro	giudizi,	e	quest’ultimi	sono	tutti	pari	ad	
--“approvazione”o	“approvazione	con	rilievi”,	lo	stato	diventa	“approvato”.	Se	tutti	i	revisori	
--ESG	associati	a	quel	bilancio	hanno	inserito	i	loro	giudizi,	ed	almeno	uno	è	pari	a	
--“respingimento”,	lo	stato	diventa	pari	a	“respinto”.	

-- TRIGGER 2: Calcola lo stato finale (Approvato/Respinto) al termine dei giudizi
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
    WHERE id_bilancio = NEW.id_bilancio;

    -- Conta quanti giudizi sono stati inseriti finora
    SELECT COUNT(*) INTO total_giudizi 
    FROM GIUDIZIO 
    WHERE id_bilancio = NEW.id_bilancio;

    -- Se tutti i revisori hanno espresso un giudizio
    IF total_revisori = total_giudizi THEN
        -- Controlla se c'è almeno un respingimento
        SELECT COUNT(*) INTO count_respinti 
        FROM GIUDIZIO 
        WHERE id_bilancio = NEW.id_bilancio AND Esito = 'respingimento';

        IF count_respinti > 0 THEN
            UPDATE BILANCIO SET Stato = 'Respinto' WHERE id = NEW.id_bilancio;
        ELSE
            UPDATE BILANCIO SET Stato = 'Approvato' WHERE id = NEW.id_bilancio;
        END IF;
    END IF;
END //

DELIMITER ;
---------------------------------------------------------------------

