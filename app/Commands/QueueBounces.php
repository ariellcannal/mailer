<?php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\Email\BounceProcessor;

#*/10 * * * * /usr/local/bin/ea-php82 /home/cannal/public_html/mailer/spark queue:bounces >> /dev/null 2>&1
class QueueBounces extends BaseCommand
{

    protected $group = 'Mailer';

    protected $name = 'queue:bounces';

    protected $description = 'Processa notificações de bounces e complaints no SNS/SQS.';

    public function run(array $params)
    {
        CLI::write("Verificando bounces e complaints...", 'yellow');

        try {
            $processor = new BounceProcessor();
            $result = $processor->process();
        } catch (\Throwable $exception) {
            CLI::error('Falha crítica: ' . $exception->getMessage());
            return;
        }

        $this->displayBounceOutput($result);
    }

    private function displayBounceOutput(array $result): void
    {
        CLI::write('Mensagens analisadas: ' . ($result['processed'] ?? 0));
        CLI::write('Bounces registrados: ' . ($result['bounced'] ?? 0), 'red');
        CLI::write('Complaints registradas: ' . ($result['complained'] ?? 0), 'red');

        if (! empty($result['errors'])) {
            CLI::newLine();
            CLI::error('Detalhes dos erros:');
            foreach ($result['errors'] as $error) {
                CLI::error('- ' . $error);
            }
        }
    }
}