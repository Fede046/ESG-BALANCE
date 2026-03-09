**Amministratore**
Amministratore viene creato da query sql. 
È un utente unico e permette di gestire e amministrare.
Amministratore se fatto così non è detto dalla consegna ma da intuizione comune,
sulla base di ciò che ha detto Gabri.
Che gli ha detto che amministratroe è solo un ruolo di gestione.

**Differenza tra HTML e PHP sul reidirezzamento in file**
*<a href="altra_pagina.html">* 
È un collegamento ipertestuale. 

Azione dell'utente: Lo spostamento avviene solo se l'utente clicca fisicamente sul link o sul pulsante.
Utilizzo: Ideale per menu di navigazione, link interni o pulsanti "Torna indietro

*header("Location: home.php")*
È un reindirizzamento lato server. 
Azione automatica: Il server invia un'istruzione al browser dicendo: "Non caricare questa pagina, vai direttamente a quest'altra".
Utilizzo: Fondamentale dopo operazioni logiche, come:
Dopo un Login (se i dati sono giusti, ti sposto alla home).
Dopo aver salvato un form (per evitare che l'utente ricarichi la pagina e invii i dati due volte).
Per proteggere pagine riservate (se non sei loggato, ti rimando alla login).