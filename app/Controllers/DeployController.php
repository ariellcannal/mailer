<?php
namespace App\Controllers;

class DeployController extends BaseController
{

    /**
     * Caminho raiz do projeto.
     *
     * @var string
     */
    private string $rootPath;

    /**
     * Caminho para o arquivo de ambiente.
     *
     * @var string
     */
    private string $envPath;

    /**
     * Caminho para o arquivo de log.
     *
     * @var string
     */
    private string $logPath;

    public function index(): void
    {
        $this->rootPath = ROOTPATH;
        $this->envPath = $this->rootPath . '/.env';
        $this->logPath = $this->rootPath . '/writable/logs/deploy.log';
        service('log');
        $this->handle();
    }

    /**
     * Inicia o processamento do webhook.
     *
     * @return void
     */
    public function handle(): void
    {
        // 1) Ambiente básico
        $this->loadEnvironment();

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            exit('Method Not Allowed');
        }

        log_message('debug', 'DEPLOY INICIADO');

        $payload = file_get_contents('php://input') ?: '';

        // 2) Validação da assinatura do GitHub
        $secret = getenv('github.wh_secret') ?: '';
        $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? ($_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '');

        if ($secret === '' || $signature === '' || ! $this->isValidSignature($payload, $secret, $signature)) {
            http_response_code(403);
            $this->logDeploy('Assinatura inválida ou ausente');
            exit('Forbidden');
        }
        $this->logDeploy('Assinatura do GitHub validada');

        // 3) Validação do evento e branch
        $event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';
        $data = json_decode($payload, true);
        $ref = $data['ref'] ?? '';

        $allowedRefs = [
            'refs/heads/master'
        ];

        if ($event !== 'push' || ! in_array($ref, $allowedRefs, true)) {
            $this->logDeploy("Evento ignorado: event={$event}, ref={$ref}");
            http_response_code(200);
            exit('Ignored');
        }

        $this->logDeploy("Payload contém push para branch master (ref={$ref})");

        // 4) Lock para evitar concorrência
        $lockFile = rtrim($this->rootPath, '/') . '/deploy.lock';
        $lockHandle = fopen($lockFile, 'c');

        if ($lockHandle === false) {
            $this->logDeploy("Falha ao abrir lockfile: {$lockFile}");
            http_response_code(500);
            exit('Erro ao criar lockfile');
        }

        if (! flock($lockHandle, LOCK_EX | LOCK_NB)) {
            $this->logDeploy('Já existe um deploy em andamento (lock ativo)');
            http_response_code(202);
            echo 'Deploy already running';
            return;
        }

        // Garante que o lock será liberado no fim
        try {
            // 5) Ajusta timeout de execução (composer update pode demorar)
            @set_time_limit(1800);

            // 6) Remove lock legado se ainda existir
            $legacyLock = $this->rootPath . '/deploy.old.lock';
            if (file_exists($legacyLock)) {
                @unlink($legacyLock);
            }

            $this->logDeploy('Iniciando passos de deploy em PHP');

            // 7) Ambiente para git/composer (ajuste HOME se precisar)
            $home = getenv('HOME') ?: '/home/cannal'; // ajuste se necessário
            putenv('HOME=' . $home);
            putenv('COMPOSER_HOME=' . $home . '/.composer');

            $repoDir = rtrim($this->rootPath, '/');
            $this->logDeploy("Diretório do repositório: {$repoDir}");

            // Garante que o diretório é um repositório Git;
            // se não for, inicializa e conecta ao remote do GitHub (do payload)
            $this->ensureGitRepository($repoDir, $data);

            // Agora é seguro rodar os comandos git normalmente
            $this->runCommand('git config --global --add safe.directory ' . escapeshellarg($repoDir), $repoDir);

            $this->runCommand('git fetch --prune origin', $repoDir);
            $this->runCommand('git reset --hard', $repoDir);
            $this->runCommand('git clean -fd', $repoDir);
            $this->runCommand('git checkout -B master origin/master', $repoDir);
            $this->runCommand('git reset --hard origin/master', $repoDir);
            $this->runCommand('git status --short --branch', $repoDir);

            // Opcional: limpar estado local antes
            $this->runCommand('git reset --hard', $repoDir);
            $this->runCommand('git clean -fd', $repoDir);

            // Faz checkout explícito de master rastreando origin/master
            $this->runCommand('git checkout -B master origin/master', $repoDir);

            // Garante que HEAD está exatamente em origin/master
            $this->runCommand('git reset --hard origin/master', $repoDir);

            // Loga situação final do git
            $this->runCommand('git status --short --branch', $repoDir);

            /*
             *
             *
             * --------------- COMPOSER CONFIG ---------------
             */

            // Caminho do PHP CLI (ajuste se necessário ou use .env)
            $phpBinary = getenv('deploy.php_binary') ?: '/usr/local/bin/php';

            // Caminho do binário/script do Composer (ajuste conforme seu servidor)
            $composerBin = getenv('deploy.composer_binary') ?: '/usr/local/bin/composer';

            $composerCmd = escapeshellarg($phpBinary) . ' ' . escapeshellarg($composerBin);

            $this->logDeploy('Iniciando composer update');

            // clear-cache não é crítico, então permitimos falha sem estourar exceção
            $this->runCommand($composerCmd . ' clear-cache', $repoDir, true);

            // validação do composer.json
            $this->runCommand($composerCmd . ' validate --no-check-lock --no-check-publish', $repoDir);

            // comando principal de update
            $this->runCommand($composerCmd . ' update --no-interaction --prefer-dist --no-dev --optimize-autoloader --no-progress', $repoDir);

            $this->logDeploy('Deploy finalizado com sucesso');
            http_response_code(200);
            echo 'OK';
        } catch (\Throwable $e) {
            // 11) Tratamento de exceções e log detalhado
            $msg = sprintf('Erro no deploy: %s em %s:%d', $e->getMessage(), $e->getFile(), $e->getLine());
            $this->logDeploy($msg);
            $this->logDeploy($e->getTraceAsString());

            http_response_code(500);
            echo 'Erro no deploy';
        } finally {
            // 12) Libera lock
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
            @unlink($lockFile);
        }
    }

    /**
     * Executa um comando de shell, loga comando/saída/status.
     *
     * @param string $command
     *            Comando a ser executado (sem cd).
     * @param string $cwd
     *            Diretório de trabalho.
     * @param bool $allowFailure
     *            Se true, não lança exceção quando status != 0.
     *            
     * @return array [int $status, string[] $output]
     *        
     * @throws \RuntimeException se $allowFailure for false e o comando retornar status != 0
     */
    private function runCommand(string $command, string $cwd, bool $allowFailure = false): array
    {
        $fullCommand = 'cd ' . escapeshellarg($cwd) . ' && ' . $command . ' 2>&1';
        $this->logDeploy("EXEC: {$fullCommand}");

        $output = [];
        $status = 0;

        exec($fullCommand, $output, $status);

        if ($output) {
            $this->logDeploy("OUTPUT:\n" . implode("\n", $output));
        } else {
            $this->logDeploy('OUTPUT: (vazio)');
        }

        $this->logDeploy("STATUS: {$status}");

        if ($status !== 0 && ! $allowFailure) {
            throw new \RuntimeException("Comando falhou (status {$status}): {$command}");
        }

        return [
            $status,
            $output
        ];
    }

    /**
     * Log centralizado para o deploy.
     * Usa log_message E, se definido, o arquivo $this->logPath.
     */
    private function logDeploy(string $message): void
    {
        $message = '[DEPLOY] ' . $message;

        // Log padrão do CodeIgniter
        log_message('debug', $message);

        // Log adicional em arquivo customizado (se você estiver usando isso)
        if (! empty($this->logPath)) {
            // Garante diretório
            $dir = dirname($this->logPath);
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, $this->logPath);
        }
    }

    /**
     * Carrega variáveis de ambiente do arquivo .env.
     *
     * @return void
     */
    private function loadEnvironment(): void
    {
        if (! is_readable($this->envPath)) {
            return;
        }

        foreach (file($this->envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if ($line[0] === '#') {
                continue;
            }
            [
                $key,
                $value
            ] = array_map('trim', explode('=', $line, 2));
            if ($key !== '') {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }

    /**
     * Verifica se a assinatura enviada é válida.
     *
     * @param string $payload
     *            Dados recebidos do GitHub.
     * @param string $secret
     *            Chave compartilhada do webhook.
     * @param string $signature
     *            Assinatura recebida.
     *            
     * @return bool
     */
    private function isValidSignature(string $payload, string $secret, string $signature): bool
    {
        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }

    /**
     * Garante que $cwd é um repositório Git.
     * Se não for, inicializa e configura o remote usando o clone_url do payload do GitHub.
     * Se houver arquivos não rastreados que conflitam com origin/master, remove apenas esses arquivos.
     *
     * @param string $cwd
     *            Diretório onde o repo deve existir.
     * @param array $data
     *            Payload já decodificado do GitHub (json_decode).
     *            
     * @throws \RuntimeException se não for possível inicializar / configurar o repositório.
     */
    private function ensureGitRepository(string $cwd, array $data): void
    {
        // 1) Se já for um repositório Git, não mexe em nada
        try {
            $this->runCommand('git rev-parse --is-inside-work-tree', $cwd);
            $this->logDeploy('Repositório Git já existente neste diretório.');
            return;
        } catch (\Throwable $e) {
            $this->logDeploy('Nenhum repositório Git detectado. Inicializando novo repositório...');
        }

        // 2) Descobre a URL remota a partir do payload do GitHub
        $remoteUrl = $data['repository']['clone_url'] ?? '';

        if ($remoteUrl === '') {
            $this->logDeploy('Falha ao obter repository.clone_url do payload do GitHub.');
            throw new \RuntimeException('Não foi possível determinar a URL remota do repositório.');
        }

        $this->logDeploy("URL remota detectada a partir do payload: {$remoteUrl}");

        // 3) Inicializa o repositório e configura remote origin
        $this->runCommand('git init', $cwd);
        $this->runCommand('git remote remove origin || true', $cwd, true);
        $this->runCommand('git remote add origin ' . escapeshellarg($remoteUrl), $cwd);

        // 4) Busca a branch remota
        $this->runCommand('git fetch origin', $cwd);

        // 5) Descobre arquivos rastreados em origin/master
        [
            $tracked
        ] = $this->runCommand('git ls-tree -r --name-only origin/master', $cwd);

        // 6) Descobre arquivos untracked no working tree (respeitando .gitignore)
        [
            $untracked
        ] = $this->runCommand('git ls-files --others --exclude-standard', $cwd);

        $trackedSet = [];
        foreach ($tracked as $path) {
            $path = trim($path);
            if ($path !== '') {
                $trackedSet[$path] = true;
            }
        }

        $conflicting = [];
        foreach ($untracked as $path) {
            $path = trim($path);
            if ($path === '') {
                continue;
            }
            if (isset($trackedSet[$path])) {
                $conflicting[] = $path;
            }
        }

        if (! empty($conflicting)) {
            $this->logDeploy('Foram detectados arquivos untracked que existem em origin/master ' . '(seriam sobrescritos pelo checkout). ' . 'Removendo apenas esses arquivos antes do primeiro checkout...');

            foreach ($conflicting as $path) {
                // segurança básica: evitar coisas estranhas
                $trimmed = trim($path);
                if ($trimmed === '' || strpos($trimmed, '..') !== false) {
                    continue;
                }

                $this->logDeploy("Removendo arquivo/diretório conflitante: {$trimmed}");
                $this->runCommand('rm -rf -- ' . escapeshellarg($trimmed), $cwd, true);
            }
        } else {
            $this->logDeploy('Nenhum arquivo untracked conflitante encontrado com origin/master.');
        }

        // 7) Agora sim, faz checkout da master rastreando origin/master
        $this->runCommand('git checkout -B master origin/master', $cwd);

        $this->logDeploy('Repositório Git criado e sincronizado com origin/master.');
    }

    /**
     * Extrai da saída do git (checkout) a lista de arquivos "untracked" que seriam sobrescritos.
     *
     * @param array $output
     *            Linhas retornadas pelo comando git.
     * @return string[] Lista de caminhos relativos.
     */
    private function extractUntrackedConflictPaths(array $output): array
    {
        $paths = [];
        $capture = false;

        foreach ($output as $line) {
            $line = rtrim($line, "\r\n");

            if (strpos($line, 'would be overwritten by checkout:') !== false) {
                // Próximas linhas (com tab) serão os caminhos
                $capture = true;
                continue;
            }

            if (! $capture) {
                continue;
            }

            // Fim da lista
            if (strpos($line, 'Please move or remove them before you switch branches.') === 0 || strpos($line, 'Aborting') === 0) {
                break;
            }

            $line = ltrim($line, " \t");
            if ($line === '') {
                continue;
            }

            $paths[] = $line;
        }

        return $paths;
    }
}
