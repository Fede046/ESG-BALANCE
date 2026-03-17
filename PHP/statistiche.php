<?php
session_start();
require_once "db.php";

// Controllo login
if (!isset($_SESSION["Username"])) {
    header("Location: login.php");
    exit();
}

$pdo    = getDB();
$errore = "";

// ── Statistica 1 — Numero aziende registrate ─────────────────────────────────
$num_aziende = 0;
try {
    $num_aziende = $pdo->query(
        "SELECT COUNT(*) FROM AZIENDA"
    )->fetchColumn();
} catch (PDOException $e) {
    $errore = "Errore stat 1: " . $e->getMessage();
}

// ── Statistica 2 — Numero revisori ESG registrati ────────────────────────────
$num_revisori = 0;
try {
    $num_revisori = $pdo->query(
        "SELECT COUNT(*) FROM REVISORE_ESG"
    )->fetchColumn();
} catch (PDOException $e) {
    $errore = "Errore stat 2: " . $e->getMessage();
}

// ── Statistica 3 — Azienda con affidabilità più alta ─────────────────────────
// Affidabilità = % bilanci con esito "approvazione" (senza rilievi)
$azienda_top = null;
try {
    $azienda_top = $pdo->query(
        "SELECT
            b.Ragione_sociale_azienda,
            COUNT(CASE WHEN g.Esito = 'approvazione' THEN 1 END) AS approvati,
            COUNT(g.Id) AS totale,
            ROUND(
                COUNT(CASE WHEN g.Esito = 'approvazione' THEN 1 END) * 100.0 / COUNT(g.Id),
                2
            ) AS affidabilita
         FROM BILANCIO b
         JOIN GIUDIZIO g
           ON b.id = g.id_bilancio
          AND b.Ragione_sociale_azienda = g.Ragione_sociale_bilancio
         GROUP BY b.Ragione_sociale_azienda
         ORDER BY affidabilita DESC
         LIMIT 1"
    )->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore stat 3: " . $e->getMessage();
}

// ── Statistica 4 — Classifica bilanci per numero indicatori ESG collegati ────
$classifica = [];
try {
    $classifica = $pdo->query(
        "SELECT
            b.id,
            b.Ragione_sociale_azienda,
            b.Stato,
            COUNT(c.NomeEsg) AS num_indicatori
         FROM BILANCIO b
         LEFT JOIN ASSOCIA_BILANCIO_VOCE abv
           ON b.id = abv.id_bilancio
          AND b.Ragione_sociale_azienda = abv.Ragione_sociale_bilancio
         LEFT JOIN COLLEGA_ESG_VOCE c
           ON abv.Nome_voce = c.NomeVoce
         GROUP BY b.id, b.Ragione_sociale_azienda, b.Stato
         ORDER BY num_indicatori DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore stat 4: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiche – ESG Balance</title>
</head>
<body>
    <h1>Statistiche ESG Balance</h1>

    <?php if ($errore): ?><p style="color:red"><?= htmlspecialchars($errore) ?></p><?php endif; ?>

    <!-- Stat 1 -->
    <h2>Aziende registrate</h2>
    <p><strong><?= htmlspecialchars($num_aziende) ?></strong> aziende presenti in piattaforma.</p>

    <!-- Stat 2 -->
    <h2>Revisori ESG registrati</h2>
    <p><strong><?= htmlspecialchars($num_revisori) ?></strong> revisori ESG presenti in piattaforma.</p>

    <!-- Stat 3 -->
    <h2>Azienda con affidabilità più alta</h2>
    <?php if ($azienda_top): ?>
        <table border="1">
            <tr><th>Azienda</th><th>Bilanci approvati</th><th>Totale giudizi</th><th>Affidabilità %</th></tr>
            <tr>
                <td><?= htmlspecialchars($azienda_top["Ragione_sociale_azienda"]) ?></td>
                <td><?= htmlspecialchars($azienda_top["approvati"]) ?></td>
                <td><?= htmlspecialchars($azienda_top["totale"]) ?></td>
                <td><?= htmlspecialchars($azienda_top["affidabilita"]) ?>%</td>
            </tr>
        </table>
    <?php else: ?>
        <p>Nessun dato disponibile.</p>
    <?php endif; ?>

    <!-- Stat 4 -->
    <h2>Classifica bilanci per indicatori ESG collegati</h2>
    <?php if ($classifica): ?>
        <table border="1">
            <tr><th>ID</th><th>Azienda</th><th>Stato</th><th>Nr. Indicatori ESG</th></tr>
            <?php foreach ($classifica as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r["id"]) ?></td>
                    <td><?= htmlspecialchars($r["Ragione_sociale_azienda"]) ?></td>
                    <td><?= htmlspecialchars($r["Stato"]) ?></td>
                    <td><?= htmlspecialchars($r["num_indicatori"]) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Nessun bilancio presente.</p>
    <?php endif; ?>

    <br><a href="menu.php">← Torna al menu</a>
</body>
</html>
