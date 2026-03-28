<?php
session_start();
require_once "../db.php";

// Protezione pagina: utente non autenticato viene rimandato al login.
if (!isset($_SESSION["Username"])) {
    header("Location: ../login.php");
    exit();
}

// Accesso riservato esclusivamente ai revisori ESG.
// La consegna prevede che ogni revisore assegnato a un bilancio esprima
// un giudizio complessivo: approvazione, approvazione con rilievi o respingimento.
if ($_SESSION["Ruolo"] !== "revisore") {
    header("Location: ../menu.php");
    exit();
}

$username  = $_SESSION["Username"];
$pdo       = getDB();
$messaggio = "";
$errore    = "";

if (isset($_POST["inserisci_giudizio"])) {
    $id_bil  = (int)$_POST["id_bilancio"];
    $rag_soc = trim($_POST["ragione_sociale"]);
    $esito   = trim($_POST["esito"]);
    $rilievi = trim($_POST["rilievi"]) ?: null;

    if ($id_bil <= 0 || $rag_soc === "") {
        $errore = "Bilancio non valido.";
    } elseif ($esito === "") {
        $errore = "L'esito è obbligatorio.";

    // Whitelist esiti: i tre valori corrispondono agli ENUM definiti nella
    // tabella GIUDIZIO dello schema relazionale della consegna.
    } elseif (!in_array($esito, ['approvazione', 'approvazione con rilievi', 'respingimento'])) {
        $errore = "Esito non valido.";
    } elseif ($rilievi !== null && strlen($rilievi) < 5) {
        $errore = "I rilievi devono avere almeno 5 caratteri.";
    } elseif ($rilievi !== null && strlen($rilievi) > 500) {
        $errore = "I rilievi non possono superare 500 caratteri.";
    } else {
        try {
            // Controllo preventivo sullo stato del bilancio: non si può esprimere
            // un giudizio su un bilancio già chiuso (approvato o respinto).
            $chk = $pdo->prepare(
                "SELECT Stato FROM BILANCIO
                 WHERE id = ? AND Ragione_sociale_azienda = ?"
            );
            $chk->execute([$id_bil, $rag_soc]);
            $bilancio = $chk->fetch(PDO::FETCH_ASSOC);

            if (!$bilancio) {
                $errore = "Bilancio non trovato.";
            } elseif (in_array(strtolower($bilancio['Stato']), ['approvato', 'respinto'])) {
                $errore = "Il bilancio è già chiuso, non puoi inserire un nuovo giudizio.";
            } else {
                // Controllo unicità giudizio: ogni revisore può esprimere al massimo
                // un giudizio per bilancio, come previsto dalla chiave primaria di GIUDIZIO.
                $chk2 = $pdo->prepare(
                    "SELECT 1 FROM GIUDIZIO
                     WHERE Username = ? AND id_bilancio = ? AND Ragione_sociale_bilancio = ?"
                );
                $chk2->execute([$username, $id_bil, $rag_soc]);

                if ($chk2->fetch()) {
                    $errore = "Hai già inserito un giudizio per questo bilancio.";
                } else {
                    // sp_InserisciGiudizioComplessivo inserisce il giudizio e, tramite
                    // trigger, aggiorna lo stato del bilancio se tutti i revisori assegnati
                    // hanno espresso il proprio giudizio (logica di consenso della consegna).
                    $stmt = $pdo->prepare("CALL sp_InserisciGiudizioComplessivo(?, ?, ?, ?, ?)");
                    $stmt->execute([$esito, $rilievi, $username, $id_bil, $rag_soc]);
                    $messaggio = "Giudizio inserito sul bilancio #$id_bil ($rag_soc).";

                    require_once "../db_mongo.php";
                    logEvento('INSERT_GIUDIZIO', "Giudizio '$esito' inserito sul bilancio #$id_bil ($rag_soc) da $username", 0, $id_bil);

                    // Rileva l'eventuale cambio di stato prodotto dal trigger
                    // per loggare l'evento di chiusura del bilancio su MongoDB.
                    $stmt_stato = $pdo->prepare(
                        "SELECT Stato FROM BILANCIO WHERE id = ? AND Ragione_sociale_azienda = ?"
                    );
                    $stmt_stato->execute([$id_bil, $rag_soc]);
                    $stato_attuale = $stmt_stato->fetchColumn();

                    if ($stato_attuale === 'approvato') {
                        logEvento('APPROVE_BILANCIO', "Bilancio #$id_bil ($rag_soc) approvato dopo tutti i giudizi", 0, $id_bil);
                    } elseif ($stato_attuale === 'respinto') {
                        logEvento('REJECT_BILANCIO', "Bilancio #$id_bil ($rag_soc) respinto dopo tutti i giudizi", 0, $id_bil);
                    }
                }
            }

        } catch (PDOException $e) {
            $errore = "Errore DB: " . $e->getMessage();
        }
    }
}

// Carica solo i bilanci assegnati al revisore loggato tramite JOIN su
// VALUTA_REVISORE_BILANCIO, che registra le assegnazioni fatte dall'amministratore.
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

// Carica i giudizi già espressi dal revisore loggato per la tabella riepilogativa
$giudizi = [];
try {
    $stmt = $pdo->prepare(
        "SELECT g.Data, g.id_bilancio, g.Ragione_sociale_bilancio, g.Esito, g.Rilievi
         FROM GIUDIZIO g
         WHERE g.Username = ?
         ORDER BY g.Data DESC"
    );
    $stmt->execute([$username]);
    $giudizi = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore lettura giudizi: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inserisci Giudizio</title>
    <link rel="stylesheet" href="../../STYLE/style.css">
</head>
<body>
    <div class="card-full">
        <div class="card-header">
            <h1>Inserisci Giudizio su Bilancio</h1>
            <a href="../menu.php" class="btn-logout">← Torna al menu</a>
        </div>
        <?php if ($messaggio): ?><p><?= htmlspecialchars($messaggio) ?></p><?php endif; ?>
        <?php if ($errore):    ?><p><?= htmlspecialchars($errore) ?></p><?php endif; ?>

        <form action="inserisci_giudizio.php" method="post">
            <div class="input-group2">
                <label>Bilancio assegnato</label>
                <select name="id_bilancio" required onchange="sincronizzaRagioneSociale(this)">
                    <option value="">-- seleziona bilancio --</option>
                    <?php foreach ($bilanci as $b): ?>
                        <option value="<?= htmlspecialchars($b["id"]) ?>"
                                data-ragione="<?= htmlspecialchars($b["Ragione_sociale_azienda"]) ?>">
                            #<?= htmlspecialchars($b["id"]) ?> — <?= htmlspecialchars($b["Ragione_sociale_azienda"]) ?> (<?= htmlspecialchars($b["Stato"]) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <input type="hidden" name="ragione_sociale" id="ragione_sociale_hidden">
            <div class="input-group2">
                <label>Esito</label>
                <select name="esito" required>
                    <option value="">-- seleziona esito --</option>
                    <option value="approvazione">Approvazione</option>
                    <option value="approvazione con rilievi">Approvazione con rilievi</option>
                    <option value="respingimento">Respingimento</option>
                </select>
            </div>
            <div class="input-group2">
                <label>Rilievi (opzionale, max 500 caratteri)</label>
                <textarea name="rilievi" rows="4" cols="50" maxlength="500"></textarea>
            </div>
            <input type="submit" name="inserisci_giudizio" value="Inserisci Giudizio" class="add-btn">
        </form>

        <script>
        function sincronizzaRagioneSociale(sel) {
            var opt = sel.options[sel.selectedIndex];
            document.getElementById('ragione_sociale_hidden').value = opt.dataset.ragione || '';
        }
        </script>

        <div class="table-container">
            <?php if ($bilanci): ?>
                <h2>Bilanci assegnati a te</h2>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Azienda</th>
                            <th>Stato</th>
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
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="empty-msg">Nessun bilancio assegnato.</p>
            <?php endif; ?>
        </div>

        <div class="table-container">
            <?php if ($giudizi): ?>
                <h2>I tuoi giudizi (<?= count($giudizi) ?>)</h2>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>ID Bilancio</th>
                            <th>Azienda</th>
                            <th>Esito</th>
                            <th>Rilievi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($giudizi as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r["Data"]) ?></td>
                                <td><code class="id-badge">#<?= htmlspecialchars($r["id_bilancio"]) ?></code></td>
                                <td><strong><?= htmlspecialchars($r["Ragione_sociale_bilancio"]) ?></strong></td>
                                <td>
                                    <span class="status-pill <?= (strtolower($r['Esito']) == 'approvazione') ? 'stato-success' : (strtolower($r['Esito']) == 'approvazione con rilievi' ? 'stato-warning' : 'stato-danger') ?>">
                                        <?= htmlspecialchars($r["Esito"]) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($r["Rilievi"] ?? "—") ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="empty-msg">Nessun giudizio inserito.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
