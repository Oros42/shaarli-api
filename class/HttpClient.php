<?php

class HttpClient
{
    private $headers = array(
        'Connection: close',
        'Accept: text/plain,text/html',
        'User-Agent: '.USER_AGENT,
        'Accept-Encoding: gzip,deflate,br',
    );

    /**
     * Make http request and return html content
     */
    public function makeRequest($url)
    {
        $opts = array(
          'socket' => array('bindto' => '0.0.0.0:0'),
          'http' => array(
            'method' => 'GET',
            'header' => $this->headers,
            'timeout' => 8.0,
            'max_redirects' => 5,
            'protocol_version' => 1.1
          ),
          'ssl' => array(
            'allow_self_signed' => true,
            'verify_peer' => false,
            'verify_peer_name' => false
          )
        );
        $context = stream_context_create($opts);
        $html = @file_get_contents($url, false, $context);
        if ($html === FALSE){
            $info = array(
                'http_code' => 404,
                'url' => $url
            );
            $error = 'Failed to connect to $url';
            $errno = 0;
        } else {
            $header = array();
            $header['reponse_code'] = 404;
            foreach($http_response_header as $k=>$v) {
                $t = explode(':', $v, 2);
                if (isset($t[1])) {
                    if (trim($t[0]) == 'Location') {
                        $location=trim($t[1]);
                        if (substr($location, 0, 4) == 'http') {
                            $url=$location;
                        } else {
                            if (substr($location, 0, 1) == '/') {
                                $url = substr($url, 0, stripos($url, '/', 8)); // http://exemple.com/
                            } else {
                                $url = substr($url, 0, strrpos($url, '/', 8)); // http://exemple.com/blabla/
                            }
                            if (substr($url, -1) != '/') {
                                $url .= '/';
                            }
                            $url .= $location;

                            // 'http://exemple.com/a/../b/./c/' => 'http://exemple.com/b/c/'
                            $url_tmp=array();
                            foreach (explode("/",$url) as $key => $value) {
                                if ($value == '.') {
                                    continue;
                                } elseif ($value == '..') {
                                    end($url_tmp);
                                    $k = key($url_tmp);
                                    if ($k > 2) {
                                        unset($url_tmp[$k]);
                                    }
                                } else {
                                    $url_tmp[$key] = $value;
                                }
                            }
                            $url = implode('/', $url_tmp);
                        }
                        // new header
                        $header = array();
                        $header['reponse_code'] = 404;
                    }
                    $header[trim($t[0])] = trim($t[1]);
                } else {
                    $header[] = $v;
                    if (preg_match("#HTTP/[0-9\.]+\s+([0-9]+)#", $v, $out)) {
                        $header['reponse_code'] = intval($out[1]);
                    }
                }
            }
            if (isset($header['Content-Encoding']) && $header['Content-Encoding']=="gzip") {
                $html = gzinflate(substr($html, 10, -8));
            }

            $info = array(
                'http_code' => $header['reponse_code'],
                'url' => $url
            );
            $error = 0;
            $errno = 0;
        }
        return compact('html', 'error', 'errno', 'info');
    }

    /**
     * Make http request and return html content
     */
    public function getContent($url)
    {
        $results = $this->makeRequest($url);

        return $results['html'];
    }
}
