<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    
</body>
</html>
<?php
    try{
        //Configurazione (Slide PROF)
        //Per farlo andare mettere:
        //porta:3308
        //usr:root
        //psw:root
        $pdo = new PDO(
            "mysql:host=127.0.0.1;port=3308;dbname=TEST",
            "root",
            "root"
        );
        //PDO::ATTR_ERRMODE è l'attributo che controlla come PDO gestisce gli errori.
        //PDO::ERRMODE_EXCEPTION fa sì che PDO lanci eccezioni quando si verifica un errore.
        $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        //Questo comando SQL dice al server MySQL di usare la codifica UTF-8 per tutti i dati scambiati tra PHP e il database.
        $pdo->exec('SET NAMES "utf8"');
        
        //------------------

        //Questo è solamente per geneare tantio username differenti (da cambiare)
        $randomUsr = random_int(1,1000);

        //Insert sul 
        $insertSql = "INSERT INTO UTENTE(Username,CodiceFiscale,Password,Luogo,Data)
        VALUES ('{$randomUsr}','MLGFRA04H21A844K','1234','Bologna','12-01-2024')"; 
        
        $result = $pdo->query($insertSql);

        
        $selectSql = "SELECT * FROM UTENTE WHERE Password='1234'";
        
        $result1 = $pdo->query($selectSql);

        

        //foreach per vedere tutta la select di output
        //Evidentemente la variabile $result di ooutput è un array di righe
        //Le quali righe non son ouna stringa ma una coppia key value
        foreach($result1 as $row){
            echo $row['Username'] . "<br>";
        }



    }catch(PDOException $e){
        echo "Errore Connessione: {$e->getMessage()}";
        exit();
    }
?>