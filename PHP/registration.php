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

        <h3>CodiceFiscale:</h3>
        <input type="text" name='CF'>   

        <h3>Data di Nascita:</h3>
        <input type="date" name='dateBorn'>
        
        <h3>Luogo di Nascita:</h3>
        <input type="text" name='birthplace'>
        
        <!--Implementare un controllo  email  adeguato -->
        <!--Implementare la possibilità di aggiungere più mail --> 
        <h3>Email:</h3>
        <div id='container'>
            <div>
                <input type="email" name='psw'>
                <button>Remove</button>
            </div>
        </div>

        <button>Add Email</button>
        <br>
        
        
        <br>
        <input type="submit" name='register' value="Register">


    </form>

    <a href="home.php"><button>Home</button></a>

    
</body>
</html>

<?php



?>