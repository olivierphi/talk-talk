<?php

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

$action = function (Application $app, Request $request) {
    return $app['twig']->render(
        'data-import-phpbb/importing/importing-page.twig'
    );
};

return $action;
