<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Processo;

use ProcessoJudicialEstudo\Evento\EventoDTO;
use ProcessoJudicialEstudo\Evento\EventoRN;
use ProcessoJudicialEstudo\Nucleo\Banco;
use ProcessoJudicialEstudo\Nucleo\NucleoException;
use ProcessoJudicialEstudo\Nucleo\NucleoRN;
use ProcessoJudicialEstudo\Util\Cnj;

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

        $ev = new EventoDTO();
        $ev->setNumIdProcesso($dto->getNumIdProcesso());
        $ev->setStrTipo($nova === 'B' ? 'BAIXA' : 'DECISAO');
        $ev->setStrDescricao('Situação alterada para: ' . self::SITUACOES[$nova]);
        new EventoRN()->cadastrar($ev);
    }

    protected function consultarConectado(ProcessoDTO $dto): ?ProcessoDTO
    {
        return $this->bd()->consultar($dto);
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
