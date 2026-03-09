<?php
session_start();

if (!isset($_SESSION["Username"])) {
    header("Location: login.php");
    exit();
}

try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3308;dbname=TEST", "root", "root");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('SET NAMES "utf8"');
} catch (PDOException $e) {
    die("Errore connessione DB: " . $e->getMessage());
}

$username = $_SESSION["Username"];

// Verifica ruolo amministratore
$stmt = $pdo->prepare("SELECT Username FROM AMMINISTRATORE WHERE Username = ? LIMIT 1");
$stmt->execute([$username]);
if (!$stmt->fetch()) {
    header("Location: login.php");
    exit();
}

if (isset($_POST["logout"])) {
    session_destroy();
    header("Location: home.php");
    exit();
}

$messaggio = "";
$errore    = "";

// ── 1. Aggiungi Indicatore ESG ────────────────────────────────────────────────
if (isset($_POST["aggiungi_indicatore"])) {
    $nome      = trim($_POST["nome_indicatore"]);
    $rilevanza = $_POST["rilevanza"] !== "" ? (int)$_POST["rilevanza"] : null;
    $immagine  = trim($_POST["immagine"]) ?: null;

    if ($nome === "") {
        $errore = "Il nome dell'indicatore è obbligatorio.";
    } elseif ($rilevanza !== null && ($rilevanza < 0 || $rilevanza > 10)) {
        $errore = "La rilevanza deve essere tra 0 e 10.";
    } else {
        try {
            $pdo->prepare(
                "INSERT INTO INDICATORE_ESG (Nome, Username_Amministratore, Immagine, Rilevanza)
                 VALUES (?, ?, ?, ?)"
            )->execute([$nome, $username, $immagine, $rilevanza]);
            $messaggio = "Indicatore ESG '$nome' aggiunto.";
        } catch (PDOException $e) {
            $errore = "Errore DB: " . $e->getMessage();
        }
    }
}

// ── 2. Crea Template Bilancio ─────────────────────────────────────────────────
if (isset($_POST["crea_template"])) {
    $nome_tpl = trim($_POST["nome_template"]);
    $anno_tpl = (int)$_POST["anno_template"];

    if ($nome_tpl === "" || $anno_tpl < 2000) {
        $errore = "Nome e anno template obbligatori (anno ≥ 2000).";
    } else {
        try {
            $pdo->prepare(
                "INSERT INTO TEMPLATE_BILANCIO (Nome, Anno, Username_Amministratore)
                 VALUES (?, ?, ?)"
            )->execute([$nome_tpl, $anno_tpl, $username]);
            $messaggio = "Template '$nome_tpl' ($anno_tpl) creato.";
        } catch (PDOException $e) {
            $errore = "Errore DB: " . $e->getMessage();
        }
    }
}

// ── 3. Associa Revisore ESG a Bilancio ───────────────────────────────────────
if (isset($_POST["associa_revisore"])) {
    $id_bil  = (int)$_POST["id_bilancio"];
    $rag_soc = trim($_POST["ragione_sociale_bil_rev"]);
    $rev     = trim($_POST["username_revisore"]);

    if ($id_bil <= 0 || $rag_soc === "" || $rev === "") {
        $errore = "Tutti i campi sono obbligatori.";
    } else {
        try {
            $pdo->prepare(
                "INSERT INTO VALUTA_REVISORE_BILANCIO
                    (Username_Revisore_ESG, id_bilancio, Ragione_sociale_bilancio)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE Username_Revisore_ESG = VALUES(Username_Revisore_ESG)"
            )->execute([$rev, $id_bil, $rag_soc]);
            $messaggio = "Revisore '$rev' associato al bilancio #$id_bil ($rag_soc).";
        } catch (PDOException $e) {
            $errore = "Errore DB: " . $e->getMessage();
        }
    }
}

// ── Lettura dati ──────────────────────────────────────────────────────────────
$indicatori = [];
$templates  = [];
$revisori   = [];
$bilanci    = [];

try {
    $indicatori = $pdo->query(
        "SELECT Nome, Rilevanza FROM INDICATORE_ESG ORDER BY Nome"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore lettura INDICATORE_ESG: " . $e->getMessage();
}

try {
    $templates = $pdo->query(
        "SELECT Nome, Anno FROM TEMPLATE_BILANCIO ORDER BY Anno DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore lettura TEMPLATE_BILANCIO: " . $e->getMessage();
}

try {
    $revisori = $pdo->query(
        "SELECT Username FROM REVISORE_ESG ORDER BY Username"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore lettura REVISORE_ESG: " . $e->getMessage();
}

try {
    $bilanci = $pdo->query(
        "SELECT id, Ragione_sociale_azienda, Stato FROM BILANCIO ORDER BY id DESC LIMIT 50"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errore = "Errore lettura BILANCIO: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin – ESG Balance</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; max-width: 960px; margin: 40px auto; padding: 0 20px; background: #f4f6f4; }
        h1 { color: #1b4332; margin-bottom: 2px; }
        h2 { color: #2c6e49; border-bottom: 2px solid #2c6e49; padding-bottom: 5px; margin-top: 0; }
        h3 { color: #1b4332; margin: 18px 0 8px; }
        .sezione { background:#fff; border:1px solid #d0ddd0; border-radius:8px; padding:22px; margin-bottom:22px; }
        .msg-ok  { background:#d4edda; border:1px solid #28a745; color:#155724; padding:10px 14px; border-radius:5px; margin-bottom:18px; }
        .msg-err { background:#f8d7da; border:1px solid #dc3545; color:#721c24; padding:10px 14px; border-radius:5px; margin-bottom:18px; }
        label { font-weight:bold; display:block; margin-top:12px; margin-bottom:3px; }
        small { font-weight:normal; color:#666; }
        input[type=text], input[type=number], select {
            width:100%; padding:8px 10px; border:1px solid #bbb; border-radius:4px; font-size:.95em;
        }
        input[type=submit] {
            margin-top:16px; padding:10px 22px; background:#2c6e49; color:#fff;
            border:none; border-radius:5px; cursor:pointer; font-size:1em;
        }
        input[type=submit]:hover { background:#1b4332; }
        table { width:100%; border-collapse:collapse; margin-top:12px; font-size:.9em; }
        th { background:#2c6e49; color:#fff; padding:8px 10px; text-align:left; }
        td { padding:7px 10px; border-bottom:1px solid #e0e0e0; }
        tr:hover td { background:#f0f7f0; }
        .topbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:28px; }
        .badge  { background:#2c6e49; color:#fff; padding:4px 14px; border-radius:12px; font-size:.85em; }
        .btn-logout { background:#c0392b; color:#fff; border:none; padding:8px 18px; border-radius:5px; cursor:pointer; }
        .btn-logout:hover { background:#922b21; }
        p.vuoto { color:#888; font-style:italic; }
    </style>
</head>
<body>

<div class="topbar">
    <div>
        <h1>ESG Balance — Amministratore</h1>
        <p style="margin:4px 0 0">
            <strong><?= htmlspecialchars($username) ?></strong>
            <span class="badge">amministratore</span>
        </p>
    </div>
    <form action="admin.php" method="post">
        <button type="submit" name="logout" class="btn-logout">Logout</button>
    </form>
</div>

<?php if ($messaggio): ?><div class="msg-ok"><?= htmlspecialchars($messaggio) ?></div><?php endif; ?>
<?php if ($errore):    ?><div class="msg-err"><?= htmlspecialchars($errore) ?></div><?php endif; ?>

<!-- ── Indicatori ESG ─────────────────────────────────────────────────────── -->
<div class="sezione">
    <h2>📋 Aggiungi Indicatore ESG</h2>
    <form action="admin.php" method="post">
        <label>Nome * <small>(max 30 caratteri)</small></label>
        <input type="text" name="nome_indicatore" maxlength="30" required>
        <label>Rilevanza <small>(0–10, opzionale)</small></label>
        <input type="number" name="rilevanza" min="0" max="10">
        <label>Immagine <small>(path al file, opzionale)</small></label>
        <input type="text" name="immagine" maxlength="500">
        <input type="submit" name="aggiungi_indicatore" value="Aggiungi Indicatore">
    </form>

    <?php if ($indicatori): ?>
        <h3>Indicatori presenti</h3>
        <table>
            <tr><th>Nome</th><th>Rilevanza</th></tr>
            <?php foreach ($indicatori as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r["Nome"]) ?></td>
                    <td><?= $r["Rilevanza"] ?? "—" ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p class="vuoto">Nessun indicatore presente.</p>
    <?php endif; ?>
</div>

<!-- ── Template Bilancio ──────────────────────────────────────────────────── -->
<div class="sezione">
    <h2>📄 Crea Template di Bilancio</h2>
    <form action="admin.php" method="post">
        <label>Nome template * <small>(max 30 caratteri)</small></label>
        <input type="text" name="nome_template" maxlength="30" required>
        <label>Anno * <small>(es: 2025)</small></label>
        <input type="number" name="anno_template" min="2000" max="2100" required>
        <input type="submit" name="crea_template" value="Crea Template">
    </form>

    <?php if ($templates): ?>
        <h3>Template esistenti</h3>
        <table>
            <tr><th>Nome</th><th>Anno</th></tr>
            <?php foreach ($templates as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r["Nome"]) ?></td>
                    <td><?= htmlspecialchars($r["Anno"]) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p class="vuoto">Nessun template presente.</p>
    <?php endif; ?>
</div>

<!-- ── Associa Revisore a Bilancio ────────────────────────────────────────── -->
<div class="sezione">
    <h2>🔗 Associa Revisore ESG a Bilancio</h2>
    <form action="admin.php" method="post">
        <label>ID Bilancio *</label>
        <input type="number" name="id_bilancio" min="1" required>
        <label>Ragione Sociale Azienda * <small>(max 30 caratteri)</small></label>
        <input type="text" name="ragione_sociale_bil_rev" maxlength="30" required>
        <label>Username Revisore ESG *</label>
        <select name="username_revisore" required>
            <option value="">-- seleziona revisore --</option>
            <?php foreach ($revisori as $r): ?>
                <option value="<?= htmlspecialchars($r["Username"]) ?>">
                    <?= htmlspecialchars($r["Username"]) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="submit" name="associa_revisore" value="Associa Revisore">
    </form>

    <?php if ($bilanci): ?>
        <h3>Ultimi 50 bilanci nel sistema</h3>
        <table>
            <tr><th>ID</th><th>Azienda</th><th>Stato</th></tr>
            <?php foreach ($bilanci as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r["id"]) ?></td>
                    <td><?= htmlspecialchars($r["Ragione_sociale_azienda"]) ?></td>
                    <td><?= htmlspecialchars($r["Stato"]) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p class="vuoto">Nessun bilancio presente.</p>
    <?php endif; ?>
</div>

</body>
</html>
