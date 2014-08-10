<?php
  require_once 'vendor/autoload.php';

  function toDataUri($farr) {

    $type = $farr['type'];
    $fname = $farr['tmp_name'];

    return "data:$type;base64," . base64_encode(file_get_contents($fname));
  }

  $app = new \Slim\Slim(array('debug' => true));
  $mc = new \Mirador\API\Client($_ENV['MIRADOR_API_KEY']);

  $app->get('/', function () use ($app) {

    echo <<<BODY
<!doctype html>

<form action='/proxy' enctype='multipart/form-data' method='POST'>
  <input type='file' name='upload'/>
  <input type='text' name='url'/>

  <button type='submit'>submit</button>
</form>

BODY;

  });

  $app->post('/proxy', function () use ($app, $mc) {

    echo "<!doctype html><h1>Results:</h1>";

    if (!empty($_FILES['upload']) && !empty($_FILES['upload']['name'])) {

      $fileRes = $mc->classifyFile(array($_FILES['upload']['name'] => $_FILES['upload']['tmp_name']));
      $safe = $fileRes->safe ? 'safe' : 'unsafe';

      echo "<div><h2>$fileRes->id</h2>";
      echo "<img src='" . toDataUri($_FILES['upload']) . "' width=400 />";
      echo "<ul><li>value: $fileRes->value</li></ul>";
      echo "</div>";

    }

    $url = $app->request()->params('url');

    if (isset($url) && $url != NULL) {

      $urlRes = $mc->classifyUrl($url);
      $safe = $urlRes->safe ? 'safe' : 'unsafe';

      echo "<div><h2>$urlRes->id is $safe</h2>";
      echo "<img src='" . $url . "' width=400 />";
      echo "<ul><li>value: $urlRes->value</li></ul>";
      echo "</div>";

    }

  });


  $app->run();
