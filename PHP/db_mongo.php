<?php
require_once __DIR__ . '/../vendor/autoload.php';
/**
 * Restituisce la MongoDB Collection corrispondente alla categoria dell'evento.
 * Usato solo da logEvento(); instrada ogni evento nella collection giusta.
 * Se il tipo non è mappato, ricade su 'events_general'.
 */
function getMongoCollection(string $event_type): MongoDB\Collection {
    try {
        $client = new MongoDB\Client("mongodb://127.0.0.1:27017");
        $db = $client->selectDatabase("TEST_PROGETTO");

        $category = getCategoryFromEventType($event_type);
        $collectionMap = [
            'USER'      => 'events_user',
            'BILANCIO'  => 'events_bilancio',
            'REVISIONE' => 'events_revisione',
            'TEMPLATE'  => 'events_template',
            'ESG'       => 'events_esg',
            'COMPANY'   => 'events_company',
            'GENERAL'   => 'events_general',
        ];

        $collectionName = $collectionMap[$category] ?? 'events_general';
        return $db->selectCollection($collectionName);

    } catch (Exception $e) {
        error_log("Errore connessione MongoDB: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Mappa ogni event_type alla sua categoria MongoDB.
 * Centralizza la logica: aggiungere un nuovo tipo richiede solo una riga qui.
 */
function getCategoryFromEventType(string $event_type): string {
    $map = [
    'CREATE_COMPANY'   => 'COMPANY',
    
    'CREATE_BILANCIO'  => 'BILANCIO',
    'APPROVE_BILANCIO' => 'BILANCIO',
    'REJECT_BILANCIO'  => 'BILANCIO',

    'USER_LOGIN'       => 'USER',
    'USER_LOGOUT'      => 'USER',
    'USER_REGISTER'    => 'USER',

    'INSERT_NOTA'      => 'REVISIONE',
    'INSERT_GIUDIZIO'  => 'REVISIONE',
    'ASSIGN_REVISORE'  => 'REVISIONE',
    'CREATE_REVISIONE' => 'REVISIONE',

    'ADD_VOCE'         => 'TEMPLATE',

    'CREATE_INDICATORE' => 'ESG',
    'INSERT_ESG'        => 'ESG',
    'ADD_COMPETENZA'    => 'ESG',
    ];

    return $map[$event_type] ?? 'GENERAL';
}

/**
 * Registra un evento significativo su MongoDB, come richiesto dalla consegna.
 * Chiamata dopo ogni operazione rilevante (creazione bilancio, giudizio, ecc.).
 * Gli errori vengono silenziati per non bloccare l'operazione principale.
 *
 * @param string $event_type  Es. 'CREATE_BILANCIO', 'USER_LOGIN'
 * @param string $text        Descrizione leggibile dell'evento
 * @param int    $user_id     ID utente (0 se non disponibile)
 * @param int    $entity_id   ID entità coinvolta, es. id bilancio (0 se N/A)
 */
function logEvento(string $event_type, string $text, int $user_id = 0, int $entity_id = 0): void {
    try {
        $col = getMongoCollection($event_type);
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