<?php

namespace TalkTalk\Core\Plugin\PackingBehaviour;

use TalkTalk\Core\Plugin\Plugin;

class NewPackersPacker extends BasePacker
{

    protected $myConfigKey = '@pluginsPackers';

    public function beforePacking(Plugin $plugin)
    {
        if (empty($plugin->config[$this->myConfigKey])) {
            return null;
        }

        $myConfigPart = $plugin->config[$this->myConfigKey];

        // In this Packer we return no code, but we add the new Plugins Packing Behaviours
        // before others Packers "getPhpCodeToPack()" calls
        foreach ($myConfigPart as $packerClass) {

            if (!class_exists($packerClass, false)) {
                $packerClassPath = $plugin->path . '/classes/' . str_replace('\\', DIRECTORY_SEPARATOR, $packerClass) . '.php' ;
                require_once $packerClassPath;
            }

            $packerInstance = new $packerClass();
            Plugin::addBehaviour($packerInstance);
        }
    }
}
