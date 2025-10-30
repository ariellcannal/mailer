<?php
namespace App\Controllers;

use App\Models\CampaignModel;
use App\Models\MessageModel;

class CampaignController extends BaseController {
    public function index() {
        $model = new CampaignModel();
        $campaigns = $model->orderBy('created_at', 'DESC')->paginate(20);
        
        return view('campaigns/index', [
            'campaigns' => $campaigns,
            'pager' => $model->pager,
            'activeMenu' => 'campaigns',
            'pageTitle' => 'Campanhas'
        ]);
    }
    
    public function create() {
        return view('campaigns/create', [
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
        
        return view('campaigns/view', [
            'campaign' => $campaign,
            'messages' => $messages,
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
        
        return view('campaigns/edit', [
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
