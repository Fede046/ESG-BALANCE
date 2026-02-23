# Roadmap Progetto ESG-BALANCE

## 1. Analisi dei requisiti

### 1.1 Analisi del dominio
- Rileggere la traccia del progetto e annotare tutti i tipi di attori coinvolti e i loro compiti principali.
- Elencare tutte le informazioni da memorizzare per ciascuna entità descritta nella traccia.
- Costruire il glossario dei termini con una definizione chiara per ogni concetto chiave presente nella traccia.
- Redigere l'elenco completo delle operazioni sui dati, suddivise per ruolo:
  - Operazioni comuni: registrazione e autenticazione.
  - Operazioni amministratore: gestione template di bilancio, gestione indicatori ESG, assegnazione revisori ai bilanci.
  - Operazioni revisore ESG: gestione competenze, inserimento note su voci, inserimento giudizio complessivo.
  - Operazioni responsabile aziendale: registrazione azienda, creazione e compilazione bilanci, inserimento valori indicatori ESG.
  - Statistiche visibili a tutti gli utenti.

### 1.2 Tavola dei volumi e carichi
- Compilare la tavola dei volumi indicando il numero stimato di occorrenze per ciascuna entità (aziende: 10, bilanci per azienda: 5, voci contabili, indicatori ESG, revisori, note, giudizi).
- Elencare le operazioni da analizzare per la ridondanza sul campo `#nr_bilanci`:
  - Inserimento di una nuova azienda con i bilanci degli ultimi tre anni (1 volta/mese, interattiva).
  - Conteggio del numero totale di bilanci di esercizio per tutte le aziende (3 volte/mese, batch).
  - Rimozione di un'azienda con tutti i bilanci associati (1 volta/mese, batch).
- Specificare tipo di esecuzione (batch o interattiva) e frequenza per ciascuna operazione.

---

## 2. Progettazione concettuale e logica

### 2.1 Schema E-R
- Identificare tutte le entità, gli attributi e le relazioni a partire dalla traccia.
- Modellare le specializzazioni degli utenti e degli indicatori ESG con le rispettive cardinalità.
- Definire le business rules derivanti dalla traccia (vincoli sugli stati del bilancio, vincoli di cardinalità, vincoli sulle categorie ESG).
- Disegnare lo schema E-R completo e verificarne la coerenza con i requisiti.

### 2.2 Analisi della ridondanza `#nr_bilanci`
- Definire due scenari alternativi:
  - Scenario A: campo `#nr_bilanci` non memorizzato, conteggio calcolato con query al momento del bisogno.
  - Scenario B: campo `#nr_bilanci` memorizzato e mantenuto aggiornato tramite trigger.
- Calcolare i costi di lettura e scrittura per le tre operazioni della tavola dei volumi, applicando i coefficienti: wI = 1, wB = 0.5, a = 2.
- Confrontare i due scenari e documentare la scelta finale con motivazione esplicita nella relazione.

### 2.3 Schema relazionale
- Tradurre lo schema E-R ristrutturato in schema relazionale, definendo per ogni tabella:
  - Attributi, tipi di dato, chiave primaria, chiavi esterne e vincoli di integrità referenziale.
- Scrivere la lista completa dei vincoli inter-relazionali.
- Verificare la normalizzazione almeno fino alla 3NF, evidenziando eventuali violazioni e le correzioni apportate.
- Verificare che il numero di tabelle rispetti il minimo previsto dal regolamento (almeno 12).

---

## 3. Implementazione database MySQL

### 3.1 Script DDL e vincoli fisici
- Scrivere lo script SQL per la creazione del database `esg_balance` e di tutte le tabelle con:
  - Chiavi primarie, chiavi esterne con azioni ON DELETE / ON UPDATE appropriate.
  - Vincoli UNIQUE sugli attributi che lo richiedono (es. ragione sociale, nome voce contabile, nome indicatore ESG).
  - Vincoli CHECK dove utili (es. livello competenza tra 0 e 5, rilevanza indicatore tra 0 e 10, stato bilancio).
- Definire indici aggiuntivi sulle colonne frequentemente usate in join e filtri.

### 3.2 Stored procedure
Implementare stored procedure per tutte le operazioni applicative:

- Registrazione di un nuovo utente con gestione del tipo e dei campi specifici per ruolo.
- Autenticazione utente.
- Creazione e modifica dei dati di un'azienda.
- Creazione di un nuovo bilancio in stato "bozza" con inizializzazione automatica delle voci del template.
- Inserimento e aggiornamento dei valori delle voci contabili di un bilancio.
- Inserimento e modifica degli indicatori ESG.
- Collegamento di un indicatore ESG a una voce contabile con valore, fonte e data di rilevazione.
- Inserimento e aggiornamento delle competenze del revisore.
- Assegnazione di uno o più revisori ESG a un bilancio.
- Inserimento di una nota su una voce di bilancio da parte di un revisore.
- Inserimento del giudizio complessivo su un bilancio.

### 3.3 Trigger

- **Trigger 1 — Cambio stato a "in revisione"**  
  Attivato all'inserimento di una riga nella tabella di associazione revisore–bilancio. Imposta lo stato del bilancio a "in revisione".

- **Trigger 2 — Cambio stato a "approvato" o "respinto"**  
  Attivato all'inserimento di un giudizio complessivo. Verifica se tutti i revisori associati al bilancio hanno emesso il proprio giudizio:
  - Se tutti i giudizi sono "approvazione" o "approvazione con rilievi" → stato "approvato".
  - Se almeno un giudizio è "respingimento" → stato "respinto".

- **Trigger 3 — Mantenimento ridondanza `#nr_bilanci`** *(solo se si decide di mantenerla)*  
  Attivato su inserimento ed eliminazione di un bilancio. Aggiorna il contatore `#nr_bilanci` nella tabella Azienda.

### 3.4 Viste per le statistiche
- Vista per il numero totale di aziende registrate in piattaforma.
- Vista per il numero di revisori ESG registrati in piattaforma.
- Vista per l'azienda con il valore di affidabilità più alto, definita come la percentuale di bilanci con esito "approvazione" (senza rilievi) sul totale dei bilanci valutati.
- Vista per la classifica dei bilanci aziendali, ordinati in modo decrescente per numero totale di indicatori ESG collegati alle voci contabili.

---

## 4. Backend Web (PHP + MySQL)

### 4.1 Struttura applicativa e autenticazione
- Definire la struttura delle cartelle del progetto (es. `config/`, `lib/`, `controllers/`, `views/`, `public/`).
- Implementare `config.php` per la connessione PDO a MySQL e i parametri globali dell'applicazione.
- Implementare la logica di autenticazione:
  - Pagine di login e registrazione che delegano alle stored procedure.
  - Gestione delle sessioni PHP.
  - Controllo del ruolo utente prima di rendere accessibile qualsiasi pagina protetta.

### 4.2 Funzionalità per i ruoli

**Amministratore**
- Pagine per la gestione del template di bilancio (CRUD voci contabili).
- Pagine per la gestione degli indicatori ESG (creazione, modifica, assegnazione categoria e rilevanza).
- Pagina per l'assegnazione di uno o più revisori ESG a un bilancio aziendale.

**Revisore ESG**
- Pagina per la gestione delle proprie competenze (inserimento e aggiornamento nome e livello).
- Pagina con l'elenco dei bilanci assegnati e il loro stato corrente.
- Pagina per l'inserimento e la modifica delle note sulle singole voci di bilancio.
- Pagina per l'inserimento e la modifica del giudizio complessivo (esito, rilievi, data).

**Responsabile aziendale**
- Pagina per la creazione e la modifica dei dati delle proprie aziende.
- Pagina per la creazione di nuovi bilanci e la modifica di quelli in stato "bozza".
- Pagina per l'inserimento e l'aggiornamento dei valori delle voci contabili.
- Pagina per il collegamento degli indicatori ESG alle voci contabili con valore, fonte e data di rilevazione.

### 4.3 Pagine statistiche
- Dashboard accessibile a tutti gli utenti che interroga le viste SQL e presenta:
  - Numero di aziende registrate.
  - Numero di revisori ESG registrati.
  - Azienda con affidabilità più alta.
  - Classifica dei bilanci per numero di indicatori ESG collegati.

---

## 5. Logging eventi con MongoDB

### 5.1 Modello di dati per i log
- Definire la struttura del documento evento nella collection:
  - `tipo`: stringa che identifica il tipo di evento (es. `creazione_bilancio`, `inizio_revisione`).
  - `descrizione`: testo descrittivo dell'evento.
  - `riferimenti`: oggetto con gli identificativi correlati (id utente, id azienda, id bilancio, ecc.).
  - `timestamp`: data e ora in formato ISO 8601.
- Creare il database `esg_balance_logs` e la collection `eventi`.

### 5.2 Integrazione nel backend PHP
- Implementare una funzione PHP di utilità `logEvent($tipo, $descrizione, $riferimenti)` che inserisce il documento nella collection con timestamp automatico.
- Richiamare il logging nei punti chiave del flusso applicativo:
  - Creazione di un nuovo bilancio.
  - Inserimento dei valori degli indicatori ESG.
  - Assegnazione di un revisore a un bilancio.
  - Inserimento di un giudizio complessivo.
  - Altri eventi rilevanti a discrezione del gruppo.

---

## 6. Relazione di progetto

La relazione deve seguire la struttura richiesta dal regolamento:

- **Capitolo 1 — Analisi dei requisiti**  
  Testo completo delle specifiche sui dati, lista delle operazioni, tavola dei volumi, glossario dei dati.

- **Capitolo 2 — Progettazione concettuale**  
  Diagramma E-R completo, dizionario delle entità, dizionario delle relazioni, tavola delle business rules.

- **Capitolo 3 — Progettazione logica**  
  Ristrutturazione dello schema E-R, analisi e motivazione delle ridondanze con calcolo dei costi, schema relazionale con tabelle, chiavi e vincoli inter-relazionali.

- **Capitolo 4 — Normalizzazione**  
  Analisi delle dipendenze funzionali e trasformazioni effettuate per raggiungere la 3NF.

- **Capitolo 5 — Funzionalità dell'applicazione Web**  
  Descrizione ad alto livello delle pagine e delle funzionalità implementate, suddivise per ruolo.

- **Appendice — Codice SQL**  
  Script DDL completo, stored procedure principali, trigger, viste.

---

## 7. Presentazione, demo e verifica finale

### 7.1 Slide per la discussione
- Slide introduttiva: contesto del progetto ESG-BALANCE e obiettivi.
- Slide sulla progettazione: schema E-R, schema relazionale, scelte progettuali rilevanti.
- Slide sull'implementazione: stored procedure, trigger e viste statistiche.
- Slide sull'applicazione Web: screenshot delle pagine principali per ciascun ruolo.
- Slide su MongoDB: struttura dei documenti di log ed esempi di eventi registrati.

### 7.2 Scenario di demo
Preparare uno scenario dimostrativo completo che percorra nell'ordine:

1. Registrazione di un amministratore, un revisore ESG e un responsabile aziendale.
2. Login come amministratore: inserimento del template di bilancio e degli indicatori ESG.
3. Login come responsabile aziendale: registrazione di un'azienda, creazione di un bilancio e compilazione delle voci con indicatori ESG.
4. Login come amministratore: assegnazione del revisore al bilancio.
5. Login come revisore ESG: inserimento note su alcune voci e giudizio complessivo.
6. Verifica del cambio di stato del bilancio tramite trigger.
7. Consultazione della dashboard con le statistiche aggiornate.
8. Verifica dei log su MongoDB.

### 7.3 Verifica finale
- Testare tutte le stored procedure per casi nominali e casi limite.
- Verificare il comportamento corretto dei trigger nelle transizioni di stato del bilancio.
- Testare tutte le pagine Web per i tre ruoli con dati realistici.
- Controllare che le viste statistiche restituiscano i risultati attesi.
- Verificare l'inserimento e la consultazione dei log su MongoDB.
- Assicurarsi che ogni membro del gruppo conosca il 100% del codice consegnato, come richiesto dal regolamento del corso.
