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
            $stmt = $pdo->prepare("CALL sp_InserisciCompetenzaRevisore(?, ?, ?)");
            $stmt->execute([$username, $nome_comp, $livello]);
            $messaggio = "Competenza '$nome_comp' (livello $livello) aggiunta.";
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
            <label>Nome competenza * (max 30 caratteri)</label><br>
            <input type="text" name="nome_competenza" maxlength="30" required><br>

            <label>Livello * (0–5)</label><br>
            <input type="number" name="livello" min="0" max="5" required><br>

            <input type="submit" name="aggiungi_competenza" value="Aggiungi Competenza">
        </form>

        <?php if ($competenze): ?>
            <h2>Le tue competenze (<?= count($competenze) ?>)</h2>
            <table border="1">
                <tr><th>Competenza</th><th>Livello</th></tr>
                <?php foreach ($competenze as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r["Nome_competenza"]) ?></td>
                        <td><?= htmlspecialchars($r["Livello"]) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>Nessuna competenza dichiarata.</p>
        <?php endif; ?>

    </div>
</body>
</html>
