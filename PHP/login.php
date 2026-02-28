<?php 
session_start()
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

    <a href="home.php"><button>Home</button></a>

</body>
</html>
<?php   
    if(isset($_POST["login"])){
        if(!empty($_POST["usr"])&&!empty($_POST["psw"])){

            if(control()){
                $_SESSION["Username"] = $_POST["usr"];
                //andiamo nell'altro file php
                header("Location: profile.php");
            }
            

        }
    }
    
    function control(){
        try{
            $pdo = new PDO(
            "mysql:host=127.0.0.1;port=3308;dbname=TEST",
            "root",
            "root"
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
            $pdo->exec('SET NAMES "utf8"');
            
            
            $sql = "SELECT Password FROM UTENTE WHERE Username = '" . $_POST["usr"] . "';";            
            
            $result = $pdo->query($sql);

            //Preso da chat al posto di fare  il ciclo for
            // 2. APRI IL PACCHETTO: Estrai la riga come array associativo
            $riga = $result->fetch(PDO::FETCH_ASSOC);

            //Aggiungere psw criptata
            if(!empty($riga)){
                if($riga["Password"]==$_POST['psw']){
                    
                    echo "Login  corretto!";
                    return true;
                }else{
                    echo "Password  errata";
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