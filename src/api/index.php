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
    $app->post('/rating', function($request, $response) {
        $json_data = $request->getParsedBody();
        var_dump('$json_data: ');
        var_dump($json_data);
        var_dump('----------------');

        //fixed test values:
        $json_data['lat'] = (double) 2.111;             //necessary type conversion?    -> @DB: double
        $json_data['lng'] = (double) 2.222;
        var_dump( $json_data['lng']);
        var_dump($json_data);

        //ToDo: replace $json_data with $request->getParsedBody()
        return createRating($response, $json_data);


        //return getRatings($response, $args);
    });

    /** checks whether a location already exists in the DB.
     * @param $stmt PDO query statement
     * @return bool location does or does not exist.
     */
    function checkLocation($stmt) {
        var_dump("-> entering checkLocation");
        // check whether location already exists
        var_dump($stmt->execute());
        $stmt->execute();
        var_dump($stmt);
        var_dump('is it here?');
        var_dump($stmt->rowCount() > 0 );
        // check whether location already exists
        return $stmt->rowCount() > 0;
    }

    /** returns a statement with lat and lng bound to it.
     * @param $stmt PDO prepared statement
     * @param $json_data  PDO   HTTP body data
     * @return mixed    statement with bound params
     */
    function bindLatAndLng($stmt, $json_data){
        $stmt->bindParam(':latitude', $json_data['lat']);
        $stmt->bindParam(':longitude', $json_data['lng']);
        return $stmt;
    }

    /** returns RAT_ID of a new rating.
     * @param $response
     * @param $db   PDO connection obj to DB.
     * @return mixed RAT_ID Primary Key
     */
    function prepareRatID_PK($response, $db) {
        var_dump("-> prepareRatID_PK()");
        $highestRatID = 0;
        $stmt = $db->prepare("
                SELECT MAX(rating.RAT_ID) FROM rating");
        var_dump($stmt);
        var_dump($stmt->execute());
        if ($stmt->execute()) {
            $array = $stmt->fetch(PDO::FETCH_ASSOC);
            var_dump($array);
            $highestRatID = $array['MAX(rating.RAT_ID)'];
            var_dump($highestRatID);
        } else {
            return $response->write('Error finding MAX(rating.RAT_ID) in database.')->withStatus(500);
        }
        return ++$highestRatID;
    }

    /** returns the image url unchanged or replaces empty url strings with null.
     * @param $string   String image url
     * @return null
     */
    function replaceEmptyStr($string) {
        var_dump("-> replaceEmptyStr()");
        var_dump($string);
        if (!$string){    //if(empty string) set to null
            $string = null;
            var_dump($string);
        }
        return $string;
    }

    function prepareInsertRating($db) {
        var_dump("-> prepareInsertRating()");
        return $db->prepare("
                    INSERT INTO rating (RAT_ID, RAT_TITLE, RAT_COMMENT, RAT_LOCATION_ID, RAT_POINTS, RAT_PICTURE_PATH)
                    VALUES (:ratID, :ratTitle, :ratComment, :ratLocationID, :ratPoints, :ratPicturePath);
                ");
    }

    function bindRatingParams($stmt, $json_data, $ratID_PK, $locID_FK) {
        $stmt->bindParam(":ratID", $ratID_PK);
        $stmt->bindParam(":ratTitle", $json_data['ratTitle']);
        $stmt->bindParam(":ratComment", $json_data['ratText']);
        $stmt->bindParam(":ratLocationID", $locID_FK);
        $stmt->bindParam(":ratPoints", $json_data['ratPoints']);
        $stmt->bindParam(":ratPicturePath", $json_data['imgPath']);
        return $stmt;
    }

    function createRating($response, $json_data){
        var_dump("entering createRating");
        try {
            $db = getDBConnection($response);

            $checkLocQuery = $db->prepare("SELECT *
                        FROM location
                        WHERE LOC_LAT = :latitude AND LOC_LNG = :longitude");
            $checkLocQuery = bindLatAndLng($checkLocQuery, $json_data);


            // check whether location already exists
            if( checkLocation($checkLocQuery) ){
                //1.a) if yes, store the rating with the foreign key to that location
                $location = $checkLocQuery->fetch(PDO::FETCH_ASSOC);
                var_dump('or here?');
                var_dump($location);
                //get LOC_ID
                $locID_FK = $location['LOC_ID'];
                var_dump($locID_FK);

                //prepare statement: TABLE rating
                $insertRatingQuery = prepareInsertRating($db);
                var_dump($insertRatingQuery);

                //get highest ratingID -> is new Primary Key:
                $ratID_PK = prepareRatID_PK($response, $db);
                var_dump($ratID_PK);

                $json_data['imgPath'] = replaceEmptyStr($json_data['imgPath']);
                var_dump($json_data['imgPath']);

                $insertRatingQuery = bindRatingParams($insertRatingQuery, $json_data, $ratID_PK, $locID_FK);
                var_dump($insertRatingQuery);

                if ($insertRatingQuery->execute()) {

                    return $response->withJson($json_data, 201);
                }
                else {

                    return $response->write('Error inserting in database.')->withStatus(500);
                }




            } else {
                // 1.b) if no, store the rating AND the location,
                //		then save the LOC_ID as FK in RAT_LOCATION_ID

                var_dump("location doesn't exist");

                // find highest LOC_ID and RAT_ID -> inc them
                $incRatID = $db->prepare("
                SELECT MAX(rating.RAT_ID) FROM rating");
                $incLocID = $db->prepare("
                SELECT MAX(location.LOC_ID) FROM location");
                $highestRatID = 0; $highestLocID = 0;
                var_dump($incRatID);
                var_dump($highestRatID);
                $incRatID->execute();
                var_dump($incRatID->execute());
                var_dump($incLocID);
                // -- loc --
                var_dump($highestLocID);
                $incLocID->execute();
                var_dump($incLocID->execute());
                var_dump("breakpoint charlie");
                var_dump($incRatID->rowCount() > 0 && $incLocID->rowCount() > 0);
                if ($incRatID->rowCount() > 0 && $incLocID->rowCount() > 0) {
                    $arrayRat = $incRatID->fetch(PDO::FETCH_ASSOC);
                    var_dump($arrayRat);
                    $highestRatID = $arrayRat['MAX(rating.RAT_ID)'] + 1;
                    var_dump($highestRatID);
                    //location
                    $arrayLoc = $incLocID->fetch(PDO::FETCH_ASSOC);
                    var_dump($arrayLoc);
                    $highestLocID = $arrayLoc['MAX(location.LOC_ID)'] + 1;
                    var_dump($highestLocID);
                } else {
                    return $response->write('Error finding MAX(*.*_ID) in database.')->withStatus(500);
                }

                // if no, store the rating AND the location
                //1st: location
                $addLocation = $db->prepare("
                    INSERT INTO location (LOC_ID, LOC_LAT, LOC_LNG)
                    VALUES (:locID, :latitude, :longitude);
                ");
                $addLocation->bindParam(":locID", $highestLocID);
                $addLocation->bindParam(":latitude", $json_data['lat']);
                $addLocation->bindParam(":longitude", $json_data['lng']);

                //2nd: rating
                $insertRatingQuery = $db->prepare("
                    INSERT INTO rating (RAT_ID, RAT_TITLE, RAT_COMMENT, RAT_LOCATION_ID, RAT_POINTS, RAT_PICTURE_PATH)
                    VALUES (:ratID, :ratTitle, :ratComment, :ratLocationID, :ratPoints, :ratPicturePath);
                ");

                $insertRatingQuery->bindParam(":ratID", $highestRatID);
                $insertRatingQuery->bindParam(":ratTitle", $json_data['ratTitle']);
                $insertRatingQuery->bindParam(":ratComment", $json_data['ratText']);
                $insertRatingQuery->bindParam(":ratLocationID", $highestLocID);
                $insertRatingQuery->bindParam(":ratPoints", $json_data['ratPoints']);
                $insertRatingQuery->bindParam(":ratPicturePath", $json_data['imgPath']);

                var_dump($addLocation);
                var_dump($insertRatingQuery);
                var_dump($addLocation->execute() && $insertRatingQuery->execute());

                if($addLocation->execute() && $insertRatingQuery->execute()){
                    var_dump("got in here");
                    return $response->withJson($jsonData, 201);
                } else {
                    return $response->write('Error inserting in database.')->withStatus(500);
                }

            }
        } catch (Exception $e){

            return $response->write($e->getMessage())->withStatus(503);
        }
        //

        return $response->withJson($json_data, 201);

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