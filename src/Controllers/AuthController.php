<?php

namespace Controllers;

use Models\Todo;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class AuthController extends BaseController
{
    public function __construct(Request $request, Application $app)
    {
        parent::__construct($request, $app);
    }

    public function logoutUser()
    {
        $this->app['session']->set('user', null);
        return $this->app->redirect('/');
    }

    public function loginUser()
    {
        // TODO add validation for the username & password
        $username = $this->request->get('username');
        $password = $this->request->get('password');

        if ($username) {
            $user = Todo::setup($this->app['db'])->login($username, $password);
            if ($user){
                $this->app['session']->set('user', $user);
                return $this->app->redirect('/todo');
            }
        }

        return $this->app['twig']->render('login.html', []);
    }
}