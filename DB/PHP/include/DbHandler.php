<?php

require_once 'DbConnect.php';
require_once 'PassHash.php';

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 * @link URL Tutorial link
 */
class DbHandler
{

    private $conn;

    function __construct()
    {
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    /* ------------- `users` table method ------------------ */

    /**
     * Creating new user
     * @param String $name User full name
     * @param String $email User login email id
     * @param String $password User login password
     * @param String $gender User gender
     * @param String $school User school
     * @return int
     */
    public function createUser($name, $email, $password, $gender, $school)
    {
        // First check if user already existed in db
        if ($this->isUserExists($email)) {
            // User with same email already existed in the db
            return USER_ALREADY_EXISTED;
        }

        // Generating password hash
        $password_hash = PassHash::hash($password);
        // Generating API key
        $api_key = $this->generateApiKey();

        // insert query
        $query = "INSERT INTO users(name, email, password_hash, gender, school, api_key) VALUES(?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('ssssss', $name, $email, $password_hash, $gender, $school, $api_key);
        $result = $stmt->execute();

        $stmt->close();

        return $result ? USER_CREATED_SUCCESSFULLY : USER_CREATE_FAILED;
    }

    /**
     * Checking user login
     * @param String $email User login email id
     * @param String $password User login password
     * @return boolean User login status success/fail
     */
    public function checkLogin($email, $password)
    {
        // fetching user by email
        $query = "SELECT password_hash FROM users WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($password_hash);
        $stmt->store_result();

        if ($stmt->num_rows <= 0) { // user not existed with the email
            $stmt->close();
            return FALSE;
        }

        // Found user with the email
        // Now verify the password
        $stmt->fetch();
        $stmt->close();

        return PassHash::check_password($password_hash, $password);
    }

    /**
     * Checking for duplicate user by email address
     * @param String $email email to check in db
     * @return boolean
     */
    private function isUserExists($email)
    {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Fetching user by email
     * @param String $email User email id
     * @return array|null
     */
    public function getUserByEmail($email)
    {
        $query = "SELECT name, email, gender, school, api_key, created_at FROM users WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $email);

        if (!$stmt->execute()) {
            return NULL;
        }

        // $user = $stmt->get_result()->fetch_assoc();
        $stmt->bind_result($name, $email, $gender, $school, $api_key, $created_at);
        $stmt->fetch();
        $user = array();
        $user["name"] = $name;
        $user["email"] = $email;
        $user["gender"] = $gender;
        $user["school"] = $school;
        $user["api_key"] = $api_key;
        $user["created_at"] = $created_at;
        $stmt->close();

        return $user;
    }

    /**
     * Fetching user id by api key
     * @param String $api_key user api key
     * @return user_id associated with api key. otherwise, null
     */
    public function getUserId($api_key) {
        $query = "SELECT id FROM users WHERE api_key = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $api_key);

        if (!$stmt->execute()) {
            return NULL;
        }

        $stmt->bind_result($user_id);
        $stmt->fetch();
        $stmt->close();
        return $user_id;
    }

    /**
     * Validating user api key
     * If the api key is there in db, it is a valid key
     * @param String $api_key user api key
     * @return boolean
     */
    public function isValidApiKey($api_key) {
        $query = "SELECT id from users WHERE api_key = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();

        return $num_rows > 0;
    }

    /**
     * Generating random Unique MD5 String for user Api key
     */
    private function generateApiKey()
    {
        return md5(uniqid(rand(), true));
    }

    /* ------------- `geolocation` table method ------------------ */
    public function setGeolocation($user_id, $latitude, $longitude, $height)
    {
        $query = "INSERT INTO geolocation(user_id, latitude, longitude, height, updated_at)
                  VALUES(?, ?, ?, ?, CURRENT_TIMESTAMP)
                  ON DUPLICATE KEY
                  UPDATE latitude=VALUES(latitude), longitude=VALUES(longitude),
                          height=VALUES(height), updated_at=CURRENT_TIMESTAMP";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iddd", $user_id, $latitude, $longitude, $height);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    public function getGeolocation($user_id)
    {
        $query = "SELECT latitude, longitude, height FROM geolocation WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $user_id);

        if (!$stmt->execute()) {
            return NULL;
        }

        $result = array();
        $stmt->bind_result($latitude, $longitude, $height);
        $stmt->fetch();
        $result["latitude"] = $latitude;
        $result["longitude"] = $longitude;
        $result["height"] = $height;
        $stmt->close();

        return $result;
    }
}