<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerBridge\Bootloader;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Boot\EnvironmentInterface as GlobalEnvironmentInterface;
use Spiral\Core\Container;
use Spiral\Goridge\Relay;
use Spiral\Goridge\RPC\RPC;
use Spiral\Goridge\RPC\RPCInterface;
use Spiral\Http\Config\HttpConfig;
use Spiral\Http\Diactoros\ServerRequestFactory;
use Spiral\Http\Diactoros\StreamFactory;
use Spiral\Http\Diactoros\UploadedFileFactory;
use Spiral\RoadRunner\Environment;
use Spiral\RoadRunner\EnvironmentInterface;
use Spiral\RoadRunner\Http\PSR7Worker;
use Spiral\RoadRunner\Http\PSR7WorkerInterface;
use Spiral\RoadRunner\Worker;
use Spiral\RoadRunner\WorkerInterface;

final class RoadRunnerBootloader extends Bootloader
{
    public function boot(Container $container)
    {
        //
        // Register RoadRunner Environment
        //
        $container->bindSingleton(EnvironmentInterface::class, Environment::class);
        $container->bindSingleton(
            Environment::class,
            static function (GlobalEnvironmentInterface $env): EnvironmentInterface {
                return new Environment($env->getAll());
            }
        );

        //
        // Register RPC
        //
        $container->bindSingleton(RPCInterface::class, RPC::class);
        $container->bindSingleton(RPC::class, static function (EnvironmentInterface $env): RPCInterface {
            return new RPC(
                Relay::create($env->getRPCAddress())
            );
        });

        //
        // Register Worker
        //
        $container->bindSingleton(WorkerInterface::class, Worker::class);
        $container->bindSingleton(Worker::class, static function (EnvironmentInterface $env): WorkerInterface {
            return Worker::createFromEnvironment($env);
        });

        //
        // Register PSR Worker
        //
        $container->bindSingleton(PSR7WorkerInterface::class, PSR7Worker::class);

        $container->bindSingleton(PSR7Worker::class, static function (
            WorkerInterface $worker,
            ServerRequestFactory $requests,
            StreamFactory $streams,
            UploadedFileFactory $uploads,
            HttpConfig $config
        ): PSR7WorkerInterface {
            $psr7Worker = new PSR7Worker($worker, $requests, $streams, $uploads);

            if (($chunkSize = $config->getChunkSize()) !== null) {
                $psr7Worker->chunkSize = $chunkSize;
            }

            return $psr7Worker;
        });
    }
}
