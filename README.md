# ESG-BALANCE

Sistema di Gestione e Bilanciamento dei Dati ESG (Environmental, Social, Governance)

## Descrizione del Progetto

ESG-BALANCE è un progetto universitario sviluppato per il Corso di Basi di Dati. Si tratta di un sistema completo di gestione per il reporting finanziario ESG che permette alle aziende di gestire i propri bilanci in termini di sostenibilità ambientale, responsabilità sociale e governance aziendale. Il sistema implementa un'architettura multi-utente con ruoli differenziati, un database relazionale MySQL con stored procedures, triggers e viste statistiche, e un sistema di logging eventi basato su MongoDB.

## Caratteristiche Principali

Il progetto offre un sistema completo di gestione ESG con autenticazione e autorizzazione basata su ruoli. Gli amministratori possono gestire i modelli di budget, definire gli indicatori ESG e assegnare i revisori ai vari task di valutazione. I revisori ESG hanno la possibilità di gestire le competenze, aggiungere note ai vari inserimenti e fornire giudizi complessivi sulle performance aziendali. I responsabili aziendali possono registrare le proprie aziende, creare e completare i fogli di bilancio ed inserire i valori degli indicatori ESG. Il sistema include inoltre un cruscotto statistico che mostra metriche chiave come il numero di aziende registrate, i revisori ESG attivi, l'azienda con la migliore affidabilità e la classifica dei bilanci aziendali basata sugli indicatori ESG collegati.

## Stack Tecnologico

Il backend del sistema è sviluppato in PHP utilizzando PDO per le connessioni al database, garantendo una gestione sicura e parametrizzata delle query. Il database primario è MySQL, che ospita le tabelle principali, le stored procedures per la logica di business, i triggers per la gestione automatica degli eventi e le viste per le statistiche aggregate. Per il logging degli eventi applicativi viene utilizzato MongoDB con una collection dedicata denominata "eventi" che memorizza tutte le operazioni di sistema. Lo styling dell'interfaccia web è gestito tramite CSS per garantire un'esperienza utente coerente e professionale.

## Struttura del Progetto

La struttura del repository è organizzata in cartelle tematiche che separano chiaramente le diverse componenti del sistema. La directory PHP contiene tutto il codice backend necessario per le funzionalità del sito web, incluse le pagine di login e registrazione, la gestione delle sessioni, il controllo degli accessi basato sui ruoli e le operazioni CRUD specifiche per ogni ruolo utente. La directory SQL ospita tutti gli script necessari per la creazione e l'inizializzazione del database, incluse le definizioni delle tabelle, le stored procedures, i triggers e le viste statistiche. La directory STYLE contiene i fogli di stile CSS che definiscono l'aspetto visivo dell'applicazione web.

## Ruoli Utente

Il sistema implementa tre ruoli principali con permessi e responsabilità specifiche. L'**Amministratore** ha il controllo completo sul sistema e può gestire i modelli di budget, definire gli indicatori ESG disponibili, assegnare i revisori ai vari compiti e visualizzare tutte le statistiche aggregate. L'**ESG Reviewer** è il revisore specializzato che gestisce le competenze tecniche, analizza i dati inseriti dalle aziende, aggiunge note e commenti ai vari inserimenti e fornisce un giudizio complessivo sulla qualità dei dati ESG presentati. Il **Company Responsible** rappresenta l'azienda nel sistema e può registrare la propria azienda, creare nuovi fogli di bilancio, completare i bilanci in fase di elaborazione e inserire i valori degli indicatori ESG richiesti.

## Requisiti di Sistema

Per l'installazione e l'esecuzione del progetto sono necessari i seguenti componenti. Il server web richiede PHP versione 7.4 o superiore con estensione PDO abilitata per la connessione al database MySQL. Il database primario richiede MySQL 8.0 o superiore per il supporto completo alle stored procedures, triggers e viste. Per il sistema di logging eventi è necessario MongoDB 4.4 o superiore. Composer deve essere installato per la gestione delle dipendenze PHP elencate nel file composer.json.

## Installazione

Il processo di installazione si articola in quattro passaggi principali. Prima di tutto, clonare il repository nel proprio ambiente di sviluppo utilizzando il comando git clone seguito dall'URL del repository. Successivamente, configurare il database MySQL importando gli script SQL presenti nella directory SQL, assicurandosi di creare il database esg_balance prima dell'importazione. Configurare poi la connessione a MongoDB seguendo le istruzioni dettagliate presenti nel file ConfigMONGO.md. Infine, installare le dipendenze PHP eseguendo il comando composer install nella directory principale del progetto.

## Documentazione Tecnica

Il repository include diverse risorse documentative per comprendere meglio l'architettura del sistema. Il file ER_Progetto.drawio e la relativa immagine PNG contengono il diagramma Entity-Relationship completo che illustra la struttura logica del database. Il file progettobd202526.pdf contiene le specifiche complete del progetto per l'anno accademico 2025-2026. Il file ConfigMONGO.md fornisce istruzioni dettagliate per la configurazione del database MongoDB per il logging degli eventi.

## Licenza

Questo progetto è distribuito con licenza MIT. Per maggiori dettagli consulta il file LICENSE presente nella directory principale del repository.

## Autore

Progetto sviluppato da Fede046, AlessiaSiri15, Tommus129 per il Corso di Basi di Dati.
