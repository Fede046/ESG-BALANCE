<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["Username"])) {
    header("Location: login.php");
    exit();
}

$pdo    = getDB();
$errore = "";

// Stat 1 — numero aziende (VIEW)
$num_aziende = 0;
try {
    $num_aziende = $pdo->query("SELECT Numero_Aziende FROM VISTA_NUMERO_AZIENDE")->fetchColumn();
} catch (PDOException $e) {
    $errore = "Errore stat 1: " . $e->getMessage();
}

// Stat 2 — numero revisori (VIEW)
$num_revisori = 0;
try {
    $num_revisori = $pdo->query("SELECT Numero_Revisori FROM VISTA_NUMERO_REVISORI")->fetchColumn();
} catch (PDOException $e) {
    $errore = "Errore stat 2: " . $e->getMessage();
}

// Stat 3 — azienda con affidabilità più alta (VIEW)
$azienda_top = null;
try {
    $azienda_top = $pdo->query("SELECT Azienda, PercentualeAffidabilita FROM VISTA_AZIENDA_TOP_AFFIDABILITA")->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore stat 3: " . $e->getMessage();
}

// Stat 4 — classifica bilanci per indicatori ESG (VIEW)
$classifica = [];
try {
    $classifica = $pdo->query("SELECT ID_Bilancio, Azienda, Totale_Indicatori_ESG FROM VISTA_CLASSIFICA_BILANCI")->fetchAll(PDO::FETCH_ASSOC);
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
    <link rel="stylesheet" href="../STYLE/style.css">

</head>
<body>
    <div class="card-full">
        <div class="card-header">
            <h1>Statistiche ESG Balance</h1>
            <a href="menu.php" class="btn-logout">← Torna al menu</a>
        </div>

        <?php if ($errore): ?><p><?= htmlspecialchars($errore) ?></p><?php endif; ?>

        <h2>Aziende registrate</h2>
        <p><strong><?= htmlspecialchars($num_aziende) ?></strong> aziende presenti in piattaforma.</p>

        <h2>Revisori ESG registrati</h2>
        <p><strong><?= htmlspecialchars($num_revisori) ?></strong> revisori ESG presenti in piattaforma.</p>

        <h2>Azienda con affidabilità più alta</h2>
        <div class="table-container">
            <?php if ($azienda_top): ?>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Azienda</th>
                            <th>Affidabilità %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong><?= htmlspecialchars($azienda_top["Azienda"]) ?></strong></td>
                            <td>
                                <span class="badge">
                                    <?= htmlspecialchars(round($azienda_top["PercentualeAffidabilita"], 2)) ?>%
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="empty-msg">Nessun dato disponibile.</p>
            <?php endif; ?>
        </div>

        <hr class="separator">

        <div class="table-container">
            <h2>Classifica bilanci per indicatori ESG collegati</h2>
            <?php if ($classifica): ?>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>ID Bilancio</th>
                            <th>Azienda</th>
                            <th>Nr. Indicatori ESG</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classifica as $r): ?>
                            <tr>
                                <td><code>#<?= htmlspecialchars($r["ID_Bilancio"]) ?></code></td>
                                <td><strong><?= htmlspecialchars($r["Azienda"]) ?></strong></td>
                                <td>
                                    <span class="badge">
                                        <?= htmlspecialchars($r["Totale_Indicatori_ESG"]) ?> indicatori
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="empty-msg">Nessun bilancio presente.</p>
            <?php endif; ?>
    </div></div>
</body>
</html>
