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
];

$heroNavItems = [
    ['href' => '#vision', 'label' => 'Vision'],
    ['href' => '#our-party', 'label' => 'Our Party'],
    ['href' => '#leadership', 'label' => 'Leadership'],
    ['href' => 'projects.php', 'label' => 'Projects'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="googlebot" content="index, follow">
    <meta name="bingbot" content="index, follow">
    <meta name="theme-color" content="#9a4a41">
    
    <!-- Primary SEO Meta Tags -->
    <title>Sri Lanka People's Front | ශ්‍රී ලංකා පොදුජන පෙරමුණ | இலங்கை பொதுஜன முன்னணி - SLPP Podujana Peramuna</title>
    <meta name="title" content="Sri Lanka People's Front | ශ්‍රී ලංකා පොදුජන පෙරමුණ | இலங்கை பொதுஜன முன்னணி - SLPP Podujana Peramuna">
    <meta name="description" content="Official website of Sri Lanka People's Front (SLPP / Podujana Peramuna) - ශ්‍රී ලංකා පොදුජන පෙරමුණ - இலங்கை பொதுஜன முன்னணி. Leadership, vision, and initiatives for a stronger Sri Lanka. Contact: +94 112 888 484 | Headquarters: 1316 Nelum Mawatha, Jayanthipura, Battaramulla.">
    <meta name="keywords" content="Sri Lanka People's Front, SLPP, Podujana Peramuna, ශ්‍රී ලංකා පොදුජන පෙරමුණ, இலங்கை பொதுஜன முன்னணி, Sri Lanka politics, political party, leadership, vision, development, SLPP Sri Lanka, Podujana Peramuna party">
    <meta name="author" content="Sri Lanka People's Front">
    <meta name="language" content="en, si, ta">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="https://slpp.lk/">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="images/slpp.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="images/slpp.ico">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://slpp.lk/">
    <meta property="og:title" content="Sri Lanka People's Front | ශ්‍රී ලංකා පොදුජන පෙරමුණ | இலங்கை பொதுஜன முன்னணி">
    <meta property="og:description" content="Official website of Sri Lanka People's Front (SLPP / Podujana Peramuna) - Leadership, vision, and initiatives for a stronger Sri Lanka. Contact: +94 112 888 484 | Headquarters: 1316 Nelum Mawatha, Jayanthipura, Battaramulla.">
    <meta property="og:image" content="https://slpp.lk/images/testlogo.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Sri Lanka People's Front Logo">
    <meta property="og:locale" content="en_LK">
    <meta property="og:locale:alternate" content="si_LK">
    <meta property="og:locale:alternate" content="ta_LK">
    <meta property="og:site_name" content="Sri Lanka People's Front">
    
    <!-- Twitter / X -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="https://slpp.lk/">
    <meta name="twitter:title" content="Sri Lanka People's Front | ශ්‍රී ලංකා පොදුජන පෙරමුණ | இலங்கை பொதுஜன முன்னணி">
    <meta name="twitter:description" content="Official website of Sri Lanka People's Front (SLPP / Podujana Peramuna) - Leadership, vision, and initiatives for a stronger Sri Lanka.">
    <meta name="twitter:image" content="https://slpp.lk/images/testlogo.png">
    <meta name="twitter:image:alt" content="Sri Lanka People's Front Logo">
    
    <!-- LinkedIn -->
    <meta property="linkedin:company" content="https://slpp.lk/">
    
    <!-- WhatsApp -->
    <meta property="og:whatsapp" content="https://slpp.lk/">
    
    <!-- Additional SEO -->
    <meta name="format-detection" content="telephone=yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    
    <!-- Structured Data (JSON-LD) for Organization -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "PoliticalParty",
        "name": "Sri Lanka People's Front",
        "alternateName": [
            "SLPP",
            "Podujana Peramuna",
            "ශ්‍රී ලංකා පොදුජන පෙරමුණ",
            "இலங்கை பொதுஜன முன்னணி"
        ],
        "url": "https://slpp.lk/",
        "logo": "https://slpp.lk/images/testlogo.png",
        "sameAs": [
            "https://www.youtube.com/@Slpp_press",
            "https://www.instagram.com/podujanaparty",
            "https://x.com/podujanaparty",
            "https://web.facebook.com/PodujanaParty",
            "https://en.wikipedia.org/wiki/Sri_Lanka_Podujana_Peramuna"
        ],
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+94 112 888 484",
            "contactType": "customer service"
        },
        "address": {
            "@type": "PostalAddress",
            "streetAddress": "1316 Nelum Mawatha, Jayanthipura",
            "addressLocality": "Battaramulla",
            "addressCountry": "LK"
        },
        "foundingDate": "2016",
        "description": "Official website of Sri Lanka People's Front (SLPP / Podujana Peramuna) - A political organization dedicated to building a stronger Sri Lanka through visionary leadership and development initiatives."
    }
    </script>
    
    <!-- Additional Structured Data for Local Business -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "Sri Lanka People's Front",
        "url": "https://slpp.lk/",
        "logo": "https://slpp.lk/images/testlogo.png",
        "sameAs": [
            "https://www.youtube.com/@Slpp_press",
            "https://www.instagram.com/podujanaparty",
            "https://x.com/podujanaparty",
            "https://web.facebook.com/PodujanaParty",
            "https://en.wikipedia.org/wiki/Sri_Lanka_Podujana_Peramuna"
        ],
        "telephone": "+94 112 888 484",
        "address": {
            "@type": "PostalAddress",
            "streetAddress": "1316 Nelum Mawatha, Jayanthipura",
            "addressLocality": "Battaramulla",
            "addressCountry": "LK"
        }
    }
    </script>
    
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
            --hero-ui-font: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
        }

        html {
            scroll-behavior: smooth;
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
        }

        @media (max-width: 768px) {
            html {
                scroll-behavior: auto;
            }
            
            body {
                -webkit-overflow-scrolling: touch;
                overscroll-behavior-y: contain;
                scroll-padding-top: 0;
            }
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background:
                radial-gradient(circle at top left, rgba(219, 137, 123, 0.22), transparent 24%),
                radial-gradient(circle at top right, rgba(255, 198, 170, 0.1), transparent 28%),
                linear-gradient(180deg, #9b4a42 0%, #a94f46 20%, #8e4139 52%, #7f382f 78%, #6e2d26 100%);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .top-hero {
            position: relative;
            overflow: hidden;
            padding: 0 0 18px;
            background:
                radial-gradient(circle at left top, rgba(210, 115, 100, 0.32), transparent 26%),
                radial-gradient(circle at right top, rgba(255, 186, 156, 0.12), transparent 28%),
                linear-gradient(145deg, #8f433b 0%, #a94f46 42%, #7d352e 100%);
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
                radial-gradient(circle at top left, rgba(255, 219, 199, 0.08), transparent 26%),
                radial-gradient(circle at bottom right, rgba(84, 26, 22, 0.2), transparent 28%),
                linear-gradient(145deg, rgba(141, 64, 56, 0.9), rgba(166, 78, 69, 0.86), rgba(125, 53, 46, 0.9));
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
            width: 190px;
            max-width: min(28vw, 190px);
            height: auto;
            flex-shrink: 0;
            object-fit: contain;
            position: relative;
            transform-origin: 50% 50%;
            filter:
                drop-shadow(0 8px 20px rgba(61, 18, 17, 0.18))
                drop-shadow(0 2px 6px rgba(244, 210, 122, 0.08));
            transform: rotateX(0deg) rotateY(0deg) scale(1);
            opacity: 1;
            image-rendering: auto;
            image-rendering: high-quality;
            -webkit-font-smoothing: antialiased;
            will-change: transform, opacity, filter;
            transition: opacity 220ms cubic-bezier(.2, .8, .2, 1), filter 220ms cubic-bezier(.2, .8, .2, 1), transform 220ms cubic-bezier(.2, .8, .2, 1);
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
            gap: 36px;
            flex: 1 1 auto;
            min-width: 0;
            margin-left: 0;
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
            font-family: var(--hero-ui-font);
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
            border-color: rgba(255, 240, 214, 0.28);
            background: rgba(255,255,255,0.13);
        }

        .hero-action:hover,
        .hero-action:focus-visible {
            box-shadow: 0 22px 38px rgba(61, 18, 17, 0.18);
        }

        .hero-social {
            display: flex;
            align-items: center;
            gap: 0;
            margin: 0 8px;
        }

        .hero-social .social-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            color: var(--hero-ink);
            border-radius: 999px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255, 240, 214, 0.12);
            text-decoration: none;
            transition: all 220ms cubic-bezier(.2, .8, .2, 1);
            position: relative;
        }

        .hero-social .social-icon svg {
            width: 18px;
            height: 18px;
        }

        .hero-social .social-icon + .social-icon::before {
            content: "";
            position: absolute;
            left: -10px;
            top: 50%;
            width: 1px;
            height: 20px;
            transform: translateY(-50%);
            background: linear-gradient(180deg,
                transparent 0%,
                rgba(255, 240, 214, 0.25) 30%,
                rgba(255, 240, 214, 0.25) 70%,
                transparent 100%);
        }

        .hero-social .social-icon:hover {
            background: rgba(255,255,255,0.12);
            border-color: rgba(255, 240, 214, 0.28);
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 8px 20px rgba(61, 18, 17, 0.12);
        }

        .hero-phone {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--hero-ink);
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 999px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255, 240, 214, 0.12);
            font-size: 0.9rem;
            font-weight: 500;
            white-space: nowrap;
            transition: all 220ms cubic-bezier(.2, .8, .2, 1);
        }

        .hero-phone svg {
            width: 18px;
            height: 18px;
        }

        .hero-phone span {
            font-size: 0.88rem;
            letter-spacing: 0.02em;
        }

        .hero-phone:hover {
            background: rgba(255,255,255,0.12);
            border-color: rgba(255, 240, 214, 0.28);
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 8px 20px rgba(61, 18, 17, 0.12);
        }

        @media (min-width: 861px) {
            .hero-nav {
                gap: 24px;
                padding: 16px 24px;
                border-radius: 28px;
                background:
                    radial-gradient(circle at 14% 18%, rgba(255, 238, 207, 0.1), transparent 18%),
                    linear-gradient(145deg, rgba(255,255,255,0.12), rgba(255,255,255,0.04)),
                    rgba(255,255,255,0.06);
                border-color: rgba(255, 237, 208, 0.1);
                box-shadow:
                    inset 0 1px 0 rgba(255,255,255,0.16),
                    0 22px 44px rgba(79, 23, 21, 0.14);
            }

            .hero-nav::before {
                content: "";
                display: block;
                position: absolute;
                inset: 8px;
                border-radius: 20px;
                border: 1px solid rgba(255,255,255,0.035);
                pointer-events: none;
            }

            .hero-brand {
                gap: 0;
                padding: 4px 0;
                border-radius: 0;
                background: transparent;
                border: 0;
                box-shadow: none;
            }

            .hero-brand-logo {
                width: 210px;
                max-width: min(18vw, 210px);
                filter:
                    drop-shadow(0 10px 24px rgba(61, 18, 17, 0.20))
                    drop-shadow(0 3px 8px rgba(244, 210, 122, 0.10));
                image-rendering: auto;
                image-rendering: high-quality;
            }

            .hero-brand:hover .hero-brand-logo {
                transform: rotateX(0deg) rotateY(0deg) scale(1.04);
                filter:
                    drop-shadow(0 14px 32px rgba(61, 18, 17, 0.24))
                    drop-shadow(0 4px 12px rgba(244, 210, 122, 0.14));
            }

            .hero-nav-panel {
                flex: 0 1 auto;
                margin-left: auto;
                gap: 0;
            }

            .hero-links {
                gap: 0;
                padding: 7px 12px;
                margin: 0;
                border-radius: 999px;
                background:
                    linear-gradient(145deg, rgba(255,255,255,0.1), rgba(255,255,255,0.03)),
                    rgba(112, 41, 37, 0.12);
                border: 1px solid rgba(255, 240, 214, 0.08);
                box-shadow: inset 0 1px 0 rgba(255,255,255,0.1);
            }

            .hero-links a {
                position: relative;
                min-height: 48px;
                padding: 14px 24px;
                border-radius: 18px;
                background: transparent;
                border: 1px solid transparent;
                font-size: 0.95rem;
                font-weight: 600;
                letter-spacing: 0.015em;
                box-shadow: none;
                overflow: hidden;
            }

            .hero-links a + a::before {
                content: "";
                position: absolute;
                left: 0;
                top: 50%;
                width: 1px;
                height: 26px;
                transform: translate(-50%, -50%);
                background: linear-gradient(180deg,
                    transparent 0%,
                    rgba(255, 240, 214, 0.18) 15%,
                    rgba(255, 240, 214, 0.52) 50%,
                    rgba(255, 240, 214, 0.18) 85%,
                    transparent 100%);
                opacity: 1;
                transition: opacity 220ms cubic-bezier(.2, .8, .2, 1);
            }

            .hero-links a::after {
                content: "";
                position: absolute;
                left: 16px;
                right: 16px;
                bottom: 8px;
                height: 1px;
                background: linear-gradient(90deg, rgba(244, 214, 143, 0), rgba(255, 244, 220, 0.9), rgba(244, 214, 143, 0));
                opacity: 0;
                transform: scaleX(0.68);
                transform-origin: center;
                transition: opacity 260ms cubic-bezier(.2, .8, .2, 1), transform 260ms cubic-bezier(.2, .8, .2, 1);
            }

            .hero-links a:hover,
            .hero-links a:focus-visible,
            .hero-links a.is-active {
                transform: translate3d(0, -2px, 0) scale(1.02);
                border-color: transparent;
                background: rgba(255,255,255,0.08);
                box-shadow: none;
            }

            .hero-links a:hover + a::before,
            .hero-links a:focus-visible + a::before,
            .hero-links a.is-active + a::before {
                opacity: 0.28;
            }

            .hero-links a:hover::before,
            .hero-links a:focus-visible::before,
            .hero-links a.is-active::before {
                opacity: 0.28;
            }

            .hero-links a:hover::after,
            .hero-links a:focus-visible::after,
            .hero-links a.is-active::after {
                opacity: 1;
                transform: scaleX(1);
            }

            .hero-nav-panel {
                gap: 42px;
            }

            .hero-social {
                margin: 0 12px 0 20px;
                gap: 0;
                position: relative;
            }

            .hero-social::before {
                content: "";
                position: absolute;
                left: -16px;
                top: 50%;
                width: 1px;
                height: 28px;
                transform: translateY(-50%);
                background: linear-gradient(180deg,
                    transparent 0%,
                    rgba(255, 240, 214, 0.32) 25%,
                    rgba(255, 240, 214, 0.32) 75%,
                    transparent 100%);
            }

            .hero-social .social-icon {
                width: 38px;
                height: 38px;
                background: transparent;
                border: 1px solid transparent;
                margin: 0 4px;
            }

            .hero-social .social-icon svg {
                width: 20px;
                height: 20px;
            }

            .hero-social .social-icon + .social-icon::before {
                left: -8px;
                height: 24px;
                background: linear-gradient(180deg,
                    transparent 0%,
                    rgba(255, 240, 214, 0.35) 30%,
                    rgba(255, 240, 214, 0.35) 70%,
                    transparent 100%);
            }

            .hero-social .social-icon:hover {
                background: rgba(255,255,255,0.08);
                border-color: rgba(255, 240, 214, 0.2);
            }

            .hero-phone {
                background: transparent;
                border: 1px solid transparent;
                padding: 8px 16px;
            }

            .hero-phone svg {
                width: 20px;
                height: 20px;
            }

            .hero-phone span {
                font-size: 0.92rem;
            }

            .hero-phone:hover {
                background: rgba(255,255,255,0.08);
                border-color: rgba(255, 240, 214, 0.2);
            }
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
                radial-gradient(circle at top left, rgba(206, 108, 94, 0.2), transparent 24%),
                radial-gradient(circle at top right, rgba(255, 192, 165, 0.08), transparent 28%),
                radial-gradient(circle at bottom right, rgba(88, 29, 24, 0.18), transparent 26%),
                linear-gradient(180deg, rgba(143, 67, 61, 0.98) 0%, rgba(132, 58, 52, 0.96) 24%, rgba(122, 51, 46, 0.95) 58%, rgba(103, 40, 35, 0.96) 100%);
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
                radial-gradient(circle at top left, rgba(204, 112, 96, 0.14), transparent 24%),
                radial-gradient(circle at bottom right, rgba(80, 26, 22, 0.24), transparent 28%),
                linear-gradient(180deg, rgba(110, 42, 37, 0.98), rgba(74, 24, 21, 0.99));
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
            border: 1px solid rgba(255, 227, 216, 0.10);
            border-bottom: 0;
            font-family: "Aptos", "Segoe UI", "Helvetica Neue", Arial, sans-serif;
            background:
                linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.012)),
                linear-gradient(135deg, rgba(122, 46, 41, 0.52), rgba(61, 19, 17, 0.68));
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
                radial-gradient(circle at top left, rgba(228, 191, 109, 0.08), transparent 28%),
                radial-gradient(circle at bottom right, rgba(244, 210, 122, 0.06), transparent 28%),
                linear-gradient(110deg, rgba(255,255,255,0.06), rgba(255,255,255,0) 48%);
            pointer-events: none;
        }

        .footer-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) repeat(3, minmax(0, 1fr));
            gap: 28px;
            align-items: start;
        }

        .footer-brand,
        .footer-card {
            min-width: 0;
            padding: 26px 26px 24px;
            border-radius: 28px;
            background:
                linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02)),
                rgba(76, 25, 24, 0.12);
            border: 1px solid rgba(255, 239, 214, 0.06);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.08),
                0 12px 28px rgba(28, 8, 8, 0.10);
            transition:
                transform 220ms cubic-bezier(.2, .8, .2, 1),
                border-color 220ms cubic-bezier(.2, .8, .2, 1),
                box-shadow 220ms cubic-bezier(.2, .8, .2, 1);
        }

        .footer-card:hover {
            transform: translateY(-4px);
            border-color: rgba(255, 239, 214, 0.10);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.10),
                0 18px 36px rgba(28, 8, 8, 0.14);
        }

        .footer-brand {
            display: grid;
            gap: 18px;
        }

        .footer-logo-row {
            display: inline-flex;
            align-items: center;
            gap: 16px;
        }

        .footer-logo {
            width: 72px;
            height: 72px;
            object-fit: contain;
            flex-shrink: 0;
            filter:
                drop-shadow(0 10px 24px rgba(29, 8, 8, 0.22))
                drop-shadow(0 4px 10px rgba(228, 191, 109, 0.12));
        }

        .footer-brand h2,
        .footer-card h3 {
            margin: 0;
            color: var(--hero-ink);
        }

        .footer-brand h2 {
            font-size: clamp(1.1rem, 1.8vw, 1.5rem);
            line-height: 1.08;
            letter-spacing: -0.02em;
            font-family: inherit;
            font-weight: 700;
        }

        .party-name-sub {
            margin: 4px 0 0;
            font-size: 0.95rem;
            font-weight: 500;
            color: rgba(228, 191, 109, 0.88);
        }

        .party-name-tamil {
            margin: 2px 0 0;
            font-size: 0.88rem;
            font-weight: 400;
            color: rgba(255, 243, 223, 0.7);
        }

        .footer-brand p,
        .footer-card p,
        .footer-contact-link,
        .footer-bottom,
        .footer-link-list a,
        .footer-card h3,
        .footer-contact-label,
        .footer-social-link,
        .footer-bottom-links a {
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
            transform: translateX(4px);
        }

        .footer-contact-list li {
            display: grid;
            gap: 6px;
        }

        .footer-contact-label {
            font-size: 0.78rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: rgba(228, 191, 109, 0.68);
            font-weight: 600;
        }

        .footer-social-list {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .footer-social-link {
            display: grid;
            grid-template-columns: 22px minmax(0, 1fr);
            align-items: center;
            column-gap: 14px;
            min-height: 52px;
            padding: 0 16px;
            text-decoration: none;
            border-radius: 18px;
            background:
                linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0.04)),
                rgba(255,255,255,0.03);
            border: 1px solid rgba(255, 238, 206, 0.14);
            color: var(--hero-ink);
            transition:
                transform 220ms cubic-bezier(.2, .8, .2, 1),
                border-color 220ms cubic-bezier(.2, .8, .2, 1),
                background 220ms cubic-bezier(.2, .8, .2, 1),
                box-shadow 220ms cubic-bezier(.2, .8, .2, 1);
        }

        .footer-social-link:hover,
        .footer-social-link:focus-visible {
            transform: translateY(-3px);
            border-color: rgba(255, 238, 206, 0.18);
            background:
                linear-gradient(180deg, rgba(255,255,255,0.1), rgba(255,255,255,0.04)),
                rgba(255,255,255,0.04);
            box-shadow: 0 16px 28px rgba(33, 10, 9, 0.18);
        }

        .footer-social-icon {
            width: 20px;
            height: 20px;
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
            gap: 16px;
            margin-top: 28px;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 236, 205, 0.12);
            font-size: 0.9rem;
        }

        .footer-bottom-links {
            display: inline-flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 16px;
        }

        .footer-bottom-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 40px;
            padding: 8px 18px;
            color: rgba(255, 243, 223, 0.92);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            letter-spacing: 0.02em;
            border-radius: 999px;
            background:
                linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0.04)),
                rgba(255,255,255,0.04);
            border: 1px solid rgba(255, 238, 206, 0.18);
            transition: all 220ms cubic-bezier(.2, .8, .2, 1);
        }

        .footer-bottom-links a svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        .footer-bottom-links a:hover,
        .footer-bottom-links a:focus-visible {
            color: var(--hero-ink);
            background:
                linear-gradient(180deg, rgba(255,255,255,0.14), rgba(255,255,255,0.06)),
                rgba(255,255,255,0.06);
            border-color: rgba(255, 238, 206, 0.28);
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(33, 10, 9, 0.18);
        }

        .back-to-top-button {
            position: fixed;
            bottom: 32px;
            right: 32px;
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            border-radius: 999px;
            background:
                linear-gradient(145deg, rgba(228, 191, 109, 0.92), rgba(184, 90, 77, 0.88)),
                rgba(61, 18, 17, 0.85);
            border: 1px solid rgba(255, 238, 206, 0.24);
            box-shadow:
                0 8px 24px rgba(29, 8, 8, 0.28),
                0 4px 12px rgba(228, 191, 109, 0.20),
                inset 0 1px 0 rgba(255, 255, 255, 0.20);
            color: white;
            opacity: 0;
            visibility: hidden;
            transform: translateY(20px) scale(0.9);
            transition:
                opacity 320ms cubic-bezier(.2, .8, .2, 1),
                visibility 320ms cubic-bezier(.2, .8, .2, 1),
                transform 320ms cubic-bezier(.2, .8, .2, 1),
                box-shadow 320ms cubic-bezier(.2, .8, .2, 1);
            z-index: 1000;
        }

        .back-to-top-button.is-visible {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }

        .back-to-top-button:hover {
            background:
                linear-gradient(145deg, rgba(244, 210, 122, 0.96), rgba(200, 100, 85, 0.92)),
                rgba(71, 20, 18, 0.90);
            border-color: rgba(255, 238, 206, 0.32);
            box-shadow:
                0 12px 32px rgba(29, 8, 8, 0.36),
                0 6px 18px rgba(228, 191, 109, 0.28),
                inset 0 1px 0 rgba(255, 255, 255, 0.25);
            transform: translateY(-4px) scale(1.08);
        }

        .back-to-top-button svg {
            width: 28px;
            height: 28px;
        }

        @media (max-width: 768px) {
            .back-to-top-button {
                bottom: 24px;
                right: 24px;
                width: 50px;
                height: 50px;
            }

            .back-to-top-button svg {
                width: 24px;
                height: 24px;
            }
        }

        .page-section-chunk {
            content-visibility: auto;
            contain: layout paint style;
            contain-intrinsic-size: 960px;
        }

        @media (max-width: 768px) {
            .page-section-chunk {
                content-visibility: visible;
                contain: layout;
            }

            .hero-carousel,
            .hero-track,
            .hero-slide {
                will-change: transform;
                transform: translateZ(0);
            }

            .hero-media {
                will-change: transform;
                transform: translateZ(0);
            }

            .vision-shell,
            .party-shell,
            .leadership-shell {
                will-change: transform;
                transform: translateZ(0);
            }

            .section-separator-copy::after,
            .hero-nav::before {
                display: none;
            }

            .hero-shell {
                will-change: transform;
            }

            .hero-brand-logo {
                animation: none;
            }

            .map-helper-icon,
            .project-map-pin::before {
                animation: none;
            }
        }

        @media (min-width: 769px) {
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
        }

        .section-tree-nav {
            position: fixed;
            top: 50%;
            right: max(70px, env(safe-area-inset-right, 0px) + 70px);
            transform: translateY(-50%);
            z-index: 100;
            display: grid;
            gap: 0;
            width: auto;
            max-width: 0;
            padding: 0;
            overflow: hidden;
            transition: max-width 0.4s cubic-bezier(0.4, 0, 0.2, 1), padding 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0;
            pointer-events: none;
        }

        .section-tree-nav.is-expanded {
            max-width: clamp(280px, 25vw, 340px);
            padding: 28px 24px 28px 24px;
            opacity: 1;
            pointer-events: auto;
        }

        @media (max-width: 768px) {
            .section-tree-nav {
                display: none;
            }
        }

        .section-tree-nav-toggle {
            position: fixed;
            top: 50%;
            right: max(16px, env(safe-area-inset-right, 0px) + 16px);
            transform: translateY(-50%);
            width: 52px;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(184, 90, 77, 0.95), rgba(143, 70, 61, 0.98));
            border: 3px solid rgba(228, 191, 109, 0.9);
            border-radius: 50%;
            cursor: pointer;
            z-index: 1001;
            box-shadow: 0 6px 24px rgba(61, 18, 17, 0.4);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), background 0.3s, border-color 0.3s, box-shadow 0.3s;
            -webkit-tap-highlight-color: transparent;
            pointer-events: auto !important;
            touch-action: manipulation;
        }

        .section-tree-nav-toggle:hover {
            transform: translateY(-50%) scale(1.08);
            background: linear-gradient(135deg, rgba(184, 90, 77, 1), rgba(143, 70, 61, 1));
            border-color: #fff5d7;
            box-shadow: 0 8px 32px rgba(61, 18, 17, 0.5);
        }

        .section-tree-nav-toggle:active {
            transform: translateY(-50%) scale(0.95);
        }

        @media (max-width: 768px) {
            .section-tree-nav-toggle {
                display: none;
            }
        }

        .section-tree-nav-toggle svg {
            width: 24px;
            height: 24px;
            color: #fff5d7;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            pointer-events: none;
            user-select: none;
            -webkit-user-select: none;
        }

        .section-tree-nav.is-expanded .section-tree-nav-toggle svg {
            transform: rotate(45deg);
        }

        @media (min-width: 1400px) {
            .section-tree-nav {
                right: max(100px, env(safe-area-inset-right, 0px) + 100px);
            }
        }

        .section-tree-nav::before {
            display: none;
        }

        .section-tree-nav::after {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: 20px;
            background: linear-gradient(135deg, rgba(184, 90, 77, 0.92), rgba(143, 70, 61, 0.88));
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            box-shadow: 0 12px 40px rgba(61, 18, 17, 0.45);
            border: 2px solid rgba(228, 191, 109, 0.6);
            pointer-events: none;
        }

        .section-tree-nav-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
            position: relative;
            z-index: 1;
        }

        .section-tree-nav-item {
            position: relative;
            width: 100%;
        }

        .section-tree-nav-link {
            display: block;
            width: 100%;
            color: inherit;
            text-decoration: none;
            pointer-events: auto;
        }

        .section-tree-nav-dot {
            display: none;
        }

        .section-tree-nav-label {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 56px;
            width: 100%;
            padding: 18px 28px;
            border-radius: 14px;
            background: rgba(255, 244, 221, 0.12);
            border: 2px solid rgba(228, 191, 109, 0.5);
            color: #fff5d7;
            font-size: 1rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            white-space: normal;
            text-align: center;
            word-wrap: break-word;
            opacity: 1;
            transform: translateX(0);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 700;
            line-height: 1.3;
            font-family: var(--hero-ui-font);
        }

        .section-tree-nav-link:hover .section-tree-nav-label,
        .section-tree-nav-link:focus-visible .section-tree-nav-label,
        .section-tree-nav-item.is-active .section-tree-nav-label {
            background: linear-gradient(135deg, rgba(228, 191, 109, 0.95), rgba(228, 191, 109, 0.88));
            border-color: #fff5d7;
            color: #8f463d;
            transform: translateX(-4px);
            box-shadow: 0 8px 24px rgba(61, 18, 17, 0.35);
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
            font-family: var(--hero-ui-font);
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
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                grid-template-areas:
                    "brand toggle"
                    "panel panel";
                align-items: center;
                padding: 14px;
                border-radius: 20px;
                gap: 12px 12px;
            }

            .hero-brand {
                grid-area: brand;
                justify-content: flex-start;
            }

            .hero-menu-toggle {
                grid-area: toggle;
                display: inline-flex;
                margin-left: auto;
                justify-self: end;
            }

            .hero-nav-panel {
                grid-area: panel;
                width: 100%;
                flex-basis: 100%;
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                grid-template-areas:
                    "social phone"
                    "links links";
                align-items: center;
                gap: 10px 14px;
                max-height: none;
                overflow: visible;
                opacity: 1;
                padding-top: 0;
                margin-left: 0;
                transition:
                    opacity 220ms ease,
                    padding-top 220ms ease;
            }

            .hero-nav.is-menu-open .hero-nav-panel {
                padding-top: 0;
            }

            .hero-nav.is-menu-open .hero-links a,
            .hero-nav.is-menu-open .hero-action {
                opacity: 1;
                transform: translate3d(0, 0, 0) scale(1);
                filter: blur(0);
            }

            .hero-links {
                display: none;
                grid-area: links;
                width: 100%;
                justify-content: stretch;
                flex-direction: column;
                margin: 0;
                padding: 8px;
                border-radius: 18px;
                background: rgba(255,255,255,0.06);
                border: 1px solid rgba(255, 240, 214, 0.12);
            }

            .hero-nav.is-menu-open .hero-links {
                display: flex;
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

            .hero-social,
            .hero-phone {
                display: inline-flex;
                width: auto;
            }

            .hero-social {
                grid-area: social;
                flex: 1 1 auto;
                justify-content: flex-start;
                margin: 0;
                min-width: 0;
            }

            .hero-phone {
                grid-area: phone;
                flex: 0 0 auto;
                justify-self: end;
                white-space: nowrap;
                padding: 8px 14px;
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
                gap: 20px;
            }

            .footer-brand,
            .footer-card {
                padding: 20px 18px;
            }

            .footer-logo {
                width: 56px;
                height: 56px;
            }

            .footer-bottom {
                flex-direction: column;
                gap: 14px;
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

            .hero-social {
                gap: 0;
                min-width: 0;
            }

            .hero-social .social-icon {
                width: 34px;
                height: 34px;
            }

            .hero-social .social-icon svg {
                width: 16px;
                height: 16px;
            }

            .hero-phone {
                font-size: 0.8rem;
                padding: 7px 12px;
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
                    <?php foreach ($heroNavItems as $index => $item): ?>
                        <a href="<?php echo htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>" style="--nav-index: <?php echo $index; ?>;">
                            <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="hero-social">
                    <a href="https://www.youtube.com/@Slpp_press" class="social-icon" aria-label="YouTube" target="_blank" rel="noopener noreferrer">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    </a>
                    <a href="https://www.instagram.com/podujanaparty" class="social-icon" aria-label="Instagram" target="_blank" rel="noopener noreferrer">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>
                    </a>
                    <a href="https://x.com/podujanaparty" class="social-icon" aria-label="X (Twitter)" target="_blank" rel="noopener noreferrer">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a href="https://web.facebook.com/PodujanaParty" class="social-icon" aria-label="Facebook" target="_blank" rel="noopener noreferrer">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                </div>
                <a href="tel:0112888484" class="hero-phone" aria-label="Call 0112 888 484">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 0 0-1.01.24l-1.57 1.97c-2.83-1.49-5.15-3.8-6.62-6.63l1.97-1.57c.3-.3.4-.74.24-1.16-.37-1.11-.56-2.3-.56-3.53 0-.54-.45-.99-.99-.99H4.19C3.65 3.18 3.18 3.65 3.18 4.19c0 9.27 7.55 16.82 16.82 16.82.54 0 .99-.45.99-.99v-3.65c0-.54-.45-.99-.99-.99z"/></svg>
                    <span>0112 888 484</span>
                </a>
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



<button class="section-tree-nav-toggle" type="button" aria-label="Toggle navigation menu" aria-expanded="false">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <line x1="5" y1="12" x2="19" y2="12"></line>
    </svg>
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

</section>

<footer class="site-footer">
    <div class="footer-shell">
        <div class="footer-grid">
            <section class="footer-brand" aria-label="Site footer brand">
                <div class="footer-logo-row">
                    <img class="footer-logo" src="images/testlogo.png" alt="Logo">
                    <div>
                        <h2>ශ්‍රී ලංකා පොදුජන පෙරමුණ</h2>
                        <p class="party-name-sub">Sri Lanka People's Front</p>
                        <p class="party-name-tamil">இலங்கை பொதுஜன முன்னணி</p>
                    </div>
                </div>
                <p>1316 Nelum Mawatha, Jayanthipura, Battaramulla</p>
            </section>

            <section class="footer-card" aria-label="Quick links">
                <h3>Quick Links</h3>
                <ul class="footer-link-list">
                    <li><a href="#top">Home</a></li>
                    <li><a href="#vision">Vision</a></li>
                    <li><a href="#our-party">Our Party</a></li>
                    <li><a href="#leadership">Leadership</a></li>
                    <li><a href="projects.php">Projects</a></li>
                </ul>
            </section>

            <section class="footer-card" aria-label="Contact details">
                <h3>Contact</h3>
                <ul class="footer-contact-list">
                    <li>
                        <span class="footer-contact-label">Phone</span>
                        <a class="footer-contact-link" href="tel:0112888484">0112 888 484</a>
                    </li>
                    <li>
                        <span class="footer-contact-label">Email</span>
                        <a class="footer-contact-link" href="mailto:info@slppsrilanka.lk">info@slppsrilanka.lk</a>
                    </li>
                    <li>
                        <span class="footer-contact-label">Headquarters</span>
                        <p>1316 Nelum Mawatha, Jayanthipura, Battaramulla</p>
                    </li>
                </ul>
            </section>

            <section class="footer-card" aria-label="Social media links">
                <h3>Follow Us</h3>
                <div class="footer-social-list">
                    <a class="footer-social-link" href="https://www.youtube.com/@Slpp_press" aria-label="YouTube" target="_blank" rel="noopener noreferrer">
                        <svg class="footer-social-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                        <span>YouTube</span>
                    </a>
                    <a class="footer-social-link" href="https://www.instagram.com/podujanaparty" aria-label="Instagram" target="_blank" rel="noopener noreferrer">
                        <svg class="footer-social-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>
                        <span>Instagram</span>
                    </a>
                    <a class="footer-social-link" href="https://x.com/podujanaparty" aria-label="X (Twitter)" target="_blank" rel="noopener noreferrer">
                        <svg class="footer-social-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        <span>X</span>
                    </a>
                    <a class="footer-social-link" href="https://web.facebook.com/PodujanaParty" aria-label="Facebook" target="_blank" rel="noopener noreferrer">
                        <svg class="footer-social-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        <span>Facebook</span>
                    </a>
                </div>
            </section>
        </div>

        <div class="footer-bottom">
            <span>&copy; <?php echo date('Y'); ?> Sri Lanka Podujana Peramuna. All rights reserved.</span>
            <div class="footer-bottom-links">
                <a href="https://en.wikipedia.org/wiki/Sri_Lanka_Podujana_Peramuna" target="_blank" rel="noopener noreferrer" aria-label="View on Wikipedia">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm6.93 6h-2.95c-.32-1.25-.78-2.45-1.38-3.56 1.84.63 3.37 1.91 4.33 3.56zM12 4.04c.83 1.2 1.48 2.53 1.91 3.96h-3.82c.43-1.43 1.08-2.76 1.91-3.96zM4.26 14C4.1 13.36 4 12.69 4 12s.1-1.36.26-2h3.38c-.08.66-.14 1.32-.14 2 0 .68.06 1.34.14 2H4.26zm.82 2h2.95c.32 1.25.78 2.45 1.38 3.56-1.84-.63-3.37-1.9-4.33-3.56zm2.95-8H5.08c.96-1.66 2.49-2.93 4.33-3.56C8.81 5.55 8.35 6.75 8.03 8zM12 19.96c-.83-1.2-1.48-2.53-1.91-3.96h3.82c-.43 1.43-1.08 2.76-1.91 3.96zM14.34 14H9.66c-.09-.66-.16-1.32-.16-2 0-.68.07-1.35.16-2h4.68c.09.65.16 1.32.16 2 0 .68-.07 1.34-.16 2zm.25 5.56c.6-1.11 1.06-2.31 1.38-3.56h2.95c-.96 1.65-2.49 2.93-4.33 3.56zM16.36 14c.08-.66.14-1.32.14-2 0-.68-.06-1.34-.14-2h3.38c.16.64.26 1.31.26 2s-.1 1.36-.26 2h-3.38z"/></svg>
                    <span>Wikipedia</span>
                </a>
            </div>
        </div>
    </div>
</footer>

<a href="#top" class="back-to-top-button" aria-label="Back to top">
    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7.41 15.41L12 10.83l4.59 4.58L18 14l-6-6-6 6z"/></svg>
</a>

<script>
    (() => {
        let scrollTimer = 0;
        let ticking = false;
        const scrollingClass = "is-scrolling";
        const isMobile = window.innerWidth <= 768;
        const scrollDelay = isMobile ? 80 : 140;

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

            scrollTimer = window.setTimeout(clearScrollingState, scrollDelay);
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

        if (!isMobile) {
            window.addEventListener("touchmove", scheduleScrollingState, { passive: true });
        }

        const backToTopButton = document.querySelector(".back-to-top-button");
        const siteFooter = document.querySelector(".site-footer");
        if (backToTopButton) {
            const toggleBackToTop = () => {
                const scrollY = window.scrollY || window.pageYOffset;
                const shouldShow = scrollY > 400;
                let nearFooter = false;
                if (siteFooter) {
                    const rect = siteFooter.getBoundingClientRect();
                    nearFooter = rect.top < window.innerHeight;
                }
                if (shouldShow && !nearFooter) {
                    backToTopButton.classList.add("is-visible");
                } else {
                    backToTopButton.classList.remove("is-visible");
                }
            };

            window.addEventListener("scroll", toggleBackToTop, { passive: true });
            window.addEventListener("resize", toggleBackToTop, { passive: true });
            toggleBackToTop();

            backToTopButton.addEventListener("click", (e) => {
                e.preventDefault();
                window.scrollTo({
                    top: 0,
                    behavior: "smooth"
                });
            });
        }
    })();

    (() => {
        const sectionTreeNav = document.querySelector(".section-tree-nav");
        const sectionTreeNavToggle = document.querySelector(".section-tree-nav-toggle");

        if (sectionTreeNav && sectionTreeNavToggle) {
            const toggleNav = (event) => {
                if (event) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                const isExpanded = sectionTreeNav.classList.toggle("is-expanded");
                sectionTreeNavToggle.setAttribute("aria-expanded", isExpanded ? "true" : "false");
            };

            sectionTreeNavToggle.addEventListener("click", toggleNav, { passive: false });
            sectionTreeNavToggle.addEventListener("touchend", toggleNav, { passive: false });

            const sectionNavLinks = sectionTreeNav.querySelectorAll(".section-tree-nav-link");
            sectionNavLinks.forEach((link) => {
                link.addEventListener("click", (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    const href = link.getAttribute("href") || "";
                    if (href.startsWith("#")) {
                        const target = document.querySelector(href);
                        if (target) {
                            target.scrollIntoView({
                                behavior: "smooth",
                                block: "start",
                            });

                            if (history.pushState) {
                                history.pushState(null, "", href);
                            }
                        }
                    }

                    sectionTreeNav.classList.remove("is-expanded");
                    sectionTreeNavToggle.setAttribute("aria-expanded", "false");
                });
            });

            const closeNavOnOutsideClick = (event) => {
                if (!sectionTreeNav.contains(event.target) && !sectionTreeNavToggle.contains(event.target) && sectionTreeNav.classList.contains("is-expanded")) {
                    sectionTreeNav.classList.remove("is-expanded");
                    sectionTreeNavToggle.setAttribute("aria-expanded", "false");
                }
            };

            document.addEventListener("click", closeNavOnOutsideClick);
            document.addEventListener("touchend", closeNavOnOutsideClick);
        }
    })();

    (() => {
        const heroShell = document.querySelector(".hero-shell");
        const heroNav = document.querySelector(".hero-nav");
        const menuToggle = heroNav ? heroNav.querySelector(".hero-menu-toggle") : null;
        const navLinks = heroNav ? Array.from(heroNav.querySelectorAll(".hero-links a")) : [];
        const navSections = navLinks
            .map((link) => {
                const href = link.getAttribute("href") || "";
                if (!href.startsWith("#")) {
                    return null;
                }

                const target = document.querySelector(href);
                return target ? { link, target } : null;
            })
            .filter(Boolean);
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

        const syncHeroNavActiveLink = () => {
            if (!navSections.length) {
                return;
            }

            const threshold = window.scrollY + Math.min(window.innerHeight * 0.34, 260);
            let activeItem = navSections[0];

            navSections.forEach((item) => {
                if (item.target.offsetTop <= threshold) {
                    activeItem = item;
                }
            });

            navLinks.forEach((link) => {
                link.classList.toggle("is-active", activeItem && activeItem.link === link);
            });
        };

        navLinks.forEach((link) => {
            link.addEventListener("click", () => {
                navLinks.forEach((item) => item.classList.toggle("is-active", item === link));
            });
        });

        window.addEventListener("scroll", syncHeroNavActiveLink, { passive: true });
        window.addEventListener("resize", syncHeroNavActiveLink, { passive: true });
        syncHeroNavActiveLink();

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

        let autoScrollTimer = 0;
        let autoScrollPaused = false;
        const IMAGE_AUTO_SCROLL_DURATION = 5000;

        const startAutoScroll = () => {
            if (autoScrollTimer) {
                window.clearTimeout(autoScrollTimer);
            }

            if (autoScrollPaused) {
                return;
            }

            const currentIndex = getIndex();
            const currentVideo = videos[currentIndex];
            const currentVideoConfig = videoWindows[currentIndex];

            let duration = IMAGE_AUTO_SCROLL_DURATION;

            if (currentVideo && currentVideoConfig) {
                const bounds = getVideoClipBounds(currentVideo, currentVideoConfig);
                if (bounds && bounds.end !== null && bounds.start !== null) {
                    duration = (bounds.end - bounds.start) * 1000;
                }
            }

            autoScrollTimer = window.setTimeout(() => {
                const nextIndex = currentIndex + 1;
                if (nextIndex < slides.length) {
                    goToSlide(nextIndex);
                } else {
                    goToSlide(0);
                }
                startAutoScroll();
            }, duration);
        };

        const pauseAutoScroll = () => {
            autoScrollPaused = true;
            if (autoScrollTimer) {
                window.clearTimeout(autoScrollTimer);
                autoScrollTimer = 0;
            }
        };

        const resumeAutoScroll = () => {
            autoScrollPaused = false;
            startAutoScroll();
        };

        heroCarousel.addEventListener("mouseenter", pauseAutoScroll);
        heroCarousel.addEventListener("mouseleave", resumeAutoScroll);
        heroCarousel.addEventListener("touchstart", pauseAutoScroll, { passive: true });
        heroCarousel.addEventListener("touchend", resumeAutoScroll);

        prev.addEventListener("click", () => {
            pauseAutoScroll();
            window.setTimeout(resumeAutoScroll, 2000);
        });

        next.addEventListener("click", () => {
            pauseAutoScroll();
            window.setTimeout(resumeAutoScroll, 2000);
        });

        dots.forEach((dot) => {
            dot.addEventListener("click", () => {
                pauseAutoScroll();
                window.setTimeout(resumeAutoScroll, 2000);
            });
        });

        window.requestAnimationFrame(() => {
            heroShell.classList.add("is-nav-ready");
            window.requestAnimationFrame(() => {
                heroCarousel.classList.add("is-ready");
                applyUpdate();
                startAutoScroll();
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
