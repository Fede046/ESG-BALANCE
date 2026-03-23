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

if (isset($_POST["inserisci_nota"])) {
    $id_bil    = (int)$_POST["id_bilancio"];
    $rag_soc   = trim($_POST["ragione_sociale"]);
    $nome_voce = trim($_POST["nome_voce"]);
    $testo     = trim($_POST["testo"]);

    if ($id_bil <= 0 || $rag_soc === "" || $nome_voce === "" || $testo === "") {
        $errore = "Tutti i campi sono obbligatori.";
        } else {
        // 1. Limite lunghezza testo nota lato PHP
        if (strlen($testo) > 500) {
            $errore = "Il testo non può superare 500 caratteri.";

        } else {
            // 2. Controlla stato bilancio: non modificabile se chiuso
            $chk_stato = $pdo->prepare(
                "SELECT Stato FROM BILANCIO
                 WHERE id = ? AND Ragione_sociale_azienda = ?"
            );
            $chk_stato->execute([$id_bil, $rag_soc]);
            $bilancio = $chk_stato->fetch(PDO::FETCH_ASSOC);

            if (!$bilancio) {
                $errore = "Bilancio non trovato.";

            } elseif (in_array(strtolower($bilancio['Stato']), ['approvato', 'respinto'])) {
                $errore = "Non puoi aggiungere note a un bilancio già chiuso.";

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
                    // Verifica che la voce appartenga al bilancio
                    $stmt2 = $pdo->prepare(
                        "SELECT COUNT(*) FROM ASSOCIA_BILANCIO_VOCE
                         WHERE Nome_voce = ? AND id_bilancio = ? AND Ragione_sociale_bilancio = ?"
                    );
                    $stmt2->execute([$nome_voce, $id_bil, $rag_soc]);
                    if ($stmt2->fetchColumn() == 0) {
                        $errore = "La voce selezionata non appartiene a questo bilancio.";
                    } else {
                        try {
                            $stmt3 = $pdo->prepare("CALL sp_InserisciNotaVoce(?, ?, ?, ?, ?)");
                            $stmt3->execute([$testo, $nome_voce, $username, $id_bil, $rag_soc]);
                            $messaggio = "Nota inserita sulla voce '$nome_voce' del bilancio #$id_bil.";

                            require_once "../db_mongo.php";
                            logEvento('INSERT_NOTA', "Nota inserita sulla voce '$nome_voce' del bilancio #$id_bil ($rag_soc) da $username", 0, $id_bil);

                        } catch (PDOException $e) {
                            $errore = "Errore DB: " . $e->getMessage();
                        }
                    }
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

// Bilancio selezionato via GET
$id_sel  = isset($_GET["id_bilancio"])     ? (int)$_GET["id_bilancio"]              : 0;
$rag_sel = isset($_GET["ragione_sociale"]) ? trim($_GET["ragione_sociale"]) : "";

// Voci del bilancio selezionato
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
        "SELECT n.Data, n.NomeVoce, n.id_bilancio, n.Ragione_sociale_bilancio, n.Testo
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
    <link rel="stylesheet" href="../../STYLE/style.css">
</head>
<body>
    <div class="card-full">
        <div class="card-header">
            <h1>Inserisci Nota su Voce di Bilancio</h1>
            <a href="../menu.php" class="btn-logout">← Torna al menu</a>
        </div>
    

        <?php if ($messaggio): ?><p><?= htmlspecialchars($messaggio) ?></p><?php endif; ?>
        <?php if ($errore):    ?><p><?= htmlspecialchars($errore) ?></p><?php endif; ?>

        <div class="table-container">
            <h2>1. Seleziona Bilancio Assegnato</h2>
            <?php if ($bilanci): ?>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Azienda</th>
                            <th>Stato</th>
                            <th>Azione</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bilanci as $r): ?>
                            <tr>
                                <td><code class="id-badge">#<?= htmlspecialchars($r["id"]) ?></code></td>
                                <td><strong><?= htmlspecialchars($r["Ragione_sociale_azienda"]) ?></strong></td>
                                <td>
                                    <span class="status-pill stato-default">
                                        <?= htmlspecialchars($r["Stato"]) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="inserisci_nota.php?id_bilancio=<?= urlencode($r["id"]) ?>&ragione_sociale=<?= urlencode($r["Ragione_sociale_azienda"]) ?>" 
                                    class="btn-action-small">
                                        Seleziona
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="empty-msg">Nessun bilancio assegnato.</p>
            <?php endif; ?>
        </div>

        <?php if ($id_sel > 0 && $rag_sel !== ""): ?>
            <h2>2. Inserisci Nota &mdash; Bilancio #<?= htmlspecialchars($id_sel) ?> (<?= htmlspecialchars($rag_sel) ?>)</h2>
            <form action="inserisci_nota.php" method="post">
                <input type="hidden" name="id_bilancio"     value="<?= htmlspecialchars($id_sel) ?>">
                <input type="hidden" name="ragione_sociale" value="<?= htmlspecialchars($rag_sel) ?>">
                <div class="input-group2">
                    <label>Voce contabile *</label>
                    <select name="nome_voce" required>
                        <option value="">-- seleziona voce --</option>
                        <?php foreach ($voci as $v): ?>
                            <option value="<?= htmlspecialchars($v["Nome_voce"]) ?>">
                                <?= htmlspecialchars($v["Nome_voce"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-group2">
                    <label>Testo nota *</label>
                    <textarea name="testo" rows="4" cols="50" required></textarea><br>
                </div>
                <input type="submit" name="inserisci_nota" value="Inserisci Nota" class="add-btn">
            </form>
        <?php elseif (count($bilanci) > 0): ?>
            <p><em>Clicca "Seleziona" su un bilancio per inserire una nota.</em></p>
        <?php endif; ?>

        <div class="table-container">
            <?php if ($note): ?>
                <h2>Le tue note (<?= count($note) ?>)</h2>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Voce</th>
                            <th>Bilancio</th>
                            <th>Azienda</th>
                            <th>Testo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($note as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r["Data"]) ?></td>
                                <td><strong><?= htmlspecialchars($r["NomeVoce"]) ?></strong></td>
                                <td><code class="id-badge">#<?= htmlspecialchars($r["id_bilancio"]) ?></code></td>
                                <td><?= htmlspecialchars($r["Ragione_sociale_bilancio"]) ?></td>
                                <td><?= htmlspecialchars($r["Testo"]) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="empty-msg">Nessuna nota inserita.</p>
            <?php endif; ?>
        </div>

</body>
</html>
