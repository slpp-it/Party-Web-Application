<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function party_history_placeholder(string $label, string $title, string $primary, string $secondary): string
{
    $gradientId = 'party-g-' . substr(md5($label . '|' . $title), 0, 10);
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 360 240" role="img" aria-label="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">'
        . '<defs><linearGradient id="' . $gradientId . '" x1="0%" y1="0%" x2="100%" y2="100%">'
        . '<stop offset="0%" stop-color="' . $primary . '"/><stop offset="100%" stop-color="' . $secondary . '"/>'
        . '</linearGradient></defs>'
        . '<rect width="360" height="240" rx="30" fill="url(#' . $gradientId . ')"/>'
        . '<circle cx="286" cy="56" r="52" fill="rgba(255,255,255,0.14)"/>'
        . '<circle cx="74" cy="184" r="72" fill="rgba(255,255,255,0.08)"/>'
        . '<path d="M36 164C86 132 132 120 178 124C224 128 267 118 324 84" stroke="rgba(255,255,255,0.54)" stroke-width="7" stroke-linecap="round" fill="none"/>'
        . '<rect x="28" y="22" width="82" height="30" rx="15" fill="rgba(255,255,255,0.16)"/>'
        . '<text x="69" y="42" text-anchor="middle" font-family="Segoe UI, Arial, sans-serif" font-size="15" font-weight="700" letter-spacing="2" fill="#ffffff">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</text>'
        . '<text x="28" y="208" font-family="Segoe UI, Arial, sans-serif" font-size="18" font-weight="700" fill="#ffffff">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</text>'
        . '</svg>';

    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
}

function party_history_asset_url(string $path): string
{
    $path = trim($path);
    if ($path === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $path) === 1 || str_starts_with($path, '/') || str_starts_with($path, 'data:')) {
        return $path;
    }

    $documentRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $candidate = realpath(__DIR__ . '/' . ltrim($path, '/'));
    if ($documentRoot && $candidate && str_starts_with(str_replace('\\', '/', $candidate), str_replace('\\', '/', $documentRoot))) {
        $relative = substr(str_replace('\\', '/', $candidate), strlen(str_replace('\\', '/', $documentRoot)));
        return $relative !== false ? $relative : '/' . ltrim($path, '/');
    }

    $base = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
    if ($base === '.' || $base === '') {
        $base = '';
    }

    return ($base !== '' ? $base : '') . '/' . ltrim($path, '/');
}

function party_history_normalize_image(?string $value, string $title, string $label, string $primary, string $secondary): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return party_history_placeholder($label, $title, $primary, $secondary);
    }

    if (preg_match('/^<img[^>]+src=["\']([^"\']+)["\']/i', $value, $matches) === 1) {
        $value = trim($matches[1]);
    }

    if (
        preg_match('#^https?://#i', $value) === 1 ||
        str_starts_with($value, '/') ||
        str_starts_with($value, './') ||
        str_starts_with($value, '../') ||
        str_starts_with($value, 'data:image/')
    ) {
        return party_history_asset_url($value);
    }

    if (preg_match('#^[a-zA-Z0-9][a-zA-Z0-9/_\-. ]+\.(?:png|jpe?g|gif|webp|svg|bmp|avif)$#i', $value) === 1) {
        return party_history_asset_url($value);
    }

    return party_history_placeholder($label, $title, $primary, $secondary);
}

function party_history_load(PDO $pdo): array
{
    $statement = $pdo->query(
        'SELECT history_year, event_title, event_description, image_path, accent_primary, accent_secondary, display_order
         FROM party_history
         WHERE is_active = 1
         ORDER BY history_year ASC, display_order ASC, id ASC'
    );

    $items = [];
    foreach ($statement->fetchAll() as $row) {
        $year = trim((string) ($row['history_year'] ?? ''));
        $title = trim((string) ($row['event_title'] ?? ''));
        if ($year === '' || $title === '') {
            continue;
        }

        $primary = trim((string) ($row['accent_primary'] ?? '#8f463d')) ?: '#8f463d';
        $secondary = trim((string) ($row['accent_secondary'] ?? '#e4bf6d')) ?: '#e4bf6d';
        $description = trim((string) ($row['event_description'] ?? ''));

        $items[] = [
            'year' => $year,
            'description' => $description !== '' ? $description : 'History details will be updated soon.',
            'image' => party_history_normalize_image((string) ($row['image_path'] ?? ''), $title, $year, $primary, $secondary),
            'title' => $title,
            'accent_primary' => $primary,
            'accent_secondary' => $secondary,
        ];
    }

    return $items;
}

function party_history_group_years(array $rows): array
{
    $grouped = [];

    foreach ($rows as $row) {
        $year = trim((string) ($row['year'] ?? ''));
        $title = trim((string) ($row['title'] ?? ''));
        if ($year === '' || $title === '') {
            continue;
        }

        if (!isset($grouped[$year])) {
            $grouped[$year] = [
                'year' => $year,
                'title' => $title,
                'description' => trim((string) ($row['description'] ?? '')) ?: 'History details will be updated soon.',
                'image' => (string) ($row['image'] ?? ''),
                'accent_primary' => trim((string) ($row['accent_primary'] ?? '#8f463d')) ?: '#8f463d',
                'accent_secondary' => trim((string) ($row['accent_secondary'] ?? '#e4bf6d')) ?: '#e4bf6d',
                'events' => [],
            ];
        }

        $grouped[$year]['events'][] = [
            'title' => $title,
            'description' => trim((string) ($row['description'] ?? '')) ?: 'History details will be updated soon.',
            'image' => (string) ($row['image'] ?? ''),
            'accent_primary' => trim((string) ($row['accent_primary'] ?? '#8f463d')) ?: '#8f463d',
            'accent_secondary' => trim((string) ($row['accent_secondary'] ?? '#e4bf6d')) ?: '#e4bf6d',
        ];
    }

    $years = array_values($grouped);
    usort($years, static function (array $left, array $right): int {
        return ((int) $left['year']) <=> ((int) $right['year']);
    });

    return $years;
}

$fallbackHistory = [
    [
        'year' => '2010',
        'title' => 'Development momentum',
        'description' => 'Infrastructure, regional development, and national identity messaging carried strong visibility.',
        'image' => party_history_asset_url('images/sagala.jpg'),
        'accent_primary' => '#9f4f47',
        'accent_secondary' => '#e6b765',
    ],
    [
        'year' => '2015',
        'title' => 'Rebuilding the movement',
        'description' => 'Attention shifted to consolidation, local coordination, and rebuilding confidence across districts.',
        'image' => party_history_asset_url('images/namal.jpg'),
        'accent_primary' => '#b76854',
        'accent_secondary' => '#efcf93',
    ],
    [
        'year' => '2019',
        'title' => 'Presidential victory',
        'description' => 'A decisive election year that reshaped organization, momentum, and public attention.',
        'image' => party_history_asset_url('images/mahinda.jpg'),
        'accent_primary' => '#a94e43',
        'accent_secondary' => '#f0d79a',
    ],
    [
        'year' => '2020',
        'title' => 'National leadership transition',
        'description' => 'A year of transition, coordination, and renewed public-facing structure across the party.',
        'image' => party_history_asset_url('images/history_cards/basil.jpg'),
        'accent_primary' => '#8f463d',
        'accent_secondary' => '#e4bf6d',
    ],
];

$partyHistory = [];
$partyLoadMessage = '';
try {
    $partyHistory = party_history_load(getDbConnection());
} catch (Throwable $exception) {
    $partyLoadMessage = 'Party history could not be loaded from the database.';
}

if ($partyHistory === []) {
    $partyHistory = $fallbackHistory;
    if ($partyLoadMessage === '') {
        $partyLoadMessage = 'No active records were found, so the fallback history was shown instead.';
    }
}

$partyHistory = party_history_group_years($partyHistory);

$partyEmbed = !empty($GLOBALS['OUR_PARTY_EMBED']);
$partyAppId = $partyEmbed ? 'party-app-' . substr(md5((string) mt_rand()), 0, 8) : 'party-app';
$partyJson = json_encode($partyHistory, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$partyIntro = 'The Sri Lanka Podujana Peramuna (SLPP) is a relatively modern political party in Sri Lanka, formally established in 2016. It emerged during a period of political transition following the 2015 general elections, when former President Mahinda Rajapaksa lost power. The party was organized primarily by Basil Rajapaksa, with strong political backing and leadership influence from Mahinda Rajapaksa, and it quickly became the central platform for the Joint Opposition.

From its early stages, the SLPP focused on building a strong grassroots network across the country, appealing particularly to rural communities and voters who supported the Rajapaksa leadership. The party’s symbol, the “Pohottuwa” (flower bud), soon became widely recognized, representing growth and renewal. By 2018, the SLPP demonstrated its rising popularity by securing a significant victory in the local government elections, establishing itself as a dominant political force in Sri Lanka.

The party reached its peak in 2019 when Gotabaya Rajapaksa, representing the SLPP, won the presidential election with a clear mandate. This victory marked a turning point, bringing the party into full national power. In 2020, the SLPP further consolidated its position by winning a landslide victory in the parliamentary elections, enabling the formation of a strong government with Mahinda Rajapaksa serving as Prime Minister.

During its time in power, the SLPP pursued policies focused on national development, infrastructure, and economic management. However, by 2022, Sri Lanka faced a severe economic crisis, leading to widespread public protests and political instability. As a result, President Gotabaya Rajapaksa resigned, marking a significant setback for the party and altering the political landscape.

Following this period, the SLPP entered a phase of reorganization and recovery, attempting to rebuild public trust and redefine its political direction. In the 2024 presidential election, the party experienced a major decline in support, and its performance in subsequent parliamentary elections reflected a reduced political presence.

Overall, the history of the SLPP is characterized by a rapid rise to power within a short period, followed by a significant decline influenced by economic and political challenges. Despite these setbacks, the party remains an important political entity in Sri Lanka, with ongoing efforts to reshape its future role in the country’s political landscape.';
$partyIntroParagraphs = array_values(array_filter(
    array_map('trim', preg_split("/\R{2,}/", $partyIntro) ?: []),
    static fn (string $paragraph): bool => $paragraph !== ''
));
$partyTimelineYears = array_values(array_filter(array_map(
    static fn (array $item): int => (int) ($item['year'] ?? 0),
    $partyHistory
)));
$partyTimelineStart = $partyTimelineYears !== [] ? (string) min($partyTimelineYears) : '2010';
$partyTimelineEnd = $partyTimelineYears !== [] ? (string) max($partyTimelineYears) : $partyTimelineStart;
$partyTimelineRecords = array_sum(array_map(
    static fn (array $item): int => count($item['events'] ?? []),
    $partyHistory
));
?>
<?php if (!$partyEmbed): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Party</title>
<?php endif; ?>
    <style>
        :root {
            --party-red: #9f4f47;
            --party-red-deep: #6f241f;
            --party-gold: #f4d27a;
            --party-gold-soft: rgba(244, 210, 122, 0.78);
            --party-ink: #fffaf0;
            --party-muted: rgba(255, 247, 232, 0.8);
            --party-glass: rgba(255, 255, 255, 0.08);
            --party-border: rgba(244, 210, 122, 0.14);
            --party-shadow: 0 22px 58px rgba(60, 12, 10, 0.16);
            --party-radius-xl: 32px;
            --party-radius-lg: 24px;
            --party-ease: 240ms cubic-bezier(.2, .8, .2, 1);
            --party-active-gradient: linear-gradient(135deg, #9f4f47, #e4bf6d);
        }

        .party-app {
            width: 100%;
            margin: 0 auto;
            padding: 0;
            color: var(--party-ink);
            font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
        }

        .party-shell {
            position: relative;
            overflow: hidden;
            border-radius: 0;
            padding: clamp(18px, 2vw, 28px);
            background:
                radial-gradient(circle at 8% 10%, rgba(244, 210, 122, 0.14), transparent 24%),
                radial-gradient(circle at 92% 0%, rgba(184, 90, 77, 0.18), transparent 26%),
                linear-gradient(145deg, rgba(127, 51, 44, 0.28), rgba(184, 90, 77, 0.18)),
                var(--party-glass);
            border: 1px solid rgba(255, 237, 205, 0.09);
            box-shadow: var(--party-shadow);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
        }

        .party-head,
        .party-rail,
        .party-panel {
            position: relative;
            z-index: 1;
        }

        [data-float] {
            opacity: 0;
            transform: translate3d(0, 24px, 0) scale(0.98);
            filter: blur(12px);
            transition:
                opacity 720ms cubic-bezier(.18, .84, .22, 1),
                transform 720ms cubic-bezier(.18, .84, .22, 1),
                filter 720ms cubic-bezier(.18, .84, .22, 1);
            transition-delay: var(--float-delay, 0ms);
            will-change: transform, opacity, filter;
        }

        [data-float].is-visible {
            opacity: 1;
            transform: translateY(0) scale(1);
            filter: blur(0);
        }

        .party-head {
            display: block;
            margin-bottom: 18px;
            width: 100%;
        }

        .party-head > div {
            width: 100%;
        }

        .party-head-copy {
            display: grid;
            gap: 18px;
        }

        .party-head h2 {
            margin: 0;
            font-size: clamp(2.2rem, 4.8vw, 4rem);
            line-height: 0.98;
            letter-spacing: -0.06em;
            font-family: Georgia, "Times New Roman", serif;
        }

        .party-head-lead {
            display: grid;
            gap: 10px;
            max-width: 1000px;
        }

        .party-insight-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .party-insight-pill {
            display: inline-grid;
            gap: 2px;
            min-width: 138px;
            padding: 12px 14px;
            border-radius: 18px;
            border: 1px solid rgba(255,255,255,0.1);
            background: linear-gradient(180deg, rgba(255,255,255,0.11), rgba(255,255,255,0.06));
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.06);
        }

        .party-insight-label {
            color: rgba(255, 241, 220, 0.66);
            font-size: 0.72rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            font-weight: 700;
        }

        .party-insight-value {
            color: var(--party-ink);
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .party-intro {
            display: block;
            width: 100%;
            max-width: 100%;
            display: grid;
            gap: 0;
            position: relative;
            border-radius: var(--party-radius-lg);
            border: 1px solid rgba(255,255,255,0.08);
            background:
                radial-gradient(circle at top right, rgba(244, 210, 122, 0.14), transparent 30%),
                radial-gradient(circle at 0% 100%, rgba(255, 255, 255, 0.08), transparent 32%),
                linear-gradient(180deg, rgba(255,255,255,0.09), rgba(255,255,255,0.05)),
                rgba(255,255,255,0.06);
            padding: clamp(20px, 2.2vw, 30px);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.08);
            transition: transform var(--party-ease), border-color var(--party-ease), background var(--party-ease), box-shadow var(--party-ease), max-height 320ms cubic-bezier(.2, .8, .2, 1), opacity var(--party-ease);
            max-height: 340px;
            overflow: hidden;
            box-sizing: border-box;
        }

        .party-intro:hover {
            transform: translateY(-1px);
            border-color: rgba(244, 210, 122, 0.18);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.08), 0 10px 28px rgba(60, 12, 10, 0.1);
        }

        .party-intro-body {
            display: grid;
            gap: 14px;
            position: relative;
        }

        .party-intro-copy {
            margin: 0;
            color: rgba(255, 248, 236, 0.92);
            line-height: 1.92;
            font-size: clamp(1.02rem, 1.4vw, 1.14rem);
            font-weight: 400;
            letter-spacing: 0.01em;
            max-width: none;
            width: 100%;
            display: -webkit-box;
            -webkit-line-clamp: 7;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-wrap: pretty;
            hyphens: auto;
            opacity: 0.86;
            transform: translateY(0);
            transition: opacity 240ms ease, transform 320ms cubic-bezier(.2, .8, .2, 1), filter 320ms ease;
        }

        .party-intro-copy + .party-intro-copy {
            position: relative;
            padding-top: 14px;
        }

        .party-intro-copy + .party-intro-copy::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            width: min(120px, 22%);
            height: 1px;
            background: linear-gradient(90deg, rgba(244, 210, 122, 0.34), rgba(244, 210, 122, 0));
        }

        .party-intro:not(.is-expanded)::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: 68px;
            height: 84px;
            background: linear-gradient(180deg, rgba(255,255,255,0), rgba(118, 45, 39, 0.68));
            pointer-events: none;
        }

        .party-intro.is-expanded .party-intro-copy {
            display: block;
            -webkit-line-clamp: unset;
            overflow: visible;
            opacity: 1;
            transform: translateY(0);
            filter: none;
        }

        .party-intro.is-expanded {
            max-height: 62vh;
            overflow: auto;
            padding-right: 10px;
            padding-bottom: 14px;
            scrollbar-width: thin;
            scrollbar-color: rgba(244, 210, 122, 0.55) rgba(255,255,255,0.08);
        }

        .party-intro:not(.is-expanded) .party-intro-copy:nth-child(n + 2) {
            opacity: 0.55;
            transform: translateY(10px);
            filter: blur(0.2px);
        }

        .party-intro.is-expanded .party-intro-copy {
            animation: partyIntroReveal 420ms cubic-bezier(.2, .8, .2, 1) both;
            animation-delay: calc(var(--intro-index, 0) * 50ms);
        }

        .party-intro.is-expanded::-webkit-scrollbar {
            width: 8px;
        }

        .party-intro.is-expanded::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.08);
            border-radius: 999px;
        }

        .party-intro.is-expanded::-webkit-scrollbar-thumb {
            background: rgba(244, 210, 122, 0.55);
            border-radius: 999px;
        }

        .party-intro-toggle {
            justify-self: start;
            margin-top: 12px;
            border: 0;
            background: linear-gradient(180deg, rgba(255,255,255,0.12), rgba(255,255,255,0.06));
            color: var(--party-gold-soft);
            font-weight: 800;
            letter-spacing: 0.08em;
            padding: 11px 16px;
            border-radius: 999px;
            box-shadow: 0 8px 20px rgba(60, 12, 10, 0.14);
            cursor: pointer;
            transition: color var(--party-ease), transform var(--party-ease), opacity var(--party-ease), background var(--party-ease), box-shadow var(--party-ease);
            opacity: 1;
            z-index: 1;
        }

        .party-intro-toggle:hover,
        .party-intro-toggle:focus-visible {
            color: #fff1c8;
            background: rgba(255,255,255,0.12);
            transform: translateY(-1px);
            opacity: 1;
            box-shadow: 0 14px 28px rgba(60, 12, 10, 0.18);
            outline: none;
        }

        .party-rail {
            margin-bottom: 18px;
            border-radius: var(--party-radius-lg);
            border: 1px solid var(--party-border);
            background:
                radial-gradient(circle at 20% 12%, rgba(255,255,255,0.08), transparent 24%),
                linear-gradient(145deg, rgba(118, 45, 39, 0.82), rgba(166, 77, 65, 0.68));
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.08);
            padding: 16px;
        }

        .party-rail-head {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            align-items: center;
            margin-bottom: 14px;
        }

        .party-rail-head h3 {
            margin: 0 0 4px;
            font-size: 1.1rem;
            letter-spacing: -0.03em;
        }

        .party-rail-head p {
            margin: 0;
            color: var(--party-muted);
            font-size: 0.93rem;
            line-height: 1.5;
        }

        .party-rail-chip {
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255,255,255,0.08);
            color: var(--party-gold-soft);
            font-size: 0.78rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .party-rail-actions {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .party-rail-action {
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.08);
            color: var(--party-ink);
            width: 40px;
            height: 40px;
            border-radius: 999px;
            display: inline-grid;
            place-items: center;
            cursor: pointer;
            transition: transform var(--party-ease), border-color var(--party-ease), background var(--party-ease);
        }

        .party-rail-action:hover,
        .party-rail-action:focus-visible {
            transform: translateY(-1px);
            border-color: rgba(244, 210, 122, 0.24);
            background: rgba(255,255,255,0.12);
            outline: none;
        }

        .party-rail-track {
            display: grid;
            grid-auto-flow: column;
            grid-auto-columns: clamp(220px, 26vw, 280px);
            gap: 12px;
            overflow-x: auto;
            padding: 10px 4px 12px;
            scroll-snap-type: x mandatory;
            scrollbar-width: none;
            touch-action: pan-x;
            cursor: grab;
            user-select: none;
        }

        .party-rail-track.is-dragging {
            cursor: grabbing;
        }

        .party-rail-track::-webkit-scrollbar {
            display: none;
        }

        .party-rail-progress {
            margin-top: 8px;
            height: 4px;
            border-radius: 999px;
            background: rgba(255, 241, 220, 0.16);
            overflow: hidden;
        }

        .party-rail-progress span {
            display: block;
            width: 0%;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #fff1c8, var(--party-gold));
            box-shadow: 0 0 18px rgba(244, 210, 122, 0.3);
            transition: width var(--party-ease);
        }

        .party-year-card {
            position: relative;
            scroll-snap-align: start;
            text-align: left;
            padding: 16px;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 22px;
            background: rgba(255,255,255,0.08);
            color: var(--party-ink);
            cursor: pointer;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            transition: transform var(--party-ease), box-shadow var(--party-ease), border-color var(--party-ease), background var(--party-ease);
        }

        .party-year-card:hover,
        .party-year-card:focus-visible,
        .party-year-card.active {
            transform: translateY(-3px);
            border-color: rgba(244, 210, 122, 0.22);
            background: rgba(255,255,255,0.12);
            box-shadow: 0 14px 34px rgba(60, 12, 10, 0.16);
            outline: none;
        }

        .party-year-card::before {
            content: "";
            position: absolute;
            top: -9px;
            left: 18px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: linear-gradient(135deg, #fff4d5, var(--party-gold));
            box-shadow: 0 0 0 7px rgba(244, 210, 122, 0.12);
            transition: transform var(--party-ease);
        }

        .party-year-card.active::before {
            transform: scale(1.12);
        }

        .party-year-card-image {
            height: 104px;
            border-radius: 18px;
            background-size: cover;
            background-position: center center;
            border: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 12px;
            position: relative;
            overflow: hidden;
        }

        .party-year-card-image::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(45, 18, 16, 0.08), rgba(45, 18, 16, 0.26));
        }

        .party-year-card-year {
            display: block;
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 1;
        }

        .party-year-card-title {
            display: block;
            margin-top: 6px;
            color: var(--party-muted);
            font-size: 0.95rem;
            line-height: 1.45;
            min-height: 2.6em;
        }

        .party-panel {
            display: block;
        }

        .party-feature {
            border-radius: var(--party-radius-lg);
            border: 1px solid var(--party-border);
            background:
                radial-gradient(circle at 20% 12%, rgba(255,255,255,0.08), transparent 24%),
                linear-gradient(145deg, rgba(118, 45, 39, 0.82), rgba(166, 77, 65, 0.68));
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.08);
            overflow: hidden;
            width: 100%;
        }

        .party-feature.is-switching .party-feature-copy,
        .party-feature.is-switching .party-feature-events {
            opacity: 0;
            transform: translateY(10px);
        }

        .party-feature-copy {
            padding: 20px 20px 10px;
            transition: opacity var(--party-ease), transform var(--party-ease);
        }

        .party-year-badge {
            display: inline-flex;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255,255,255,0.1);
            color: var(--party-gold-soft);
            letter-spacing: 0.12em;
            text-transform: uppercase;
            font-size: 0.75rem;
            margin-bottom: 12px;
        }

        .party-feature-copy h3 {
            margin: 0 0 10px;
            font-size: clamp(1.5rem, 2.3vw, 2rem);
            line-height: 1.04;
            letter-spacing: -0.04em;
            font-family: Georgia, "Times New Roman", serif;
        }

        .party-feature-copy p {
            margin: 0;
            color: var(--party-muted);
            line-height: 1.7;
            font-size: 0.98rem;
            max-width: 90ch;
        }

        .party-feature-events {
            padding: 8px 20px 24px;
            display: block;
            transition: opacity var(--party-ease), transform var(--party-ease);
        }

        .party-tree {
            position: relative;
            display: grid;
            gap: 12px;
            padding-left: 18px;
        }

        .party-tree::before {
            content: "";
            position: absolute;
            left: 7px;
            top: 4px;
            bottom: 6px;
            width: 2px;
            border-radius: 999px;
            background: linear-gradient(180deg, rgba(255,241,200,0.85), rgba(244,210,122,0.18));
        }

        .party-tree-item {
            position: relative;
            animation: partyFadeUp 420ms cubic-bezier(.2, .8, .2, 1) both;
            animation-delay: calc(var(--item-index, 0) * 50ms);
        }

        .party-tree-dot {
            position: absolute;
            left: -18px;
            top: 22px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: linear-gradient(135deg, #fff4d5, var(--party-gold));
            box-shadow: 0 0 0 7px rgba(244, 210, 122, 0.12);
        }

        .party-tree-card {
            display: grid;
            grid-template-columns: 108px minmax(0, 1fr);
            gap: 16px;
            align-items: stretch;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.08);
            overflow: hidden;
            color: inherit;
        }

        .party-tree-image {
            min-height: 104px;
            background-size: cover;
            background-position: center center;
        }

        .party-tree-body {
            padding: 14px 14px 14px 0;
        }

        .party-tree-body strong {
            display: block;
            font-size: 1rem;
            margin-bottom: 8px;
            letter-spacing: -0.02em;
        }

        .party-tree-body p {
            margin: 0;
            color: var(--party-muted);
            line-height: 1.68;
            font-size: 0.96rem;
        }

        .party-empty {
            padding: 18px;
            color: var(--party-muted);
        }

        @keyframes partyFadeUp {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes partyIntroReveal {
            from {
                opacity: 0;
                transform: translateY(16px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .party-load-message {
            margin-top: 14px;
            padding: 10px 12px;
            border-radius: 999px;
            display: inline-flex;
            background: rgba(255,255,255,0.08);
            color: var(--party-gold-soft);
            font-size: 0.78rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        @media (max-width: 720px) {
            .party-shell {
                padding: 14px;
            }

            .party-head {
                grid-template-columns: 1fr;
            }

            .party-head-copy {
                gap: 16px;
            }

            .party-insight-pill {
                min-width: 0;
                flex: 1 1 140px;
            }

            .party-intro {
                max-height: 380px;
            }

            .party-rail {
                padding: 14px;
            }

            .party-rail-track {
                grid-auto-columns: 80%;
            }

            .party-rail-head {
                flex-direction: column;
                align-items: flex-start;
            }

            .party-feature-media {
                min-height: 0;
            }

            .party-feature-copy,
            .party-feature-events {
                padding-left: 14px;
                padding-right: 14px;
            }

            .party-tree-card {
                grid-template-columns: 1fr;
            }

            .party-tree-image {
                min-height: 140px;
            }

            .party-tree-body {
                padding: 0 14px 14px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 1ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 1ms !important;
                scroll-behavior: auto !important;
            }
        }
    </style>
<?php if (!$partyEmbed): ?>
</head>
<body>
<?php endif; ?>
<section id="<?php echo htmlspecialchars($partyAppId, ENT_QUOTES, 'UTF-8'); ?>" class="party-app">
    <div class="party-shell">
        <header class="party-head" data-float style="--float-delay: 0ms;">
            <div class="party-head-copy">
                <div class="party-head-lead">
                    <h2>History of the Party</h2>
                </div>
                <div class="party-insight-row" aria-label="Party history insights">
                    <div class="party-insight-pill">
                        <span class="party-insight-label">Timeline</span>
                        <span class="party-insight-value"><?php echo htmlspecialchars($partyTimelineStart . ' - ' . $partyTimelineEnd, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="party-insight-pill">
                        <span class="party-insight-label">Records</span>
                        <span class="party-insight-value"><?php echo htmlspecialchars((string) $partyTimelineRecords, ENT_QUOTES, 'UTF-8'); ?> milestones</span>
                    </div>
                </div>
                <div class="party-intro" id="partyIntro">
                    <div class="party-intro-body">
                        <?php foreach ($partyIntroParagraphs as $index => $paragraph): ?>
                            <p class="party-intro-copy" style="--intro-index: <?php echo (int) $index; ?>"><?php echo htmlspecialchars($paragraph, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button class="party-intro-toggle" type="button" id="partyIntroToggle" aria-expanded="false">See more</button>
                <?php if ($partyLoadMessage !== ''): ?>
                    <div class="party-load-message"><?php echo htmlspecialchars($partyLoadMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
            </div>
        </header>

        <section class="party-rail" data-float style="--float-delay: 90ms;">
            <div class="party-rail-head">
                <div>
                    <h3>Years</h3>
                    <p>Drag, scroll, or tap a year to snap it into focus.</p>
                </div>
                <div class="party-rail-actions">
                    <button class="party-rail-action" type="button" id="partyPrevYear" aria-label="Previous year">‹</button>
                    <button class="party-rail-action" type="button" id="partyNextYear" aria-label="Next year">›</button>
                    <div class="party-rail-chip">Year slider</div>
                </div>
            </div>
            <div class="party-rail-track" id="partyYearTrack" aria-label="Party history year slider"></div>
            <div class="party-rail-progress" aria-hidden="true"><span id="partyRailProgress"></span></div>
        </section>

        <section class="party-panel" data-float style="--float-delay: 180ms;">
            <article class="party-feature" id="partyFeature">
                <div class="party-feature-copy">
                    <div class="party-year-badge" id="partyFeatureYear">2020</div>
                    <h3 id="partyFeatureTitle">National leadership transition</h3>
                    <p id="partyFeatureDescription">A year of transition, coordination, and renewed public-facing structure across the party.</p>
                </div>
                <div class="party-feature-events" id="partyFeatureEvents"></div>
            </article>
        </section>
    </div>
</section>
<script>
(() => {
    const root = document.getElementById(<?php echo json_encode($partyAppId); ?>);
    if (!root) {
        return;
    }

    const partyHistory = <?php echo $partyJson; ?>;
    const intro = root.querySelector("#partyIntro");
    const introToggle = root.querySelector("#partyIntroToggle");
    const yearTrack = root.querySelector("#partyYearTrack");
    const prevYear = root.querySelector("#partyPrevYear");
    const nextYear = root.querySelector("#partyNextYear");
    const railProgress = root.querySelector("#partyRailProgress");
    const feature = root.querySelector("#partyFeature");
    const featureYear = root.querySelector("#partyFeatureYear");
    const featureTitle = root.querySelector("#partyFeatureTitle");
    const featureDescription = root.querySelector("#partyFeatureDescription");
    const featureEvents = root.querySelector("#partyFeatureEvents");
    const floatItems = Array.from(root.querySelectorAll("[data-float]"));

    if (!yearTrack || !railProgress || !feature || !featureYear || !featureTitle || !featureDescription || !featureEvents) {
        return;
    }

    if (!window.matchMedia("(prefers-reduced-motion: reduce)").matches && floatItems.length) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("is-visible");
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.18,
            rootMargin: "0px 0px -8% 0px"
        });

        floatItems.forEach((item) => observer.observe(item));
    } else {
        floatItems.forEach((item) => item.classList.add("is-visible"));
    }

    const escapeHtml = (value) => String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");

    const escapeCssUrl = (value) => String(value).replace(/'/g, "\\'");

    const renderYearCard = (item, index) => `
        <button class="party-year-card${index === 0 ? ' active' : ''}" type="button" data-index="${index}" aria-label="${escapeHtml(item.year)} ${escapeHtml(item.title)}">
            <div class="party-year-card-image" style="background-image:url('${escapeCssUrl(item.image)}')"></div>
            <span class="party-year-card-year">${escapeHtml(item.year)}</span>
            <span class="party-year-card-title">${escapeHtml(item.title)} · ${item.events.length} record${item.events.length === 1 ? "" : "s"}</span>
        </button>
    `;

    const renderFeature = (item) => {
        feature.style.setProperty("--party-active-gradient", `linear-gradient(135deg, ${item.accent_primary || "#9f4f47"}, ${item.accent_secondary || "#e4bf6d"})`);
        featureYear.textContent = item.year;
        featureTitle.textContent = `${item.year} history`;
        featureDescription.textContent = `${item.events.length} record${item.events.length === 1 ? "" : "s"} found for ${item.year}.`;
        featureEvents.innerHTML = item.events.length === 0 ? '<div class="party-empty">No records available for this year.</div>' : `
            <div class="party-tree">
                ${item.events.map((event, index) => `
                    <article class="party-tree-item" style="--item-index:${index}">
                        <span class="party-tree-dot" aria-hidden="true"></span>
                        <div class="party-tree-card">
                            <div class="party-tree-image" style="background-image:url('${escapeCssUrl(event.image)}')"></div>
                            <div class="party-tree-body">
                                <strong>${escapeHtml(event.title)}</strong>
                                <p>${escapeHtml(event.description)}</p>
                            </div>
                        </div>
                    </article>
                `).join("")}
            </div>
        `;
    };

    yearTrack.innerHTML = partyHistory.map(renderYearCard).join("");
    const yearButtons = Array.from(yearTrack.querySelectorAll(".party-year-card"));

    let activeIndex = 0;
    let renderTimer = 0;
    let dragging = false;
    let dragStartX = 0;
    let dragStartScroll = 0;
    let dragged = false;

    const setActive = (index, options = {}) => {
        const safeIndex = Math.max(0, Math.min(partyHistory.length - 1, index));
        const { smooth = true, scrollIntoView = true, animate = true, force = false } = options;
        if (!force && safeIndex === activeIndex) {
            return;
        }

        activeIndex = safeIndex;
        yearButtons.forEach((button, buttonIndex) => button.classList.toggle("active", buttonIndex === safeIndex));
        if (renderTimer) {
            window.clearTimeout(renderTimer);
        }

        if (animate) {
            feature.classList.add("is-switching");
        }

        renderTimer = window.setTimeout(() => {
            renderFeature(partyHistory[safeIndex]);
            feature.classList.remove("is-switching");
            renderTimer = 0;
        }, animate ? 130 : 0);

        railProgress.style.width = partyHistory.length === 1 ? "100%" : `${(safeIndex / (partyHistory.length - 1)) * 100}%`;

        if (scrollIntoView && yearButtons[safeIndex]) {
            yearButtons[safeIndex].scrollIntoView({
                behavior: smooth ? "smooth" : "auto",
                inline: "center",
                block: "nearest"
            });
        }
    };

    const selectYear = (index, options = {}) => {
        setActive(index, { ...options, force: true });
    };

    const nearestIndex = () => {
        const trackRect = yearTrack.getBoundingClientRect();
        const center = trackRect.left + trackRect.width / 2;
        let bestIndex = 0;
        let bestDistance = Infinity;

        yearButtons.forEach((button, index) => {
            const rect = button.getBoundingClientRect();
            const distance = Math.abs(center - (rect.left + rect.width / 2));
            if (distance < bestDistance) {
                bestDistance = distance;
                bestIndex = index;
            }
        });

        return bestIndex;
    };

    yearButtons.forEach((button) => {
        button.addEventListener("click", (event) => {
            if (dragged) {
                event.preventDefault();
                return;
            }

            const index = Number.parseInt(button.dataset.index || "0", 10);
            selectYear(index, { smooth: true, scrollIntoView: true, animate: true });
        });
    });

    if (intro && introToggle) {
        introToggle.addEventListener("click", () => {
            const expanded = intro.classList.toggle("is-expanded");
            introToggle.textContent = expanded ? "Show less" : "See more";
            introToggle.setAttribute("aria-expanded", expanded ? "true" : "false");
        });
    }

    yearTrack.addEventListener("pointerdown", (event) => {
        if (event.button !== 0) {
            return;
        }

        dragging = true;
        dragged = false;
        dragStartX = event.clientX;
        dragStartScroll = yearTrack.scrollLeft;
        yearTrack.classList.add("is-dragging");
        yearTrack.setPointerCapture(event.pointerId);
    });

    yearTrack.addEventListener("pointermove", (event) => {
        if (!dragging) {
            return;
        }

        const delta = event.clientX - dragStartX;
        if (Math.abs(delta) > 6) {
            dragged = true;
        }

        yearTrack.scrollLeft = dragStartScroll - delta;
    });

    const stopDragging = (event) => {
        if (!dragging) {
            return;
        }

        const shouldSelectNearest = dragged;
        dragging = false;
        yearTrack.classList.remove("is-dragging");
        if (yearTrack.hasPointerCapture(event.pointerId)) {
            yearTrack.releasePointerCapture(event.pointerId);
        }

        if (shouldSelectNearest) {
            const index = nearestIndex();
            selectYear(index, { smooth: true, scrollIntoView: true, animate: false });
        }

        window.setTimeout(() => {
            dragged = false;
        }, 0);
    };

    yearTrack.addEventListener("pointerup", stopDragging);
    yearTrack.addEventListener("pointercancel", stopDragging);

    if (prevYear && nextYear) {
        prevYear.addEventListener("click", () => {
            selectYear(activeIndex - 1, { smooth: true, scrollIntoView: true, animate: true });
        });

        nextYear.addEventListener("click", () => {
            selectYear(activeIndex + 1, { smooth: true, scrollIntoView: true, animate: true });
        });
    }

    window.addEventListener("resize", () => {
        selectYear(activeIndex, { smooth: false, scrollIntoView: false, animate: false });
    });

    selectYear(0, { smooth: false, scrollIntoView: false, animate: false });
})();
</script>
<?php if (!$partyEmbed): ?>
</body>
</html>
<?php endif; ?>
