<?php

declare(strict_types=1);

// Conexão usada pelo Doctrine Migrations: as mesmas variáveis de ambiente da aplicação.
use Doctrine\DBAL\DriverManager;
use ProcessoJudicialEstudo\Nucleo\ConfiguracaoBanco;

require __DIR__ . '/vendor/autoload.php';

$config = ConfiguracaoBanco::doAmbiente();

return DriverManager::getConnection([
    'driver' => 'pdo_mysql',
    'host' => $config->host,
    'port' => $config->porta,
    'dbname' => $config->banco,
    'user' => $config->usuario,
    'password' => $config->senha,
    'charset' => 'utf8mb4',
]);
