<?php
  namespace Mirador\API;

  trait ArgumentProcessing {

    protected static $spi_class = 'SplFileInfo';
    protected static $spo_class = 'SplFileObject';


    protected function argPusher(&$args, $datatype) {

      // a closure to help out
      // with doing the encoding & adding new elements
      // to our output array
      return function ($id, $data) use (&$args, $datatype) {

        $enc = $this->encoder;

        return array_push($args, array(
          'id' => $id,
          'data' => $enc->$datatype($data),
        ));

      };
    }

    /**
     * Turn an argument into a correctly-formatted array
     *
     */

    protected function processArguments($datatype, $items) {

      $output = array();
      $pusher = $this->argPusher($output, $datatype);

      foreach ($items as $idx => $item) {

        if (is_array($item)) {

          if (isset($item['id'], $item['data'])) {

            $pusher($item['id'], $item['data']);
            continue;

          }

          foreach ($item as $id => $data) {

            if (isset($data['id'], $data['data'])) {
              $pusher($data['id'], $data['data']);
              continue;
            }

            $pusher($id, $data);

          }

        }

        else if (is_string($item)) {

          $id = (strlen($item) < self::MAX_KEY_SIZE) ? $item : $idx;
          $pusher($id, $item);

        }

        else if ($item instanceof self::$spi_class) {

          $pusher($item->getPathname(), $item);

        }

        else {

          $obj_t = is_object($item) ? get_class($item) : gettype($item);

          throw new RequestException(
            NULL, 400,
            "invalid parameter " . $obj_t . "; array or valid string expected"
          );

        }


      }

      return $output;
    }

  }
