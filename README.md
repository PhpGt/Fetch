# Asynchronous HTTP client with promises.

Asynchronous HTTP client, [PSR-7 compatible][psr-7] implementation of the [Fetch Standard][fetch-standard] which defines requests, responses, and the process that binds them: _fetching_.

See also, the [JavaScript implementation][fetch-js] that ships as standard in all modern browsers.

***

<a href="https://circleci.com/gh/PhpGt/Fetch" target="_blank">
    <img src="https://badge.php.gt/fetch-build.svg" alt="Build status" />
</a>
<a href="https://scrutinizer-ci.com/g/PhpGt/Fetch" target="_blank">
    <img src="https://badge.php.gt/fetch-quality.svg" alt="Code quality" />
</a>
<a href="https://scrutinizer-ci.com/g/PhpGt/Fetch" target="_blank">
    <img src="https://badge.php.gt/fetch-coverage.svg" alt="Code coverage" />
</a>
<a href="https://packagist.org/packages/PhpGt/Fetch" target="_blank">
    <img src="https://badge.php.gt/fetch-version.svg" alt="Current version" />
</a>
<a href="https://www.php.gt/fetch" target="_blank">
    <img src="https://badge.php.gt/fetch-docs.svg" alt="PHP.Gt/Fetch documentation" />
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
