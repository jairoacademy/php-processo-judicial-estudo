# Um cadastro passo a passo: da tela ao INSERT

[Voltar ao README](../README.md)

O que acontece desde o clique em **Distribuir** na tela de novo processo até o `COMMIT` no banco, passando pela camada web (`Aplicacao` e controller), pela `ProcessoRN`, pelo `NucleoRN::__call`, pelo `NucleoBD` e pelo `Banco`.

## Visão geral
```
public/index.php                             requisição PSR-7 montada a partir das superglobais
  -> Aplicacao::atender                      confere o token CSRF; ?acao=processo_cadastrar + POST
  -> ProcessoController::cadastrar           lê o formulário e preenche o DTO
  -> ProcessoRN::cadastrar($dto)            [não existe: __call]
     -> NucleoRN::__call                     BEGIN (nível 0 -> 1)
        -> ProcessoRN::cadastrarControlado
           1. validações                     (NucleoException acumula as mensagens)
           2. já existe esse número?         SELECT  (ProcessoBD::consultar)
           3. valores padrão                 situação 'A', sigilo 'N', data de hoje
           4. grava o processo               INSERT  (ProcessoBD::cadastrar -> NucleoBD)
           5. evento de distribuição         EventoRN::cadastrar -> __call (nível 1 -> 2) -> INSERT
        <- volta                              COMMIT (nível 1 -> 0)
  <- 303 See Other, Location: ?acao=processo_consultar&id=N   + mensagem flash na sessão
```

## 1. A requisição chega ao controller
O `public/index.php` monta uma requisição PSR-7 e a entrega a `Aplicacao::atender()` (`src/Web/Aplicacao.php`), que:
- recusa com 400 um POST sem o token CSRF da sessão;
- procura `processo_cadastrar` + POST na tabela de rotas e chama `ProcessoController::cadastrar`;
- envolve a chamada num `try`, que transforma exceções em páginas de erro (422, 404 ou 500).

O controller lê o formulário com os ajudantes da classe base `Controller`, preenche o DTO e chama a RN:
```php
$processo->setStrNumero($this->texto($requisicao, 'numero'))->setStrClasse(...)->...;
$processo = new ProcessoRN()->cadastrar($processo);
```
O controller não sabe nada de SQL nem de transação; ele só conhece a RN.

## 2. Validações (`src/Processo/ProcessoRN.php:42-53`)
```php
$ex = new NucleoException('Dados do processo inválidos.');
if (!Cnj::validar($numero)) { $ex->adicionarValidacao('Número inválido ...'); }
// classe, assunto e órgão obrigatórios...
$ex->lancarValidacoes();   // lança só se houver mensagens
```
A exceção junta **todas** as mensagens antes de lançar, então o usuário vê os 3 erros de uma vez, e não um por envio. Em Java, é como o Bean Validation devolvendo um conjunto de `ConstraintViolation`. A tela captura e mostra a lista com HTTP 422.

## 3. Checagem de número duplicado (`src/Processo/ProcessoRN.php:55-60`)
```php
$filtro->setStrNumero($numero)->retNumIdProcesso();
$this->bd()->consultar($filtro)
```
O `NucleoBD` monta:
```sql
SELECT id_processo AS NumIdProcesso FROM processo WHERE numero = :w0 LIMIT 1 OFFSET 0
```
O `retNumIdProcesso()` limita o `SELECT` a uma coluna, que é só o necessário para saber se existe.

**Ponto fino:** isso é um *check-then-act*. Duas requisições simultâneas com o mesmo número podem passar ambas pelo `SELECT` antes de qualquer `INSERT`. Quem garante de verdade é a constraint `ak_processo_numero UNIQUE` da migration: a segunda requisição leva uma `PDOException`, que o `__call` embrulha como "Erro em cadastrar." (uma mensagem menos amigável, mas sem duplicata no banco). A regra é: valide na aplicação para dar uma boa mensagem, e garanta no banco para ter consistência.

## 4. Valores padrão (`src/Processo/ProcessoRN.php:62-70`)
`isSet` distingue "não informado" de "informado como `null`": só preenche o que a tela não mandou.

## 5. O `INSERT` (`src/Nucleo/NucleoBD.php:77-96`)
```php
foreach ($dto->valores() as $attr => $valor) {
    if ($attr === $pk && $valor === null) { continue; }   // deixa o AUTO_INCREMENT gerar o id
    $campos[] = $cols[$attr];            // 'StrNumero' -> 'numero'   (vem do montar())
    $marcas[] = ':' . $cols[$attr];      // ':numero'
    $params[':' . $cols[$attr]] = $valor;
}
```
Só entram os atributos **informados**: o que está em `valores()`, não todos os do `montar()`. Resultado:
```sql
INSERT INTO processo (numero, classe, assunto, orgao_julgador, sin_sigilo, sta_situacao, dta_distribuicao)
VALUES (:numero, :classe, :assunto, :orgao_julgador, :sin_sigilo, :sta_situacao, :dta_distribuicao)
```
com `$params = [':numero' => '0000001-69.2026.8.19.0001', ...]`.

**Por que isso é seguro contra SQL injection:**
- **Nomes de tabela e coluna** vêm do código (`getStrNomeTabela()` e `montar()`), **nunca** da entrada do usuário. Por isso podem ser concatenados.
- **Valores** vão sempre por *bind*: os marcadores `:numero` são preenchidos pelo MySQL num prepared statement real (`EMULATE_PREPARES=false`).

## 6. Execução e id gerado (`src/Nucleo/Banco.php:94-104`, `src/Nucleo/NucleoBD.php:94`)
```php
$st = $this->pdo()->prepare($sql);  $st->execute($params);
$dto->definirValor($pk, $this->banco->ultimoId());   // lastInsertId() -> NumIdProcesso
```
O **mesmo objeto** `$dto` recebe o id e volta para a RN. Por isso a linha 75 da `ProcessoRN` consegue fazer `$dto->getNumIdProcesso()` logo depois. Em Java, é como o `entityManager.persist(entity)` preenchendo o `@Id` na própria entidade.

## 7. O evento na mesma transação (`src/Processo/ProcessoRN.php:74-78`)
`new EventoRN()->cadastrar($ev)` passa de novo pelo `__call`. O nível de transação vai para 2, não há `BEGIN`, e a `EventoRN` faz as próprias validações e o próprio `INSERT INTO evento`. Se o evento falhar, o processo também é desfeito: o `TransacaoTest::rollbackDesfazProcessoEEventoDeDistribuicao` prova isso.

## 8. Volta e commit
O `cadastrarControlado` devolve o `$dto`. O `__call` chama `confirmarTransacao()`: o nível vai de 1 para 0, e o `COMMIT` é real. O controller guarda a mensagem "Processo distribuído." na sessão (flash) e responde **303 See Other** para a consulta, no padrão PRG (POST, depois redirecionamento, depois GET), para que um F5 não cadastre o processo de novo. A mensagem aparece uma única vez, na página seguinte.

## Para ver o SQL de verdade passando
Ligue o log geral do MySQL, cadastre um processo pela tela e veja as consultas:
```
docker compose exec mysql sh -c 'mysql -uroot -proot -e "SET GLOBAL general_log=1; SET GLOBAL log_output=\"TABLE\";"'
# cadastre pela tela, depois:
docker compose exec mysql sh -c 'mysql -uroot -proot -e "SELECT argument FROM mysql.general_log WHERE argument LIKE \"%processo%\" OR argument IN (\"BEGIN\",\"COMMIT\") ORDER BY event_time DESC LIMIT 15;"'
docker compose exec mysql sh -c 'mysql -uroot -proot -e "SET GLOBAL general_log=0;"'
```
Você vai ver a sequência: `Prepare`, `Execute` do `SELECT`, `INSERT processo`, `INSERT evento` e `COMMIT`.

Os números de linha indicados podem mudar com refatorações; os nomes de classe e método são a referência.
