<?php
require_once "db.php";

$message = "";

if (isset($_POST["register"])) {
    if (!empty($_POST["usr"]) && !empty($_POST["psw"])) {
        $message = registraUtente();
        if ($message === "ok") {
            header("Location: login.php");
            exit();
        }
    } else {
        $message = "Inserisci almeno Username e Password per proseguire con la registrazione.";
    }
}

function registraUtente() {
    try {
        $pdo   = getDB();
        $usr   = trim($_POST['usr']         ?? '');
        $psw   = $_POST['psw']              ?? '';
        $psw_confirm = $_POST['psw_confirm'] ?? '';
        $CF    = trim($_POST['CF']          ?? '');
        $luogo = trim($_POST['luogo']       ?? '');
        $data  = $_POST['data']             ?? '';
        $ruolo = $_POST['ruolo']            ?? '';
        $emails = $_POST['emails']          ?? [];

        // 1. Lunghezza minima password
        if (strlen($psw) < 8) {
            return "La password deve essere di almeno 8 caratteri.";
        }

        // 2. Conferma password
        if ($psw !== $psw_confirm) {
            return "Le password non coincidono.";
        }

        // 3. Ruolo valido
        if (!in_array($ruolo, ['revisore', 'responsabile'])) {
            return "Seleziona un ruolo valido.";
        }

        // 4. Codice Fiscale formato
        if (!preg_match('/^[A-Z0-9]{16}$/i', $CF)) {
            return "Codice Fiscale non valido (16 caratteri alfanumerici).";
        }

        // 5. Data di nascita: non nel futuro e almeno 18 anni
        if (!empty($data)) {
            $dataNascita = new DateTime($data);
            $oggi        = new DateTime();
            $eta         = $oggi->diff($dataNascita)->y;
            if ($dataNascita > $oggi) {
                return "Data di nascita non valida (non può essere nel futuro).";
            }
        }

        // 6. Validazione email
        foreach ($emails as $email) {
            $email = trim($email);
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return "L'indirizzo email '$email' non è valido.";
            }
        }

        // Hash MD5 con salt 'jdd'
        $psw_hash = md5($psw . "jdd");

        // 7. Almeno una email obbligatoria
        $emailsFiltrate = array_filter(array_map('trim', $emails));
        if (empty($emailsFiltrate)) {
            return "Inserisci almeno un indirizzo email.";
        }

        // Gestione upload CV per il responsabile
        // Placeholder di default: indica che nessun CV è stato caricato
        $extra = 'uploads/cv/default.pdf';
        if ($ruolo === 'responsabile') {
            if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime  = $finfo->file($_FILES['cv']['tmp_name']);

                if ($mime !== 'application/pdf') {
                    return "Il CV deve essere un file PDF valido.";
                }

                $uploadDir = '../uploads/cv/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                $filename = 'cv_' . $usr . '.pdf';
                $destPath = $uploadDir . $filename;

                if (move_uploaded_file($_FILES['cv']['tmp_name'], $destPath)) {
                    $extra = 'uploads/cv/' . $filename; // sovrascrive il placeholder
                } else {
                    return "Errore nel salvataggio del CV.";
                }
            }
            // CV opzionale: se non caricato $extra rimane 'uploads/cv/default.pdf'
        }

        $stmt = $pdo->prepare("CALL sp_Registrazione(?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$usr, $CF, $psw_hash, $luogo, $data, $ruolo, $extra]);

        foreach ($emails as $email) {
            $email = trim($email);
            if ($email !== '') {
                $pdo->prepare(
                    "INSERT INTO EMAIL (Username_Utente, Indirizzo) VALUES (?, ?)"
                )->execute([$usr, $email]);
            }
        }

        require_once "db_mongo.php";
        logEvento('USER_REGISTER', "Nuovo utente registrato: $usr (ruolo: $ruolo)", 0, 0);

        return "ok";

    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            return "Errore: lo username è già occupato. Scegline un altro.";
        }
        return "Si è verificato un errore imprevisto: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione – ESG Balance</title>
    <link rel="stylesheet" href="../STYLE/style.css">
</head>
<body>
    <div class="card">
        <form action="registration.php" method="post" enctype="multipart/form-data">
            <div class="input-group">
                <label>Username:</label>
                <input type="text" name="usr" placeholder="Inserisci username" required
                value="<?= htmlspecialchars($_POST['usr'] ?? '') ?>">
            </div>
            <div class="input-group">
                <label>Password:</label>
                <input type="password" name="psw" placeholder="••••••••" required>
            </div>
            <div class="input-group">
                <label>Ripeti Password:</label>
                <input type="password" name="psw_confirm" placeholder="••••••••" required>
            </div>
            <div class="input-group">
                <label>Codice Fiscale:</label>
                <input type="text" name="CF" placeholder="Es: RSSMRA50R15H501Y" required
                value="<?= htmlspecialchars($_POST['CF'] ?? '') ?>">
            </div>
            <div class="input-group">
                <label>Luogo di Nascita:</label>
                <input type="text" name="luogo" placeholder="Es: Bologna" required
                value="<?= htmlspecialchars($_POST['luogo'] ?? '') ?>">
            </div>
            <div class="input-group">
                <label>Data di Nascita:</label>
                <input type="date" name="data" required>
            </div>
            <div class="input-group">
                <label>Ruolo *</label>
                <label><input type="radio" name="ruolo" value="revisore" required
                <?= (($_POST['ruolo'] ?? '') === 'revisore') ? 'checked' : '' ?>> Revisore ESG</label>
                <label><input type="radio" name="ruolo" value="responsabile"
                <?= (($_POST['ruolo'] ?? '') === 'responsabile') ? 'checked' : '' ?>> Responsabile Aziendale</label>
            </div>

            <div class="input-group">
                <div id="cv_block" style="display:none">
                    <label>CV (PDF):</label>
                    <input type="file" name="cv" accept=".pdf">
                </div>
            </div>

            <div class="input-group">
                <div id="container">
                    <?php
                    $emails_post = $_POST['emails'] ?? [''];
                    foreach ($emails_post as $i => $em):
                    ?>
                <div>
                    <input type="email" name="emails[]"
                   placeholder="mario.rossi@gmail.com"
                   <?= $i === 0 ? 'required' : '' ?>
                   value="<?= htmlspecialchars(trim($em)) ?>">
                    <button type="button" class="remove-btn">Remove</button>
                </div>
                <?php endforeach; ?>
                </div>
                <input type="button" id="addEmail" class="add-btn" value="Add Email">
            </div>

            <script>
                document.querySelectorAll('input[name="ruolo"]').forEach(function(radio) {
                    radio.addEventListener('change', function() {
                        document.getElementById('cv_block').style.display =
                            (this.value === 'responsabile') ? 'block' : 'none';
                    });
                });

                const container = document.getElementById('container');
                document.getElementById('addEmail').addEventListener('click', function() {
                    const div = document.createElement('div');
                    div.innerHTML = '<input type="email" name="emails[]"><button type="button" class="remove-btn">Remove</button>';
                    container.appendChild(div);
                });
                container.addEventListener('click', function(e) {
                    if (e.target.classList.contains('remove-btn')) {
                        if (container.children.length > 1) e.target.parentElement.remove();
                    }
                });
            </script>

            <br>
            <input type="submit" name="register" value="Register" class="btn-login">
        </form>
        <?php if ($message !== '' && $message !== 'ok'): ?>
            <p class="error-msg"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>
        <a href="home.php" class="btn-home">Home</a>
    </div>
</body>
</html>
