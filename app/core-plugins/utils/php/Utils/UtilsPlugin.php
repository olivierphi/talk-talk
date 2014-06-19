<?php

namespace TalkTalk\CorePlugin\Utils;

use TalkTalk\Kernel\Plugin\PluginBase;
use TalkTalk\Kernel\Plugin\PluginInterface;

class UtilsPlugin extends PluginBase
{

    protected $pluginId = 'utils';
    protected $pluginType = PluginInterface::PLUGIN_TYPE_CORE_PLUGIN;
    protected $hasJsBootstrapModule = false;

    public function registerRestResources()
    {
        parent::registerRestResources();

        $NS = 'TalkTalk\\CorePlugin\\Utils\\Controller';

        if ($this->app->vars['debug']) {
            $this->app->addRestResource('GET', '/utils/phpinfo', "$NS\\UtilsController::phpinfo");
        }

        $this->app->addRestResource('GET', '/utils/js-app-compilation', "$NS\\UtilsController::compileJsApp");
        $this->app->addRestResource('POST', '/utils/js-app-compilation', "$NS\\UtilsController::saveJsAppCompilation");
    }

    /**
     * @inheritdoc
     */
    public function getJsModulesToCompile()
    {
        return array();

        $myJsAmdModulesRootPath = $this->app->vars['app.root_path'] . '/' . $this->path . '/assets/js/amd';
        $myJsAmdModulesFilesPaths = $this->app
            ->get('utils.io')
            ->rglob('** /*.js', $myJsAmdModulesRootPath . '/mixins');

        $app = &$this->app;
        $myJsAmdModulesIds = array_map(
            function ($jsFilePath) use ($app) {
                return preg_replace('~\.js$~', '', $app->appPath($jsFilePath));
            },
            $myJsAmdModulesFilesPaths
        );

        return $myJsAmdModulesIds;
    }



    /**
     * @inheritdoc
     */
    public function getTemplatesFolders()
    {
        return array(
            array(
                'namespace' => 'utils',
                'path' => $this->getAbsPath() . '/php/Utils/Resources/templates'
            )
        );
    }


}