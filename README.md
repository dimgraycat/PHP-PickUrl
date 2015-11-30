PHP-PickUrl, a simple PHP Web Scraper
=====================================

Requirements
------------

PHP-PickUrl depends on PHP 5.5+ and Goutte 1+ and Guzzle 6+.

Installation
------------
This library can be found on [Packagist](https://packagist.org/packages/dimgraycat/pickurl).
The recommended way to install this is through [composer](http://getcomposer.org).

Edit your `composer.json` and add:

```json
{
    "require": {
        "dimgraycat/pickurl": "~0.0.2"
    }
}
```

And install dependencies:

```bash
$ curl -sS https://getcomposer.org/installer | php
$ php composer.phar install
```

how to use
----------

```php:sample.php
<?php
$spider = new PickUrl\Spider();
$spider->addHook('before', function($crawler, $crawl_url) {
    print "$crawl_url\n";
})
->addHook('after', function($crawler, $crawl_url, $searched_urls) {
    print_r($searched_urls);
})
->setWaitTime(1)
->crawl('http://foo.sample.com');
```

License
-------
[PHP-PickUrl is licensed under the MIT license.](https://github.com/dimgraycat/PHP-PickUrl/blob/master/LICENSE)
