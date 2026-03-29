# ESG-BALANCE
> Sistema di Gestione e Bilanciamento dei Dati ESG (Environmental, Social, Governance)

Progetto universitario per il **Corso di Basi di Dati** — architettura multi-utente per il reporting finanziario ESG con database relazionale MySQL e logging eventi su MongoDB.

---

## Stack Tecnologico

| Componente | Tecnologia |
|---|---|
| Backend | PHP + PDO |
| Database primario | MySQL 8.0+ (stored procedures, triggers, viste) |
| Database di logging | MongoDB 4.4+ |
| Dipendenze | Composer |
| Frontend | CSS |

---

## Ruoli Utente

| Ruolo | Responsabilità |
|---|---|
| **Amministratore** | Gestisce template di budget, definisce indicatori ESG, assegna revisori, visualizza statistiche |
| **Revisore ESG** | Gestisce competenze, aggiunge note agli inserimenti, fornisce giudizi complessivi |
| **Responsabile Aziendale** | Registra l'azienda, crea e completa fogli di bilancio, inserisce valori ESG |

---

## Funzionalità Principali

| Funzionalità | Descrizione |
|---|---|
| **Autenticazione e autorizzazione** | Accesso differenziato per ruolo con gestione sessioni e controllo permessi |
| **Cruscotto statistico** | Metriche aggregate in tempo reale: aziende registrate, revisori attivi, affidabilità e classifica bilanci |
| **Gestione bilanci ESG** | Creazione, compilazione e revisione dei fogli di bilancio lungo l'intero ciclo di vita |
| **Logica di business** | Stored procedures e triggers MySQL per automatizzare le operazioni critiche del sistema |
| **Logging eventi** | Tracciamento completo delle operazioni applicative su MongoDB |

---

## Struttura del Repository

```
ESG-BALANCE/
├── PHP/               # Backend: login, registrazione, sessioni, operazioni CRUD per ruolo
├── SQL/               # Script DB: tabelle, stored procedures, triggers, viste statistiche
├── STYLE/             # Fogli di stile CSS
├── ConfigMONGO.md     # Guida configurazione MongoDB
├── ER_Progetto.drawio # Diagramma Entity-Relationship
├── composer.json
└── composer.lock
```

---

## Requisiti

- PHP 7.4+ con estensione PDO
- MySQL 8.0+
- MongoDB 4.4+
- Composer

---

## Installazione

```bash
# 1. Clona il repository
git clone https://github.com/Fede046/ESG-BALANCE.git
cd ESG-BALANCE

# 2. Installa le dipendenze PHP
composer install

# 3. Crea il database e importa gli script
mysql -u root -p -e "CREATE DATABASE esg_balance;"
mysql -u root -p esg_balance < SQL/data.sql

# 4. Configura MongoDB
# → Segui le istruzioni in ConfigMONGO.md
```

---

## Documentazione

| File | Contenuto |
|---|---|
| `ConfigMONGO.md` | Configurazione MongoDB per il logging |
| `ER_Progetto.drawio` | Diagramma Entity-Relationship del database |
| `progettobd202526.pdf` | Specifiche complete del progetto (A.A. 2025-2026) |

---

## Autori

Progetto sviluppato da **Fede046**, **AlessiaSiri15**, **Tommus129** per il Corso di Basi di Dati.

---

## Licenza

Distribuito sotto licenza **MIT** — vedi [LICENSE](LICENSE) per i dettagli.
