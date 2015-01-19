<?php

use Slim\Slim;

require_once 'DbHandler.php';
require_once 'PassHash.php';

Slim::registerAutoloader();

class App extends Slim
{
    private $user_id = NULL; // User id from db
    private $db_handler;
    private $authenticate;

    public function __construct(array $userSettings = array())
    {
        parent::__construct($userSettings);
        $this->db_handler = new DbHandler();

        /**
         * Adding Middle Layer to authenticate every request
         * Checking if the request has valid api key in the 'Authorization' header
         */
        $this->authenticate = function (\Slim\Route $route) {
            // Getting request headers
            $headers = apache_request_headers();
            $response = array();
            $app = Slim::getInstance();

            // Verifying Authorization Header
            if (isset($headers['Authorization'])) {
                // get the api key
                $api_key = $headers['Authorization'];
                // validating api key
                if (!$this->db_handler->isValidApiKey($api_key)) {
                    // api key is not present in users table
                    $response["error"] = true;
                    $response["message"] = "Access Denied. Invalid Api key";
                    $this->echoResponse(401, $response);
                    $app->stop();
                } else {
                    // get user primary key id
                    $this->user_id = $this->db_handler->getUserId($api_key);
                }
            } else {
                // api key is missing in header
                $response["error"] = true;
                $response["message"] = "Api key is misssing";
                $this->echoResponse(400, $response);
                $app->stop();
            }
        };

        /**
         * ----------- METHODS WITHOUT AUTHENTICATION ---------------------------------
         */
        /**
         * User Registration
         * url - /register
         * method - POST
         * params - required: name, email, password
         *        - optional: gender, school
         */
        $this->post('/register', function () {
            $response = array();

            // check for required params
            $this->verifyRequiredParams(array('name', 'email', 'password'));

            // reading post params
            // required fields
            $name = $this->request->post('name');
            $email = $this->request->post('email');
            $password = $this->request->post('password');

            // optional fields - if empty, set to null
            $gender = $this->request->post('gender');
            $gender = $gender != '' ? $gender : NULL;
            $school = $this->request->post('school');
            $school = $school != '' ? $school : NULL;

            // validating email address
            $this->validateEmail($email);

            $res = $this->db_handler->createUser($name, $email, $password, $gender, $school);

            if ($res == USER_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "You are successfully registered";
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registereing";
            } else if ($res == USER_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this email already existed";
            }
            // echo json response
            $this->echoResponse(201, $response);
        });

        /**
         * User Login
         * url - /login
         * method - POST
         * params - email, password
         */
        $this->post('/login', function () {
            // check for required params
            $this->verifyRequiredParams(array('email', 'password'));

            // reading post params
            $email = $this->request()->post('email');
            $password = $this->request()->post('password');
            $response = array();

            // check for correct email and password
            if ($this->db_handler->checkLogin($email, $password)) {
                // get the user by email
                $userInfo = $this->db_handler->getUserByEmail($email);

                if ($userInfo != NULL) {
                    $response["error"] = false;
                    $response += $userInfo;
                } else {
                    // unknown error occurred
                    $response['error'] = true;
                    $response['message'] = "An error occurred. Please try again";
                }
            } else {
                // user credentials are wrong
                $response['error'] = true;
                $response['message'] = 'Login failed. Incorrect credentials';
            }

            $this->echoResponse(200, $response);
        });

        /**
         * ------------------------ METHODS WITH AUTHENTICATION ------------------------
         **/
        /**
         * Listing geolocation of particual user
         * method GET
         * url /geolocation
         */
        $this->get('/geolocation', $this->authenticate, function () {
            $response = array();

            // fetch geolocation
            $geolocation = $this->db_handler->getGeolocation($this->user_id);

            if ($geolocation != NULL) {
                $response["error"] = false;
                $response += $geolocation;
                $this->echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "The requested resource doesn't exists";
                $this->echoResponse(404, $response);
            }
        });

        /**
         * Setting geolocation
         * method PUT
         * url - /geolocation
         */
        $this->put('/geolocation', $this->authenticate, function () {
            // check for required params
            $this->verifyRequiredParams(array('latitude', 'longitude', 'height'));

            $latitude = $this->request->put('latitude');
            $longitude = $this->request->put('longitude');
            $height = $this->request->put('height');
            $response = array();

            // updating task
            $result = $this->db_handler->setGeolocation($this->user_id, $latitude, $longitude, $height);
            if ($result) {
                // task updated successfully
                $response["error"] = false;
                $response["message"] = "Geolocation set successfully";
            } else {
                // task failed to update
                $response["error"] = true;
                $response["message"] = "Failed to set geolocation. Please try again!";
            }
            $this->echoResponse(200, $response);
        });
    }

    /**
     * Validating email address
     */
    private function validateEmail($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response["error"] = true;
            $response["message"] = 'Email address is not valid';
            $this->echoResponse(400, $response);
            $this->stop();
        }
    }

    /**
     * Verifying required params posted or not
     */
    function verifyRequiredParams($required_fields)
    {
        $error = false;
        $error_fields = "";
        $request_params = array();
        $request_params = $_REQUEST;
        // Handling PUT request params
        if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
            $app = Slim::getInstance();
            parse_str($app->request()->getBody(), $request_params);
        }
        foreach ($required_fields as $field) {
            if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
                $error = true;
                $error_fields .= $field . ', ';
            }
        }

        if ($error) {
            // Required field(s) are missing or empty
            // echo error json and stop the app
            $response = array();
            $app = Slim::getInstance();
            $response["error"] = true;
            $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
            $this->echoResponse(400, $response);
            $app->stop();
        }
    }

    /**
     * Echoing json response to client
     * @param String $status_code Http response code
     * @param Int $response Json response
     */
    private function echoResponse($status_code, $response)
    {
        $app = Slim::getInstance();
        // Http response code
        $app->status($status_code);

        // setting response content type to json
        $app->contentType('application/json');

        echo json_encode($response);
    }

    /**
     * @return \Slim\Http\Response
     */
    public function invoke()
    {
        $this->middleware[0]->call();
        $this->response()->finalize();
        return $this->response();
    }
}