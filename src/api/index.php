<?php


require '../vendor/autoload.php';
require('api_config.php');

/**
 * REST
 *
 *      Neue Bewertung anlegen:     /api/rating             (POST)
 *      Bewertungen abfragen:       /api/ratings/lat/lng    (GET)
 *
 */


$app = new \Slim\App();


/**
 * Die SLIM Routes.
 */

$app->get('/ratings/{lat}/{lng}', function ($request, $response, $args) {
    return getRatings($response, $args);
});


$app->post('/rating', function ($request, $response) {

    $json_data = json_decode($request->getParam('jsonDataObj'), true);

    if (count($request->getUploadedFiles()) > 0) {
        $file = $request->getUploadedFiles()["image"];

        $imgName = addPicture($file);

        $json_data['imgPath'] = $imgName;
    }

    return createRating($response, $json_data);
});

/** checks whether a location already exists in the DB.
 * @param $stmt PDO query statement
 * @return bool location does or does not exist.
 */
function checkLocation($stmt)
{
    $stmt->execute();
    return $stmt->rowCount() > 0;
}

/** returns a statement with lat and lng bound to it.
 * @param $stmt PDO prepared statement
 * @param $json_data  PDO   HTTP body data
 * @return mixed    statement with bound params
 */
function bindLatAndLng($stmt, $json_data)
{
    $stmt->bindParam(':latitude', $json_data['lat']);
    $stmt->bindParam(':longitude', $json_data['lng']);
    return $stmt;
}

/** returns RAT_ID of a new rating.
 * @param $response
 * @param $db   PDO connection to DB.
 * @return mixed RAT_ID primary key
 */
function prepareRatID_PK($response, $db)
{
    $highestRatID = 0;
    $stmt = $db->prepare("
                SELECT MAX(rating.RAT_ID) FROM rating");

    if ($stmt->execute()) {
        $array = $stmt->fetch(PDO::FETCH_ASSOC);
        $highestRatID = $array['MAX(rating.RAT_ID)'];
    } else {
        return $response->write('Error finding MAX(rating.RAT_ID) in database.')->withStatus(500);
    }
    return ++$highestRatID;
}


/** returns LOC_ID of a new location.
 * @param $response
 * @param $db PDO connection to DB
 * @return mixed LOC_ID primary key
 */
function prepareLocID_PK($response, $db)
{
    $highestLocID = 0;
    $stmt = $db->prepare("
                SELECT MAX(location.LOC_ID) FROM location");
    if ($stmt->execute()) {
        $array = $stmt->fetch(PDO::FETCH_ASSOC);
        $highestLocID = $array['MAX(location.LOC_ID)'];
    } else {
        return $response->write('Error finding MAX(rating.RAT_ID) in database.')->withStatus(500);
    }
    return ++$highestLocID;
}

/** returns the image url unchanged or replaces empty url strings with null.
 * @param $string   String image url
 * @return null
 */
function replaceEmptyStr($string)
{
    if (!$string) {    //if(empty string) set to null
        $string = null;
    }
    return $string;
}


/** returns a PDO prepared statement insert query.
 * @param $db PDO connection to DB.
 * @return mixed statement
 */
function prepareInsertRating($db)
{
    return $db->prepare("
                    INSERT INTO rating (RAT_ID, RAT_TITLE, RAT_COMMENT, RAT_LOCATION_ID, RAT_POINTS, RAT_PICTURE_PATH)
                    VALUES (:ratID, :ratTitle, :ratComment, :ratLocationID, :ratPoints, :ratPicturePath);
                ");
}

/** binds parameters to PDO insert statement and returns the statement.
 * @param $stmt PDO statement
 * @param $json_data
 * @param $ratID_PK String RAT_ID
 * @param $locID_FK String LOC_ID
 * @return mixed PDO statement with bound parameters.
 */
function bindRatingParams($stmt, $json_data, $ratID_PK, $locID_FK)
{
    $stmt->bindParam(":ratID", $ratID_PK);
    $stmt->bindParam(":ratTitle", $json_data['ratTitle']);
    $stmt->bindParam(":ratComment", $json_data['ratText']);
    $stmt->bindParam(":ratLocationID", $locID_FK);
    $stmt->bindParam(":ratPoints", $json_data['ratPoints']);
    $stmt->bindParam(":ratPicturePath", $json_data['imgPath']);
    return $stmt;
}


/** creates a database rating entry and checks if the corresponding location already exists.
 *  If not, a location entry will also be created.
 * @param $response mixed Slim response obj.
 * @param $json_data mixed HTTP body content
 * @return mixed
 */
function createRating($response, $json_data)
{
    try {
        $db = getDBConnection($response);

        $checkLocQuery = $db->prepare("SELECT *
                        FROM location
                        WHERE LOC_LAT = :latitude AND LOC_LNG = :longitude");
        $checkLocQuery = bindLatAndLng($checkLocQuery, $json_data);


        // check whether location already exists
        if (checkLocation($checkLocQuery)) {
            // location exists. Store the rating with the foreign key pointing to that location
            $location = $checkLocQuery->fetch(PDO::FETCH_ASSOC);
            $locID_FK = $location['LOC_ID'];

            $insertRatingQuery = prepareInsertRating($db);
            $ratID_PK = prepareRatID_PK($response, $db);
            $insertRatingQuery = bindRatingParams($insertRatingQuery, $json_data, $ratID_PK, $locID_FK);

            if ($insertRatingQuery->execute()) {
                return $response->withJson($json_data, 201);
            } else {
                return $response->write('Error inserting in database.')->withStatus(500);
            }
        } else {
            // location doesn't exist.Store the rating AND the location,
            //		then save the LOC_ID as FK in RAT_LOCATION_ID
            $locID_PK = prepareLocID_PK($response, $db);
            $ratID_PK = prepareRatID_PK($response, $db);

            //1st: location
            $insertLocationQuery = $db->prepare("
                    INSERT INTO location (LOC_ID, LOC_LAT, LOC_LNG)
                    VALUES (:locID, :latitude, :longitude);
                ");
            $insertLocationQuery = bindLocationParams($insertLocationQuery, $json_data, $locID_PK);

            //2nd: rating
            $insertRatingQuery = prepareInsertRating($db);
            $insertRatingQuery = bindRatingParams($insertRatingQuery, $json_data, $ratID_PK, $locID_PK);


            if ($insertLocationQuery->execute() && $insertRatingQuery->execute()) {
                return $response->withJson($json_data, 201);
            } else {
                return $response->write('Error inserting in database.')->withStatus(500);
            }
        }
    } catch (Exception $e) {
        return $response->write($e->getMessage())->withStatus(503);
    }
}

/** binds parameters to PDO statement and returns it.
 * @param $stmt PDO statement
 * @param $json_data
 * @param $locID_PK String LOC_ID
 * @return mixed statement with bound params.
 */
function bindLocationParams($stmt, $json_data, $locID_PK)
{
    $stmt->bindParam(":locID", $locID_PK);
    $stmt->bindParam(":latitude", $json_data['lat']);
    $stmt->bindParam(":longitude", $json_data['lng']);
    return $stmt;
}


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
 * saves an image file to a predefined directory on the server.
 * @param $file mixed jpg, jpeg, png, gif
 * @return null|string
 */
function addPicture($file)
{

    $target_dir = "../img/ratings/";

    if (count(scandir($target_dir)) > 3) { // 2 cause of . and ..
        $lastImgIndex = (int)pathinfo(scandir($target_dir, SCANDIR_SORT_DESCENDING)[0])['filename'];
        $lastImgIndex++;
    } else {
        $lastImgIndex = 0;
    }


    // Check if image file is a actual image or fake image
    $check = $file->getSize();
    if ($check == false) {
        return null;
    }

    $imageFileType = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);

    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
        && $imageFileType != "gif"
    ) {
        return null;
    }

    $name = (string)$lastImgIndex . '.' . $imageFileType;

    try {
        $file->moveTo($target_dir . $name);
        return $name;
    } catch (Exception $e) {
        return null;
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

            if ($selectRatings->rowCount() > 0) {
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
 * Startet die SLIM App. Muss zuletzt im Skript aufgerufen werden.
 */
$app->run();
