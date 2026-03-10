<?php
    require_once "db.php";

    $message = "";

    if(isset($_POST["register"])){
        if(!empty($_POST["usr"])&&!empty($_POST["psw"])){
            $message = instertDB();
            // Redirect solo se registrazione riuscita
            if($message === "ok"){
                header("Location: login.php");
                exit();
            }
        }else{
            $message = 'Inserisci almeno Username e Password per proseguire con la registrazione';
        }
    }

    function instertDB(){
        try{
            $pdo = getDB();

            $sql = query($_POST['usr'],$_POST['psw'],$_POST['CF'],$_POST['luogo'],$_POST['data']);
            $pdo->query($sql);       

            $arrayQuery = queryEmail($_POST['usr']);
            foreach($arrayQuery as $q){
                $pdo->query($q); 
            }

            $queryRuolo = queryRuolo($_POST['usr']);
            if ($queryRuolo !== null) {
                $pdo->query($queryRuolo);
            }

            return "ok";

        }catch(PDOException $e){
            if ($e->errorInfo[1] == 1062) {
                return "Errore: Lo username è già occupato. Scegline un altro.";
            } else {
                return "Si è verificato un errore imprevisto: " . $e->getMessage();
            }
        }
    }

    function queryRuolo($usr) {
        $ruolo = $_POST['ruolo'] ?? '';
        
        switch($ruolo) {
            case 'RevisoreESG':
                return "INSERT INTO REVISORE_ESG(Username, IndiceAffidabilita, NumRevisioni) VALUES ('{$usr}', 5, 0)";
            case 'ResponsabileAziendale':
                return "INSERT INTO RESPONSABILE_AZIENDALE(Username, CV) VALUES ('{$usr}', '')";
            default:
                return null;
        }
    }

    function queryEmail($usr){
        $arrayQuery = [];

        $emails = $_POST['emails'];
        foreach($emails as $email){
            $arrayQuery[] = "INSERT INTO EMAIL(Username_Utente,Indirizzo) VALUES ('{$usr}','{$email}')";
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

        <h3>Ruolo</h3>
        <label><input type="radio" name="ruolo" value="RevisoreESG"> Revisore ESG</label><br>
        <label><input type="radio" name="ruolo" value="ResponsabileAziendale"> Responsabile Aziendale</label><br>
        
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

            addButton.addEventListener('click', () => {
                const div = document.createElement('div');
                div.innerHTML = `
                    <input type="email" name="emails[]">
                    <button type="button" class="remove-btn">Remove</button>
                `;
                container.appendChild(div);
            });

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

    <a href="home.html"><button>Home</button></a>

    <?php
        if ($message !== "" && $message !== "ok") {
            echo "<h3 style='color: red;'>{$message}</h3>";
        }    
    ?>
</body>
</html>
