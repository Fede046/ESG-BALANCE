<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION["Username"])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION["Ruolo"] !== "revisore") {
    header("Location: ../menu.php");
    exit();
}

$username  = $_SESSION["Username"];
$pdo       = getDB();
$messaggio = "";
$errore    = "";

if (isset($_POST["aggiungi_competenza"])) {
    $nome_comp = trim($_POST["nome_competenza"]);
    $livello   = ($_POST["livello"] !== "") ? (int)$_POST["livello"] : null;

    if ($nome_comp === "") {
        $errore = "Il nome della competenza è obbligatorio.";
    } elseif ($livello === null || $livello < 0 || $livello > 5) {
        $errore = "Il livello deve essere tra 0 e 5.";
} else {
    try {
        // Controllo duplicato PRIMA della SP, per messaggio chiaro
        $chk = $pdo->prepare(
            "SELECT 1 FROM DICHIARA_COMPETENZA_REVISORE
             WHERE Username_revisore = ? AND Nome_competenza = ?"
        );
        $chk->execute([$username, $nome_comp]);

        if ($chk->fetch()) {
            $errore = "Hai già dichiarato questa competenza.";
        } else {
            $stmt = $pdo->prepare("CALL sp_InserisciCompetenzaRevisore(?, ?, ?)");
            $stmt->execute([$username, $nome_comp, $livello]);
            $messaggio = "Competenza '$nome_comp' (livello $livello) aggiunta.";

            require_once "../db_mongo.php";
            logEvento('ADD_COMPETENZA', "Competenza '$nome_comp' (livello $livello) aggiunta da $username", 0, 0);
        }

    } catch (PDOException $e) {
        $errore = "Errore DB: " . $e->getMessage();
    }
}
}

// Lettura competenze del revisore loggato
$competenze = [];
try {
    $stmt = $pdo->prepare(
        "SELECT d.Nome_competenza, d.Livello
         FROM DICHIARA_COMPETENZA_REVISORE d
         WHERE d.Username_revisore = ?
         ORDER BY d.Nome_competenza"
    );
    $stmt->execute([$username]);
    $competenze = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore lettura competenze: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Le mie Competenze</title>
    <link rel="stylesheet" href="../../STYLE/style.css">
</head>
<body>
    <div class="card-full">
        <div class="card-header">
            <h1>Le mie Competenze</h1>
            <a href="../menu.php" class="btn-logout">← Torna al menu</a>
        </div>
        <?php if ($messaggio): ?><p><?= htmlspecialchars($messaggio) ?></p><?php endif; ?>
        <?php if ($errore):    ?><p><?= htmlspecialchars($errore) ?></p><?php endif; ?>

        <form action="aggiungi_competenze.php" method="post">
            <div class="input-group2">
                <label>Nome competenza * (max 30 caratteri)</label>
                <input type="text" name="nome_competenza" maxlength="30" required>
            </div>
            <div class="input-group2">
                <label>Livello * (0–5)</label>
                <input type="number" name="livello" min="0" max="5" required>
            </div>
            <input type="submit" name="aggiungi_competenza" value="Aggiungi Competenza" class="add-btn">
        </form>

        <div class="table-container">
            <?php if ($competenze): ?>
                <h2>Le tue competenze (<?= count($competenze) ?>)</h2>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Competenza</th>
                            <th>Livello</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($competenze as $r): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r["Nome_competenza"]) ?></strong></td>
                                <td>
                                    <span class="badge" >
                                        <?= htmlspecialchars($r["Livello"]) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="empty-msg">Nessuna competenza dichiarata.</p>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>
