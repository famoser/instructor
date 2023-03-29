<?php

/*
 * This file is part of the DiniTheorie project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DiniTheorie\Instructor\Exam\Category\Question;

use DiniTheorie\Instructor\Exam\Category\RequestValidator as CategoryRequestValidator;
use DiniTheorie\Instructor\Exam\Category\Storage as CategoryStorage;
use DiniTheorie\Instructor\Utils\SlimExtensions;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Stream;
use Slim\Routing\RouteCollectorProxy;

class RouteFactory
{
    public static function addRoutes(RouteCollectorProxy $route, CategoryStorage $categoryStorage): void
    {
        $storage = new Storage();

        $route->group('/exam/category/{categoryId}', function (RouteCollectorProxy $route) use ($categoryStorage, $storage) {
            $route->get('/questionIds', function (Request $request, Response $response, array $args) use ($categoryStorage, $storage) {
                $categoryId = $args['categoryId'];
                CategoryRequestValidator::validateExistingCategoryId($request, $categoryStorage, $categoryId);

                $categories = $storage->getQuestionIds($categoryId);

                return SlimExtensions::createJsonResponse($response, $categories);
            });

            $route->get('/question/{id}', function (Request $request, Response $response, array $args) use ($categoryStorage, $storage) {
                $categoryId = $args['categoryId'];
                CategoryRequestValidator::validateExistingCategoryId($request, $categoryStorage, $categoryId);

                $questionId = $args['id'];
                RequestValidator::validateExistingQuestionId($request, $storage, $categoryId, $questionId);

                $question = $storage->getQuestion($categoryId, $questionId);

                return SlimExtensions::createJsonResponse($response, $question);
            });

            $route->post('/question', function (Request $request, Response $response, array $args) use ($categoryStorage, $storage) {
                $categoryId = $args['categoryId'];
                CategoryRequestValidator::validateExistingCategoryId($request, $categoryStorage, $categoryId);

                $question = SlimExtensions::parseJsonRequestBody($request);
                RequestValidator::validateNewQuestionId($request, $storage, $categoryId, $question['id']);
                RequestValidator::validateQuestion($request, $question);

                $storage->addQuestion($categoryId, $question);

                $question = $storage->getQuestion($categoryId, $question['id']);

                return SlimExtensions::createJsonResponse($response, $question, SlimExtensions::STATUS_CREATED);
            });

            $route->put('/question/{id}', function (Request $request, Response $response, array $args) use ($categoryStorage, $storage) {
                $categoryId = $args['categoryId'];
                CategoryRequestValidator::validateExistingCategoryId($request, $categoryStorage, $categoryId);

                $question = SlimExtensions::parseJsonRequestBody($request);
                RequestValidator::validateExistingQuestionId($request, $storage, $categoryId, $args['id']);
                RequestValidator::validateQuestion($request, $question);

                $storage->storeQuestion($categoryId, $question);

                $question = $storage->getQuestion($categoryId, $question['id']);

                return SlimExtensions::createJsonResponse($response, $question);
            });

            $route->delete('/question/{id}', function (Request $request, Response $response, array $args) use ($categoryStorage, $storage) {
                $categoryId = $args['categoryId'];
                CategoryRequestValidator::validateExistingCategoryId($request, $categoryStorage, $categoryId);

                $questionId = $args['id'];
                RequestValidator::validateExistingQuestionId($request, $storage, $categoryId, $questionId);

                $storage->removeQuestion($categoryId, $questionId);

                return $response->withStatus(SlimExtensions::STATUS_OK);
            });

            $route->get('/question/{id}/image/{filename}', function (Request $request, Response $response, array $args) use ($categoryStorage, $storage) {
                $categoryId = $args['categoryId'];
                CategoryRequestValidator::validateExistingCategoryId($request, $categoryStorage, $categoryId);

                $questionId = $args['id'];
                RequestValidator::validateQuestionId($request, $questionId);

                $filename = $args['filename'];
                RequestValidator::validateExistingQuestionImage($request, $storage, $categoryId, $questionId, $filename);

                $path = $storage->getQuestionImagePath($categoryId, $questionId, $filename);

                $fh = fopen($path, 'rb');
                $stream = new Stream($fh);

                return $response->withBody($stream)
                    ->withHeader('Content-Disposition', 'attachment; filename='.$filename.';')
                    ->withHeader('Expires', '0') // immediately expire
                    ->withHeader('Content-Type', mime_content_type($path))
                    ->withHeader('Content-Length', filesize($path));
            });

            $route->post('/question/{id}/image', function (Request $request, Response $response, array $args) use ($categoryStorage, $storage) {
                $categoryId = $args['categoryId'];
                CategoryRequestValidator::validateExistingCategoryId($request, $categoryStorage, $categoryId);

                $questionId = $args['id'];
                RequestValidator::validateQuestionId($request, $questionId);

                $file = current($request->getUploadedFiles());
                RequestValidator::validateQuestionImage($request, $file);
                $storage->replaceQuestionImage($categoryId, $questionId, $file);

                return $response->withStatus(SlimExtensions::STATUS_OK);
            });
        });
    }
}
