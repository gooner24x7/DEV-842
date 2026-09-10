<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\TenderNotice;
use Carbon\Carbon;
use Exception;

class SyncTenderNotices extends Command
{
    protected $signature = 'tenders:sync {--days=30 : Number of days to sync back} {--sources= : Comma separated list of sources to sync} {--max-pages=50 : Maximum pages per source (0 for unlimited)}';
    protected $description = 'Sync tender notices from UK public procurement and planning portals';

    protected array $targetCpvs = ['44', '45', '71'];
    protected array $ukpOrgCache = [];

    public function handle(): void
    {
        // Clear any previous cancel flags at start
        Cache::forget('sync_cancel');

        $maxPages = (int) $this->option('max-pages');
        $days = (int) $this->option('days');
        $dateFrom = Carbon::now()->subDays($days)->startOfDay();
        $dateTo = Carbon::now()->endOfDay();

        $sourcesOpt = $this->option('sources');
        $sources = $sourcesOpt ? explode(',', $sourcesOpt) : ['fts', 'cf', 'pcs', 's2w', 'ted', 'ukp'];

        $this->info("Starting tender sync from {$dateFrom->toDateString()} to {$dateTo->toDateString()} (max pages: " . ($maxPages > 0 ? $maxPages : 'unlimited') . ")...");

        if (in_array('fts', $sources)) $this->syncFTS($dateFrom, $dateTo, $maxPages);
        if (in_array('cf', $sources)) $this->syncContractsFinder($dateFrom, $dateTo, $maxPages);

        $months = [];
        $period = new \DatePeriod($dateFrom, new \DateInterval('P1M'), $dateTo);
        foreach ($period as $dt) { $months[] = $dt->format('m-Y'); }
        if (!in_array($dateTo->format('m-Y'), $months)) { $months[] = $dateTo->format('m-Y'); }

        if (in_array('pcs', $sources)) $this->syncPCS($months, $maxPages);
        if (in_array('s2w', $sources)) $this->syncSell2Wales($months, $maxPages);

        if (in_array('ted', $sources)) $this->syncTED($dateFrom, $dateTo, $maxPages);
        if (in_array('ukp', $sources)) $this->syncUKPlanning($dateFrom, $dateTo, $maxPages);

        if (Cache::get('sync_cancel')) {
            $this->info('Tender sync was cancelled by user.');
            Cache::forget('sync_cancel');
        } else {
            $this->info('Tender sync complete!');
        }
    }

    protected function hasMatchingCpv($release): bool
    {
        // Check root classification (used by Contracts Finder)
        if (!empty($release['tender']['classification']['scheme']) && strcasecmp($release['tender']['classification']['scheme'], 'CPV') === 0) {
            $id = substr(trim($release['tender']['classification']['id']), 0, 2);
            if (in_array($id, $this->targetCpvs)) return true;
        }

        if (!empty($release['tender']['additionalClassifications'])) {
            foreach ($release['tender']['additionalClassifications'] as $ac) {
                if (!empty($ac['scheme']) && strcasecmp($ac['scheme'], 'CPV') === 0) {
                    $id = substr(trim($ac['id']), 0, 2);
                    if (in_array($id, $this->targetCpvs)) return true;
                }
            }
        }

        // Check tender items
        if (!empty($release['tender']['items'])) {
            foreach ($release['tender']['items'] as $item) {
                if (!empty($item['classification']['scheme']) && strcasecmp($item['classification']['scheme'], 'CPV') === 0) {
                    $id = substr(trim($item['classification']['id']), 0, 2);
                    if (in_array($id, $this->targetCpvs)) return true;
                }
                if (!empty($item['additionalClassifications'])) {
                    foreach ($item['additionalClassifications'] as $ac) {
                        if (!empty($ac['scheme']) && strcasecmp($ac['scheme'], 'CPV') === 0) {
                            $id = substr(trim($ac['id']), 0, 2);
                            if (in_array($id, $this->targetCpvs)) return true;
                        }
                    }
                }
            }
        }

        // Check award items
        if (!empty($release['awards'])) {
            foreach ($release['awards'] as $award) {
                if (!empty($award['items'])) {
                    foreach ($award['items'] as $item) {
                        if (!empty($item['classification']['scheme']) && strcasecmp($item['classification']['scheme'], 'CPV') === 0) {
                            $id = substr(trim($item['classification']['id']), 0, 2);
                            if (in_array($id, $this->targetCpvs)) return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    protected function processReleases($releases, $serviceName): void
    {
        foreach ($releases as $release) {
            if (!$this->hasMatchingCpv($release)) continue;

            $noticeId = $release['id'] ?? null;
            if (!$noticeId) continue;

            $ocid = $release['ocid'] ?? $noticeId;
            $releaseDate = $release['date'] ?? null;

            $title = $release['tender']['title'] ?? 'Untitled Procurement Opportunity';
            $buyerName = $release['buyer']['name'] ?? 'Undisclosed Contracting Authority';

            // Extract Stage
            $stage = 'tender';
            if (!empty($release['tender']['status'])) {
                if ($release['tender']['status'] === 'planning') $stage = 'planning';
                if ($release['tender']['status'] === 'active') $stage = 'tender';
                if ($release['tender']['status'] === 'complete') $stage = 'award';
            } elseif (!empty($release['tag'])) {
                if (in_array('planning', $release['tag'])) $stage = 'planning';
                if (in_array('tender', $release['tag'])) $stage = 'tender';
                if (in_array('award', $release['tag'])) $stage = 'award';
            }

            // Extract End Date
            $endDate = null;
            if (!empty($release['tender']['tenderPeriod']['endDate'])) {
                $endDate = Carbon::parse($release['tender']['tenderPeriod']['endDate'])->toDateTimeString();
            }

            // Fallback Link logic
            $docUrl = null;
            if ($serviceName === 'Find-a-Tender' && $noticeId) {
                $docUrl = "https://www.find-tender.service.gov.uk/Notice/{$noticeId}";
            } else if ($serviceName === 'Contracts Finder' && $noticeId) {
                $idPart = $noticeId;
                if (strlen($idPart) > 36) $idPart = substr($idPart, 0, 36);
                $docUrl = "https://www.contractsfinder.service.gov.uk/Notice/{$idPart}";
            } else if ($serviceName === 'Sell2Wales' && $noticeId) {
                $docUrl = "https://www.sell2wales.gov.wales/search/show/search_view.aspx?ID={$noticeId}";
            }

            $content = json_encode($release);

            TenderNotice::updateOrCreate(
                ['notice_id' => (string) $noticeId],
                [
                    'procurement_id' => (string) $ocid,
                    'type' => $serviceName,
                    'title' => substr($title, 0, 255),
                    'organisation' => substr($buyerName, 0, 255),
                    'notice_type' => $stage,
                    'link' => (string) $docUrl,
                    'date_published' => $releaseDate ? Carbon::parse($releaseDate)->toDateTimeString() : null,
                    'date_closing' => $endDate,
                    'content' => $content
                ]
            );
        }
    }

    protected function syncFTS($dateFrom, $dateTo, $maxPages = 50): void
    {
        $this->info("Fetching Find-a-Tender...");
        $url = "https://www.find-tender.service.gov.uk/api/1.0/ocdsReleasePackages";
        $params = [
            'limit' => 100,
            'updatedFrom' => $dateFrom->format('Y-m-d\TH:i:s'),
            'updatedTo' => $dateTo->format('Y-m-d\T23:59:59'),
        ];
        $this->fetchPaginated($url . '?' . http_build_query($params), 'Find-a-Tender', $maxPages);
    }

    protected function syncContractsFinder($dateFrom, $dateTo, $maxPages = 50): void
    {
        $this->info("Fetching Contracts Finder...");
        $url = "https://www.contractsfinder.service.gov.uk/Published/Notices/OCDS/Search";
        $params = [
            'limit' => 100,
            'publishedFrom' => $dateFrom->format('Y-m-d\TH:i:s\Z'),
            'publishedTo' => $dateTo->format('Y-m-d\T23:59:59\Z'),
        ];
        $this->fetchPaginated($url . '?' . http_build_query($params), 'Contracts Finder', $maxPages);
    }

    protected function syncPCS($months, $maxPages = 50): void
    {
        $this->info("Fetching Public Contracts Scotland...");
        foreach ($months as $m) {
            $url = "https://api.publiccontractsscotland.gov.uk/v1/Notices?monthYear={$m}";
            $this->fetchPaginated($url, 'Public Contracts Scotland', $maxPages);
        }
    }

    protected function syncSell2Wales($months, $maxPages = 50): void
    {
        $this->info("Fetching Sell2Wales...");
        foreach ($months as $m) {
            $url = "https://api.sell2wales.gov.wales/v1/Notices?monthYear={$m}";
            $this->fetchPaginated($url, 'Sell2Wales', $maxPages);
        }
    }

    protected function syncTED($dateFrom, $dateTo, $maxPages = 50): void
    {
        $this->info("Fetching Tenders Electronic Daily...");
        $from = $dateFrom->format('Ymd');
        $to = $dateTo->format('Ymd');
        $query = "publication-date>=$from AND publication-date<=$to AND buyer-country=GBR AND classification-cpv IN (44*, 45*, 71*)";
        $url = "https://api.ted.europa.eu/v3/notices/search";
        $page = 1;

        while ($maxPages <= 0 || $page <= $maxPages) {
            if (Cache::get('sync_cancel')) return;

            $payload = [
                'query' => $query,
                'fields' => ['publication-number', 'publication-date', 'notice-title', 'buyer-name', 'notice-type'],
                'page' => $page,
                'limit' => 250
            ];

            try {
                $response = Http::withOptions(['verify' => false])->timeout(15)->post($url, $payload);
                if (!$response->successful() || empty($response->json('notices'))) break;

                $notices = $response->json('notices');
                foreach ($notices as $result) {
                    $noticeId = $result['publication-number'] ?? null;
                    if (!$noticeId) continue;

                    $title = 'Untitled';
                    if (!empty($result['notice-title'])) {
                        $titles = $result['notice-title'];
                        $title = is_string($titles) ? $titles : (is_array($titles) ? (isset($titles['eng']) ? $titles['eng'] : reset($titles)) : 'Untitled');
                        if (is_array($title)) $title = reset($title);
                    }

                    $buyer = 'Undisclosed';
                    if (!empty($result['buyer-name'])) {
                        $buyers = $result['buyer-name'];
                        $buyer = is_string($buyers) ? $buyers : (is_array($buyers) ? (isset($buyers['eng']) ? $buyers['eng'] : reset($buyers)) : 'Undisclosed');
                        if (is_array($buyer)) $buyer = reset($buyer);
                    }

                    $stage = 'tender';
                    if (!empty($result['notice-type'])) {
                        $td = strtolower($result['notice-type']);
                        if (strpos($td, 'pin') !== false) $stage = 'planning';
                        elseif (strpos($td, 'can') !== false) $stage = 'award';
                    }

                    $pubDate = $result['publication-date'] ?? date('Y-m-d');
                    if (strlen($pubDate) > 10) $pubDate = substr($pubDate, 0, 10);

                    $docUrl = "https://ted.europa.eu/udl?uri=TED:NOTICE:{$noticeId}:TEXT:EN:HTML";

                    TenderNotice::updateOrCreate(
                        ['notice_id' => (string) $noticeId],
                        [
                            'procurement_id' => (string) $noticeId,
                            'type' => 'Tenders Electronic Daily',
                            'title' => substr($title, 0, 255),
                            'organisation' => substr($buyer, 0, 255),
                            'notice_type' => $stage,
                            'link' => $docUrl,
                            'date_published' => Carbon::parse($pubDate)->toDateTimeString(),
                            'date_closing' => null,
                            'content' => json_encode($result)
                        ]
                    );
                }

                if (count($notices) < 250) break;
                $page++;
            } catch (Exception $e) {
                $this->error("TED Error: " . $e->getMessage());
                break;
            }
        }
    }

    protected function syncUKPlanning($dateFrom, $dateTo, $maxPages = 50): void
    {
        $this->info("Fetching UK Planning Applications...");
        $offset = 0;
        $limit = 100;
        $pages = 0;

        while ($maxPages <= 0 || $pages < $maxPages) {
            if (Cache::get('sync_cancel')) return;

            $url = "https://www.planning.data.gov.uk/entity.json?dataset=planning-application&limit={$limit}&offset={$offset}&entry_date_since={$dateFrom->format('Y-m-d')}&entry_date_until={$dateTo->format('Y-m-d')}";

            try {
                $response = Http::withOptions(['verify' => false])->timeout(15)->get($url);
                if (!$response->successful() || empty($response->json('entities'))) break;

                $entities = $response->json('entities');
                foreach ($entities as $result) {
                    $noticeId = $result['entity'] ?? null;
                    if (!$noticeId) continue;

                    $ref = $result['reference'] ?? 'Unknown';
                    $desc = $result['description'] ?? 'Planning Application';

                    // Domestic filtering
                    $lowerRef = strtolower(trim($ref));
                    $lowerDesc = strtolower($desc);

                    // 1. Check reference suffix
                    if (str_ends_with($lowerRef, '/tpo') || str_ends_with($lowerRef, '/hou') || str_ends_with($lowerRef, '/hh')) {
                        continue;
                    }

                    // 2. Check description keywords
                    $domesticKeywords = [
                        'single storey extension',
                        'rear extension',
                        'side extension',
                        'front extension',
                        'two storey extension',
                        'extension to dwelling',
                        'conservatory',
                        'porch',
                        'loft conversion',
                        'dormer',
                        'tree preservation',
                        'crown clean',
                        'crown lift',
                        'crown reduce',
                        'crown reduction',
                        'lateral branches',
                        'felling',
                        'prune',
                        'detached dwelling',
                        'granny flat',
                        'bungalow',
                        'domestic garage',
                        'residential extension',
                        'householder'
                    ];

                    $isDomestic = false;
                    foreach ($domesticKeywords as $keyword) {
                        if (str_contains($lowerDesc, $keyword)) {
                            $isDomestic = true;
                            break;
                        }
                    }

                    if ($isDomestic) {
                        continue;
                    }

                    $title = $desc;
                    $shortLength = 200;
                    if (preg_match('/^.*?[.!?](?:\s|$)/', $title, $matches)) {
                        $firstSentence = trim($matches[0]);
                        if (strlen($firstSentence) <= $shortLength) {
                            $title = $firstSentence;
                        } else {
                            $title = trim(substr($title, 0, $shortLength)) . '...';
                        }
                    } elseif (strlen($title) > $shortLength) {
                        $title = trim(substr($title, 0, $shortLength)) . '...';
                    }

                    $orgId = $result['organisation-entity'] ?? '';
                    $orgName = $this->getUKPOrgName($orgId);
                    $ref = $result['reference'] ?? 'Unknown';
                    $buyer = "$orgName (Ref: $ref)";

                    $pubDate = $result['entry-date'] ?? date('Y-m-d');

                    $docUrl = "https://www.planning.data.gov.uk/entity/{$noticeId}";

                    TenderNotice::updateOrCreate(
                        ['notice_id' => (string) "ukp-{$noticeId}"],
                        [
                            'procurement_id' => (string) "ukp-{$noticeId}",
                            'type' => 'UK Planning Applications',
                            'title' => substr($title, 0, 255),
                            'organisation' => substr($buyer, 0, 255),
                            'notice_type' => 'planning',
                            'link' => $docUrl,
                            'date_published' => Carbon::parse($pubDate)->toDateTimeString(),
                            'date_closing' => null,
                            'content' => json_encode($result)
                        ]
                    );
                }

                $offset += $limit;
                $pages++;
            } catch (Exception $e) {
                $this->error("UK Planning Error: " . $e->getMessage());
                break;
            }
        }
    }

    protected function getUKPOrgName($id): string
    {
        if (!$id) return 'Local Planning Authority';
        if (isset($this->ukpOrgCache[$id])) return $this->ukpOrgCache[$id];

        try {
            $response = Http::withOptions(['verify' => false])->timeout(5)->get("https://www.planning.data.gov.uk/entity/{$id}.json");
            if ($response->successful() && $response->json('name')) {
                $name = $response->json('name');
                $this->ukpOrgCache[$id] = $name;
                return $name;
            }
        } catch (Exception $e) {
            // ignore
        }

        $name = 'Local Planning Authority ' . $id;
        $this->ukpOrgCache[$id] = $name;
        return $name;
    }

    protected function fetchPaginated($baseUrl, $serviceName, $maxPages = 50): void
    {
        $url = $baseUrl;
        $pages = 0;

        while ($url && ($maxPages <= 0 || $pages < $maxPages)) {
            if (Cache::get('sync_cancel')) return;

            try {
                // Disable SSL verify specifically if curl throws SEC_E_UNTRUSTED_ROOT
                $response = Http::withOptions(['verify' => false])->timeout(15)->get($url);

                if (!$response->successful() || empty($response->json('releases'))) break;

                $this->processReleases($response->json('releases'), $serviceName);

                $url = $response->json('links.next') ?? null;
                $pages++;
            } catch (Exception $e) {
                $this->error("Error fetching {$serviceName}: " . $e->getMessage());
                break;
            }
        }
    }
}
