<?php


    require '../vendor/autoload.php';;

    /*
     * (a) Es gibt zwei Ressourcen: Die Ratings und die Locations.
     *
     *
     * (b) Das sind die URL's mit den entsprechenden Methoden:
     *
     *      Locations:
     *      Neue Lokation anlegen:      /api/locations      (POST)  // Hinweis: in $.ajax() zuerst aufrufen, danach ratings (POST)
     *      Lokation abfragen:          /api/location/id    (GET) //evtl überflüssig
     *
     *
     *      Ratings:
     *      Neue Bewertung anlegen:     /api/ratings        (POST)
     *      Bewertung abfragen:         /api/rating/id      (GET)
     *      Bewertungen abfragen:       /api/ratings        (GET)
     *
     *
     *
     *     BEMERKUNG: Damit dieser Code läuft, muss die Aufgabe 9 implementiert sein und die Datenbank bestehen.
     *
     * */

    define('QUERY_STRING', 'mysql:host=localhost:3306;dbname=hangloose;charset=utf8');
    define('DB_USER', 'TestAdmin');
    define('DB_PWD', 'webec');

    define('TIME_ZONE', 'UTC');
    define('DATE_FORMAT', 'd\.m\.Y');
    define('DATE_FIELD_NAME', 'date');

    $app = new \Slim\App();



    /**
     * Die SLIM Routes.
     */
    $id = array();

    $app->get('/ratings/{lat}/{lng}', function($request, $response, $args) {
        return getRatings($response, $args);
    });


//--------------------------------------------------------------------------------------------------
   $app->get('/film-quote/{id}', function($request, $response, $args) {

        return getRating($response, "SELECT * FROM quotes WHERE id = {$args['id']}");
    });

    $app->get('/random-film-quote', function($request, $response)  {

        return getRating($response, 'SELECT * FROM quotes ORDER BY RAND() LIMIT 0,1');
    });

    $app->post('/film-quotes', function($request, $response) {

        return createFilmQuote($response, $request->getParsedBody());
    });

    $app->put('/film-quote/{id}', function($request, $response, $args) {

        return updateFilmQuote($response, $request->getParsedBody(), $args['id']);
    });

    $app->delete('/film-quote/{id}', function($request, $response, $args) {

        return deleteFilmQuote($response, $args['id']);
    });

    /**
     * Gibt eine Datenbank-Verbindung zurück. Falls das nicht gelingt, wird die SLIM App mit einem
     * 503 Error gestoppt.
     *
     * @param $response  Das Response Object
     * @return PDO  Die Datenbank-Verbindung
     */
    function getDBConnection($response) {

        try {

            return new PDO(QUERY_STRING, DB_USER, DB_PWD);
        }
        catch(PDOException $e) {

            throw new Exception('Database connection could not be established.');
        }
    }

    function getRatings($response, $args) {
        try {
            $db = getDBConnection($response);
            $selectRatings = $db->prepare("SELECT * FROM rating INNER JOIN location ON rating.RAT_LOCATION_ID = location.LOC_ID WHERE location.LOC_LAT = {$args['lat']} AND location.LOC_LNG = {$args['lng']};");

            if($selectRatings->execute()) {

            }
        }
        catch(Exception $e) {

            return $response->write($e->getMessage())->withStatus(503);
        }
    }

    /**
     * Liest ensprechend dem Query aus der Datenbank.
     *
     * @param $response  Das Response Object
     * @param  Ein String mit einem gültigen SQL Query
     * @return Das Response Object
     */
    function getRating($response, $query) {

        try {

            $db = getDBConnection($response);

            $selectRating = $db->prepare($query);

            if($selectRating->execute()) {

                $ratings = $selectRating->fetch(PDO::FETCH_ASSOC);

                if($selectRating->rowCount() > 0) {

                    date_default_timezone_set(TIME_ZONE);
                    $ratings[DATE_FIELD_NAME] = date(DATE_FORMAT);

                    return $response->withJson($ratings);
                }
                else {

                    return $response->write('No film-quote found.')->withStatus(404);
                }
            }
            else {

                return $response->write('Error in quering database.')->withStatus(500);
            }
        }
        catch(Exception $e) {

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
    function bindParameters($preparedStatement, $jsonData) {

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
    function createFilmQuote($response, $jsonData) {

        try {

            $db = getDBConnection($response);

            $stmt = $db->prepare('INSERT INTO quotes (title, quote, movie_character, actor, year) values(:title, :quote, :movie_character, :actor, :year)');

            $stmt = bindParameters($stmt, $jsonData);

            if($stmt->execute() ) {

                $jsonData['id'] = $db->lastInsertId();

                return $response->withJson($jsonData, 201);
            }
            else {

                return $response->write('Error inserting in database.')->withStatus(500);
            }
        }
        catch(Exception $e) {

            return $response->write($e->getMessage())->withStatus(503);
        }
    }

    /**
     * Startet die SLIM App. Muss zuletzt im Skript aufgerufen werden.
     */
    $app->run();