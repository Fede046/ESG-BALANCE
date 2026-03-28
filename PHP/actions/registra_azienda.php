<?php
session_start();
require_once "../db.php";

// Protezione pagina: utente non autenticato viene rimandato al login.
if (!isset($_SESSION["Username"])) {
    header("Location: ../login.php");
    exit();
}

// Accesso riservato esclusivamente ai responsabili aziendali.
// La consegna prevede che la registrazione di un'azienda sia un'operazione
// riservata solo a questa categoria di utenti.
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

    // Placeholder usato se il logo non viene caricato correttamente;
    // in questo form il logo è obbligatorio, ma il placeholder garantisce
    // un valore valido nel DB in caso di errori imprevisti durante l'upload.
    $logo = 'uploads/loghi/default.png';

    if ($ragione_sociale === "") {
        $errore = "La ragione sociale è obbligatoria.";
    } elseif (strlen($ragione_sociale) < 2) {
        $errore = "La ragione sociale deve avere almeno 2 caratteri.";
    } elseif ($nome === "") {
        $errore = "Il nome è obbligatorio.";
    } elseif (strlen($nome) < 2) {
        $errore = "Il nome deve avere almeno 2 caratteri.";
    } elseif ($piva === "") {
        $errore = "La Partita IVA è obbligatoria.";

    // Formato P.IVA italiana: esattamente 11 cifre numeriche
    } elseif (!preg_match('/^\d{11}$/', $piva)) {
        $errore = "La P.IVA deve contenere esattamente 11 cifre numeriche.";
    } elseif ($settore === null || strlen($settore) < 2) {
        $errore = "Il settore è obbligatorio (almeno 2 caratteri).";
    } elseif ($n_dip < 0) {
        $errore = "Il numero di dipendenti non può essere negativo.";

    // Logo obbligatorio: la consegna prevede che ogni azienda disponga di un logo
    } elseif (!isset($_FILES['logo']) || $_FILES['logo']['error'] === UPLOAD_ERR_NO_FILE) {
        $errore = "Il logo aziendale è obbligatorio.";
    } else {
        // Gestione upload logo: eseguita solo dopo aver superato tutte le validazioni
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $ext        = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            // Whitelist estensioni per evitare upload di file non immagine
            if (!in_array($ext, $allowedExt)) {
                $errore = "Formato immagine non supportato (jpg, jpeg, png, gif, webp).";
            } else {
                $uploadDir = '../../uploads/loghi/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                // Nome file basato sulla ragione sociale, sanificato per il filesystem
                $filename = 'logo_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $ragione_sociale) . '.' . $ext;
                $destPath = $uploadDir . $filename;

                if (move_uploaded_file($_FILES['logo']['tmp_name'], $destPath)) {
                    $logo = 'uploads/loghi/' . $filename;
                } else {
                    $errore = "Errore nel salvataggio del logo.";
                }
            }
        }

        if ($errore === "") {
            try {
                // sp_RegistraAzienda inserisce l'azienda e la collega al responsabile loggato.
                // La consegna prevede che ogni azienda sia associata a un solo responsabile,
                // ma lo stesso responsabile può gestire più aziende.
                $stmt = $pdo->prepare("CALL sp_RegistraAzienda(?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$ragione_sociale, $nome, $piva, $settore, $n_dip, $logo, $username]);
                $messaggio = "Azienda '$ragione_sociale' registrata.";

                require_once "../db_mongo.php";
                logEvento('CREATE_COMPANY', "Azienda registrata: " . $ragione_sociale . " da " . $username, 0, 0);

            } catch (PDOException $e) {
                // Codice 1062 = duplicate entry: la ragione sociale deve essere univoca in piattaforma
                if ($e->errorInfo[1] == 1062) {
                    $errore = "Errore: una azienda con questa ragione sociale esiste già.";
                } else {
                    $errore = "Errore DB: " . $e->getMessage();
                }
            }
        }
    }
}

// Carica solo le aziende associate al responsabile loggato, mostrando anche
// nr_bilanci (ridondanza concettuale mantenuta nel DB per efficienza, come
// analizzato nella tabella dei volumi della consegna).
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
    <link rel="stylesheet" href="../../STYLE/style.css">
</head>
<body>
    <div class="card-full">
        <div class="card-header">
            <h1>Registra Azienda</h1>
            <a href="../menu.php" class="btn-logout">← Torna al menu</a>
        </div>
        <?php if ($messaggio): ?><p><?= htmlspecialchars($messaggio) ?></p><?php endif; ?>
        <?php if ($errore):    ?><p><?= htmlspecialchars($errore) ?></p><?php endif; ?>

        <form action="registra_azienda.php" method="post" enctype="multipart/form-data">
            <div class="input-group2">
                <label>Ragione Sociale (max 30 caratteri)</label>
                <input type="text" name="ragione_sociale" maxlength="30" required
                value="<?= htmlspecialchars($_POST['ragione_sociale'] ?? '') ?>">
            </div>
            <div class="input-group2">
                <label>Nome (max 30 caratteri)</label>
                <input type="text" name="nome" maxlength="30" required
                value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
            </div>
            <div class="input-group2">
                <label>Partita IVA </label>
                <input type="text" name="piva" pattern="[0-9]+" inputmode="numeric" required
                value="<?= htmlspecialchars($_POST['piva'] ?? '') ?>">
            </div>
            <div class="input-group2">
                <label>Settore (max 30 caratteri)</label>
                <input type="text" name="settore" maxlength="30" required
                value="<?= htmlspecialchars($_POST['settore'] ?? '') ?>">
            </div>
            <div class="input-group2">
                <label>Numero dipendenti </label>
                <input type="number" name="n_dip" min="0" value="<?= htmlspecialchars($_POST['n_dip'] ?? '0') ?>" required>
            </div>
            <div class="input-group2">
                <label>Logo Azienda (immagine)</label>
                <input type="file" name="logo" accept="image/*" required>
            </div>

            <input type="submit" name="registra_azienda" value="Registra Azienda" class="add-btn">
        </form>

        <div class="table-container">
            <?php if ($aziende): ?>
                <h2>Le tue aziende (<?= count($aziende) ?>)</h2>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Ragione Sociale</th>
                            <th>Nome</th>
                            <th>P.IVA</th>
                            <th>Settore</th>
                            <th>Dipendenti</th>
                            <th>Nr Bilanci</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($aziende as $r): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r["Ragione_sociale"]) ?></strong></td>
                                <td><?= htmlspecialchars($r["Nome"]) ?></td>
                                <td><code class="id-badge"><?= htmlspecialchars($r["p_IVA"]) ?></code></td>
                                <td><?= htmlspecialchars($r["Settore"] ?? "—") ?></td>
                                <td><span class="badge"><?= htmlspecialchars($r["n_dip"]) ?></span></td>
                                <td><span class="badge"><?= htmlspecialchars($r["nr_bilanci"]) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="empty-msg">Nessuna azienda registrata.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
