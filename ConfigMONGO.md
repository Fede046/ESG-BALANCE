# 🛠️ Guida di Installazione — ESG-BALANCE

Questa guida descrive il processo completo per configurare l'ambiente di sviluppo locale per il progetto **ESG-BALANCE**, sia su **Windows (MAMP)** che su **macOS (MAMP)**.

---

## 📋 Requisiti

| Strumento | Versione minima |
|---|---|
| MAMP | ultima disponibile |
| PHP (via MAMP) | 8.3.x |
| MySQL (via MAMP) | incluso in MAMP |
| MongoDB | 6.0+ |
| MongoDB Compass | ultima disponibile |
| Composer | ultima disponibile |
| Git | ultima disponibile |

---

## 🪟 Windows

### 1. Installa MAMP

1. Scarica MAMP da **https://www.mamp.info**
2. Installa con le opzioni di default
3. Apri MAMP → **Preferences → Ports** e imposta:
   - **Apache Port:** `80`
   - **MySQL Port:** `3308`
4. Vai su **Preferences → PHP** e seleziona `8.3.1`
5. Clicca **OK** poi **Start Servers** — entrambe le luci devono diventare verdi

---

### 2. Installa MongoDB

1. Vai su **https://www.mongodb.com/try/download/community**
2. Seleziona: Version → ultima, Platform → Windows, Package → MSI
3. Installa e seleziona **"Install MongoD as a Service"** durante il processo
4. Completa con le opzioni di default

---

### 3. Installa Composer

1. Scarica l'installer da **https://getcomposer.org/Composer-Setup.exe**
2. Eseguilo
3. Quando chiede il **PHP executable**, punta a: C:\MAMP\bin\php\php8.3.1\php.exe
4. Completa con le opzioni di default

---

### 4. Aggiungi i PATH di sistema

1. Premi `Win + R`, digita `sysdm.cpl` → Invio
2. Vai su **Avanzate → Variabili d'ambiente**
3. In **Variabili di sistema** seleziona `Path` → clicca **Modifica → Nuovo**
4. Aggiungi **una per riga**: 

C:\MAMP\bin\php\php8.3.1
C:\Program Files\MongoDB\Server\8.2\bin
C:\ProgramData\ComposerSetup\bin

5. Clicca **OK** su tutte le finestre
6. **Riapri PowerShell** per caricare i nuovi PATH

Verifica che tutto funzioni:
```powershell
php --version
mongod --version
composer --version
```

---

### 5. Installa l'estensione php_mongodb

#### 5a. Verifica Thread Safety
```powershell
& "C:\MAMP\bin\php\php8.3.1\php.exe" -i | findstr "Thread"
```
Deve rispondere `Thread Safety => enabled`.

#### 5b. Scarica la DLL
1. Vai su **https://pecl.php.net/package/mongodb**
2. Clicca sulla versione più recente
3. Scarica il file per PHP **8.3**, **Thread Safe (TS)**, **x64**
- Es: `php_mongodb-2.2.1-8.3-ts-vs16-x64.zip`
4. Estrai e copia `php_mongodb.dll` in: 

C:\MAMP\bin\php\php8.3.1\ext


#### 5c. Abilita nel php.ini
Apri il file:
```powershell
notepad "C:\MAMP\conf\php8.3.1\php.ini"
```
Cerca con `Ctrl+F` la sezione `extension=` e aggiungi:
```ini
extension=php_mongodb.dll
```
Salva e chiudi.

#### 5d. Riavvia MAMP
In MAMP: **Stop Servers** → **Start Servers**

#### 5e. Verifica
Crea `C:\MAMP\htdocs\test.php` con:
```php
<?php phpinfo();
```
Apri `http://localhost/test.php` e cerca **"mongodb"** con `Ctrl+F`.
Deve comparire **MongoDB support: enabled**.

---

### 6. Clona il progetto e installa le dipendenze

```powershell
cd C:\MAMP\htdocs
git clone https://github.com/Fede046/ESG-BALANCE.git
cd ESG-BALANCE
composer install
```

Se appare un warning sul lock file:
```powershell
composer update
```

---

## 🍎 macOS

### 1. Installa MAMP

1. Scarica MAMP da **https://www.mamp.info**
2. Installa con le opzioni di default
3. Apri MAMP → **Preferences → PHP** e seleziona `8.3.1`
4. Clicca **OK** poi **Start Servers**

---

### 2. Installa MongoDB

```bash
brew tap mongodb/brew
brew install mongodb-community
brew services start mongodb-community
```

---

### 3. Installa Composer

```bash
curl -sS https://getcomposer.org/installer | /Applications/MAMP/bin/php/php8.3.1/bin/php
sudo mv composer.phar /usr/local/bin/composer
```

Verifica:
```bash
composer --version
```

---

### 4. Aggiungi i PATH di sistema

```bash
echo 'export PATH="/Applications/MAMP/bin/php/php8.3.1/bin:$PATH"' >> ~/.zshrc
echo 'export PATH="/usr/local/bin:$PATH"' >> ~/.zshrc
source ~/.zshrc
```

Verifica:
```bash
php --version
mongod --version
composer --version
```

---

### 5. Installa l'estensione php_mongodb

#### 5a. Installa tramite PECL
```bash
/Applications/MAMP/bin/php/php8.3.1/bin/pecl install mongodb
```

#### 5b. Abilita nel php.ini
```bash
nano /Applications/MAMP/conf/php8.3.1/php.ini
```
Aggiungi in fondo:
```ini
extension=mongodb.so
```
> ⚠️ Su macOS l'estensione si chiama `.so`, non `.dll`

Salva con `Ctrl+X` → `Y` → Invio.

#### 5c. Riavvia MAMP
In MAMP: **Stop Servers** → **Start Servers**

#### 5d. Verifica
Crea `/Applications/MAMP/htdocs/test.php` con:
```php
<?php phpinfo();
```
Apri `http://localhost/test.php` e cerca **"mongodb"** con `Ctrl+F`.
Deve comparire **MongoDB support: enabled**.

---

### 6. Clona il progetto e installa le dipendenze

```bash
cd /Applications/MAMP/htdocs
git clone https://github.com/Fede046/ESG-BALANCE.git
cd ESG-BALANCE
composer install
```

---

## 🧭 Configurazione MongoDB Compass

### 1. Crea una nuova connessione

1. Apri MongoDB Compass
2. Clicca **"+ Add new connection"**
3. Nel campo **URI** inserisci: mongodb://127.0.0.1:27017

4. Assegna il nome `ESG-LOCAL`
5. Clicca **Save & Connect**

Dovresti vedere i database di sistema (`admin`, `config`, `local`).

---

### 2. Crea il database

1. Clicca **"+"** accanto a **Databases**
2. Inserisci:
- **Database Name:** `TEST_PROGETTO`
- **Collection Name:** `events_user`
3. Clicca **Create Database**

> Le altre collection vengono create automaticamente al primo evento loggato:

| Collection | Descrizione |
|---|---|
| `events_user` | Login, registrazione, aggiornamento utenti |
| `events_bilancio` | Creazione e gestione bilanci |
| `events_revisione` | Assegnazione revisori, note, giudizi |
| `events_template` | Gestione voci contabili |
| `events_esg` | Indicatori ESG e competenze |
| `events_company` | Gestione aziende |
| `events_general` | Eventi non categorizzati |

---

## ✅ Verifica finale

1. Apri nel browser: http://localhost/ESG-BALANCE/PHP/login.php

2. Esegui il login con un utente esistente
3. Torna su Compass → `TEST_PROGETTO → events_user`
4. Clicca **Refresh** — deve comparire un documento simile a:

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

✅ Se il documento è presente, l'ambiente è configurato correttamente.

---

## ❓ Problemi comuni

| Problema | Soluzione |
|---|---|
| `mongod` non riconosciuto | Aggiungi `C:\Program Files\MongoDB\Server\8.2\bin` al PATH e riapri PowerShell |
| `composer` non riconosciuto | Aggiungi `C:\ProgramData\ComposerSetup\bin` al PATH e riapri PowerShell |
| `ext-mongodb` mancante | Verifica che `php_mongodb.dll` sia in `ext\` e che `php.ini` abbia `extension=php_mongodb.dll` |
| HTTP ERROR 500 | Controlla `C:\MAMP\logs\php_error.log` per il dettaglio dell'errore |
| Lock file warning | Esegui `composer update` invece di `composer install` |