<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Web;

use RuntimeException;

/** Registro inexistente pedido por uma tela (por exemplo, um processo com id que não existe). Vira 404. */
final class NaoEncontradoException extends RuntimeException {}
