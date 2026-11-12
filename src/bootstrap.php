<?php
declare(strict_types=1);

// Autoload PSR-4 minimalista (sem Composer): ProcessoJudicialEstudo\Pasta\Classe -> src/Pasta/Classe.php
spl_autoload_register(static function (string $classe): void {
    $prefixo = 'ProcessoJudicialEstudo\\';
    if (strncmp($classe, $prefixo, strlen($prefixo)) !== 0) {
        return;
    }
    $arquivo = __DIR__ . '/' . str_replace('\\', '/', substr($classe, strlen($prefixo))) . '.php';
    if (is_file($arquivo)) {
        require $arquivo;
    }
});
