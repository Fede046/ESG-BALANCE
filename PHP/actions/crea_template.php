<?php
session_start();
require_once "../db.php";

// Protezione pagina: utente non autenticato viene rimandato al login.
if (!isset($_SESSION["Username"])) {
    header("Location: ../login.php");
    exit();
}

// Accesso riservato esclusivamente agli amministratori.
// La consegna prevede che il template del bilancio possa essere creato
// solo dagli utenti amministratori; qualsiasi altro ruolo viene rimandato
// al proprio menu senza messaggi di errore espliciti.
if ($_SESSION["Ruolo"] !== "amministratore") {
    header("Location: ../menu.php");
    exit();
}

$username  = $_SESSION["Username"];
$pdo       = getDB();
$messaggio = "";
$errore    = "";

if (isset($_POST["aggiungi_voce"])) {
    $nome_voce   = trim($_POST["nome_voce"]);
    $descrizione = trim($_POST["descrizione"]) ?: null;

    if ($nome_voce === "") {
        $errore = "Il nome della voce è obbligatorio.";

    // Lunghezza minima per evitare nomi troppo generici o accidentali
    } elseif (strlen($nome_voce) < 2) {
        $errore = "Il nome della voce deve avere almeno 2 caratteri.";

    // La descrizione è obbligatoria: la consegna prevede che ogni voce contabile
    // del template sia accompagnata da una descrizione testuale.
    } elseif (empty($descrizione)) {
        $errore = "La descrizione è obbligatoria.";

    } else {
        try {
            // sp_CreaVoceTemplate inserisce la voce in VOCE e la collega al template
            // condiviso tra tutte le aziende della piattaforma.
            $stmt = $pdo->prepare("CALL sp_CreaVoceTemplate(?, ?, ?)");
            $stmt->execute([$nome_voce, $descrizione, $username]);
            $messaggio = "Voce '$nome_voce' aggiunta al template.";

            require_once "../db_mongo.php";
            logEvento('ADD_VOCE', "Voce template aggiunta: '$nome_voce' da $username", $_SESSION["Username"]);

        } catch (PDOException $e) {
            // Codice 1062 = duplicate entry: il nome della voce deve essere univoco
            // come richiesto dalla consegna (es. "Ricavi vendite", "Ammortamenti").
            if ($e->errorInfo[1] == 1062) {
                $errore = "Errore: una voce con questo nome esiste già.";
            } else {
                $errore = "Errore DB: " . $e->getMessage();
            }
        }
    }
}

// Carica tutte le voci contabili già presenti nel template per mostrarle
// nella tabella riepilogativa. Il template è condiviso tra tutte le aziende,
// quindi questa lista riflette lo stato globale della piattaforma.
$voci = [];
try {
    $voci = $pdo->query(
        "SELECT Nome, Descrizione FROM VOCE ORDER BY Nome"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore lettura VOCE: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crea Template Bilancio</title>
    <link rel="stylesheet" href="../../STYLE/style.css">
</head>
<body>
    <div class="card-full">
        <div class="card-header">
            <h1>Crea Template Bilancio</h1>
            <a href="../menu.php" class="btn-logout">← Torna al menu</a>
        </div>
        <?php if ($messaggio): ?><p><?= htmlspecialchars($messaggio) ?></p><?php endif; ?>
        <?php if ($errore):    ?><p><?= htmlspecialchars($errore) ?></p><?php endif; ?>

        <h2>Aggiungi Voce Contabile</h2>
        <form action="crea_template.php" method="post">
            <div class="input-group2">
                <label>Nome voce (max 30 caratteri)</label>
                <input type="text" name="nome_voce" maxlength="30" required>
            </div>
            <div class="input-group2">
                <label>Descrizione</label>
                <input type="text" name="descrizione" maxlength="255" placeholder="Descrizione della voce contabile..." required>
            </div>
            <input type="submit" name="aggiungi_voce" value="Aggiungi Voce" class="add-btn">
        </form>

        <div class="table-container">
            <?php if ($voci): ?>
                <h2>Voci contabili presenti (<?= count($voci) ?>)</h2>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Nome Voce</th>
                            <th>Descrizione</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($voci as $r): ?>
                            <tr>
                                <td >
                                    <strong><?= htmlspecialchars($r["Nome"]) ?></strong>
                                </td>
                                <td >
                                    <?= htmlspecialchars($r["Descrizione"] ?? "—") ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="empty-msg">Nessuna voce contabile presente nel sistema.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
