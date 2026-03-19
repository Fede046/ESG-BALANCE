<?php
require_once __DIR__ . '/../vendor/autoload.php';

function getMongoCollection(string $collectionName): MongoDB\Collection {
    try {
        $client = new MongoDB\Client("mongodb://127.0.0.1:27017");
        return $client->selectDatabase("esg_balance_logs")
                      ->selectCollection($collectionName);
    } catch (Exception $e) {
        error_log("Errore connessione MongoDB: " . $e->getMessage());
        throw $e;
    }
}

function logEvento(string $collection, array $dati): void {
    try {
        $col = getMongoCollection($collection);
        $col->insertOne(array_merge($dati, [
            'timestamp' => new MongoDB\BSON\UTCDateTime()
        ]));
    } catch (Exception $e) {
        error_log("Errore log MongoDB: " . $e->getMessage());
    }
}
