<?php
session_start();
require_once "../db.php";

// Controllo login
if (!isset($_SESSION["Username"])) {
    header("Location: ../login.php");
    exit();
}

// Controllo ruolo
if ($_SESSION["Ruolo"] !== "amministratore") {
    header("Location: ../menu.php");
    exit();
}

$username  = $_SESSION["Username"];
$pdo       = getDB();
$messaggio = "";
$errore    = "";

// Aggiungi indicatore
if (isset($_POST["aggiungi_indicatore"])) {
    $nome      = trim($_POST["nome_indicatore"]);
    $rilevanza = $_POST["rilevanza"] !== "" ? (int)$_POST["rilevanza"] : null;
    $immagine  = trim($_POST["immagine"]) ?: null;

    if ($nome === "") {
        $errore = "Il nome dell'indicatore è obbligatorio.";
    } elseif ($rilevanza !== null && ($rilevanza < 0 || $rilevanza > 10)) {
        $errore = "La rilevanza deve essere tra 0 e 10.";
    } else {
        try {
            $pdo->prepare(
                "INSERT INTO INDICATORE_ESG (Nome, Username_Amministratore, Immagine, Rilevanza)
                 VALUES (?, ?, ?, ?)"
            )->execute([$nome, $username, $immagine, $rilevanza]);
            $messaggio = "Indicatore '$nome' aggiunto.";
        } catch (PDOException $e) {
            $errore = "Errore DB: " . $e->getMessage();
        }
    }
}

// Lettura indicatori esistenti
$indicatori = [];
try {
    $indicatori = $pdo->query(
        "SELECT Nome, Rilevanza FROM INDICATORE_ESG ORDER BY Nome"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore lettura: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aggiungi Indicatore ESG</title>
</head>
<body>
    <h1>Aggiungi Indicatore ESG</h1>

    <?php if ($messaggio): ?><p style="color:green"><?= htmlspecialchars($messaggio) ?></p><?php endif; ?>
    <?php if ($errore):    ?><p style="color:red"><?= htmlspecialchars($errore) ?></p><?php endif; ?>

    <form action="aggiungi_indicatore.php" method="post">
        <label>Nome * (max 30 caratteri)</label><br>
        <input type="text" name="nome_indicatore" maxlength="30" required><br>

        <label>Rilevanza (0–10, opzionale)</label><br>
        <input type="number" name="rilevanza" min="0" max="10"><br>

        <label>Immagine (path al file, opzionale)</label><br>
        <input type="text" name="immagine" maxlength="500"><br>

        <input type="submit" name="aggiungi_indicatore" value="Aggiungi Indicatore">
    </form>

    <?php if ($indicatori): ?>
        <h2>Indicatori presenti (<?= count($indicatori) ?>)</h2>
        <table border="1">
            <tr><th>Nome</th><th>Rilevanza</th></tr>
            <?php foreach ($indicatori as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r["Nome"]) ?></td>
                    <td><?= $r["Rilevanza"] ?? "—" ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Nessun indicatore presente.</p>
    <?php endif; ?>

    <br><a href="../menu.php">← Torna al menu</a>
</body>
</html>
