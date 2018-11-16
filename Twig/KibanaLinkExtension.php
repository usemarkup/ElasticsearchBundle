<?php

declare(strict_types=1);

namespace Markup\ElasticsearchBundle\Twig;

use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * A Twig extension that is able to build a link to a Kibana console output.
 */
class KibanaLinkExtension extends AbstractExtension
{
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var string
     */
    private $kibanaHost;

    /**
     * @var bool
     */
    private $shouldLinkToKibana;

    public function __construct(UrlGeneratorInterface $urlGenerator, string $kibanaHost, bool $shouldLinkToKibana)
    {
        $this->urlGenerator = $urlGenerator;
        $this->kibanaHost = $kibanaHost;
        $this->shouldLinkToKibana = $shouldLinkToKibana;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('can_show_link_to_kibana', function (): bool {
                return $this->shouldLinkToKibana;
            }),
            new TwigFunction('link_to_kibana', function (string $token, string $interactionId): ?string {
                try {
                    $loadFromUri = $this->urlGenerator->generate(
                        'markup_elasticsearch_json_for_kibana',
                        [
                            'token' => $token,
                            'interactionId' => $interactionId,
                        ],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    );
                } catch (RouteNotFoundException $e) {
                    return null;
                }
                return $this->kibanaHost.'/app/kibana#/dev_tools/console?'.http_build_query([
                        'load_from' => $loadFromUri,
                    ]);
            }),
        ];
    }
}
