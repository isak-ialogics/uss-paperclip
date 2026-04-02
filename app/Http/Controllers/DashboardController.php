<?php

namespace App\Http\Controllers;

use App\Services\PaperclipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private PaperclipService $paperclip) {}

    public function index()
    {
        $data = $this->paperclip->getDashboardData();

        $agents   = $data['agents'];
        $issues   = $data['issues'];
        $projects = $data['projects'];

        $stats = [
            'done'       => count(array_filter($issues, fn ($i) => ($i['status'] ?? '') === 'done')),
            'inProgress' => count(array_filter($issues, fn ($i) => ($i['status'] ?? '') === 'in_progress')),
            'todo'       => count(array_filter($issues, fn ($i) => in_array($i['status'] ?? '', ['todo', 'backlog']))),
            'blocked'    => count(array_filter($issues, fn ($i) => ($i['status'] ?? '') === 'blocked')),
            'agentsTotal'  => count($agents),
            'agentsActive' => count(array_filter($agents, fn ($a) => ($a['status'] ?? '') === 'running')),
        ];

        $blockedIssues = array_filter($issues, fn ($i) => ($i['status'] ?? '') === 'blocked');
        $recentIssues  = array_slice(
            array_filter($issues, fn ($i) => in_array($i['status'] ?? '', ['in_progress', 'done'])),
            0, 10
        );

        return view('dashboard', compact('agents', 'projects', 'stats', 'blockedIssues', 'recentIssues'));
    }

    public function refresh(): JsonResponse
    {
        $data = $this->paperclip->getDashboardData();

        $agents   = $data['agents'];
        $issues   = $data['issues'];
        $projects = $data['projects'];

        $stats = [
            'done'        => count(array_filter($issues, fn ($i) => ($i['status'] ?? '') === 'done')),
            'inProgress'  => count(array_filter($issues, fn ($i) => ($i['status'] ?? '') === 'in_progress')),
            'todo'        => count(array_filter($issues, fn ($i) => in_array($i['status'] ?? '', ['todo', 'backlog']))),
            'blocked'     => count(array_filter($issues, fn ($i) => ($i['status'] ?? '') === 'blocked')),
            'agentsTotal' => count($agents),
            'agentsActive'=> count(array_filter($agents, fn ($a) => ($a['status'] ?? '') === 'running')),
        ];

        return response()->json([
            'agents'        => array_values($agents),
            'projects'      => array_values($projects),
            'stats'         => $stats,
            'blockedIssues' => array_values(array_filter($issues, fn ($i) => ($i['status'] ?? '') === 'blocked')),
            'recentIssues'  => array_values(array_slice(
                array_filter($issues, fn ($i) => in_array($i['status'] ?? '', ['in_progress', 'done'])),
                0, 10
            )),
        ]);
    }
}
