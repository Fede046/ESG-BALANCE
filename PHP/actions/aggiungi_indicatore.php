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

$username  = $_SESSION["Username"];
$pdo       = getDB();
$messaggio = "";
$errore    = "";

if (isset($_POST["aggiungi_indicatore"])) {
    $nome      = trim($_POST["nome_indicatore"]);
    $rilevanza = ($_POST["rilevanza"] !== "") ? (int)$_POST["rilevanza"] : null;
    $immagine  = trim($_POST["immagine"]) ?: null;
    $tipo      = $_POST["tipo"] ?? "";
    $cod_norm  = trim($_POST["cod_norm"]  ?? "") ?: null;
    $ambito    = trim($_POST["ambito"]    ?? "") ?: null;
    $frequenza = ($_POST["frequenza"] ?? "") !== "" ? (int)$_POST["frequenza"] : null;

    if ($nome === "") {
        $errore = "Il nome dell'indicatore è obbligatorio.";
    } elseif ($rilevanza !== null && ($rilevanza < 0 || $rilevanza > 10)) {
        $errore = "La rilevanza deve essere tra 0 e 10.";
    } elseif ($tipo === "ambientale" && $cod_norm === null) {
        $errore = "Il codice normativa è obbligatorio per indicatori ambientali.";
    } elseif ($tipo === "sociale" && ($ambito === null || $frequenza === null)) {
        $errore = "Ambito e frequenza sono obbligatori per indicatori sociali.";
    } else {
        try {
            $stmt = $pdo->prepare("CALL sp_PopolaIndicatoreESG(?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $nome,
                $username,
                $immagine,
                $rilevanza,
                $tipo ?: null,
                $cod_norm,
                $ambito,
                $frequenza
            ]);
            $messaggio = "Indicatore '$nome' aggiunto" . ($tipo ? " (tipo: $tipo)" : " (generico)") . ".";
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $errore = "Errore: un indicatore con questo nome esiste già.";
            } else {
                $errore = "Errore DB: " . $e->getMessage();
            }
        }
    }
}

// Lettura indicatori esistenti con tipo
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
    <script>
    function aggiornaFormTipo() {
        var tipo = document.getElementById("tipo").value;
        document.getElementById("form_ambientale").style.display = (tipo === "ambientale") ? "block" : "none";
        document.getElementById("form_sociale").style.display    = (tipo === "sociale")    ? "block" : "none";
    }
    </script>
</head>
<body>
    <h1>Aggiungi Indicatore ESG</h1>

    <?php if ($messaggio): ?><p><?= htmlspecialchars($messaggio) ?></p><?php endif; ?>
    <?php if ($errore):    ?><p><?= htmlspecialchars($errore) ?></p><?php endif; ?>

    <form action="aggiungi_indicatore.php" method="post">
        <label>Nome * (max 30 caratteri)</label><br>
        <input type="text" name="nome_indicatore" maxlength="30" required><br>

        <label>Rilevanza (0–10, opzionale)</label><br>
        <input type="number" name="rilevanza" min="0" max="10"><br>

        <label>Immagine (path al file, opzionale)</label><br>
        <input type="text" name="immagine" maxlength="500"><br>

        <label>Tipo indicatore</label><br>
        <select name="tipo" id="tipo" onchange="aggiornaFormTipo()">
            <option value="">-- generico (nessuna categoria) --</option>
            <option value="ambientale">Ambientale</option>
            <option value="sociale">Sociale</option>
        </select><br><br>

        <div id="form_ambientale" style="display:none">
            <strong>Dati ambientale</strong><br>
            <label>Codice normativa di rilevamento *</label><br>
            <input type="text" name="cod_norm" maxlength="30"><br>
        </div>

        <div id="form_sociale" style="display:none">
            <strong>Dati sociale</strong><br>
            <label>Ambito sociale di riferimento *</label><br>
            <input type="text" name="ambito" maxlength="30"><br>
            <label>Frequenza di rilevazione (giorni) *</label><br>
            <input type="number" name="frequenza" min="1"><br>
        </div>

        <input type="submit" name="aggiungi_indicatore" value="Aggiungi Indicatore">
    </form>

    <?php if ($indicatori): ?>
        <h2>Indicatori presenti (<?= count($indicatori) ?>)</h2>
        <table border="1">
            <tr><th>Nome</th><th>Rilevanza</th><th>Tipo</th></tr>
            <?php foreach ($indicatori as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r["Nome"]) ?></td>
                    <td><?= $r["Rilevanza"] ?? "—" ?></td>
                    <td><?= htmlspecialchars($r["Tipo"]) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Nessun indicatore presente.</p>
    <?php endif; ?>

    <br><a href="../menu.php">← Torna al menu</a>
</body>
</html>
