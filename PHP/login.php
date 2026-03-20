<?php
session_start();
require_once "db.php";

if (isset($_POST["login"])) {
    if (!empty($_POST["usr"]) && !empty($_POST["psw"])) {
        try {
            $pdo  = getDB();
            $stmt = $pdo->prepare(
                "SELECT u.Password, 
                        CASE
                            WHEN a.Username IS NOT NULL THEN 'amministratore'
                            WHEN r.Username IS NOT NULL THEN 'revisore'
                            WHEN ra.Username IS NOT NULL THEN 'responsabile'
                            ELSE 'generico'
                        END AS Ruolo
                 FROM UTENTE u
                 LEFT JOIN AMMINISTRATORE a ON u.Username = a.Username
                 LEFT JOIN REVISORE_ESG r ON u.Username = r.Username
                 LEFT JOIN RESPONSABILE_AZIENDALE ra ON u.Username = ra.Username
                 WHERE u.Username = ?"
            );
            $stmt->execute([$_POST["usr"]]);
            $riga = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!empty($riga) && $riga["Password"] === $_POST["psw"]) {
                $_SESSION["Username"] = $_POST["usr"];
                $_SESSION["Ruolo"]    = $riga["Ruolo"];

                require_once "db_mongo.php";
                logEvento(
                    'USER_LOGIN',
                    "User logged in: " . $_POST["usr"] . " (ruolo: " . $riga["Ruolo"] . ")",
                    0, 0
                );

                header("Location: profile.php");
                exit();
            } else {
                $loginError = "Username o password errati.";
            }
        } catch (PDOException $e) {
            $loginError = "Errore connessione: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <?php if (!empty($loginError)): ?>
        <p style="color:red"><?= htmlspecialchars($loginError) ?></p>
    <?php endif; ?>

    <form action="login.php" method="post">
        <h3>Username:</h3>
        <input type="text" name='usr'>
        <h3>Password:</h3>
        <input type="password" name='psw'>
        <br>
        <input type="submit" name='login' value="Login">
    </form>

    <a href="home.php"><button>Home</button></a>
</body>
</html>
