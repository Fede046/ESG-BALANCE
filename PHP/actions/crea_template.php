<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION["Username"])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION["Ruolo"] !== "amministratore") {
    header("Location: ../menu.php");
    exit();
}

$username  = $_SESSION["Username"];
$pdo       = getDB();
$messaggio = "";
$errore    = "";

// Aggiungi voce al template tramite SP
if (isset($_POST["aggiungi_voce"])) {
    $nome_voce   = trim($_POST["nome_voce"]);
    $descrizione = trim($_POST["descrizione"]) ?: null;

    if ($nome_voce === "") {
        $errore = "Il nome della voce è obbligatorio.";
    } else {
        try {
            $stmt = $pdo->prepare("CALL sp_CreaVoceTemplate(?, ?, ?)");
            $stmt->execute([$nome_voce, $descrizione, $username]);
            $messaggio = "Voce '$nome_voce' aggiunta al template.";
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $errore = "Errore: una voce con questo nome esiste già.";
            } else {
                $errore = "Errore DB: " . $e->getMessage();
            }
        }
    }
}

// Lettura voci esistenti
$voci = [];
try {
    $voci = $pdo->query(
        "SELECT Nome, Descrizione FROM VOCE ORDER BY Nome"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore lettura VOCE: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crea Template Bilancio</title>
</head>
<body>
    <h1>Crea Template Bilancio</h1>

    <?php if ($messaggio): ?><p><?= htmlspecialchars($messaggio) ?></p><?php endif; ?>
    <?php if ($errore):    ?><p><?= htmlspecialchars($errore) ?></p><?php endif; ?>

    <h2>Aggiungi Voce Contabile</h2>
    <form action="crea_template.php" method="post">
        <label>Nome voce * (max 30 caratteri)</label><br>
        <input type="text" name="nome_voce" maxlength="30" required><br>

        <label>Descrizione (path al file, opzionale)</label><br>
        <input type="text" name="descrizione" maxlength="500"><br>

        <input type="submit" name="aggiungi_voce" value="Aggiungi Voce">
    </form>

    <?php if ($voci): ?>
        <h2>Voci contabili presenti (<?= count($voci) ?>)</h2>
        <table border="1">
            <tr><th>Nome</th><th>Descrizione</th></tr>
            <?php foreach ($voci as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r["Nome"]) ?></td>
                    <td><?= htmlspecialchars($r["Descrizione"] ?? "—") ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Nessuna voce presente.</p>
    <?php endif; ?>

    <br><a href="../menu.php">← Torna al menu</a>
</body>
</html>
