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
public/index.php (página)  ->  RN (regra de negócio)  ->  BD (acesso a dados)  ->  Banco (PDO / MySQL)
                           <-------------------- DTOs entre as camadas -------------------->
```

| Arquivo | Conceito |
|---|---|
| `src/Nucleo/NucleoDTO.php` | DTO como construtor de consultas: `set/get/ret/setOrd/isSet` por `__call`; `set` vira WHERE em consulta; operadores `IN`, `LIKE`, etc. |
| `src/Nucleo/NucleoBD.php` | CRUD genérico guiado pelo DTO, valores sempre por `bind`, colunas só do DTO (nunca da entrada do usuário) |
| `src/Nucleo/NucleoRN.php` | `__call` abre conexão (`Conectado`) ou conexão + transação (`Controlado`), com commit/rollback |
| `src/Nucleo/Banco.php` | Singleton de PDO (MySQL, prepared statements reais) e controle de transação (aninhada "entra" na externa) |
| `src/Processo/ProcessoRN.php` | Uma RN chama outra RN, nunca o BD alheio; o evento de distribuição entra na mesma transação |
| `src/Util/` | Número CNJ (DV módulo 97), CPF/CNPJ, prazo em dias úteis |
| `public/index.php` | Roteamento `?acao=entidade_acao`, CSRF, escape de saída |

Armadilha demonstrada em `demo.php`: chamar um método transacional dentro de um loop abre uma transação por iteração (50 commits, e uma falha no meio deixa gravação parcial).

Passo a passo de um cadastro, da tela ao `COMMIT`: [docs/caminho-ate-o-insert.md](docs/caminho-ate-o-insert.md).

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

## Documentação
| Documento | Conteúdo |
|---|---|
| [Ambiente de desenvolvimento](docs/ambiente.md) | Containers, variáveis de ambiente, comandos do dia a dia, como rodar sem Docker |
| [Banco de dados e migrations](docs/banco-de-dados.md) | MySQL, Doctrine Migrations, esquema, convenções de nome, índices e `EXPLAIN` |
| [Testes, qualidade de código e CI](docs/qualidade-e-ci.md) | Suítes do PHPUnit, PHPStan, PHP-CS-Fixer, GitHub Actions e Dependabot |
| [Um cadastro passo a passo](docs/caminho-ate-o-insert.md) | O caminho de um cadastro pelas camadas, da tela ao `COMMIT` |

## Licença
MIT (ver `LICENSE`).
