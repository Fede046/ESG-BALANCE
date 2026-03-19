-- ============================================================
--  POPULATE TEST DATABASE
-- ============================================================
USE TEST;

-- ─────────────────────────────────────────────
-- 1. UTENTE
-- ─────────────────────────────────────────────
INSERT INTO UTENTE (Username, CodiceFiscale, Password, Luogo, Data) VALUES
('mario.rossi',    'RSSMRA80A01H501Z', 'pass123',  'Roma',    '1980-01-01'),
('giulia.bianchi', 'BNCGLI90B02F205Y', 'pass456',  'Milano',  '1990-02-02'),
('luca.verdi',     'VRDLCU85C03L219X', 'pass789',  'Torino',  '1985-03-03'),
('anna.neri',      'NRANNA75D04G273W', 'passabc',  'Napoli',  '1975-04-04'),
('paolo.gialli',   'GLLPLA92E05A662V', 'passdef',  'Bologna', '1992-05-05'),
('sara.blu',       'BLASRA88F06H501U', 'passghi',  'Firenze', '1988-06-06');

-- ─────────────────────────────────────────────
-- 2. EMAIL
-- ─────────────────────────────────────────────
INSERT INTO EMAIL (Username_Utente, Indirizzo) VALUES
('mario.rossi',    'mario.rossi@gmail.com'),
('mario.rossi',    'mario.rossi@lavoro.it'),
('giulia.bianchi', 'giulia.bianchi@gmail.com'),
('luca.verdi',     'luca.verdi@yahoo.it'),
('anna.neri',      'anna.neri@outlook.com'),
('paolo.gialli',   'paolo.gialli@gmail.com'),
('sara.blu',       'sara.blu@libero.it');

-- ─────────────────────────────────────────────
-- 3a. AMMINISTRATORE 
-- ─────────────────────────────────────────────
INSERT INTO AMMINISTRATORE (Username) VALUES
('mario.rossi');

-- ─────────────────────────────────────────────
-- 3b. REVISORE_ESG 
-- ─────────────────────────────────────────────
INSERT INTO REVISORE_ESG (Username, IndiceAffidabilita, NumRevisioni) VALUES
('giulia.bianchi', 8, 15),
('luca.verdi',     6, 7);

-- ─────────────────────────────────────────────
-- 3c. RESPONSABILE_AZIENDALE  
-- ─────────────────────────────────────────────
INSERT INTO RESPONSABILE_AZIENDALE (Username, CV) VALUES
('anna.neri',    '/cv/anna_neri.pdf'),
('paolo.gialli', '/cv/paolo_gialli.pdf'),
('sara.blu',     '/cv/sara_blu.pdf');

-- ─────────────────────────────────────────────
-- 4. COMPETENZA
-- ─────────────────────────────────────────────
INSERT INTO COMPETENZA (Nome, Username) VALUES
('Ambiente',       'giulia.bianchi'),
('Governance',     'giulia.bianchi'),
('Sociale',        'luca.verdi'),
('Rendicontazione','luca.verdi');

-- ─────────────────────────────────────────────
-- 5. DICHIARA_COMPETENZA_REVISORE
-- ─────────────────────────────────────────────
INSERT INTO DICHIARA_COMPETENZA_REVISORE (Nome_competenza, Username_revisore, Livello) VALUES
('Ambiente',       'giulia.bianchi', 5),
('Governance',     'giulia.bianchi', 4),
('Sociale',        'luca.verdi',     3),
('Rendicontazione','luca.verdi',     4);

-- ─────────────────────────────────────────────
-- 6. INDICATORE_ESG
-- ─────────────────────────────────────────────
INSERT INTO INDICATORE_ESG (Nome, Username_Amministratore, Immagine, Rilevanza) VALUES
('Emissioni CO2',    'mario.rossi', '/img/co2.png',    9),
('Parita di genere', 'mario.rossi', '/img/gender.png', 7),
('Consumo idrico',   'mario.rossi', '/img/water.png',  8),
('Diversita CdA',    'mario.rossi', '/img/board.png',  6),
('Sicurezza lavoro', 'mario.rossi', '/img/safety.png', 8);

-- ─────────────────────────────────────────────
-- 7a. ESG_AMBIENTALE
-- ─────────────────────────────────────────────
INSERT INTO ESG_AMBIENTALE (NomeEsg, cod_norm_rilevamento) VALUES
('Emissioni CO2',  'ISO-14064'),
('Consumo idrico', 'ISO-14046');

-- ─────────────────────────────────────────────
-- 7b. ESG_INDICATORE_SOCIALE
-- ─────────────────────────────────────────────
INSERT INTO ESG_INDICATORE_SOCIALE (NomeEsg, Ambito, Frequenza_rilevazione) VALUES
('Parita di genere', 'Risorse Umane', 365),
('Diversita CdA',    'Governance',   365),
('Sicurezza lavoro', 'Salute',        90);

-- ─────────────────────────────────────────────
-- 8. VOCE
-- ─────────────────────────────────────────────
INSERT INTO VOCE (Nome, Descrizione, Username_Amministratore) VALUES
('Ricavi',            '/desc/ricavi.pdf',    'mario.rossi'),
('Costi operativi',   '/desc/costi_op.pdf',  'mario.rossi'),
('EBITDA',            '/desc/ebitda.pdf',    'mario.rossi'),
('Patrimonio netto',  '/desc/pat_netto.pdf', 'mario.rossi'),
('Debiti finanziari', '/desc/debiti.pdf',    'mario.rossi');

-- ─────────────────────────────────────────────
-- 9. AZIENDA
-- ─────────────────────────────────────────────
INSERT INTO AZIENDA (Ragione_sociale, Nome, p_IVA, Settore, n_dip, logo, nr_bilanci, Username_Responsabile_Aziendale) VALUES
('GreenTech SRL', 'GreenTech', 12345678, 'Tecnologia',  150, '/logo/gt.png', 3, 'anna.neri'),
('EcoFarm SPA',   'EcoFarm',   87654321, 'Agricoltura',  80, '/logo/ef.png', 2, 'paolo.gialli'),
('BluEnergy SRL', 'BluEnergy', 11223344, 'Energia',     200, '/logo/be.png', 3, 'sara.blu');

-- ─────────────────────────────────────────────
-- 10. BILANCIO
-- ─────────────────────────────────────────────
INSERT INTO BILANCIO (id, Ragione_sociale_azienda, Data_creazione, Stato) VALUES
(1, 'GreenTech SRL', '2022-03-31 00:00:00', 'bozza'),
(2, 'GreenTech SRL', '2023-03-31 00:00:00', 'bozza'),
(3, 'GreenTech SRL', '2024-03-31 00:00:00', 'in revisione'),
(4, 'EcoFarm SPA',   '2023-04-30 00:00:00', 'approvato'),
(5, 'EcoFarm SPA',   '2024-04-30 00:00:00', 'in revisione'),
(6, 'BluEnergy SRL', '2022-06-30 00:00:00', 'approvato'),
(7, 'BluEnergy SRL', '2023-06-30 00:00:00', 'approvato'),
(8, 'BluEnergy SRL', '2024-06-30 00:00:00', 'bozza');

-- ─────────────────────────────────────────────
-- 11. GIUDIZIO
-- ─────────────────────────────────────────────
INSERT INTO GIUDIZIO (Id, Esito, Data, Rilievi, Username, id_bilancio, Ragione_sociale_bilancio) VALUES
(1, 'approvazione',             '2022-05-01 00:00:00', '/rilievi/r1.pdf', 'giulia.bianchi', 1, 'GreenTech SRL'),
(2, 'approvazione',             '2023-05-01 00:00:00', '/rilievi/r2.pdf', 'giulia.bianchi', 2, 'GreenTech SRL'),
(3, 'approvazione con rilievi', '2023-06-15 00:00:00', '/rilievi/r3.pdf', 'luca.verdi',     4, 'EcoFarm SPA'),
(4, 'respingimento',            '2022-08-01 00:00:00', '/rilievi/r4.pdf', 'giulia.bianchi', 6, 'BluEnergy SRL'),
(5, 'approvazione con rilievi', '2023-08-01 00:00:00', '/rilievi/r5.pdf', 'luca.verdi',     7, 'BluEnergy SRL');

-- ─────────────────────────────────────────────
-- 12. VALUTA_REVISORE_BILANCIO
-- ─────────────────────────────────────────────
INSERT INTO VALUTA_REVISORE_BILANCIO (Username_Revisore_ESG, id_bilancio, Ragione_sociale_bilancio) VALUES
('giulia.bianchi', 1, 'GreenTech SRL'),
('giulia.bianchi', 2, 'GreenTech SRL'),
('giulia.bianchi', 3, 'GreenTech SRL'),
('luca.verdi',     4, 'EcoFarm SPA'),
('luca.verdi',     5, 'EcoFarm SPA'),
('giulia.bianchi', 6, 'BluEnergy SRL'),
('luca.verdi',     7, 'BluEnergy SRL'),
('luca.verdi',     8, 'BluEnergy SRL');

-- ─────────────────────────────────────────────
-- 13. ASSOCIA_BILANCIO_VOCE
-- ─────────────────────────────────────────────
INSERT INTO ASSOCIA_BILANCIO_VOCE (Nome_voce, id_bilancio, Ragione_sociale_bilancio, Valore) VALUES
('Ricavi',            1, 'GreenTech SRL', 500000),
('Costi operativi',   1, 'GreenTech SRL', 300000),
('EBITDA',            1, 'GreenTech SRL', 200000),
('Ricavi',            2, 'GreenTech SRL', 550000),
('Costi operativi',   2, 'GreenTech SRL', 320000),
('Patrimonio netto',  2, 'GreenTech SRL', 180000),
('Ricavi',            3, 'GreenTech SRL', 600000),
('Ricavi',            4, 'EcoFarm SPA',   400000),
('EBITDA',            4, 'EcoFarm SPA',   150000),
('Debiti finanziari', 4, 'EcoFarm SPA',    80000),
('Ricavi',            5, 'EcoFarm SPA',   420000),
('Costi operativi',   5, 'EcoFarm SPA',   210000),
('Ricavi',            6, 'BluEnergy SRL', 900000),
('Patrimonio netto',  6, 'BluEnergy SRL', 450000),
('EBITDA',            6, 'BluEnergy SRL', 300000),
('Ricavi',            7, 'BluEnergy SRL', 950000),
('Costi operativi',   7, 'BluEnergy SRL', 500000),
('Debiti finanziari', 8, 'BluEnergy SRL', 120000);

-- ─────────────────────────────────────────────
-- 14. NOTA
-- ─────────────────────────────────────────────
INSERT INTO NOTA (ID, Data, Testo, Username_Revisore_ESG, NomeVoce, id_bilancio, Ragione_sociale_bilancio) VALUES
(1, '2024-01-10 10:00:00', '/note/nota1.txt', 'giulia.bianchi', 'Ricavi',           1, 'GreenTech SRL'),
(2, '2024-02-15 11:30:00', '/note/nota2.txt', 'giulia.bianchi', 'Costi operativi',  1, 'GreenTech SRL'),
(3, '2024-03-20 09:15:00', '/note/nota3.txt', 'luca.verdi',     'EBITDA',           4, 'EcoFarm SPA'),
(4, '2024-04-05 14:00:00', '/note/nota4.txt', 'luca.verdi',     'Debiti finanziari',4, 'EcoFarm SPA');

-- ─────────────────────────────────────────────
-- 15. COLLEGA_ESG_VOCE
-- ─────────────────────────────────────────────
INSERT INTO COLLEGA_ESG_VOCE (NomeVoce, NomeEsg, Fonte, Valore, Data) VALUES
('Ricavi',            'Emissioni CO2',    'GRI',  1250.50, '2024-01-01 00:00:00'),
('Costi operativi',   'Consumo idrico',   'SASB',  340.00, '2024-01-01 00:00:00'),
('EBITDA',            'Parita di genere', 'GRI',    65.30, '2024-01-01 00:00:00'),
('Patrimonio netto',  'Diversita CdA',    'GRI',    48.00, '2024-01-01 00:00:00'),
('Debiti finanziari', 'Sicurezza lavoro', 'OSHA',    2.10, '2024-01-01 00:00:00');