<?php
    session_start();
    if(!isset($_SESSION["Username"])){
        header("Location: login.php");
        exit();
    }

    function getDB() {
    try{
        $pdo = new PDO(
            "mysql:host=127.0.0.1;port=3308;dbname=TEST",
            "root", "root"
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('SET NAMES "utf8"');
        return $pdo;
    } catch (PDOException $e) {
        die("Errore connessione: " . $e->getMessage());
    }
}
function getRuolo(PDO $pdo, string $username): ?string {
    foreach([
        "amministratore"=>"AMMINISTRATORE",
        "revisore"=>"REVISORE_ESG",
        "responsabile"=>"RESPONSABILE_AZIENDALE",
    ] as $ruolo => $tabella) {
        $stmt = $pdo->prepare("SELECT Username FROM $tabella WHERE Username = ? LIMIT 1");

        $stmt->execute([$username]);
        if ($stmt->fetch()) return $ruolo;
    }
    return null;
}

$pdo      = getDB();
$username = $_SESSION["Username"];
$ruolo    = getRuolo($pdo, $username);

if (!$ruolo) {
    session_destroy();
    header("Location: login.php");
    exit();
}

if (isset($_POST["logout"])) {
    session_destroy();
    header("Location: home.html");
    exit();
}

//AMMINISTRATORE 


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    This is the homepage;
    <form action="home.php" method="post">
        <input type="submit" name="logout" value="logout">

    </form>
</body>
</html>
<?php
    echo "Ciao, {$_SESSION["Username"]}";
    if(isset($_POST["logout"])){
        session_destroy();
        header("Location: home.php");
    }
?>