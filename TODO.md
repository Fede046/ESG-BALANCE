
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


## 🔒 VALIDAZIONI & WARNING – Tutti i file

### `login.php`
- [ ] Nessun controllo se i campi sono stati lasciati vuoti lato server (solo `required` HTML) → aggiungere: "Inserisci username e password." (già presente ma verificare che copra anche POST vuoti forzati)

### `registration.php`
- [ ] Password troppo corta: `strlen($psw) >= 8` → "La password deve essere di almeno 8 caratteri."
- [ ] Ruolo non selezionato: `$ruolo` può arrivare vuoto se nessun radio è selezionato → `if (!in_array($ruolo, ['revisore', 'responsabile']))` → "Seleziona un ruolo valido."
- [ ] Codice Fiscale: nessuna validazione formato → `preg_match('/^[A-Z0-9]{16}$/i', $CF)` → "Codice Fiscale non valido (16 caratteri alfanumerici)."
- [ ] Data di nascita: nessun controllo che non sia nel futuro o che l'utente abbia almeno 18 anni → "Data di nascita non valida."
- [ ] Email: validare ogni indirizzo con `filter_var($email, FILTER_VALIDATE_EMAIL)` lato PHP → "L'indirizzo email '$email' non è valido."
- [ ] Nessuna conferma password (campo "Ripeti password") → aggiungere campo e check `$psw === $psw_confirm`

### `statistiche.php`
- [ ] Nessun messaggio se le VIEW restituiscono `null` o `0` per un motivo diverso dall'assenza di dati (es. errore DB silenzioso) → distinguere tra "0 aziende registrate" e "impossibile caricare i dati"
- [ ] Nessun controllo accesso per ruolo: tutti i ruoli vedono le statistiche (probabilmente voluto, ma verificare)

### `actions/registra_azienda.php`
- [ ] Partita IVA: nessuna validazione formato → `preg_match('/^\d{11}$/', $piva)` → "La P.IVA deve contenere esattamente 11 cifre numeriche."
- [ ] Numero dipendenti: nessun controllo PHP che `$n_dip >= 0` (solo `min="0"` HTML aggirabile) → "Il numero di dipendenti non può essere negativo."

### `actions/aggiungi_competenze.php`
- [ ] Nome competenza: nessun controllo su caratteri speciali o se è già presente per questo revisore → "Hai già dichiarato questa competenza." (prima della SP, per un messaggio più chiaro)

### `actions/aggiungi_indicatore.php`
- [ ] Frequenza per indicatori sociali: controllato se `!== null` ma non se `> 0` → "La frequenza deve essere maggiore di 0 giorni."
- [ ] Rilevanza: controllata solo se `!== null`; se l'utente mette un valore non intero (es. "abc") `(int)` lo converte a 0 silenziosamente → aggiungere `is_numeric($_POST['rilevanza'])` prima del cast

### `actions/crea_template.php`
- [ ] Nessun controllo lunghezza minima sul nome voce (es. 1 solo carattere è accettato) → aggiungere `strlen($nome_voce) >= 2` → "Il nome della voce deve avere almeno 2 caratteri."

### `actions/crea_bilancio.php`
- [ ] `id_bilancio` inserito manualmente: nessun check preventivo se esiste già per quell'azienda → mostra errore DB grezzo; sostituire con query di verifica prima della SP e messaggio: "Un bilancio con questo ID esiste già per questa azienda."
- [ ] Azione `associa_voce`: nessun controllo che il bilancio inserito appartenga davvero a un'azienda del responsabile loggato → "Bilancio non trovato o non di tua competenza."

### `actions/associa_revisore.php`
- [ ] Nessun controllo che il bilancio non sia già in stato `approvato` o `respinto` → "Non puoi assegnare un revisore a un bilancio già chiuso."
- [ ] Nessun controllo che il revisore scelto abbia competenze rilevanti (opzionale/miglioramento)

### `actions/inserisci_giudizio.php`
- [ ] Nessun controllo che il revisore non abbia già inserito un giudizio su quel bilancio → "Hai già inserito un giudizio per questo bilancio."
- [ ] Nessun controllo che il bilancio non sia già in stato finale (`approvato`/`respinto`) → "Il bilancio è già chiuso, non puoi inserire un nuovo giudizio."

### `actions/inserisci_nota.php`
- [ ] Nessun limite di lunghezza sul testo nota lato PHP (solo textarea HTML) → `strlen($testo) <= 500` → "Il testo non può superare 500 caratteri."
- [ ] Nessun controllo che il bilancio non sia già in stato finale prima di inserire la nota → "Non puoi aggiungere note a un bilancio già chiuso."

### `actions/inserisci_valore_esg.php`
- [ ] `$valore` non viene validato come numerico: `is_numeric($valore)` → "Il valore deve essere un numero."
- [ ] Nessun controllo che il bilancio non sia già in stato `approvato`/`respinto` prima di inserire valori → "Non puoi modificare un bilancio già chiuso."
