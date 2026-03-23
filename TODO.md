
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

### Warnig/Errori

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


## Validazioni & Warning – Implementate

### `login.php`
- [x] Controllo campi vuoti lato server già presente (`!empty`) – nessuna modifica necessaria

### `registration.php`
- [x] Password minimo 8 caratteri → "La password deve essere di almeno 8 caratteri."
- [x] Conferma password → campo `psw_confirm` + check `$psw === $psw_confirm`
- [x] Ruolo non valido → `in_array($ruolo, ['revisore','responsabile'])` → "Seleziona un ruolo valido."
- [x] Codice Fiscale formato → `preg_match('/^[A-Z0-9]{16}$/i', $CF)` → "Codice Fiscale non valido."
- [x] Data di nascita → controllo futuro + età minima 18 anni
- [x] Email → `filter_var($email, FILTER_VALIDATE_EMAIL)` su ogni indirizzo

### `actions/registra_azienda.php`
- [x] Partita IVA → `preg_match('/^\d{11}$/', $piva)` → "La P.IVA deve contenere esattamente 11 cifre numeriche."
- [x] Numero dipendenti → `$n_dip < 0` → "Il numero di dipendenti non può essere negativo."

### `actions/aggiungi_competenze.php`
- [x] Competenza duplicata → SELECT preventivo su `DICHIARA_COMPETENZA_REVISORE` → "Hai già dichiarato questa competenza."

### `actions/aggiungi_indicatore.php`
- [x] Rilevanza non numerica → `is_numeric($_POST['rilevanza'])` prima del cast → "La rilevanza deve essere un numero intero."
- [x] Frequenza sociale → `$frequenza <= 0` → "La frequenza deve essere maggiore di 0 giorni."

### `actions/crea_template.php`
- [x] Lunghezza minima nome voce → `strlen($nome_voce) >= 2` → "Il nome della voce deve avere almeno 2 caratteri."

### `actions/crea_bilancio.php`
- [x] ID bilancio duplicato → SELECT preventivo su `BILANCIO` → "Un bilancio con questo ID esiste già per questa azienda."
- [x] Ownership bilancio in `associa_voce` → JOIN su `AZIENDA` → "Bilancio non trovato o non di tua competenza."

### `actions/associa_revisore.php`
- [x] Bilancio già chiuso → SELECT su `Stato` → "Non puoi assegnare un revisore a un bilancio già chiuso."

### `actions/inserisci_giudizio.php`
- [x] Bilancio già chiuso → SELECT su `Stato` → "Il bilancio è già chiuso, non puoi inserire un nuovo giudizio."
- [x] Giudizio duplicato → SELECT su `GIUDIZIO` → "Hai già inserito un giudizio per questo bilancio."

### `actions/inserisci_nota.php`
- [x] Limite lunghezza testo → `strlen($testo) <= 500` → "Il testo non può superare 500 caratteri."
- [x] Bilancio già chiuso → SELECT su `Stato` → "Non puoi aggiungere note a un bilancio già chiuso."

### `actions/inserisci_valore_esg.php`
- [x] Valore non numerico → `is_numeric($valore)` → "Il valore deve essere un numero."
- [x] Bilancio già chiuso → SELECT su `Stato` → "Non puoi modificare un bilancio già chiuso."
