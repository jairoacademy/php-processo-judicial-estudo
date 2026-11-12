<?php
declare(strict_types=1);

// Front controller no estilo controlador.php?acao=<entidade>_<acao>
// Rodar: php -S localhost:8090 -t public
require __DIR__ . '/../vendor/autoload.php';

use ProcessoJudicialEstudo\Evento\EventoDTO;
use ProcessoJudicialEstudo\Evento\EventoRN;
use ProcessoJudicialEstudo\Nucleo\NucleoDTO;
use ProcessoJudicialEstudo\Nucleo\NucleoException;
use ProcessoJudicialEstudo\Parte\ParteDTO;
use ProcessoJudicialEstudo\Parte\ParteRN;
use ProcessoJudicialEstudo\Processo\ProcessoDTO;
use ProcessoJudicialEstudo\Processo\ProcessoRN;

session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(16));

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function pagina(string $titulo, string $corpo, array $erros = []): never
{
    header('Content-Type: text/html; charset=utf-8');
    $e = $erros ? '<ul class="erro">' . implode('', array_map(fn($m) => '<li>' . h($m) . '</li>', $erros)) . '</ul>' : '';
    echo '<!doctype html><meta charset="utf-8"><title>' . h($titulo) . '</title>'
        . '<style>body{font:15px system-ui;max-width:900px;margin:2rem auto;padding:0 1rem}table{border-collapse:collapse;width:100%}'
        . 'td,th{border:1px solid #ccc;padding:.35rem .5rem;text-align:left}.erro{background:#fee;border:1px solid #c66;padding:.5rem 1.5rem}'
        . 'label{display:block;margin:.4rem 0}input,select{padding:.3rem}</style>'
        . '<h1><a href="?acao=processo_listar" style="text-decoration:none">Estudo: processo judicial</a> – ' . h($titulo) . '</h1>' . $e . $corpo;
    exit;
}

function token(): string
{
    return '<input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '">';
}

function exigirPost(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        pagina('Requisição inválida', '<p>CSRF ou método inválido.</p>');
    }
}

$acao = $_GET['acao'] ?? 'processo_listar';
$rnProcesso = new ProcessoRN();

try {
    switch ($acao) {
        case 'processo_listar':
            $f = new ProcessoDTO();
            $q = trim((string) ($_GET['q'] ?? ''));
            $sit = (string) ($_GET['sit'] ?? '');
            $q !== '' && $f->setStrNumero($q, NucleoDTO::OPER_LIKE);
            isset(ProcessoRN::SITUACOES[$sit]) && $f->setStrSituacao($sit);
            $total = $rnProcesso->contar($f);
            $pag = max(0, (int) ($_GET['p'] ?? 0));
            $f->setOrdNumIdProcesso('DESC')->setNumMaxRegistrosRetorno(10)->setNumPaginaAtual($pag);
            $linhas = '';
            foreach ($rnProcesso->listar($f) as $p) {
                $linhas .= '<tr><td><a href="?acao=processo_consultar&id=' . (int) $p->getNumIdProcesso() . '">' . h($p->getStrNumero()) . '</a></td>'
                    . '<td>' . h($p->getStrClasse()) . '</td><td>' . h($p->getStrOrgaoJulgador()) . '</td>'
                    . '<td>' . h(ProcessoRN::SITUACOES[$p->getStrSituacao()] ?? '') . '</td></tr>';
            }
            $opts = '<option value="">Todas</option>';
            foreach (ProcessoRN::SITUACOES as $k => $v) {
                $opts .= '<option value="' . $k . '"' . ($sit === $k ? ' selected' : '') . '>' . h($v) . '</option>';
            }
            $nav = ($pag > 0 ? '<a href="?acao=processo_listar&q=' . urlencode($q) . '&sit=' . h($sit) . '&p=' . ($pag - 1) . '">« anterior</a> ' : '')
                . (($pag + 1) * 10 < $total ? '<a href="?acao=processo_listar&q=' . urlencode($q) . '&sit=' . h($sit) . '&p=' . ($pag + 1) . '">próxima »</a>' : '');
            pagina('Processos', '<form method="get"><input type="hidden" name="acao" value="processo_listar">'
                . '<input name="q" placeholder="número" value="' . h($q) . '"> <select name="sit">' . $opts . '</select> <button>Filtrar</button> '
                . '<a href="?acao=processo_cadastrar">+ Novo processo</a></form><p>' . $total . ' resultado(s)</p>'
                . '<table><tr><th>Número</th><th>Classe</th><th>Órgão julgador</th><th>Situação</th></tr>' . $linhas . '</table><p>' . $nav . '</p>');

        case 'processo_cadastrar':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                exigirPost();
                $p = new ProcessoDTO();
                $p->setStrNumero(trim((string) $_POST['numero']))->setStrClasse(trim((string) $_POST['classe']))
                    ->setStrAssunto(trim((string) $_POST['assunto']))->setStrOrgaoJulgador(trim((string) $_POST['orgao']))
                    ->setStrSinSigilo(isset($_POST['sigilo']) ? 'S' : 'N');
                $p = $rnProcesso->cadastrar($p);
                header('Location: ?acao=processo_consultar&id=' . $p->getNumIdProcesso());
                exit;
            }
            pagina('Novo processo', '<form method="post">' . token()
                . '<label>Número CNJ <input name="numero" size="28" placeholder="0000001-00.2026.8.19.0001"></label>'
                . '<label>Classe <input name="classe"></label><label>Assunto <input name="assunto"></label>'
                . '<label>Órgão julgador <input name="orgao"></label><label><input type="checkbox" name="sigilo"> Segredo de justiça</label>'
                . '<button>Distribuir</button></form>');

        case 'processo_consultar':
            $id = (int) ($_GET['id'] ?? 0);
            $f = new ProcessoDTO();
            $f->setNumIdProcesso($id);
            $p = $rnProcesso->consultar($f);
            $p ?? pagina('Não encontrado', '<p>Processo inexistente.</p>');

            $fp = new ParteDTO();
            $fp->setNumIdProcesso($id)->setOrdStrPolo();
            $partes = '';
            foreach ((new ParteRN())->listar($fp) as $x) {
                $partes .= '<tr><td>' . h(ParteRN::POLOS[$x->getStrPolo()] ?? '') . '</td><td>' . h($x->getStrTipo()) . '</td><td>' . h($x->getStrNome()) . '</td></tr>';
            }
            $fe = new EventoDTO();
            $fe->setNumIdProcesso($id)->setOrdNumIdEvento('DESC');
            $eventos = '';
            foreach ((new EventoRN())->listar($fe) as $x) {
                $eventos .= '<tr><td>' . h($x->getDthEvento()) . '</td><td>' . h($x->getStrTipo()) . '</td><td>' . h($x->getStrDescricao())
                    . '</td><td>' . h($x->getDtaPrazoFinal()) . '</td></tr>';
            }
            $tipos = implode('', array_map(fn($t) => '<option>' . h($t) . '</option>', EventoRN::TIPOS));
            $tp = implode('', array_map(fn($t) => '<option>' . h($t) . '</option>', ParteRN::TIPOS));
            $sit = ProcessoRN::SITUACOES[$p->getStrSituacao()] ?? '';
            pagina('Processo ' . $p->getStrNumero(),
                '<p>' . h($p->getStrClasse()) . ' – ' . h($p->getStrAssunto()) . '<br>' . h($p->getStrOrgaoJulgador())
                . ' · ' . h($sit) . ($p->getStrSinSigilo() === 'S' ? ' · <b>segredo de justiça</b>' : '') . '</p>'
                . '<h3>Partes</h3><table><tr><th>Polo</th><th>Tipo</th><th>Nome</th></tr>' . $partes . '</table>'
                . '<form method="post" action="?acao=parte_cadastrar&id=' . $id . '">' . token()
                . '<input name="nome" placeholder="nome"> <input name="doc" placeholder="CPF/CNPJ"> <select name="polo"><option value="A">Ativo</option><option value="P">Passivo</option><option value="T">Terceiro</option></select> '
                . '<select name="tipo">' . $tp . '</select> <button>Adicionar parte</button></form>'
                . '<h3>Eventos</h3><table><tr><th>Data/hora</th><th>Tipo</th><th>Descrição</th><th>Prazo final</th></tr>' . $eventos . '</table>'
                . '<form method="post" action="?acao=evento_cadastrar&id=' . $id . '">' . token()
                . '<select name="tipo">' . $tipos . '</select> <input name="descricao" placeholder="descrição"> <input name="dias" type="number" min="0" placeholder="prazo (dias úteis)"> <button>Registrar evento</button></form>');

        case 'parte_cadastrar':
            exigirPost();
            $id = (int) ($_GET['id'] ?? 0);
            $x = new ParteDTO();
            $x->setNumIdProcesso($id)->setStrNome(trim((string) $_POST['nome']))->setStrDocumento(trim((string) $_POST['doc']))
                ->setStrPolo((string) $_POST['polo'])->setStrTipo((string) $_POST['tipo']);
            (new ParteRN())->cadastrar($x);
            header('Location: ?acao=processo_consultar&id=' . $id);
            exit;

        case 'evento_cadastrar':
            exigirPost();
            $id = (int) ($_GET['id'] ?? 0);
            $x = new EventoDTO();
            $x->setNumIdProcesso($id)->setStrTipo((string) $_POST['tipo'])->setStrDescricao(trim((string) $_POST['descricao']));
            if ((int) $_POST['dias'] > 0) {
                $x->setNumDiasPrazo((int) $_POST['dias']);
            }
            (new EventoRN())->cadastrar($x);
            header('Location: ?acao=processo_consultar&id=' . $id);
            exit;

        default:
            http_response_code(404);
            pagina('Ação desconhecida', '<p>acao=' . h($acao) . '</p>');
    }
} catch (NucleoException $e) {
    $msgs = $e->contemValidacoes() ? $e->getValidacoes() : [$e->getMessage()];
    http_response_code(422);
    pagina('Erro', '<p><a href="javascript:history.back()">Voltar</a></p>', $msgs);
}
