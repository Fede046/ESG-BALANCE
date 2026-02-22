# ESG-BALANCE

# 🗺️ Roadmap Progetto ESG-BALANCE

Ecco una roadmap strutturata 

---

## 📋 **FASE 1: ANALISI E PROGETTAZIONE (Settimana 1-2)**

### 1.1 Analisi dei Requisiti
- [ ] Leggere attentamente la traccia del progetto
- [ ] Creare il **glossario dei termini**
- [ ] Identificare tutte le **entità** (Utente, Azienda, Bilancio, Indicatore ESG, ecc.)
- [ ] Identificare tutte le **relazioni** tra entità
- [ ] Definire le **operazioni** richieste per ogni tipo di utente
- [ ] Compilare la **tabella dei volumi** (10 aziende, 5 bilanci ciascuna)

### 1.2 Progettazione Concettuale
- [ ] Disegnare lo **schema E-R** completo
- [ ] Creare il **dizionario delle entità** (attributi, chiavi, vincoli)
- [ ] Creare il **dizionario delle relazioni** (cardinalità, vincoli)
- [ ] Documentare le **business rules**
- [ ] Gestire la gerarchia degli utenti (amministratore/revisore/responsabile)
- [ ] Modellare la specializzazione degli indicatori ESG (ambientali/sociali)

### 1.3 Progettazione Logica
- [ ] **Ristrutturare** lo schema E-R (eliminare attributi composti, gestire gerarchie)
- [ ] **Analisi delle ridondanze**: valutare se mantenere `#nr_bilanci`
  - Calcolare costi con e senza ridondanza
  - Applicare i coefficienti forniti (wI=1, wB=0.5, a=2)
- [ ] Tradurre lo schema E-R in **schema relazionale**
- [ ] Definire **chiavi primarie** e **chiavi esterne**
- [ ] Elencare tutti i **vincoli inter-relazionali**
- [ ] Verificare la **normalizzazione** (almeno 3NF)

---

## 💾 **FASE 2: IMPLEMENTAZIONE DATABASE (Settimana 2-3)**

### 2.1 Creazione Schema MySQL
- [ ] Installare **MAMP** (Apache + MySQL + PHP)
- [ ] Creare il database `esg_balance`
- [ ] Creare tutte le **tabelle** con vincoli:
  - Tabella `Utente` (con sottotipi: Amministratore, Revisore, Responsabile)
  - Tabella `Azienda` (con campo ridondante `#nr_bilanci`)
  - Tabella `VoceContabile` (template di bilancio)
  - Tabella `IndicatoreESG` (con specializzazioni ambientale/sociale)
  - Tabella `BilancioEsercizio`
  - Tabella `ValoreVoce` (collega bilancio e voce contabile)
  - Tabella `CollegamentoESG` (collega voce a indicatore ESG)
  - Tabella `Revisione` (associazione revisore-bilancio)
  - Tabella `NotaBilancio`
  - Tabella `GiudizioRevisione`
  - Tabella `Competenza` (competenze dei revisori)

### 2.2 Stored Procedures
- [ ] SP per **registrazione utente**
- [ ] SP per **autenticazione**
- [ ] SP per **creazione azienda**
- [ ] SP per **creazione bilancio**
- [ ] SP per **inserimento valori indicatori ESG**
- [ ] SP per **associazione revisore a bilancio**
- [ ] SP per **inserimento note revisore**
- [ ] SP per **inserimento giudizio revisore**
- [ ] SP per **popolamento template bilancio**
- [ ] SP per **popolamento indicatori ESG**

### 2.3 Trigger
- [ ] **Trigger 1**: cambio stato bilancio in "in revisione" quando viene aggiunto un revisore
- [ ] **Trigger 2**: cambio stato bilancio in "approvato" o "respinto" quando tutti i revisori hanno dato giudizio
- [ ] **Trigger 3** (opzionale): aggiornamento campo `#nr_bilanci` quando viene inserito/eliminato un bilancio

### 2.4 Viste per Statistiche
- [ ] Vista: **numero aziende registrate**
- [ ] Vista: **numero revisori ESG registrati**
- [ ] Vista: **azienda con valore più alto di affidabilità** (% bilanci approvati senza rilievi)
- [ ] Vista: **classifica bilanci per numero indicatori ESG collegati**

---

## 🌐 **FASE 3: IMPLEMENTAZIONE WEB (Settimana 3-4)**

### 3.1 Struttura Base
- [ ] Creare la struttura delle cartelle del progetto
- [ ] File `config.php` per connessione database (PDO)
- [ ] File `index.php` (homepage)
- [ ] File `login.php` e `register.php`
- [ ] Implementare **gestione sessioni** per login

### 3.2 Pagine per Ogni Ruolo

**Amministratore:**
- [ ] Pagina per inserire template bilancio
- [ ] Pagina per inserire indicatori ESG
- [ ] Pagina per associare revisori a bilanci

**Revisore ESG:**
- [ ] Pagina per inserire competenze
- [ ] Pagina per visualizzare bilanci assegnati
- [ ] Pagina per inserire note su voci di bilancio
- [ ] Pagina per inserire giudizio complessivo

**Responsabile Aziendale:**
- [ ] Pagina per registrare azienda
- [ ] Pagina per creare bilancio
- [ ] Pagina per inserire valori voci contabili
- [ ] Pagina per collegare indicatori ESG a voci

### 3.3 Pagine Pubbliche
- [ ] Dashboard con statistiche (accessibile a tutti)
- [ ] Visualizzazione classifica bilanci
- [ ] Visualizzazione aziende più affidabili

---

## 🗄️ **FASE 4: INTEGRAZIONE MONGODB (Settimana 4)**

### 4.1 Setup MongoDB
- [ ] Installare MongoDB localmente o usare MongoDB Atlas
- [ ] Creare database `esg_balance_logs`
- [ ] Creare collection `eventi`

### 4.2 Logging Eventi
- [ ] Funzione PHP per registrare eventi su MongoDB
- [ ] Log evento: creazione bilancio
- [ ] Log evento: inserimento indicatore ESG
- [ ] Log evento: inizio revisione
- [ ] Log evento: inserimento giudizio
- [ ] Includere **timestamp** in ogni evento

---

## 📝 **FASE 5: DOCUMENTAZIONE (Settimana 5)**

### 5.1 Relazione di Progetto
La relazione DEVE contenere:
- [ ] **Copertina**: titolo, nomi componenti gruppo
- [ ] **Capitolo 1**: Analisi requisiti (glossario, operazioni, tavola volumi)
- [ ] **Capitolo 2**: Progettazione concettuale (diagramma E-R, dizionari, business rules)
- [ ] **Capitolo 3**: Progettazione logica (schema relazionale, vincoli, analisi ridondanze)
- [ ] **Capitolo 4**: Normalizzazione (se necessaria)
- [ ] **Capitolo 5**: Descrizione funzionalità applicazione Web
- [ ] **Appendice**: Codice SQL completo dello schema

### 5.2 Presentazione PowerPoint
- [ ] Slide introduttiva (traccia progetto)
- [ ] Slide su progettazione database (E-R, schema relazionale)
- [ ] Slide su implementazione (stored procedures, trigger, viste)
- [ ] Slide su applicazione Web (screenshot interfacce)
- [ ] Slide su MongoDB (logging eventi)
- [ ] Slide per demo live

---

## 🎯 **FASE 6: TESTING E PREPARAZIONE ESAME (Settimana 5-6)**

### 6.1 Testing
- [ ] Testare tutte le stored procedures
- [ ] Verificare funzionamento trigger
- [ ] Testare tutte le pagine Web
- [ ] Popolare database con dati di test sufficienti per la demo
- [ ] Verificare logging su MongoDB

### 6.2 Preparazione Demo
- [ ] Preparare scenario di demo completo
- [ ] Testare demo su computer di ogni membro del gruppo
- [ ] Assicurarsi che tutti conoscano il 100% del codice
- [ ] Prepararsi a domande su qualsiasi parte del progetto



