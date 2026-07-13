<?php
declare(strict_types=1);

$GLOBALS['PROJECTS_PAGE_TITLE'] = 'Projects | Sri Lanka People\'s Front';
$GLOBALS['SL_EMBED'] = true;

require_once __DIR__ . '/translation_toggle.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#b85a4d">
    <title>Projects | Sri Lanka People's Front</title>
    <link rel="icon" type="image/x-icon" href="images/slpp.ico">
    <style>
        :root {
            --hero-red: #b85a4d;
            --hero-red-deep: #8f463d;
            --hero-gold: #e4bf6d;
            --hero-ink: #fff7ea;
            --hero-muted: rgba(255, 242, 221, 0.82);
            --glass: rgba(255, 255, 255, 0.08);
            --glass-strong: rgba(255, 255, 255, 0.12);
            --border: rgba(255, 237, 205, 0.14);
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at top left, rgba(201, 104, 86, 0.24), transparent 24%),
                radial-gradient(circle at top right, rgba(228, 191, 109, 0.22), transparent 28%),
                linear-gradient(180deg, #9f4f47 0%, #b76052 18%, #c77158 34%, #da975f 54%, #e6b765 72%, #efcf93 88%, #f3dcc2 100%);
            color: var(--hero-ink);
            font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .goog-te-banner-frame.skiptranslate,
        iframe.goog-te-banner-frame {
            display: none !important;
        }

        body {
            top: 0 !important;
        }

        .goog-tooltip,
        .goog-tooltip:hover,
        .goog-text-highlight {
            background: transparent !important;
            box-shadow: none !important;
        }

        .projects-page {
            min-height: 100vh;
            display: grid;
            grid-template-rows: auto 1fr auto;
        }

        .projects-header {
            position: sticky;
            top: 0;
            z-index: 30;
            padding: 14px;
            background: linear-gradient(180deg, rgba(92, 31, 27, 0.94), rgba(92, 31, 27, 0.82));
            border-bottom: 1px solid rgba(255, 237, 205, 0.12);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .projects-header-inner {
            width: min(1440px, 100%);
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 10px 16px;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid var(--border);
            box-shadow: 0 16px 36px rgba(44, 13, 12, 0.14);
        }

        .projects-brand {
            display: inline-flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            color: inherit;
            min-width: 0;
        }

        .projects-brand img {
            width: 52px;
            height: 52px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .projects-brand-text {
            display: grid;
            gap: 2px;
            min-width: 0;
        }

        .projects-brand-text strong {
            font-size: 1rem;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }

        .projects-brand-text span {
            font-size: 0.82rem;
            color: var(--hero-muted);
        }

        .projects-nav {
            display: inline-flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 10px;
        }

        .projects-nav a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 10px 16px;
            border-radius: 999px;
            color: var(--hero-ink);
            text-decoration: none;
            border: 1px solid rgba(255, 237, 205, 0.14);
            background: rgba(255, 255, 255, 0.08);
            transition: transform 220ms ease, background 220ms ease, border-color 220ms ease;
        }

        .projects-nav a:hover,
        .projects-nav a:focus-visible {
            transform: translateY(-1px);
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 237, 205, 0.24);
            outline: none;
        }

        .projects-header-actions {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            gap: 14px;
            flex: 1 1 auto;
            min-width: 0;
        }

        .projects-language-toggle {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            border-radius: 999px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255, 237, 205, 0.14);
            white-space: nowrap;
        }

        .projects-language-toggle .language-toggle {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0;
            border: 0;
            background: transparent;
            box-shadow: none;
            white-space: nowrap;
        }

        .projects-language-toggle .language-toggle-label {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: rgba(255, 247, 232, 0.82);
        }

        .projects-language-toggle .language-toggle-group {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .projects-language-toggle .language-toggle-button {
            min-width: 42px;
            min-height: 34px;
            padding: 0 12px;
            border-radius: 999px;
            border: 1px solid transparent;
            background: rgba(255,255,255,0.06);
            color: var(--hero-ink);
            font: inherit;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 220ms ease, background 220ms ease, border-color 220ms ease, box-shadow 220ms ease;
        }

        .projects-language-toggle .language-toggle-button:hover,
        .projects-language-toggle .language-toggle-button:focus-visible {
            outline: none;
            transform: translateY(-1px);
            background: rgba(255,255,255,0.14);
            border-color: rgba(255, 237, 205, 0.22);
            box-shadow: 0 8px 18px rgba(44, 13, 12, 0.12);
        }

        .projects-language-toggle .language-toggle-button.is-active {
            background: linear-gradient(135deg, rgba(228, 191, 109, 0.32), rgba(255,255,255,0.14));
            border-color: rgba(255, 237, 205, 0.26);
            box-shadow: 0 10px 20px rgba(44, 13, 12, 0.14);
        }

        .projects-language-toggle .language-toggle-widget {
            position: absolute;
            left: -9999px;
            top: auto;
            width: 1px;
            height: 1px;
            overflow: hidden;
        }

        .projects-main {
            width: 100%;
            padding: 18px 14px 24px;
        }

        .projects-shell {
            width: min(1440px, 100%);
            margin: 0 auto;
            border-radius: 32px;
            overflow: hidden;
            box-shadow: 0 28px 70px rgba(88, 33, 28, 0.18);
        }

        .projects-intro {
            margin: 0 auto 14px;
            width: min(1440px, 100%);
            padding: 0 4px;
        }

        .projects-intro h1 {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(2rem, 3.8vw, 3.6rem);
            line-height: 0.98;
            letter-spacing: -0.05em;
        }

        .projects-intro p {
            margin: 10px 0 0;
            max-width: 72ch;
            color: rgba(255, 247, 232, 0.9);
            line-height: 1.75;
            font-size: clamp(0.98rem, 1.45vw, 1.08rem);
        }

        .projects-footer {
            margin-top: 6px;
            padding: 18px 14px 22px;
        }

        .projects-footer-inner {
            width: min(1440px, 100%);
            margin: 0 auto;
            padding: 18px 20px;
            border-radius: 28px;
            background: linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.03));
            border: 1px solid rgba(255, 237, 205, 0.12);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
        }

        .projects-footer-inner p {
            margin: 0;
            color: rgba(255, 247, 232, 0.82);
        }

        @media (max-width: 768px) {
            .projects-header {
                padding: 10px;
            }

            .projects-header-inner {
                padding: 12px;
                border-radius: 20px;
                align-items: flex-start;
                flex-direction: column;
            }

            .projects-header-actions {
                width: 100%;
                justify-content: flex-start;
                flex-wrap: wrap;
            }

            .projects-nav {
                width: 100%;
                justify-content: flex-start;
            }

            .projects-main {
                padding: 12px 10px 18px;
            }

            .projects-shell {
                border-radius: 24px;
            }

            .projects-intro {
                padding: 0 2px;
            }

            .projects-footer {
                padding: 12px 10px 18px;
            }

            .projects-footer-inner {
                padding: 16px;
                border-radius: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="projects-page">
        <header class="projects-header">
            <div class="projects-header-inner">
                <a class="projects-brand" href="index.php" aria-label="Go to home page">
                    <img src="images/testlogo.png" alt="Sri Lanka People's Front logo">
                    <div class="projects-brand-text">
                        <strong>Sri Lanka People's Front</strong>
                        <span>Projects</span>
                    </div>
                </a>
                <div class="projects-header-actions">
                    <nav class="projects-nav" aria-label="Projects navigation">
                        <a href="index.php#vision">Vision</a>
                        <a href="index.php#our-party">Our Party</a>
                        <a href="index.php#leadership">Leadership</a>
                        <a href="projects.php" aria-current="page">Projects</a>
                    </nav>
                    <div class="projects-language-toggle">
                        <?php render_language_toggle(); ?>
                    </div>
                </div>
            </div>
        </header>

        <main class="projects-main">
            <div class="projects-intro">
                <h1>Development projects by district</h1>
                <p>Explore the district map, project listings, and detailed development profiles in a dedicated full-page view.</p>
            </div>

            <div class="projects-shell">
                <?php include __DIR__ . '/sl.php'; ?>
            </div>
        </main>

        <footer class="projects-footer">
            <div class="projects-footer-inner">
            </div>
        </footer>
    </div>
</body>
</html>
