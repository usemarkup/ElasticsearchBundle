<?php

declare(strict_types=1);

namespace Markup\ElasticsearchBundle\Controller;

use Markup\ElasticsearchBundle\DataCollector\ElasticDataCollector;
use Markup\ElasticsearchBundle\DataCollector\KibanaConsoleRequestPrinter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\Profiler;

class KibanaConsoleJsonController
{
    public function viewConsoleJson(
        string $token,
        string $interactionId,
        ?Profiler $profiler
    ): Response {
        if (null === $profiler) {
            return $this->respondWith404();
        }
        /** @var Profile|null $profile */
        $profile = $profiler->loadProfile($token);
        if (!$profile) {
            return $this->respondWith404();
        }

        try {
            /** @var ElasticDataCollector $elasticCollector */
            $elasticCollector = $profile->getCollector(ElasticDataCollector::NAME);
        } catch (\Exception $e) {
            return $this->respondWith404();
        }

        $printed = (new KibanaConsoleRequestPrinter())->printConsoleJsonBody($elasticCollector, $interactionId);
        if (null === $printed) {
            return $this->respondWith404();
        }

        $response = new Response($printed);
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response;
    }

    private function respondWith404(): Response
    {
        return new Response('', 404);
    }
}
