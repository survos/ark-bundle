<?php

declare(strict_types=1);

namespace Survos\ArkBundle\Controller;

use Survos\ArkBundle\Service\ArkRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ArkRedirectController
{
    public function __construct(
        private readonly ArkRegistry $registry,
        private readonly string $naan,
        private readonly bool $n2tResolve,
    ) {}

    #[Route('/{naan}/{name}', name: 'survos_ark_resolve', requirements: ['name' => '.+'], methods: ['GET'])]
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
            return new Response('This ARK is managed by SurvosArkBundle.', Response::HTTP_OK, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        return new RedirectResponse($url, Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/{naan}/_probe', name: 'survos_ark_probe', methods: ['GET'])]
    public function probe(string $naan): Response
    {
        if ($naan !== $this->naan) {
            return new Response('Unknown NAAN.', Response::HTTP_NOT_FOUND);
        }

        $body = implode("\n", [
            'ok: true',
            sprintf('naan: %s', $naan),
            sprintf('n2t_probe: https://n2t.net/ark:/%s/?', $naan),
        ]);

        return new Response($body, Response::HTTP_OK, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
