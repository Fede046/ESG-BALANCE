<?php
session_start();
require_once "db.php";

// Protezione pagina: utente non autenticato viene rimandato al login
if (!isset($_SESSION["Username"])) {
    header("Location: login.php");
    exit();
}

// Logout: registra l'evento su MongoDB prima di distruggere la sessione
if (isset($_POST["logout"])) {
    $username_log = $_SESSION["Username"];
    require_once "db_mongo.php";
    logEvento('USER_LOGOUT', "Logout effettuato", $_SESSION["Username"]);
    session_destroy();
    header("Location: home.php");
    exit();
}

$username = $_SESSION["Username"];
$ruolo    = $_SESSION["Ruolo"];

// Voci di menu differenziate per ruolo, coerenti con le operazioni previste
// dalla consegna per ciascuna categoria utente. Le statistiche sono visibili
// a tutti gli utenti autenticati come da specifica.
$voci_menu = [
    "amministratore" => [
        ["label" => "Aggiungi Indicatore ESG",    "href" => "actions/aggiungi_indicatore.php"],
        ["label" => "Crea Template Bilancio",      "href" => "actions/crea_template.php"],
        ["label" => "Associa Revisore a Bilancio", "href" => "actions/associa_revisore.php"],
    ],
    "revisore" => [
        ["label" => "Le mie Competenze",           "href" => "actions/aggiungi_competenze.php"],
        ["label" => "Inserisci Nota su Voce",      "href" => "actions/inserisci_nota.php"],
        ["label" => "Inserisci Giudizio",          "href" => "actions/inserisci_giudizio.php"],
    ],
    "responsabile" => [
        ["label" => "Registra Azienda",            "href" => "actions/registra_azienda.php"],
        ["label" => "Crea Bilancio",               "href" => "actions/crea_bilancio.php"],
        ["label" => "Inserisci Valore ESG",        "href" => "actions/inserisci_valore_esg.php"],
    ],
];
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu – ESG Balance</title>
    <link rel="stylesheet" href="../STYLE/style.css">
</head>
<body>
    <div class="card-full">
        <div class="card-header">
            <h1>ESG Balance</h1>
            <form action="menu.php" method="post">
                <input type="submit" name="logout" value="Logout" class="btn-logout">
            </form>
        </div>
        <p>Benvenuto <strong><?= htmlspecialchars($username) ?></strong><br>  Ruolo: <strong><?= htmlspecialchars($ruolo) ?></strong></p>

        <ul>
            <?php foreach ($voci_menu[$ruolo] as $voce): ?>
                <li><a href="<?= htmlspecialchars($voce["href"]) ?>"><?= htmlspecialchars($voce["label"]) ?></a></li>
            <?php endforeach; ?>

            <!-- Statistiche visibili a tutti -->
            <li><a href="statistiche.php">Statistiche</a></li>
        </ul>
    </div>
</body>
</html>
