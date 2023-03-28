<?php

/*
 * This file is part of the DiniTheorie project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use DiniTheorie\Instructor\Exam\Category\RouteFactory as ExamCategoryRouteFactory;
use DiniTheorie\Instructor\Repository;
use DiniTheorie\Instructor\Utils\SlimExtensions;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__.'/../vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
$isDevMode = 'dev' === $_SERVER['APP_ENV'];
if ($isDevMode) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: *');
    header('Access-Control-Allow-Methods: GET, PUT, OPTIONS, DELETE, POST');
    if ('OPTIONS' === $_SERVER['REQUEST_METHOD']) {
        header('Allow: GET, PUT, OPTIONS, DELETE, POST');
        exit;
    }
}

if (str_starts_with($_SERVER['REQUEST_URI'], '/api')) {
    $app = AppFactory::create();
    $app->addRoutingMiddleware();
    $app->addErrorMiddleware('dev' === $_SERVER['APP_ENV'], true, true);

    $app->group('/api', function (RouteCollectorProxy $route) {
        $repository = new Repository();

        $route->post('/publish', function (Request $request, Response $response) use ($repository) {
            $repository->store();

            return $response->withStatus(SlimExtensions::STATUS_OK);
        });
        $route->post('/discard', function (Request $request, Response $response) use ($repository) {
            $repository->resetHard();

            return $response->withStatus(SlimExtensions::STATUS_OK);
        });
        ExamCategoryRouteFactory::addRoutes($route);
    });
    $app->run();
    exit;
} elseif ($isDevMode) {
    // when developing locally, use the server provided by vite
    header('Location: http://localhost:5173/');
} else {
    include 'index.html';
}
