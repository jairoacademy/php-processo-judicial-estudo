<?php

declare(strict_types=1);

if (!function_exists('e')) {
    /**
     * Escape de saída para HTML. Todo valor variável impresso num template passa por aqui:
     * é a proteção contra XSS (o equivalente ao auto-escape do Twig ou do Thymeleaf).
     */
    function e(int|string|null $valor): string
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
