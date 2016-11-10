# Asynchronous HTTP client with promises for PHP 7 projects.

Based on the client-side [JavaScript fetch API][fetch].

***

<a href="https://gitter.im/phpgt/fetch" target="_blank">
    <img src="https://img.shields.io/gitter/room/phpgt/fetch.svg?style=flat-square" alt="Gitter chat" />
</a>
<a href="https://circleci.com/gh/phpgt/fetch" target="_blank">
    <img src="https://img.shields.io/circleci/project/phpgt/fetch/master.svg?style=flat-square" alt="Build status" />
</a>
<a href="https://scrutinizer-ci.com/g/phpgt/fetch" target="_blank">
    <img src="https://img.shields.io/scrutinizer/g/phpgt/fetch/master.svg?style=flat-square" alt="Code quality" />
</a>
<a href="https://scrutinizer-ci.com/g/phpgt/fetch" target="_blank">
    <img src="https://img.shields.io/scrutinizer/coverage/g/phpgt/fetch/master.svg?style=flat-square" alt="Code coverage" />
</a>
<a href="https://packagist.org/packages/phpgt/fetch" target="_blank">
    <img src="https://img.shields.io/packagist/v/phpgt/fetch.svg?style=flat-square" alt="Current version" />
</a>

## Example usage: compute multiple HTTP requests in parallel.

```php
<?php
$http = new \Gt\Fetch\Http();

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

[fetch]: https://developer.mozilla.org/en/docs/Web/API/Fetch_API
