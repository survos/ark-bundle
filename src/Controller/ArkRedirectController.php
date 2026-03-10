<?php

declare(strict_types=1);

namespace Museado\ArkBundle\Controller;

use Museado\ArkBundle\Service\ArkRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ArkRedirectController
{
    public function __construct(
        private readonly ArkRegistry $registry,
        private readonly string $naan,
        private readonly bool $n2tResolve,
    ) {}

    public function __invoke(Request $request, string $naan, string $name): Response
    {
        if ($naan !== $this->naan) {
            return new Response('Unknown NAAN.', Response::HTTP_NOT_FOUND);
        }

        $url = $this->registry->resolve($name);
        if ($url === null) {
            return new Response('ARK not found.', Response::HTTP_NOT_FOUND);
        }

        if ($request->query->has('info')) {
            $body = implode("\n", [
                'erc:',
                'who:   Unknown',
                sprintf('what:  ARK %s', $name),
                'when:  unknown',
                sprintf('where: %s', $url),
            ]);

            return new Response($body, Response::HTTP_OK, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        $uri = $request->getRequestUri();
        if ($this->n2tResolve && str_ends_with($uri, '??')) {
            return new Response('This ARK is managed by MuseadoArkBundle.', Response::HTTP_OK, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        return new RedirectResponse($url, Response::HTTP_MOVED_PERMANENTLY);
    }
}
