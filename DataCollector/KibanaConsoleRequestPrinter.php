<?php

declare(strict_types=1);

namespace Markup\ElasticsearchBundle\DataCollector;

use Markup\Json\Encoder;

class KibanaConsoleRequestPrinter
{
    public function printConsoleJsonBody(ElasticDataCollector $collector, string $interactionId): ?string
    {
        $interaction = $collector->getInteraction($interactionId);
        if (null === $interaction) {
            return null;
        }

        [
            'http_method' => $method,
            'uri' => $absoluteUri,
            'request_body' => $requestBody,
        ] = $interaction;

        $uri = implode(
            '?',
            array_filter([
                parse_url($absoluteUri, PHP_URL_PATH),
                parse_url($absoluteUri, PHP_URL_QUERY),
            ])
        );

        $body = $method.' '.$uri."\n";
        $jsonChunks = explode("\n", $requestBody);
        foreach ($jsonChunks as $jsonChunk) {
            $body .= Encoder::encode(Encoder::decode($jsonChunk), JSON_PRETTY_PRINT);
        }

        return $body."\n";
    }
}
