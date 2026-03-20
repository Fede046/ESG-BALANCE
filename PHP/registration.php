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
        $ruolo = $_POST["ruolo"] ?? '';
        $extra = ($ruolo === 'responsabile') ? (trim($_POST['cv'] ?? '')) : '';

        // Hash MD5 con salt 'jdd' prima di salvare nel DB
        $psw_hash = md5($_POST['psw'] . "jdd");

        $stmt = $pdo->prepare("CALL sp_Registrazione(?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['usr'],
            $_POST['CF']    ?? null,
            $psw_hash,
            $_POST['luogo'] ?? null,
            $_POST['data']  ?? null,
            $ruolo,
            $extra
        ]);

        // Inserimento email (una o più)
        $emails = $_POST['emails'] ?? [];
        foreach ($emails as $email) {
            $email = trim($email);
            if ($email !== '') {
                $pdo->prepare(
                    "INSERT INTO EMAIL (Username_Utente, Indirizzo) VALUES (?, ?)"
                )->execute([$_POST['usr'], $email]);
            }
        }

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
        <form action="registration.php" method="post">
            <div class="input-group">
                <label>Username:</label>
                <input type="text" name="usr" placeholder="Inserisci username" required>
            </div>
            <div class="input-group">
                <label>Password:</label>
                <input type="password" name="psw" placeholder="••••••••" required>
            </div>
            <div class="input-group">
                <label>Codice Fiscale:</label>
                <input type="text" name="CF" placeholder="Es: RSSMRA50R15H501Y" required>
            </div>
            <div class="input-group">
                <label>Luogo di Nascita:</label>
                <input type="text" name="luogo" placeholder="Es: Bologna" required>
            </div>
            <div class="input-group">
                <label>Data di Nascita:</label>
                <input type="date" name="data" required>
            </div>
            <div class="input-group">
                <label>Ruolo *</label>
                <label><input type="radio" name="ruolo" value="revisore" required> Revisore ESG</label>
                <label><input type="radio" name="ruolo" value="responsabile" > Responsabile Aziendale</label>
            </div>
            <div class="input-group">
                <div id="cv_block" style="display:none">
                    <label>CV (PDF):</label>
                    <input type="file" name="cv" accept=".pdf">
                </div>
            </div>
            <div class="input-group">
                <div id="container">
                    <div>
                        <input type="email" name="emails[]" placeholder="mario.rossi@gmail.com">
                        <button type="button" class="remove-btn">Remove</button>
                    </div>
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

        <a href="home.php" class="btn-home">Home</a>
</div>

    <?php if ($message !== '' && $message !== 'ok'): ?>
        <p class="error-msg"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
</body>
</html>
