<?php
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

$heroSlides = [
    [
        'eyebrow' => 'National Vision',
        'title' => 'Build a stronger digital presence with connected district stories.',
        'text' => 'A modern single-page experience for projects, leadership, and future public-facing components.',
        'image' => hero_slide_image('#c15d4f', '#efcd7b', 'National vision slide'),
    ],
    [
        'eyebrow' => 'Project Focus',
        'title' => 'Showcase regional opportunities with smoother discovery and smarter presentation.',
        'text' => 'Maps, district projects, and leadership content now live inside one scalable page structure.',
        'image' => hero_slide_image('#a94e43', '#e7bb66', 'Project focus slide'),
    ],
    [
        'eyebrow' => 'Leadership Layer',
        'title' => 'Bring together strategy, place, and identity in one polished interface.',
        'text' => 'The layout is ready for future sections while staying responsive across mobile, tablet, and desktop.',
        'image' => hero_slide_image('#b76854', '#f0d79a', 'Leadership layer slide'),
    ],
];

$sectionNavItems = [
    ['id' => 'vision', 'label' => 'Vision'],
    ['id' => 'our-party', 'label' => 'Our Party'],
    ['id' => 'districts', 'label' => 'Projects'],
    ['id' => 'leadership', 'label' => 'Leadership'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Party App</title>
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

        body {
            margin: 0;
            background:
                radial-gradient(circle at top left, rgba(201, 104, 86, 0.24), transparent 24%),
                radial-gradient(circle at top right, rgba(228, 191, 109, 0.22), transparent 28%),
                linear-gradient(180deg, #9f4f47 0%, #b76052 18%, #c77158 34%, #da975f 54%, #e6b765 72%, #efcf93 88%, #f3dcc2 100%);
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
            gap: 16px;
            padding: 14px 18px;
            margin-bottom: 18px;
            border-radius: 24px;
            background: var(--hero-glass);
            border: 1px solid var(--hero-border);
            backdrop-filter: blur(18px);
            overflow: hidden;
            isolation: isolate;
        }

        .hero-nav::before {
            content: "";
            position: absolute;
            top: -28%;
            bottom: -28%;
            left: -24%;
            width: 18%;
            background: linear-gradient(90deg, rgba(255,255,255,0), rgba(255,255,255,0.34), rgba(255,255,255,0));
            transform: skewX(-18deg);
            filter: blur(1px);
            opacity: 0;
            pointer-events: none;
            z-index: 0;
            animation: heroNavSweep 2.4s ease-in-out infinite;
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
            min-width: 0;
            text-decoration: none;
            padding: 0;
            outline: none;
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
            transition: opacity 220ms cubic-bezier(.2, .8, .2, 1);
            animation: heroLogoBlink 1.2s ease-in-out infinite;
            will-change: transform, filter, opacity;
            backface-visibility: hidden;
        }

        .hero-brand:hover .hero-brand-logo,
        .hero-brand:focus-visible .hero-brand-logo {
            opacity: 1;
            animation: heroLogoRoll 1.05s cubic-bezier(.45, .05, .55, .95) infinite;
        }

        .hero-brand:hover,
        .hero-brand:focus-visible {
            outline: none;
        }

        .hero-links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }

        .hero-links a,
        .hero-action {
            color: var(--hero-ink);
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 999px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255, 240, 214, 0.18);
            font-size: 0.88rem;
        }

        .hero-action {
            background: linear-gradient(135deg, rgba(244, 214, 143, 0.24), rgba(255,255,255,0.12));
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
        }

        .hero-track {
            display: grid;
            grid-auto-flow: column;
            grid-auto-columns: 100%;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            scrollbar-width: none;
            scroll-behavior: smooth;
        }

        .hero-track::-webkit-scrollbar {
            display: none;
        }

        .hero-slide {
            position: relative;
            min-height: clamp(420px, 62vw, 620px);
            scroll-snap-align: start;
            display: grid;
            align-items: end;
            padding: clamp(22px, 4vw, 42px);
            background-size: cover;
            background-position: center;
        }

        .hero-slide::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(90deg, rgba(61, 18, 17, 0.72), rgba(61, 18, 17, 0.18) 58%, rgba(61, 18, 17, 0.1)),
                linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0));
        }

        .hero-copy {
            position: relative;
            z-index: 1;
            max-width: 720px;
            color: var(--hero-ink);
        }

        .hero-copy span {
            display: inline-flex;
            margin-bottom: 16px;
            padding: 9px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255, 237, 205, 0.18);
            font-size: 0.78rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .hero-copy h1 {
            margin: 0;
            font-size: clamp(2.1rem, 5vw, 4.6rem);
            line-height: 0.96;
            letter-spacing: -0.05em;
        }

        .hero-copy p {
            margin: 18px 0 0;
            max-width: 54ch;
            color: var(--hero-muted);
            line-height: 1.72;
            font-size: clamp(0.98rem, 1.7vw, 1.08rem);
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

        .section-separator {
            width: 100%;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
            align-items: center;
            gap: 26px;
            margin: 0 auto;
            padding: 22px 14px 18px;
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
            min-height: 36px;
            width: 100%;
            padding: 8px 14px;
            border-radius: 999px;
            background:
                linear-gradient(180deg, rgba(255,255,255,0.1), rgba(255,255,255,0.04)),
                rgba(116, 44, 38, 0.34);
            border: 1px solid rgba(255, 237, 205, 0.1);
            color: rgba(255, 244, 224, 0.76);
            font-size: 0.8rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            white-space: nowrap;
            opacity: 0.72;
            transform: translateX(0) scale(1);
            transition: opacity 260ms cubic-bezier(.2, .8, .2, 1), transform 260ms cubic-bezier(.2, .8, .2, 1), color 260ms cubic-bezier(.2, .8, .2, 1), background 260ms cubic-bezier(.2, .8, .2, 1), border-color 260ms cubic-bezier(.2, .8, .2, 1), box-shadow 260ms cubic-bezier(.2, .8, .2, 1);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.06);
            overflow: visible;
        }

        .section-tree-nav-link:hover .section-tree-nav-label,
        .section-tree-nav-link:focus-visible .section-tree-nav-label,
        .section-tree-nav-item.is-active .section-tree-nav-label {
            opacity: 1;
            transform: translateX(-4px) scale(1);
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
                linear-gradient(180deg, rgba(255,255,255,0.14), rgba(255,255,255,0.06)),
                rgba(126, 49, 42, 0.56);
            border-color: rgba(255, 237, 205, 0.18);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.08), 0 10px 24px rgba(61, 18, 17, 0.16);
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
            height: 1px;
            border-radius: 999px;
            background:
                linear-gradient(90deg, rgba(255, 237, 205, 0), rgba(255, 237, 205, 0.42) 22%, rgba(244, 207, 133, 0.6) 50%, rgba(255, 237, 205, 0.42) 78%, rgba(255, 237, 205, 0));
            opacity: 0.78;
            box-shadow:
                0 0 0 1px rgba(255, 250, 239, 0.03),
                0 0 10px rgba(244, 207, 133, 0.08);
        }

        .section-separator-line::after {
            content: "";
            position: absolute;
            left: 12%;
            right: 12%;
            top: 6px;
            height: 1px;
            border-radius: 999px;
            background: linear-gradient(90deg, rgba(255,255,255,0), rgba(255,255,255,0.28), rgba(255,255,255,0));
            opacity: 0.76;
        }

        .section-separator-copy {
            position: relative;
            min-width: min(100%, 320px);
            max-width: 640px;
            margin: 0 auto;
            padding: 10px 26px 12px;
            text-align: center;
            display: grid;
            justify-items: center;
            row-gap: 0;
            border-radius: 999px;
            border: 1px solid rgba(255, 237, 205, 0.08);
            background:
                linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.025)),
                rgba(130, 53, 46, 0.12);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.08),
                0 12px 28px rgba(61, 18, 17, 0.1);
            opacity: 0;
            transform: translateY(18px) scale(0.985);
            overflow: hidden;
            isolation: isolate;
        }

        .section-separator-copy::before {
            content: "";
            position: absolute;
            inset: -10px -12px;
            border-radius: 999px;
            background:
                radial-gradient(circle at center, rgba(255, 244, 221, 0.14), rgba(255, 244, 221, 0) 72%);
            pointer-events: none;
        }

        .section-separator-copy::after {
            content: "";
            position: absolute;
            top: -34%;
            bottom: -34%;
            left: -26%;
            width: 16%;
            background: linear-gradient(90deg, rgba(255,255,255,0), rgba(255,255,255,0.34), rgba(255,255,255,0));
            transform: skewX(-18deg);
            filter: blur(1px);
            opacity: 0;
            pointer-events: none;
            z-index: 0;
            animation: sectionTopicSweep 2.8s ease-in-out infinite;
        }

        .section-separator.is-visible .section-separator-copy,
        .section-separator.is-visible .section-separator-line {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .section-separator:hover .section-separator-copy {
            transform: translateY(-2px);
            border-color: rgba(255, 237, 205, 0.14);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.1),
                0 16px 34px rgba(61, 18, 17, 0.14);
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
            font-size: clamp(1.04rem, 1.8vw, 1.48rem);
            line-height: 1.08;
            letter-spacing: -0.04em;
            font-weight: 700;
            text-wrap: balance;
            text-shadow: 0 2px 16px rgba(61, 18, 17, 0.18);
            clear: both;
            font-family: Georgia, "Times New Roman", serif;
            position: relative;
            z-index: 1;
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
                flex-direction: column;
                align-items: stretch;
                padding: 14px;
            }

            .hero-brand {
                justify-content: flex-start;
            }

            .hero-links {
                justify-content: flex-start;
            }

            .hero-action {
                align-self: flex-start;
            }

            .hero-carousel {
                border-radius: 26px;
            }

            .hero-controls {
                right: 14px;
                bottom: 14px;
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

            .section-separator {
                grid-template-columns: 1fr;
                gap: 10px;
                padding: 14px 8px 10px;
            }

            .section-separator-line::after {
                left: 18%;
                right: 18%;
            }

            .section-separator-copy {
                min-width: 0;
                max-width: 100%;
                order: 2;
                padding: 10px 16px 12px;
            }

            .section-separator-line:first-child {
                order: 1;
            }

            .section-separator-line:last-child {
                display: none;
            }

            .section-separator-copy h2 {
                font-size: clamp(1rem, 5.4vw, 1.35rem);
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

            .hero-links {
                gap: 8px;
            }

            .hero-links a,
            .hero-action {
                font-size: 0.82rem;
                padding: 9px 12px;
            }

            .hero-slide {
                min-height: 460px;
                padding: 22px 18px 74px;
            }

            .hero-copy h1 {
                font-size: 2.2rem;
            }

            .hero-controls {
                gap: 8px;
            }

            .hero-button {
                width: 42px;
                height: 42px;
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
    </style>
</head>
<body>
<section class="top-hero" id="top">
    <div class="hero-shell">
        <nav class="hero-nav" aria-label="Main navigation">
            <a class="hero-brand" href="#top" aria-label="Home">
                <img class="hero-brand-logo" src="images/testlogo.png" alt="Logo">
            </a>
            <div class="hero-links">
                <a href="#top">Home</a>
                <a href="#vision">Vision</a>
                <a href="#our-party">Our Party</a>
                <a href="#districts">Districts</a>
                <a href="#leadership">Leadership</a>
            </div>
            <a class="hero-action" href="#districts">Explore Now</a>
        </nav>

        <div class="hero-carousel" id="heroCarousel">
            <div class="hero-track" id="heroTrack">
                <?php foreach ($heroSlides as $slide): ?>
                    <article class="hero-slide" style="background-image: url('<?php echo htmlspecialchars($slide['image'], ENT_QUOTES, 'UTF-8'); ?>');">
                        <div class="hero-copy">
                            <span><?php echo htmlspecialchars($slide['eyebrow'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <h1><?php echo htmlspecialchars($slide['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
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

    <?php render_section_separator('districts', 'What we Build for a Nation'); ?>
    <div class="page-section-chunk">
        <?php
        $GLOBALS['SL_EMBED'] = true;
        include __DIR__ . '/sl.php';
        unset($GLOBALS['SL_EMBED']);
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
        const track = document.getElementById("heroTrack");
        const prev = document.getElementById("heroPrev");
        const next = document.getElementById("heroNext");
        const dotsHost = document.getElementById("heroDots");
        const slides = track ? Array.from(track.children) : [];

        if (!track || !prev || !next || !dotsHost || !slides.length) {
            return;
        }

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

        function getIndex() {
            return Math.max(0, Math.min(slides.length - 1, Math.round(track.scrollLeft / track.clientWidth)));
        }

        let updateFrame = 0;

        function applyUpdate() {
            const index = getIndex();
            dots.forEach((dot, dotIndex) => dot.classList.toggle("active", dotIndex === index));
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
        window.addEventListener("resize", applyUpdate);
        applyUpdate();
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
