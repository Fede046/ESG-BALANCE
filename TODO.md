
FATTO - Sistemare controllo criptato psw (da slide del prof)
- Sistemare CSS
- Finire viste triigger e stored procedure (CONTROLLARE)
- Valutere di mettere delle funzioni al posto delle query quando si fa il collegamento php (Osservazione il fatto che questo non sia del tutto menzionato nelle slide, potrebbe essere visto come un overkill, una soluzione implementa delle funzioni non utilizzate dal prof, ci sono. Il che mi fa dubitare anche se si tratta della scelta + pulita)
- Valutare questa idea di implementazione di ogni profilo ARCHITETTURA MODULARE: abbiamo una pagina centrale, con tanti pulsanti che portano a pagine di operazioni, una sorta di menu principale dell'utente.
  Questo a mio parere fa sì che il codice è meno condensato in un unico file php.

### MongoDB
FATTO  - Creare connessione PHP a MongoDB (file `db_mongo.php`)
FATTO  - Registrare evento su MongoDB alla **creazione di un bilancio** (`crea_bilancio.php`) con: username responsabile, ragione sociale, id bilancio, timestamp
FATTO  - Registrare evento su MongoDB all'**inserimento di un valore ESG** (`inserisci_valore_esg.php`) con: voce, indicatore, valore, timestamp
FATTO  - Registrare evento su MongoDB all'**inizio revisione** (`associa_revisore.php`) con: username revisore, id bilancio, ragione sociale, timestamp
FATTO  - Registrare evento su MongoDB alla **registrazione di una nuova azienda** (`registra_azienda.php`) con: ragione sociale, username responsabile, timestamp
FATTO??? - finire di inserire i LogEvento(), es. logout ecc.


ALESSIA 
- modificare la aprte di errori per fare in modo che siano coerenti e più belli.
- modificare all'interno di menu come si venono username e ruolo. 
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
