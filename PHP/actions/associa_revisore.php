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

$pdo       = getDB();
$messaggio = "";
$errore    = "";

// Associa revisore a bilancio tramite SP
if (isset($_POST["associa_revisore"])) {
    $id_bil  = (int)$_POST["id_bilancio"];
    $rag_soc = trim($_POST["ragione_sociale"]);
    $rev     = trim($_POST["username_revisore"]);

    if ($id_bil <= 0 || $rag_soc === "" || $rev === "") {
        $errore = "Tutti i campi sono obbligatori.";
    } else {
        try {
            $stmt = $pdo->prepare("CALL sp_AssociaRevisore(?, ?, ?)");
            $stmt->execute([$rev, $id_bil, $rag_soc]);
            $messaggio = "Revisore '$rev' associato al bilancio #$id_bil ($rag_soc).";
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $errore = "Errore: il revisore è già assegnato a questo bilancio.";
            } else {
                $errore = "Errore DB: " . $e->getMessage();
            }
        }
    }
}

// Lettura revisori disponibili
$revisori = [];
try {
    $revisori = $pdo->query(
        "SELECT Username FROM REVISORE_ESG ORDER BY Username"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore lettura REVISORE_ESG: " . $e->getMessage();
}

// Lettura ultimi 50 bilanci
$bilanci = [];
try {
    $bilanci = $pdo->query(
        "SELECT id, Ragione_sociale_azienda, Stato FROM BILANCIO ORDER BY id DESC LIMIT 50"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore lettura BILANCIO: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Associa Revisore a Bilancio</title>
</head>
<body>
    <h1>Associa Revisore ESG a Bilancio</h1>

    <?php if ($messaggio): ?><p><?= htmlspecialchars($messaggio) ?></p><?php endif; ?>
    <?php if ($errore):    ?><p><?= htmlspecialchars($errore) ?></p><?php endif; ?>

    <form action="associa_revisore.php" method="post">
        <label>Bilancio * </label><br>
        <select name="id_bilancio" required onchange="sincronizzaRagioneSociale(this)">
            <option value="">-- seleziona bilancio --</option>
            <?php foreach ($bilanci as $b): ?>
                <option value="<?= htmlspecialchars($b["id"]) ?>"
                        data-ragione="<?= htmlspecialchars($b["Ragione_sociale_azienda"]) ?>">
                    #<?= htmlspecialchars($b["id"]) ?> — <?= htmlspecialchars($b["Ragione_sociale_azienda"]) ?> (<?= htmlspecialchars($b["Stato"]) ?>)
                </option>
            <?php endforeach; ?>
        </select><br>
        <input type="hidden" name="ragione_sociale" id="ragione_sociale_hidden">

        <label>Username Revisore ESG *</label><br>
        <select name="username_revisore" required>
            <option value="">-- seleziona revisore --</option>
            <?php foreach ($revisori as $r): ?>
                <option value="<?= htmlspecialchars($r["Username"]) ?>">
                    <?= htmlspecialchars($r["Username"]) ?>
                </option>
            <?php endforeach; ?>
        </select><br>

        <input type="submit" name="associa_revisore" value="Associa Revisore">
    </form>

    <script>
    function sincronizzaRagioneSociale(sel) {
        var opt = sel.options[sel.selectedIndex];
        document.getElementById('ragione_sociale_hidden').value = opt.dataset.ragione || '';
    }
    </script>

    <?php if ($bilanci): ?>
        <h2>Ultimi 50 bilanci</h2>
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
        <p>Nessun bilancio presente.</p>
    <?php endif; ?>

    <br><a href="../menu.php">← Torna al menu</a>
</body>
</html>
