
- Sistemare controllo criptato psw (da slide del prof)
- Sistemare CSS
- Finire viste triigger e stored procedure (CONTROLLARE)
- Valutere di mettere delle funzioni al posto delle query quando si fa il collegamento php (Osservazione il fatto che questo non sia del tutto menzionato nelle slide, potrebbe essere visto come un overkill, una soluzione implementa delle funzioni non utilizzate dal prof, ci sono. Il che mi fa dubitare anche se si tratta della scelta + pulita)
- Valutare questa idea di implementazione di ogni profilo ARCHITETTURA MODULARE: abbiamo una pagina centrale, con tanti pulsanti che portano a pagine di operazioni, una sorta di menu principale dell'utente.
  Questo a mio parere fa sì che il codice è meno condensato in un unico file php.

  ### MongoDB
- Creare connessione PHP a MongoDB (file `db_mongo.php`)
- Registrare evento su MongoDB alla **creazione di un bilancio** (`crea_bilancio.php`) con: username responsabile, ragione sociale, id bilancio, timestamp
- Registrare evento su MongoDB all'**inserimento di un valore ESG** (`inserisci_valore_esg.php`) con: voce, indicatore, valore, timestamp
- Registrare evento su MongoDB all'**inizio revisione** (`associa_revisore.php`) con: username revisore, id bilancio, ragione sociale, timestamp
- Registrare evento su MongoDB alla **registrazione di una nuova azienda** (`registra_azienda.php`) con: ragione sociale, username responsabile, timestamp

### Controllo unicità ruolo utente
-  Aggiungere in `sp_Registrazione` (o come trigger su `UTENTE`) un controllo che impedisca di registrare lo stesso username in più di una tabella ruolo (`AMMINISTRATORE`, `REVISORE_ESG`, `RESPONSABILE_AZIENDALE`)
- Replicare lo stesso controllo in `registration.php` lato applicativo, con messaggio di errore chiaro


## Collegamento TEMPLATE_BILANCIO ↔ VOCE

Le tabelle `TEMPLATE_BILANCIO` e `VOCE` non hanno nessun legame diretto tra loro:
non esiste una FK né una tabella intermedia che dica "questo template contiene queste voci".
Il risultato è che tutte le voci finiscono in un unico calderone indistinto,
e non è possibile sapere quali voci appartengono a quale template.

**Due opzioni:**

- **Opzione A (consigliata):** aggiungere una tabella ponte
  `TEMPLATE_VOCE(NomeTemplate, AnnoTemplate, NomeVoce)` con FK verso entrambe le tabelle
- **Opzione B (più semplice):** se un solo template globale è sufficiente per il progetto,
  aggiungere una riga nel README che lo documenta esplicitamente:
  *"Le voci in `VOCE` costituiscono il template unico globale,
  non è prevista la gestione di template multipli"*

---

## Pagina statistiche

La traccia richiede statistiche sulla piattaforma. Nel database esistono già 4 VIEW pronte:

| VIEW | Cosa mostra |
|---|---|
| `VISTA_NUMERO_AZIENDE` | Quante aziende sono registrate |
| `VISTA_NUMERO_REVISORI` | Quanti revisori ESG sono registrati |
| `VISTA_AZIENDA_TOP_AFFIDABILITA` | Azienda con la percentuale più alta di bilanci approvati |
| `VISTA_CLASSIFICA_BILANCI` | Classifica bilanci per numero di indicatori ESG collegati |

Verificare che `statistiche.php` faccia una query tipo `SELECT * FROM VISTA_NUMERO_AZIENDE`
per ciascuna VIEW e la mostri a schermo. Se la pagina calcola i dati con query dirette
sulle tabelle o è ancora incompleta, va aggiornata per **usare le VIEW già definite nel SQL**.
