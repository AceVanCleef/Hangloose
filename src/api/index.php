<?php


    require '../vendor/autoload.php';;

    /*
     * (a) Es gibt zwei Ressourcen: Die Ratings.
     *
     *
     * (b) Das sind die URL's mit den entsprechenden Methoden:
     *
     *
     *
     *      Ratings:
     *      Neue Bewertung anlegen:     /api/ratings/{lat}/{long}        (POST)
     *      Bewertung abfragen:         /api/rating/id      (GET)
     *      Bewertungen abfragen:       /api/ratings        (GET)
     *
     *
     *
     *     BEMERKUNG: Damit dieser Code läuft, muss die Aufgabe 9 implementiert sein und die Datenbank bestehen.
     *
     * */

/*    define('QUERY_STRING', 'mysql:host=localhost:3306;dbname=hangloose;charset=utf8');
    define('DB_USER', 'TestAdmin');
    define('DB_PWD', 'webec');*/

    require('api_config.php');

    define('TIME_ZONE', 'UTC');
    define('DATE_FORMAT', 'd\.m\.Y');
    define('DATE_FIELD_NAME', 'date');

    $app = new \Slim\App();



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


    $app->get('/ratings', function($request, $response, $args) {

        $foo = $response->getParams();


        return $response->write($foo['lat']);
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
     * Updates eine bestehende Film-Quote.
     *
     * @param $response  Das Response Object
     * @param $jsonData  Die Film-Quote als JSON
     * @param $id Die ID der Film-Quote
     * @return Das Response Object
     */
    function updateFilmQuote($response, $jsonData, $id) {

        try {

            $db = getDBConnection($response);

            $stmt = $db->prepare("UPDATE quotes SET title=:title, quote=:quote, movie_character=:movie_character, actor=:actor, year=:year WHERE id={$id}");

            $stmt = bindParameters($stmt, $jsonData);

            if($stmt->execute()) {

                // Hier wird nicht unterschieden zwischen:
                // (i)  Keine Row gefunden mit dieser ID (HTTP Status 404) und
                // (ii) Keine Änderung in den Daten, 0 Rows affected (HTTP Status 200)
                //
                // Wie würden Sie das Implementieren?

                if($stmt->rowCount() > 0) {

                    // HTTP Status 200 wird automatisch gesetzt.
                    $jsonData['id'] = $id;

                    return $response->withJson($jsonData);
                }
                else {

                    return $response
                            ->write('No Film-Quote found to update (row not found, or data are up to date).')
                            ->withStatus(404);
                }
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
     * Löscht ein Film-Quote für eine gegebene ID.
     *
     * @param $response  Das Response Object
     * @param $id Die ID der Film-Quote
     * @return Das Response Object
     */
    function deleteFilmQuote($response, $id) {

        try {

            $db = getDBConnection($response);

            $stmt = $db->prepare("DELETE FROM quotes WHERE id = {$id}" );

            if($stmt->execute()) {

                if($stmt->rowCount() > 0) {

                    return $response->withJSON(array('id' => $id));
                }
                else {

                    return $response->write('Film-Quote not found.')->withStatus(404);
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
     * Startet die SLIM App. Muss zuletzt im Skript aufgerufen werden.
     */
    $app->run();