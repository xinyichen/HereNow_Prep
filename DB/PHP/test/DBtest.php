<?php
use Slim\Environment;

require_once '../vendor/autoload.php';
require_once "../src/App.php";

class DBTest extends  PHPUnit_Framework_TestCase {
    private $app;

    public function  setUp()
    {
        $_SESSION = array();
        $this->app = new App();
    }

//    public function testNotFound() {
//        Environment::mock(array(
//            'PATH_INFO' => '/not-exists'
//        ));
//        $response = $this->app->invoke();
//
//        $this->assertTrue($response->isNotFound());
//    }

    public function  testLogin() {
        Environment::mock(array(
            'REQUEST_METHOD' => 'POST',
            'PATH_INFO' => '/login',
            'slim.input' => 'email=hanyi%40hotmail.com&password=12345'
        ));
        $response = $this->app->invoke();
    }
}