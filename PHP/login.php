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
</head>
<body>
    <form action="login.php" method="post">
        <h3>Username:</h3>
        <input type="text" name="usr">
        <h3>Password:</h3>
        <input type="password" name="psw">
        <br>
        <input type="submit" name="login" value="Login">
    </form>

    <?php if ($errore): ?>
        <p><?= htmlspecialchars($errore) ?></p>
    <?php endif; ?>

    <a href="home.php"><button>Home</button></a>
</body>
</html>
