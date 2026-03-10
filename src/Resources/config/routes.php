<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('survos_ark_resolve', '/ark/{naan}/{name}')
        ->controller('survos_ark.controller.redirect')
        ->requirements(['name' => '.+'])
        ->methods(['GET'])
    ;
};
