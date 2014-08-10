<?php

  namespace Mirador\API;

  // helper function mostly just to make code
  // more readable
  function cFn($inst, $fname) {
    return array($inst, $fname);
  }


  class Client
  {
    use ArgumentProcessing;

    private $apikey;
    private $http;
    private $encoder;

    // for chunking requests
    const CHUNK_SIZE = 3;
    const MAX_KEY_SIZE = 256;

    const REQ_VERB = 'POST';
    const API_ENDPOINT = 'http://api.mirador.im/v1/classify';

    // constants for datatypes
    const URL_DTYPE = "url";
    const FILE_DTYPE = "file";
    const BUFFER_DTYPE = "buffer";
    const DATAURI_DTYPE = "datauri";


    /**
     * Create a new Client
     *
     * @param string $apikey  the api key
     */
    public function __construct($apikey) {

      $this->apikey = $apikey;
      $this->http = new \GuzzleHttp\Client();
      $this->encoder = new ItemEncoder();

    }

    /**
     * Classify Urls
     *
     * @param [Array|String,..] arrays of 'id' => 'url' or url string(s)
     * @return Object with arays $object->results and $object->errors
     */
    public function classifyUrls() {

      return $this->callApi(self::URL_DTYPE, func_get_args());

    }

    /**
     * Classify Files
     *
     * @param [Array|String,..] arrays of 'id' => 'file' or file(s) where
     *  a file can be a filename, SplFileInfo or subclass
     * @return Object with arays $object->results and $object->errors
     */
    public function classifyFiles() {

      return $this->callApi(self::FILE_DTYPE, func_get_args());

    }

    /**
     * Classify Buffers
     *
     * @param [Array|String,..] arrays of 'id' => 'buffer' or buffer(s) where
     *  a buffer is the contents of an image file
     * @return Object with arays $object->results and $object->errors
     */
    public function classifyBuffers() {

      return $this->callApi(self::BUFFER_DTYPE, func_get_args());

    }

    /**
     * Classify Data URIs
     *
     * @param [Array|String,..] arrays of 'id' => 'data uri' or data uri(s) where
     *  data uris have a image/* content-type and are base64-encoded
     * @return Object with arays $object->results and $object->errors
     */
    public function classifyDataUris() {

      return $this->callApi(self::DATAURI_DTYPE, func_get_args());

    }

    /**
     * Shortcut for a single classification
     *
     * Call the name of another method (e.g., classifyUrls) as 
     * singular (classifyUrl) to automatically retrieve result or
     * throw any error directly.
     */
    public function __call($fn, $args) {

      $mul = $fn . "s";

      if (method_exists($this, $mul)) {

        $r = $this->$mul($args);

        if (count($r->results) > 0) {
          return array_shift($r->results);
        }

        throw array_shift($r->errors);
      }

    }

    protected function doRequest($datatype, $items) {

      $req = $this->http->createRequest(
        self::REQ_VERB, self::API_ENDPOINT, array(
        'body' => array(
          'api_key' => $this->apikey,
          $this->encoder->map($datatype) => $items

        )));

      try {


        $res = $this->http->send($req);
        $status = $res->getStatusCode();

        if ($status > 299 || $status < 200) {
          return array('errors' => array(new RequestException($res)));
        }

        $b = $res->json();

        if (!isset($b['results']) && !isset($b['errors'])) {

          $b['errors'] = array(new RequestException(NULL, 500, "unexpected error"));

        }

        return $b;
      }

      catch (\GuzzleHttp\Exception\RequestException $e) {

        if ($e->hasResponse()) {
          return array('errors' => array(new RequestException($e->getResponse())));
        }

        return array('errors' => array(new RequestException(NULL, 400, $e->getMessage())));
      }

    }

    // chunk a request into CHUNK_SIZE parts
    protected function chunkRequest($datatype, $items) {

      $results = array();
      $errors = array();

      foreach (array_chunk($items, self::CHUNK_SIZE) as $chunk) {

        $res = $this->doRequest($datatype, $chunk);

        if (!isset($res['results'])) {
          $errors = array_merge($errors, $res['errors']);
        }

        else {

          foreach ($res['results'] as $r) {
            $results[$r['id']] = Result::fromJson($r);
          }

        }

      }

      return (object) array('results' => $results, 'errors' => $errors);
    }

    protected function callApi($datatype, $items) {

      $reqs = $this->processArguments($datatype, $items);

      if (!$reqs || count($reqs) == 0) {
        throw new RequestException(NULL, 400, "invalid request: no items!");
      }

      return $this->chunkRequest($datatype, $reqs);
    }

  }
