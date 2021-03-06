PHP-PickUrl, a simple PHP Web Scraper
=====================================

[![License](http://img.shields.io/badge/license-mit-blue.svg?style=flat-square)](https://github.com/dimgraycat/PHP-PickUrl/blob/master/LICENSE)
[![Packagist](https://img.shields.io/packagist/v/dimgraycat/pickurl.svg?style=flat-square)](https://packagist.org/packages/dimgraycat/pickurl)

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
        "dimgraycat/pickurl": "*"
    }
}
```

And install dependencies:

```bash
$ curl -sS https://getcomposer.org/installer | php
$ php composer.phar install
```

Usage
----------
Create a Pickurl\Spider Client instance:

```php:sample.php
<?php
$spider = new PickUrl\Spider();
$spider->addHook('before', function($crawler, $crawl_url) {
    print "$crawl_url\n";
})
->addHook('after', function($crawler, $crawl_url, $searched_urls) {
    print_r($searched_urls);
})
->crawl('http://foo.sample.com');
```

set UserAgent:
```php
$this->setUserAgent("MyCrawler 1.0");
```

set WaitTime:
```php
// default is 10sec. It can be shortened by setting.
$this->WaitTime(1);
```


License
-------
[PHP-PickUrl is licensed under the MIT license.](https://github.com/dimgraycat/PHP-PickUrl/blob/master/LICENSE)
