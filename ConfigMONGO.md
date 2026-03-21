# Configurazione MongoDB per ESG-BALANCE

Questa guida spiega come configurare MongoDB, l'estensione PHP e MongoDB Compass per il corretto funzionamento del sistema di logging di ESG-BALANCE, sia su **Windows (MAMP)** che su **macOS (MAMP)**.

---

## Requisiti

- MAMP installato e funzionante
- MongoDB installato (v6+)
- PHP 8.3.x selezionato in MAMP
- MongoDB Compass installato

---

## Windows

### 1. Verifica che MongoDB sia in esecuzione

Apri PowerShell e digita:

```powershell
Get-Service -Name MongoDB
```

Se lo stato è `Stopped`, avvialo:

```powershell
Start-Service -Name MongoDB
```

Se il servizio non esiste, avvia MongoDB manualmente:

```powershell
mongod --dbpath "C:\data\db"
```

### 2. Verifica la versione PHP usata da MAMP

Apri **MAMP → Preferences → PHP** e annota la versione attiva (es. `8.3.1`).

### 3. Verifica Thread Safety

```powershell
& "C:\MAMP\bin\php\php8.3.1\php.exe" -i | findstr "Thread"
```

Deve rispondere `Thread Safety => enabled`.

### 4. Scarica la DLL php_mongodb

1. Vai su https://pecl.php.net/package/mongodb
2. Clicca sulla versione più recente
3. Scarica il file corrispondente a:
   - PHP **8.3**
   - **Thread Safe (TS)**
   - **x64**

   Es: `php_mongodb-2.2.1-8.3-ts-vs16-x64.zip`

4. Estrai lo zip e copia `php_mongodb.dll` in: C:\MAMP\bin\php\php8.3.1\ext


### 5. Abilita l'estensione nel php.ini

Apri il file:

```powershell
notepad "C:\MAMP\conf\php8.3.1\php.ini"
```

Cerca la sezione delle extension con `Ctrl+F` cercando `extension=` e aggiungi:

```ini
extension=mongodb
```

Salva e chiudi.

### 6. Riavvia MAMP

In MAMP clicca **Stop Servers** → **Start Servers**.

### 7. Verifica che l'estensione sia attiva

Crea un file `test.php` in `C:\MAMP\htdocs\` con:

```php
<?php phpinfo();
```

Aprilo nel browser su `http://localhost/test.php` e cerca **"mongodb"** con `Ctrl+F`.
Deve comparire la sezione **MongoDB support: enabled**.

---

## macOS

### 1. Verifica che MongoDB sia in esecuzione

```bash
brew services list | grep mongodb
```

Se non è in esecuzione:

```bash
brew services start mongodb-community
```

### 2. Verifica la versione PHP usata da MAMP

Apri **MAMP → Preferences → PHP** e annota la versione attiva (es. `8.3.1`).

### 3. Installa l'estensione php_mongodb tramite PECL

```bash
/Applications/MAMP/bin/php/php8.3.1/bin/pecl install mongodb
```

### 4. Abilita l'estensione nel php.ini

Apri il file:

```bash
nano /Applications/MAMP/conf/php8.3.1/php.ini
```

Aggiungi in fondo alla sezione extension:

```ini
extension=mongodb.so
```

> ⚠️ Su macOS l'estensione si chiama `mongodb.so`, non `.dll`

Salva con `Ctrl+X` → `Y` → `Invio`.

### 5. Riavvia MAMP

In MAMP clicca **Stop Servers** → **Start Servers**.

### 6. Verifica che l'estensione sia attiva

Crea un file `test.php` in `/Applications/MAMP/htdocs/` con:

```php
<?php phpinfo();
```

Aprilo nel browser su `http://localhost/test.php` e cerca **"mongodb"** con `Ctrl+F`.
Deve comparire la sezione **MongoDB support: enabled**.

---

## Configurazione MongoDB Compass

### 1. Apri MongoDB Compass

Avvia l'applicazione. Nella schermata iniziale vedrai il campo **URI**.

### 2. Crea una nuova connessione

1. Clicca su **"+ Add new connection"**
2. Nel campo **URI** inserisci: mongodb://127.0.0.1:27017


3. Assegna un nome alla connessione (es. `ESG-LOCAL`)
4. Clicca **Save & Connect**

Dovresti vedere i database di sistema (`admin`, `config`, `local`).

### 3. Crea il database

1. Clicca sul pulsante **"+"** accanto a **Databases** nel pannello sinistro
2. Inserisci i seguenti valori:
   - **Database Name:** `TEST_PROGETTO`
   - **Collection Name:** `events_user`
3. Clicca **Create Database**

> Le altre collection vengono create **automaticamente** dal sistema al primo evento loggato:
>
> | Collection | Descrizione |
> |---|---|
> | `events_user` | Login, registrazione, aggiornamento utenti |
> | `events_bilancio` | Creazione e gestione bilanci |
> | `events_revisione` | Assegnazione revisori, note, giudizi |
> | `events_template` | Gestione voci contabili |
> | `events_esg` | Indicatori ESG e competenze |
> | `events_company` | Gestione aziende |
> | `events_general` | Eventi non categorizzati |

---

## Verifica finale

1. Avvia il progetto nel browser:

**Windows:** http://localhost/ESG-BALANCE/PHP/login.php

**macOS:** http://localhost/ESG-BALANCE/PHP/login.php


2. Esegui il login con un utente esistente
3. Torna su Compass e vai su `TEST_PROGETTO → events_user`
4. Clicca il pulsante **Refresh** — deve comparire un documento simile a:

```json
{
  "text": "Login effettuato: mario.rossi (ruolo: amministratore)",
  "event_type": "USER_LOGIN",
  "category": "USER",
  "user_id": 0,
  "entity_id": 0,
  "timestamp": "2026-03-21T11:04:32.684+00:00"
}
```

✅ Se il documento è presente, MongoDB è configurato correttamente e il sistema di logging funziona.