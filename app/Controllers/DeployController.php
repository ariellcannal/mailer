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
     * Caminho para o script de deploy.
     *
     * @var string
     */
    private string $deployScript;

    /**
     * Caminho para o arquivo de log.
     *
     * @var string
     */
    private string $logPath;

    /**
     * Endpoint inicial do webhook de deploy.
     *
     * @return void
     */
    public function index(): void
    {
        $this->rootPath = ROOTPATH;
        $this->envPath = $this->rootPath . '/.env';
        $this->deployScript = $this->rootPath . '/app/Libraries/deploy.sh';
        $this->logPath = $this->rootPath . '/writable/logs/deploy.log';
        $this->handle();
    }

    /**
     * Inicia o processamento do webhook.
     *
     * @return void
     */
    public function handle(): void
    {
        // Carrega variáveis de ambiente
        $this->loadEnvironment();

        // Valida método HTTP
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            exit('Method Not Allowed');
        }

        $payload = file_get_contents('php://input');

        // Valida assinatura
        $secret = getenv('github.wh_secret') ?: '';
        $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '';

        if ($secret === '' || $signature === '' || ! $this->isValidSignature($payload, $secret, $signature)) {
            http_response_code(403);
            error_log(date('[Y-m-d H:i:s] ') . "Assinatura inválida\n", 3, $this->logPath);
            exit('Forbidden');
        }

        // Valida evento e branch
        $event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';
        $data = json_decode($payload, true);
        $ref = $data['ref'] ?? '';
        $allowedRefs = [
            'refs/heads/master',
            'refs/heads/main'
        ];
        if ($event !== 'push' || ! in_array($ref, $allowedRefs, true)) {
            http_response_code(200);
            exit('Ignored');
        }

        // Remove lock legado para evitar bloqueio falso
        $legacyLock = $this->rootPath . '/deploy.lock';
        if (file_exists($legacyLock)) {
            @unlink($legacyLock);
        }

        // Garante que o script de deploy esteja atualizado antes da execução
        $rootEsc = escapeshellarg($this->rootPath);
        exec("git -C {$rootEsc} fetch --prune origin");
        $branch = 'master';
        $output = [];
        $status = 0;
        exec("git -C {$rootEsc} rev-parse --verify origin/main >/dev/null 2>&1", $output, $status);
        if ($status === 0) {
            $branch = 'main';
        }
        exec("git -C {$rootEsc} reset --hard origin/{$branch}");

        // Script de deploy possui lock próprio para evitar concorrência
        // e é disparado em background
        if (! is_executable($this->deployScript)) {
            @chmod($this->deployScript, 0755);
        }
        $logDir = dirname($this->logPath);
        if (! is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        // Script registra logs internamente; saída é descartada para evitar duplicidade
        exec('nohup bash ' . escapeshellarg($this->deployScript) . ' >/dev/null 2>&1 &');

        http_response_code(200);
        echo 'OK';
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
}
