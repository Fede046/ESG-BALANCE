<?php 
session_start();
require_once "db.php";

// La logica va PRIMA dell'HTML, altrimenti header() non funziona
if(isset($_POST["login"])){
    if(!empty($_POST["usr"])&&!empty($_POST["psw"])){
        if(control()){
            $_SESSION["Username"] = $_POST["usr"];
            header("Location: profile.php");
            exit();
        }
    }
}

function control(){
    try{
        $pdo = getDB();
        
        $sql = "SELECT Password FROM UTENTE WHERE Username = '" . $_POST["usr"] . "';";            
        
        $result = $pdo->query($sql);

        $riga = $result->fetch(PDO::FETCH_ASSOC);

        //Aggiungere psw criptata
        if(!empty($riga)){
            if($riga["Password"]==$_POST['psw']){
                echo "Login corretto!";
                return true;
            }else{
                echo "Password errata";
            }
        }else{
            echo "Username inesistente";
        }
        return false;

    }catch(PDOException $e){
        echo "Errore Connessione: {$e->getMessage()}";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form action="login.php" method="post">
        <h3>Username:</h3>
        <input type="text" name='usr'>
        <h3>Password:</h3>
        <input type="text" name='psw'>
        <br>
        <input type="submit" name='login' value="Login">
    </form>

    <a href="home.html"><button>Home</button></a>
</body>
</html>
