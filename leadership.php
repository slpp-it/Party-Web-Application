<?php
function leadership_photo(string $primary, string $secondary): string
{
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 320" role="img" aria-label="Leadership portrait placeholder">'
        . '<defs>'
        . '<linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%">'
        . '<stop offset="0%" stop-color="' . $primary . '"/>'
        . '<stop offset="100%" stop-color="' . $secondary . '"/>'
        . '</linearGradient>'
        . '</defs>'
        . '<rect width="320" height="320" rx="56" fill="url(#g)"/>'
        . '<circle cx="160" cy="106" r="68" fill="rgba(255,255,255,0.18)"/>'
        . '<circle cx="160" cy="126" r="50" fill="rgba(255,251,240,0.95)"/>'
        . '<path d="M54 278c24-63 66-96 106-96s82 33 106 96" fill="rgba(255,251,240,0.92)"/>'
        . '</svg>';

    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
}

$leadershipMembers = [
    [
        'name' => 'Mahinda Rajapaksa',
        'position' => 'Leader',
        'description' => 'Mahinda Rajapaksa is a dominant figure in modern Sri Lankan politics, best known for his leadership during the conclusion of the Sri Lankan Civil War in 2009.',
        'priority' => 1,
        'accent' => '#e2c06c',
        'photo' => 'images/mahinda.jpg',
    ],
    // [
    //     'name' => 'Sagara Kariyawasam',
    //     'position' => 'General Secretary',
    //     'description' => 'Sagara Kariyawasam is a Sri Lankan lawyer, politician, and Member of Parliament (MP). Hon. Kariyawasam was born on 13 November 1967. He is the son of Albert Kariyawasum, who was a renowned politician from Southern province.',
    //     'priority' => 2,
    //     'accent' => '#d9b45a',
    //     'photo' => 'images/sagala.jpg',
    // ],
    [
        'name' => 'Namal Rajapaksa',
        'position' => 'National Organizer',
        'description' => 'Namal Rajapaksa is a Sri Lankan lawyer and politician. He is the eldest son of former President and former Prime Minister Mahinda Rajapaksa and a member of parliament',
        'priority' => 3,
        'accent' => '#e6c778',
        'photo' => 'images/namal.jpg',
    ]
    // [
    //     'name' => 'Johnston Fernando',
    //     'position' => 'Head of Operations',
    //     'description' => 'Johnston Fernando is a Sri Lankan politician, former Cabinet Minister, Chief Government Whip and a former member of the Parliament of Sri Lanka from the Kurunegala District.',
    //     'priority' => 5,
    //     'accent' => '#d4aa51',
    //     'photo' => 'images/Johnston.jpg',
    // ],
    // [
    //     'name' => 'D.V.Chanaka',
    //     'position' => 'National Convenor',
    //     'description' => 'Denagama Vitharanage Chanaka Dinushan is a Sri Lankan politician and a member of the Parliament of Sri Lanka.',
    //     'priority' => 4,
    //     'accent' => '#dec16d',
    //     'photo' => 'images/chanaka.jpg',
    // ],
];

usort($leadershipMembers, static fn ($a, $b) => $a['priority'] <=> $b['priority']);

$leadershipEmbed = !empty($GLOBALS['LEADERSHIP_EMBED']);
$leadershipAppId = $leadershipEmbed ? 'leadership-app-' . substr(md5((string) mt_rand()), 0, 8) : 'leadership-app';
?>
<?php if (!$leadershipEmbed): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leadership</title>
    <link rel="icon" type="image/png" href="images/testlogo.png">
<?php endif; ?>
    <style>
        :root {
            --hero-red: #b85a4d;
            --hero-red-deep: #7f332c;
            --hero-red-soft: rgba(184, 90, 77, 0.28);
            --hero-gold: #f1cf7a;
            --hero-gold-soft: #fff0bd;
            --hero-ink: #fffaf0;
            --hero-muted: rgba(255, 244, 221, 0.8);
            --glass-bg: rgba(255, 255, 255, 0.075);
            --glass-bg-strong: rgba(255, 255, 255, 0.12);
            --glass-border: rgba(255, 231, 183, 0.12);
            --glass-border-soft: rgba(255, 255, 255, 0.08);
            --shadow-soft: 0 14px 34px rgba(71, 20, 18, 0.11);
            --shadow-hover: 0 20px 44px rgba(71, 20, 18, 0.16);
            --radius-xl: 30px;
            --radius-lg: 24px;
            --radius-md: 16px;
            --ease: 260ms cubic-bezier(.2, .8, .2, 1);
        }

        .leadership-app {
            width: 100%;
            margin: 0 auto;
            padding: 12px 14px 22px;
            font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
            color: var(--hero-ink);
        }

        .leadership-shell {
            position: relative;
            overflow: hidden;
            border-radius: var(--radius-xl);
            padding: clamp(14px, 1.55vw, 22px);
            background:
                radial-gradient(circle at 12% 8%, rgba(241, 207, 122, 0.13), transparent 26%),
                radial-gradient(circle at 86% 12%, rgba(184, 90, 77, 0.22), transparent 32%),
                radial-gradient(circle at 70% 92%, rgba(241, 207, 122, 0.1), transparent 28%),
                linear-gradient(145deg, rgba(127, 51, 44, 0.28), rgba(184, 90, 77, 0.16)),
                var(--glass-bg);
            border: 1px solid rgba(255, 231, 183, 0.09);
            box-shadow: var(--shadow-soft);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
        }

        .leadership-shell::before {
            content: "";
            position: absolute;
            inset: 10px;
            border-radius: calc(var(--radius-xl) - 9px);
            border: 1px solid rgba(255,255,255,0.055);
            background: linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.025));
            pointer-events: none;
        }

        .leadership-head,
        .leadership-slider {
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

        .leadership-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 14px;
        }

        .leadership-kicker {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            gap: 10px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,0.095);
            border: 1px solid rgba(255, 231, 183, 0.12);
            color: var(--hero-gold-soft);
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.14);
        }

        .leadership-kicker::before {
            content: "";
            width: 9px;
            height: 9px;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--hero-gold), var(--hero-red));
            box-shadow: 0 0 0 6px rgba(241, 207, 122, 0.1);
        }

        .leadership-head h2,
        .leadership-head p,
        .leadership-topbar p,
        .leader-main h3,
        .leader-main p {
            margin: 0;
        }

        .leadership-head h2,
        .leadership-head p,
        .leadership-topbar p {
            display: none;
        }

        .leadership-topbar {
            position: absolute;
            top: -60px;
            right: 0;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .leadership-controls {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .leadership-control {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 231, 183, 0.1);
            border-radius: 999px;
            background:
                linear-gradient(145deg, rgba(241, 207, 122, 0.14), rgba(184, 90, 77, 0.18)),
                rgba(255,255,255,0.08);
            color: var(--hero-gold-soft);
            font-size: 1rem;
            cursor: pointer;
            transition: background var(--ease), transform var(--ease), opacity var(--ease), box-shadow var(--ease), border-color var(--ease);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.14),
                0 10px 20px rgba(71, 20, 18, 0.1);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .leadership-control-label {
            display: none;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .leadership-control:hover {
            background:
                linear-gradient(145deg, rgba(241, 207, 122, 0.22), rgba(184, 90, 77, 0.25)),
                rgba(255,255,255,0.12);
            transform: translateY(-2px);
            border-color: rgba(241, 207, 122, 0.26);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.2),
                0 14px 24px rgba(71, 20, 18, 0.16);
        }

        .leadership-control:disabled {
            opacity: 0.42;
            cursor: not-allowed;
            transform: none;
        }

        .leadership-slider {
            display: grid;
            gap: 12px;
        }

        .leadership-track-wrap {
            position: relative;
        }

        .leadership-track {
            display: grid;
            grid-auto-flow: column;
            grid-auto-columns: calc((100% - 14px) / 2);
            gap: 14px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            padding: 2px 2px 10px;
            overscroll-behavior-x: contain;
            overscroll-behavior-y: auto;
            touch-action: pan-x pan-y pinch-zoom;
            align-items: stretch;
        }

        .leadership-track.is-vertical-scroll {
        }

        .leadership-track::-webkit-scrollbar {
            display: none;
        }

        .leader-card {
            position: relative;
            min-height: clamp(505px, 56vh, 610px);
            height: 100%;
            scroll-snap-align: start;
            border-radius: 24px;
            border: 1px solid rgba(255, 231, 183, 0.075);
            background:
                radial-gradient(circle at 14% 10%, rgba(241, 207, 122, 0.11), transparent 26%),
                radial-gradient(circle at 92% 14%, rgba(255,255,255,0.08), transparent 25%),
                linear-gradient(180deg, rgba(255,255,255,0.105), rgba(255,255,255,0.045)),
                rgba(127, 51, 44, 0.14);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: 0 12px 28px rgba(71, 20, 18, 0.11);
            overflow: hidden;
            transition: transform var(--ease), box-shadow var(--ease), border-color var(--ease), background var(--ease);
        }

        .leader-card:hover {
            transform: translateY(-4px);
            border-color: rgba(241, 207, 122, 0.2);
            box-shadow: var(--shadow-hover);
            background:
                radial-gradient(circle at 14% 10%, rgba(241, 207, 122, 0.15), transparent 26%),
                radial-gradient(circle at 92% 14%, rgba(255,255,255,0.1), transparent 25%),
                linear-gradient(180deg, rgba(255,255,255,0.13), rgba(255,255,255,0.055)),
                rgba(127, 51, 44, 0.16);
        }

        .leader-card.priority-1 {
            border-color: rgba(241, 207, 122, 0.18);
        }

        .leader-card-inner {
            display: grid;
            min-height: 100%;
            height: 100%;
            gap: 13px;
            padding: 14px;
            align-content: start;
            grid-template-rows: 1fr auto;
        }

        .leader-headline {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            align-items: start;
        }

        .leader-photo {
            position: relative;
            width: 100%;
            aspect-ratio: 16 / 10;
            height: auto;
            border-radius: 20px;
            border: 1px solid rgba(255, 231, 183, 0.08);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.12),
                0 12px 24px rgba(71, 20, 18, 0.11);
            overflow: hidden;
            background:
                linear-gradient(145deg, rgba(241, 207, 122, 0.12), rgba(184, 90, 77, 0.16)),
                rgba(255,255,255,0.06);
        }

        .leader-photo img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            object-position: center top;
            filter: saturate(1.04) contrast(1.03);
            transform: scale(1.002);
        }

        .leader-photo::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: 1;
            background:
                linear-gradient(180deg, rgba(255,255,255,0.04), transparent 58%, rgba(71, 20, 18, 0.1)),
                radial-gradient(circle at top right, rgba(241, 207, 122, 0.1), transparent 28%);
            pointer-events: none;
        }

        .leader-photo::after {
            content: "";
            position: absolute;
            inset: 0;
            z-index: 2;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.035);
            pointer-events: none;
        }

        .leader-main {
            min-width: 0;
        }

        .leader-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 8px;
        }

        .leader-role,
        .leader-more {
            display: inline-flex;
            align-items: center;
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .leader-role {
            color: #fffaf0;
            background:
                linear-gradient(135deg, rgba(241, 207, 122, 0.38), rgba(184, 90, 77, 0.52)),
                rgba(255,255,255,0.08);
            border: 1px solid rgba(255, 231, 183, 0.13);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.15);
        }

        .leader-main h3 {
            font-size: clamp(1.12rem, 1.55vw, 1.42rem);
            color: var(--hero-ink);
            letter-spacing: -0.035em;
            line-height: 1.08;
        }

        .leader-main p {
            margin-top: 8px;
            color: var(--hero-muted);
            line-height: 1.6;
            max-width: none;
            font-size: 0.92rem;
        }

        .leader-foot {
            position: absolute;
            right: 16px;
            bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            margin: 0;
        }

        .leader-more {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border-radius: 999px;
            color: var(--hero-gold-soft);
            background:
                linear-gradient(145deg, rgba(241, 207, 122, 0.16), rgba(184, 90, 77, 0.18)),
                rgba(255,255,255,0.07);
            border: 1px solid rgba(255, 231, 183, 0.09);
            text-decoration: none;
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.12),
                0 8px 18px rgba(71, 20, 18, 0.1);
            transition: transform var(--ease), background var(--ease), border-color var(--ease);
        }

        .leader-more:hover {
            transform: translateX(2px);
            background:
                linear-gradient(145deg, rgba(241, 207, 122, 0.24), rgba(184, 90, 77, 0.22)),
                rgba(255,255,255,0.1);
            border-color: rgba(255, 231, 183, 0.18);
        }

        .leadership-progress {
            position: relative;
            height: 4px;
            border-radius: 999px;
            background: rgba(255, 231, 183, 0.1);
            overflow: hidden;
        }

        .leadership-progress-bar {
            position: absolute;
            inset: 0 auto 0 0;
            width: 25%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--hero-red), var(--hero-gold));
            transition: width var(--ease);
        }

        .leadership-dots {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .leadership-dot {
            width: 8px;
            height: 8px;
            border: 0;
            border-radius: 999px;
            background: rgba(255, 231, 183, 0.2);
            cursor: pointer;
            padding: 0;
            transition: width var(--ease), background var(--ease), transform var(--ease);
        }

        .leadership-dot:hover {
            transform: translateY(-1px);
        }

        .leadership-dot.active {
            width: 28px;
            background: linear-gradient(90deg, var(--hero-red), var(--hero-gold));
        }

        @media (max-width: 960px) {
            .leadership-topbar {
                position: static;
                width: 100%;
                margin-bottom: 10px;
                justify-content: flex-end;
            }

            .leadership-controls {
                width: auto;
                justify-content: flex-end;
            }

            .leadership-track {
                grid-auto-columns: calc((100% - 14px) / 2);
            }

            .leader-card {
                min-height: 500px;
            }

            .leader-photo {
                aspect-ratio: 16 / 10;
                height: auto;
            }
        }

        @media (max-width: 640px) {
            .leadership-app {
                padding: 10px 8px 20px;
            }

            .leadership-shell {
                padding: 14px;
                border-radius: 24px;
            }

            .leadership-shell::before {
                inset: 9px;
                border-radius: 17px;
            }

            .leadership-head {
                margin-bottom: 12px;
            }

            .leadership-topbar {
                width: 100%;
                justify-content: space-between;
            }

            .leadership-controls {
                width: 100%;
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }

            .leadership-track {
                grid-auto-columns: 100%;
                gap: 14px;
                padding-bottom: 10px;
                overflow-x: hidden;
                overscroll-behavior-x: auto;
                touch-action: pan-y pinch-zoom;
            }

            .leader-card {
                border-radius: 24px;
                min-height: 455px;
            }

            .leader-card-inner {
                gap: 14px;
                padding: 16px;
            }

            .leader-headline {
                gap: 14px;
            }

            .leader-photo {
                width: 100%;
                aspect-ratio: 16 / 10;
                height: auto;
                border-radius: 18px;
            }

            .leader-main h3 {
                font-size: 1.12rem;
            }

            .leader-role,
            .leader-more {
                font-size: 0.68rem;
                padding: 6px 10px;
            }

            .leadership-control {
                width: 100%;
                height: 40px;
                gap: 8px;
                border-radius: 14px;
                font-size: 0.94rem;
            }

            .leadership-control-label {
                display: inline;
            }
        }
    </style>
<?php if (!$leadershipEmbed): ?>
</head>
<body>
<?php endif; ?>
    <section id="<?php echo htmlspecialchars($leadershipAppId, ENT_QUOTES, 'UTF-8'); ?>" class="leadership-app">
        <div class="leadership-shell" data-float>
            <div class="leadership-head">
                <span class="leadership-kicker" data-float>Leadership</span>
            </div>

            <div class="leadership-slider">
                <div class="leadership-topbar" data-float style="--float-delay: 80ms;">
                    <div class="leadership-controls">
                        <button class="leadership-control" type="button" data-leadership-prev aria-label="Previous leadership card">
                            <span aria-hidden="true">&#8592;</span>
                            <span class="leadership-control-label">Previous</span>
                        </button>
                        <button class="leadership-control" type="button" data-leadership-next aria-label="Next leadership card">
                            <span class="leadership-control-label">Next</span>
                            <span aria-hidden="true">&#8594;</span>
                        </button>
                    </div>
                </div>

                <div class="leadership-track-wrap">
                    <div class="leadership-track" data-leadership-track>
                        <?php foreach ($leadershipMembers as $index => $member): ?>
                            <article class="leader-card priority-<?php echo (int) $member['priority']; ?>" style="--leader-accent: <?php echo htmlspecialchars($member['accent'], ENT_QUOTES, 'UTF-8'); ?>; --float-delay: <?php echo (int) ($index * 80); ?>ms;" data-leadership-card data-float>
                                <div class="leader-card-inner">
                                    <div class="leader-headline">
                                        <div class="leader-photo">
                                            <img src="<?php echo htmlspecialchars($member['photo'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($member['name'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy" decoding="async">
                                        </div>
                                        <div class="leader-main">
                                            <div class="leader-meta">
                                                <span class="leader-role"><?php echo htmlspecialchars($member['position'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            </div>
                                            <h3><?php echo htmlspecialchars($member['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                            <p><?php echo htmlspecialchars($member['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        </div>
                                    </div>
                                    <div class="leader-foot">
                                        <span class="leader-more" aria-label="View leader details">&#8594;</span>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="leadership-progress" aria-hidden="true">
                    <div class="leadership-progress-bar" data-leadership-progress></div>
                </div>
                <div class="leadership-dots" data-leadership-dots></div>
            </div>
        </div>
    </section>
    <script>
        (() => {
            const root = document.getElementById(<?php echo json_encode($leadershipAppId); ?>);
            if (!root) {
                return;
            }

            const track = root.querySelector("[data-leadership-track]");
            const cards = Array.from(root.querySelectorAll("[data-leadership-card]"));
            const prevButton = root.querySelector("[data-leadership-prev]");
            const nextButton = root.querySelector("[data-leadership-next]");
            const progressBar = root.querySelector("[data-leadership-progress]");
            const dotsHost = root.querySelector("[data-leadership-dots]");
            const floatItems = Array.from(root.querySelectorAll("[data-float]"));

            if (!track || !cards.length || !prevButton || !nextButton || !progressBar || !dotsHost) {
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

            function getVisibleCards() {
                if (window.innerWidth > 640) {
                    return 2;
                }

                return 1;
            }

            function getPageStartPositions() {
                const visibleCards = getVisibleCards();
                if (visibleCards === 1) {
                    return cards.map((card) => card.offsetLeft);
                }

                const positions = [];

                for (let index = 0; index < cards.length; index += visibleCards) {
                    positions.push(cards[index].offsetLeft);
                }

                return positions.length ? positions : [0];
            }

            function getCardStep() {
                const computedStyle = window.getComputedStyle(track);
                const gap = Number.parseFloat(computedStyle.columnGap || computedStyle.gap || "0") || 0;
                const cardWidth = cards[0] ? cards[0].offsetWidth : track.clientWidth;
                return cardWidth + gap;
            }

            function getPageCount() {
                return getPageStartPositions().length;
            }

            function getActivePage() {
                const positions = getPageStartPositions();
                if (!positions.length) {
                    return 0;
                }

                let closestPage = 0;
                let smallestDistance = Number.POSITIVE_INFINITY;

                positions.forEach((position, index) => {
                    const distance = Math.abs(track.scrollLeft - position);
                    if (distance < smallestDistance) {
                        smallestDistance = distance;
                        closestPage = index;
                    }
                });

                return closestPage;
            }

            function scrollToPage(pageIndex) {
                const positions = getPageStartPositions();
                const nextPage = Math.max(0, Math.min(positions.length - 1, pageIndex));
                track.scrollTo({
                    left: positions[nextPage] ?? 0,
                    behavior: "smooth"
                });
            }

            function renderDots() {
                dotsHost.innerHTML = "";
                const pageCount = getPageCount();

                for (let index = 0; index < pageCount; index += 1) {
                    const dot = document.createElement("button");
                    dot.type = "button";
                    dot.className = "leadership-dot";
                    dot.setAttribute("aria-label", `Go to leadership slide ${index + 1}`);
                    dot.addEventListener("click", () => { scrollToPage(index); window.setTimeout(updateSliderState, 420); scheduleAutoSlide(); });
                    dotsHost.appendChild(dot);
                }
            }

            function updateSliderState() {
                const activePage = getActivePage();
                const dots = Array.from(dotsHost.querySelectorAll(".leadership-dot"));
                const pageCount = getPageCount();
                const progress = pageCount > 1 ? ((activePage + 1) / pageCount) * 100 : 100;

                progressBar.style.width = `${progress}%`;
                dots.forEach((dot, index) => {
                    dot.classList.toggle("active", index === activePage);
                });

                prevButton.disabled = activePage === 0;
                nextButton.disabled = activePage >= pageCount - 1;
            }

            prevButton.addEventListener("click", () => {
                scrollToPage(getActivePage() - 1);
                window.setTimeout(updateSliderState, 420);
                scheduleAutoSlide();
            });

            nextButton.addEventListener("click", () => {
                scrollToPage(getActivePage() + 1);
                window.setTimeout(updateSliderState, 420);
                scheduleAutoSlide();
            });

            let resizeTimer = null;
            let scrollStateTimer = null;
            let touchStartX = 0;
            let touchStartY = 0;
            let touchIntent = "";

            track.addEventListener("scroll", () => {
                updateSliderState();
                pauseAutoSlide();
                window.clearTimeout(scrollStateTimer);
                scrollStateTimer = window.setTimeout(resumeAutoSlide, 700);
            }, { passive: true });
            window.addEventListener("resize", () => {
                window.clearTimeout(resizeTimer);
                resizeTimer = window.setTimeout(() => {
                    renderDots();
                    scrollToPage(getActivePage());
                    updateSliderState();
                }, 120);
            });

            track.addEventListener("touchstart", (event) => {
                const touch = event.touches[0];
                if (!touch) {
                    return;
                }

                touchStartX = touch.clientX;
                touchStartY = touch.clientY;
                touchIntent = "";
                track.classList.remove("is-vertical-scroll");
            }, { passive: true });

            track.addEventListener("touchmove", (event) => {
                const touch = event.touches[0];
                if (!touch) {
                    return;
                }

                const deltaX = Math.abs(touch.clientX - touchStartX);
                const deltaY = Math.abs(touch.clientY - touchStartY);

                if (!touchIntent) {
                    if (deltaY > deltaX + 8) {
                        touchIntent = "vertical";
                        track.classList.add("is-vertical-scroll");
                    } else if (deltaX > deltaY + 8) {
                        touchIntent = "horizontal";
                        track.classList.remove("is-vertical-scroll");
                    }
                }
            }, { passive: true });

            const resetTouchIntent = () => {
                touchIntent = "";
                track.classList.remove("is-vertical-scroll");
            };

            track.addEventListener("touchend", resetTouchIntent, { passive: true });
            track.addEventListener("touchcancel", resetTouchIntent, { passive: true });

            const autoSlideDelay = 6000;
            let autoSlideTimer = null;
            let autoSlidePaused = false;

            function stopAutoSlide() {
                if (autoSlideTimer) {
                    window.clearTimeout(autoSlideTimer);
                    autoSlideTimer = null;
                }
            }

            function scheduleAutoSlide() {
                stopAutoSlide();
                if (autoSlidePaused || document.hidden || getPageCount() <= 1) {
                    return;
                }

                autoSlideTimer = window.setTimeout(() => {
                    const pageCount = getPageCount();
                    const activePage = getActivePage();
                    const nextPage = activePage >= pageCount - 1 ? 0 : activePage + 1;
                    scrollToPage(nextPage);
                    window.setTimeout(updateSliderState, 420);
                    scheduleAutoSlide();
                }, autoSlideDelay);
            }

            function pauseAutoSlide() {
                autoSlidePaused = true;
                stopAutoSlide();
            }

            function resumeAutoSlide() {
                autoSlidePaused = false;
                scheduleAutoSlide();
            }

            [track, prevButton, nextButton, dotsHost].forEach((item) => {
                item.addEventListener("mouseenter", pauseAutoSlide);
                item.addEventListener("mouseleave", resumeAutoSlide);
                item.addEventListener("focusin", pauseAutoSlide);
                item.addEventListener("focusout", resumeAutoSlide);
                item.addEventListener("pointerdown", pauseAutoSlide);
                item.addEventListener("pointerup", () => window.setTimeout(resumeAutoSlide, 900));
            });

            document.addEventListener("visibilitychange", () => {
                if (document.hidden) {
                    stopAutoSlide();
                } else {
                    scheduleAutoSlide();
                }
            });

            renderDots();
            updateSliderState();
            scheduleAutoSlide();
        })();
    </script>
<?php if (!$leadershipEmbed): ?>
</body>
</html>
<?php endif; ?>
