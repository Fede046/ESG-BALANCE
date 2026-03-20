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

if (isset($_POST["aggiungi_voce"])) {
    $nome_voce   = trim($_POST["nome_voce"]);
    $descrizione = trim($_POST["descrizione"]) ?: null;

    if ($nome_voce === "") {
        $errore = "Il nome della voce è obbligatorio.";
    } else {
        try {
            $pdo->prepare(
                "INSERT INTO VOCE (Nome, Descrizione) VALUES (?, ?)"
            )->execute([$nome_voce, $descrizione]);
            $messaggio = "Voce '$nome_voce' aggiunta al template.";

            require_once "../db_mongo.php";
            logEvento(
                'ADD_VOCE',
                "New template voice added: $nome_voce",
                0, 0
            );
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $errore = "Errore: una voce con questo nome esiste già.";
            } else {
                $errore = "Errore DB: " . $e->getMessage();
            }
        }
    }
}

if (isset($_POST["crea_template"])) {
    $nome_tpl = trim($_POST["nome_template"]);
    $anno_tpl = (int)$_POST["anno_template"];

    if ($nome_tpl === "" || $anno_tpl < 2000) {
        $errore = "Nome e anno obbligatori (anno ≥ 2000).";
    } else {
        try {
            $pdo->prepare(
                "INSERT INTO TEMPLATE_BILANCIO (Nome, Anno, Username_Amministratore)
                 VALUES (?, ?, ?)"
            )->execute([$nome_tpl, $anno_tpl, $username]);
            $messaggio = "Template '$nome_tpl' ($anno_tpl) creato.";

            require_once "../db_mongo.php";
            logEvento(
                'CREATE_TEMPLATE',
                "New billing template created: $nome_tpl ($anno_tpl)",
                0, 0
            );
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $errore = "Errore: un template con questo nome e anno esiste già.";
            } else {
                $errore = "Errore DB: " . $e->getMessage();
            }
        }
    }
}

$voci = [];
try {
    $voci = $pdo->query(
        "SELECT Nome, Descrizione FROM VOCE ORDER BY Nome"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore lettura VOCE: " . $e->getMessage();
}

$templates = [];
try {
    $templates = $pdo->query(
        "SELECT Nome, Anno FROM TEMPLATE_BILANCIO ORDER BY Anno DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore lettura TEMPLATE_BILANCIO: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crea Template Bilancio</title>
</head>
<body>
    <h1>Crea Template Bilancio</h1>

    <?php if ($messaggio): ?><p style="color:green"><?= htmlspecialchars($messaggio) ?></p><?php endif; ?>
    <?php if ($errore):    ?><p style="color:red"><?= htmlspecialchars($errore) ?></p><?php endif; ?>

    <h2>Nuovo Template</h2>
    <form action="crea_template.php" method="post">
        <label>Nome template * (max 30 caratteri)</label><br>
        <input type="text" name="nome_template" maxlength="30" required><br>

        <label>Anno *</label><br>
        <input type="number" name="anno_template" min="2000" max="2100" required><br>

        <input type="submit" name="crea_template" value="Crea Template">
    </form>

    <?php if ($templates): ?>
        <h2>Template esistenti</h2>
        <table border="1">
            <tr><th>Nome</th><th>Anno</th></tr>
            <?php foreach ($templates as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r["Nome"]) ?></td>
                    <td><?= htmlspecialchars($r["Anno"]) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Nessun template presente.</p>
    <?php endif; ?>

    <h2>Aggiungi Voce Contabile</h2>
    <form action="crea_template.php" method="post">
        <label>Nome voce * (max 30 caratteri)</label><br>
        <input type="text" name="nome_voce" maxlength="30" required><br>

        <label>Descrizione (opzionale)</label><br>
        <input type="text" name="descrizione" maxlength="500"><br>

        <input type="submit" name="aggiungi_voce" value="Aggiungi Voce">
    </form>

    <?php if ($voci): ?>
        <h2>Voci contabili presenti (<?= count($voci) ?>)</h2>
        <table border="1">
            <tr><th>Nome</th><th>Descrizione</th></tr>
            <?php foreach ($voci as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r["Nome"]) ?></td>
                    <td><?= htmlspecialchars($r["Descrizione"] ?? "—") ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Nessuna voce presente.</p>
    <?php endif; ?>

    <br><a href="../menu.php">← Torna al menu</a>
</body>
</html>
