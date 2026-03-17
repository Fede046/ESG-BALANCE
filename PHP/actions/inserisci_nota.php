<?php
session_start();
require_once "../db.php";

// Controllo login
if (!isset($_SESSION["Username"])) {
    header("Location: ../login.php");
    exit();
}

// Controllo ruolo
if ($_SESSION["Ruolo"] !== "revisore") {
    header("Location: ../menu.php");
    exit();
}

$username  = $_SESSION["Username"];
$pdo       = getDB();
$messaggio = "";
$errore    = "";

// Inserisci nota
if (isset($_POST["inserisci_nota"])) {
    $nome_voce = trim($_POST["nome_voce"]);
    $testo     = trim($_POST["testo"]);

    if ($nome_voce === "" || $testo === "") {
        $errore = "Tutti i campi sono obbligatori.";
    } else {
        try {
            $pdo->prepare(
                "INSERT INTO NOTA (Data, Testo, NomeVoce, Username_Revisore_ESG)
                 VALUES (NOW(), ?, ?, ?)"
            )->execute([$testo, $nome_voce, $username]);
            $messaggio = "Nota inserita sulla voce '$nome_voce'.";
        } catch (PDOException $e) {
            $errore = "Errore DB: " . $e->getMessage();
        }
    }
}

// Lettura voci disponibili
$voci = [];
try {
    $voci = $pdo->query(
        "SELECT Nome FROM VOCE ORDER BY Nome"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore lettura VOCE: " . $e->getMessage();
}

// Lettura note già inserite dal revisore loggato
$note = [];
try {
    $stmt = $pdo->prepare(
        "SELECT n.Data, n.NomeVoce, n.Testo
         FROM NOTA n
         WHERE n.Username_Revisore_ESG = ?
         ORDER BY n.Data DESC"
    );
    $stmt->execute([$username]);
    $note = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore lettura note: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inserisci Nota</title>
</head>
<body>
    <h1>Inserisci Nota su Voce di Bilancio</h1>

    <?php if ($messaggio): ?><p style="color:green"><?= htmlspecialchars($messaggio) ?></p><?php endif; ?>
    <?php if ($errore):    ?><p style="color:red"><?= htmlspecialchars($errore) ?></p><?php endif; ?>

    <form action="inserisci_nota.php" method="post">
        <label>Voce contabile *</label><br>
        <select name="nome_voce" required>
            <option value="">-- seleziona voce --</option>
            <?php foreach ($voci as $v): ?>
                <option value="<?= htmlspecialchars($v["Nome"]) ?>">
                    <?= htmlspecialchars($v["Nome"]) ?>
                </option>
            <?php endforeach; ?>
        </select><br>

        <label>Testo nota *</label><br>
        <textarea name="testo" rows="4" cols="50" required></textarea><br>

        <input type="submit" name="inserisci_nota" value="Inserisci Nota">
    </form>

    <?php if ($note): ?>
        <h2>Le tue note (<?= count($note) ?>)</h2>
        <table border="1">
            <tr><th>Data</th><th>Voce</th><th>Testo</th></tr>
            <?php foreach ($note as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r["Data"]) ?></td>
                    <td><?= htmlspecialchars($r["NomeVoce"]) ?></td>
                    <td><?= htmlspecialchars($r["Testo"]) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Nessuna nota inserita.</p>
    <?php endif; ?>

    <br><a href="../menu.php">← Torna al menu</a>
</body>
</html>
