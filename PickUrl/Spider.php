<?php

namespace PickUrl;

use PickUrl\Config as PickUrlConfig;
use PickUrl\Picker as PickUrlPickper;
use Sabre\Uri;

class Spider extends PickUrlConfig
{
    const USE_STREAM_LIMIT = 5;
    const WAIT_TIME = 10;

    const CRAWL_BEFORE  = "before";
    const CRAWL_AFTER   = "after";

    protected $picker;
    protected $cookies;
    protected $uri;
    protected $tmpdir;
    protected $tmpfile;
    protected $wait_time;
    protected $filters = [
        self::CRAWL_BEFORE  => [],
        self::CRAWL_AFTER   => []
    ];

    public function __construct()
    {
    }

    public function crawl($url)
    {
        $this->uri = parse_url($url);

        $count = 0;
        do {
            $count++;
            $list = $this->fileRead();
            $this->runHooks(self::CRAWL_BEFORE, $crawler, $url);
            $crawler = $this->picker()->client($this->cookies)->request(
                $this->picker()->getMethod(), $url
            );
            $this->cookie();
            $urls = [];
            $crawler->filter('a')->each(function($node) use (&$list, &$urls) {
                $url = $this->anchorHref($list, $node);
                $urls[$url] = true;
            });
            $urls = array_keys($urls);
            $list['match']['urls'][base64_encode($url)] = true;
            $this->runHooks(self::CRAWL_AFTER, $crawler, $url, $urls);
            unset($urls);
            $this->fileSave($list);
            $url = $this->getCrawlUrl($url);
            $this->wait();
            unset($list);
            if($count >= self::USE_STREAM_LIMIT) {
                $this->picker()->reset($this->cookies);
                $count = 0;
            }
        } while(!empty($url));
    }

    public function picker()
    {
        if(empty($this->picker)) {
            $this->picker = new PickUrlPickper();
        }
        return $this->picker;
    }

    protected function runHooks($hook_mode, &$crawler, &$url, $urls = [])
    {
        foreach($this->filters[$hook_mode] as $code) {
            if($hook_mode == self::CRAWL_BEFORE) {
                $code($crawler, $url, $urls);
            }
            if($hook_mode == self::CRAWL_AFTER) {
                $code($crawler, $url, $urls);
            }
        }
    }

    public function addHook($hook_mode = self::CRAWL_BEFORE, $code)
    {
        if(is_callable($code)) {
            $this->filters[$hook_mode][] = $code;
        }
        return $this;
    }

    protected function wait()
    {
        sleep($this->getWaitTime());
    }

    public function setWaitTime($wait_time = null)
    {
        if(!empty($wait_time)) {
            $this->wait_time = $wait_time;
        }
        return $this;
    }

    public function getWaitTime()
    {
        if(empty($this->wait_time)) {
            $this->wait_time = self::WAIT_TIME;
        }
        return $this->wait_time;
    }

    protected function getCrawlUrl(&$url)
    {
        $array = $this->fileRead();
        $key = base64_decode(array_search(false, $array['match']['urls']));
        unset($array);
        return $key;
    }

    protected function fileSave($array)
    {
        $path = realpath(sprintf('%s/%s', $this->getTmpDir(), $this->getTmpFile()));
        $fp = @fopen($path, "w+");
        fwrite($fp, json_encode($array));
        fclose($fp);
    }

    protected function fileRead()
    {
        $path = realpath(sprintf('%s/%s', $this->getTmpDir(), $this->getTmpFile()));
        if(!is_file($path)) {
            return [];
        }
        $data = json_decode(@file_get_contents($path), true);
        return $data;
    }

    public function setTmpDir($tmpdir = null)
    {
        if(!empty($tmpdir)) {
            $this->tmpdir = $tmpdir;
        }
        return $this;
    }

    public function getTmpDir()
    {
        if(empty($this->tmpdir)) {
            $this->tmpdir = sys_get_temp_dir();
        }
        return $this->tmpdir;
    }

    public function setTmpFile($tmpfile = null)
    {
        if(!empty($tmpfile)) {
            $this->tmpfile = $tmpfile;
        }
        return $this;
    }

    public function getTmpFile()
    {
        if(empty($this->tmpfile)) {
            $tmpfname = tempnam($this->getTmpDir(), "PiS");
            $tmp = explode('/', $tmpfname);
            $this->tmpfile = array_pop($tmp);
            unset($tmp);
        }
        return $this->tmpfile;
    }

    protected function anchorHref(&$list, &$node)
    {
        $href = $node->attr('href');
        $uri = parse_url($href);
        $url;
        if(!empty($uri)) {
            if(array_key_exists('host', $uri) and $this->uri['host'] === $uri['host']) {
                $url = $href;
                $key = base64_encode($url);
                if(!$this->hasUrl($key, $list)) {
                    $list['match']['urls'][$key] = false;
                }
                return $url;
            } elseif(!array_key_exists('host', $uri)) {
                $url = $this->httpBuildUrl($uri);
                $key = base64_encode($url);
                if(!$this->hasUrl($key, $list)) {
                    $list['match']['urls'][$key] = false;
                }
                return $url;
            }
            $url = $this->httpBuildUrl($uri);
            $list['else']['urls'][base64_encode($url)] = false;
            return $url;
        }
    }

    protected function hasUrl($key, &$list)
    {
        if(!is_array($list)) {
            return false;
        }
        if(!array_key_exists('match', $list)) {
            return false;
        }
        if(array_key_exists('urls', $list['match'])) {
            return (array_key_exists($key, $list['match']['urls']));
        }
        return false;
    }

    protected function cookie()
    {
        $this->cookies = $this->picker()->client()->getCookieJar()->all();
        $this->picker()->client($this->cookies);
    }

    protected function httpBuildUrl($uri)
    {
        $base_url = Uri\build($this->uri);
        unset($uri['fragment']);
        $new_url    = Uri\build($uri);
        return Uri\resolve($base_url, $new_url);
    }
}
