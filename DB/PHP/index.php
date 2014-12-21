<?php

require_once './include/DbHandler.php';
require_once './include/PassHash.php';
require './libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();
$db_handler = new DbHandler();

// User id from db - Global Variable
$user_id = NULL;

/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */
function authenticate(\Slim\Route $route)
{
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();

    // Verifying Authorization Header
    if (isset($headers['Authorization'])) {
        global $db_handler;

        // get the api key
        $api_key = $headers['Authorization'];
        // validating api key
        if (!$db_handler->isValidApiKey($api_key)) {
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoRespnse(401, $response);
            $app->stop();
        } else {
            global $user_id;
            // get user primary key id
            $user_id = $db_handler->getUserId($api_key);
        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        echoRespnse(400, $response);
        $app->stop();
    }
}

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
$app->post('/register', function () use ($app) {
    global $db_handler;
    $response = array();

    // check for required params
    verifyRequiredParams(array('name', 'email', 'password'));

    // reading post params
    // required fields
    $name = $app->request->post('name');
    $email = $app->request->post('email');
    $password = $app->request->post('password');

    // optional fields - if empty, set to null
    $gender = $app->request->post('gender');
    $gender = $gender != '' ? $gender : NULL;
    $school = $app->request->post('school');
    $school = $school != '' ? $school : NULL;

    // validating email address
    validateEmail($email);

    $res = $db_handler->createUser($name, $email, $password, $gender, $school);

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
    echoRespnse(201, $response);
});

/**
 * User Login
 * url - /login
 * method - POST
 * params - email, password
 */
$app->post('/login', function () use ($app) {
    global $db_handler;

    // check for required params
    verifyRequiredParams(array('email', 'password'));

    // reading post params
    $email = $app->request()->post('email');
    $password = $app->request()->post('password');
    $response = array();

    // check for correct email and password
    if ($db_handler->checkLogin($email, $password)) {
        // get the user by email
        $userInfo = $db_handler->getUserByEmail($email);

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

    echoRespnse(200, $response);
});

/*
 * ------------------------ METHODS WITH AUTHENTICATION ------------------------
 */

/**
 * Listing geolocation of particual user
 * method GET
 * url /geolocation
 */
$app->get('/geolocation', 'authenticate', function () {
    global $user_id;
    global $db_handler;
    $response = array();

    // fetch geolocation
    $geolocation = $db_handler->getGeolocation($user_id);

    if ($geolocation != NULL) {
        $response["error"] = false;
        $response += $geolocation;
        echoRespnse(200, $response);
    } else {
        $response["error"] = true;
        $response["message"] = "The requested resource doesn't exists";
        echoRespnse(404, $response);
    }
});

/**
 * Setting geolocation
 * method PUT
 * url - /geolocation
 */
$app->put('/geolocation', 'authenticate', function () use ($app) {
    global $user_id;
    global $db_handler;

    // check for required params
    verifyRequiredParams(array('latitude', 'longitude', 'height'));

    $latitude = $app->request->put('latitude');
    $longitude = $app->request->put('longitude');
    $height = $app->request->put('height');
    $response = array();

    // updating task
    $result = $db_handler->setGeolocation($user_id, $latitude, $longitude, $height);
    if ($result) {
        // task updated successfully
        $response["error"] = false;
        $response["message"] = "Geolocation set successfully";
    } else {
        // task failed to update
        $response["error"] = true;
        $response["message"] = "Failed to set geolocation. Please try again!";
    }
    echoRespnse(200, $response);
});

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
        $app = \Slim\Slim::getInstance();
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
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * Validating email address
 */
function validateEmail($email)
{
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Email address is not valid';
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoRespnse($status_code, $response)
{
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');

    echo json_encode($response);
}

$app->run();