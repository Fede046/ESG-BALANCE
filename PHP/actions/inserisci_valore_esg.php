<?php
session_start();
require_once "../db.php";

// Protezione pagina: utente non autenticato viene rimandato al login.
if (!isset($_SESSION["Username"])) {
    header("Location: ../login.php");
    exit();
}

// Accesso riservato esclusivamente ai responsabili aziendali.
// La consegna prevede che il responsabile inserisca i valori degli indicatori
// ESG per ciascuna voce contabile del bilancio, prima che venga avviata la revisione.
if ($_SESSION["Ruolo"] !== "responsabile") {
    header("Location: ../menu.php");
    exit();
}

$username  = $_SESSION["Username"];
$pdo       = getDB();
$messaggio = "";
$errore    = "";

if (isset($_POST["inserisci_valore"])) {
    $id_bil     = (int)$_POST["id_bilancio"];
    $rag_soc    = trim($_POST["ragione_sociale"]);
    $nome_voce  = trim($_POST["nome_voce"]);
    $nome_esg   = trim($_POST["nome_esg"]);
    $valore     = trim($_POST["valore"]);
    $fonte      = trim($_POST["fonte"] ?? '');
    $data_rilev = trim($_POST["data_rilevazione"] ?? '');

    if ($id_bil <= 0 || $rag_soc === "") {
        $errore = "Bilancio non valido.";
    } elseif ($nome_voce === "") {
        $errore = "La voce contabile è obbligatoria.";
    } elseif ($nome_esg === "") {
        $errore = "L'indicatore ESG è obbligatorio.";
    } elseif ($valore === "" || !is_numeric($valore)) {
        $errore = "Il valore deve essere un numero.";
    } elseif ($fonte === "" || strlen($fonte) < 2) {
        $errore = "La fonte è obbligatoria (almeno 2 caratteri).";

    // Data di rilevazione non può essere futura: il dato ESG deve essere già osservato
    } elseif ($data_rilev === "" || strtotime($data_rilev) > time()) {
        $errore = "La data di rilevazione è obbligatoria e non può essere futura.";
    } else {
        // Controllo preventivo sullo stato del bilancio: non si possono inserire
        // valori ESG su un bilancio già chiuso (approvato o respinto).
        $chk_stato = $pdo->prepare(
            "SELECT Stato FROM BILANCIO
             WHERE id = ? AND Ragione_sociale_azienda = ?"
        );
        $chk_stato->execute([$id_bil, $rag_soc]);
        $bilancio = $chk_stato->fetch(PDO::FETCH_ASSOC);

        if (!$bilancio) {
            $errore = "Bilancio non trovato.";
        } elseif (in_array(strtolower($bilancio['Stato']), ['approvato', 'respinto'])) {
            $errore = "Non puoi modificare un bilancio già chiuso.";
        } else {
            // Controllo di ownership: il responsabile può modificare solo i bilanci
            // delle proprie aziende. La JOIN con AZIENDA impedisce accessi non autorizzati
            // passando ID arbitrari nel form.
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM BILANCIO b
                 JOIN AZIENDA a ON b.Ragione_sociale_azienda = a.Ragione_sociale
                 WHERE b.id = ? AND b.Ragione_sociale_azienda = ?
                   AND a.Username_Responsabile_Aziendale = ?"
            );
            $stmt->execute([$id_bil, $rag_soc, $username]);
            if ($stmt->fetchColumn() == 0) {
                $errore = "Bilancio non trovato o non di tua competenza.";
            } else {
                // Verifica che la voce selezionata sia effettivamente associata al bilancio
                // in ASSOCIA_BILANCIO_VOCE, prima di tentare l'inserimento del valore ESG.
                $stmt2 = $pdo->prepare(
                    "SELECT COUNT(*) FROM ASSOCIA_BILANCIO_VOCE
                     WHERE Nome_voce = ? AND id_bilancio = ? AND Ragione_sociale_bilancio = ?"
                );
                $stmt2->execute([$nome_voce, $id_bil, $rag_soc]);
                if ($stmt2->fetchColumn() == 0) {
                    $errore = "La voce selezionata non è associata a questo bilancio.";
                } else {
                    try {
                        // sp_InserisciValoreESG esegue un INSERT OR UPDATE (UPSERT) su
                        // COLLEGA_ESG_VOCE: se il collegamento voce-indicatore esiste già,
                        // aggiorna il valore e la fonte; altrimenti lo crea.
                        $stmt3 = $pdo->prepare("CALL sp_InserisciValoreESG(?, ?, ?, ?, ?)");
                        $stmt3->execute([$nome_voce, $nome_esg, $fonte, $valore, $data_rilev]);
                        $messaggio = "Valore ESG per voce '$nome_voce' — indicatore '$nome_esg' salvato (inserito o aggiornato).";

                        require_once "../db_mongo.php";
                        logEvento('INSERT_ESG', "Valore ESG inserito/aggiornato: voce '$nome_voce', indicatore '$nome_esg' nel bilancio #$id_bil ($rag_soc) da $username", $_SESSION["Username"], $id_bil);

                    } catch (PDOException $e) {
                        $errore = "Errore DB: " . $e->getMessage();
                    }
                }
            }
        }
    }
}

// Carica solo i bilanci delle aziende del responsabile loggato tramite JOIN con AZIENDA
$bilanci = [];
try {
    $stmt = $pdo->prepare(
        "SELECT b.id, b.Ragione_sociale_azienda, b.Stato
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

// Bilancio selezionato via GET dal click su "Seleziona" nella tabella superiore
$id_sel  = isset($_GET["id_bilancio"])     ? (int)$_GET["id_bilancio"]     : 0;
$rag_sel = isset($_GET["ragione_sociale"]) ? trim($_GET["ragione_sociale"]) : "";

// Carica le voci del bilancio selezionato per popolare il select del form
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

// Carica tutti gli indicatori ESG disponibili per il select del form
$indicatori = [];
try {
    $indicatori = $pdo->query(
        "SELECT Nome, Rilevanza FROM INDICATORE_ESG ORDER BY Nome"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore lettura INDICATORE_ESG: " . $e->getMessage();
}

// Carica i valori ESG già inseriti per il bilancio selezionato tramite JOIN su
// ASSOCIA_BILANCIO_VOCE, per mostrare solo i valori delle voci di quel bilancio.
$valori = [];
if ($id_sel > 0 && $rag_sel !== "") {
    try {
        $stmt = $pdo->prepare(
            "SELECT cev.NomeVoce, cev.NomeEsg, cev.Valore, cev.Fonte, cev.Data
             FROM COLLEGA_ESG_VOCE cev
             JOIN ASSOCIA_BILANCIO_VOCE abv
               ON cev.NomeVoce = abv.Nome_voce
              AND abv.id_bilancio = ?
              AND abv.Ragione_sociale_bilancio = ?
             ORDER BY cev.Data DESC"
        );
        $stmt->execute([$id_sel, $rag_sel]);
        $valori = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errore = "Errore lettura valori ESG: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inserisci Valore ESG</title>
    <link rel="stylesheet" href="../../STYLE/style.css">
</head>
<body>
    <div class="card-full">
        <div class="card-header">
            <h1>Inserisci Valore Indicatore ESG per Voce</h1>
            <a href="../menu.php" class="btn-logout">← Torna al menu</a>
        </div>

        <?php if ($messaggio): ?><p><?= htmlspecialchars($messaggio) ?></p><?php endif; ?>
        <?php if ($errore):    ?><p><?= htmlspecialchars($errore) ?></p><?php endif; ?>

        <div class="table-container">
            <h2>1. Seleziona Bilancio</h2>
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
                                    <a href="inserisci_valore_esg.php?id_bilancio=<?= urlencode($r["id"]) ?>&ragione_sociale=<?= urlencode($r["Ragione_sociale_azienda"]) ?>"
                                    class="btn-action-small">
                                        Seleziona
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="empty-msg">Nessun bilancio disponibile.</p>
            <?php endif; ?>
        </div>

        <?php if ($id_sel > 0 && $rag_sel !== ""): ?>
            <h2>2. Inserisci Valore ESG &mdash; Bilancio #<?= htmlspecialchars($id_sel) ?> (<?= htmlspecialchars($rag_sel) ?>)</h2>

            <?php if (empty($voci)): ?>
                <p>Nessuna voce associata a questo bilancio. Aggiungile prima dalla pagina "Crea Bilancio".</p>
            <?php else: ?>
                <form action="inserisci_valore_esg.php" method="post">
                    <input type="hidden" name="id_bilancio"     value="<?= htmlspecialchars($id_sel) ?>">
                    <input type="hidden" name="ragione_sociale" value="<?= htmlspecialchars($rag_sel) ?>">
                    <div class="input-group2">
                        <label>Voce Contabile </label>
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
                        <label>Indicatore ESG </label>
                        <select name="nome_esg" required>
                            <option value="">-- seleziona indicatore --</option>
                            <?php foreach ($indicatori as $i): ?>
                                <option value="<?= htmlspecialchars($i["Nome"]) ?>">
                                    <?= htmlspecialchars($i["Nome"]) ?> (rilevanza: <?= $i["Rilevanza"] ?? "—" ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-group2">
                        <label>Valore </label>
                        <input type="number" step="0.01" name="valore" required>
                    </div>
                    <div class="input-group2">
                        <label>Fonte (max 30 caratteri)</label>
                        <input type="text" name="fonte" maxlength="30" required>
                    </div>
                    <div class="input-group2">
                        <label>Data di rilevazione </label>
                        <input type="date" name="data_rilevazione"
                               max="<?= date('Y-m-d') ?>" required>
                    </div>
                    <input type="submit" name="inserisci_valore" value="Inserisci Valore" class="add-btn">
                </form>
            <?php endif; ?>

            <div class="table-container">
                <?php if ($valori): ?>
                    <h2>Valori ESG già inseriti per questo bilancio (<?= count($valori) ?>)</h2>
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Voce</th>
                                <th>Indicatore ESG</th>
                                <th>Valore</th>
                                <th>Fonte</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($valori as $r): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($r["NomeVoce"]) ?></strong></td>
                                    <td><strong><?= htmlspecialchars($r["NomeEsg"]) ?></strong></td>
                                    <td><span class="badge"><?= htmlspecialchars($r["Valore"]) ?></span></td>
                                    <td><?= htmlspecialchars($r["Fonte"] ?? "—") ?></td>
                                    <td><?= htmlspecialchars($r["Data"]) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="empty-msg">Nessun valore ESG inserito per questo bilancio.</p>
                <?php endif; ?>
            </div>

        <?php elseif (count($bilanci) > 0): ?>
            <p class="empty-msg"><em>Clicca "Seleziona" su un bilancio per inserire i valori ESG.</em></p>
        <?php endif; ?>
    </div>
</body>
</html>
