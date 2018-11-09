<?php

namespace Markup\ElasticsearchBundle;

use Symfony\Component\Console\Application;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MarkupElasticsearchBundle extends Bundle
{
    public function registerCommands(Application $application)
    {
        //do nothing (this method can be removed when using Symfony 4)
    }
}
