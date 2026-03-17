<?php
session_start();
require_once "../db.php";

// Controllo login
if (!isset($_SESSION["Username"])) {
    header("Location: ../login.php");
    exit();
}

// Controllo ruolo
if ($_SESSION["Ruolo"] !== "responsabile") {
    header("Location: ../menu.php");
    exit();
}

$username  = $_SESSION["Username"];
$pdo       = getDB();
$messaggio = "";
$errore    = "";

// Inserisci valore ESG
if (isset($_POST["inserisci_valore"])) {
    $nome_voce = trim($_POST["nome_voce"]);
    $nome_esg  = trim($_POST["nome_esg"]);
    $valore    = trim($_POST["valore"]);
    $fonte     = trim($_POST["fonte"]) ?: null;

    if ($nome_voce === "" || $nome_esg === "" || $valore === "") {
        $errore = "Voce, indicatore ESG e valore sono obbligatori.";
    } else {
        try {
            $pdo->prepare(
                "INSERT INTO COLLEGA_ESG_VOCE
                    (NomeVoce, NomeEsg, Valore, Fonte, Data)
                 VALUES (?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE Valore = VALUES(Valore), Fonte = VALUES(Fonte), Data = NOW()"
            )->execute([$nome_voce, $nome_esg, $valore, $fonte]);
            $messaggio = "Valore ESG inserito per voce '$nome_voce' — indicatore '$nome_esg'.";
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

// Lettura indicatori ESG disponibili
$indicatori = [];
try {
    $indicatori = $pdo->query(
        "SELECT Nome, Rilevanza FROM INDICATORE_ESG ORDER BY Nome"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore lettura INDICATORE_ESG: " . $e->getMessage();
}

// Lettura valori ESG già inseriti
$valori = [];
try {
    $valori = $pdo->query(
        "SELECT NomeVoce, NomeEsg, Valore, Fonte, Data
         FROM COLLEGA_ESG_VOCE
         ORDER BY Data DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore lettura valori ESG: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inserisci Valore ESG</title>
</head>
<body>
    <h1>Inserisci Valore Indicatore ESG per Voce</h1>

    <?php if ($messaggio): ?><p style="color:green"><?= htmlspecialchars($messaggio) ?></p><?php endif; ?>
    <?php if ($errore):    ?><p style="color:red"><?= htmlspecialchars($errore) ?></p><?php endif; ?>

    <form action="inserisci_valore_esg.php" method="post">
        <label>Voce Contabile *</label><br>
        <select name="nome_voce" required>
            <option value="">-- seleziona voce --</option>
            <?php foreach ($voci as $v): ?>
                <option value="<?= htmlspecialchars($v["Nome"]) ?>">
                    <?= htmlspecialchars($v["Nome"]) ?>
                </option>
            <?php endforeach; ?>
        </select><br>

        <label>Indicatore ESG *</label><br>
        <select name="nome_esg" required>
            <option value="">-- seleziona indicatore --</option>
            <?php foreach ($indicatori as $i): ?>
                <option value="<?= htmlspecialchars($i["Nome"]) ?>">
                    <?= htmlspecialchars($i["Nome"]) ?> (rilevanza: <?= $i["Rilevanza"] ?? "—" ?>)
                </option>
            <?php endforeach; ?>
        </select><br>

        <label>Valore *</label><br>
        <input type="number" step="0.01" name="valore" required><br>

        <label>Fonte (opzionale)</label><br>
        <input type="text" name="fonte" maxlength="30"><br>

        <input type="submit" name="inserisci_valore" value="Inserisci Valore">
    </form>

    <?php if ($valori): ?>
        <h2>Valori ESG inseriti (<?= count($valori) ?>)</h2>
        <table border="1">
            <tr><th>Voce</th><th>Indicatore ESG</th><th>Valore</th><th>Fonte</th><th>Data</th></tr>
            <?php foreach ($valori as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r["NomeVoce"]) ?></td>
                    <td><?= htmlspecialchars($r["NomeEsg"]) ?></td>
                    <td><?= htmlspecialchars($r["Valore"]) ?></td>
                    <td><?= htmlspecialchars($r["Fonte"] ?? "—") ?></td>
                    <td><?= htmlspecialchars($r["Data"]) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Nessun valore ESG inserito.</p>
    <?php endif; ?>

    <br><a href="../menu.php">← Torna al menu</a>
</body>
</html>
