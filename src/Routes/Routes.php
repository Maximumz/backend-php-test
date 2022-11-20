<?php

namespace Routes;

use Controllers\BaseController;
use Controllers\TodoController;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;
use Controllers\AuthController;

class Routes
{
    public static function registerRoutes(Application $app)
    {
        $app->get('/', function (Request $request) use ($app) {
            return (new BaseController($request, $app))->sendHome();
        });

        $app->match('/login', function (Request $request) use ($app) {
            return (new AuthController($request, $app))->loginUser();
        });

        $app->get('/logout', function (Request $request) use ($app) {
            return (new AuthController($request, $app))->logoutUser();
        });

        $app->get('/todo/{id}', function (Request $request, int $id) use ($app) {
            return (new TodoController($request, $app))->getTodos($id);
        })->value('id', 0);

        $app->post('/todo/add', function (Request $request) use ($app) {
            return (new TodoController($request, $app))->addTodo();
        });

        $app->match('/todo/delete/{id}', function (Request $request, int $id) use ($app) {
            return (new TodoController($request, $app))->deleteTodo($id);
        });

        $app->match('/todo/complete/{id}', function (Request $request, int $id) use ($app) {
            return (new TodoController($request, $app))->completeTodo($id);
        });

        $app->get('/todo/{id}/json', function (Request $request, int $id) use ($app) {
            return (new TodoController($request, $app))->getTodoJson($id);
        });
    }
}