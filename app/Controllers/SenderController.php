<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\AWS\SESService;
use App\Libraries\DNS\DNSValidator;
use App\Models\SenderModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * Controlador responsável pelos remetentes cadastrados na plataforma.
 */
class SenderController extends BaseController
{
    private SenderModel $model;

    public function __construct()
    {
        $this->model = new SenderModel();
    }

    /**
     * Lista todos os remetentes cadastrados.
     *
     * @return string
     */
    public function index(): string
    {
        $senders = $this->model->orderBy('created_at', 'DESC')->findAll();

        return view('senders/index', [
            'senders' => $senders,
            'activeMenu' => 'senders',
            'pageTitle' => 'Remetentes',
        ]);
    }

    /**
     * Exibe o formulário de criação de remetente.
     *
     * @return string
     */
    public function create(): string
    {
        return view('senders/create', [
            'activeMenu' => 'senders',
            'pageTitle' => 'Novo Remetente',
        ]);
    }

    /**
     * Persiste um novo remetente e inicia a verificação na AWS.
     *
     * @return RedirectResponse
     */
    public function store(): RedirectResponse
    {
        $email = (string) $this->request->getPost('email');
        $name = (string) $this->request->getPost('name');
        $domain = $this->extractDomainFromEmail($email);

        $data = [
            'email' => $email,
            'name' => $name,
            'domain' => $domain,
            'is_active' => 0,
        ];

        if ($senderId = $this->model->insert($data)) {
            $this->verifySender((int) $senderId);

            return redirect()->to('/senders/view/' . $senderId)
                ->with('success', 'Remetente criado! Consulte os registros DNS necessários.');
        }

        return redirect()->back()->with('error', 'Erro ao criar remetente')->withInput();
    }

    /**
     * Mostra os detalhes do remetente selecionado.
     *
     * @param int $id Identificador do remetente.
     * @return string|RedirectResponse
     */
    public function view(int $id)
    {
        $sender = $this->model->find($id);

        if ($sender === null) {
            return redirect()->to('/senders')->with('error', 'Remetente não encontrado');
        }

        $validator = new DNSValidator();
        $dkimTokens = $this->decodeDkimTokens($sender['dkim_tokens'] ?? null);
        $dnsStatus = $validator->validateAll($sender['domain'], $dkimTokens);
        $dnsInstructions = $validator->generateDNSInstructions(
            $sender['domain'],
            $dkimTokens,
            $sender['ses_verification_token'] ?? null
        );

        return view('senders/view', [
            'sender' => $sender,
            'dnsStatus' => $dnsStatus,
            'dnsInstructions' => $dnsInstructions,
            'dkimTokens' => $dkimTokens,
            'activeMenu' => 'senders',
            'pageTitle' => $sender['email'],
        ]);
    }

    /**
     * Exibe formulário de edição do remetente.
     *
     * @param int $id Identificador do remetente.
     * @return string|RedirectResponse
     */
    public function edit(int $id)
    {
        $sender = $this->model->find($id);

        if ($sender === null) {
            return redirect()->to('/senders')->with('error', 'Remetente não encontrado');
        }

        return view('senders/edit', [
            'sender' => $sender,
            'activeMenu' => 'senders',
            'pageTitle' => 'Editar Remetente',
        ]);
    }

    /**
     * Atualiza os dados básicos do remetente.
     *
     * @param int $id Identificador do remetente.
     * @return RedirectResponse
     */
    public function update(int $id): RedirectResponse
    {
        $sender = $this->model->find($id);

        if ($sender === null) {
            return redirect()->to('/senders')->with('error', 'Remetente não encontrado');
        }

        $email = (string) $this->request->getPost('email');
        $name = (string) $this->request->getPost('name');
        $domain = $this->extractDomainFromEmail($email) ?: $sender['domain'];

        $this->model->update($id, [
            'email' => $email,
            'name' => $name,
            'domain' => $domain,
        ]);

        return redirect()->to('/senders/view/' . $id)->with('success', 'Remetente atualizado com sucesso!');
    }

    /**
     * Remove definitivamente um remetente.
     *
     * @param int $id Identificador do remetente.
     * @return RedirectResponse
     */
    public function delete(int $id): RedirectResponse
    {
        $sender = $this->model->find($id);

        if ($sender === null) {
            return redirect()->to('/senders')->with('error', 'Remetente não encontrado');
        }

        $this->model->delete($id);

        return redirect()->to('/senders')->with('success', 'Remetente removido com sucesso!');
    }

    /**
     * Reexecuta o fluxo de verificação do remetente.
     *
     * @param int $id Identificador do remetente.
     * @return RedirectResponse
     */
    public function verify(int $id): RedirectResponse
    {
        $this->verifySender($id);

        return redirect()->to('/senders/view/' . $id)->with('success', 'Verificação iniciada!');
    }

    /**
     * Valida novamente os registros DNS via AJAX.
     *
     * @param int $id Identificador do remetente.
     * @return ResponseInterface
     */
    public function checkDNS(int $id): ResponseInterface
    {
        $sender = $this->model->find($id);

        if ($sender === null) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Remetente não encontrado',
            ]);
        }

        $validator = new DNSValidator();
        $dkimTokens = $this->decodeDkimTokens($sender['dkim_tokens'] ?? null);
        $result = $validator->validateAll($sender['domain'], $dkimTokens);

        $this->model->update($id, [
            'spf_verified' => $result['spf']['valid'] ? 1 : 0,
            'dkim_verified' => $result['dkim']['valid'] ? 1 : 0,
            'dmarc_verified' => $result['dmarc']['valid'] ? 1 : 0,
        ]);

        return $this->response->setJSON([
            'success' => true,
            'result' => $result,
        ]);
    }

    /**
     * Dispara a verificação da identidade e coleta os tokens DKIM.
     *
     * @param int $id Identificador do remetente.
     * @return void
     */
    protected function verifySender(int $id): void
    {
        $sender = $this->model->find($id);

        if ($sender === null) {
            return;
        }

        try {
            $service = new SESService();
            $updateData = [];

            $domainResult = $service->verifyDomain($sender['domain']);
            if (($domainResult['success'] ?? false) === true) {
                $updateData['ses_verification_token'] = $domainResult['verificationToken'] ?? null;
            }

            $dkimResult = $service->enableDKIM($sender['domain']);
            if (($dkimResult['success'] ?? false) === true && !empty($dkimResult['dkimTokens'])) {
                $updateData['dkim_tokens'] = json_encode($dkimResult['dkimTokens']);
            }

            $service->verifyEmail($sender['email']);

            if (!empty($updateData)) {
                $this->model->update($id, $updateData);
            }

            $statusResult = $service->getIdentityVerificationStatus($sender['domain']);
            if (($statusResult['verified'] ?? false) === true) {
                $this->model->update($id, [
                    'ses_verified' => 1,
                    'is_active' => 1,
                ]);
            }
        } catch (Throwable $exception) {
            log_message('error', 'Error verifying sender: ' . $exception->getMessage());
        }
    }

    /**
     * Converte o valor armazenado de tokens DKIM em array.
     *
     * @param string|null $value Valor bruto armazenado no banco.
     * @return array<int, string>
     */
    private function decodeDkimTokens(?string $value): array
    {
        if (empty($value)) {
            return [];
        }

        $tokens = json_decode($value, true);

        if (!is_array($tokens)) {
            return [];
        }

        return array_values(array_filter($tokens, static fn($item): bool => is_string($item) && $item !== ''));
    }

    /**
     * Obtém o domínio a partir de um endereço de email.
     *
     * @param string $email Endereço informado pelo usuário.
     * @return string
     */
    private function extractDomainFromEmail(string $email): string
    {
        $atPosition = strrpos($email, '@');

        if ($atPosition === false) {
            return '';
        }

        return substr($email, $atPosition + 1);
    }
}
