<?php

$app->before(
    function () use ($app) {
        $phpBbDbSettings = $app['session']->get('phpbb.db-settings');
        if (null !== $phpBbDbSettings) {
            $app['phpbb.db.init']($phpBbDbSettings);
        }
    }
);