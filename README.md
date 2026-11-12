# php-processo-judicial-estudo

Projeto de estudo em **PHP 8** sobre o domínio de **processo judicial eletrônico**. O objetivo é duplo: conhecer o negócio (como um processo nasce, recebe partes, eventos e prazos) e, por tabela, refrescar e modernizar o PHP de quem já trabalhou com a linguagem no passado.

> **Aviso.** Projeto pessoal e independente, **sem vínculo** com tribunais, com o CNJ ou com qualquer sistema judicial existente. Não contém código de terceiros, nem de sistemas de tribunais, nem de frameworks proprietários. A arquitetura em camadas é um padrão comum e foi inspirada na documentação pública do SEI (manual de módulos, disponível no Portal do Software Público); as regras de domínio seguem normas públicas (CNJ, CPC). Nomes de sistemas e órgãos pertencem aos seus respectivos titulares. Onde algo é suposição, está marcado como tal.

## Como rodar (PHP 8.1+; usa SQLite, sem instalar nada)
```
php tests/run.php                  # 23 verificações (CNJ, CPF/CNPJ, prazos, rollback, filtros do DTO)
php demo.php                       # 50 transações em loop x 1 transação envolvente
php -S localhost:8080 -t public    # interface web: ?acao=processo_listar
```
Para usar MySQL, troque o DSN em `Banco::configurar()` (o esquema usa SQL simples; ajuste `AUTOINCREMENT` para `AUTO_INCREMENT`).

## Arquitetura
Página (`public/index.php`) -> **RN** (regra de negócio) -> **BD** (acesso a dados) -> `Banco` (PDO). O tráfego entre camadas usa **DTOs**.

| Arquivo | Conceito |
|---|---|
| `src/Nucleo/NucleoDTO.php` | DTO como construtor de consultas: `set/get/ret/setOrd/isSet` por `__call`; `set` vira WHERE em consulta; operadores `IN`, `LIKE`, etc. |
| `src/Nucleo/NucleoBD.php` | CRUD genérico guiado pelo DTO, valores sempre por `bind`, colunas só do DTO (nunca da entrada do usuário) |
| `src/Nucleo/NucleoRN.php` | `__call` abre conexão (`Conectado`) ou conexão + transação (`Controlado`), com commit/rollback |
| `src/Nucleo/Banco.php` | Singleton de PDO e controle de transação (aninhada "entra" na externa) |
| `src/Processo/ProcessoRN.php` | Uma RN chama outra RN, nunca o BD alheio; o evento de distribuição entra na mesma transação |
| `src/Util/` | Número CNJ (DV módulo 97), CPF/CNPJ, prazo em dias úteis |
| `public/index.php` | Roteamento `?acao=entidade_acao`, CSRF, escape de saída |

Armadilha demonstrada em `demo.php`: chamar um método transacional dentro de um loop abre uma transação por iteração (50 commits, e uma falha no meio deixa gravação parcial).

## Domínios de um sistema de processo judicial eletrônico
Legenda: **PADRÃO** = decorre de norma pública (CNJ, CPC) e é praticamente certo; **COMUM** = suposição razoável sobre o que esse tipo de sistema precisa ter.

| # | Domínio | O que cobre | Confiança | Aqui |
|---|---|---|---|---|
| 1 | Processo e numeração | Autuação, número único CNJ `NNNNNNN-DD.AAAA.J.TR.OOOO`, situação | PADRÃO | Sim |
| 2 | Classes e assuntos | Tabelas Processuais Unificadas do CNJ | PADRÃO | Texto livre |
| 3 | Competência e distribuição | Sorteio, prevenção, redistribuição, órgão julgador | PADRÃO | Só o campo órgão |
| 4 | Partes e representantes | Polos, advogado, MP, Defensoria, CPF/CNPJ | PADRÃO | Sim |
| 5 | Eventos / movimentações | Todo ato como evento tipado; histórico | COMUM | Sim |
| 6 | Documentos e peticionamento | Petições, juntada, assinatura ICP-Brasil, hash | PADRÃO | Não |
| 7 | Citação, intimação e prazos | Comunicação eletrônica, contagem em dias úteis, feriados | PADRÃO (CPC) | Simplificado |
| 8 | Painel e organização do trabalho | Filas por usuário/unidade, lembretes, etiquetas | COMUM | Não |
| 9 | Audiências e pautas | Agendamento, sala, ata | COMUM | Não |
| 10 | Gabinete e julgamento | Minutas, decisões, sentenças, acórdãos, sessões | COMUM | Tipos de evento |
| 11 | Recursos e remessa | Subida e descida entre instâncias | COMUM | Não |
| 12 | Cumprimento e execução | Mandados e oficiais de justiça, penhora online, precatórios e RPV | COMUM | Não |
| 13 | Custas e guias | Taxa judiciária, guia, gratuidade | COMUM | Não |
| 14 | Sigilo e LGPD | Segredo de justiça, níveis de acesso | PADRÃO | Flag de sigilo |
| 15 | Perfis, permissões e unidades | Perfil -> recurso -> ação, validação de permissão | COMUM | Não |
| 16 | Interoperabilidade | MNI, DataJud, integrações com MP, Defensoria, PGE, OAB | PADRÃO/COMUM | Não |
| 17 | Auditoria e estatística | Log de acesso e alteração, relatórios | COMUM | Não |
| 18 | Migração de acervo | Convivência e migração de sistemas legados | COMUM | Não |
| 19 | Parametrização local | Customização por tribunal sem alterar o núcleo | COMUM | Não |

## Próximos passos
1. Perfil/permissão com `validarPermissao` e tabela de auditoria.
2. Documento com hash e juntada (upload seguro: tipo, tamanho, path traversal).
3. Tabela de classes/assuntos com FK e JOIN montado pelo DTO.
4. Audiência com detecção de conflito de agenda.
5. Rodar em MySQL e estudar `EXPLAIN`, índices e `utf8mb4`.

## Licença
MIT (ver `LICENSE`).
