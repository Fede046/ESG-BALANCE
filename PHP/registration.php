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
</head>
<body>
    <form action="registration.php" method="post">
        <h3>Username:</h3>
        <input type="text" name="usr">

        <h3>Password:</h3>
        <input type="password" name="psw">

        <h3>Codice Fiscale:</h3>
        <input type="text" name="CF">

        <h3>Luogo di Nascita:</h3>
        <input type="text" name="luogo">

        <h3>Data di Nascita:</h3>
        <input type="date" name="data">

        <h3>Ruolo *</h3>
        <label><input type="radio" name="ruolo" value="revisore" required> Revisore ESG</label><br>
        <label><input type="radio" name="ruolo" value="responsabile"> Responsabile Aziendale</label><br>

        <div id="cv_block" style="display:none">
            <h3>CV (path al file):</h3>
            <input type="text" name="cv" maxlength="500">
        </div>

        <h3>Email:</h3>
        <div id="container">
            <div>
                <input type="email" name="emails[]">
                <button type="button" class="remove-btn">Remove</button>
            </div>
        </div>
        <input type="button" id="addEmail" value="Add Email">

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

        <br><br>
        <input type="submit" name="register" value="Register">
    </form>

    <a href="home.php"><button>Home</button></a>

    <?php if ($message !== '' && $message !== 'ok'): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
</body>
</html>
