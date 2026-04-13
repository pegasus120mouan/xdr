<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class OpenCtiService
{
    private const SEARCH_QUERY = <<<'GQL'
query OpenCtiThreatHunt($search: String!, $first: Int) {
  stixCoreObjects(search: $search, first: $first) {
    edges {
      node {
        id
        standard_id
        entity_type
        created_at
        updated_at
        ... on Indicator {
          name
          pattern
        }
        ... on AttackPattern {
          name
          x_mitre_id
        }
        ... on Malware {
          name
        }
        ... on ThreatActor {
          name
        }
        ... on Campaign {
          name
        }
        ... on IntrusionSet {
          name
        }
      }
    }
    pageInfo {
      globalCount
      hasNextPage
    }
  }
}
GQL;

    /**
     * @return array<string, mixed>
     */
    public function search(string $term, ?int $first = null): array
    {
        if (! config('opencti.enabled', false)) {
            return ['ok' => false, 'reason' => 'disabled'];
        }

        $token = (string) config('opencti.token', '');
        if ($token === '') {
            return [
                'ok' => false,
                'reason' => 'missing_token',
                'message' => 'Définissez OPENCTI_TOKEN et OPENCTI_URL dans .env.',
            ];
        }

        $base = (string) config('opencti.base_url', '');
        if ($base === '') {
            return [
                'ok' => false,
                'reason' => 'missing_url',
                'message' => 'Définissez OPENCTI_URL (ex. https://votre-opencti.example.com).',
            ];
        }

        $first = $first ?? (int) config('opencti.default_first', 25);
        $first = max(5, min(100, $first));

        $term = trim($term);
        if ($term === '') {
            return ['ok' => false, 'reason' => 'empty_search'];
        }

        $cacheKey = 'opencti:search:'.sha1($term.'|'.$first);
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return array_merge($cached, ['cached' => true]);
        }

        $rateKey = 'opencti:outbound';
        $perMinute = (int) config('opencti.rate_per_minute', 30);
        if (RateLimiter::tooManyAttempts($rateKey, $perMinute)) {
            return [
                'ok' => false,
                'reason' => 'rate_limited',
                'retry_after' => RateLimiter::availableIn($rateKey),
            ];
        }

        RateLimiter::hit($rateKey, 60);

        $url = $base.'/graphql';
        $timeout = (int) config('opencti.timeout', 25);

        try {
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($url, [
                    'query' => self::SEARCH_QUERY,
                    'variables' => [
                        'search' => $term,
                        'first' => $first,
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::warning('OpenCTI request failed', ['message' => $e->getMessage()]);

            return ['ok' => false, 'reason' => 'http_exception', 'message' => $e->getMessage()];
        }

        if (! $response->successful()) {
            return [
                'ok' => false,
                'reason' => 'http_error',
                'http_status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
            ];
        }

        $json = $response->json();
        if (! is_array($json)) {
            return ['ok' => false, 'reason' => 'invalid_json'];
        }

        if (! empty($json['errors'])) {
            return [
                'ok' => false,
                'reason' => 'graphql_errors',
                'errors' => $json['errors'],
                'hint' => 'Vérifiez la version d’OpenCTI (schéma GraphQL). Le playground sur votre instance permet d’ajuster la requête.',
            ];
        }

        $data = $json['data'] ?? null;
        $stix = is_array($data) ? ($data['stixCoreObjects'] ?? null) : null;

        $payload = [
            'ok' => true,
            'cached' => false,
            'search' => $term,
            'first' => $first,
            'graphql_url' => $url,
            'raw' => $data,
            'rows' => $this->flattenNodes(is_array($stix) ? $stix : [], $base),
            'page_info' => is_array($stix) ? ($stix['pageInfo'] ?? null) : null,
        ];

        Cache::put($cacheKey, $payload, now()->addSeconds((int) config('opencti.cache_ttl', 900)));

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $stixCoreObjects
     * @return list<array<string, mixed>>
     */
    protected function flattenNodes(array $stixCoreObjects, string $baseUrl): array
    {
        $edges = $stixCoreObjects['edges'] ?? [];
        if (! is_array($edges)) {
            return [];
        }

        $rows = [];
        foreach ($edges as $edge) {
            $n = is_array($edge) ? ($edge['node'] ?? null) : null;
            if (! is_array($n)) {
                continue;
            }

            $label = $n['name'] ?? $n['pattern'] ?? null;

            $id = $n['id'] ?? null;
            $rows[] = [
                'id' => $id,
                'standard_id' => $n['standard_id'] ?? null,
                'entity_type' => $n['entity_type'] ?? null,
                'label' => $label,
                'pattern' => $n['pattern'] ?? null,
                'x_mitre_id' => $n['x_mitre_id'] ?? null,
                'created_at' => $n['created_at'] ?? null,
                'updated_at' => $n['updated_at'] ?? null,
                'open_url' => ($baseUrl !== '' && $id) ? $baseUrl.'/dashboard/id/'.$id : null,
            ];
        }

        return $rows;
    }
}
