<?php
session_start();
require_once "../db.php";

// Protezione pagina: utente non autenticato viene rimandato al login.
if (!isset($_SESSION["Username"])) {
    header("Location: ../login.php");
    exit();
}

// Accesso riservato esclusivamente agli amministratori.
// La consegna prevede che l'associazione di un revisore ESG a un bilancio
// sia un'operazione riservata solo agli amministratori di piattaforma.
if ($_SESSION["Ruolo"] !== "amministratore") {
    header("Location: ../menu.php");
    exit();
}

$pdo       = getDB();
$messaggio = "";
$errore    = "";

if (isset($_POST["associa_revisore"])) {
    $id_bil  = (int)$_POST["id_bilancio"];
    $rag_soc = trim($_POST["ragione_sociale"]);
    $rev     = trim($_POST["username_revisore"]);

    if ($id_bil <= 0 || $rag_soc === "" || $rev === "") {
        $errore = "Tutti i campi sono obbligatori.";
    } else {
        try {
            // Controllo preventivo sullo stato del bilancio: non si può assegnare
            // un revisore a un bilancio già chiuso (approvato o respinto).
            // Senza questo controllo la SP procederebbe ugualmente, portando
            // il bilancio in stato "in revisione" anche se già concluso.
            $chk = $pdo->prepare(
                "SELECT Stato FROM BILANCIO
                 WHERE id = ? AND Ragione_sociale_azienda = ?"
            );
            $chk->execute([$id_bil, $rag_soc]);
            $bilancio = $chk->fetch(PDO::FETCH_ASSOC);

            if (!$bilancio) {
                $errore = "Bilancio non trovato.";
            } elseif (in_array(strtolower($bilancio['Stato']), ['approvato', 'respinto'])) {
                $errore = "Non puoi assegnare un revisore a un bilancio già chiuso.";
            } else {
                // sp_AssociaRevisore inserisce la riga in REVISIONE e attiva il trigger
                // che porta automaticamente lo stato del bilancio a "in revisione",
                // come richiesto dalla consegna.
                $stmt = $pdo->prepare("CALL sp_AssociaRevisore(?, ?, ?)");
                $stmt->execute([$rev, $id_bil, $rag_soc]);
                $messaggio = "Revisore '$rev' associato al bilancio #$id_bil ($rag_soc).";

                require_once "../db_mongo.php";
                logEvento('ASSIGN_REVISORE', "Revisore '$rev' assegnato al bilancio #$id_bil ($rag_soc)", 0, $id_bil);
                logEvento('CREATE_REVISIONE', "Revisione avviata sul bilancio #$id_bil ($rag_soc)", 0, $id_bil);
            }

        } catch (PDOException $e) {
            // Codice 1062 = duplicate entry: il revisore è già assegnato a questo bilancio
            if ($e->errorInfo[1] == 1062) {
                $errore = "Errore: il revisore è già assegnato a questo bilancio.";
            } else {
                $errore = "Errore DB: " . $e->getMessage();
            }
        }
    }
}

// Carica tutti i revisori ESG registrati per popolare il select del form
$revisori = [];
try {
    $revisori = $pdo->query(
        "SELECT Username FROM REVISORE_ESG ORDER BY Username"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore lettura REVISORE_ESG: " . $e->getMessage();
}

// Carica gli ultimi 50 bilanci per popolare il select e la tabella riepilogativa.
// Il limite a 50 evita di sovraccaricare il form in caso di molti bilanci presenti.
$bilanci = [];
try {
    $bilanci = $pdo->query(
        "SELECT id, Ragione_sociale_azienda, Stato FROM BILANCIO ORDER BY id DESC LIMIT 50"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore lettura BILANCIO: " . $e->getMessage();
}


// Carica tutti i revisori assegnati ai bilanci (ultimi 50)
$revisori_assegnati = [];
try {
    $ids = array_column($bilanci, 'id');
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare(
        "SELECT id_bilancio, Username_Revisore_ESG
        FROM VALUTA_REVISORE_BILANCIO
        WHERE id_bilancio IN ($placeholders)
        ORDER BY Username_Revisore_ESG"
        );
        $stmt->execute($ids);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $revisori_assegnati[$row['id_bilancio']][] = $row['Username_Revisore_ESG'];
        }
    }
} catch (PDOException $e) {
    $errore = "Errore lettura REVISIONE: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Associa Revisore a Bilancio</title>
    <link rel="stylesheet" href="../../STYLE/style.css">
</head>
<body>
    <div class="card-full">
        <div class="card-header">
            <h1>Associa Revisore ESG a Bilancio</h1>
            <a href="../menu.php" class="btn-logout">← Torna al menu</a>
        </div>
        <?php if ($messaggio): ?><p><?= htmlspecialchars($messaggio) ?></p><?php endif; ?>
        <?php if ($errore):    ?><p><?= htmlspecialchars($errore) ?></p><?php endif; ?>

        <form action="associa_revisore.php" method="post">
            <div class="input-group2">
                <label>Bilancio * </label>
                <select name="id_bilancio" required onchange="sincronizzaRagioneSociale(this)">
                    <option value="">-- seleziona bilancio --</option>
                    <?php foreach ($bilanci as $b): ?>
                        <option value="<?= htmlspecialchars($b["id"]) ?>"
                                data-ragione="<?= htmlspecialchars($b["Ragione_sociale_azienda"]) ?>">
                            #<?= htmlspecialchars($b["id"]) ?> — <?= htmlspecialchars($b["Ragione_sociale_azienda"]) ?> (<?= htmlspecialchars($b["Stato"]) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="ragione_sociale" id="ragione_sociale_hidden">
            </div>
            <div class="input-group2">
                <label>Username Revisore ESG *</label>
                <select name="username_revisore" required>
                    <option value="">-- seleziona revisore --</option>
                    <?php foreach ($revisori as $r): ?>
                        <option value="<?= htmlspecialchars($r["Username"]) ?>">
                            <?= htmlspecialchars($r["Username"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <input type="submit" name="associa_revisore" value="Associa Revisore" class="add-btn">
        </form>

        <script>
        function sincronizzaRagioneSociale(sel) {
            var opt = sel.options[sel.selectedIndex];
            document.getElementById('ragione_sociale_hidden').value = opt.dataset.ragione || '';
        }
        </script>

        <div class="table-container">
        <?php if ($bilanci): ?>
            <h2>Ultimi 50 bilanci</h2>
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Azienda</th>
                        <th>Stato</th>
                        <th>Revisori assegnati</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bilanci as $r): ?>
                        <tr>
                            <td><code>#<?= htmlspecialchars($r["id"]) ?></code></td>
                            <td><strong><?= htmlspecialchars($r["Ragione_sociale_azienda"]) ?></strong></td>
                            <td>
                                <?php 
                                    $stato_classe = 'stato-default';
                                    $stato_testo = strtolower($r["Stato"]);
                                    if (strpos($stato_testo, 'approvato') !== false) $stato_classe = 'stato-success';
                                    elseif (strpos($stato_testo, 'revisione') !== false) $stato_classe = 'stato-warning';
                                    elseif (strpos($stato_testo, 'bozza') !== false) $stato_classe = 'stato-info';
                                ?>
                                <span class="status-pill <?= $stato_classe ?>">
                                    <?= htmlspecialchars($r["Stato"]) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($revisori_assegnati[$r["id"]])): ?>
                                    <?php foreach ($revisori_assegnati[$r["id"]] as $rev): ?>
                                        <span class="status-pill stato-info"><?= htmlspecialchars($rev) ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span style="color: var(--text-muted, #aaa); font-style: italic;">Nessuno</span>
                                <?php endif; ?>
                            </td>                            
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="empty-msg">Nessun bilancio presente nel sistema.</p>
        <?php endif; ?>
    </div>
    </div>
</body>
</html>
