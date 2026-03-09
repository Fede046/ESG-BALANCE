<?php
function getDB(): PDO {
    try {
        $pdo = new PDO(
            "mysql:host=127.0.0.1;port=3308;dbname=TEST",
            "root", "root"
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('SET NAMES "utf8"');
        return $pdo;
    } catch (PDOException $e) {
        die("Errore connessione DB: " . $e->getMessage());
    }
}

function getRuolo(PDO $pdo, string $username): ?string {
    foreach ([
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
