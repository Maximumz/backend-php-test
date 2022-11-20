<?php

namespace Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class BaseController
{
    /** @var Request */
    protected $request;
    /** @var Application */
    protected $app;
    protected $user;

    public function __construct(Request $request, Application $app) {
        $this->request = $request;
        $this->app = $app;

        $this->app['twig'] = $this->app->share($this->app->extend('twig', function($twig, Application $app) {
            $twig->addGlobal('user', $app['session']->get('user'));

            return $twig;
        }));

        // if the user is not authenticated, push to login page
        if (null === $this->user = $this->app['session']->get('user')) {
            if (($request->getRequestUri() != '/' && $request->getRequestUri() != '/login')) {
                $this->app->redirect('/login')->sendHeaders();
                die();
            }
        }
    }

    public function sendHome() {
        return $this->app['twig']->render('index.html', [
            'readme' => file_get_contents('README.md'),
        ]);
    }
}