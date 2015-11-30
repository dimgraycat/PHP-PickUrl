PHP-PickUrl, a simple PHP Web Scraper
=====================================

Requirements
------------

PHP-PickUrl depends on PHP 5.5+ and Goutte 1+ and Guzzle 6+.

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
