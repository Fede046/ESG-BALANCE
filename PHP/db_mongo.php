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
 * Restituisce la categoria in base all'event_type.
 */
function getCategoryFromEventType(string $event_type): string {
    $map = [
        'CREATE_COMPANY'   => 'COMPANY',
        'UPDATE_COMPANY'   => 'COMPANY',
        'DELETE_COMPANY'   => 'COMPANY',

        'CREATE_BILANCIO'  => 'BILANCIO',
        'SUBMIT_BILANCIO'  => 'BILANCIO',
        'APPROVE_BILANCIO' => 'BILANCIO',
        'REJECT_BILANCIO'  => 'BILANCIO',

        'USER_LOGIN'       => 'USER',
        'USER_LOGOUT'      => 'USER',
        'USER_REGISTER'    => 'USER',
        'USER_UPDATE'      => 'USER',

        'CREATE_REVISIONE' => 'REVISIONE',
        'UPDATE_REVISIONE' => 'REVISIONE',
        'CLOSE_REVISIONE'  => 'REVISIONE',

        'CREATE_TEMPLATE'  => 'TEMPLATE',
        'UPDATE_TEMPLATE'  => 'TEMPLATE',
        'DELETE_TEMPLATE'  => 'TEMPLATE',
    ];

    return $map[$event_type] ?? 'GENERAL';
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
            'category'   => getCategoryFromEventType($event_type),
        ]);
    } catch (Exception $e) {
        error_log("Errore log MongoDB: " . $e->getMessage());
    }
}
