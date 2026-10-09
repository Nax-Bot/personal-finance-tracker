<?php

namespace App\Core;

class Route
{
    public static function get($methodRequest, $pathRequest, $callback)
    {

        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        // SCRIPT_NAME indique le chemin réel de public/index.php sous WAMP,
        // même si le dossier du projet a un nom différent.
        $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
        $route = $basePath !== '' && str_starts_with($uri, $basePath)
            ? substr($uri, strlen($basePath))
            : $uri;

        //rtrim() est utilisé pour supprimer les barres obliques (/) à la fin de la route, ce qui facilite la comparaison avec les routes définies dans le code.
        $route = rtrim($route, '/');

        if ($route === '') {
            $route = '/';
        }

        if ($method === $methodRequest && $route === $pathRequest) {
            call_user_func($callback);
            exit;
        }
        // preg_match => fonction de recherche (regex) => extraction de motif
        // mettre dans matches => ['pathrequest' , motif recuperer]
        if ($method === $methodRequest && preg_match('#^' . $pathRequest . '$#', $route, $matches)) {
            array_shift($matches); // Supprimer le premier élément qui est la chaîne complète
            call_user_func_array($callback, $matches); // execute une fonction
            exit;
        }
    }
}
