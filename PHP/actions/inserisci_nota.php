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

// Inserisci nota
if (isset($_POST["inserisci_nota"])) {
    $id_bil  = (int)$_POST["id_bilancio"];
    $rag_soc = trim($_POST["ragione_sociale"]);
    $nome_voce = trim($_POST["nome_voce"]);
    $testo     = trim($_POST["testo"]);

    if ($id_bil <= 0 || $rag_soc === "" || $nome_voce === "" || $testo === "") {
        $errore = "Tutti i campi sono obbligatori.";
    } else {
        // Verifica che il revisore sia assegnato a quel bilancio
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM VALUTA_REVISORE_BILANCIO
             WHERE Username_Revisore_ESG = ? AND id_bilancio = ? AND Ragione_sociale_bilancio = ?"
        );
        $stmt->execute([$username, $id_bil, $rag_soc]);
        if ($stmt->fetchColumn() == 0) {
            $errore = "Non sei assegnato a questo bilancio.";
        } else {
            // Verifica che la voce appartenga al bilancio selezionato
            $stmt2 = $pdo->prepare(
                "SELECT COUNT(*) FROM ASSOCIA_BILANCIO_VOCE
                 WHERE Nome_voce = ? AND id_bilancio = ? AND Ragione_sociale_bilancio = ?"
            );
            $stmt2->execute([$nome_voce, $id_bil, $rag_soc]);
            if ($stmt2->fetchColumn() == 0) {
                $errore = "La voce selezionata non appartiene a questo bilancio.";
            } else {
                try {
                    $pdo->prepare(
                        "INSERT INTO NOTA (Data, Testo, NomeVoce, Username_Revisore_ESG)
                         VALUES (NOW(), ?, ?, ?)"
                    )->execute([$testo, $nome_voce, $username]);
                    $messaggio = "Nota inserita sulla voce '$nome_voce' del bilancio #$id_bil.";
                } catch (PDOException $e) {
                    $errore = "Errore DB: " . $e->getMessage();
                }
            }
        }
    }
}

// Bilanci assegnati al revisore loggato
$bilanci = [];
try {
    $stmt = $pdo->prepare(
        "SELECT b.id, b.Ragione_sociale_azienda, b.Stato
         FROM BILANCIO b
         JOIN VALUTA_REVISORE_BILANCIO v
           ON b.id = v.id_bilancio AND b.Ragione_sociale_azienda = v.Ragione_sociale_bilancio
         WHERE v.Username_Revisore_ESG = ?
         ORDER BY b.id DESC"
    );
    $stmt->execute([$username]);
    $bilanci = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore lettura bilanci: " . $e->getMessage();
}

// Voci del bilancio selezionato (popolate via JS oppure tutte se nessun bilancio selezionato)
$id_sel  = isset($_GET["id_bilancio"]) ? (int)$_GET["id_bilancio"] : 0;
$rag_sel = isset($_GET["ragione_sociale"]) ? trim($_GET["ragione_sociale"]) : "";

$voci = [];
if ($id_sel > 0 && $rag_sel !== "") {
    try {
        $stmt = $pdo->prepare(
            "SELECT abv.Nome_voce
             FROM ASSOCIA_BILANCIO_VOCE abv
             WHERE abv.id_bilancio = ? AND abv.Ragione_sociale_bilancio = ?
             ORDER BY abv.Nome_voce"
        );
        $stmt->execute([$id_sel, $rag_sel]);
        $voci = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errore = "Errore lettura voci: " . $e->getMessage();
    }
}

// Note già inserite dal revisore loggato
$note = [];
try {
    $stmt = $pdo->prepare(
        "SELECT n.Data, n.NomeVoce, n.Testo
         FROM NOTA n
         WHERE n.Username_Revisore_ESG = ?
         ORDER BY n.Data DESC"
    );
    $stmt->execute([$username]);
    $note = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore lettura note: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inserisci Nota</title>
</head>
<body>
    <h1>Inserisci Nota su Voce di Bilancio</h1>

    <?php if ($messaggio): ?><p style="color:green"><?= htmlspecialchars($messaggio) ?></p><?php endif; ?>
    <?php if ($errore):    ?><p style="color:red"><?= htmlspecialchars($errore) ?></p><?php endif; ?>

    <!-- Step 1: seleziona bilancio -->
    <h2>1. Seleziona Bilancio Assegnato</h2>
    <?php if ($bilanci): ?>
        <table border="1">
            <tr><th>ID</th><th>Azienda</th><th>Stato</th><th>Azione</th></tr>
            <?php foreach ($bilanci as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r["id"]) ?></td>
                    <td><?= htmlspecialchars($r["Ragione_sociale_azienda"]) ?></td>
                    <td><?= htmlspecialchars($r["Stato"]) ?></td>
                    <td>
                        <a href="inserisci_nota.php?id_bilancio=<?= urlencode($r["id"]) ?>&ragione_sociale=<?= urlencode($r["Ragione_sociale_azienda"]) ?>">
                            Seleziona
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Nessun bilancio assegnato.</p>
    <?php endif; ?>

    <!-- Step 2: inserisci nota (solo se bilancio selezionato) -->
    <?php if ($id_sel > 0 && $rag_sel !== ""): ?>
        <h2>2. Inserisci Nota &mdash; Bilancio #<?= htmlspecialchars($id_sel) ?> (<?= htmlspecialchars($rag_sel) ?>)</h2>
        <form action="inserisci_nota.php" method="post">
            <input type="hidden" name="id_bilancio" value="<?= htmlspecialchars($id_sel) ?>">
            <input type="hidden" name="ragione_sociale" value="<?= htmlspecialchars($rag_sel) ?>">

            <label>Voce contabile *</label><br>
            <select name="nome_voce" required>
                <option value="">-- seleziona voce --</option>
                <?php foreach ($voci as $v): ?>
                    <option value="<?= htmlspecialchars($v["Nome_voce"]) ?>">
                        <?= htmlspecialchars($v["Nome_voce"]) ?>
                    </option>
                <?php endforeach; ?>
            </select><br>

            <label>Testo nota *</label><br>
            <textarea name="testo" rows="4" cols="50" required></textarea><br>

            <input type="submit" name="inserisci_nota" value="Inserisci Nota">
        </form>
    <?php elseif (count($bilanci) > 0): ?>
        <p><em>Clicca "Seleziona" su un bilancio per inserire una nota.</em></p>
    <?php endif; ?>

    <?php if ($note): ?>
        <h2>Le tue note (<?= count($note) ?>)</h2>
        <table border="1">
            <tr><th>Data</th><th>Voce</th><th>Testo</th></tr>
            <?php foreach ($note as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r["Data"]) ?></td>
                    <td><?= htmlspecialchars($r["NomeVoce"]) ?></td>
                    <td><?= htmlspecialchars($r["Testo"]) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Nessuna nota inserita.</p>
    <?php endif; ?>

    <br><a href="../menu.php">← Torna al menu</a>
</body>
</html>
