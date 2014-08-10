# Mirador-PHP

A php client for the Mirador [Image Moderation API](http://mirador.im). To get started, you will need an API Key, available at [mirador.im](http://mirador.im). For questions about keys or support with this module please email support@mirador.im.

## Installation

The module is available on Packagist, and can be installed with composer (in your composer.json):

```json
"require": {
  "mirador/mirador": "*"
}
```

## Getting Started

```php
<?php

  require_once 'vendor/autoload.php';

  $client = new \Mirador\API\Client('your_api_key');
  $baby = $client->classifyUrl('http://static.mirador.im/test/baby.jpg');

  // results have a value (float), safe (bool) and an id (string)
  echo $baby->value . ", " . $baby->safe . "\n";
  echo $baby->id; // "http://static.mirador.im/test/baby.jpg", since you didn't specify

```

## Classifying Files

The php client supports working with a number of data types:

* filenames [classifyFiles](#classifyFiles)
* `SplFileInfo` objects and subclasses (e.g., SplFileObject) [classifyFiles](#classifyFiles)
* base64-encoded data URIs, as in those provided by Javascript `FileReader` or `CanvasElement` APIs. [classifyDataUris](#classifyDataUris).
* file contents (e.g., output of `file_get_contents()`) [classifyBuffers](#classifyBuffers)

Every method has an associated single-request method, e.g., [classifyFile](#classifyFile) for classifyFiles. These have a similar interface but only support processing of one item at a time.

### <a name='classifyFiles'></a> \Mirador\API\Client#classifyFiles

All classification methods share an identical interface that allows for generally flexible input. The mirador API, and the  object returned by the API allow for the attribution of an arbitrary identifier to your requests, to make post-processing of results easier. However, in cases where you do not specify an ID, the client will choose one (in a way that generally makes sense).

A very simple way to classify files is by filename:

```php
<?php
  require_once 'vendor/autoload.php';

  $client = new \Mirador\API\Client('your_api_key');
  $output = $client->classifyFiles('nsfw.jpg', 'sfw.jpg');

  // $output is an object with two fields:

  // results, an array of \Mirador\API\Result objects
  $output->results

  // errors, an array (hopefully empty!) of \Mirador\API\RequestException objects
  $output->errors

  foreach ($output->results as $id => $result) {

    // each result has a float value, indicating the probability that
    // the image is pornographic (0.0 - 1.0)
    echo "$id, " . $result->value . "\n";

  }

```

Alternatively, you can specify an id by passing in an associative array:

```php

$output = $client->classifyFiles(array('nsfw' => 'nsfw.jpg'));
var_dump($output->results['nsfw']);

```

It's pretty easy to use this method with the `$_FILES` superglobal:

```php

$output = $client->classifyFiles(array('uploaded' => $_FILES['my-file-field']['tmp_name']));
var_dump($output->results['uploaded']);

```

#### <a name='splFileInfo'></a> Working with `SplFileInfo` and Subclasses

`classifyFiles` also accepts `SplFileInfo` objects and subclasses (e.g., Symfony/Laravel's `UploadedFile`). When an id is not specified, the output of `SplFileInfo::getPathname()` is used as the id:

```php

$output = $client->classifyFiles(new SplFileInfo(__DIR__ . '/nsfw.jpg'));
var_dump($output->results[__DIR__ . '/nsfw.jpg']);

$output = $client->classifyFiles(array('nsfw' => (new SplFileInfo('nsfw.jpg'))));
var_dump($output->results['nsfw']);

```

#### <a name='classifyFile'></a> \Mirador\API\Client#classifyFile

As in the other classification methods, classifyFiles has a corresponding single-request method, classifyFile. This can be used with the same interface as its multiple-request sibling:

Since the result is returned directly, any exceptions will be thrown instead of being available in an `errors` array:

```php
$nsfw = $client->classifyFile('nsfw.jpg')
echo $nsfw->id // "nsfw.jpg"

// \Mirador\API\RequestException error is thrown with error 400
$nsfw = $client->classifyFile('not-a-real-file')

```

### <a name='classifyBuffers'></a> \Mirador\API\Client#classifyBuffers

This has an identical usage/interface to [classifyFiles](#classifyFiles), except that instead of passing in filenames or file objects, you only provide already-read buffers.

This method is useful when working with a library that only gives access to `POST` or `PUT` bodies, e.g., Slim framework's `$app->request()->getBody()`.

When not explicitly specifying an ID, the client uses the index of the item in the parameters, since we can't derive a name from a file buffer:

```php
<?php
  require_once 'vendor/autoload.php';

  $client = new \Mirador\API\Client('your_api_key');
  $output = $client->classifyBuffers(file_get_contents('nsfw.jpg'));

  // the result is in $output->results[0]
  var_dump($output->results[0]);

```

For this reason, when working with buffers, it's good to specify an id (if you can):

```php

$output = $client->classifyBuffers(array('nsfw' => file_get_contents('nsfw.jpg')));
var_dump($output->results['nsfw']);

```

#### <a name='classifyBuffer'></a> \Mirador\API\Client#classifyBuffer

This is a simple helper when only classifying one buffer, it returns a `\Mirador\API\Result` object or throws `\Mirador\API\RequestException` on error:

```php

$nsfw_result = $client->classifyBuffer(file_get_contents('nsfw.jpg'))
$nsfw_result = $client->classifyBuffer(array('nsfw' => file_get_contents('nsfw.jpg')))

```

### <a name='classifyDataUris'><a> \Mirador\API\Client\#classifyDataUris

This method exists as a convenience for simplified client-server communication when using clients that work with data uris (e.g., in web applications). For example, given this javascript (using jQuery to be concise):

```javascript

$('#form-field').on('change', function (e) {
  var file = this.files[0],
      reader = new FileReader();

  reader.onload = function (e) {
    $.post('/proxy/mirador', { id: file.name, data: e.target.result }).done(function (res) {
      console.log(res);
    });
  };

  reader.readAsDataURL(file);
});
```

We could handle that request on the server with this code:

```php
<?php
  require_once 'vendor/autoload.php';

  $app = new \Slim\Slim();
  $client = new \Mirador\API\Client('your_api_key');

  $app->post('/proxy/mirador', function () use ($app) {

    $datauri = $app->request()->params('data')
    echo json_encode($client->classifyDataUri($datauri));

  });

```

This example shows the singular, `classifyDataUri`, however, the multiple -- `classifyDataUris`, has an identical interface.

## <a name='classifying-urls'></a> Classifying Urls

There are a couple of requirements to be mindful of when classifying urls, they must meet the following criteria:

* be publically-accessibly
* have a correctly set mimetype (`image/*`)
* not require query paramters

Given that, the interface for classifying urls is identical to that when using [classifyFiles](#classifyFiles)

### <a name='classifyUrls'></a> \Mirador\API\Client#classifyUrls

Since urls are text and are generally short, our client uses the url as an id by default:

```php
<?php
  require_once 'vendor/autoload.php';

  $client = new \Mirador\API\Client('your_api_key');

  $output = $client->classifyUrls('http://static.mirador.im/test/baby.jpg', 'http://static.mirador.im/test/beach_party.jpg');

  var_dump($output->results);

  foreach ($output->results as $url => $result) {
    echo "$url has $result->value probability of being pornographic\n";
  }

```

However, as with classifying files, an id can be specified by passing an array:

```php

$output = $client->classifyUrls(array('baby' => 'http://static.mirador.im/test/baby.jpg'));

// now the url has the id 'baby'
var_dump($output->results['baby']);


```

#### <a name='classifyUrl'></a> \Mirador\API\Client#classifyUrl

As with the other methods/data types, you can also classify a single url using the convenience method `classifyUrl`. This will return a \Mirador\API\Result object or throw a \Mirador\API\RequestException:

```php

$nsfw_result = $client->classifyUrl('http://static.mirador.im/test/nsfw.jpg');
echo "$nsfw_result->id is $nsfw_result->value / 1.0 of being porn\n";

// this will throw a \Mirador\API\RequestException
$bad_url = $client->classifyUrl('notreallyaurl.io')

```

## <a name='result'></a> \Mirador\API\Result

The `\Mirador\API\Result` object reprents the classification result for a single image/url. It has the following properties:

* `id` `[string|int]` - a unique identifier for the result
* `safe` `[boolean]` - indicates if an image contains adult content.
* `value` `[float 0.0-1.0]` - the likelyhood that the image does contain adult content (for implementing a custom threshold)

## <a name='result-list'></a> Method Output/Results

Multiple-request methods (e.g., `classifyUrls`) return an object with two properties:

* `results` [Array of Mirador\API\Result] results, indexed by `id`
* `errors` [Array of Mirador\API\RequestException] exceptions; can be thrown


## Contributing / Issues

Please submit any issues as issues [here on github](http://github.com/mirador-cv/mirador-py/issues), feel free to submit a pull request, or for immediate support, contact us at support@mirador.im.
#
