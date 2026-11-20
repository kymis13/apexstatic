<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Публичный профиль Apex Legends Status — единственный доступный без
 * коммерческого API-ключа источник детальной статистики по легендам.
 *
 * Steam OpenID даёт нам SteamID64, по нему сайт Apex Legends Status
 * показывает реальные игровые показатели: уровень, ранг, убийства,
 * победы, урон и разбивку по каждому персонажу. Данные кэшируются.
 */
class ApexProfileScraper
{
    private const BASE_URL = 'https://apexlegendsstatus.com';

    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0 Safari/537.36';

    public function fetch(string $steamId): ?array
    {
        return Cache::remember('apex:profile:'.$steamId, now()->addMinutes(10), function () use ($steamId) {
            return $this->scrape($steamId);
        });
    }

    public function forget(string $steamId): void
    {
        Cache::forget('apex:profile:'.$steamId);
    }

    private function scrape(string $steamId): ?array
    {
        $profileUrl = self::BASE_URL.'/profile/uid/'.$steamId.'/PC';

        try {
            $page = Http::withHeaders([
                'User-Agent' => self::USER_AGENT,
                'Accept' => 'text/html,application/xhtml+xml',
            ])->timeout(20)->get($profileUrl);

            if (! $page->successful()) {
                throw new RuntimeException('Apex Legends Status вернул HTTP '.$page->status().'.');
            }

            $html = $page->body();

            if (! preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $tokenMatch)) {
                throw new RuntimeException('Не удалось получить CSRF-токен Apex Legends Status.');
            }

            $cookies = $this->cookies($page);

            $fragment = Http::withHeaders([
                'User-Agent' => self::USER_AGENT,
                'Accept' => 'text/html, */*',
                'X-Requested-With' => 'XMLHttpRequest',
                'Referer' => $profileUrl,
                'Cookie' => $cookies,
            ])->timeout(20)->get(self::BASE_URL.'/core/interface-v2', [
                'platform' => 'PC',
                'uid' => $steamId,
                'token' => html_entity_decode($tokenMatch[1], ENT_QUOTES),
            ]);

            if (! $fragment->successful()) {
                throw new RuntimeException('Apex Legends Status вернул HTTP '.$fragment->status().' для профиля.');
            }

            $body = $fragment->body();

            if (str_contains($body, 'No profile found')) {
                return null;
            }

            return $this->parse($body);
        } catch (RuntimeException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new RuntimeException('Ошибка загрузки профиля Apex Legends: '.$exception->getMessage(), 0, $exception);
        }
    }

    private function cookies($response): string
    {
        $lines = $response->toPsrResponse()->getHeader('Set-Cookie');
        $cookies = [];

        foreach ($lines as $line) {
            $pair = trim(explode(';', $line)[0]);
            if ($pair !== '') {
                $cookies[] = $pair;
            }
        }

        return implode('; ', $cookies);
    }

    private function parse(string $html): array
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        $level = $this->intValue($xpath, "//div[contains(@class,'v2-sb-stat--level')]//strong[contains(@class,'v2-sb-stat__value')]");
        $rankPoints = $this->intValue($xpath, "//div[contains(@class,'v2-sb-stat--rank')]//strong[contains(@class,'v2-sb-stat__value')]");
        $rankImage = $this->attribute($xpath, "//div[contains(@class,'v2-sb-stat--rank')]//img[contains(@class,'image-sharp')]", 'src');
        $rankTier = $this->rankName($rankImage);

        $selectedLegend = $this->text($xpath, "//div[contains(@class,'profile-legend') and contains(@class,'v2-selected')]//h2[contains(@class,'profile-legend__name')]");

        $legends = [];
        $global = ['kills' => 0, 'wins' => 0, 'damage' => 0];

        $blocks = $xpath->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' profile-legend ')]");

        foreach ($blocks as $block) {
            $name = $this->text($xpath, ".//h2[contains(@class,'profile-legend__name')]", $block);

            if ($name === '') {
                continue;
            }

            $stats = $this->stats($xpath, $block);

            if ($name === 'Global') {
                $global = [
                    'kills' => $stats['BR Kills'] ?? $stats['Career Kills'] ?? 0,
                    'wins' => $stats['BR Wins'] ?? $stats['Career Wins'] ?? 0,
                    'damage' => $stats['BR Damage'] ?? 0,
                ];
                continue;
            }

            if (! isset($stats['BR Kills']) && ! isset($stats['BR Damage']) && ! isset($stats['BR Wins'])) {
                continue;
            }

            $legends[$name] = [
                'name' => $name,
                'kills' => $stats['BR Kills'] ?? 0,
                'wins' => $stats['BR Wins'] ?? 0,
                'damage' => $stats['BR Damage'] ?? 0,
            ];
        }

        return [
            'level' => $level,
            'rank_tier' => $rankTier,
            'rank_points' => $rankPoints,
            'selected_legend' => $selectedLegend ?: null,
            'global' => $global,
            'legends' => array_values($legends),
        ];
    }

    private function stats(DOMXPath $xpath, $context): array
    {
        $result = [];
        $nodes = $xpath->query(".//div[contains(concat(' ', normalize-space(@class), ' '), ' profile-stats ')]", $context);

        foreach ($nodes as $node) {
            $label = $this->attribute($xpath, ".//span[contains(@class,'v2-stat__label')]", 'title', $node);
            $value = $this->text($xpath, ".//span[contains(@class,'amount__total')]", $node);

            if ($label === '' || $value === '') {
                continue;
            }

            $result[$label] = $this->intValueFromString($value);
        }

        return $result;
    }

    private function rankName(string $image): string
    {
        $file = strtolower(basename(parse_url($image, PHP_URL_PATH) ?: ''));
        $key = preg_replace('/[^a-z]/', '', pathinfo($file, PATHINFO_FILENAME));

        $base = preg_replace('/[1-4]$/', '', $key);
        $division = substr($key, -1);

        $names = [
            'rookie' => 'Новичок',
            'bronze' => 'Бронза',
            'silver' => 'Серебро',
            'gold' => 'Золото',
            'platinum' => 'Платина',
            'diamond' => 'Алмаз',
            'master' => 'Мастер',
            'predator' => 'Хищник',
            'apexpredator' => 'Хищник',
        ];

        $name = $names[$base] ?? null;

        if (! $name) {
            return 'Неизвестно';
        }

        if (in_array($base, ['rookie', 'bronze', 'silver', 'gold', 'platinum', 'diamond'], true) && in_array($division, ['1', '2', '3', '4'], true)) {
            return $name.' '.$this->roman((int) $division);
        }

        return $name;
    }

    private function roman(int $number): string
    {
        return [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV'][$number] ?? (string) $number;
    }

    private function text(DOMXPath $xpath, string $query, $context = null): string
    {
        $nodes = $xpath->query($query, $context);

        return $nodes && $nodes->length > 0 ? trim($nodes->item(0)->textContent) : '';
    }

    private function attribute(DOMXPath $xpath, string $query, string $attribute, $context = null): string
    {
        $nodes = $xpath->query($query, $context);

        return $nodes && $nodes->length > 0 ? trim($nodes->item(0)->getAttribute($attribute)) : '';
    }

    private function intValue(DOMXPath $xpath, string $query): int
    {
        return $this->intValueFromString($this->text($xpath, $query));
    }

    private function intValueFromString(string $value): int
    {
        return (int) preg_replace('/[^\d]/', '', $value);
    }
}
