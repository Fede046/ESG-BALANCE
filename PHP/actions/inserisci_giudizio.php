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

// Inserisci giudizio
if (isset($_POST["inserisci_giudizio"])) {
    $id_bil  = (int)$_POST["id_bilancio"];
    $rag_soc = trim($_POST["ragione_sociale"]);
    $esito   = trim($_POST["esito"]);
    $rilievi = trim($_POST["rilievi"]) ?: null;

    if ($id_bil <= 0 || $rag_soc === "" || $esito === "") {
        $errore = "ID bilancio, ragione sociale ed esito sono obbligatori.";
    } else {
        try {
            $pdo->prepare(
                "INSERT INTO GIUDIZIO
                    (Esito, Data, Rilievi, Username, id_bilancio, Ragione_sociale_bilancio)
                 VALUES (?, NOW(), ?, ?, ?, ?)"
            )->execute([$esito, $rilievi, $username, $id_bil, $rag_soc]);
            $messaggio = "Giudizio inserito sul bilancio #$id_bil ($rag_soc).";
        } catch (PDOException $e) {
            $errore = "Errore DB: " . $e->getMessage();
        }
    }
}

// Lettura bilanci assegnati al revisore loggato
$bilanci = [];
try {
    $stmt = $pdo->prepare(
        "SELECT b.id, b.Ragione_sociale_azienda, b.Stato
         FROM BILANCIO b
         JOIN VALUTA_REVISORE_BILANCIO v
           ON b.id = v.id_bilancio
          AND b.Ragione_sociale_azienda = v.Ragione_sociale_bilancio
         WHERE v.Username_Revisore_ESG = ?
         ORDER BY b.id DESC"
    );
    $stmt->execute([$username]);
    $bilanci = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore lettura bilanci: " . $e->getMessage();
}

// Lettura giudizi già inseriti dal revisore loggato
$giudizi = [];
try {
    $stmt = $pdo->prepare(
        "SELECT g.Data, g.id_bilancio, g.Ragione_sociale_bilancio, g.Esito, g.Rilievi
         FROM GIUDIZIO g
         WHERE g.Username = ?
         ORDER BY g.Data DESC"
    );
    $stmt->execute([$username]);
    $giudizi = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore lettura giudizi: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inserisci Giudizio</title>
</head>
<body>
    <h1>Inserisci Giudizio su Bilancio</h1>

    <?php if ($messaggio): ?><p style="color:green"><?= htmlspecialchars($messaggio) ?></p><?php endif; ?>
    <?php if ($errore):    ?><p style="color:red"><?= htmlspecialchars($errore) ?></p><?php endif; ?>

    <form action="inserisci_giudizio.php" method="post">
        <label>ID Bilancio *</label><br>
        <input type="number" name="id_bilancio" min="1" required><br>

        <label>Ragione Sociale Azienda * (max 30 caratteri)</label><br>
        <input type="text" name="ragione_sociale" maxlength="30" required><br>

        <label>Esito *</label><br>
        <select name="esito" required>
            <option value="">-- seleziona esito --</option>
            <option value="approvazione">Approvazione</option>
            <option value="approvazione con rilievi">Approvazione con rilievi</option>
            <option value="respingimento">Respingimento</option>
        </select><br>

        <label>Rilievi (opzionale)</label><br>
        <textarea name="rilievi" rows="4" cols="50"></textarea><br>

        <input type="submit" name="inserisci_giudizio" value="Inserisci Giudizio">
    </form>

    <?php if ($bilanci): ?>
        <h2>Bilanci assegnati a te</h2>
        <table border="1">
            <tr><th>ID</th><th>Azienda</th><th>Stato</th></tr>
            <?php foreach ($bilanci as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r["id"]) ?></td>
                    <td><?= htmlspecialchars($r["Ragione_sociale_azienda"]) ?></td>
                    <td><?= htmlspecialchars($r["Stato"]) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Nessun bilancio assegnato.</p>
    <?php endif; ?>

    <?php if ($giudizi): ?>
        <h2>I tuoi giudizi (<?= count($giudizi) ?>)</h2>
        <table border="1">
            <tr><th>Data</th><th>ID Bilancio</th><th>Azienda</th><th>Esito</th><th>Rilievi</th></tr>
            <?php foreach ($giudizi as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r["Data"]) ?></td>
                    <td><?= htmlspecialchars($r["id_bilancio"]) ?></td>
                    <td><?= htmlspecialchars($r["Ragione_sociale_bilancio"]) ?></td>
                    <td><?= htmlspecialchars($r["Esito"]) ?></td>
                    <td><?= htmlspecialchars($r["Rilievi"] ?? "—") ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Nessun giudizio inserito.</p>
    <?php endif; ?>

    <br><a href="../menu.php">← Torna al menu</a>
</body>
</html>
