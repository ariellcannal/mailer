<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\MessageSendModel;
use App\Models\MessageOpenModel;
use App\Models\MessageClickModel;
use App\Models\OptoutModel;
use CodeIgniter\I18n\Time;

/**
 * Controlador responsável pelas análises de tracking.
 */
class TrackingController extends BaseController
{
    /**
     * Dashboard de métricas gerais.
     */
    public function index(): string
    {
        $db = \Config\Database::connect();
        $companyId = (int) session()->get('company_id');
        $totalSent = $db->table('message_sends')->join('messages', 'messages.id = message_sends.message_id')
            ->where('messages.company_id', $companyId)->where('message_sends.status', 'sent')->countAllResults();
        $totalOpens = $db->table('message_opens')->join('message_sends', 'message_sends.id = message_opens.send_id')
            ->join('messages', 'messages.id = message_sends.message_id')->where('messages.company_id', $companyId)->countAllResults();
        $totalClicks = $db->table('message_clicks')->join('message_sends', 'message_sends.id = message_clicks.send_id')
            ->join('messages', 'messages.id = message_sends.message_id')->where('messages.company_id', $companyId)->countAllResults();
        $totalBounces = $db->table('message_sends')->join('messages', 'messages.id = message_sends.message_id')
            ->where('messages.company_id', $companyId)->where('message_sends.status', 'bounced')->countAllResults();
        $totalOptouts = $db->table('optouts')->join('messages', 'messages.id = optouts.message_id')
            ->where('messages.company_id', $companyId)->countAllResults();

        $recentActivity = $this->buildRecentTimeline();

        return view('tracking/index', [
            'activeMenu' => 'tracking',
            'pageTitle' => 'Análises',
            'metrics' => [
                'sent' => $totalSent,
                'opens' => $totalOpens,
                'clicks' => $totalClicks,
                'bounces' => $totalBounces,
                'optouts' => $totalOptouts,
            ],
            'recentActivity' => $recentActivity,
        ]);
    }

    /**
     * Lista as aberturas recentes.
     */
    public function opens(): string
    {
        $opens = \Config\Database::connect()->table('message_opens')
            ->select('message_opens.*')
            ->join('message_sends', 'message_sends.id = message_opens.send_id')
            ->join('messages', 'messages.id = message_sends.message_id')
            ->where('messages.company_id', (int) session()->get('company_id'))
            ->orderBy('message_opens.opened_at', 'DESC')->limit(50)->get()->getResultArray();

        return view('tracking/list', [
            'activeMenu' => 'tracking',
            'pageTitle' => 'Aberturas',
            'records' => $opens,
            'type' => 'opens',
        ]);
    }

    /**
     * Lista os cliques recentes.
     */
    public function clicks(): string
    {
        $clicks = \Config\Database::connect()->table('message_clicks')
            ->select('message_clicks.*')
            ->join('message_sends', 'message_sends.id = message_clicks.send_id')
            ->join('messages', 'messages.id = message_sends.message_id')
            ->where('messages.company_id', (int) session()->get('company_id'))
            ->orderBy('message_clicks.clicked_at', 'DESC')->limit(50)->get()->getResultArray();

        return view('tracking/list', [
            'activeMenu' => 'tracking',
            'pageTitle' => 'Cliques',
            'records' => $clicks,
            'type' => 'clicks',
        ]);
    }

    /**
     * Lista os bounces recentes.
     */
    public function bounces(): string
    {
        $bounces = \Config\Database::connect()->table('message_sends')
            ->select('message_sends.*')
            ->join('messages', 'messages.id = message_sends.message_id')
            ->where('messages.company_id', (int) session()->get('company_id'))
            ->where('message_sends.status', 'bounced')
            ->orderBy('message_sends.bounced_at', 'DESC')->limit(50)->get()->getResultArray();

        return view('tracking/list', [
            'activeMenu' => 'tracking',
            'pageTitle' => 'Bounces',
            'records' => $bounces,
            'type' => 'bounces',
        ]);
    }

    /**
     * Lista os opt-outs recentes.
     */
    public function optouts(): string
    {
        $optouts = \Config\Database::connect()->table('optouts')
            ->select('optouts.*')
            ->join('messages', 'messages.id = optouts.message_id')
            ->where('messages.company_id', (int) session()->get('company_id'))
            ->orderBy('optouts.opted_out_at', 'DESC')->limit(50)->get()->getResultArray();

        return view('tracking/list', [
            'activeMenu' => 'tracking',
            'pageTitle' => 'Descadastros',
            'records' => $optouts,
            'type' => 'optouts',
        ]);
    }

    /**
     * Monta uma linha do tempo resumida das últimas atividades.
     *
     * @return array<int, array<string, string>>
     */
    protected function buildRecentTimeline(): array
    {
        $db = \Config\Database::connect();
        $events = [];

        $openEvents = $db->table('message_opens')
            ->select("'open' AS type, opened_at AS event_time")
            ->join('message_sends', 'message_sends.id = message_opens.send_id')
            ->join('messages', 'messages.id = message_sends.message_id')
            ->where('messages.company_id', (int) session()->get('company_id'))
            ->orderBy('opened_at', 'DESC')
            ->limit(20)
            ->get()
            ->getResultArray();

        $clickEvents = $db->table('message_clicks')
            ->select("'click' AS type, clicked_at AS event_time")
            ->join('message_sends', 'message_sends.id = message_clicks.send_id')
            ->join('messages', 'messages.id = message_sends.message_id')
            ->where('messages.company_id', (int) session()->get('company_id'))
            ->orderBy('clicked_at', 'DESC')
            ->limit(20)
            ->get()
            ->getResultArray();

        $optoutEvents = $db->table('optouts')
            ->select("'optout' AS type, opted_out_at AS event_time")
            ->join('messages', 'messages.id = optouts.message_id')
            ->where('messages.company_id', (int) session()->get('company_id'))
            ->orderBy('opted_out_at', 'DESC')
            ->limit(20)
            ->get()
            ->getResultArray();

        $events = array_merge($openEvents, $clickEvents, $optoutEvents);

        usort($events, static function (array $a, array $b): int {
            return strcmp((string) $b['event_time'], (string) $a['event_time']);
        });

        return array_slice(array_map(static function (array $event): array {
            return [
                'type' => $event['type'],
                'time' => Time::parse($event['event_time'])->toLocalizedString('dd/MM/yyyy HH:mm'),
            ];
        }, $events), 0, 20);
    }
}
