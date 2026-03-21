<?php
session_start();
require_once "db.php";

$errore = "";

if (isset($_POST["login"])) {
    if (!empty($_POST["usr"]) && !empty($_POST["psw"])) {
        try {
            $pdo = getDB();
            // Hash MD5 con salt 'jdd' prima di confrontare col DB
            $psw_hash = md5($_POST["psw"] . "jdd");

            $stmt = $pdo->prepare("CALL sp_Login(?, ?)");
            $stmt->execute([$_POST["usr"], $psw_hash]);
            $riga = $stmt->fetch(PDO::FETCH_ASSOC);

if ($riga) {
    $_SESSION["Username"] = $riga["Username"];
    $_SESSION["Ruolo"]    = $riga["Ruolo"];

    require_once "db_mongo.php";
    logEvento(
        'USER_LOGIN',
        "Login effettuato: " . $riga["Username"] . " (ruolo: " . $riga["Ruolo"] . ")",
        0, 0
    );

    header("Location: menu.php");
    exit();
} else {
                $errore = "Username o password errati.";
            }
        } catch (PDOException $e) {
            $errore = "Errore connessione: " . $e->getMessage();
        }
    } else {
        $errore = "Inserisci username e password.";
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – ESG Balance</title>
    <link rel="stylesheet" href="../STYLE/style.css">

</head>
<body>
    <div class="card">
        <form action="login.php" method="post">
            <div class="input-group">
                <label>Username</label>
                <input type="text" name="usr" placeholder="Inserisci username" required>
            </div>
            
            <div class="input-group">
                <label>Password</label>
                <input type="password" name="psw" placeholder="••••••••" required>
            </div>

            <input type="submit" name="login" value="Accedi" class="btn-login">
        </form>

        <?php if ($errore): ?>
            <p class="error-msg"><?= htmlspecialchars($errore) ?></p>
        <?php endif; ?>

        <a href="home.php" class="btn-home">Home</a>
    </div>
</body>
</html>
