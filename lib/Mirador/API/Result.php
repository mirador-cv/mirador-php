<?php
  namespace Mirador\API;

  class Result {

    public $value;
    public $safe;
    public $id;

    public function __construct($value, $safe, $id) {

      $this->value = $value;
      $this->safe = $safe;
      $this->id = $id;

    }

    public static function fromJson($json) {

      $j = $json['result'];
      return new self($j['value'], $j['safe'], $json['id']);

    }

    public function __get($prop) {

      if ($prop == 'name') {
        error_log("warning: `name` is deprecated. Please use `id`", 0);
        return $this->id;
      }

    }

  }
