<?php

  define('NSFW_FILE', __DIR__ . '/images/nsfw.jpg');
  define('SFW_FILE', __DIR__ . '/images/sfw.jpg');

  class SplMockContents extends SplFileInfo {

    public function getContents() {
      return file_get_contents($this->getPathname());
    }

  }

  class FileClassificationTest extends PHPUnit_Framework_TestCase {

    public function testFilesByFilename() {
      $c = new Mirador\API\Client(API_KEY);

      $r = $c->classifyFiles(NSFW_FILE, SFW_FILE);

      $this->assertObjectHasAttribute('results', $r);
      $this->assertEquals(2, count($r->results));
      $this->assertEquals(NSFW_FILE, $r->results[NSFW_FILE]->id);
      $this->assertGreaterThanOrEqual(0.50, $r->results[NSFW_FILE]->value);

    }

    public function testFilesWithIds() {
      $c = new Mirador\API\Client(API_KEY);

      $r = $c->classifyFiles(array('nsfw' => NSFW_FILE, 'sfw' => SFW_FILE));

      $this->assertObjectHasAttribute('results', $r);
      $this->assertEquals(2, count($r->results));
      $this->assertEquals('nsfw', $r->results['nsfw']->id);
      $this->assertGreaterThanOrEqual(0.50, $r->results['nsfw']->value);
    }

    public function testFileWithId() {

      $c = new Mirador\API\Client(API_KEY);

      $r = $c->classifyFile(array('nsfw' => NSFW_FILE));

      $this->assertEquals('nsfw', $r->id);
      $this->assertGreaterThanOrEqual(0.50, $r->value);

    }

    public function testFilesWithMockObj() {

      $c = new Mirador\API\Client(API_KEY);

      $finfo = new SplMockContents(NSFW_FILE);
      $r = $c->classifyFiles($finfo, SFW_FILE);

      $this->assertObjectHasAttribute('results', $r);
      $this->assertEquals(2, count($r->results));
      $this->assertEquals(NSFW_FILE, $r->results[NSFW_FILE]->id);
      $this->assertGreaterThanOrEqual(0.50, $r->results[NSFW_FILE]->value);


    }

    public function testFilesBySplFileInfo() {

      $c = new Mirador\API\Client(API_KEY);

      $finfo = new SplFileInfo(NSFW_FILE);
      $r = $c->classifyFiles($finfo, SFW_FILE);

      $this->assertObjectHasAttribute('results', $r);
      $this->assertEquals(2, count($r->results));
      $this->assertEquals(NSFW_FILE, $r->results[NSFW_FILE]->id);
      $this->assertGreaterThanOrEqual(0.50, $r->results[NSFW_FILE]->value);


    }

    public function testFilesBySplFileObject() {

      $c = new Mirador\API\Client(API_KEY);

      $fobject = (new SplFileInfo(NSFW_FILE))->openFile();
      $r = $c->classifyFiles($fobject, SFW_FILE);

      $this->assertObjectHasAttribute('results', $r);
      $this->assertEquals(2, count($r->results));
      $this->assertEquals(NSFW_FILE, $r->results[NSFW_FILE]->id);
      $this->assertGreaterThanOrEqual(0.50, $r->results[NSFW_FILE]->value);

    }

    public function testFilesWithBuffer() {
      $c = new Mirador\API\Client(API_KEY);

      $nbuf = file_get_contents(NSFW_FILE);
      $sbuf = file_get_contents(SFW_FILE);

      $r = $c->classifyBuffers($nbuf, $sbuf);

      $this->assertEquals(2, count($r->results));
      $this->assertEquals(0, $r->results[0]->id);
      $this->assertGreaterThanOrEqual(0.50, $r->results[0]->value);

    }

    public function testBuffersWithIds() {

      $c = new Mirador\API\Client(API_KEY);

      $nbuf = file_get_contents(NSFW_FILE);
      $sbuf = file_get_contents(SFW_FILE);

      $r = $c->classifyBuffers(array('nsfw' => $nbuf, 'sfw' => $sbuf));

      $this->assertEquals(2, count($r->results));
      $this->assertEquals('nsfw', $r->results['nsfw']->id);
      $this->assertGreaterThanOrEqual(0.50, $r->results['nsfw']->value);

    }

    public function testDataUris() {

      $c = new Mirador\API\Client(API_KEY);

      $nduri = 'data:image/jpg;base64,' . base64_encode(file_get_contents(NSFW_FILE));

      $r = $c->classifyDataUris(array('nsfw' => $nduri));

      $this->assertEquals(1, count($r->results));
      $this->assertEquals('nsfw', $r->results['nsfw']->id);
      $this->assertGreaterThanOrEqual(0.50, $r->results['nsfw']->value);


    }

  }
