<?php

namespace App\Controllers;

use App\Models\CampaignModel;
use App\Models\ContactModel;
use App\Models\MessageModel;
use App\Models\MessageSendModel;
use App\Libraries\AWS\SESService;

/**
 * Dashboard Controller
 * 
 * Controller principal do dashboard
 * 
 * @package App\Controllers
 * @author  Mailer System
 * @version 1.0.0
 */
class DashboardController extends BaseController
{
    /**
     * Dashboard principal
     * 
     * @return string View do dashboard
     */
    public function index()
    {
        // Estatísticas gerais
        $stats = $this->getGeneralStats();
        
        // Campanhas recentes
        $campaignModel = new CampaignModel();
        $recentCampaigns = $campaignModel
            ->orderBy('created_at', 'DESC')
            ->findAll(5);

        // Mensagens recentes
        $messageModel = new MessageModel();
        $recentMessages = $messageModel
            ->orderBy('created_at', 'DESC')
            ->findAll(5);

        // Limites AWS SES
        $sesLimits = $this->getSESLimits();

        return view('dashboard/index', [
            'activeMenu' => 'dashboard',
            'stats' => $stats,
            'recentCampaigns' => $recentCampaigns,
            'recentMessages' => $recentMessages,
            'sesLimits' => $sesLimits,
        ]);
    }

    /**
     * Obtém estatísticas gerais do sistema
     * 
     * @return array Estatísticas
     */
    protected function getGeneralStats(): array
    {
        $contactModel = new ContactModel();
        $campaignModel = new CampaignModel();
        $messageModel = new MessageModel();
        $sendModel = new MessageSendModel();

        // Total de contatos
        $totalContacts = $contactModel->countAll();
        $activeContacts = $contactModel->where('is_active', 1)->countAllResults();

        // Total de campanhas
        $totalCampaigns = $campaignModel->countAll();

        // Total de mensagens
        $totalMessages = $messageModel->countAll();

        // Envios
        $totalSends = $sendModel->where('status', 'sent')->countAllResults(false);
        $totalOpens = $sendModel->where('opened', 1)->countAllResults(false);
        $totalClicks = $sendModel->where('clicked', 1)->countAllResults(false);

        // Taxas
        $openRate = $totalSends > 0 ? round(($totalOpens / $totalSends) * 100, 2) : 0;
        $clickRate = $totalSends > 0 ? round(($totalClicks / $totalSends) * 100, 2) : 0;

        return [
            'totalContacts' => $totalContacts,
            'activeContacts' => $activeContacts,
            'totalCampaigns' => $totalCampaigns,
            'totalMessages' => $totalMessages,
            'totalSends' => $totalSends,
            'totalOpens' => $totalOpens,
            'totalClicks' => $totalClicks,
            'openRate' => $openRate,
            'clickRate' => $clickRate,
        ];
    }

    /**
     * Obtém limites do AWS SES
     * 
     * @return array Limites
     */
    protected function getSESLimits(): array
    {
        try {
            $sesService = new SESService();
            $quota = $sesService->getSendQuota();

            if ($quota['success']) {
                return [
                    'max24Hour' => number_format((float) $quota['max24HourSend'], 0, ',', '.'),
                    'maxRate' => number_format((float) $quota['maxSendRate'], 2, ',', '.'),
                    'sentLast24Hours' => number_format((float) $quota['sentLast24Hours'], 0, ',', '.'),
                    'remaining' => number_format((float) $quota['remaining'], 0, ',', '.'),
                    'percentUsed' => $quota['max24HourSend'] > 0
                        ? round(($quota['sentLast24Hours'] / $quota['max24HourSend']) * 100, 1)
                        : 0,
                ];
            }
        } catch (\Exception $e) {
            log_message('error', 'Error getting SES limits: ' . $e->getMessage());
        }

        return [
            'max24Hour' => 'N/A',
            'maxRate' => 'N/A',
            'sentLast24Hours' => 'N/A',
            'remaining' => 'N/A',
            'percentUsed' => 0,
        ];
    }

    /**
     * API: Dados para gráficos
     * 
     * @return \CodeIgniter\HTTP\ResponseInterface JSON com dados
     */
    public function chartData()
    {
        $period = $this->request->getGet('period') ?? '7days';
        
        // Determina intervalo de datas
        switch ($period) {
            case '24hours':
                $startDate = date('Y-m-d H:i:s', strtotime('-24 hours'));
                $groupBy = 'HOUR';
                break;
            case '7days':
                $startDate = date('Y-m-d H:i:s', strtotime('-7 days'));
                $groupBy = 'DAY';
                break;
            case '30days':
                $startDate = date('Y-m-d H:i:s', strtotime('-30 days'));
                $groupBy = 'DAY';
                break;
            case '90days':
                $startDate = date('Y-m-d H:i:s', strtotime('-90 days'));
                $groupBy = 'WEEK';
                break;
            default:
                $startDate = date('Y-m-d H:i:s', strtotime('-7 days'));
                $groupBy = 'DAY';
        }

        $db = \Config\Database::connect();

        // Envios por período
        $sends = $db->table('message_sends')
            ->select("DATE_FORMAT(sent_at, '%Y-%m-%d') as date, COUNT(*) as count")
            ->where('sent_at >=', $startDate)
            ->where('status', 'sent')
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get()
            ->getResultArray();

        // Aberturas por período
        $opens = $db->table('message_opens')
            ->select("DATE_FORMAT(opened_at, '%Y-%m-%d') as date, COUNT(*) as count")
            ->where('opened_at >=', $startDate)
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get()
            ->getResultArray();

        // Cliques por período
        $clicks = $db->table('message_clicks')
            ->select("DATE_FORMAT(clicked_at, '%Y-%m-%d') as date, COUNT(*) as count")
            ->where('clicked_at >=', $startDate)
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'sends' => $sends,
            'opens' => $opens,
            'clicks' => $clicks,
        ]);
    }
}
