<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION["Username"])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION["Ruolo"] !== "responsabile") {
    header("Location: ../menu.php");
    exit();
}

$username  = $_SESSION["Username"];
$pdo       = getDB();
$messaggio = "";
$errore    = "";

if (isset($_POST["registra_azienda"])) {
    $ragione_sociale = trim($_POST["ragione_sociale"]);
    $nome            = trim($_POST["nome"]);
    $piva            = trim($_POST["piva"]);
    $settore         = trim($_POST["settore"]) ?: null;
    $n_dip           = (int)($_POST["n_dip"] ?? 0);
    $logo            = trim($_POST["logo"]) ?: null;

    if ($ragione_sociale === "" || $nome === "" || $piva === "") {
        $errore = "Ragione sociale, nome e partita IVA sono obbligatori.";
    } else {
        try {
            $stmt = $pdo->prepare("CALL sp_RegistraAzienda(?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$ragione_sociale, $nome, $piva, $settore, $n_dip, $logo, $username]);
            $messaggio = "Azienda '$ragione_sociale' registrata.";
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $errore = "Errore: una azienda con questa ragione sociale esiste già.";
            } else {
                $errore = "Errore DB: " . $e->getMessage();
            }
        }
    }
}

// Lettura aziende del responsabile loggato
$aziende = [];
try {
    $stmt = $pdo->prepare(
        "SELECT Ragione_sociale, Nome, p_IVA, Settore, n_dip, nr_bilanci
         FROM AZIENDA
         WHERE Username_Responsabile_Aziendale = ?
         ORDER BY Ragione_sociale"
    );
    $stmt->execute([$username]);
    $aziende = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore lettura aziende: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registra Azienda</title>
</head>
<body>
    <h1>Registra Azienda</h1>

    <?php if ($messaggio): ?><p><?= htmlspecialchars($messaggio) ?></p><?php endif; ?>
    <?php if ($errore):    ?><p><?= htmlspecialchars($errore) ?></p><?php endif; ?>

    <form action="registra_azienda.php" method="post">
        <label>Ragione Sociale * (max 30 caratteri)</label><br>
        <input type="text" name="ragione_sociale" maxlength="30" required><br>

        <label>Nome * (max 30 caratteri)</label><br>
        <input type="text" name="nome" maxlength="30" required><br>

        <label>Partita IVA *</label><br>
        <input type="number" name="piva" required><br>

        <label>Settore (max 30 caratteri)</label><br>
        <input type="text" name="settore" maxlength="30"><br>

        <label>Numero dipendenti</label><br>
        <input type="number" name="n_dip" min="0" value="0"><br>

        <label>Logo (path al file, opzionale, max 30 caratteri)</label><br>
        <input type="text" name="logo" maxlength="30"><br>

        <input type="submit" name="registra_azienda" value="Registra Azienda">
    </form>

    <?php if ($aziende): ?>
        <h2>Le tue aziende (<?= count($aziende) ?>)</h2>
        <table border="1">
            <tr><th>Ragione Sociale</th><th>Nome</th><th>P.IVA</th><th>Settore</th><th>Dipendenti</th><th>Nr Bilanci</th></tr>
            <?php foreach ($aziende as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r["Ragione_sociale"]) ?></td>
                    <td><?= htmlspecialchars($r["Nome"]) ?></td>
                    <td><?= htmlspecialchars($r["p_IVA"]) ?></td>
                    <td><?= htmlspecialchars($r["Settore"] ?? "—") ?></td>
                    <td><?= htmlspecialchars($r["n_dip"]) ?></td>
                    <td><?= htmlspecialchars($r["nr_bilanci"]) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Nessuna azienda registrata.</p>
    <?php endif; ?>

    <br><a href="../menu.php">← Torna al menu</a>
</body>
</html>
