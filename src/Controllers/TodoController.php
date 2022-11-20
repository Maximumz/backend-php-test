<?php

namespace Controllers;

use Models\Todo;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\JsonResponse;

class TodoController extends BaseController
{
    public function __construct(Request $request, Application $app)
    {
        parent::__construct($request, $app);
    }

    public function getTodos(int $todoId)
    {
        if ($todoId) {
            $todo = Todo::setup($this->app['db'])->getTodoById($todoId, $this->user['id']);

            // No results
            if (!$todo) {
                return $this->app->redirect('/todo');
            }

            return $this->app['twig']->render('todo.html', [
                'todo' => $todo,
            ]);
        }

        $page = (int)$this->request->get('page');

        if (!$page) {
            $page = 1;
        }

        $total_count = Todo::setup($this->app['db'])->getTodoCountByUserId($this->user['id']);

        // page must be at lest 1
        $page_limit = 10;
        $offset = ($page - 1) * $page_limit;
        $rowCount = $page_limit;
        $pages = ceil($total_count/$page_limit);

        $todos = Todo::setup($this->app['db'])->getPaginatedTodosByUserId($this->user['id'], $offset, $rowCount);

        $results =  [
            'todos' => $todos,
            'page' => $page,
            'numberOfPages' => $pages,
            'totalCount' => $total_count
        ];

        return $this->app['twig']->render('todos.html', $results);
    }

    public function addTodo()
    {
        $description = $this->request->get('description');

        if (!$description) {
            $this->app['session']->getFlashBag()->add(
                'error',
                'A description is required!'
            );

            return $this->app->redirect('/todo');
        }

        $result = Todo::setup($this->app['db'])->add($this->user['id'], $description);

        if ($result) {
            $this->app['session']->getFlashBag()->add(
                'success',
                "Todo added!"
            );
        } else {
            $this->app['session']->getFlashBag()->add(
                'error',
                "Failed adding todo!"
            );
        }

        return $this->app->redirect('/todo');
    }

    public function deleteTodo(int $todoId)
    {
        $result = Todo::setup($this->app['db'])->delete($this->user['id'], $todoId);

        if ($result) {
            $this->app['session']->getFlashBag()->add(
                'success',
                "Todo#{$todoId} deleted!"
            );
        } else {
            $this->app['session']->getFlashBag()->add(
                'error',
                "Failed deleting todo#{$todoId}!"
            );
        }

        return $this->app->redirect('/todo');
    }

    public function completeTodo(int $todoId)
    {
        $completedStatus = $this->request->get('completed_status');

        $result = Todo::setup($this->app['db'])->complete($this->user['id'], $todoId, $completedStatus);

        if ($result) {
            $this->app['session']->getFlashBag()->add(
                'success',
                $completedStatus ? "Todo#{$todoId} marked completed!" : "Todo#{$todoId} marked as uncompleted!"
            );
        } else {
            $this->app['session']->getFlashBag()->add(
                'error',
                'Failed updating!'
            );
        }

        return $this->app->redirect('/todo');
    }

    public function getTodoJson(int $todoId): JsonResponse
    {
        return $this->app->json(Todo::setup($this->app['db'])->getTodoById($todoId, $this->user['id']));
    }
}