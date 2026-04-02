<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaperclipService
{
    private string $baseUrl;
    private string $apiKey;
    private string $companyId;
    private int $cacheTtl = 60;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('paperclip.api_url'), '/');
        $this->apiKey = config('paperclip.api_key');
        $this->companyId = config('paperclip.company_id');
    }

    public function getDashboardData(): array
    {
        return Cache::remember("paperclip_dashboard_{$this->companyId}", $this->cacheTtl, function () {
            return [
                'agents'   => $this->fetchAgents(),
                'issues'   => $this->fetchIssues(),
                'projects' => $this->fetchProjects(),
            ];
        });
    }

    public function getAgents(): array
    {
        return Cache::remember("paperclip_agents_{$this->companyId}", $this->cacheTtl, fn () => $this->fetchAgents());
    }

    public function getIssues(): array
    {
        return Cache::remember("paperclip_issues_{$this->companyId}", $this->cacheTtl, fn () => $this->fetchIssues());
    }

    public function getProjects(): array
    {
        return Cache::remember("paperclip_projects_{$this->companyId}", $this->cacheTtl, fn () => $this->fetchProjects());
    }

    private function fetchAgents(): array
    {
        try {
            $response = $this->get("/api/companies/{$this->companyId}/agents");
            return $response ?? [];
        } catch (\Exception $e) {
            Log::warning('PaperclipService: failed to fetch agents', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function fetchIssues(): array
    {
        try {
            $all = [];

            // Fetch in_progress and todo issues
            foreach (['in_progress', 'todo', 'blocked', 'done'] as $status) {
                $page = $this->get("/api/companies/{$this->companyId}/issues", [
                    'status' => $status,
                    'limit'  => 50,
                ]);
                if (is_array($page)) {
                    $all = array_merge($all, $page);
                }
            }

            return $all;
        } catch (\Exception $e) {
            Log::warning('PaperclipService: failed to fetch issues', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function fetchProjects(): array
    {
        try {
            $response = $this->get("/api/companies/{$this->companyId}/projects");
            return $response ?? [];
        } catch (\Exception $e) {
            Log::warning('PaperclipService: failed to fetch projects', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function get(string $path, array $query = []): mixed
    {
        $url = $this->baseUrl . $path;

        $response = Http::withToken($this->apiKey)
            ->timeout(10)
            ->get($url, $query);

        if ($response->successful()) {
            return $response->json();
        }

        Log::warning('PaperclipService: HTTP error', [
            'url'    => $url,
            'status' => $response->status(),
        ]);

        return null;
    }
}
