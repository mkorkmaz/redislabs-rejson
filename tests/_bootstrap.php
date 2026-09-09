<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

$environmentFile = dirname(__DIR__) . '/.env';
if (is_file($environmentFile)) {
    $dotenv = new Dotenv();
    $dotenv->usePutenv();
    $dotenv->load($environmentFile);
}
