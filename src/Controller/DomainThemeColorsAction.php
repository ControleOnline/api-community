<?php

namespace App\Controller;

use App\Service\DomainThemeBinding;
use ControleOnline\Controller\GetThemeColorsAction;
use ControleOnline\Service\DomainService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator(decorates: GetThemeColorsAction::class)]
final class DomainThemeColorsAction
{
    public function __construct(private EntityManagerInterface $manager, private DomainService $domains) {}

    public function __invoke(Request $request): Response
    {
        [$domain] = DomainThemeBinding::resolve($this->manager, $this->domains->getDomain());
        $theme = $domain?->getTheme();
        if (!$theme) return new Response('', Response::HTTP_NOT_FOUND);

        $css = [':root{'];
        foreach ($theme->getColors(true) as $key => $color) {
            $css[] = "    --q-$key: $color;";
            $css[] = "    --$key: $color;";
        }
        $css[] = '}';

        return new Response(implode(PHP_EOL, $css), Response::HTTP_OK, ['Content-Type' => 'text/css']);
    }
}
