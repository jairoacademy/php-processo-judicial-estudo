<?php

declare(strict_types=1);

namespace ProcessoJudicialEstudo\Parte;

use ProcessoJudicialEstudo\Nucleo\Banco;
use ProcessoJudicialEstudo\Nucleo\NucleoException;
use ProcessoJudicialEstudo\Nucleo\NucleoRN;
use ProcessoJudicialEstudo\Processo\ProcessoDTO;
use ProcessoJudicialEstudo\Processo\ProcessoRN;
use ProcessoJudicialEstudo\Util\Documento;

/**
 * Domínio: Partes e representantes (polo ativo/passivo, advogado, MP, defensoria).
 *
 * Métodos públicos despachados pelo NucleoRN::__call (declarados para o PHPStan e a IDE):
 * @method ParteDTO cadastrar(ParteDTO $dto)
 * @method list<ParteDTO> listar(ParteDTO $dto)
 */
class ParteRN extends NucleoRN
{
    public const POLOS = ['A' => 'Ativo', 'P' => 'Passivo', 'T' => 'Terceiro'];
    public const TIPOS = ['AUTOR', 'REU', 'ADVOGADO', 'MP', 'DEFENSORIA', 'TERCEIRO'];

    protected function inicializarObjBanco(): Banco
    {
        return Banco::getInstance();
    }

    private function bd(): ParteBD
    {
        return new ParteBD($this->getObjBanco());
    }

    protected function cadastrarControlado(ParteDTO $dto): ParteDTO
    {
        $ex = new NucleoException('Dados da parte inválidos.');
        if (trim((string) $dto->getStrNome()) === '') {
            $ex->adicionarValidacao('Nome é obrigatório.');
        }
        if (!isset(self::POLOS[(string) $dto->getStrPolo()])) {
            $ex->adicionarValidacao('Polo deve ser A, P ou T.');
        }
        if (!in_array($dto->getStrTipo(), self::TIPOS, true)) {
            $ex->adicionarValidacao('Tipo de parte inválido.');
        }
        $doc = $dto->getStrDocumento() ?? '';
        if ($doc !== '' && !Documento::validar($doc)) {
            $ex->adicionarValidacao('CPF/CNPJ inválido.');
        }
        $ex->lancarValidacoes();

        $proc = new ProcessoDTO();
        $proc->setNumIdProcesso($dto->getNumIdProcesso())->retNumIdProcesso();
        if (new ProcessoRN()->consultar($proc) === null) { // RN chama RN, nunca o BD alheio
            $ex->adicionarValidacao('Processo inexistente.');
            $ex->lancarValidacoes();
        }
        if ($doc !== '') {
            $dto->setStrDocumento(Documento::somenteDigitos($doc));
        }
        return $this->bd()->cadastrar($dto);
    }

    /** @return list<ParteDTO> */
    protected function listarConectado(ParteDTO $dto): array
    {
        return $this->bd()->listar($dto);
    }
}
