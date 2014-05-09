<?php

namespace TalkTalk\Core\Plugins\Manager\Behaviour;

use TalkTalk\Core\Plugins\Plugin;
use TalkTalk\Core\Utils\ArrayUtils;
use Silex\Controller;

class ActionsManager extends BehaviourBase
{

    public function registerActions()
    {
        $app = $this->app;
        foreach ($this->pluginsManager->getPlugins() as $plugin) {
            if (!isset($plugin->data['@actions'])) {
                continue;
            }

            foreach ($plugin->data['@actions'] as $actionData) {
                if (isset($actionData['onlyForDebug']) && !$app['debug']) {
                    continue;
                }
                $this->registerAction($plugin, $actionData);
            }
        }
    }

    protected function registerAction(Plugin $plugin, array $actionData)
    {
        $app = $this->app;
        $pluginsManager = $this->pluginsManager;
        $actionData['method'] = isset($actionData['method']) ? $actionData['method'] : 'GET';
        $urlsPrefix = isset($plugin->data['@general']['actionsUrlsPrefix']) ? $plugin->data['@general']['actionsUrlsPrefix'] : '';

        $controller = $app->match(
            $urlsPrefix . $actionData['url'],
            function () use ($app, $pluginsManager, $plugin, $actionData) {
                // We resolve this action controller file path...
                $actionPath = $plugin->path . '/actions/' . $actionData['target'] . '.php';
                // ...we include it in an isolated context, with only "$app" access...
                $actionFunc = $pluginsManager->includeFileInIsolatedClosure($actionPath);
                // ...we trigger the Dependencies Injector on the returned Closure...
                $actionArgs = $app['resolver']->getArguments(
                    $app['request'],
                    $actionFunc
                );

                // ...and we finally trigger the Closure!
                return call_user_func_array($actionFunc, $actionArgs);
            }
        )->method($actionData['method']);

        // Route name management
        if (isset($actionData['name'])) {
            $controller->bind($actionData['name']);
        }

        // Route "before" middlewares management
        $this->handleActionBeforeMiddlewares($plugin, $controller, $actionData);

        // Route requirements management
        $this->handleActionRequirements($plugin, $controller, $actionData);

        /*
         * TODO: add a optional "verbose" mode to Behaviours
        $app['monolog']->addDebug(
            sprintf('Route "%s" (method %s) registered.', $actionData['url'], $actionData['method'])
        );
        */
    }

    protected function handleActionBeforeMiddlewares(
        Plugin $plugin,
        Controller $controller,
        array $actionData
    ) {
        $app = $this->app;

        // Whole Plugin "general/actionsBefore" middlewares goes first
        if (isset($plugin->data['@general']['actionsBefore'])) {
            $plugin->data['@general']['actionsBefore'] = ArrayUtils::getArray(
                $plugin->data['@general']['actionsBefore']
            );

            foreach ($plugin->data['@general']['actionsBefore'] as $wholePluginbeforeMiddlewareServiceName) {
                $controller->before($app[$wholePluginbeforeMiddlewareServiceName]);
            }
        }

        // Now, let's handle this route specific middlewares
        if (isset($actionData['before'])) {
            $actionData['before'] = ArrayUtils::getArray($actionData['before']);
            foreach ($actionData['before'] as $beforeMiddlewareServiceName) {
                $controller->before($app[$beforeMiddlewareServiceName]);
            }
        }
    }

    protected function handleActionRequirements(
        Plugin $plugin,
        Controller $controller,
        array $actionData
    ) {
        if (isset($actionData['requirements'])) {
            foreach ($actionData['requirements'] as $argName => $pattern) {
                $controller->assert($argName, $pattern);
            }
        }
    }
}
