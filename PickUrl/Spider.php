<?php

namespace PickUrl;

use PickUrl\Config as PickUrlConfig;
use PickUrl\Picker as PickUrlPickper;

class Spider extends PickUrlConfig
{
    const USE_STREAM_LIMIT = 20;
    const WAIT_TIME = 10;

    protected $picker;
    protected $cookies;
    protected $uri;
    protected $tmpdir;
    protected $tmpfile;
    protected $count = 0;
    protected $wait_time;
    protected $filters = array();

    public function __construct()
    {
    }

    public function crawl($url)
    {
        $this->uri = parse_url($url);
        $this->count++;

        $list = $this->fileRead();
        if(!empty($url)) {
            $crawler = $this->picker()->client($this->cookies)->request(
                $this->picker()->getMethod(), $url
            );
            $this->cookie();
            $crawler->filter('a')->each(function($node) use (&$list) {
                $this->anchorHref($list, $node);
            });
            $list['match']['urls'][base64_encode($url)] = true;
            $this->fileSave($list);
            $this->runHooks($crawler, $url);
            $url = $this->getCrawlUrl($url);
            $this->wait();
            unset($list);
            if($this->count >= self::USE_STREAM_LIMIT) {
                $this->picker()->reset($this->cookies);
                $this->count = 0;
            }
            $this->crawl($url);
        }
    }

    public function picker()
    {
        if(empty($this->picker)) {
            $this->picker = new PickUrlPickper();
        }
        return $this->picker;
    }

    protected function runHooks(&$crawler, &$url)
    {
        foreach($this->filters as $code) {
            $code($crawler, $url);
        }
    }

    public function addHook($code)
    {
        if(is_callable($code)) {
            $this->filters[] = $code;
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
        if(!empty($uri)) {
            if(array_key_exists('host', $uri) and $this->uri['host'] === $uri['host']) {
                $key = base64_encode($href);
                if(!$this->hasUrl($key, $list)) {
                    $list['match']['urls'][$key] = false;
                }
                return;
            }
            if(!array_key_exists('host', $uri)) {
                $key = base64_encode($this->httpBuildUrl($uri));
                if(!$this->hasUrl($key, $list)) {
                    $list['match']['urls'][$key] = false;
                }
                return;
            }
            $list['else']['urls'][base64_encode($href)] = false;
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
        $scheme   = isset($this->uri['scheme']) ? $this->uri['scheme'] . '://' : '';
        $host     = isset($this->uri['host']) ? $this->uri['host'] : '';
        $port     = isset($this->uri['port']) ? ':' . $this->uri['port'] : '';
        $path     = isset($uri['path']) ? $this->getUriPath($uri['path']) : '';
        $query    = isset($uri['query']) ? '?' . $uri['query'] : '';
        return "$scheme$host$port/$path$query";
    }

    protected function getUriPath($path)
    {
        if(preg_match('/^\/.*/', $path)) {
            return ltrim($path, '/');
        }
        return $path;
    }
}
