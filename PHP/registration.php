<?php

    $message = "";
    

    if(isset($_POST["register"])){
        if(!empty($_POST["usr"])&&!empty($_POST["psw"])){
            instertDB();
        }else{
            $message = 'Inserisci alemno Username e  Password per proseguire con la registrazione';
        }
    }

    function instertDB(){
        try{
            $pdo = new PDO(
            "mysql:host=127.0.0.1;port=3308;dbname=TEST",
            "root",
            "root"
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
            $pdo->exec('SET NAMES "utf8"');


            $sql = query($_POST['usr'],$_POST['psw'],$_POST['CF'],$_POST['luogo'],$_POST['data'],);
            $pdo->query($sql);       
            

            $arrayQuery = queryEmail($_POST['usr']);
            foreach($arrayQuery as $q){
                $pdo->query($q); 
            }

        }catch(PDOException $e){
            // Verifichiamo se il codice d'errore è quello del duplicato (1062)
            if ($e->errorInfo[1] == 1062) {
                echo "Errore: Lo username 'fede' è già occupato. Scegline un altro.";
            } else {
            // Gestione di altri tipi di errori SQL
                echo "Si è verificato un errore imprevisto: " . $e->getMessage();
            }
        }

    }

    function queryEmail($usr){
        $arrayQuery = [];

        $emails = $_POST['emails'];
        foreach($emails as $email){
            $arrayQuery[] = "INSERT INTO EMAIL(Username,Indirizzo) VALUES ('{$usr}','{$email}')";
        }
        return $arrayQuery;
    }

    function query($usr,$psw,$CF,$luogo,$data){
        return "INSERT INTO UTENTE(Username,CodiceFiscale,Password,Luogo,Data)
        VALUES ('{$usr}','{$CF}','{$psw}','{$luogo}','{$data}')";
        
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
    <form action="registration.php" method="post">
        <h3>Username:</h3>
        <input type="text" name='usr'>

        <h3>Password:</h3>
        <input type="text" name='psw'>

        <h3>CodiceFiscale:</h3>
        <input type="text" name='CF'>   
        
        <h3>Luogo di Nascita:</h3>
        <input type="text" name='luogo'>

        <h3>Data di Nascita:</h3>
        <input type="date" name='data'>
        
        <!--Implementare un controllo  email  adeguato -->
        <!--Implementare la possibilità di aggiungere più mail --> 
        <h3>Email:</h3>
        <div id="container">
    <div>
        <input type="email" name="emails[]">
        <button type="button" class="remove-btn">Remove</button>
    </div>
</div>

<input type="button" id="addEmail" value="Add Email">

<script>
    const container = document.getElementById('container');
    const addButton = document.getElementById('addEmail');

    // Add new email field
    addButton.addEventListener('click', () => {
        const div = document.createElement('div');
        div.innerHTML = `
            <input type="email" name="emails[]">
            <button type="button" class="remove-btn">Remove</button>
        `;
        container.appendChild(div);
    });

    // Remove email field (using Event Delegation)
    container.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-btn')) {
            if (container.children.length > 1) {
                e.target.parentElement.remove();
            }
        }
    });
</script>

        <br>
        
        
        <br>
        <input type="submit" name='register' value="Register">


    </form>

    <a href="home.php"><button>Home</button></a>

    
    
    <?php
        if (isset($message)) {
            echo "<h3 style='color: red;'>{$message}</h3>";
        }    
    ?>



    
</body>
</html>

