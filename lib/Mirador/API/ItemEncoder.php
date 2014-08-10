<?php

  namespace Mirador\API;

  class ItemEncoder {

    private static $SPI_CLASS = 'SplFileInfo';
    private static $SPO_CLASS = 'SplFileObject';

    private static $TYPEMAP = array(
      'file' => 'image',
      'buffer' => 'image',
      'datauri' => 'image',
      'raw' => 'image',
      'url' => 'url',
    );

    public function __construct() {

    }

    public function __call($datatype, $args) {

      $mthd = "process_$datatype";

      if (!method_exists($this, $mthd)) {
        throw new RequestException(NULL, 400, "invalid data type: `$datatype`");
      }

      if (!$args) {
        throw new RequestException(NULL, 400, "no arguments provided");
      }

      return call_user_func(array($this, $mthd), $args[0]);
    }

    public function map($datatype) {
      return self::$TYPEMAP[$datatype];
    }

    private function encodeData($data) {
      return base64_encode($data);
    }

    private function process_url($item) {

      if (strstr($item, 'http') === false) {
        throw new RequestException(
          NULL, 400,
          "invalid url: `$item` Must include protocol (http/https)"
        );
      }

      return $item;
    }

    private function process_buffer($item) {
      return $this->encodeData($item);
    }

    private function process_datauri($item) {
      $cleaned = preg_replace('/^.+;base64,/', '', $item);

      if ($cleaned == NULL || strlen($cleaned) == strlen($item)) {
        throw new RequestException(NULL, 400, "invalid data uri: must be base64 encoded");
      }

      // we just use 'cleaned'
      return $cleaned;
    }

    private function process_file($item) {
      $contents = '';

      // we want to take a file object, file info object,
      // or a filename
      if ($item instanceof self::$SPI_CLASS) {

        // seek back to 0 if we have an open file
        if ($item instanceof self::$SPO_CLASS) {
          $item->rewind();
        }

        // check for methods that might simplify reading
        // the contents of the file

        // @symfony subclass
        if (method_exists($item, 'getContents')) {

          $contents = $item->getContents();

        }

        else if (method_exists($item, 'fread')) {

          $contents = $item->fread();

        }

        else {

          // getContents() shim, borrowed from symfony source
          $contents = file_get_contents($item->getPathname());

          if (false === $contents) {
            throw new RequestException(NULL, 400, "could not read file: $item->getPathname()");
          }

        }

      }

      else if (is_string($item)) {

        $contents = file_get_contents($item);

      }

      if (!$contents || strlen($contents) == 0) {
        throw new RequestException(NULL, 400, "invalid file: $item");
      }


      return $this->encodeData($contents);
    }


  }
