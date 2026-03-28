<?php
session_start();
require_once "../db.php";

// Protezione pagina: utente non autenticato viene rimandato al login.
if (!isset($_SESSION["Username"])) {
    header("Location: ../login.php");
    exit();
}

// Accesso riservato esclusivamente agli amministratori.
// La consegna prevede che la lista degli indicatori ESG sia popolata
// solo dagli utenti amministratori.
if ($_SESSION["Ruolo"] !== "amministratore") {
    header("Location: ../menu.php");
    exit();
}

$username  = $_SESSION["Username"];
$pdo       = getDB();
$messaggio = "";
$errore    = "";

if (isset($_POST["aggiungi_indicatore"])) {
    $nome     = trim($_POST["nome_indicatore"]);

    // Immagine opzionale: se non caricata viene usata quella di default.
    // La consegna prevede un'immagine rappresentativa per ogni indicatore.
    $immagine = 'uploads/indicatori/default.png';

    if (isset($_FILES['immagine']) && $_FILES['immagine']['error'] === UPLOAD_ERR_OK) {
        $ext        = strtolower(pathinfo($_FILES['immagine']['name'], PATHINFO_EXTENSION));
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        // Whitelist delle estensioni consentite per evitare upload di file pericolosi
        if (!in_array($ext, $allowedExt)) {
            $errore = "Formato immagine non supportato.";
        } else {
            $uploadDir = '../../uploads/indicatori/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            // Nome file basato sul nome dell'indicatore, sanificato per evitare
            // caratteri speciali nel path del filesystem
            $filename = 'img_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $nome) . '.' . $ext;
            if (move_uploaded_file($_FILES['immagine']['tmp_name'], $uploadDir . $filename)) {
                $immagine = 'uploads/indicatori/' . $filename;
            } else {
                $errore = "Errore nel salvataggio dell'immagine.";
            }
        }
    }

    $rilevanza = ($_POST["rilevanza"] !== "" && is_numeric($_POST["rilevanza"]))
                 ? (int)$_POST["rilevanza"]
                 : null;

    $tipo      = $_POST["tipo"] ?? "";
    $cod_norm  = trim($_POST["cod_norm"]  ?? "") ?: null;
    $ambito    = trim($_POST["ambito"]    ?? "") ?: null;
    $frequenza = ($_POST["frequenza"] ?? "") !== "" ? (int)$_POST["frequenza"] : null;

    if ($nome === "") {
        $errore = "Il nome dell'indicatore è obbligatorio.";

    } elseif ($rilevanza === null || !is_numeric($_POST["rilevanza"])) {
        // Rilevanza tra 0 e 10 come richiesto dalla consegna
        $errore = "La rilevanza è obbligatoria (valore tra 0 e 10).";

    } elseif ($rilevanza < 0 || $rilevanza > 10) {
        $errore = "La rilevanza deve essere tra 0 e 10.";

    } elseif ($tipo === "ambientale" && $cod_norm === null) {
        // Gli indicatori ambientali hanno un campo "codice normativa di rilevamento"
        // obbligatorio come specificato nella consegna
        $errore = "Il codice normativa è obbligatorio per indicatori ambientali.";

    } elseif ($tipo === "sociale" && ($ambito === null || $frequenza === null)) {
        // Gli indicatori sociali richiedono ambito sociale e frequenza di rilevazione,
        // entrambi campi aggiuntivi previsti dalla consegna
        $errore = "Ambito e frequenza sono obbligatori per indicatori sociali.";

    } elseif ($tipo === "sociale" && $frequenza !== null && $frequenza <= 0) {
        $errore = "La frequenza deve essere maggiore di 0 giorni.";

    } else {
        try {
            // sp_PopolaIndicatoreESG gestisce l'inserimento in INDICATORE_ESG e,
            // in base al tipo, anche in ESG_AMBIENTALE o ESG_INDICATORE_SOCIALE.
            // La consegna prevede che possano esistere indicatori senza categoria (tipo null = generico).
            $stmt = $pdo->prepare("CALL sp_PopolaIndicatoreESG(?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $nome, $username, $immagine, $rilevanza,
                $tipo ?: null, $cod_norm, $ambito, $frequenza
            ]);
            $messaggio = "Indicatore '$nome' aggiunto" . ($tipo ? " (tipo: $tipo)" : " (generico)") . ".";

            require_once "../db_mongo.php";
            logEvento('CREATE_INDICATORE', "Indicatore ESG creato: '$nome' (tipo: " . ($tipo ?: 'generico') . ") da $username", 0, 0);

        } catch (PDOException $e) {
            // Codice 1062 = duplicate entry: il nome dell'indicatore deve essere univoco
            if ($e->errorInfo[1] == 1062) {
                $errore = "Errore: un indicatore con questo nome esiste già.";
            } else {
                $errore = "Errore DB: " . $e->getMessage();
            }
        }
    }
}

// Carica tutti gli indicatori esistenti con il loro tipo, determinato tramite
// LEFT JOIN sulle tabelle di specializzazione ESG_AMBIENTALE e ESG_INDICATORE_SOCIALE.
// Gli indicatori senza riga in nessuna delle due tabelle vengono classificati come 'generico'.
$indicatori = [];
try {
    $indicatori = $pdo->query(
        "SELECT i.Nome, i.Rilevanza,
                CASE
                    WHEN a.NomeEsg IS NOT NULL THEN 'ambientale'
                    WHEN s.NomeEsg IS NOT NULL THEN 'sociale'
                    ELSE 'generico'
                END AS Tipo
         FROM INDICATORE_ESG i
         LEFT JOIN ESG_AMBIENTALE a ON i.Nome = a.NomeEsg
         LEFT JOIN ESG_INDICATORE_SOCIALE s ON i.Nome = s.NomeEsg
         ORDER BY i.Nome"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore lettura: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aggiungi Indicatore ESG</title>
    <link rel="stylesheet" href="../../STYLE/style.css">
    <script>
    function aggiornaFormTipo() {
        var tipo = document.getElementById("tipo").value;
        document.getElementById("form_ambientale").style.display = (tipo === "ambientale") ? "block" : "none";
        document.getElementById("form_sociale").style.display    = (tipo === "sociale")    ? "block" : "none";
    }
    </script>
</head>
<body>
    <div class="card-full">
        <div class="card-header">
            <h1>Aggiungi Indicatore ESG</h1>
            <a href="../menu.php" class="btn-logout">← Torna al menu</a>
        </div>
        <?php if ($messaggio): ?><p><?= htmlspecialchars($messaggio) ?></p><?php endif; ?>
        <?php if ($errore):    ?><p><?= htmlspecialchars($errore) ?></p><?php endif; ?>

        <form action="aggiungi_indicatore.php" method="post" enctype="multipart/form-data">
            <div class="input-group2">
                <label>Nome (max 30 caratteri)</label>
                <input type="text" name="nome_indicatore" maxlength="30" required>
            </div>
            <div class="input-group2">
                <label>Rilevanza (0–10)</label>
                <input type="number" name="rilevanza" min="0" max="10" required>
            </div>
            <div class="input-group2">
                <label>Immagine</label>
                <input type="file" name="immagine" accept="image/*">
            </div>
            <div class="input-group2">
                <label>Tipo indicatore</label>
                <select name="tipo" id="tipo" onchange="aggiornaFormTipo()">
                    <option value="">-- generico (nessuna categoria) --</option>
                    <option value="ambientale">Ambientale</option>
                    <option value="sociale">Sociale</option>
                </select>
            </div>
            <div class="input-group2">
                <div id="form_ambientale" style="display:none">
                    <strong>Dati ambientale</strong>
                    <label>Codice normativa di rilevamento</label>
                    <input type="text" name="cod_norm" maxlength="30">
                </div>
            </div>
            <div class="input-group2">
                <div id="form_sociale" style="display:none">
                    <strong>Dati sociale</strong><br>
                    <label>Ambito sociale di riferimento</label>
                    <input type="text" name="ambito" maxlength="30"><br><br>
                    <label>Frequenza di rilevazione (giorni)</label>
                    <input type="number" name="frequenza" min="1">
                </div>
            </div>
            <input type="submit" name="aggiungi_indicatore" value="Aggiungi Indicatore" class="add-btn">
        </form>

        <?php if ($indicatori): ?>
            <div class="table-container">
                <h2>Indicatori presenti (<?= count($indicatori) ?>)</h2>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Rilevanza</th>
                            <th>Tipo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($indicatori as $r): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r["Nome"]) ?></strong></td>
                                <td><span class="badge"><?= $r["Rilevanza"] ?? "—" ?></span></td>
                                <td><span class="type-tag <?= strtolower($r["Tipo"]) ?>"><?= htmlspecialchars($r["Tipo"] ?: 'Generico') ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="empty-msg">Nessun indicatore presente nel sistema.</p>
        <?php endif; ?>

    </div>
</body>
</html>
