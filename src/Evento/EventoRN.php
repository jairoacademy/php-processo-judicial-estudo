<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Evento;

use DateTimeImmutable;
use ProcessoJudicialEstudo\Nucleo\Banco;
use ProcessoJudicialEstudo\Nucleo\NucleoException;
use ProcessoJudicialEstudo\Nucleo\NucleoRN;
use ProcessoJudicialEstudo\Processo\ProcessoDTO;
use ProcessoJudicialEstudo\Processo\ProcessoRN;
use ProcessoJudicialEstudo\Util\Prazo;

/**
 * Domínio: Eventos/movimentações e prazos. Todo ato processual vira um evento tipado;
 * citação e intimação geram prazo em dias úteis.
 *
 * Métodos públicos despachados pelo NucleoRN::__call (declarados para o PHPStan e a IDE):
 * @method EventoDTO cadastrar(EventoDTO $dto)
 * @method list<EventoDTO> listar(EventoDTO $dto)
 */
class EventoRN extends NucleoRN
{
    public const TIPOS = ['DISTRIBUICAO', 'PETICAO', 'JUNTADA', 'CITACAO', 'INTIMACAO', 'AUDIENCIA', 'DECISAO', 'SENTENCA', 'BAIXA'];
    private const COM_PRAZO = ['CITACAO', 'INTIMACAO'];

    /** @var string[] feriados Y-m-d usados na contagem (injetável para testes) */
    public static array $feriados = [];

    protected function inicializarObjBanco(): Banco
    {
        return Banco::getInstance();
    }

    private function bd(): EventoBD
    {
        return new EventoBD($this->getObjBanco());
    }

    protected function cadastrarControlado(EventoDTO $dto): EventoDTO
    {
        $ex = new NucleoException('Dados do evento inválidos.');
        if (!in_array($dto->getStrTipo(), self::TIPOS, true)) {
            $ex->adicionarValidacao('Tipo de evento inválido.');
        }
        if (trim((string) $dto->getStrDescricao()) === '') {
            $ex->adicionarValidacao('Descrição é obrigatória.');
        }
        $comPrazo = in_array($dto->getStrTipo(), self::COM_PRAZO, true);
        $dias = $dto->getNumDiasPrazo() ?? 0;
        if ($comPrazo && $dias <= 0) {
            $ex->adicionarValidacao('Citação/intimação exige prazo em dias úteis (> 0).');
        }
        $ex->lancarValidacoes();

        $proc = new ProcessoDTO();
        $proc->setNumIdProcesso($dto->getNumIdProcesso())->retNumIdProcesso()->retStrSituacao();
        $processo = new ProcessoRN()->consultar($proc);
        if ($processo === null) {
            $ex->adicionarValidacao('Processo inexistente.');
            throw $ex;
        }
        if ($processo->getStrSituacao() === 'B' && $dto->getStrTipo() !== 'BAIXA') {
            $ex->adicionarValidacao('Processo baixado não admite novos eventos.');
            $ex->lancarValidacoes();
        }

        $agora = new DateTimeImmutable('now');
        if (!$dto->isSetDthEvento()) {
            $dto->setDthEvento($agora->format('Y-m-d H:i:s'));
        }
        if (!$dto->isSetStrUsuario()) {
            $dto->setStrUsuario('sistema');
        }
        if ($comPrazo) {
            $base = new DateTimeImmutable($dto->getDthEvento() ?? 'now');
            $dto->setDtaPrazoFinal(Prazo::somarDiasUteis($base, $dias, self::$feriados)->format('Y-m-d'));
        }
        return $this->bd()->cadastrar($dto);
    }

    /** @return list<EventoDTO> */
    protected function listarConectado(EventoDTO $dto): array
    {
        return $this->bd()->listar($dto);
    }
}
