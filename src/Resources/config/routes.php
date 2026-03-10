<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('museado_ark_resolve', '/ark/{naan}/{name}')
        ->controller('museado_ark.controller.redirect')
        ->methods(['GET'])
    ;
};
