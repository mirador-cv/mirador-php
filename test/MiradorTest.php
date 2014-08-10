<?php
  require_once 'vendor/autoload.php';

  class MiradorTest extends PHPUnit_Framework_TestCase
  {

    public function testCalls($callable, $type, $path, $params, $which_mock = '')
    {
      $client = new Mirador\API\Client(API_KEY);
      $callable($client);
    }

  }
