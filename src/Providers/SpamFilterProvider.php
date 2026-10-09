<?php

namespace Prm\Moderation\Providers;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Config;
use Prm\Moderation\Spam\Analyzer;
use Prm\Moderation\Spam\Detectors\BlockedWordsDetector;
use Prm\Moderation\Spam\Detectors\EmailDetector;
use Prm\Moderation\Spam\Detectors\PhoneDetector;
use Prm\Moderation\Spam\Detectors\UrlDetector;
use Prm\Moderation\Spam\SpamActions;
use Prm\Moderation\Spam\SpamConfig;

class SpamFilterProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(SpamConfig::class, function ($container) {
            return new SpamConfig(
                $container->make('flarum.settings'),
                $container->make(Config::class)
            );
        });

        $this->container->singleton(SpamActions::class);

        $this->container->singleton(Analyzer::class, function ($container) {
            $analyzer = new Analyzer(
                $container->make(SpamConfig::class),
                $container->make('log')
            );

            $analyzer->addDetector($container->make(PhoneDetector::class));
            $analyzer->addDetector($container->make(EmailDetector::class));
            $analyzer->addDetector($container->make(UrlDetector::class));
            $analyzer->addDetector($container->make(BlockedWordsDetector::class));

            return $analyzer;
        });
    }
}
