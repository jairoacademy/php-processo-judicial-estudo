# Processo Judicial · laboratório de arquitetura PHP

[![CI](https://github.com/jairoacademy/php-processo-judicial-estudo/actions/workflows/ci.yml/badge.svg)](https://github.com/jairoacademy/php-processo-judicial-estudo/actions/workflows/ci.yml)

Sistema de processo judicial eletrônico que acompanha o ciclo de vida de um processo, da distribuição à baixa.

O que ele garante:
- número único do CNJ válido, com dígito verificador, e sem duplicidade;
- processo e registro da distribuição gravados juntos: ou os dois, ou nenhum;
- partes com CPF ou CNPJ validado, quando informado;
- citações e intimações com o prazo final calculado em dias úteis;
- processo baixado não recebe novos atos.

**Stack:** PHP 8.4 · MySQL 8.4 · Docker (nginx + PHP-FPM) · Composer · Doctrine Migrations · PHPUnit · PHPStan (nível max) · PHP-CS-Fixer · GitHub Actions

> **Aviso.** Projeto pessoal e independente, **sem vínculo** com tribunais, com o CNJ ou com qualquer sistema judicial existente. Não contém código de terceiros nem de frameworks proprietários. A arquitetura em camadas foi inspirada na documentação pública do SEI (manual de módulos, no Portal do Software Público), e as regras de domínio seguem normas públicas (CNJ, CPC). Onde algo é suposição, está marcado como tal.

## Como rodar
Requisito: Docker.
```
cp .env.example .env
docker compose up -d --build
docker compose exec php composer install
docker compose exec php composer db:migrate
```
Abra http://localhost:8090.

Para conferir estilo, análise estática e testes de uma vez: `docker compose exec php composer check`.

## Arquitetura
```
Web (controllers + templates)  ->  RN (regra de negócio)  ->  BD (acesso a dados)  ->  Banco (PDO / MySQL)
                               <-------------------- DTOs entre as camadas -------------------->
```

O código é organizado por **subdomínio** (área do negócio), com a infraestrutura comum à parte:
```
src/
├── Autuacao/Processo/       ProcessoDTO, ProcessoBD, ProcessoRN
├── Partes/Parte/            ParteDTO, ParteBD, ParteRN
├── Movimentacao/Evento/     EventoDTO, EventoBD, EventoRN
└── Compartilhado/           Nucleo (DTO, BD, RN e Banco genéricos), Util, Web (controllers, sessão, visão)
```
Um subdomínio fala com outro só pela RN, nunca pelo BD do vizinho; o `Compartilhado` não tem regra de negócio.

| Arquivo | Conceito |
|---|---|
| `src/Compartilhado/Nucleo/NucleoDTO.php` | DTO como construtor de consultas: `set/get/ret/setOrd/isSet` por `__call`; `set` vira WHERE em consulta; operadores `IN`, `LIKE`, etc. |
| `src/Compartilhado/Nucleo/NucleoBD.php` | CRUD genérico guiado pelo DTO, valores sempre por `bind`, colunas só do DTO (nunca da entrada do usuário) |
| `src/Compartilhado/Nucleo/NucleoRN.php` | `__call` abre conexão (`Conectado`) ou conexão + transação (`Controlado`), com commit/rollback |
| `src/Compartilhado/Nucleo/Banco.php` | Singleton de PDO (MySQL, prepared statements reais) e controle de transação (aninhada "entra" na externa) |
| `src/Autuacao/Processo/ProcessoRN.php` | Uma RN chama outra RN, nunca o BD alheio; o evento de distribuição entra na mesma transação |
| `src/Compartilhado/Util/` | Número CNJ (DV módulo 97), CPF/CNPJ, prazo em dias úteis |
| `src/Compartilhado/Web/Aplicacao.php` | Recebe a requisição (PSR-7): confere o CSRF, escolhe o controller pela tabela de rotas `?acao=entidade_acao` e transforma erros em páginas (422, 404, 405, 500) |
| `src/Compartilhado/Web/Controller/` | Controllers finos: leem a requisição, chamam a RN e respondem com uma página ou com um redirecionamento (PRG, mensagens flash) |
| `templates/` | Telas em PHP puro, com escape de saída (`e()`) em todo valor variável |

Armadilha demonstrada em `demo.php`: chamar um método transacional dentro de um loop abre uma transação por iteração (50 commits, e uma falha no meio deixa gravação parcial).

Passo a passo de um cadastro, da tela ao `COMMIT`: [docs/caminho-ate-o-insert.md](docs/caminho-ate-o-insert.md).

## Domínios de um sistema de processo judicial eletrônico
Um sistema de processo judicial eletrônico se divide em **17 subdomínios** (áreas de negócio com regras e vocabulário próprios), de três tipos: **Núcleo**, o coração do negócio; **Apoio**, específico da área, mas periférico; e **Genérico**, problemas comuns a qualquer sistema, que atravessam todos os outros subdomínios.

**Situação atual:** 3 implementados · 7 parciais · 7 a implementar.

Legenda da coluna Base: **PADRÃO** = decorre de norma pública (CNJ, CPC) e é praticamente certo; **COMUM** = suposição razoável sobre o que esse tipo de sistema precisa ter.

### O que já existe
| Tipo | Subdomínio | Situação | O que o projeto entrega | Base |
|---|---|---|---|---|
| Núcleo | **Autuação** | Implementado | Processo com número único CNJ `NNNNNNN-DD.AAAA.J.TR.OOOO` validado pelo dígito verificador e sem duplicidade; classe e assunto; situação em andamento, suspenso ou baixado | PADRÃO |
| Núcleo | **Partes** | Implementado | Polo ativo, passivo ou terceiro; tipo de parte (autor, réu, advogado, MP, Defensoria); CPF/CNPJ validado | PADRÃO |
| Núcleo | **Movimentação** | Implementado | Todo ato como evento tipado (9 tipos), com data e hora; histórico na tela do processo | COMUM |
| Núcleo | **Distribuição** | Parcial | Órgão julgador informado no cadastro; evento de distribuição gravado na mesma transação do processo | PADRÃO |
| Núcleo | **Comunicação** | Parcial | Citação e intimação registradas como eventos, com prazo obrigatório | PADRÃO (CPC) |
| Núcleo | **Prazos** | Parcial | Data final do prazo calculada em dias úteis | PADRÃO (CPC) |
| Núcleo | **Julgamento** | Parcial | Decisão e sentença registradas como tipos de evento | COMUM |
| Apoio | **Pauta** | Parcial | Audiência registrada como tipo de evento | COMUM |
| Genérico | **Acesso** | Parcial | Marcação de segredo de justiça, exibida na tela do processo | PADRÃO |
| Genérico | **Auditoria** | Parcial | Data e hora de cada evento, com registro do usuário | COMUM |

### O que vem a seguir
| Tipo | Subdomínio | O que vai cobrir | Base |
|---|---|---|---|
| Núcleo | **Documentos** | Petições, juntada de arquivos com hash, assinatura digital ICP-Brasil | PADRÃO |
| Apoio | **Recursos** | Recursos e remessa entre instâncias | COMUM |
| Apoio | **Execução** | Mandados e oficiais de justiça, penhora online, precatórios e RPV | COMUM |
| Apoio | **Custas** | Taxa judiciária, guias, gratuidade de justiça | COMUM |
| Apoio | **Trabalho** | Painel e filas por unidade ou usuário, localizadores, lembretes | COMUM |
| Genérico | **Integração** | Troca de processos entre sistemas (MNI), envio de estatística (DataJud), integrações com MP, Defensoria e OAB, migração de acervo | PADRÃO/COMUM |
| Genérico | **Parametrização** | Configurações por tribunal sem alterar o núcleo | COMUM |

Os parciais também crescem: Distribuição (sorteio, prevenção, redistribuição), Comunicação (intimação eletrônica), Prazos (calendário de feriados), Julgamento (minutas e sessões), Pauta (agenda e salas), Acesso (usuários, perfis e permissões) e Auditoria (registro de acesso e alteração).

## Documentação
| Documento | Conteúdo |
|---|---|
| [Ambiente de desenvolvimento](docs/ambiente.md) | Containers, variáveis de ambiente, comandos do dia a dia, como rodar sem Docker |
| [Banco de dados e migrations](docs/banco-de-dados.md) | MySQL, Doctrine Migrations, esquema, convenções de nome, índices e `EXPLAIN` |
| [Testes, qualidade de código e CI](docs/qualidade-e-ci.md) | Suítes do PHPUnit, PHPStan, PHP-CS-Fixer, GitHub Actions e Dependabot |
| [Um cadastro passo a passo](docs/caminho-ate-o-insert.md) | O caminho de um cadastro pelas camadas, da tela ao `COMMIT` |

## Licença
MIT (ver `LICENSE`).
