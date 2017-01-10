<?php


require '../vendor/autoload.php';
require('api_config.php');

/*
 * REST
 *
 *      Neue Bewertung anlegen:     /api/rating             (POST)
 *      Bewertungen abfragen:       /api/ratings/lat/lng    (GET)
 *
 * */

define('TIME_ZONE', 'UTC');
define('DATE_FORMAT', 'd\.m\.Y');
define('DATE_FIELD_NAME', 'date');

$app = new \Slim\App();


/**
 * Die SLIM Routes.
 */
$id = array();

$app->get('/ratings/{lat}/{lng}', function ($request, $response, $args) {
    return getRatings($response, $args);
});


//--------------------------------------------------------------------------------------------------
$app->get('/film-quote/{id}', function ($request, $response, $args) {

    return getRating($response, "SELECT * FROM quotes WHERE id = {$args['id']}");
});

$app->post('/film-quotes', function ($request, $response) {

    return createFilmQuote($response, $request->getParsedBody());
});


/**
 * Gibt eine Datenbank-Verbindung zurück. Falls das nicht gelingt, wird die SLIM App mit einem
 * 503 Error gestoppt.
 *
 * @param $response  Das Response Object
 * @return PDO  Die Datenbank-Verbindung
 */
function getDBConnection($response)
{

    try {

        return new PDO(QUERY_STRING, DB_USER, DB_PWD);
    } catch (PDOException $e) {

        throw new Exception('Database connection could not be established.');
    }
}

/**
 * Liest die ratings entsprechend der args aus der Datenbank.
 *
 * @param $response Das Response Object
 * @param $args Die Parameter mit den Koordinaten (lat/lng)
 * @return Das Response Object
 */
function getRatings($response, $args)
{
    try {
        $db = getDBConnection($response);
        $selectRatings = $db->prepare("SELECT * FROM rating INNER JOIN location ON rating.RAT_LOCATION_ID = location.LOC_ID WHERE location.LOC_LAT = {$args['lat']} AND location.LOC_LNG = {$args['lng']};");

        if ($selectRatings->execute()) {
            $ratings = $selectRatings->fetchAll(PDO::FETCH_ASSOC);

            if($selectRatings->rowCount() > 0) {
                return $response->withJson($ratings);
            } else {
                return $response->write('No rating found.')->withStatus(404);
            }
        } else {
            return $response->write('Error in quering database.')->withStatus(500);
        }
    } catch (Exception $e) {

        return $response->write($e->getMessage())->withStatus(503);
    }
}


/**
 * Bindet die JSON-Daten in das Prepared Statement.
 *
 * @param $preparedStatement  Das Prepared-Statement
 * @param $jsonData  Film-Quote als JSON Struktur
 * @return  Das Prepared-Statement den Daten
 */
function bindParameters($preparedStatement, $jsonData)
{

    $preparedStatement->bindParam(':title', $jsonData['title'], PDO::PARAM_STR);
    $preparedStatement->bindParam(':quote', $jsonData['quote'], PDO::PARAM_STR);
    $preparedStatement->bindParam(':movie_character', $jsonData['movie_character'], PDO::PARAM_STR);
    $preparedStatement->bindParam(':actor', $jsonData['actor'], PDO::PARAM_STR);
    $preparedStatement->bindParam(':year', $jsonData['year'], PDO::PARAM_INT);

    return $preparedStatement;
}


/**
 * Erzeugt einen Film-Quote Eintrag in der Datenbank. Gibt dem Aufrufer die JSON Struktur ergänzt mit der ID,
 * die zum Abfragen nötig ist zurück. Status Code ist 201 Created. Falls etwas schief läuft wird der Request mit
 * Status 500 zurück gegeben.
 *
 * @param $response  Das Response Object
 * @param $jsonData  Die Film-Quote als JSON
 * @return Das Response Object
 */
function createFilmQuote($response, $jsonData)
{

    try {

        $db = getDBConnection($response);

        $stmt = $db->prepare('INSERT INTO quotes (title, quote, movie_character, actor, year) values(:title, :quote, :movie_character, :actor, :year)');

        $stmt = bindParameters($stmt, $jsonData);

        if ($stmt->execute()) {

            $jsonData['id'] = $db->lastInsertId();

            return $response->withJson($jsonData, 201);
        } else {

            return $response->write('Error inserting in database.')->withStatus(500);
        }
    } catch (Exception $e) {

        return $response->write($e->getMessage())->withStatus(503);
    }
}

/**
 * Startet die SLIM App. Muss zuletzt im Skript aufgerufen werden.
 */
$app->run();