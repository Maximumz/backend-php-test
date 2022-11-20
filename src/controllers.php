<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;
use \Doctrine\DBAL\ParameterType;

$app['twig'] = $app->share($app->extend('twig', function($twig, Application $app) {
    $twig->addGlobal('user', $app['session']->get('user'));

    return $twig;
}));


$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html', [
        'readme' => file_get_contents('README.md'),
    ]);
});


$app->match('/login', function (Request $request) use ($app) {
    $username = $request->get('username');
    $password = $request->get('password');

    if ($username) {
        $sql = "SELECT * FROM users WHERE username = '$username' and password = '$password'";
        $user = $app['db']->fetchAssoc($sql);

        if ($user){
            $app['session']->set('user', $user);
            return $app->redirect('/todo');
        }
    }

    return $app['twig']->render('login.html', []);
});


$app->get('/logout', function () use ($app) {
    $app['session']->set('user', null);
    return $app->redirect('/');
});


$app->get('/todo/{id}', function (Request $request, int $id) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }

    $page = (int)$request->get('page');

    if (!$page) {
        $page = 1;
    }

    $params = ['user_id' => $user['id']];

    if ($id){
        $sql = "SELECT * FROM todos WHERE id = :todo_id AND user_id = :user_id";

        $params['todo_id'] = $id;

        $todo = $app['db']->fetchAssoc($sql, $params);

        // No results
        if (!$todo) {
            return $app->redirect('/todo');
        }

        return $app['twig']->render('todo.html', [
            'todo' => $todo,
        ]);
    } else {

        $sql = "SELECT id FROM todos WHERE user_id = :user_id";
        $total_count = $app['db']->executeStatement($sql, $params);

        // page must be at lest 1
        $page_limit = 10;
        $params['offset'] = ($page - 1) * $page_limit;
        $params['row_count'] = $page_limit;

        $sql = "SELECT * FROM todos WHERE user_id = :user_id LIMIT :offset, :row_count";

        $todos = $app['db']->fetchAll($sql, $params,
            [
                'offset' => ParameterType::INTEGER,
                'row_count' => ParameterType::INTEGER
            ]
        );

        $results =  [
            'todos' => $todos,
            'page' => $page,
            'count' => count($todos),
            'totalCount' => $total_count
        ];

        return $app['twig']->render('todos.html', $results);
    }
})
->value('id', 0);


$app->post('/todo/add', function (Request $request) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }

    $user_id = $user['id'];
    $description = $request->get('description');

    if ($description) {
        $sql = "INSERT INTO todos (user_id, description) VALUES (?, ?)";

        $params = [$user_id, $description];

        $result = $app['db']->executeStatement($sql, $params);

        if ($result) {
            $app['session']->getFlashBag()->add(
                'success',
                "Todo added!"
            );
        } else {
            $app['session']->getFlashBag()->add(
                'error',
                "Failed adding todo!"
            );
        }
    } else {
        $app['session']->getFlashBag()->add(
            'error',
            'A description is required!'
        );
    }

    return $app->redirect('/todo');
});


$app->match('/todo/delete/{id}', function (int $id) use ($app) {

    $sql = "DELETE FROM todos WHERE id = :todo_id AND user_id = :user_id";

    $params = [
        'todo_id' => $id,
        'user_id' => $app['session']->get('user')['id']
    ];

    $result = $app['db']->executeStatement($sql, $params);

    if ($result) {
        $app['session']->getFlashBag()->add(
            'success',
            "Todo#{$id} deleted!"
        );
    } else {
        $app['session']->getFlashBag()->add(
            'error',
            "Failed deleting todo#{$id}!"
        );
    }

    return $app->redirect('/todo');
});

$app->match('/todo/complete/{id}', function (Request $request, int $id) use ($app) {

    $completed_status = $request->get('completed_status');

    $sql = "UPDATE todos SET complete = :completed_status WHERE id = :todo_id AND user_id = :user_id";

    $params = [
        'completed_status' => $completed_status,
        'todo_id' => $id,
        'user_id' => $app['session']->get('user')['id']
    ];

    $result = $app['db']->executeStatement($sql, $params);

    if ($result) {
        $app['session']->getFlashBag()->add(
            'success',
            $completed_status ? "Todo#{$id} marked completed!" : "Todo#{$id} marked as uncompleted!"
        );
    } else {
        $app['session']->getFlashBag()->add(
            'error',
            'Failed updating!'
        );
    }

    return $app->redirect('/todo');
});

$app->get('/todo/{id}/json', function (int $id) use ($app) {

    $sql = "SELECT * FROM todos WHERE id = :todo_id AND user_id = :user_id";

    $params = [
        'todo_id' => $id,
        'user_id' => $app['session']->get('user')['id']
    ];

    $todo = $app['db']->fetchAssoc($sql, $params);

    return json_encode($todo);
});