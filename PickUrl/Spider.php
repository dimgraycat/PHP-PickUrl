<?php

namespace PickUrl;

use Goutte\Client as GoutteClient;

class Spider
{
    static public $GET_IMGS     = true;
    static protected $METHOD    = 'GET';
    static protected $TMPDIR    = '/tmp';

    protected $client;
    protected $cookies;
    protected $uri;
    protected $method;
    protected $tmpdir;
    protected $get_imgs;
    protected $tmpfile;
    protected $is_tmpfile = false;

    public function __construct()
    {
        $this->client = new GoutteClient();
    }

    public function crawl($url, $get_imgs = false)
    {
        $this->uri = parse_url($url);
        $this->get_imgs = $get_imgs;

        $list = $this->fileRead();
        if(!empty($url)) {
            $crawler = $this->client->request($this->getMethod(), $url);
            $this->cookie();
            $crawler->filter('a')->each(function($node) use (&$list) {
                $this->anchorHref($list, $node);
            });
            if($this->get_imgs) {
                $crawler->filter('img')->each(function($node) use (&$list) {
                    $this->imageUrl($list, $node);
                });
            }
            $list['match']['urls'][base64_encode($url)] = true;
            $this->fileSave($list);
            print "$url\n";
            $url = $this->getCrawlUrl($url);
            sleep(1);
            unset($list);
            $this->crawl($url, $this->get_imgs);
        }
            print "finish.\n";
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

    public function setMethod($method = false)
    {
        if(empty($method)) {
            $this->method = self::$METHOD;
        } else {
            $this->method = $method;
        }
        return $this;
    }

    public function getMethod()
    {
        if(empty($this->method)) {
            $this->method = self::$METHOD;
        }
        return $this->method;
    }

    public function setTmpDir($tmpdir = false)
    {
        if(empty($tmpdir)) {
            $this->tmpdir = sys_get_temp_dir();
        } else {
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
        if(empty($tmpfile)) {
            $this->tmpfile = tmpfile();
        } else {
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

    protected function imageUrl(&$list, &$node)
    {
        $src = $node->attr('src');
        $uri = parse_url($src);
        if(!empty($uri)) {
            if(array_key_exists('host', $uri) and $this->uri['host'] === $uri['host']) {
                $key = base64_encode($href);
                if(!$this->hasImageUrl($key, $list)) {
                    $list['match']['imgs'][$key] = true;
                }
                return;
            }
            if(!array_key_exists('host', $uri)) {
                $key = base64_encode($this->httpBuildUrl($uri));
                if(!$this->hasImageUrl($key, $list)) {
                    $list['match']['imgs'][$key] = true;
                }
                return;
            }
            $list['else']['imgs'][base64_encode($src)] = true;
        }
    }

    protected function hasImageUrl()
    {
        if(!array_key_exists('match', $list)) {
            return false;
        }
        if(array_key_exists('urls', $list['match'])) {
            return (array_key_exists($key, $list['match']['imgs']));
        }
        return false;
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
        $cookies = $this->client->getCookieJar()->all();
        $this->client->getCookieJar()->updateFromSetCookie($cookies);
    }

    protected function httpBuildUrl($uri)
    {
        $scheme   = isset($this->uri['scheme']) ? $this->uri['scheme'] . '://' : '';
        $host     = isset($this->uri['host']) ? $this->uri['host'] : '';
        $port     = isset($this->uri['port']) ? ':' . $this->uri['port'] : '';
        $path     = isset($uri['path']) ? $uri['path'] : '';
        $query    = isset($uri['query']) ? '?' . $uri['query'] : '';
        return "$scheme$host$port/$path$query";
    }
}
