<?php
namespace App\Controllers;

use App\Models\CampaignModel;
use App\Models\MessageModel;

class CampaignController extends BaseController {
    public function index() {
        $model = new CampaignModel();
        
        // Suporte a per_page (25, 50, 100, 200)
        $perPage = (int) $this->request->getGet('per_page');
        if (!in_array($perPage, [25, 50, 100, 200])) {
            $perPage = 25; // Padrão
        }
        
        $campaigns = $model->orderBy('created_at', 'DESC')->paginate($perPage);
        
        return view('campaigns/index', [
            'campaigns' => $campaigns,
            'pager' => $model->pager,
            'activeMenu' => 'campaigns',
            'pageTitle' => 'Campanhas'
        ]);
    }
    
    public function create() {
        return view('campaigns/entry', [
            'campaign' => null,
            'activeMenu' => 'campaigns',
            'pageTitle' => 'Nova Campanha'
        ]);
    }
    
    public function store() {
        $model = new CampaignModel();
        
        $data = [
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
        ];
        
        if ($model->insert($data)) {
            return redirect()->to('/campaigns')->with('success', 'Campanha criada com sucesso!');
        }
        
        return redirect()->back()->with('error', 'Erro ao criar campanha')->withInput();
    }
    
    public function view($id) {
        $campaignModel = new CampaignModel();
        $campaign = $campaignModel->find($id);
        
        if (!$campaign) {
            return redirect()->to('/campaigns')->with('error', 'Campanha não encontrada');
        }
        
        $messageModel = new MessageModel();
        $messages = $messageModel->where('campaign_id', $id)->orderBy('created_at', 'DESC')->findAll();
        $db = \Config\Database::connect();
        $companyId = (int) session()->get('company_id');

        $whatsappMessages = $db->table('whatsapp_messages')
            ->select('whatsapp_messages.*, whatsapp_templates.name AS template_name, whatsapp_numbers.display_phone_number')
            ->join('whatsapp_templates', 'whatsapp_templates.id = whatsapp_messages.template_id')
            ->join('whatsapp_numbers', 'whatsapp_numbers.id = whatsapp_messages.whatsapp_number_id')
            ->where('whatsapp_messages.company_id', $companyId)
            ->where('whatsapp_messages.campaign_id', $id)
            ->orderBy('whatsapp_messages.created_at', 'DESC')
            ->get()
            ->getResultArray();

        $emailStats = $db->table('message_sends')
            ->select("COUNT(*) AS total, SUM(message_sends.status = 'sent') AS sent, SUM(message_sends.opened = 1) AS opened, SUM(message_sends.clicked = 1) AS clicked, SUM(message_sends.status = 'bounced') AS bounced", false)
            ->join('messages', 'messages.id = message_sends.message_id')
            ->where('messages.company_id', $companyId)
            ->where('messages.campaign_id', $id)
            ->get()->getRowArray() ?? [];

        $whatsappStats = $db->table('whatsapp_sends')
            ->select("COUNT(*) AS total, SUM(whatsapp_sends.status IN ('sent','delivered','read')) AS sent, SUM(whatsapp_sends.status IN ('delivered','read')) AS delivered, SUM(whatsapp_sends.status = 'read') AS `read`, SUM(whatsapp_sends.status = 'failed') AS failed", false)
            ->join('whatsapp_messages', 'whatsapp_messages.id = whatsapp_sends.whatsapp_message_id')
            ->where('whatsapp_messages.company_id', $companyId)
            ->where('whatsapp_messages.campaign_id', $id)
            ->get()->getRowArray() ?? [];
        
        return view('campaigns/view', [
            'campaign' => $campaign,
            'messages' => $messages,
            'whatsappMessages' => $whatsappMessages,
            'emailStats' => array_merge(array_map('intval', $emailStats), [
                'optouts' => (int) ($campaign['total_optouts'] ?? 0),
            ]),
            'whatsappStats' => array_map('intval', $whatsappStats),
            'activeMenu' => 'campaigns',
            'pageTitle' => $campaign['name']
        ]);
    }
    
    public function edit($id) {
        $model = new CampaignModel();
        $campaign = $model->find($id);

        if (!$campaign) {
            return redirect()->to('/campaigns')->with('error', 'Campanha não encontrada');
        }

        return view('campaigns/entry', [
            'campaign' => $campaign,
            'activeMenu' => 'campaigns',
            'pageTitle' => 'Editar Campanha'
        ]);
    }
    
    public function update($id) {
        $model = new CampaignModel();
        
        $data = [
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
        ];
        
        if ($model->update($id, $data)) {
            return redirect()->to('/campaigns/view/' . $id)->with('success', 'Campanha atualizada!');
        }
        
        return redirect()->back()->with('error', 'Erro ao atualizar')->withInput();
    }
    
    public function delete($id) {
        $model = new CampaignModel();
        
        if ($model->delete($id)) {
            return redirect()->to('/campaigns')->with('success', 'Campanha excluída!');
        }
        
        return redirect()->back()->with('error', 'Erro ao excluir');
    }
}
