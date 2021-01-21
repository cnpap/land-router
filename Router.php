<?php

namespace LandRouter;

use Closure;
use Land15\Handle;
use LandRouter\Exception\Invalid;
use LandRouter\Exception\Undefined;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Router implements RequestHandlerInterface
{
    protected $attributes = [];
    protected $routes     = [];

    function handle(ServerRequestInterface $request): ResponseInterface
    {
        $route = $this->routes[$request->getMethod() . $request->getUri()->getPath()] ?? null;
        if (is_null($route)) {
            throw new Undefined();
        }
        $processes   = $route['middleware'];
        $processes[] = $route['handle'];
        $handle      = new Handle($processes);
        return $handle->handle($request);
    }

    function group(array $newAttributes, Closure $next)
    {
        if (count(array_diff(array_keys($newAttributes), ['middleware', 'prefix']))) {
            throw new Invalid();
        }
        if ($this->attributes) {
            $lastAttributes = end($this->attributes);
            if (isset($newAttributes['prefix'])) {
                $lastAttributes['prefix'] = $lastAttributes['prefix'] ?? '';
                $lastAttributes['prefix'] .= '/' . trim($newAttributes['prefix'], '/');
            }
            if (isset($newAttributes['middleware'])) {
                $lastAttributes['middleware'] = array_merge($lastAttributes['middleware'] ?? [], $newAttributes['middleware']);
            }
            $this->attributes[] = $lastAttributes;
        } else {
            $this->attributes[] = $newAttributes;
        }
        $next($this);
        array_pop($this->attributes);
    }

    function add(string $method, string $url, string $handle)
    {
        $lastAttribute = end($this->attributes);
        if (isset($lastAttribute['prefix'])) {
            $url = $lastAttribute['prefix'] . '/' . trim($url, '/');
        }
        $url                          = '/' . trim($url, '/');
        $middleware                   = $lastAttribute['middleware'] ?? [];
        $this->routes[$method . $url] = compact('method', 'url', 'middleware', 'handle');
    }

    function get(string $url, string $handle)
    {
        $this->add('GET', $url, $handle);
    }

    function post(string $url, string $handle)
    {
        $this->add('POST', $url, $handle);
    }

    function put(string $url, string $handle)
    {
        $this->add('PUT', $url, $handle);
    }

    function delete(string $url, string $handle)
    {
        $this->add('DELETE', $url, $handle);
    }
}