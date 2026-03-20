<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION["Username"])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION["Ruolo"] !== "responsabile") {
    header("Location: ../menu.php");
    exit();
}

$username  = $_SESSION["Username"];
$pdo       = getDB();
$messaggio = "";
$errore    = "";

// Crea bilancio tramite SP
if (isset($_POST["crea_bilancio"])) {
    $rag_soc = trim($_POST["ragione_sociale"]);
    $id_bil  = (int)$_POST["id_bilancio"];

    if ($rag_soc === "" || $id_bil <= 0) {
        $errore = "Ragione sociale e ID bilancio sono obbligatori.";
    } else {
        try {
            $stmt = $pdo->prepare("CALL sp_CreaBilancioEsercizio(?, ?)");
            $stmt->execute([$id_bil, $rag_soc]);
            $messaggio = "Bilancio #$id_bil creato per '$rag_soc'.";
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $errore = "Errore: un bilancio con questo ID esiste già per questa azienda.";
            } else {
                $errore = "Errore DB: " . $e->getMessage();
            }
        }
    }
}

// Associa voce al bilancio tramite SP
if (isset($_POST["associa_voce"])) {
    $rag_soc   = trim($_POST["ragione_sociale_voce"]);
    $id_bil    = (int)$_POST["id_bilancio_voce"];
    $nome_voce = trim($_POST["nome_voce"]);
    $valore    = (int)$_POST["valore"];

    if ($rag_soc === "" || $id_bil <= 0 || $nome_voce === "") {
        $errore = "Tutti i campi sono obbligatori.";
    } else {
        try {
            $stmt = $pdo->prepare("CALL sp_PopolaBilancioEsercizio(?, ?, ?, ?)");
            $stmt->execute([$id_bil, $nome_voce, $rag_soc, $valore]);
            $messaggio = "Voce '$nome_voce' (valore: $valore) associata al bilancio #$id_bil.";
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $errore = "Errore: questa voce è già associata al bilancio.";
            } else {
                $errore = "Errore DB: " . $e->getMessage();
            }
        }
    }
}

// Lettura aziende del responsabile loggato
$aziende = [];
try {
    $stmt = $pdo->prepare(
        "SELECT Ragione_sociale FROM AZIENDA
         WHERE Username_Responsabile_Aziendale = ?
         ORDER BY Ragione_sociale"
    );
    $stmt->execute([$username]);
    $aziende = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore lettura aziende: " . $e->getMessage();
}

// Lettura voci disponibili
$voci = [];
try {
    $voci = $pdo->query("SELECT Nome FROM VOCE ORDER BY Nome")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore lettura VOCE: " . $e->getMessage();
}

// Lettura bilanci delle aziende del responsabile
$bilanci = [];
try {
    $stmt = $pdo->prepare(
        "SELECT b.id, b.Ragione_sociale_azienda, b.Data_creazione, b.Stato
         FROM BILANCIO b
         JOIN AZIENDA a ON b.Ragione_sociale_azienda = a.Ragione_sociale
         WHERE a.Username_Responsabile_Aziendale = ?
         ORDER BY b.id DESC"
    );
    $stmt->execute([$username]);
    $bilanci = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore lettura bilanci: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crea Bilancio</title>
    <link rel="stylesheet" href="../../STYLE/style.css">
</head>
<body>
    <div class="card-full">
        <div class="card-header">
            <h1>Crea Bilancio di Esercizio</h1>
            <a href="../menu.php" class="btn-logout">← Torna al menu</a>
        </div>

        <?php if ($messaggio): ?><p><?= htmlspecialchars($messaggio) ?></p><?php endif; ?>
        <?php if ($errore):    ?><p><?= htmlspecialchars($errore) ?></p><?php endif; ?>

        <h2>Nuovo Bilancio</h2>
        <form action="crea_bilancio.php" method="post">
            <div class="input-group2">
                <label>Ragione Sociale Azienda *</label>
                <select name="ragione_sociale" required>
                    <option value="">-- seleziona azienda --</option>
                    <?php foreach ($aziende as $a): ?>
                        <option value="<?= htmlspecialchars($a["Ragione_sociale"]) ?>">
                            <?= htmlspecialchars($a["Ragione_sociale"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="input-group2">
                <label>ID Bilancio *</label>
                <input type="number" name="id_bilancio" min="1" required>
            </div>
            <input type="submit" name="crea_bilancio" value="Crea Bilancio" class="add-btn">
        </form>
        <br>
        <h2>Associa Voce al Bilancio</h2>
        <form action="crea_bilancio.php" method="post">
            <div class="input-group2">
                <label>Ragione Sociale Azienda *</label>
                <select name="ragione_sociale_voce" required>
                    <option value="">-- seleziona azienda --</option>
                    <?php foreach ($aziende as $a): ?>
                        <option value="<?= htmlspecialchars($a["Ragione_sociale"]) ?>">
                            <?= htmlspecialchars($a["Ragione_sociale"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <div class="input-group2">
            <label>ID Bilancio *</label>
            <input type="number" name="id_bilancio_voce" min="1" required>
        </div>
        <div class="input-group2">
            <label>Voce Contabile *</label>
            <select name="nome_voce" required>
                <option value="">-- seleziona voce --</option>
                <?php foreach ($voci as $v): ?>
                    <option value="<?= htmlspecialchars($v["Nome"]) ?>">
                        <?= htmlspecialchars($v["Nome"]) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="input-group2">
            <label>Valore *</label>
            <input type="number" name="valore" required>
        </div>
            <input type="submit" name="associa_voce" value="Associa Voce" class="add-btn">
        </form>

        <div class="table-container">
            <?php if ($bilanci): ?>
                <h2>I tuoi bilanci (<?= count($bilanci) ?>)</h2>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Azienda</th>
                            <th>Data creazione</th>
                            <th>Stato</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bilanci as $r): ?>
                            <tr>
                                <td><code class="id-badge">#<?= htmlspecialchars($r["id"]) ?></code></td>
                                <td><strong><?= htmlspecialchars($r["Ragione_sociale_azienda"]) ?></strong></td>
                                <td style="color: #64748b; font-size: 0.9rem;">
                                    <?= htmlspecialchars($r["Data_creazione"]) ?>
                                </td>
                                <td>
                                    <span class="status-pill stato-default">
                                        <?= htmlspecialchars($r["Stato"]) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="empty-msg">Nessun bilancio presente.</p>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>
