<?php
    session_start();
    require_once "db.php";

    if(!isset($_SESSION["Username"])){
        header("Location: login.php");
        exit();
    }

    function getRuolo(PDO $pdo, string $username): ?string {
        foreach([
            "amministratore" => "AMMINISTRATORE",
            "revisore"       => "REVISORE_ESG",
            "responsabile"   => "RESPONSABILE_AZIENDALE",
        ] as $ruolo => $tabella) {
            $stmt = $pdo->prepare("SELECT Username FROM $tabella WHERE Username = ? LIMIT 1");
            $stmt->execute([$username]);
            if ($stmt->fetch()) return $ruolo;
        }
        return null;
    }

    $pdo   = getDB();
    $ruolo = getRuolo($pdo, $_SESSION["Username"]);

    if($ruolo !== null){
        $_SESSION["Ruolo"] = $ruolo; // salviamo il ruolo in sessione per non ricalcolarlo ogni volta
        header("Location: menu.php");
    } else {
        session_destroy();
        header("Location: login.php?errore=ruolo_mancante");
    }
    exit();
?>
