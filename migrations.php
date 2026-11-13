<?php

declare(strict_types=1);

// Configuração do Doctrine Migrations (lida por vendor/bin/doctrine-migrations).
return [
    'migrations_paths' => [
        'ProcessoJudicialEstudo\Migrations' => __DIR__ . '/migrations',
    ],
    // O MySQL faz commit implícito a cada DDL (CREATE/ALTER/DROP), então uma transação
    // em volta da migration não protege nada; cada migration deve ser pequena e ter down().
    'transactional' => false,
    'all_or_nothing' => false,
    'check_database_platform' => true,
];
