# Asynchronous HTTP client with promises.

[PSR-7 compatible][psr-7] HTTP client implementation of the [Fetch Standard][fetch-standard] which defines requests, responses, and the process that binds them: fetching.

See also, the [JavaScript implementation][fetch-js] that ships as standard in all modern browsers.

***

<a href="https://circleci.com/gh/PhpGt/Fetch" target="_blank">
    <img src="https://img.shields.io/circleci/project/PhpGt/Fetch/master.svg?style=flat-square" alt="Build status" />
</a>
<a href="https://scrutinizer-ci.com/g/PhpGt/Fetch" target="_blank">
    <img src="https://img.shields.io/scrutinizer/g/PhpGt/Fetch/master.svg?style=flat-square" alt="Code quality" />
</a>
<a href="https://scrutinizer-ci.com/g/PhpGt/Fetch" target="_blank">
    <img src="https://img.shields.io/scrutinizer/coverage/g/PhpGt/Fetch/master.svg?style=flat-square" alt="Code coverage" />
</a>
<a href="https://packagist.org/packages/PhpGt/Fetch" target="_blank">
    <img src="https://img.shields.io/packagist/v/PhpGt/Fetch.svg?style=flat-square" alt="Current version" />
</a>

## Example usage: compute multiple HTTP requests in parallel.

```php
<?php
$http = new Gt\Fetch\Http();

$http->get("http://example.com/api/something.json")
->then(function($response) {
	if($response->status !== 200) {
		echo "Looks like there was a problem. Status code: "
			. $response->status . PHP_EOL;
		return;
	}

    return $response->json();
})
->then(function($json) {
    echo "Got JSON result length "
    	. count($json->results)
    	. PHP_EOL;

    echo "Name of first result: "
    	. $json->results[0]->name
    	. PHP_EOL;
});

$http->get("http://example.com/something.jpg")
->then(function($response) {
    return $response->blob();
})
->then(function($blob) {
    echo "Got JPG blob. Saving file." . PHP_EOL;
    file_put_contents("/tmp/something.jpg", $blob);
});

$http->all()->then(function() {
    echo "All HTTP calls have completed!" . PHP_EOL;
});
```

[psr-7]: http://www.php-fig.org/psr/psr-7/
[fetch-standard]: https://fetch.spec.whatwg.org/
[fetch-js]: https://developer.mozilla.org/en/docs/Web/API/Fetch_API
