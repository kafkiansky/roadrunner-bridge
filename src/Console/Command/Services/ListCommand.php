<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerBridge\Console\Command\Services;

use Spiral\Console\Command;
use Spiral\RoadRunner\Services\Manager;

final class ListCommand extends Command
{
    protected const NAME = 'rr:services';
    protected const DESCRIPTION = 'List available RoadRunner services';

    public function perform(Manager $manager): int
    {
        $services = $manager->list();

        if ($services === []) {
            $this->writeln('<comment>No available services were found.</comment>');

            return self::SUCCESS;
        }

        $table = $this->table([
            'Service:',
            'CPU usage:',
            'Memory usage:',
            'PID:',
            'Command:',
        ]);

        foreach ($services as $name) {
            $status = $manager->status($name);

            $table->addRow([
                $name,
                $status['cpu_percent'],
                $status['memory_usage'],
                $status['pid'],
                $status['command'],
            ]);
        }

        $table->render();

        return self::SUCCESS;
    }
}
