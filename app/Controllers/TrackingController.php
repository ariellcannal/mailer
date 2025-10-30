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
        $sendModel = new MessageSendModel();
        $openModel = new MessageOpenModel();
        $clickModel = new MessageClickModel();
        $optoutModel = new OptoutModel();

        $totalSent = $sendModel->where('status', 'sent')->countAllResults();
        $totalOpens = $openModel->countAllResults();
        $totalClicks = $clickModel->countAllResults();
        $totalBounces = $sendModel->where('status', 'bounced')->countAllResults();
        $totalOptouts = $optoutModel->countAllResults();

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
        $openModel = new MessageOpenModel();
        $opens = $openModel->orderBy('opened_at', 'DESC')->findAll(50);

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
        $clickModel = new MessageClickModel();
        $clicks = $clickModel->orderBy('clicked_at', 'DESC')->findAll(50);

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
        $sendModel = new MessageSendModel();
        $bounces = $sendModel->where('status', 'bounced')
            ->orderBy('bounced_at', 'DESC')
            ->findAll(50);

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
        $optoutModel = new OptoutModel();
        $optouts = $optoutModel->orderBy('opted_out_at', 'DESC')->findAll(50);

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
            ->orderBy('opened_at', 'DESC')
            ->limit(20)
            ->get()
            ->getResultArray();

        $clickEvents = $db->table('message_clicks')
            ->select("'click' AS type, clicked_at AS event_time")
            ->orderBy('clicked_at', 'DESC')
            ->limit(20)
            ->get()
            ->getResultArray();

        $optoutEvents = $db->table('optouts')
            ->select("'optout' AS type, opted_out_at AS event_time")
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
