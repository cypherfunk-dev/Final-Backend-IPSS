<?php

use JetBrains\PhpStorm\NoReturn;

class BaseController
{
    #[NoReturn] public function __call($name, $arguments)
    {
        $this-> sendOutput("", array('HTTP/1.1 404 Not Found'));
    }
    protected function getUriSegments(): array
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = explode('/', trim($uri, "/"));
        return $uri;
    }
    protected function getQueryStringParams():array
    {
        $query = [];

        if (isset($_SERVER['QUERY_STRING'])) {
            parse_str($_SERVER['QUERY_STRING'], $query);
        }

        return $query;
    }
    #[NoReturn] protected function sendOutput($data, $httpHeaders=array()): void
    {
        header_remove('Set-Cookie');

        if (is_array($httpHeaders) && count ($httpHeaders) ){
            foreach ($httpHeaders as $httpHeader) {
                header($httpHeader);
        }
        }
        echo $data;
        exit;
    }
}
