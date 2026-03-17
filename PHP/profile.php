<?php
    session_start();
    require_once "db.php";

    if(!isset($_SESSION["Username"])){
        header("Location: login.php");
        exit();
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
