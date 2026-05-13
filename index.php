<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function hero_slide_image(string $primary, string $secondary, string $label): string
{
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 760" role="img" aria-label="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '">'
        . '<defs>'
        . '<linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">'
        . '<stop offset="0%" stop-color="' . $primary . '"/>'
        . '<stop offset="100%" stop-color="' . $secondary . '"/>'
        . '</linearGradient>'
        . '</defs>'
        . '<rect width="1440" height="760" fill="url(#bg)"/>'
        . '<circle cx="1180" cy="120" r="180" fill="rgba(255,255,255,0.14)"/>'
        . '<circle cx="190" cy="620" r="220" fill="rgba(255,241,214,0.18)"/>'
        . '<path d="M0 540C169 486 284 441 450 454C638 469 767 610 942 602C1118 594 1245 492 1440 426V760H0Z" fill="rgba(255,255,255,0.14)"/>'
        . '<path d="M0 580C207 520 385 564 533 590C696 618 816 631 991 569C1122 523 1262 448 1440 412V760H0Z" fill="rgba(122,39,35,0.18)"/>'
        . '</svg>';

    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
}

function render_section_separator(string $id, string $title): void
{
    $title = mb_convert_case(trim($title), MB_CASE_TITLE, 'UTF-8');
    ?>
    <section id="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>" class="section-separator" data-separator>
        <div class="section-separator-line" aria-hidden="true"></div>
        <div class="section-separator-copy">
            <h2><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h2>
        </div>
        <div class="section-separator-line" aria-hidden="true"></div>
    </section>
    <?php
}

function hero_title_markup(string $title): string
{
    $words = preg_split('/\s+/', trim($title)) ?: [];
    if ($words === []) {
        return htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    }

    $markup = [];
    foreach ($words as $index => $word) {
        $markup[] = '<span class="hero-title-word" style="--word-index:' . $index . ';">'
            . htmlspecialchars($word, ENT_QUOTES, 'UTF-8')
            . '</span>';
    }

    return implode(' ', $markup);
}

function hero_normalize_media_path(?string $value, string $fallbackLabel, string $primary, string $secondary): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return hero_slide_image($primary, $secondary, $fallbackLabel);
    }

    if (
        preg_match('#^https?://#i', $value) === 1 ||
        str_starts_with($value, '/') ||
        str_starts_with($value, './') ||
        str_starts_with($value, '../') ||
        str_starts_with($value, 'data:image/')
    ) {
        return $value;
    }

    if (preg_match('#^[a-zA-Z0-9][a-zA-Z0-9/_\-. ]+\.(?:png|jpe?g|gif|webp|svg|bmp|avif|mp4|webm|ogg|mov)$#i', $value) === 1) {
        return $value;
    }

    return hero_slide_image($primary, $secondary, $fallbackLabel);
}

function hero_is_video_reference(?string $value): bool
{
    $value = trim((string) $value);
    if ($value === '') {
        return false;
    }

    if (preg_match('#^https?://#i', $value) === 1) {
        return true;
    }

    return preg_match('#^[a-zA-Z0-9./_\- ]+\.(?:mp4|webm|ogg|mov)$#i', $value) === 1;
}

function hero_animation_profile(int $index): array
{
    $profiles = [
        ['text_animation' => 'lift', 'media_animation' => 'zoom'],
        ['text_animation' => 'split', 'media_animation' => 'pan-right'],
        ['text_animation' => 'blur', 'media_animation' => 'tilt'],
        ['text_animation' => 'wave', 'media_animation' => 'pan-left'],
    ];

    return $profiles[$index % count($profiles)];
}

function hero_sanitize_color(?string $value, string $fallback): string
{
    $value = trim((string) $value);
    if (preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value) === 1) {
        return strtoupper($value);
    }

    return $fallback;
}

function hero_sanitize_video_time(mixed $value): ?float
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    if (is_numeric($value)) {
        $time = (float) $value;
        if ($time < 0) {
            return null;
        }

        return round($time, 3);
    }

    if (preg_match('/^(?:(\d{1,2}):)?(\d{1,2}):(\d{2})(?:\.(\d{1,3}))?$/', $value, $matches) === 1) {
        $hours = isset($matches[1]) && $matches[1] !== '' ? (int) $matches[1] : 0;
        $minutes = (int) $matches[2];
        $seconds = (int) $matches[3];
        $fraction = isset($matches[4]) ? (float) ('0.' . str_pad($matches[4], 3, '0')) : 0.0;

        if ($minutes >= 60 || $seconds >= 60) {
            return null;
        }

        return round(($hours * 3600) + ($minutes * 60) + $seconds + $fraction, 3);
    }

    if (preg_match('/^(\d{1,2}):(\d{2})(?:\.(\d{1,3}))?$/', $value, $matches) === 1) {
        $minutes = (int) $matches[1];
        $seconds = (int) $matches[2];
        $fraction = isset($matches[3]) ? (float) ('0.' . str_pad($matches[3], 3, '0')) : 0.0;

        if ($seconds >= 60) {
            return null;
        }

        return round(($minutes * 60) + $seconds + $fraction, 3);
    }

    return null;
}

function load_hero_slides(PDO $pdo): array
{
    $schemaStatement = $pdo->query('SHOW COLUMNS FROM hero_slider');
    $availableColumns = array_map(
        static fn (array $column): string => (string) ($column['Field'] ?? ''),
        $schemaStatement->fetchAll()
    );

    $colorColumns = [
        'eyebrow_color',
        'title_color',
        'description_color',
    ];
    $videoColumns = [
        'video_start_time',
        'video_end_time',
    ];

    $selectColumns = ['eyebrow', 'title', 'description', 'media_type', 'media_path', 'display_order'];
    foreach (array_merge($colorColumns, $videoColumns) as $column) {
        if (in_array($column, $availableColumns, true)) {
            $selectColumns[] = $column;
        }
    }

    $statement = $pdo->query(
        'SELECT ' . implode(', ', $selectColumns) . '
         FROM hero_slider
         WHERE is_active = 1
         ORDER BY display_order, id'
    );

    $slides = [];
    foreach ($statement->fetchAll() as $index => $row) {
        $title = trim((string) ($row['title'] ?? ''));
        if ($title === '') {
            continue;
        }

        $primary = ['#c15d4f', '#a94e43', '#b76854', '#9f4f47'][$index % 4];
        $secondary = ['#efcd7b', '#e7bb66', '#f0d79a', '#efcf93'][$index % 4];
        $mediaType = strtolower(trim((string) ($row['media_type'] ?? 'image')));
        $mediaType = $mediaType === 'video' ? 'video' : 'image';
        $mediaPath = trim((string) ($row['media_path'] ?? ''));
        $animation = hero_animation_profile($index);
        $videoStartTime = hero_sanitize_video_time($row['video_start_time'] ?? null);
        $videoEndTime = hero_sanitize_video_time($row['video_end_time'] ?? null);

        if ($videoStartTime !== null && $videoEndTime !== null && $videoEndTime <= $videoStartTime) {
            $videoEndTime = null;
        }

        if ($mediaType === 'video' && !hero_is_video_reference($mediaPath)) {
            $mediaType = 'image';
        }

        $slides[] = [
            'eyebrow' => trim((string) ($row['eyebrow'] ?? '')),
            'title' => $title,
            'text' => trim((string) ($row['description'] ?? 'Content will be updated soon.')) ?: 'Content will be updated soon.',
            'media_type' => $mediaType,
            'media' => hero_normalize_media_path($mediaPath, $title, $primary, $secondary),
            'poster' => hero_slide_image($primary, $secondary, $title),
            'text_animation' => $animation['text_animation'],
            'media_animation' => $animation['media_animation'],
            'nav_label' => $title,
            'eyebrow_color' => hero_sanitize_color($row['eyebrow_color'] ?? null, '#FFF7EA'),
            'title_color' => hero_sanitize_color($row['title_color'] ?? null, '#FFF7EA'),
            'description_color' => hero_sanitize_color($row['description_color'] ?? null, 'rgba(255, 242, 221, 0.82)'),
            'video_start_time' => $mediaType === 'video' ? $videoStartTime : null,
            'video_end_time' => $mediaType === 'video' ? $videoEndTime : null,
        ];
    }

    return $slides;
}

$heroSlides = [
    [
        'eyebrow' => 'National Vision',
        'title' => 'Build a stronger digital presence with connected district stories.',
        'text' => 'A modern single-page experience for projects, leadership, and future public-facing components.',
        'media_type' => 'image',
        'media' => hero_slide_image('#c15d4f', '#efcd7b', 'National vision slide'),
        'poster' => hero_slide_image('#c15d4f', '#efcd7b', 'National vision slide'),
        'text_animation' => hero_animation_profile(0)['text_animation'],
        'media_animation' => hero_animation_profile(0)['media_animation'],
        'nav_label' => 'National Vision',
        'eyebrow_color' => '#FFF7EA',
        'title_color' => '#FFF7EA',
        'description_color' => 'rgba(255, 242, 221, 0.82)',
        'video_start_time' => null,
        'video_end_time' => null,
    ],
    [
        'eyebrow' => 'Project Focus',
        'title' => 'Showcase regional opportunities with smoother discovery and smarter presentation.',
        'text' => 'Maps, district projects, and leadership content now live inside one scalable page structure.',
        'media_type' => 'image',
        'media' => hero_slide_image('#a94e43', '#e7bb66', 'Project focus slide'),
        'poster' => hero_slide_image('#a94e43', '#e7bb66', 'Project focus slide'),
        'text_animation' => hero_animation_profile(1)['text_animation'],
        'media_animation' => hero_animation_profile(1)['media_animation'],
        'nav_label' => 'Project Focus',
        'eyebrow_color' => '#FFF7EA',
        'title_color' => '#FFF7EA',
        'description_color' => 'rgba(255, 242, 221, 0.82)',
        'video_start_time' => null,
        'video_end_time' => null,
    ],
    [
        'eyebrow' => 'Leadership Layer',
        'title' => 'Bring together strategy, place, and identity in one polished interface.',
        'text' => 'The layout is ready for future sections while staying responsive across mobile, tablet, and desktop.',
        'media_type' => 'image',
        'media' => hero_slide_image('#b76854', '#f0d79a', 'Leadership layer slide'),
        'poster' => hero_slide_image('#b76854', '#f0d79a', 'Leadership layer slide'),
        'text_animation' => hero_animation_profile(2)['text_animation'],
        'media_animation' => hero_animation_profile(2)['media_animation'],
        'nav_label' => 'Leadership Layer',
        'eyebrow_color' => '#FFF7EA',
        'title_color' => '#FFF7EA',
        'description_color' => 'rgba(255, 242, 221, 0.82)',
        'video_start_time' => null,
        'video_end_time' => null,
    ],
];

try {
    $dbHeroSlides = load_hero_slides(getDbConnection());
    if ($dbHeroSlides !== []) {
        $heroSlides = $dbHeroSlides;
    }
} catch (Throwable $exception) {
    // Keep the hero usable with fallback slides when the table is missing.
}

$sectionNavItems = [
    ['id' => 'vision', 'label' => 'Vision'],
    ['id' => 'our-party', 'label' => 'Our Party'],
    ['id' => 'leadership', 'label' => 'Leadership'],
    ['id' => 'districts', 'label' => 'Projects'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SLPP Srilanka</title>
    <link rel="icon" type="image/x-icon" href="images/slpp.ico">
    <style>
        :root {
            --hero-red: #b85a4d;
            --hero-red-deep: #8f463d;
            --hero-gold: #e4bf6d;
            --hero-gold-soft: #f4deb0;
            --hero-ink: #fff7ea;
            --hero-muted: rgba(255, 242, 221, 0.82);
            --hero-glass: rgba(255, 248, 238, 0.14);
            --hero-glass-strong: rgba(255, 248, 238, 0.2);
            --hero-border: rgba(255, 237, 205, 0.2);
        }

        html {
            scroll-behavior: smooth;
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background:
                radial-gradient(circle at top left, rgba(201, 104, 86, 0.24), transparent 24%),
                radial-gradient(circle at top right, rgba(228, 191, 109, 0.22), transparent 28%),
                linear-gradient(180deg, #9f4f47 0%, #b76052 18%, #c77158 34%, #da975f 54%, #e6b765 72%, #efcf93 88%, #f3dcc2 100%);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .top-hero {
            position: relative;
            overflow: hidden;
            padding: 0 0 18px;
            background:
                radial-gradient(circle at left top, rgba(194, 94, 82, 0.28), transparent 26%),
                radial-gradient(circle at right top, rgba(230, 192, 110, 0.36), transparent 28%),
                linear-gradient(145deg, #8b433d 0%, #b76052 34%, #e2b961 100%);
        }

        .top-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255,255,255,0.05), rgba(255,255,255,0));
            pointer-events: none;
        }

        .hero-shell {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            padding: 14px 14px 0;
            border-radius: 0;
            background:
                radial-gradient(circle at top left, rgba(255, 216, 144, 0.18), transparent 26%),
                radial-gradient(circle at bottom right, rgba(124, 41, 36, 0.16), transparent 28%),
                linear-gradient(145deg, rgba(139, 67, 61, 0.82), rgba(183, 96, 82, 0.78), rgba(226, 185, 97, 0.7));
        }

        .hero-nav {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
            padding: 14px 22px;
            margin-bottom: 18px;
            border-radius: 24px;
            background: var(--hero-glass);
            border: 1px solid var(--hero-border);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            overflow: hidden;
            isolation: isolate;
            opacity: 0;
            transform: translate3d(0, -22px, 0) scale(0.985);
            filter: blur(14px);
            will-change: opacity, transform, filter;
            transition:
                opacity 760ms cubic-bezier(.18, .84, .22, 1),
                transform 760ms cubic-bezier(.18, .84, .22, 1),
                filter 760ms cubic-bezier(.18, .84, .22, 1),
                box-shadow 320ms cubic-bezier(.2, .8, .2, 1);
        }

        @media (max-width: 768px) {
            .hero-nav {
                backdrop-filter: none;
                -webkit-backdrop-filter: none;
            }
        }

        .hero-shell.is-nav-ready .hero-nav {
            opacity: 1;
            transform: translate3d(0, 0, 0) scale(1);
            filter: blur(0);
        }

        .hero-nav::before {
            display: none;
        }

        .hero-nav::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0));
            pointer-events: none;
            z-index: 0;
        }

        .hero-nav > * {
            position: relative;
            z-index: 1;
        }

        .hero-brand {
            perspective: 1200px;
            display: inline-flex;
            align-items: center;
            gap: 0;
            flex: 0 0 auto;
            min-width: 0;
            text-decoration: none;
            padding: 0;
            outline: none;
            opacity: 0;
            transform: translate3d(-18px, 0, 0) scale(0.94);
            filter: blur(10px);
            will-change: opacity, transform, filter;
            transition:
                opacity 620ms cubic-bezier(.18, .84, .22, 1) 120ms,
                transform 620ms cubic-bezier(.18, .84, .22, 1) 120ms,
                filter 620ms cubic-bezier(.18, .84, .22, 1) 120ms;
        }

        .hero-shell.is-nav-ready .hero-brand {
            opacity: 1;
            transform: translate3d(0, 0, 0) scale(1);
            filter: blur(0);
        }

        .hero-brand-logo {
            display: block;
            width: 168px;
            max-width: min(28vw, 168px);
            height: auto;
            flex-shrink: 0;
            object-fit: contain;
            position: relative;
            transform-origin: 50% 50%;
            filter: drop-shadow(0 10px 22px rgba(61, 18, 17, 0.18));
            transform: rotateX(0deg) rotateY(0deg) scale(1);
            opacity: 1;
            will-change: transform, opacity;
            transition: opacity 220ms cubic-bezier(.2, .8, .2, 1);
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
        }

        @media (max-width: 768px) {
            .hero-brand-logo {
                filter: drop-shadow(0 6px 14px rgba(61, 18, 17, 0.14));
            }
        }

        .hero-brand:hover .hero-brand-logo,
        .hero-brand:focus-visible .hero-brand-logo {
            opacity: 1;
            transform: scale(1.05);
            transition: transform 220ms cubic-bezier(.2, .8, .2, 1);
        }

        .hero-brand:hover,
        .hero-brand:focus-visible {
            outline: none;
        }

        .hero-links {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
            align-items: center;
            margin: 0 auto;
        }

        .hero-menu-toggle {
            display: none;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            padding: 0;
            border: 1px solid rgba(255, 240, 214, 0.2);
            border-radius: 16px;
            background: rgba(255,255,255,0.1);
            color: var(--hero-ink);
            cursor: pointer;
            backdrop-filter: blur(18px);
            transition:
                transform 220ms cubic-bezier(.2, .8, .2, 1),
                background 220ms cubic-bezier(.2, .8, .2, 1),
                border-color 220ms cubic-bezier(.2, .8, .2, 1),
                box-shadow 220ms cubic-bezier(.2, .8, .2, 1);
        }

        .hero-menu-toggle:hover,
        .hero-menu-toggle:focus-visible {
            background: rgba(255,255,255,0.14);
            border-color: rgba(255, 240, 214, 0.3);
            box-shadow: 0 16px 28px rgba(61, 18, 17, 0.16);
        }

        .hero-menu-toggle:focus-visible {
            outline: 2px solid rgba(255, 240, 214, 0.36);
            outline-offset: 2px;
        }

        .hero-menu-toggle-icon {
            position: relative;
            display: block;
            width: 20px;
            height: 14px;
        }

        .hero-menu-toggle-line {
            position: absolute;
            left: 0;
            width: 100%;
            height: 2px;
            border-radius: 999px;
            background: currentColor;
            transition:
                transform 260ms cubic-bezier(.22, .84, .24, 1),
                opacity 180ms ease,
                top 260ms cubic-bezier(.22, .84, .24, 1);
        }

        .hero-menu-toggle-line:nth-child(1) {
            top: 0;
        }

        .hero-menu-toggle-line:nth-child(2) {
            top: 6px;
        }

        .hero-menu-toggle-line:nth-child(3) {
            top: 12px;
        }

        .hero-nav-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 18px;
            flex: 1 1 auto;
            min-width: 0;
            margin-left: clamp(18px, 3vw, 42px);
        }

        .hero-nav.is-menu-open .hero-menu-toggle {
            background: rgba(255,255,255,0.16);
            border-color: rgba(255, 240, 214, 0.34);
            transform: translateY(-1px);
        }

        .hero-nav.is-menu-open .hero-menu-toggle-line:nth-child(1) {
            top: 6px;
            transform: rotate(45deg);
        }

        .hero-nav.is-menu-open .hero-menu-toggle-line:nth-child(2) {
            opacity: 0;
            transform: scaleX(0.4);
        }

        .hero-nav.is-menu-open .hero-menu-toggle-line:nth-child(3) {
            top: 6px;
            transform: rotate(-45deg);
        }

        .hero-links a {
            opacity: 0;
            transform: translate3d(0, -16px, 0) scale(0.92);
            filter: blur(10px);
            will-change: opacity, transform, filter;
            transition:
                opacity 580ms cubic-bezier(.18, .84, .22, 1) calc(180ms + (var(--nav-index, 0) * 70ms)),
                transform 580ms cubic-bezier(.18, .84, .22, 1) calc(180ms + (var(--nav-index, 0) * 70ms)),
                filter 580ms cubic-bezier(.18, .84, .22, 1) calc(180ms + (var(--nav-index, 0) * 70ms)),
                background 260ms cubic-bezier(.2, .8, .2, 1),
                border-color 260ms cubic-bezier(.2, .8, .2, 1);
        }

        .hero-shell.is-nav-ready .hero-links a {
            opacity: 1;
            transform: translate3d(0, 0, 0) scale(1);
            filter: blur(0);
        }

        .hero-links a,
        .hero-action {
            color: var(--hero-ink);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 18px;
            border-radius: 999px;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255, 240, 214, 0.16);
            font-size: 0.9rem;
            font-weight: 500;
            letter-spacing: 0.01em;
            white-space: nowrap;
        }

        .hero-action {
            flex: 0 0 auto;
            margin-left: auto;
            background: linear-gradient(135deg, rgba(244, 214, 143, 0.34), rgba(255,255,255,0.14));
            opacity: 0;
            transform: translate3d(18px, 0, 0) scale(0.92);
            filter: blur(10px);
            box-shadow: 0 18px 32px rgba(61, 18, 17, 0.16);
            will-change: opacity, transform, filter;
            transition:
                opacity 640ms cubic-bezier(.18, .84, .22, 1) 420ms,
                transform 640ms cubic-bezier(.18, .84, .22, 1) 420ms,
                filter 640ms cubic-bezier(.18, .84, .22, 1) 420ms,
                box-shadow 260ms cubic-bezier(.2, .8, .2, 1),
                border-color 260ms cubic-bezier(.2, .8, .2, 1);
        }

        .hero-shell.is-nav-ready .hero-action {
            opacity: 1;
            transform: translate3d(0, 0, 0) scale(1);
            filter: blur(0);
        }

        .hero-links a:hover,
        .hero-links a:focus-visible,
        .hero-action:hover,
        .hero-action:focus-visible {
            transform: translate3d(0, -2px, 0) scale(1.02);
            border-color: rgba(255, 240, 214, 0.3);
            background: rgba(255,255,255,0.12);
        }

        .hero-action:hover,
        .hero-action:focus-visible {
            box-shadow: 0 22px 38px rgba(61, 18, 17, 0.18);
        }

        .hero-carousel {
            position: relative;
            width: 100%;
            border-radius: 34px;
            overflow: hidden;
            border: 1px solid rgba(255, 236, 205, 0.18);
            background:
                radial-gradient(circle at top left, rgba(255, 220, 162, 0.12), transparent 26%),
                linear-gradient(145deg, rgba(125, 53, 46, 0.42), rgba(198, 111, 83, 0.22), rgba(233, 192, 110, 0.18));
            box-shadow: 0 28px 70px rgba(88, 33, 28, 0.28);
            isolation: isolate;
        }

        .hero-carousel::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: 1;
            pointer-events: none;
            background:
                radial-gradient(circle at 18% 12%, rgba(255,255,255,0.12), transparent 18%),
                linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0));
            mix-blend-mode: screen;
        }

        .hero-track {
            display: grid;
            grid-auto-flow: column;
            grid-auto-columns: 100%;
            overflow-x: auto;
            scrollbar-width: none;
            contain: layout style paint;
        }

        .hero-track::-webkit-scrollbar {
            display: none;
        }

        .hero-slide {
            position: relative;
            min-height: clamp(420px, 62vw, 620px);
            display: grid;
            align-items: end;
            padding: clamp(22px, 4vw, 42px);
            isolation: isolate;
            will-change: transform;
            transform: translateZ(0);
        }

        .hero-media-wrap {
            position: absolute;
            inset: 0;
            overflow: hidden;
            z-index: 0;
            background:
                radial-gradient(circle at top right, rgba(255,255,255,0.08), transparent 24%),
                linear-gradient(145deg, rgba(64, 24, 21, 0.16), rgba(64, 24, 21, 0.04), rgba(233, 192, 110, 0.03));
            transform: translateZ(0);
            will-change: transform;
        }

        .hero-media-wrap::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: 1;
            pointer-events: none;
            background:
                linear-gradient(120deg, rgba(255,255,255,0) 18%, rgba(255,255,255,0.22) 34%, rgba(255,255,255,0.82) 40%, rgba(255,255,255,0.22) 46%, rgba(255,255,255,0) 58%);
            opacity: 0;
            transform: translateX(-34%) skewX(-14deg);
            will-change: transform, opacity;
        }

        @media (max-width: 768px) {
            .hero-media-wrap::before {
                display: none;
            }
        }

        .hero-media-wrap::after {
            content: "";
            position: absolute;
            inset: 14px;
            z-index: 1;
            pointer-events: none;
            border-radius: 26px;
            border: 1px solid rgba(255,255,255,0.08);
            opacity: 0;
            transition: opacity 700ms cubic-bezier(.2, .8, .2, 1);
        }

        .hero-media {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center center;
            display: block;
            transform-origin: center;
            opacity: 0.82;
            filter: none;
            clip-path: inset(0 0 0 0 round 0);
            transition:
                transform 1100ms cubic-bezier(.16, .84, .22, 1),
                opacity 820ms cubic-bezier(.2, .8, .2, 1),
                filter 820ms cubic-bezier(.2, .8, .2, 1),
                clip-path 980ms cubic-bezier(.16, .84, .22, 1);
            will-change: transform, opacity, filter;
            image-rendering: auto;
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
            transform: translateZ(0);
        }

        img.hero-media {
            image-rendering: -webkit-optimize-contrast;
        }

        video.hero-media {
            background: transparent;
        }

        .hero-slide.is-active .hero-media {
            opacity: 1;
            filter: none;
        }

        .hero-slide.is-active .hero-media-wrap::after {
            opacity: 1;
        }

        .hero-slide.is-leaving .hero-media {
            opacity: 0.74;
        }

        .hero-slide::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(90deg, rgba(45, 14, 13, 0.52), rgba(45, 14, 13, 0.12) 48%, rgba(45, 14, 13, 0.04)),
                linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0));
        }

        .hero-slide[data-media-anim="zoom"] .hero-media {
            transform: scale(1.055) translateZ(0);
            clip-path: inset(1.5% 1% 1.5% 1% round 20px);
        }

        .hero-slide.is-active[data-media-anim="zoom"] .hero-media {
            transform: scale(1) translateZ(0);
            clip-path: inset(0 0 0 0 round 0);
        }

        .hero-slide[data-media-anim="pan-left"] .hero-media {
            transform: scale(1.06) translateX(3%) translateZ(0);
            clip-path: inset(0.5% 0.5% 0.5% 2.5% round 20px);
        }

        .hero-slide.is-active[data-media-anim="pan-left"] .hero-media {
            transform: scale(1.01) translateX(0) translateZ(0);
            clip-path: inset(0 0 0 0 round 0);
        }

        .hero-slide[data-media-anim="pan-right"] .hero-media {
            transform: scale(1.06) translateX(-3%) translateZ(0);
            clip-path: inset(0.5% 2.5% 0.5% 0.5% round 20px);
        }

        .hero-slide.is-active[data-media-anim="pan-right"] .hero-media {
            transform: scale(1.01) translateX(0) translateZ(0);
            clip-path: inset(0 0 0 0 round 0);
        }

        .hero-slide[data-media-anim="tilt"] .hero-media {
            transform: scale(1.05) rotate(-1deg) translateY(-0.5%) translateZ(0);
            clip-path: inset(1% 1% 1% 1% round 20px);
        }

        .hero-slide.is-active[data-media-anim="tilt"] .hero-media {
            transform: scale(1.005) rotate(0deg) translateZ(0);
            clip-path: inset(0 0 0 0 round 0);
        }

        .hero-copy {
            position: relative;
            z-index: 1;
            width: min(100%, 760px);
            max-width: 760px;
            padding: clamp(18px, 2.4vw, 28px) clamp(18px, 2.8vw, 30px);
            border-radius: 26px;
            background:
                linear-gradient(180deg, rgba(15, 18, 30, 0.05), rgba(15, 18, 30, 0.01)),
                linear-gradient(135deg, rgba(255, 255, 255, 0.035), rgba(255, 255, 255, 0));
            border: 0.5px solid rgba(255, 244, 224, 0.06);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.04),
                0 12px 26px rgba(12, 8, 18, 0.08);
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(3px);
            color: var(--hero-ink);
            opacity: 0.2;
            transform: translate3d(0, 30px, 0) scale(0.985);
            filter: blur(16px);
            transition:
                opacity 760ms cubic-bezier(.18, .84, .22, 1),
                transform 760ms cubic-bezier(.18, .84, .22, 1),
                filter 760ms cubic-bezier(.18, .84, .22, 1);
            will-change: transform, opacity, filter;
            overflow: hidden;
            isolation: isolate;
        }

        .hero-copy::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at top left, rgba(255,255,255,0.025), transparent 28%),
                linear-gradient(110deg, rgba(255,255,255,0.02), rgba(255,255,255,0) 36%);
            pointer-events: none;
            z-index: 0;
        }

        .hero-copy > * {
            position: relative;
            z-index: 1;
        }

        .hero-carousel.is-ready .hero-slide.is-active .hero-copy {
            opacity: 1;
            transform: translate3d(0, 0, 0);
            filter: blur(0);
        }

        .hero-copy > span {
            display: inline-flex;
            margin-bottom: 16px;
            max-width: 100%;
            padding: 9px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255, 237, 205, 0.16);
            font-size: 0.78rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            line-height: 1.4;
            text-wrap: pretty;
            opacity: 0;
            transform: translate3d(0, 18px, 0) scale(0.94);
            filter: blur(10px);
            transition:
                opacity 620ms cubic-bezier(.18, .84, .22, 1) 80ms,
                transform 620ms cubic-bezier(.18, .84, .22, 1) 80ms,
                filter 620ms cubic-bezier(.18, .84, .22, 1) 80ms;
            color: var(--slide-eyebrow-color, var(--hero-ink));
        }

        .hero-copy h1 {
            margin: 0;
            max-width: 12ch;
            font-size: clamp(2.1rem, 5vw, 4.6rem);
            line-height: 0.98;
            letter-spacing: -0.05em;
            color: var(--slide-title-color, var(--hero-ink));
            text-wrap: balance;
            overflow-wrap: anywhere;
            text-shadow: 0 6px 18px rgba(9, 8, 16, 0.18);
        }

        .hero-title-word {
            display: inline-block;
            opacity: 0;
            transform: translate3d(0, 42px, 0) rotate(4deg) scale(0.92);
            filter: blur(14px);
            transition:
                opacity 680ms cubic-bezier(.16, .84, .22, 1) calc(150ms + (var(--word-index) * 46ms)),
                transform 680ms cubic-bezier(.16, .84, .22, 1) calc(150ms + (var(--word-index) * 46ms)),
                filter 680ms cubic-bezier(.16, .84, .22, 1) calc(150ms + (var(--word-index) * 46ms));
            will-change: transform, opacity, filter;
        }

        .hero-copy p {
            margin: 18px 0 0;
            max-width: min(100%, 60ch);
            color: var(--slide-description-color, var(--hero-muted));
            line-height: 1.74;
            font-size: clamp(0.98rem, 1.7vw, 1.08rem);
            text-wrap: pretty;
            overflow-wrap: anywhere;
            hyphens: auto;
            text-shadow: 0 3px 12px rgba(9, 8, 16, 0.14);
            opacity: 0;
            transform: translate3d(0, 24px, 0);
            filter: blur(12px);
            transition:
                opacity 720ms cubic-bezier(.18, .84, .22, 1) 360ms,
                transform 720ms cubic-bezier(.18, .84, .22, 1) 360ms,
                filter 720ms cubic-bezier(.18, .84, .22, 1) 360ms;
        }

        .hero-carousel.is-ready .hero-slide.is-active .hero-copy > span,
        .hero-carousel.is-ready .hero-slide.is-active .hero-copy p,
        .hero-carousel.is-ready .hero-slide.is-active .hero-title-word {
            opacity: 1;
            transform: translate3d(0, 0, 0) rotate(0deg) scale(1);
            filter: blur(0);
        }

        .hero-slide[data-text-anim="lift"] .hero-copy {
            transform: translate3d(0, 34px, 0);
        }

        .hero-slide.is-active[data-text-anim="lift"] .hero-copy {
            transform: translate3d(0, 0, 0);
        }

        .hero-slide[data-text-anim="split"] .hero-copy {
            transform: translate3d(-26px, 0, 0) scale(0.98);
        }

        .hero-carousel.is-ready .hero-slide.is-active[data-text-anim="split"] .hero-copy {
            transform: translate3d(0, 0, 0) scale(1);
        }

        .hero-slide[data-text-anim="split"] .hero-title-word {
            transform: translate3d(calc(-18px + (var(--word-index) * 3px)), 28px, 0) scale(0.94);
        }

        .hero-slide[data-text-anim="blur"] .hero-copy {
            transform: translate3d(0, 0, 0) scale(1.04);
            filter: blur(18px);
        }

        .hero-carousel.is-ready .hero-slide.is-active[data-text-anim="blur"] .hero-copy {
            transform: translate3d(0, 0, 0) scale(1);
            filter: blur(0);
        }

        .hero-slide[data-text-anim="blur"] .hero-title-word {
            transform: translate3d(0, 18px, 0) scale(1.08);
            filter: blur(20px);
        }

        .hero-slide[data-text-anim="wave"] .hero-copy {
            transform: translate3d(0, 18px, 0) rotate(-1deg);
        }

        .hero-carousel.is-ready .hero-slide.is-active[data-text-anim="wave"] .hero-copy {
            transform: translate3d(0, 0, 0) rotate(0deg);
        }

        .hero-slide[data-text-anim="wave"] .hero-title-word {
            transform: translate3d(0, calc(30px + (var(--word-index) * 2px)), 0) rotate(calc(-5deg + (var(--word-index) * 1deg))) scale(0.95);
        }

        .hero-controls {
            position: absolute;
            right: 20px;
            bottom: 20px;
            z-index: 2;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .hero-button {
            width: 48px;
            height: 48px;
            border: 1px solid rgba(255, 235, 201, 0.24);
            border-radius: 999px;
            background: rgba(255,255,255,0.12);
            color: var(--hero-ink);
            font-size: 1rem;
            cursor: pointer;
            backdrop-filter: blur(18px);
        }

        .hero-dots {
            position: absolute;
            left: 50%;
            bottom: 24px;
            z-index: 2;
            display: flex;
            gap: 8px;
            transform: translateX(-50%);
        }

        .hero-dot {
            width: 10px;
            height: 10px;
            border: 0;
            border-radius: 999px;
            background: rgba(255, 241, 219, 0.34);
            padding: 0;
            cursor: pointer;
        }

        .hero-dot.active {
            width: 30px;
            background: linear-gradient(90deg, #f4cf85, #fff2d1);
        }

        .smart-top-button {
            position: fixed;
            right: max(18px, env(safe-area-inset-right, 0px) + 18px);
            bottom: max(18px, env(safe-area-inset-bottom, 0px) + 18px);
            z-index: 12;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-width: 56px;
            height: 56px;
            padding: 0 18px;
            border: 1px solid rgba(255, 237, 205, 0.18);
            border-radius: 999px;
            background:
                linear-gradient(145deg, rgba(146, 56, 47, 0.88), rgba(95, 33, 29, 0.94)),
                rgba(255,255,255,0.06);
            color: var(--hero-ink);
            box-shadow:
                0 18px 38px rgba(44, 13, 12, 0.24),
                inset 0 1px 0 rgba(255,255,255,0.12);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transform: translate3d(0, 18px, 0) scale(0.92);
            transition:
                opacity 240ms cubic-bezier(.2, .8, .2, 1),
                transform 240ms cubic-bezier(.2, .8, .2, 1),
                visibility 240ms ease,
                box-shadow 220ms cubic-bezier(.2, .8, .2, 1),
                border-color 220ms cubic-bezier(.2, .8, .2, 1);
        }

        .smart-top-button.is-visible {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: translate3d(0, 0, 0) scale(1);
        }

        .smart-top-button:hover,
        .smart-top-button:focus-visible {
            border-color: rgba(255, 237, 205, 0.32);
            box-shadow:
                0 22px 42px rgba(44, 13, 12, 0.28),
                inset 0 1px 0 rgba(255,255,255,0.14);
        }

        .smart-top-button:focus-visible {
            outline: 2px solid rgba(255, 237, 205, 0.34);
            outline-offset: 3px;
        }

        .smart-top-button-icon {
            font-size: 1rem;
            line-height: 1;
        }

        .smart-top-button-label {
            font-size: 0.84rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }

        .section-separator {
            width: 100%;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
            align-items: center;
            gap: 24px;
            margin: 0 auto;
            padding: 30px 18px 24px;
            scroll-margin-top: 24px;
        }

        .page-main {
            position: relative;
            margin-top: -10px;
            padding-top: 18px;
            padding-bottom: 32px;
            background:
                radial-gradient(circle at top left, rgba(194, 94, 82, 0.24), transparent 24%),
                radial-gradient(circle at top right, rgba(230, 192, 110, 0.26), transparent 28%),
                radial-gradient(circle at bottom right, rgba(124, 41, 36, 0.18), transparent 26%),
                linear-gradient(180deg, rgba(143, 67, 61, 0.98) 0%, rgba(183, 96, 82, 0.94) 20%, rgba(206, 120, 87, 0.9) 42%, rgba(226, 185, 97, 0.84) 70%, rgba(243, 220, 194, 0.78) 100%);
        }

        .page-main::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0)),
                linear-gradient(90deg, rgba(255, 248, 238, 0.05), transparent 18%, transparent 82%, rgba(255, 248, 238, 0.05));
            pointer-events: none;
        }

        .site-footer {
            position: relative;
            padding: 18px 0 0;
            background:
                radial-gradient(circle at top left, rgba(238, 192, 111, 0.12), transparent 24%),
                radial-gradient(circle at bottom right, rgba(133, 46, 40, 0.18), transparent 28%),
                linear-gradient(180deg, rgba(120, 51, 45, 0.96), rgba(80, 28, 25, 0.98));
        }

        .site-footer::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255,255,255,0.05), rgba(255,255,255,0));
            pointer-events: none;
        }

        .footer-shell {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: none;
            margin: 0 auto;
            padding: clamp(24px, 3.4vw, 38px) clamp(18px, 3vw, 38px) clamp(20px, 2.6vw, 26px);
            border-radius: 34px 34px 0 0;
            border: 1px solid rgba(255, 236, 205, 0.12);
            border-bottom: 0;
            background:
                linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.015)),
                linear-gradient(135deg, rgba(129, 48, 42, 0.42), rgba(66, 23, 21, 0.58));
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.08),
                0 -1px 0 rgba(255,255,255,0.04),
                0 24px 56px rgba(39, 11, 10, 0.18);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            overflow: hidden;
        }

        .footer-shell::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at top left, rgba(255,255,255,0.1), transparent 28%),
                linear-gradient(110deg, rgba(255,255,255,0.06), rgba(255,255,255,0) 48%);
            pointer-events: none;
        }

        .footer-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) repeat(3, minmax(0, 1fr));
            gap: 20px;
            align-items: start;
        }

        .footer-brand,
        .footer-card {
            min-width: 0;
            padding: 22px 22px 20px;
            border-radius: 24px;
            background:
                linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.02)),
                rgba(76, 25, 24, 0.16);
            border: 1px solid rgba(255, 239, 214, 0.08);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.05),
                0 10px 24px rgba(28, 8, 8, 0.08);
        }

        .footer-brand {
            display: grid;
            gap: 14px;
        }

        .footer-logo-row {
            display: inline-flex;
            align-items: center;
            gap: 14px;
        }

        .footer-logo {
            width: 62px;
            height: 62px;
            object-fit: contain;
            flex-shrink: 0;
            filter: drop-shadow(0 8px 18px rgba(29, 8, 8, 0.18));
        }

        .footer-brand h2,
        .footer-card h3 {
            margin: 0;
            color: var(--hero-ink);
        }

        .footer-brand h2 {
            font-size: clamp(1.1rem, 1.8vw, 1.5rem);
            line-height: 1.08;
            letter-spacing: -0.03em;
            font-family: "Avenir Next", "Segoe UI", "Helvetica Neue", Arial, sans-serif;
        }

        .footer-brand p,
        .footer-card p,
        .footer-contact-link,
        .footer-bottom,
        .footer-link-list a {
            color: rgba(255, 243, 223, 0.8);
        }

        .footer-brand p,
        .footer-card p {
            margin: 0;
            line-height: 1.7;
            font-size: 0.95rem;
        }

        .footer-card {
            display: grid;
            gap: 14px;
        }

        .footer-card h3 {
            font-size: 0.84rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            font-weight: 700;
        }

        .footer-link-list,
        .footer-contact-list,
        .footer-social-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 10px;
        }

        .footer-link-list a,
        .footer-contact-link {
            text-decoration: none;
            transition: color 220ms cubic-bezier(.2, .8, .2, 1), transform 220ms cubic-bezier(.2, .8, .2, 1);
        }

        .footer-link-list a:hover,
        .footer-link-list a:focus-visible,
        .footer-contact-link:hover,
        .footer-contact-link:focus-visible {
            color: var(--hero-ink);
            transform: translateX(2px);
        }

        .footer-contact-list li {
            display: grid;
            gap: 4px;
        }

        .footer-contact-label {
            font-size: 0.76rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(255, 239, 214, 0.58);
        }

        .footer-social-list {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .footer-social-link {
            display: grid;
            grid-template-columns: 20px minmax(0, 1fr);
            align-items: center;
            column-gap: 12px;
            min-height: 48px;
            padding: 0 14px;
            text-decoration: none;
            border-radius: 16px;
            background:
                linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.03)),
                rgba(255,255,255,0.02);
            border: 1px solid rgba(255, 238, 206, 0.1);
            color: var(--hero-ink);
            transition:
                transform 220ms cubic-bezier(.2, .8, .2, 1),
                border-color 220ms cubic-bezier(.2, .8, .2, 1),
                background 220ms cubic-bezier(.2, .8, .2, 1),
                box-shadow 220ms cubic-bezier(.2, .8, .2, 1);
        }

        .footer-social-link:hover,
        .footer-social-link:focus-visible {
            transform: translateY(-2px);
            border-color: rgba(255, 238, 206, 0.18);
            background:
                linear-gradient(180deg, rgba(255,255,255,0.1), rgba(255,255,255,0.04)),
                rgba(255,255,255,0.04);
            box-shadow: 0 16px 28px rgba(33, 10, 9, 0.18);
        }

        .footer-social-icon {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            justify-self: center;
        }

        .footer-social-link span {
            text-align: left;
        }

        .footer-bottom {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px solid rgba(255, 236, 205, 0.08);
            font-size: 0.88rem;
        }

        .footer-bottom-links {
            display: inline-flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 14px;
        }

        .footer-bottom-links a {
            color: rgba(255, 243, 223, 0.72);
            text-decoration: none;
        }

        .footer-bottom-links a:hover,
        .footer-bottom-links a:focus-visible {
            color: var(--hero-ink);
        }

        .page-section-chunk {
            content-visibility: auto;
            contain: layout paint style;
            contain-intrinsic-size: 960px;
        }

        body.is-scrolling .hero-nav::before,
        body.is-scrolling .section-separator-copy::after,
        body.is-scrolling .hero-brand-logo,
        body.is-scrolling .map-helper-icon,
        body.is-scrolling .project-map-pin::before {
            animation-play-state: paused !important;
        }

        body.is-scrolling .hero-nav,
        body.is-scrolling .section-tree-nav-label,
        body.is-scrolling .section-separator-copy,
        body.is-scrolling .vision-shell,
        body.is-scrolling .party-shell,
        body.is-scrolling .leadership-shell,
        body.is-scrolling .map-shell,
        body.is-scrolling .project-detail-shell {
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }

        body.is-scrolling .vision-shell,
        body.is-scrolling .party-shell,
        body.is-scrolling .leadership-shell,
        body.is-scrolling .map-shell,
        body.is-scrolling .project-detail-shell {
            box-shadow: none !important;
        }

        .section-tree-nav {
            position: fixed;
            top: 50%;
            right: max(20px, env(safe-area-inset-right, 0px) + 20px);
            z-index: 8;
            transform: translateY(-50%);
            display: grid;
            gap: 0;
            width: clamp(220px, 16vw, 260px);
            padding: 18px 0 18px 16px;
            pointer-events: none;
        }

        .section-tree-nav::before {
            content: "";
            position: absolute;
            right: 16px;
            top: 18px;
            bottom: 18px;
            width: 2px;
            border-radius: 999px;
            background:
                repeating-linear-gradient(
                    to bottom,
                    rgba(255, 240, 214, 0.9) 0 7px,
                    rgba(255, 240, 214, 0) 7px 16px
                );
            opacity: 0.62;
            box-shadow: 0 0 16px rgba(255, 234, 189, 0.16);
        }

        .section-tree-nav::after {
            content: "";
            position: absolute;
            inset: 0 0 0 auto;
            width: 100%;
            border-radius: 999px 0 0 999px;
            background: linear-gradient(270deg, rgba(255, 248, 238, 0.12), rgba(255, 248, 238, 0.02) 42%, rgba(255, 248, 238, 0));
            opacity: 0.92;
            pointer-events: none;
        }

        .section-tree-nav-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 14px;
        }

        .section-tree-nav-item {
            position: relative;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 30px;
            align-items: center;
            gap: 12px;
        }

        .section-tree-nav-link {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 30px;
            align-items: center;
            gap: 12px;
            color: inherit;
            text-decoration: none;
            pointer-events: auto;
        }

        .section-tree-nav-dot {
            position: relative;
            width: 30px;
            height: 30px;
            display: grid;
            place-items: center;
        }

        .section-tree-nav-dot::before {
            content: "";
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 246, 226, 0.3);
            border: 1px solid rgba(255, 241, 219, 0.42);
            box-shadow: 0 0 0 6px rgba(255, 241, 219, 0.05);
            transition: transform 260ms cubic-bezier(.2, .8, .2, 1), background 260ms cubic-bezier(.2, .8, .2, 1), border-color 260ms cubic-bezier(.2, .8, .2, 1), box-shadow 260ms cubic-bezier(.2, .8, .2, 1);
        }

        .section-tree-nav-dot::after {
            content: "";
            position: absolute;
            right: 15px;
            top: 50%;
            width: 18px;
            height: 1px;
            background: linear-gradient(270deg, rgba(255, 241, 219, 0.46), rgba(255, 241, 219, 0));
            transform: translateY(-50%);
            opacity: 0.72;
        }

        .section-tree-nav-label {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            min-height: 40px;
            width: 100%;
            padding: 10px 16px;
            border-radius: 999px;
            background:
                linear-gradient(180deg, rgba(255,255,255,0.14), rgba(255,255,255,0.03)),
                linear-gradient(135deg, rgba(123, 46, 40, 0.52), rgba(91, 33, 29, 0.22));
            border: 1px solid rgba(255, 237, 205, 0.14);
            color: rgba(255, 246, 230, 0.82);
            font-size: 0.78rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            white-space: nowrap;
            opacity: 0.8;
            transform: translateX(0) scale(1);
            transition: opacity 260ms cubic-bezier(.2, .8, .2, 1), transform 260ms cubic-bezier(.2, .8, .2, 1), color 260ms cubic-bezier(.2, .8, .2, 1), background 260ms cubic-bezier(.2, .8, .2, 1), border-color 260ms cubic-bezier(.2, .8, .2, 1), box-shadow 260ms cubic-bezier(.2, .8, .2, 1);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.08),
                0 10px 22px rgba(61, 18, 17, 0.12);
            overflow: visible;
            font-weight: 600;
        }

        .section-tree-nav-link:hover .section-tree-nav-label,
        .section-tree-nav-link:focus-visible .section-tree-nav-label,
        .section-tree-nav-item.is-active .section-tree-nav-label {
            opacity: 1;
            transform: translateX(-6px) scale(1);
        }

        .section-tree-nav-link:hover .section-tree-nav-dot::before,
        .section-tree-nav-link:focus-visible .section-tree-nav-dot::before,
        .section-tree-nav-item.is-active .section-tree-nav-dot::before {
            transform: scale(1.18);
            background: linear-gradient(135deg, #fff5d7, #f4cf85);
            border-color: rgba(255, 241, 219, 0.92);
            box-shadow: 0 0 0 7px rgba(255, 241, 219, 0.09), 0 0 22px rgba(244, 207, 133, 0.24);
        }

        .section-tree-nav-item.is-active .section-tree-nav-label {
            color: var(--hero-ink);
            background:
                linear-gradient(180deg, rgba(255,255,255,0.18), rgba(255,255,255,0.06)),
                linear-gradient(135deg, rgba(144, 57, 48, 0.76), rgba(109, 40, 35, 0.42));
            border-color: rgba(255, 237, 205, 0.24);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.12),
                0 14px 30px rgba(61, 18, 17, 0.18);
        }

        .section-tree-nav-link:focus-visible {
            outline: none;
        }

        .section-separator-copy,
        .section-separator-line {
            transition: transform 320ms cubic-bezier(.2, .8, .2, 1), opacity 320ms cubic-bezier(.2, .8, .2, 1), border-color 320ms cubic-bezier(.2, .8, .2, 1), background 320ms cubic-bezier(.2, .8, .2, 1), box-shadow 320ms cubic-bezier(.2, .8, .2, 1);
        }

        .section-separator-line {
            position: relative;
            height: 2px;
            border-radius: 999px;
            background:
                linear-gradient(90deg, rgba(255, 237, 205, 0), rgba(255, 237, 205, 0.08) 10%, rgba(244, 207, 133, 0.78) 50%, rgba(255, 237, 205, 0.08) 90%, rgba(255, 237, 205, 0));
            opacity: 0.94;
            box-shadow:
                0 0 0 1px rgba(255, 250, 239, 0.02),
                0 0 16px rgba(244, 207, 133, 0.12);
        }

        .section-separator-line::after {
            content: "";
            position: absolute;
            left: 24%;
            right: 24%;
            top: 10px;
            height: 1px;
            border-radius: 999px;
            background: linear-gradient(90deg, rgba(255,255,255,0), rgba(255,255,255,0.18), rgba(255,255,255,0));
            opacity: 0.56;
        }

        .section-separator-line::before {
            content: "";
            position: absolute;
            top: 50%;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            border: 1px solid rgba(255, 236, 205, 0.34);
            background: rgba(244, 207, 133, 0.18);
            box-shadow: 0 0 10px rgba(244, 207, 133, 0.14);
            transform: translateY(-50%);
        }

        .section-separator-line:first-child::before {
            right: 10%;
        }

        .section-separator-line:last-child::before {
            left: 10%;
        }

        .section-separator-copy {
            position: relative;
            min-width: min(100%, 380px);
            max-width: 760px;
            margin: 0 auto;
            padding: 16px 28px 18px;
            text-align: center;
            display: grid;
            justify-items: center;
            row-gap: 8px;
            border-radius: 18px;
            border: 1px solid rgba(255, 237, 205, 0.1);
            background:
                linear-gradient(180deg, rgba(255,255,255,0.07), rgba(255,255,255,0.015)),
                linear-gradient(135deg, rgba(124, 46, 40, 0.16), rgba(78, 28, 25, 0.04));
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.06),
                0 14px 28px rgba(61, 18, 17, 0.1);
            opacity: 0;
            transform: translateY(18px) scale(0.985);
            overflow: hidden;
            isolation: isolate;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .section-separator-copy::before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background:
                linear-gradient(90deg, rgba(255,255,255,0.02), rgba(255,255,255,0.06) 50%, rgba(255,255,255,0.02)),
                radial-gradient(circle at center, rgba(255, 244, 221, 0.08), rgba(255, 244, 221, 0) 74%);
            pointer-events: none;
        }

        .section-separator-copy::after {
            content: "";
            position: absolute;
            inset: 10px;
            border-radius: 12px;
            border: 1px solid rgba(255, 242, 221, 0.08);
            background: linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0));
            filter: blur(1px);
            opacity: 1;
            pointer-events: none;
            z-index: 0;
        }

        .section-separator.is-visible .section-separator-copy,
        .section-separator.is-visible .section-separator-line {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .section-separator:hover .section-separator-copy {
            transform: translateY(-3px) scale(1.01);
            border-color: rgba(255, 237, 205, 0.16);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.08),
                0 18px 36px rgba(61, 18, 17, 0.14);
        }

        .section-separator:hover .section-separator-line {
            opacity: 1;
            box-shadow:
                0 0 0 1px rgba(255, 250, 239, 0.04),
                0 0 12px rgba(244, 207, 133, 0.1);
        }

        .section-separator-copy h2 {
            color: var(--hero-ink);
            width: 100%;
            margin: 0;
            font-size: clamp(1.14rem, 1.95vw, 1.76rem);
            line-height: 1.1;
            letter-spacing: -0.02em;
            font-weight: 600;
            text-wrap: balance;
            text-transform: none;
            text-shadow: 0 2px 12px rgba(61, 18, 17, 0.16);
            clear: both;
            font-family: "Gill Sans", "Avenir Next Condensed", "Trebuchet MS", "Segoe UI", sans-serif;
            position: relative;
            z-index: 1;
        }

        .section-separator-copy h2::before {
            content: "";
            display: block;
            width: 54px;
            height: 2px;
            margin: 0 auto 12px;
            border-radius: 999px;
            background: linear-gradient(90deg, rgba(255,255,255,0), rgba(244, 207, 133, 0.8), rgba(255,255,255,0));
            box-shadow: 0 0 10px rgba(244, 207, 133, 0.14);
        }

        @keyframes heroLogoBlink {
            0%,
            100% {
                opacity: 1;
                transform: scale(1);
                filter: drop-shadow(0 10px 22px rgba(61, 18, 17, 0.18));
            }
            35% {
                opacity: 0.55;
                transform: scale(0.985);
                filter: drop-shadow(0 7px 18px rgba(61, 18, 17, 0.12));
            }
            60% {
                opacity: 1;
                transform: scale(1.02);
                filter: drop-shadow(0 14px 26px rgba(61, 18, 17, 0.22));
            }
        }

        @keyframes heroLogoRoll {
            0%,
            100% {
                opacity: 1;
                transform: rotateX(0deg) rotateY(0deg) scale(1);
                filter: drop-shadow(0 10px 22px rgba(61, 18, 17, 0.18));
            }
            25% {
                opacity: 1;
                transform: rotateX(10deg) rotateY(-12deg) scale(1.028);
                filter: drop-shadow(0 15px 29px rgba(61, 18, 17, 0.22));
            }
            50% {
                opacity: 1;
                transform: rotateX(15deg) rotateY(-18deg) scale(1.04);
                filter: drop-shadow(0 18px 34px rgba(61, 18, 17, 0.26));
            }
            75% {
                opacity: 1;
                transform: rotateX(9deg) rotateY(-10deg) scale(1.024);
                filter: drop-shadow(0 14px 27px rgba(61, 18, 17, 0.21));
            }
        }

        @keyframes heroNavSweep {
            0%,
            100% {
                left: -24%;
                opacity: 0;
            }
            18% {
                opacity: 0;
            }
            30% {
                opacity: 0.32;
            }
            52% {
                left: 106%;
                opacity: 0.58;
            }
            66% {
                opacity: 0;
            }
        }

        @keyframes sectionTopicSweep {
            0%,
            100% {
                left: -26%;
                opacity: 0;
            }
            22% {
                opacity: 0;
            }
            34% {
                opacity: 0.28;
            }
            56% {
                left: 108%;
                opacity: 0.52;
            }
            70% {
                opacity: 0;
            }
        }

        @media (max-width: 1024px) {
            .hero-nav {
                gap: 14px;
                padding: 14px 16px;
            }

            .hero-brand-logo {
                width: 148px;
                max-width: 24vw;
            }

            .hero-slide {
                min-height: clamp(420px, 72vw, 560px);
                padding: 26px 24px 86px;
            }

            .hero-copy {
                width: min(100%, 680px);
                max-width: 680px;
                padding: 20px 22px 22px;
            }

            .hero-nav-panel {
                margin-left: 18px;
                gap: 14px;
            }

            .hero-links {
                gap: 10px;
            }

            .hero-links a,
            .hero-action {
                padding: 9px 15px;
                font-size: 0.85rem;
            }

            .footer-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 860px) {
            .section-tree-nav {
                right: 14px;
                width: 172px;
            }

            .top-hero {
                padding: 0 0 14px;
            }

            .hero-shell {
                padding: 10px 10px 0;
                border-radius: 0;
            }

            .hero-nav {
                align-items: center;
                padding: 14px;
                border-radius: 20px;
            }

            .hero-brand {
                justify-content: flex-start;
            }

            .hero-menu-toggle {
                display: inline-flex;
                margin-left: auto;
            }

            .hero-nav-panel {
                display: flex;
                width: 100%;
                flex-basis: 100%;
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
                max-height: 0;
                overflow: hidden;
                opacity: 0;
                padding-top: 0;
                margin-left: 0;
                transition:
                    max-height 320ms cubic-bezier(.22, .84, .24, 1),
                    opacity 220ms ease,
                    padding-top 220ms ease;
            }

            .hero-nav.is-menu-open .hero-nav-panel {
                max-height: 420px;
                opacity: 1;
                padding-top: 8px;
            }

            .hero-nav.is-menu-open .hero-links a,
            .hero-nav.is-menu-open .hero-action {
                opacity: 1;
                transform: translate3d(0, 0, 0) scale(1);
                filter: blur(0);
            }

            .hero-links {
                justify-content: stretch;
                flex-direction: column;
                margin: 0;
                padding: 8px;
                border-radius: 18px;
                background: rgba(255,255,255,0.06);
                border: 1px solid rgba(255, 240, 214, 0.12);
            }

            .hero-links a,
            .hero-action {
                width: 100%;
                box-sizing: border-box;
                justify-content: center;
                text-align: center;
                border-radius: 14px;
            }

            .hero-action {
                align-self: stretch;
                margin-left: 0;
            }

            .hero-carousel {
                border-radius: 26px;
            }

            .hero-controls {
                right: 14px;
                bottom: 14px;
            }

            .hero-dots {
                bottom: 18px;
            }

            .footer-grid {
                grid-template-columns: 1fr;
            }

            .footer-bottom {
                flex-direction: column;
                align-items: flex-start;
            }

            .footer-bottom-links {
                justify-content: flex-start;
            }
        }

        @media (max-width: 640px) {
            .section-tree-nav {
                display: none;
            }

            .hero-shell {
                width: 100%;
                padding: 8px 8px 0;
                border-radius: 0;
            }

            .site-footer {
                padding: 14px 0 0;
            }

            .footer-shell {
                padding: 18px 14px 16px;
                border-radius: 24px 24px 0 0;
            }

            .footer-brand,
            .footer-card {
                padding: 16px 16px 15px;
                border-radius: 18px;
            }

            .footer-social-list {
                grid-template-columns: 1fr;
            }

            .footer-card[aria-label="Social media links"] {
                gap: 12px;
            }

            .footer-social-link {
                grid-template-columns: 22px minmax(0, 1fr);
                min-height: 54px;
                padding: 0 16px;
                border-radius: 18px;
                column-gap: 14px;
            }

            .section-separator {
                grid-template-columns: 1fr;
                gap: 10px;
                padding: 18px 8px 12px;
            }

            .section-separator-line::after {
                left: 18%;
                right: 18%;
            }

            .section-separator-line::before {
                display: none;
            }

            .section-separator-copy {
                min-width: 0;
                max-width: 100%;
                order: 2;
                padding: 12px 16px 14px;
            }

            .section-separator-line:first-child {
                order: 1;
            }

            .section-separator-line:last-child {
                display: none;
            }

            .section-separator-copy h2 {
                font-size: clamp(1rem, 5.1vw, 1.32rem);
                gap: 10px;
            }

            .hero-nav {
                gap: 12px;
                padding: 12px;
                border-radius: 18px;
            }

            .hero-brand-logo {
                width: 136px;
                max-width: 48vw;
            }

            .hero-menu-toggle {
                width: 44px;
                height: 44px;
                border-radius: 14px;
            }

            .hero-menu-toggle-icon {
                width: 18px;
                height: 13px;
            }

            .hero-menu-toggle-line:nth-child(2) {
                top: 5.5px;
            }

            .hero-menu-toggle-line:nth-child(3) {
                top: 11px;
            }

            .hero-links {
                gap: 8px;
            }

            .hero-links a,
            .hero-action {
                font-size: 0.82rem;
                padding: 9px 12px;
            }

            .hero-slide {
                min-height: 540px;
                padding: 16px 12px 88px;
            }

            .hero-copy {
                width: calc(100% - 24px);
                max-width: none;
                padding: 19px 18px 20px;
                border-radius: 24px;
                background:
                    linear-gradient(180deg, rgba(146, 42, 35, 0.16), rgba(104, 32, 27, 0.62) 54%, rgba(84, 28, 24, 0.74)),
                    linear-gradient(135deg, rgba(255, 255, 255, 0.14), rgba(255, 255, 255, 0.03));
                border-color: rgba(255, 244, 224, 0.2);
                box-shadow:
                    inset 0 1px 0 rgba(255,255,255,0.12),
                    inset 0 -18px 26px rgba(255, 203, 142, 0.05),
                    0 20px 40px rgba(20, 8, 10, 0.24);
                backdrop-filter: blur(13px);
                -webkit-backdrop-filter: blur(13px);
            }

            .hero-copy h1 {
                font-size: clamp(1.9rem, 7.4vw, 2.45rem);
                max-width: 100%;
                line-height: 1;
                letter-spacing: -0.045em;
                text-shadow: 0 8px 22px rgba(9, 8, 16, 0.22);
            }

            .hero-copy p {
                max-width: 100%;
                line-height: 1.64;
                font-size: 0.97rem;
                color: rgba(255, 243, 225, 0.88);
            }

            .hero-slide::before {
                background:
                    linear-gradient(180deg, rgba(22, 10, 11, 0.06), rgba(22, 10, 11, 0.02) 30%, rgba(22, 10, 11, 0.12) 62%, rgba(22, 10, 11, 0.28)),
                    linear-gradient(90deg, rgba(22, 10, 11, 0.1), rgba(22, 10, 11, 0.03) 52%, rgba(22, 10, 11, 0.01));
            }

            .hero-media-wrap {
                inset: 12px 12px 144px;
                border-radius: 24px;
                background:
                    radial-gradient(circle at top center, rgba(255,255,255,0.12), transparent 28%),
                    linear-gradient(180deg, rgba(98, 41, 36, 0.28), rgba(63, 22, 20, 0.22));
                box-shadow:
                    inset 0 1px 0 rgba(255,255,255,0.08),
                    0 18px 32px rgba(22, 8, 9, 0.14);
            }

            .hero-slide[data-media-type="image"] .hero-media-wrap {
                background:
                    linear-gradient(180deg, rgba(18, 10, 11, 0.14), rgba(18, 10, 11, 0.2)),
                    var(--slide-media) center center / cover no-repeat;
            }

            .hero-slide[data-media-type="image"] .hero-media-wrap::before {
                opacity: 1;
                transform: none;
                background:
                    linear-gradient(180deg, rgba(255,255,255,0.07), rgba(255,255,255,0) 34%, rgba(18, 10, 11, 0.14));
                filter: none;
            }

            .hero-slide[data-media-type="image"] .hero-media,
            .hero-slide.is-active[data-media-type="image"] .hero-media,
            .hero-slide.is-leaving[data-media-type="image"] .hero-media {
                width: 100%;
                height: 100%;
                margin: 0;
                object-fit: contain;
                object-position: center center;
                background: transparent;
                transform: translateZ(0);
                filter: drop-shadow(0 16px 28px rgba(23, 8, 8, 0.14));
                clip-path: inset(0 0 0 0 round 0);
            }

            .hero-slide.is-active .hero-media-wrap::after {
                opacity: 1;
                inset: 12px;
                border-radius: 22px;
                border-color: rgba(255,255,255,0.16);
                box-shadow:
                    inset 0 1px 0 rgba(255,255,255,0.06),
                    0 0 0 1px rgba(255, 228, 187, 0.04);
            }

            .hero-slide[data-media-type="video"] .hero-media,
            .hero-slide.is-active[data-media-type="video"] .hero-media,
            .hero-slide.is-leaving[data-media-type="video"] .hero-media {
                width: 124%;
                height: 148%;
                margin: -24% 0 0 -12%;
                object-fit: cover;
                object-position: center 28%;
                background: transparent;
                transform: scale(1.2) translateZ(0);
                filter: saturate(1.08) contrast(1.06) brightness(1.03);
                clip-path: inset(0 0 0 0 round 0);
            }

            .hero-slide[data-media-anim="zoom"] .hero-media,
            .hero-slide[data-media-anim="pan-left"] .hero-media,
            .hero-slide[data-media-anim="pan-right"] .hero-media,
            .hero-slide[data-media-anim="tilt"] .hero-media {
                clip-path: inset(0 0 0 0 round 0);
            }

            .hero-controls {
                gap: 8px;
                right: 12px;
                bottom: 12px;
                padding: 6px;
                border-radius: 999px;
                background:
                    linear-gradient(180deg, rgba(255,255,255,0.12), rgba(255,255,255,0.02)),
                    rgba(84, 31, 27, 0.12);
                border: 1px solid rgba(255, 239, 214, 0.12);
                box-shadow:
                    inset 0 1px 0 rgba(255,255,255,0.06),
                    0 12px 28px rgba(28, 8, 8, 0.12);
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
            }

            .hero-button {
                width: 44px;
                height: 44px;
                border-color: rgba(255, 235, 201, 0.22);
                background:
                    linear-gradient(145deg, rgba(244, 214, 143, 0.24), rgba(255,255,255,0.1)),
                    rgba(91, 33, 29, 0.18);
                box-shadow:
                    inset 0 1px 0 rgba(255,255,255,0.14),
                    0 12px 24px rgba(61, 18, 17, 0.18);
            }

            .hero-dots {
                left: 20px;
                right: 84px;
                bottom: 20px;
                transform: none;
                justify-content: flex-start;
                overflow-x: auto;
                align-items: center;
                gap: 9px;
                padding: 6px 6px 4px;
                border-radius: 999px;
                background: linear-gradient(90deg, rgba(255,255,255,0.08), rgba(255,255,255,0));
            }

            .hero-dot {
                width: 9px;
                height: 9px;
                background: rgba(255, 241, 219, 0.34);
                box-shadow: 0 0 0 1px rgba(255, 241, 219, 0.06);
            }

            .hero-dot.active {
                width: 28px;
                height: 10px;
                border-radius: 999px;
                box-shadow: 0 0 16px rgba(244, 207, 133, 0.24);
            }

            .smart-top-button {
                right: max(12px, env(safe-area-inset-right, 0px) + 12px);
                bottom: max(12px, env(safe-area-inset-bottom, 0px) + 12px);
                min-width: 50px;
                height: 50px;
                padding: 0 16px;
            }

            .smart-top-button-label {
                font-size: 0.78rem;
            }
        }

        @media (max-width: 480px) {
            .hero-shell {
                padding: 6px 6px 0;
            }

            .hero-nav {
                margin-bottom: 12px;
                padding: 10px;
            }

            .hero-brand-logo {
                width: 118px;
                max-width: 44vw;
            }

            .hero-links a,
            .hero-action {
                font-size: 0.8rem;
                padding: 8px 11px;
            }

            .hero-links {
                padding: 6px;
                border-radius: 16px;
            }

            .hero-slide {
                min-height: 510px;
                padding: 14px 10px 82px;
            }

            .hero-media-wrap {
                inset: 10px 10px 138px;
                border-radius: 22px;
            }

            .hero-slide[data-media-type="image"] .hero-media,
            .hero-slide.is-active[data-media-type="image"] .hero-media,
            .hero-slide.is-leaving[data-media-type="image"] .hero-media {
                width: 100%;
                height: 100%;
                object-position: center center;
            }

            .hero-slide[data-media-type="video"] .hero-media,
            .hero-slide.is-active[data-media-type="video"] .hero-media,
            .hero-slide.is-leaving[data-media-type="video"] .hero-media {
                width: 128%;
                height: 154%;
                margin: -28% 0 0 -14%;
                object-position: center 26%;
                transform: scale(1.24) translateZ(0);
            }

            .hero-copy {
                width: calc(100% - 20px);
                padding: 16px 16px 18px;
                border-radius: 20px;
            }

            .hero-copy > span {
                margin-bottom: 12px;
                padding: 8px 12px;
                font-size: 0.72rem;
                letter-spacing: 0.12em;
            }

            .hero-copy h1 {
                font-size: clamp(1.7rem, 8.5vw, 2.08rem);
                line-height: 1.04;
            }

            .hero-copy p {
                margin-top: 10px;
                font-size: 0.94rem;
                line-height: 1.58;
            }

            .hero-controls {
                gap: 6px;
                padding: 5px;
            }

            .hero-button {
                width: 42px;
                height: 42px;
            }

            .hero-dots {
                left: 16px;
                right: 78px;
                bottom: 18px;
                gap: 8px;
                padding: 5px 5px 3px;
            }

            .hero-dot.active {
                width: 24px;
                height: 9px;
            }

            .smart-top-button {
                min-width: 48px;
                height: 48px;
                padding: 0 14px;
            }

            .footer-social-link {
                grid-template-columns: 20px minmax(0, 1fr);
                min-height: 52px;
                padding: 0 14px;
                column-gap: 12px;
            }

            .smart-top-button-label {
                display: none;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .hero-brand,
            .hero-brand-logo,
            .section-separator-copy,
            .section-separator-line {
                transition: none;
                animation: none;
            }

            .hero-brand-logo,
            .section-separator-copy,
            .section-separator-line {
                opacity: 1;
                transform: none;
            }
        }
        .page-loader {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background:
                radial-gradient(circle at top left, rgba(201, 104, 86, 0.24), transparent 24%),
                radial-gradient(circle at top right, rgba(228, 191, 109, 0.22), transparent 28%),
                linear-gradient(180deg, #9f4f47 0%, #b76052 18%, #c77158 34%, #da975f 54%, #e6b765 72%, #efcf93 88%, #f3dcc2 100%);
            transition: opacity 600ms cubic-bezier(0.4, 0, 0.2, 1), visibility 600ms cubic-bezier(0.4, 0, 0.2, 1);
        }

        .page-loader.is-loaded {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .page-loader-content {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 28px;
        }

        .page-loader-logo {
            width: clamp(100px, 18vw, 160px);
            height: auto;
            object-fit: contain;
            position: relative;
            animation: logoPulse 3s ease-in-out infinite;
            filter: drop-shadow(0 12px 32px rgba(61, 18, 17, 0.4));
            text-decoration: none;
        }

        @keyframes logoPulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.95; }
        }

        .page-loader-progress {
            margin-top: 0;
            width: clamp(140px, 22vw, 220px);
            height: 6px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 999px;
            overflow: hidden;
            position: relative;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .page-loader-progress-bar {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, rgba(244, 210, 122, 0.9), rgba(255, 255, 255, 0.95), rgba(244, 210, 122, 0.9));
            background-size: 200% 100%;
            border-radius: 999px;
            animation: progressGlow 2s ease-in-out infinite;
            will-change: width;
            box-shadow: 0 0 12px rgba(244, 210, 122, 0.6);
        }

        @keyframes progressGlow {
            0% { width: 0%; background-position: 100% 0; }
            50% { width: 75%; background-position: 50% 0; }
            100% { width: 100%; background-position: 0% 0; }
        }

        body.is-loading {
            overflow: hidden;
        }
    </style>
</head>
<body class="is-loading">
<div class="page-loader" id="pageLoader">
    <div class="page-loader-content">
        <img class="page-loader-logo" src="images/testlogo.png" alt="Loading">
        <div class="page-loader-progress">
            <div class="page-loader-progress-bar"></div>
        </div>
    </div>
</div>

<script>
    (function() {
        const loader = document.getElementById('pageLoader');
        if (!loader) return;
        
        let resourcesLoaded = 0;
        let totalResources = 0;
        
        // Count all images and videos
        const images = document.querySelectorAll('img[src]');
        const videos = document.querySelectorAll('video');
        totalResources = images.length + videos.length;
        
        // Track resource loading
        const checkAllLoaded = function() {
            if (totalResources === 0 || resourcesLoaded >= totalResources) {
                setTimeout(function() {
                    loader.classList.add('is-loaded');
                    document.body.classList.remove('is-loading');
                }, 500);
            }
        };
        
        // Wait for window load first
        window.addEventListener('load', function() {
            if (totalResources === 0) {
                checkAllLoaded();
            }
        });
        
        // Track image loading
        images.forEach(function(img) {
            if (img.complete) {
                resourcesLoaded++;
                checkAllLoaded();
            } else {
                img.addEventListener('load', function() {
                    resourcesLoaded++;
                    checkAllLoaded();
                });
                img.addEventListener('error', function() {
                    resourcesLoaded++;
                    checkAllLoaded();
                });
            }
        });
        
        // Track video loading
        videos.forEach(function(video) {
            if (video.readyState >= 3) {
                resourcesLoaded++;
                checkAllLoaded();
            } else {
                video.addEventListener('loadeddata', function() {
                    resourcesLoaded++;
                    checkAllLoaded();
                });
                video.addEventListener('error', function() {
                    resourcesLoaded++;
                    checkAllLoaded();
                });
            }
        });
        
        // Fallback: hide after 5 seconds max
        setTimeout(function() {
            if (!loader.classList.contains('is-loaded')) {
                loader.classList.add('is-loaded');
                document.body.classList.remove('is-loading');
            }
        }, 5000);
    })();
</script>

<section class="top-hero" id="top">
    <div class="hero-shell">
        <nav class="hero-nav" aria-label="Main navigation">
            <a class="hero-brand" href="#top" aria-label="Home">
                <img class="hero-brand-logo" src="images/testlogo.png" alt="Logo">
            </a>
            <button class="hero-menu-toggle" type="button" aria-expanded="false" aria-controls="heroNavPanel" aria-label="Toggle navigation menu">
                <span class="hero-menu-toggle-icon" aria-hidden="true">
                    <span class="hero-menu-toggle-line"></span>
                    <span class="hero-menu-toggle-line"></span>
                    <span class="hero-menu-toggle-line"></span>
                </span>
            </button>
            <div class="hero-nav-panel" id="heroNavPanel">
                <div class="hero-links">
                    <?php foreach ($sectionNavItems as $index => $item): ?>
                        <a href="#<?php echo htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8'); ?>" style="--nav-index: <?php echo $index; ?>;">
                            <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </nav>

        <div class="hero-carousel" id="heroCarousel">
            <div class="hero-track" id="heroTrack">
                <?php foreach ($heroSlides as $index => $slide): ?>
                    <article
                        class="hero-slide<?php echo $index === 0 ? ' is-active' : ''; ?>"
                        data-media-type="<?php echo htmlspecialchars((string) ($slide['media_type'] ?? 'image'), ENT_QUOTES, 'UTF-8'); ?>"
                        data-text-anim="<?php echo htmlspecialchars($slide['text_animation'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-media-anim="<?php echo htmlspecialchars($slide['media_animation'], ENT_QUOTES, 'UTF-8'); ?>"
                        style="<?php echo htmlspecialchars('--slide-eyebrow-color: ' . ($slide['eyebrow_color'] ?? '#FFF7EA') . '; --slide-title-color: ' . ($slide['title_color'] ?? '#FFF7EA') . '; --slide-description-color: ' . ($slide['description_color'] ?? 'rgba(255, 242, 221, 0.82)') . '; --slide-media: url("' . ($slide['media'] ?? '') . '");', ENT_QUOTES, 'UTF-8'); ?>"
                    >
                        <div class="hero-media-wrap">
                            <?php if (($slide['media_type'] ?? 'image') === 'video'): ?>
                                <video
                                    class="hero-media"
                                    src="<?php echo htmlspecialchars($slide['media'], ENT_QUOTES, 'UTF-8'); ?>"
                                    poster="<?php echo htmlspecialchars($slide['poster'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-video-start="<?php echo htmlspecialchars((string) ($slide['video_start_time'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-video-end="<?php echo htmlspecialchars((string) ($slide['video_end_time'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    muted
                                    playsinline
                                    preload="<?php echo $index === 0 ? 'auto' : 'metadata'; ?>"
                                ></video>
                            <?php else: ?>
                                <img
                                    class="hero-media"
                                    src="<?php echo htmlspecialchars($slide['media'], ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="<?php echo htmlspecialchars($slide['nav_label'], ENT_QUOTES, 'UTF-8'); ?>"
                                    loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>"
                                    decoding="async"
                                    fetchpriority="<?php echo $index === 0 ? 'high' : 'auto'; ?>"
                                >
                            <?php endif; ?>
                        </div>
                        <div class="hero-copy">
                            <?php if (trim((string) ($slide['eyebrow'] ?? '')) !== ''): ?>
                                <span><?php echo htmlspecialchars($slide['eyebrow'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                            <h1><?php echo hero_title_markup($slide['title']); ?></h1>
                            <p><?php echo htmlspecialchars($slide['text'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="hero-controls">
                <button class="hero-button" type="button" id="heroPrev" aria-label="Previous slide">&#8592;</button>
                <button class="hero-button" type="button" id="heroNext" aria-label="Next slide">&#8594;</button>
            </div>
            <div class="hero-dots" id="heroDots" aria-label="Hero slide navigation"></div>
        </div>
    </div>
</section>

<button class="smart-top-button" type="button" id="smartTopButton" aria-label="Back to top">
    <span class="smart-top-button-icon" aria-hidden="true">&#8593;</span>
    <span class="smart-top-button-label">Top</span>
</button>

<aside class="section-tree-nav" aria-label="Section navigation">
    <ul class="section-tree-nav-list">
        <?php foreach ($sectionNavItems as $item): ?>
            <li class="section-tree-nav-item" data-section-nav-item data-target="<?php echo htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8'); ?>">
                <a class="section-tree-nav-link" href="#<?php echo htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="section-tree-nav-dot" aria-hidden="true"></span>
                    <span class="section-tree-nav-label"><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</aside>

<section class="page-main">
    <?php render_section_separator('vision', 'Vision for Sri Lanka'); ?>
    <div class="page-section-chunk">
        <?php
        $GLOBALS['VISION_EMBED'] = true;
        include __DIR__ . '/vision.php';
        unset($GLOBALS['VISION_EMBED']);
        ?>
    </div>

    <?php render_section_separator('our-party', 'Our Party'); ?>
    <div class="page-section-chunk">
        <?php
        $GLOBALS['OUR_PARTY_EMBED'] = true;
        include __DIR__ . '/our_party.php';
        unset($GLOBALS['OUR_PARTY_EMBED']);
        ?>
    </div>

    <?php render_section_separator('leadership', 'Our Leadership'); ?>
    <div class="page-section-chunk">
        <?php
        $GLOBALS['LEADERSHIP_EMBED'] = true;
        include __DIR__ . '/leadership.php';
        unset($GLOBALS['LEADERSHIP_EMBED']);
        ?>
    </div>

    <?php render_section_separator('districts', 'What we Build for a Nation'); ?>
    <div class="page-section-chunk">
        <?php
        $GLOBALS['SL_EMBED'] = true;
        include __DIR__ . '/sl.php';
        unset($GLOBALS['SL_EMBED']);
        ?>
    </div>
</section>

<footer class="site-footer">
    <div class="footer-shell">
        <div class="footer-grid">
            <section class="footer-brand" aria-label="Site footer brand">
                <div class="footer-logo-row">
                    <img class="footer-logo" src="images/testlogo.png" alt="Logo">
                    <div>
                        <h2>Stronger Presence for a Stronger Nation</h2>
                    </div>
                </div>
                <p>A modern public-facing platform for leadership stories, district initiatives, and campaign communication that stays clear, trustworthy, and mobile-ready.</p>
            </section>

            <section class="footer-card" aria-label="Quick links">
                <h3>Quick Links</h3>
                <ul class="footer-link-list">
                    <li><a href="#top">Home</a></li>
                    <li><a href="#vision">Vision</a></li>
                    <li><a href="#our-party">Our Party</a></li>
                    <li><a href="#leadership">Leadership</a></li>
                    <li><a href="#districts">Projects</a></li>
                </ul>
            </section>

            <section class="footer-card" aria-label="Contact details">
                <h3>Contact</h3>
                <ul class="footer-contact-list">
                    <li>
                        <span class="footer-contact-label">Phone</span>
                        <a class="footer-contact-link" href="tel:+94000000000">+94 00 000 0000</a>
                    </li>
                    <li>
                        <span class="footer-contact-label">Email</span>
                        <a class="footer-contact-link" href="mailto:info@slppsrilanka.lk">info@slppsrilanka.lk</a>
                    </li>
                    <li>
                        <span class="footer-contact-label">Office</span>
                        <p>Colombo, Sri Lanka</p>
                    </li>
                </ul>
            </section>

            <section class="footer-card" aria-label="Social media links">
                <h3>Follow Us</h3>
                <div class="footer-social-list">
                    <a class="footer-social-link" href="#" aria-label="Facebook">
                        <svg class="footer-social-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M13.5 21V12.8H16.3L16.72 9.6H13.5V7.55C13.5 6.63 13.76 6 15.08 6H16.84V3.14C16.53 3.1 15.47 3 14.24 3C11.67 3 9.92 4.57 9.92 7.46V9.6H7.2V12.8H9.92V21H13.5Z" fill="currentColor"/></svg>
                        <span>Facebook</span>
                    </a>
                    <a class="footer-social-link" href="#" aria-label="Instagram">
                        <svg class="footer-social-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 8.2A3.8 3.8 0 1 0 12 15.8A3.8 3.8 0 0 0 12 8.2ZM12 14.44A2.44 2.44 0 1 1 12 9.56A2.44 2.44 0 0 1 12 14.44ZM16.84 8.04A.89.89 0 1 0 16.84 6.26A.89.89 0 0 0 16.84 8.04ZM21 8.06C20.91 6.22 20.49 4.58 19.14 3.24C17.8 1.89 16.16 1.47 14.32 1.38C12.42 1.27 11.58 1.27 9.68 1.38C7.84 1.47 6.2 1.89 4.86 3.24C3.51 4.58 3.09 6.22 3 8.06C2.89 9.96 2.89 10.8 3 12.7C3.09 14.54 3.51 16.18 4.86 17.52C6.2 18.87 7.84 19.29 9.68 19.38C11.58 19.49 12.42 19.49 14.32 19.38C16.16 19.29 17.8 18.87 19.14 17.52C20.49 16.18 20.91 14.54 21 12.7C21.11 10.8 21.11 9.96 21 8.06ZM19.38 15.76C18.98 16.76 18.21 17.53 17.21 17.93C15.76 18.51 12.32 18.38 12 18.38C11.68 18.38 8.24 18.51 6.79 17.93C5.79 17.53 5.02 16.76 4.62 15.76C4.04 14.31 4.17 10.87 4.17 10.55C4.17 10.23 4.04 6.79 4.62 5.34C5.02 4.34 5.79 3.57 6.79 3.17C8.24 2.59 11.68 2.72 12 2.72C12.32 2.72 15.76 2.59 17.21 3.17C18.21 3.57 18.98 4.34 19.38 5.34C19.96 6.79 19.83 10.23 19.83 10.55C19.83 10.87 19.96 14.31 19.38 15.76Z" fill="currentColor"/></svg>
                        <span>Instagram</span>
                    </a>
                    <a class="footer-social-link" href="#" aria-label="YouTube">
                        <svg class="footer-social-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20.45 7.34C20.24 6.54 19.61 5.91 18.81 5.7C17.35 5.31 12 5.31 12 5.31S6.65 5.31 5.19 5.7C4.39 5.91 3.76 6.54 3.55 7.34C3.16 8.8 3.16 11.85 3.16 11.85S3.16 14.9 3.55 16.36C3.76 17.16 4.39 17.79 5.19 18C6.65 18.39 12 18.39 12 18.39S17.35 18.39 18.81 18C19.61 17.79 20.24 17.16 20.45 16.36C20.84 14.9 20.84 11.85 20.84 11.85S20.84 8.8 20.45 7.34ZM10.29 14.15V9.55L14.27 11.85L10.29 14.15Z" fill="currentColor"/></svg>
                        <span>YouTube</span>
                    </a>
                    <a class="footer-social-link" href="#" aria-label="WhatsApp">
                        <svg class="footer-social-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20.52 3.48A11.86 11.86 0 0 0 12.05 0C5.48 0 .12 5.35.12 11.92C.12 14.03.67 16.08 1.73 17.89L0 24L6.27 22.35C8 23.3 9.95 23.79 12.03 23.79H12.04C18.61 23.79 23.96 18.44 23.96 11.87C23.97 8.69 22.73 5.7 20.52 3.48ZM12.04 21.78H12.03C10.24 21.78 8.49 21.3 6.96 20.39L6.6 20.18L2.88 21.16L3.87 17.54L3.63 17.17C2.65 15.61 2.14 13.81 2.14 11.92C2.14 6.46 6.58 2.01 12.05 2.01C14.7 2.01 17.19 3.04 19.06 4.91C20.92 6.78 21.95 9.27 21.95 11.91C21.95 17.37 17.5 21.78 12.04 21.78ZM17.47 14.38C17.17 14.23 15.7 13.51 15.42 13.41C15.14 13.31 14.94 13.26 14.74 13.56C14.54 13.86 13.97 14.53 13.8 14.73C13.63 14.93 13.46 14.95 13.16 14.8C12.86 14.65 11.9 14.34 10.76 13.33C9.88 12.55 9.29 11.59 9.12 11.29C8.95 10.99 9.1 10.82 9.25 10.67C9.39 10.53 9.55 10.3 9.7 10.13C9.85 9.95 9.9 9.83 10 9.63C10.1 9.43 10.05 9.25 9.97 9.1C9.9 8.95 9.3 7.48 9.05 6.88C8.8 6.3 8.56 6.37 8.38 6.36H7.83C7.63 6.36 7.31 6.43 7.03 6.73C6.75 7.03 5.95 7.77 5.95 9.28C5.95 10.79 7.06 12.25 7.21 12.45C7.36 12.65 9.32 15.67 12.31 16.96C13.02 17.27 13.58 17.46 14.01 17.6C14.72 17.83 15.36 17.8 15.87 17.72C16.44 17.63 17.61 17 17.86 16.31C18.11 15.61 18.11 15.03 18.04 14.91C17.96 14.78 17.77 14.71 17.47 14.56V14.38Z" fill="currentColor"/></svg>
                        <span>WhatsApp</span>
                    </a>
                </div>
            </section>
        </div>

        <div class="footer-bottom">
            <span>&copy; <?php echo date('Y'); ?> SLPP Srilanka. All rights reserved.</span>
            <div class="footer-bottom-links">
                <a href="#top">Back to top</a>
                <a href="#vision">Vision</a>
                <a href="#districts">Projects</a>
            </div>
        </div>
    </div>
</footer>
<script>
    (() => {
        let scrollTimer = 0;
        let ticking = false;
        const scrollingClass = "is-scrolling";

        function clearScrollingState() {
            document.body.classList.remove(scrollingClass);
            scrollTimer = 0;
        }

        function markScrolling() {
            if (!document.body.classList.contains(scrollingClass)) {
                document.body.classList.add(scrollingClass);
            }

            if (scrollTimer) {
                window.clearTimeout(scrollTimer);
            }

            scrollTimer = window.setTimeout(clearScrollingState, 140);
            ticking = false;
        }

        function scheduleScrollingState() {
            if (ticking) {
                return;
            }

            ticking = true;
            window.requestAnimationFrame(markScrolling);
        }

        window.addEventListener("scroll", scheduleScrollingState, { passive: true });
        window.addEventListener("wheel", scheduleScrollingState, { passive: true });
        window.addEventListener("touchmove", scheduleScrollingState, { passive: true });
    })();

    (() => {
        const heroShell = document.querySelector(".hero-shell");
        const heroNav = document.querySelector(".hero-nav");
        const menuToggle = heroNav ? heroNav.querySelector(".hero-menu-toggle") : null;
        const navLinks = heroNav ? Array.from(heroNav.querySelectorAll(".hero-links a")) : [];
        const smartTopButton = document.getElementById("smartTopButton");
        const heroCarousel = document.getElementById("heroCarousel");
        const track = document.getElementById("heroTrack");
        const prev = document.getElementById("heroPrev");
        const next = document.getElementById("heroNext");
        const dotsHost = document.getElementById("heroDots");
        const slides = track ? Array.from(track.children) : [];
        const videos = slides.map((slide) => slide.querySelector("video"));
        const videoWindows = videos.map((video) => {
            if (!video) {
                return null;
            }

            const rawStart = Number.parseFloat(video.dataset.videoStart || "");
            const rawEnd = Number.parseFloat(video.dataset.videoEnd || "");

            return {
                start: Number.isFinite(rawStart) && rawStart >= 0 ? rawStart : 0,
                end: Number.isFinite(rawEnd) && rawEnd >= 0 ? rawEnd : null,
            };
        });

        if (!heroShell || !heroCarousel || !track || !prev || !next || !dotsHost || !slides.length) {
            return;
        }

        const syncNavState = (isOpen) => {
            if (!heroNav || !menuToggle) {
                return;
            }

            heroNav.classList.toggle("is-menu-open", isOpen);
            menuToggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
        };

        if (heroNav && menuToggle) {
            menuToggle.addEventListener("click", () => {
                syncNavState(!heroNav.classList.contains("is-menu-open"));
            });

            navLinks.forEach((link) => {
                link.addEventListener("click", () => {
                    if (window.innerWidth <= 860) {
                        syncNavState(false);
                    }
                });
            });

            window.addEventListener("resize", () => {
                if (window.innerWidth > 860) {
                    syncNavState(false);
                }
            }, { passive: true });
        }

        navLinks.forEach((link) => {
            link.addEventListener("click", (event) => {
                const href = link.getAttribute("href") || "";
                if (!href.startsWith("#")) {
                    return;
                }

                const target = document.querySelector(href);
                if (!target) {
                    return;
                }

                event.preventDefault();
                target.scrollIntoView({
                    behavior: "smooth",
                    block: "start",
                });
            });
        });

        if (smartTopButton) {
            const syncSmartTopButton = () => {
                const shouldShow = window.scrollY > Math.max(320, window.innerHeight * 0.45);
                smartTopButton.classList.toggle("is-visible", shouldShow);
            };

            smartTopButton.addEventListener("click", () => {
                window.scrollTo({
                    top: 0,
                    behavior: "smooth",
                });
            });

            window.addEventListener("scroll", syncSmartTopButton, { passive: true });
            window.addEventListener("resize", syncSmartTopButton, { passive: true });
            syncSmartTopButton();
        }

        const getVideoClipBounds = (video, config) => {
            if (!video || !config) {
                return null;
            }

            const duration = Number.isFinite(video.duration) ? video.duration : null;
            const start = duration !== null ? Math.min(config.start, duration) : config.start;
            const end = config.end !== null && duration !== null ? Math.min(config.end, duration) : config.end;

            return {
                start,
                end: end !== null && end > start ? end : null,
            };
        };

        const syncVideoWindow = (video, config) => {
            const bounds = getVideoClipBounds(video, config);
            if (!bounds) {
                return;
            }

            if (video.currentTime < bounds.start) {
                video.currentTime = bounds.start;
                return;
            }

            if (bounds.end !== null && video.currentTime >= Math.max(bounds.start, bounds.end - 0.08)) {
                video.currentTime = bounds.start;
            }
        };

        function getIndex() {
            return Math.max(0, Math.min(slides.length - 1, Math.round(track.scrollLeft / track.clientWidth)));
        }

        function goToSlide(index) {
            const targetIndex = Math.max(0, Math.min(slides.length - 1, index));
            track.scrollTo({ left: track.clientWidth * targetIndex, behavior: "smooth" });
        }

        let videoAdvanceLock = false;

        const advanceToNextSlideFromVideo = (videoIndex) => {
            if (videoAdvanceLock || videoIndex !== getIndex()) {
                return;
            }

            const nextIndex = videoIndex + 1;
            if (nextIndex >= slides.length) {
                return;
            }

            videoAdvanceLock = true;
            goToSlide(nextIndex);
            window.setTimeout(() => {
                videoAdvanceLock = false;
            }, 700);
        };

        videos.forEach((video, index) => {
            const config = videoWindows[index];
            if (!video || !config) {
                return;
            }

            video.addEventListener("loadedmetadata", () => {
                const bounds = getVideoClipBounds(video, config);
                if (bounds && (config.start > 0 || config.end !== null)) {
                    video.currentTime = bounds.start;
                }
            });

            video.addEventListener("timeupdate", () => {
                const bounds = getVideoClipBounds(video, config);
                if (bounds && bounds.end !== null && video.currentTime >= Math.max(bounds.start, bounds.end - 0.08)) {
                    video.pause();
                    advanceToNextSlideFromVideo(index);
                    return;
                }

                syncVideoWindow(video, config);
            });

            video.addEventListener("play", () => {
                const bounds = getVideoClipBounds(video, config);
                if (!bounds) {
                    return;
                }

                if (video.currentTime < bounds.start || (bounds.end !== null && video.currentTime >= bounds.end)) {
                    video.currentTime = bounds.start;
                }
            });

            video.addEventListener("ended", () => {
                advanceToNextSlideFromVideo(index);
            });
        });

        const dots = slides.map((_, index) => {
            const dot = document.createElement("button");
            dot.type = "button";
            dot.className = "hero-dot";
            dot.setAttribute("aria-label", `Go to slide ${index + 1}`);
            dot.addEventListener("click", () => {
                track.scrollTo({ left: track.clientWidth * index, behavior: "smooth" });
            });
            dotsHost.appendChild(dot);
            return dot;
        });

        let updateFrame = 0;
        let activeIndex = getIndex();
        let leavingTimer = 0;

        function applyUpdate() {
            const index = getIndex();
            if (index !== activeIndex) {
                const previousSlide = slides[activeIndex];
                if (previousSlide) {
                    previousSlide.classList.remove("is-active");
                    previousSlide.classList.add("is-leaving");
                }

                if (leavingTimer) {
                    window.clearTimeout(leavingTimer);
                }

                leavingTimer = window.setTimeout(() => {
                    slides.forEach((slide) => slide.classList.remove("is-leaving"));
                    leavingTimer = 0;
                }, 760);

                activeIndex = index;
            }

            dots.forEach((dot, dotIndex) => dot.classList.toggle("active", dotIndex === index));
            slides.forEach((slide, slideIndex) => {
                const isActive = slideIndex === index;
                slide.classList.toggle("is-active", isActive);
                if (isActive) {
                    slide.classList.remove("is-leaving");
                }
                const video = videos[slideIndex];
                if (!video) {
                    return;
                }

                if (isActive) {
                    const bounds = getVideoClipBounds(video, videoWindows[slideIndex]);
                    if (bounds && (video.currentTime < bounds.start || (bounds.end !== null && video.currentTime >= bounds.end))) {
                        video.currentTime = bounds.start;
                    }

                    if (video.paused) {
                        video.play().catch(() => {});
                    }
                } else {
                    video.pause();
                    const bounds = getVideoClipBounds(video, videoWindows[slideIndex]);
                    video.currentTime = bounds ? bounds.start : 0;
                }
            });
            prev.disabled = index === 0;
            next.disabled = index === slides.length - 1;
            updateFrame = 0;
        }

        function update() {
            if (updateFrame) {
                return;
            }

            updateFrame = window.requestAnimationFrame(applyUpdate);
        }

        prev.addEventListener("click", () => {
            const index = getIndex();
            track.scrollTo({ left: track.clientWidth * Math.max(0, index - 1), behavior: "smooth" });
        });

        next.addEventListener("click", () => {
            const index = getIndex();
            track.scrollTo({ left: track.clientWidth * Math.min(slides.length - 1, index + 1), behavior: "smooth" });
        });

        track.addEventListener("scroll", update, { passive: true });
        window.addEventListener("resize", applyUpdate, { passive: true });
        applyUpdate();

        window.requestAnimationFrame(() => {
            heroShell.classList.add("is-nav-ready");
            window.requestAnimationFrame(() => {
                heroCarousel.classList.add("is-ready");
                applyUpdate();
            });
        });
    })();

    (() => {
        const navItems = Array.from(document.querySelectorAll("[data-section-nav-item]"));
        const sections = navItems
            .map((item) => {
                const id = item.getAttribute("data-target");
                const section = id ? document.getElementById(id) : null;
                return section ? { item, section } : null;
            })
            .filter(Boolean);

        if (!sections.length) {
            return;
        }

        const setActiveSection = (id) => {
            sections.forEach(({ item, section }) => {
                item.classList.toggle("is-active", section.id === id);
            });
        };

        const observer = new IntersectionObserver((entries) => {
            const visible = entries
                .filter((entry) => entry.isIntersecting)
                .sort((left, right) => right.intersectionRatio - left.intersectionRatio);

            if (visible.length) {
                setActiveSection(visible[0].target.id);
            }
        }, {
            threshold: [0.2, 0.35, 0.55, 0.75],
            rootMargin: "-18% 0px -48% 0px"
        });

        sections.forEach(({ section }) => observer.observe(section));
        setActiveSection(sections[0].section.id);
    })();

    (() => {
        const separators = Array.from(document.querySelectorAll("[data-separator]"));
        if (!separators.length) {
            return;
        }

        if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
            separators.forEach((separator) => separator.classList.add("is-visible"));
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("is-visible");
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.35,
            rootMargin: "0px 0px -8% 0px"
        });

        separators.forEach((separator) => observer.observe(separator));
    })();
</script>
</body>
</html>
