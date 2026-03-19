<?php
require_once __DIR__ . '/../vendor/autoload.php';

function getMongoEvents(): MongoDB\Collection {
    try {
        $client = new MongoDB\Client("mongodb://127.0.0.1:27017");
        return $client->selectDatabase("TEST_PROGETTO")
                      ->selectCollection("events");
    } catch (Exception $e) {
        error_log("Errore connessione MongoDB: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Registra un evento nel formato unificato della collection 'events'.
 *
 * @param string $event_type  Es. 'CREATE_COMPANY', 'CREATE_BILANCIO', ...
 * @param string $text        Descrizione leggibile dell'evento
 * @param int    $user_id     ID numerico utente (0 se non disponibile)
 * @param int    $entity_id   ID entità coinvolta (0 se non disponibile)
 */
function logEvento(string $event_type, string $text, int $user_id = 0, int $entity_id = 0): void {
    try {
        $col = getMongoEvents();
        $col->insertOne([
            'text'       => $text,
            'timestamp'  => new MongoDB\BSON\UTCDateTime(),
            'user_id'    => $user_id,
            'event_type' => $event_type,
            'entity_id'  => $entity_id,
        ]);
    } catch (Exception $e) {
        error_log("Errore log MongoDB: " . $e->getMessage());
    }
}
