<?php
  namespace Mirador\API;

  class RequestException extends \Exception
  {

    public function __construct($res, $code = NULL, $msg = NULL)
    {

      if ($code != NULL) {

        $this->statusCode = intval($code);

        $this->message = is_string($msg) ? $msg : 'unexpected exception';

        parent::__construct($this->message, $this->statusCode);
        return;
      }

      try {
        $err = (object) $res->json();
      }

      catch (\GuzzleHttp\Exception\ParseException $e) {

        $err = (object) array('errors' => 'unexpected error');

      }

      if (isset($err->errors) && $err->errors != NULL)
      {
        if (is_array($err->errors)) {
          $this->message = implode(',', $err->errors);
        }

        else if (is_object($err->errors)) {
          $this->message = "" . $err->errors . "";
        }

        else {
          $this->message = $err->errors;
        }
      }

      else
      {
        $this->message = 'Unexpected Error';
      }


      $this->statusCode = $res->getStatusCode();
      parent::__construct($this->message, $this->statusCode);
    }

    public function __toString()
    {
      return __CLASS__ . ": [$this->statusCode]: $this->message\n";
    }
  }
