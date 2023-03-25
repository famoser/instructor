<?php

namespace DiniTheorie\Instructor\ExamCategory;

use DiniTheorie\Instructor\Repository;
use DiniTheorie\Instructor\utils\SlimExtensions;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

class RouteFactory
{
    public static function addRoutes(App $app, Repository $repository): void
    {
        $storage = new Storage($repository);

        $app->get('/exam/categories', function (Request $request, Response $response, array $args) use ($storage) {
            $categories = $storage->getCategories();

            return SlimExtensions::createJsonResponse($response, $categories);
        });

        $app->get('/exam/category/{id}', function (Request $request, Response $response, array $args) use ($storage) {
            $categoryId = $args['id'];
            RequestValidator::validateCategoryId($request, $storage, $categoryId);

            $category = $storage->getCategory($categoryId);

            return SlimExtensions::createJsonResponse($response, $category);
        });

        $app->post('/exam/category', function (Request $request, Response $response, array $args) use ($storage) {
            $category = SlimExtensions::parseJsonRequestBody($request);
            RequestValidator::validateCategory($request, $category);
            RequestValidator::validateNewCategoryId($request, $storage, $category['id']);

            $storage->addCategory($category);

            $category = $storage->getCategory($category['id']);

            return SlimExtensions::createJsonResponse($response, $category, SlimExtensions::STATUS_CREATED);
        });

        $app->put('/exam/category/{id}', function (Request $request, Response $response, array $args) use ($storage) {
            $categoryId = $args['id'];
            $category = SlimExtensions::parseJsonRequestBody($request);
            RequestValidator::validateCategoryId($request, $storage, $categoryId);
            RequestValidator::validateCategory($request, $category);

            $storage->storeCategory($categoryId, $category);

            $category = $storage->getCategory($category['id']);

            return SlimExtensions::createJsonResponse($response, $category);
        });

        $app->delete('/exam/category/{id}', function (Request $request, Response $response, array $args) use ($storage) {
            $categoryId = $args['id'];
            RequestValidator::validateCategoryId($request, $storage, $categoryId);

            $storage->removeCategory($categoryId);

            return $response->withStatus(SlimExtensions::STATUS_OK);
        });
    }
}
