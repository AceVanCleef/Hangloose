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



    /**
     * Die SLIM Routes.
     */
    $id = array();

    /**
     *  Test query
     */
    $app->get('/test/{nr}', function($request, $response, $args) {

        echo('{response: "hallo"}');
    });



$app->get('/ratings/{lat}/{lng}', function ($request, $response, $args) {
    var_dump($request); //educational use (Stefan)
    var_dump($response);
    return getRatings($response, $args);
});


    // Stefan
    $app->post('/rating', function($request, $response, $args) {
        //var_dump($request);
        //var_dump($response);
        //var_dump($args);
        $json_data = $request->getParsedBody();
        var_dump('$json_data: ');
        var_dump($json_data);
        var_dump('----------------');

        //fixed test values:
        $json_data['lat'] = (double) 4.444;             //necessary type conversion?    -> @DB: double
        $json_data['lng'] = (double) 8.888;
        var_dump( $json_data['lng']);
        var_dump($json_data);
        try {
            $db = getDBConnection($response);

            // check whether location already exists
            $checkLocation = $db->prepare("SELECT * 
                        FROM location
                        WHERE LOC_LAT = :latitude AND LOC_LNG = :longitude");
            $checkLocation->bindParam(':latitude', $json_data['lat']);
            $checkLocation->bindParam(':longitude', $json_data['lng']);
            var_dump('is it here?');
            var_dump($checkLocation->execute());
            var_dump($checkLocation);
            if($checkLocation->execute() > 0 ){
                //1.a) if yes, store the rating with the foreign key to that location
                $location = $checkLocation->fetch(PDO::FETCH_ASSOC);
                var_dump('or here?');
                var_dump($location);

                //Datenstruktur $location = array[] m. 3 El.
                $equal = ($location['LOC_LAT'] == $json_data['lat']);
                var_dump($equal);


                // -------------- moving on: -----------------

                //get LOC_ID
                $locID = $location['LOC_ID'];
                //prepare statement: TABLE rating
                $addRating = $db->prepare("
                    INSERT INTO rating (RAT_ID, RAT_TITLE, RAT_COMMENT, RAT_LOCATION_ID, RAT_POINTS, RAT_PICTURE_PATH)
                    VALUES (:ratID, :ratTitle, :ratComment, :ratLocationID, :ratPoints, :ratPicturePath);
                ");

                //get highest ratingPK -> increment it (development device):
                $incRatID = $db->prepare("
                SELECT MAX(rating.RAT_ID) FROM rating");
                $highestRatID = -1;
                var_dump($incRatID);
                var_dump($highestRatID);
                var_dump($incRatID->execute());
                if ($incRatID->execute()) {
                    $array = $incRatID->fetch(PDO::FETCH_ASSOC);
                    var_dump($array);
                    $highestRatID = $array['MAX(rating.RAT_ID)'] + 1;
                    var_dump($highestRatID);
                } else {
                    return $response->write('Error finding MAX(rating.RAT_ID) in database.')->withStatus(500);
                }
                var_dump($json_data['imgPath']);
                if (!$json_data['imgPath']){    //if(empty string) set to null
                    $json_data['imgPath'] = null;
                    var_dump($json_data['imgPath']);
                }
                $addRating->bindParam(":ratID", $highestRatID);
                $addRating->bindParam(":ratTitle", $json_data['ratTitle']);
                $addRating->bindParam(":ratComment", $json_data['ratText']);
                $addRating->bindParam(":ratLocationID", $locID);
                $addRating->bindParam(":ratPoints", $json_data['ratPoints']);
                $addRating->bindParam(":ratPicturePath", $json_data['imgPath']);

                var_dump($addRating);

                //ToDo if( statment->execute)
                if ($addRating->execute()) {
                    //$jsonData['id'] = $db->lastInsertId();

                    return $response->withJson($json_data, 201);
                }
                else {

                    return $response->write('Error inserting in database.')->withStatus(500);
                }




                return $response->withJson($location);

            } else {
                // 1.b) if no, store the rating AND the location,
                //		then save the LOC_ID as FK in RAT_LOCATION_ID
            }
        } catch (Exception $e){

            return $response->write($e->getMessage())->withStatus(503);
        }
        //

        return $response->withJson($json_data, 201);

        //return getRatings($response, $args);
    });



    function createRating($response, $query){

    }

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