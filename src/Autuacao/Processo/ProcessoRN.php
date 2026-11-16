<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Autuacao\Processo;

use ProcessoJudicialEstudo\Compartilhado\Cache\Caches;
use ProcessoJudicialEstudo\Compartilhado\Nucleo\Banco;
use ProcessoJudicialEstudo\Compartilhado\Nucleo\NucleoException;
use ProcessoJudicialEstudo\Compartilhado\Nucleo\NucleoRN;
use ProcessoJudicialEstudo\Compartilhado\Util\Cnj;
use ProcessoJudicialEstudo\Movimentacao\Evento\EventoDTO;
use ProcessoJudicialEstudo\Movimentacao\Evento\EventoRN;

/**
 * Domínio: Processo (autuação, distribuição, situação, sigilo).
 * Chamadas externas: $rn->cadastrar($dto), $rn->consultar($dto), $rn->listar($dto)...
 *
 * Métodos públicos despachados pelo NucleoRN::__call (declarados para o PHPStan e a IDE):
 * @method ProcessoDTO cadastrar(ProcessoDTO $dto)
 * @method void alterarSituacao(ProcessoDTO $dto)
 * @method ProcessoDTO|null consultar(ProcessoDTO $dto)
 * @method list<ProcessoDTO> listar(ProcessoDTO $dto)
 * @method int contar(ProcessoDTO $dto)
 */
class ProcessoRN extends NucleoRN
{
    public const SITUACOES = ['A' => 'Em andamento', 'S' => 'Suspenso', 'B' => 'Baixado'];

    /** Validade do processo no cache: 5 minutos (a invalidação na alteração garante dado atualizado antes disso). */
    private const CACHE_SEGUNDOS = 300;

    protected function inicializarObjBanco(): Banco
    {
        return Banco::getInstance();
    }

    private function bd(): ProcessoBD
    {
        return new ProcessoBD($this->getObjBanco());
    }

    /** Autua e distribui; registra o evento de distribuição na MESMA transação. */
    protected function cadastrarControlado(ProcessoDTO $dto): ProcessoDTO
    {
        $ex = new NucleoException('Dados do processo inválidos.');
        $numero = $dto->getStrNumero() ?? '';
        if (!Cnj::validar($numero)) {
            $ex->adicionarValidacao('Número inválido (formato ou dígito verificador CNJ).');
        }
        $obrigatorios = ['Classe' => $dto->getStrClasse(), 'Assunto' => $dto->getStrAssunto(), 'Órgão julgador' => $dto->getStrOrgaoJulgador()];
        foreach ($obrigatorios as $rotulo => $valor) {
            if (trim($valor ?? '') === '') {
                $ex->adicionarValidacao("$rotulo é obrigatório.");
            }
        }
        $ex->lancarValidacoes();

        $filtro = new ProcessoDTO();
        $filtro->setStrNumero($numero)->retNumIdProcesso();
        if ($this->bd()->consultar($filtro) !== null) {
            $ex->adicionarValidacao('Já existe processo com este número.');
            $ex->lancarValidacoes();
        }

        if (!$dto->isSetStrSituacao()) {
            $dto->setStrSituacao('A');
        }
        if (!$dto->isSetStrSinSigilo()) {
            $dto->setStrSinSigilo('N');
        }
        if (!$dto->isSetDtaDistribuicao()) {
            $dto->setDtaDistribuicao(date('Y-m-d'));
        }

        $this->bd()->cadastrar($dto);

        $ev = new EventoDTO();
        $ev->setNumIdProcesso($dto->getNumIdProcesso());
        $ev->setStrTipo('DISTRIBUICAO');
        $ev->setStrDescricao('Distribuído ao órgão julgador: ' . $dto->getStrOrgaoJulgador());
        new EventoRN()->cadastrar($ev); // entra na transação aberta aqui
        return $dto;
    }

    protected function alterarSituacaoControlado(ProcessoDTO $dto): void
    {
        $nova = (string) $dto->getStrSituacao();
        if (!isset(self::SITUACOES[$nova])) {
            $ex = new NucleoException('Situação inválida.');
            $ex->adicionarValidacao('Situação deve ser A, S ou B.');
            $ex->lancarValidacoes();
        }
        $alt = new ProcessoDTO();
        $alt->setNumIdProcesso($dto->getNumIdProcesso())->setStrSituacao($nova);
        $this->bd()->alterar($alt);
        // Invalida o cache (em vez de atualizá-lo): a próxima consulta busca o dado novo no banco
        Caches::atual()->remover(self::chaveCache((int) $dto->getNumIdProcesso()));

        $ev = new EventoDTO();
        $ev->setNumIdProcesso($dto->getNumIdProcesso());
        $ev->setStrTipo($nova === 'B' ? 'BAIXA' : 'DECISAO');
        $ev->setStrDescricao('Situação alterada para: ' . self::SITUACOES[$nova]);
        new EventoRN()->cadastrar($ev);
    }

    /**
     * Consulta com cache-aside para o caso mais comum (a tela do processo pede só pelo id):
     * procura no cache; se não achar, vai ao banco e guarda o resultado no cache.
     * Consultas com outros filtros ou colunas escolhidas (ret) vão direto ao banco.
     */
    protected function consultarConectado(ProcessoDTO $dto): ?ProcessoDTO
    {
        if (!self::ehConsultaSoPorId($dto)) {
            return $this->bd()->consultar($dto);
        }
        $chave = self::chaveCache((int) $dto->getNumIdProcesso());
        $cache = Caches::atual();

        $guardado = $cache->buscar($chave);
        if ($guardado !== null) {
            $processo = new ProcessoDTO();
            foreach ($guardado as $atributo => $valor) {
                $processo->definirValor($atributo, $valor);
            }
            return $processo;
        }

        $processo = $this->bd()->consultar($dto);
        if ($processo !== null) {
            $cache->guardar($chave, $processo->valores(), self::CACHE_SEGUNDOS);
        }
        return $processo;
    }

    private static function chaveCache(int $id): string
    {
        return "processo:$id";
    }

    private static function ehConsultaSoPorId(ProcessoDTO $dto): bool
    {
        return array_keys($dto->valores()) === ['NumIdProcesso']
            && $dto->operadores() === [] && $dto->retorno() === [] && $dto->ordem() === [];
    }

    /** @return list<ProcessoDTO> */
    protected function listarConectado(ProcessoDTO $dto): array
    {
        return $this->bd()->listar($dto);
    }

    protected function contarConectado(ProcessoDTO $dto): int
    {
        return $this->bd()->contar($dto);
    }
}
