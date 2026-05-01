<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function vision_placeholder(string $title, string $tag, string $primary, string $secondary): string
{
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 220" role="img" aria-label="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">'
        . '<defs>'
        . '<linearGradient id="vision-g" x1="0%" y1="0%" x2="100%" y2="100%">'
        . '<stop offset="0%" stop-color="' . $primary . '"/>'
        . '<stop offset="100%" stop-color="' . $secondary . '"/>'
        . '</linearGradient>'
        . '</defs>'
        . '<rect width="320" height="220" rx="34" fill="url(#vision-g)"/>'
        . '<circle cx="248" cy="58" r="48" fill="rgba(255,255,255,0.14)"/>'
        . '<circle cx="84" cy="170" r="76" fill="rgba(255,255,255,0.08)"/>'
        . '<path d="M34 164C78 126 118 112 166 118C206 123 244 116 286 88" stroke="rgba(255,255,255,0.52)" stroke-width="7" stroke-linecap="round" fill="none"/>'
        . '<rect x="24" y="24" width="84" height="30" rx="15" fill="rgba(255,255,255,0.14)"/>'
        . '<text x="66" y="44" text-anchor="middle" font-family="Segoe UI, Arial, sans-serif" font-size="14" font-weight="700" letter-spacing="2" fill="#ffffff">' . htmlspecialchars(strtoupper($tag), ENT_QUOTES, 'UTF-8') . '</text>'
        . '<text x="24" y="198" font-family="Segoe UI, Arial, sans-serif" font-size="20" font-weight="700" fill="#ffffff">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</text>'
        . '</svg>';

    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
}

function vision_normalize_image(?string $value, string $title, string $tag, string $primary, string $secondary): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return vision_placeholder($title, $tag, $primary, $secondary);
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
        return $value;
    }

    if (preg_match('#^[a-zA-Z0-9][a-zA-Z0-9/_\-. ]+\.(?:png|jpe?g|gif|webp|svg|bmp|avif)$#i', $value) === 1) {
        return $value;
    }

    return vision_placeholder($title, $tag, $primary, $secondary);
}

function load_vision_pillars(PDO $pdo): array
{
    $statement = $pdo->query(
        'SELECT pillar_tag, title, short_description, full_description, image_path, accent_primary, accent_secondary
         FROM vision_pillars
         WHERE is_active = 1
         ORDER BY display_order, id'
    );

    $pillars = [];
    foreach ($statement->fetchAll() as $row) {
        $title = trim((string) ($row['title'] ?? ''));
        if ($title === '') {
            continue;
        }

        $tag = trim((string) ($row['pillar_tag'] ?? 'VIS'));
        $tag = $tag !== '' ? strtoupper($tag) : 'VIS';
        $primary = trim((string) ($row['accent_primary'] ?? '#8f463d')) ?: '#8f463d';
        $secondary = trim((string) ($row['accent_secondary'] ?? '#d88b63')) ?: '#d88b63';
        $summary = trim((string) ($row['short_description'] ?? ''));
        $detail = trim((string) ($row['full_description'] ?? ''));

        $pillars[] = [
            'tag' => $tag,
            'title' => $title,
            'summary' => $summary !== '' ? $summary : 'Vision details will be updated soon.',
            'detail' => $detail !== '' ? $detail : ($summary !== '' ? $summary : 'Vision details will be updated soon.'),
            'image' => vision_normalize_image((string) ($row['image_path'] ?? ''), $title, $tag, $primary, $secondary),
        ];
    }

    return $pillars;
}

$visionPillars = [];
$visionLoadMessage = '';
try {
    $visionPillars = load_vision_pillars(getDbConnection());
} catch (Throwable $exception) {
    $visionLoadMessage = 'Vision pillars could not be loaded from the database. Please check the `vision_pillars` table.';
}

if ($visionPillars === [] && $visionLoadMessage === '') {
    $visionLoadMessage = 'No active vision pillars were found in the `vision_pillars` table yet.';
}

$visionEmbed = !empty($GLOBALS['VISION_EMBED']);
$visionAppId = $visionEmbed ? 'vision-app-' . substr(md5((string) mt_rand()), 0, 8) : 'vision-app';
$visionHeroImage = $visionPillars[0]['image'] ?? vision_placeholder('Vision for Sri Lanka', 'VIS', '#8f463d', '#d88b63');
$visionHeroVideo = 'images/vision_piller/vision-bg.mp4';
?>
<?php if (!$visionEmbed): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vision for Sri Lanka</title>
<?php endif; ?>
    <style>
        :root {
            --vision-red: #b85a4d;
            --vision-red-deep: #8f463d;
            --vision-gold: #e4bf6d;
            --vision-gold-soft: #f4deb0;
            --vision-ink: #fff7ea;
            --vision-muted: rgba(255, 242, 221, 0.82);
            --vision-glass: rgba(255, 248, 238, 0.12);
            --vision-border: rgba(255, 237, 205, 0.14);
            --vision-shadow: 0 18px 42px rgba(71, 20, 18, 0.12);
            --vision-shadow-hover: 0 24px 52px rgba(71, 20, 18, 0.17);
            --vision-radius-xl: 32px;
            --vision-radius-lg: 24px;
            --vision-radius-md: 18px;
            --vision-ease: 260ms cubic-bezier(.2, .8, .2, 1);
        }

        .vision-app {
            width: 100%;
            margin: 0 auto;
            padding: 12px 14px 18px;
            font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
            color: var(--vision-ink);
        }

        .vision-shell {
            position: relative;
            overflow: hidden;
            border-radius: var(--vision-radius-xl);
            padding: clamp(16px, 1.8vw, 24px);
            background:
                radial-gradient(circle at 10% 10%, rgba(228, 191, 109, 0.15), transparent 24%),
                radial-gradient(circle at 86% 10%, rgba(184, 90, 77, 0.2), transparent 28%),
                radial-gradient(circle at 75% 90%, rgba(255,255,255,0.08), transparent 24%),
                linear-gradient(145deg, rgba(127, 51, 44, 0.24), rgba(184, 90, 77, 0.12)),
                rgba(255,255,255,0.05);
            border: 1px solid rgba(255, 231, 183, 0.08);
            box-shadow: var(--vision-shadow);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
        }

        .vision-shell::before {
            content: "";
            position: absolute;
            inset: 10px;
            border-radius: calc(var(--vision-radius-xl) - 10px);
            border: 1px solid rgba(255,255,255,0.05);
            background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.015));
            pointer-events: none;
        }

        .vision-hero,
        .vision-carousel,
        .vision-cta {
            position: relative;
            z-index: 1;
        }

        .vision-reveal {
            opacity: 0;
            transform: translate3d(0, 22px, 0) scale(0.985);
            filter: blur(12px);
            transition:
                opacity 720ms cubic-bezier(.18, .84, .22, 1),
                transform 720ms cubic-bezier(.18, .84, .22, 1),
                filter 720ms cubic-bezier(.18, .84, .22, 1);
            transition-delay: var(--vision-delay, 0ms);
        }

        .vision-reveal.is-visible {
            opacity: 1;
            transform: translateY(0) scale(1);
            filter: blur(0);
        }

        .vision-hero {
            display: block;
            margin-bottom: 18px;
        }

        .vision-hero-copy {
            position: relative;
            overflow: hidden;
            border-radius: var(--vision-radius-lg);
            border: 1px solid var(--vision-border);
            background:
                radial-gradient(circle at 85% 18%, rgba(228, 191, 109, 0.14), transparent 24%),
                linear-gradient(135deg, rgba(120, 47, 42, 0.88), rgba(164, 84, 69, 0.72)),
                linear-gradient(180deg, rgba(255,255,255,0.09), rgba(255,255,255,0.03)),
                var(--vision-glass);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.08);
            padding: clamp(22px, 3vw, 36px);
            min-height: clamp(280px, 34vw, 380px);
            display: flex;
            align-items: flex-end;
        }

        .vision-hero-video {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.31;
            filter: saturate(1.03) contrast(1.05);
            pointer-events: none;
        }

        .vision-hero-copy::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(90deg, rgba(61, 18, 17, 0.6), rgba(61, 18, 17, 0.28) 48%, rgba(61, 18, 17, 0.12)),
                linear-gradient(180deg, rgba(255,255,255,0.025), rgba(255,255,255,0.01));
            pointer-events: none;
        }

        .vision-hero-content {
            position: relative;
            z-index: 1;
            max-width: 70ch;
        }

        .vision-kicker {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 9px 14px;
            border-radius: 999px;
            border: 1px solid rgba(255, 237, 205, 0.16);
            background: rgba(255,255,255,0.08);
            color: var(--vision-gold-soft);
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .vision-kicker::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--vision-gold), #fff4d6);
            box-shadow: 0 0 0 6px rgba(244, 207, 133, 0.08);
        }

        .vision-hero-copy h2,
        .vision-hero-copy p,
        .vision-card-copy h3,
        .vision-card-copy p,
        .vision-card-panel p {
            margin: 0;
        }

        .vision-hero-copy h2 {
            margin-top: 18px;
            max-width: 13ch;
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(2.35rem, 4.8vw, 4.4rem);
            line-height: 1;
            letter-spacing: -0.045em;
            color: var(--vision-ink);
            text-shadow: 0 14px 30px rgba(20, 5, 5, 0.28);
        }

        .vision-hero-copy p {
            margin-top: 16px;
            max-width: 64ch;
            color: rgba(255, 242, 221, 0.9);
            font-size: clamp(1rem, 1.22vw, 1.1rem);
            line-height: 1.74;
        }

        .vision-carousel-head {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 14px;
        }

        .vision-carousel-head strong {
            color: var(--vision-gold-soft);
            font-size: 0.78rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .vision-carousel-controls {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .vision-carousel-btn {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 231, 183, 0.12);
            border-radius: 999px;
            background:
                linear-gradient(145deg, rgba(241, 207, 122, 0.12), rgba(184, 90, 77, 0.16)),
                rgba(255,255,255,0.08);
            color: var(--vision-gold-soft);
            font-size: 1rem;
            cursor: pointer;
            transition: background var(--vision-ease), transform var(--vision-ease), box-shadow var(--vision-ease), border-color var(--vision-ease), opacity var(--vision-ease);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.12), 0 10px 22px rgba(71, 20, 18, 0.1);
        }

        .vision-carousel-btn:hover,
        .vision-carousel-btn:focus-visible {
            transform: translateY(-2px);
            border-color: rgba(241, 207, 122, 0.24);
            background:
                linear-gradient(145deg, rgba(241, 207, 122, 0.2), rgba(184, 90, 77, 0.2)),
                rgba(255,255,255,0.12);
            outline: none;
        }

        .vision-carousel-btn:disabled {
            opacity: 0.44;
            cursor: not-allowed;
            transform: none;
        }

        .vision-carousel-wrap {
            position: relative;
            overflow: hidden;
        }

        .vision-empty {
            padding: 28px 24px;
            border-radius: var(--vision-radius-lg);
            border: 1px solid rgba(255, 237, 205, 0.12);
            background:
                linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.03)),
                rgba(134, 56, 49, 0.12);
            color: var(--vision-muted);
            text-align: center;
            line-height: 1.7;
        }

        .vision-track {
            display: grid;
            grid-auto-flow: column;
            grid-auto-columns: calc((100% - (16px * 3)) / 4);
            gap: 16px;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            padding: 4px 2px 8px;
        }

        .vision-track::-webkit-scrollbar {
            display: none;
        }

        .vision-card {
            position: relative;
            display: grid;
            min-height: 100%;
            scroll-snap-align: start;
            border-radius: var(--vision-radius-lg);
            border: 1px solid rgba(255, 237, 205, 0.1);
            background:
                linear-gradient(180deg, rgba(255,255,255,0.085), rgba(255,255,255,0.03)),
                rgba(134, 56, 49, 0.12);
            overflow: hidden;
            box-shadow: 0 10px 24px rgba(71, 20, 18, 0.08);
            transition: transform var(--vision-ease), border-color var(--vision-ease), box-shadow var(--vision-ease), background var(--vision-ease);
        }

        .vision-card:hover {
            transform: translateY(-4px);
            border-color: rgba(255, 237, 205, 0.18);
            box-shadow: var(--vision-shadow-hover);
            background:
                linear-gradient(180deg, rgba(255,255,255,0.11), rgba(255,255,255,0.04)),
                rgba(134, 56, 49, 0.16);
        }

        .vision-card-media {
            position: relative;
            aspect-ratio: 16 / 10;
            overflow: hidden;
        }

        .vision-card-media img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
        }

        .vision-card-media::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(61, 18, 17, 0.18));
        }

        .vision-card-copy {
            padding: 18px 18px 16px;
        }

        .vision-card-tag {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            border: 1px solid rgba(255, 237, 205, 0.14);
            background: rgba(255,255,255,0.05);
            color: var(--vision-gold-soft);
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .vision-card-copy h3 {
            margin-top: 14px;
            color: var(--vision-ink);
            font-size: 1.16rem;
            line-height: 1.18;
            letter-spacing: -0.03em;
        }

        .vision-card-copy p {
            margin-top: 10px;
            color: var(--vision-muted);
            font-size: 0.94rem;
            line-height: 1.66;
        }

        .vision-card-toggle {
            margin: 0 18px 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: calc(100% - 36px);
            padding: 11px 16px;
            border-radius: 999px;
            border: 1px solid rgba(255, 237, 205, 0.16);
            background: linear-gradient(145deg, rgba(228, 191, 109, 0.14), rgba(255,255,255,0.05));
            color: var(--vision-ink);
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            cursor: pointer;
            transition: transform var(--vision-ease), background var(--vision-ease), border-color var(--vision-ease);
        }

        .vision-card-toggle:hover,
        .vision-card-toggle:focus-visible {
            transform: translateY(-2px);
            background: linear-gradient(145deg, rgba(228, 191, 109, 0.22), rgba(255,255,255,0.08));
            border-color: rgba(255, 237, 205, 0.24);
            outline: none;
        }

        .vision-card-panel {
            display: grid;
            grid-template-rows: 0fr;
            transition: grid-template-rows var(--vision-ease);
        }

        .vision-card.is-open .vision-card-panel {
            grid-template-rows: 1fr;
        }

        .vision-card-panel-inner {
            overflow: hidden;
        }

        .vision-card-panel p {
            padding: 0 18px 20px;
            color: rgba(255, 242, 221, 0.86);
            line-height: 1.7;
            font-size: 0.92rem;
        }

        @media (max-width: 1180px) {
            .vision-track {
                grid-auto-columns: calc((100% - 16px) / 2);
            }
        }

        @media (max-width: 760px) {
            .vision-app {
                padding: 10px 10px 16px;
            }

            .vision-shell {
                padding: 14px;
                border-radius: 24px;
            }

            .vision-shell::before {
                inset: 7px;
                border-radius: 18px;
            }

            .vision-track {
                grid-auto-columns: 100%;
            }

            .vision-hero-copy h2 {
                max-width: none;
            }

            .vision-hero-copy {
                min-height: 260px;
            }
        }

        @media (max-width: 520px) {
            .vision-hero-copy {
                padding-left: 16px;
                padding-right: 16px;
            }

            .vision-card-toggle {
                width: calc(100% - 28px);
                margin-left: 14px;
                margin-right: 14px;
            }

            .vision-card-copy,
            .vision-card-panel p {
                padding-left: 14px;
                padding-right: 14px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .vision-reveal,
            .vision-card,
            .vision-card-toggle,
            .vision-carousel-btn,
            .vision-track,
            .vision-card-panel {
                transition: none;
            }

            .vision-reveal {
                opacity: 1;
                transform: none;
                filter: none;
            }
        }
    </style>
<?php if (!$visionEmbed): ?>
</head>
<body>
<?php endif; ?>
    <section id="<?php echo htmlspecialchars($visionAppId, ENT_QUOTES, 'UTF-8'); ?>" class="vision-app">
        <div class="vision-shell">
            <div class="vision-hero">
                <div class="vision-hero-copy vision-reveal">
                    <video class="vision-hero-video" autoplay muted loop playsinline>
                        <source src="<?php echo htmlspecialchars($visionHeroVideo, ENT_QUOTES, 'UTF-8'); ?>" type="video/mp4">
                    </video>
                    <div class="vision-hero-content">
                        <span class="vision-kicker">Vision 2030</span>
                        <h2>Our Vision for a Stronger Sri Lanka</h2>
                        <p>We believe the next chapter of Sri Lanka should be built on confidence, competence, and national purpose. This vision sets out the priorities that can strengthen institutions, unlock economic opportunity, and improve daily life across every district.</p>
                    </div>
                </div>
            </div>

            <section class="vision-carousel vision-reveal" style="--vision-delay: 140ms;" aria-label="Vision pillars">
                <div class="vision-carousel-head">
                    <strong>Vision Pillars</strong>
                    <div class="vision-carousel-controls">
                        <button class="vision-carousel-btn" type="button" data-vision-prev aria-label="Previous vision cards" <?php echo $visionPillars === [] ? 'disabled' : ''; ?>>&#8592;</button>
                        <button class="vision-carousel-btn" type="button" data-vision-next aria-label="Next vision cards" <?php echo $visionPillars === [] ? 'disabled' : ''; ?>>&#8594;</button>
                    </div>
                </div>

                <div class="vision-carousel-wrap">
                    <?php if ($visionPillars !== []): ?>
                        <div class="vision-track" data-vision-track>
                            <?php foreach ($visionPillars as $pillar): ?>
                                <article class="vision-card">
                                    <div class="vision-card-media">
                                        <img src="<?php echo htmlspecialchars($pillar['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($pillar['title'], ENT_QUOTES, 'UTF-8'); ?> placeholder" loading="lazy" decoding="async">
                                    </div>
                                    <div class="vision-card-copy">
                                        <span class="vision-card-tag"><?php echo htmlspecialchars($pillar['tag'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <h3><?php echo htmlspecialchars($pillar['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                        <p><?php echo htmlspecialchars($pillar['summary'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    </div>
                                    <button class="vision-card-toggle" type="button" aria-expanded="false">Learn More</button>
                                    <div class="vision-card-panel">
                                        <div class="vision-card-panel-inner">
                                            <p><?php echo htmlspecialchars($pillar['detail'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="vision-empty"><?php echo htmlspecialchars($visionLoadMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
                </div>
            </section>

        </div>
    </section>

    <script>
        (() => {
            const root = document.getElementById(<?php echo json_encode($visionAppId); ?>);
            if (!root) {
                return;
            }

            const track = root.querySelector("[data-vision-track]");
            const prevButton = root.querySelector("[data-vision-prev]");
            const nextButton = root.querySelector("[data-vision-next]");
            const heroVideo = root.querySelector(".vision-hero-video");
            let cards = track ? Array.from(track.children) : [];
            let autoSlideTimer = null;
            const heroVideoLoopEnd = 5;

            root.addEventListener("click", (event) => {
                const toggle = event.target.closest(".vision-card-toggle");
                if (!toggle || !root.contains(toggle)) {
                    return;
                }

                const card = toggle.closest(".vision-card");
                if (!card) {
                    return;
                }

                const isOpen = card.classList.toggle("is-open");
                toggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
                toggle.textContent = isOpen ? "Show Less" : "Learn More";
            });

            if (!track || !prevButton || !nextButton || !cards.length) {
                if (heroVideo) {
                    heroVideo.addEventListener("loadedmetadata", () => {
                        heroVideo.currentTime = 0;
                    });

                    heroVideo.addEventListener("timeupdate", () => {
                        if (heroVideo.currentTime >= heroVideoLoopEnd) {
                            heroVideo.currentTime = 0;
                            heroVideo.play().catch(() => {});
                        }
                    });
                }
                return;
            }

            if (heroVideo) {
                heroVideo.addEventListener("loadedmetadata", () => {
                    heroVideo.currentTime = 0;
                });

                heroVideo.addEventListener("timeupdate", () => {
                    if (heroVideo.currentTime >= heroVideoLoopEnd) {
                        heroVideo.currentTime = 0;
                        heroVideo.play().catch(() => {});
                    }
                });
            }

            function ensureLoopableCards() {
                if (!track || cards.length <= 1) {
                    return;
                }

                const minimumCards = 5;
                const originalCards = cards.slice();
                let cloneIndex = 0;

                while (cards.length < minimumCards) {
                    const sourceCard = originalCards[cloneIndex % originalCards.length];
                    const cloneCard = sourceCard.cloneNode(true);
                    cloneCard.setAttribute("aria-hidden", "true");
                    track.appendChild(cloneCard);
                    cards.push(cloneCard);
                    cloneIndex += 1;
                }
            }

            ensureLoopableCards();

            function itemsPerView() {
                if (window.innerWidth <= 760) {
                    return 1;
                }

                if (window.innerWidth <= 1180) {
                    return 2;
                }

                return 4;
            }

            function maxIndex() {
                return Math.max(0, cards.length - itemsPerView());
            }

            function currentIndex() {
                const firstCard = cards[0];
                if (!firstCard) {
                    return 0;
                }

                const step = firstCard.getBoundingClientRect().width + 16;
                if (step <= 0) {
                    return 0;
                }

                return Math.max(0, Math.min(maxIndex(), Math.round(track.scrollLeft / step)));
            }

            function goToIndex(index, behavior = "smooth") {
                const firstCard = cards[0];
                if (!firstCard) {
                    return;
                }

                const step = firstCard.getBoundingClientRect().width + 16;
                const safeIndex = Math.max(0, Math.min(maxIndex(), index));
                track.scrollTo({ left: safeIndex * step, behavior });
            }

            function updateControls() {
                const index = currentIndex();
                prevButton.disabled = index <= 0;
                nextButton.disabled = index >= maxIndex();
            }

            function restartAutoSlide() {
                if (window.matchMedia("(prefers-reduced-motion: reduce)").matches || cards.length <= 1 || maxIndex() <= 0) {
                    return;
                }

                window.clearInterval(autoSlideTimer);
                autoSlideTimer = window.setInterval(() => {
                    const index = currentIndex();
                    const nextIndex = index >= maxIndex() ? 0 : index + 1;
                    goToIndex(nextIndex);
                }, 1200);
            }

            prevButton.addEventListener("click", () => {
                goToIndex(currentIndex() - 1);
                restartAutoSlide();
            });

            nextButton.addEventListener("click", () => {
                goToIndex(currentIndex() + 1);
                restartAutoSlide();
            });

            track.addEventListener("scroll", updateControls, { passive: true });
            track.addEventListener("mouseenter", () => window.clearInterval(autoSlideTimer));
            track.addEventListener("mouseleave", restartAutoSlide);
            track.addEventListener("touchstart", () => window.clearInterval(autoSlideTimer), { passive: true });
            track.addEventListener("touchend", restartAutoSlide, { passive: true });

            window.addEventListener("resize", () => {
                goToIndex(currentIndex(), "auto");
                updateControls();
                restartAutoSlide();
            });

            updateControls();
            restartAutoSlide();

            if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
                root.querySelectorAll(".vision-reveal").forEach((item) => item.classList.add("is-visible"));
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
                threshold: 0.18,
                rootMargin: "0px 0px -8% 0px"
            });

            root.querySelectorAll(".vision-reveal").forEach((item) => observer.observe(item));
        })();
    </script>
<?php if (!$visionEmbed): ?>
</body>
</html>
<?php endif; ?>
