<?php

  define('NSFW_URL', "http://static.mirador.im/test/nsfw.jpg");
  define('SFW_URL', "http://static.mirador.im/test/sfw.jpg");


  class ApiBaseTest extends PHPUnit_Framework_TestCase
  {

    /**
     * @expectedException Mirador\API\RequestException
     */
    public function testApiEdgeCases() {

      $client = new Mirador\API\Client(API_KEY);

      $client->classifyUrls(array(1, 2, 3));

    }

    /**
     * @expectedException Mirador\API\RequestException
     */
    public function testAuthenticationError() {

      $c = new Mirador\API\Client('bad-key');
      $c->classifyUrl('http://static.mirador.im/nsfw.jpg');

    }

    /**
     * @expectedException Mirador\API\RequestException
     */
    public function testBadRequest() {

      $c = new Mirador\API\Client('bad-key');
      $c->classifyUrl('http://taargus.taargus/nsfw.jpg');

    }

    public function testClassifyUrlsByArray() {

      $c = new Mirador\API\Client(API_KEY);
      $res = $c->classifyUrls(array('nsfw' => NSFW_URL, 'sfw' => SFW_URL));

      $this->assertEquals(2, count($res->results));
      $this->assertEquals('nsfw', $res->results['nsfw']->id);
      $this->assertEquals('sfw', $res->results['sfw']->id);

    }

    public function testChunkRequest() {
      $c = new Mirador\API\Client(API_KEY);

      // mock request; 
      $reqs = array_map(function ($i) {

        return array('id' => "$i-x", 'data' => NSFW_URL);

      }, range(0, (Mirador\API\Client::CHUNK_SIZE * 2) - 1));

      $out = $c->classifyUrls($reqs);

      $this->assertObjectHasAttribute('results', $out);
      $this->assertObjectHasAttribute('errors', $out);

      $this->assertEquals(count($out->results), $c::CHUNK_SIZE * 2);
      $this->assertEquals(count($out->errors), 0);

    }

    public function testUrlResults() {
      $c = new Mirador\API\Client(API_KEY);

      $res = $c->classifyUrls(NSFW_URL, SFW_URL);

      $this->assertObjectHasAttribute('results', $res);
      $this->assertEquals(count($res->results), 2);

      $this->assertEquals($res->results[NSFW_URL]->id, NSFW_URL);
      $this->assertEquals($res->results[SFW_URL]->id, SFW_URL);

      $nsfw = $res->results[NSFW_URL];
      $sfw = $res->results[SFW_URL];

      $this->assertGreaterThanOrEqual(0.50, $nsfw->value);
      $this->assertLessThan(0.50, $sfw->value);
    }

  }
