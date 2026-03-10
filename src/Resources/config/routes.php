<?php

declare(strict_types=1);

use Museado\ArkBundle\Controller\ArkRedirectController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('museado_ark_resolve', '/ark/{naan}/{name}')
        ->controller(ArkRedirectController::class)
        ->methods(['GET'])
    ;
};
