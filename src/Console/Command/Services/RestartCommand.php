<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerBridge\Console\Command\Services;

use Spiral\Console\Command;
use Spiral\RoadRunner\Services\Manager;
use Symfony\Component\Console\Input\InputArgument;

final class RestartCommand extends Command
{
    protected const NAME = 'rr:service-restart';
    protected const DESCRIPTION = 'Restart service with given name.';

    protected const ARGUMENTS = [
        ['name', InputArgument::REQUIRED, 'Service name'],
    ];

    public function perform(Manager $manager): int
    {
        $status = $manager->restart($this->argument('name'));

        if (!$status) {
            $this->writeln('<Error>Service restart failed.</Error>');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
