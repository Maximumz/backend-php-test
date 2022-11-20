<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
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


$app->get('/todo/{id}', function ($id) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
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
        $sql = "SELECT * FROM todos WHERE user_id = :user_id";
        $todos = $app['db']->fetchAll($sql, $params);

        return $app['twig']->render('todos.html', [
            'todos' => $todos,
        ]);
    }
})
->value('id', null);


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