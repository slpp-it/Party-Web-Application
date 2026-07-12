<?php
declare(strict_types=1);

http_response_code(200);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>District Coordination Heads</title>
    <link rel="icon" type="image/x-icon" href="images/slpp.ico">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: linear-gradient(180deg, #9a4a41 0%, #883d36 100%);
            color: #fff7ea;
            font-family: "Segoe UI", Arial, sans-serif;
        }

        main {
            width: min(680px, calc(100vw - 32px));
            padding: 28px;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 18px 50px rgba(60, 12, 10, 0.2);
            text-align: center;
        }

        h1 {
            margin: 0 0 12px;
            font-size: clamp(1.6rem, 4vw, 2.3rem);
        }

        p {
            margin: 0;
            line-height: 1.6;
            color: rgba(255, 247, 234, 0.86);
        }
    </style>
</head>
<body>
    <main>
        <h1>District Coordination Heads are currently disabled</h1>
        <p>This section is not being shown right now. We can bring it back later when it is needed again.</p>
    </main>
</body>
</html>
<?php
exit;

require_once __DIR__ . '/db.php';

function district_heads_placeholder(string $name, string $district): string
{
    $label = htmlspecialchars($name . ' - ' . $district, ENT_QUOTES, 'UTF-8');
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 320" role="img" aria-label="' . $label . '">'
        . '<defs>'
        . '<linearGradient id="dg" x1="0%" y1="0%" x2="100%" y2="100%">'
        . '<stop offset="0%" stop-color="#a24d43"/>'
        . '<stop offset="100%" stop-color="#e4bf6d"/>'
        . '</linearGradient>'
        . '</defs>'
        . '<rect width="320" height="320" rx="56" fill="url(#dg)"/>'
        . '<circle cx="160" cy="108" r="68" fill="rgba(255,255,255,0.18)"/>'
        . '<circle cx="160" cy="130" r="46" fill="rgba(255,250,240,0.96)"/>'
        . '<path d="M56 282c24-64 64-96 104-96s80 32 104 96" fill="rgba(255,250,240,0.92)"/>'
        . '<text x="160" y="258" text-anchor="middle" font-family="Segoe UI, Arial, sans-serif" font-size="18" font-weight="700" fill="#fff7ea">' . htmlspecialchars($district, ENT_QUOTES, 'UTF-8') . '</text>'
        . '</svg>';

    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
}

function district_heads_asset_url(?string $value, string $name, string $district): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return district_heads_placeholder($name, $district);
    }

    if (
        preg_match('#^https?://#i', $value) === 1 ||
        str_starts_with($value, '/') ||
        str_starts_with($value, './') ||
        str_starts_with($value, '../') ||
        str_starts_with($value, 'data:') ||
        preg_match('#^[a-zA-Z0-9][a-zA-Z0-9/_\-. ]+\.(?:png|jpe?g|gif|webp|svg|bmp|avif)$#i', $value) === 1
    ) {
        return $value;
    }

    return district_heads_placeholder($name, $district);
}

function district_heads_wikipedia_url(string $name, string $district, ?string $value): string
{
    $value = trim((string) $value);
    if ($value !== '' && preg_match('#^https?://#i', $value) === 1) {
        return $value;
    }

    $query = trim($name . ' ' . $district);
    return 'https://en.wikipedia.org/wiki/Special:Search?search=' . rawurlencode($query);
}

function district_heads_load(PDO $pdo): array
{
    $schemaStatement = $pdo->query('SHOW COLUMNS FROM district_coordination_heads');
    $availableColumns = array_map(
        static fn (array $column): string => (string) ($column['Field'] ?? ''),
        $schemaStatement->fetchAll()
    );

    $selectColumns = ['coordinator_name', 'district_name', 'photo_path', 'wikipedia_url', 'display_order'];
    if (in_array('phone_number', $availableColumns, true)) {
        $selectColumns[] = 'phone_number';
    }

    $statement = $pdo->query(
        'SELECT ' . implode(', ', $selectColumns) . '
         FROM district_coordination_heads
         WHERE is_active = 1
         ORDER BY display_order ASC, id ASC'
    );

    $heads = [];
    foreach ($statement->fetchAll() as $row) {
        $name = trim((string) ($row['coordinator_name'] ?? ''));
        $district = trim((string) ($row['district_name'] ?? ''));
        $phone = trim((string) ($row['phone_number'] ?? ''));
        $wiki = trim((string) ($row['wikipedia_url'] ?? ''));

        if ($name === '' || $district === '') {
            continue;
        }

        $heads[] = [
            'name' => $name,
            'district' => $district,
            'phone' => $phone,
            'photo' => district_heads_asset_url($row['photo_path'] ?? null, $name, $district),
            'wiki' => district_heads_wikipedia_url($name, $district, $wiki),
        ];
    }

    return $heads;
}

$districtHeads = [];
$districtHeadsLoadMessage = '';
try {
    $districtHeads = district_heads_load(getDbConnection());
} catch (Throwable $exception) {
    $districtHeadsLoadMessage = 'District coordination heads could not be loaded from the database.';
}

if ($districtHeads === []) {
    $districtHeads = [
        [
            'name' => 'Mahinda Rajapaksa',
            'district' => 'Hambantota',
            'phone' => '',
            'photo' => district_heads_placeholder('Mahinda Rajapaksa', 'Hambantota'),
            'wiki' => district_heads_wikipedia_url('Mahinda Rajapaksa', 'Hambantota', 'https://en.wikipedia.org/wiki/Mahinda_Rajapaksa'),
        ],
        [
            'name' => 'Namal Rajapaksa',
            'district' => 'Matara',
            'phone' => '',
            'photo' => district_heads_placeholder('Namal Rajapaksa', 'Matara'),
            'wiki' => district_heads_wikipedia_url('Namal Rajapaksa', 'Matara', 'https://en.wikipedia.org/wiki/Namal_Rajapaksa'),
        ],
        [
            'name' => 'Sagara Kariyawasam',
            'district' => 'Galle',
            'phone' => '',
            'photo' => district_heads_placeholder('Sagara Kariyawasam', 'Galle'),
            'wiki' => district_heads_wikipedia_url('Sagara Kariyawasam', 'Galle', 'https://en.wikipedia.org/wiki/Sagara_Kariyawasam'),
        ],
    ];
    if ($districtHeadsLoadMessage === '') {
        $districtHeadsLoadMessage = 'No active district coordination heads were found, so fallback records are shown instead.';
    }
}

$districtHeadsEmbed = !empty($GLOBALS['DISTRICT_HEADS_EMBED']);
$districtHeadsAppId = $districtHeadsEmbed ? 'district-heads-app-' . substr(md5((string) mt_rand()), 0, 8) : 'district-heads-app';
?>
<?php if (!$districtHeadsEmbed): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our District Coordination Heads</title>
    <link rel="icon" type="image/x-icon" href="images/slpp.ico">
<?php endif; ?>
    <style>
        .district-heads-app {
            width: 100%;
            margin: 0 auto;
            padding: 12px 14px 22px;
            color: #fff7ea;
            font-family: "Aptos", "Segoe UI", "Helvetica Neue", Arial, sans-serif;
            content-visibility: auto;
            --district-heading-font: "Iowan Old Style", "Palatino Linotype", "Book Antiqua", Georgia, serif;
            --district-text-font: "Aptos", "Segoe UI", "Helvetica Neue", Arial, sans-serif;
        }

        body.district-heads-page {
            margin: 0;
            background:
                radial-gradient(circle at top left, rgba(188, 90, 77, 0.34), transparent 24%),
                radial-gradient(circle at top right, rgba(222, 160, 115, 0.16), transparent 28%),
                linear-gradient(180deg, #9a4a41 0%, #a75348 22%, #b15a4e 48%, #9e4b42 78%, #883d36 100%);
            min-height: 100vh;
        }

        .district-heads-shell {
            position: relative;
            overflow: hidden;
            border-radius: 32px;
            padding: clamp(18px, 2vw, 28px);
            background:
                radial-gradient(circle at 8% 10%, rgba(244, 210, 122, 0.14), transparent 24%),
                radial-gradient(circle at 92% 0%, rgba(184, 90, 77, 0.18), transparent 26%),
                linear-gradient(145deg, rgba(127, 51, 44, 0.28), rgba(184, 90, 77, 0.18)),
                rgba(255,255,255,0.05);
            border: 1px solid rgba(255, 237, 205, 0.09);
            box-shadow: 0 22px 58px rgba(60, 12, 10, 0.16);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            will-change: transform;
        }

        @media (max-width: 768px) {
            .district-heads-shell {
                backdrop-filter: none;
                -webkit-backdrop-filter: none;
            }
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

        .district-heads-head {
            display: grid;
            gap: 12px;
            margin-bottom: 16px;
            width: 100%;
        }

        .district-heads-head-rail {
            display: flex;
            align-items: center;
            gap: 18px;
            width: 100%;
            opacity: 0.72;
        }

        .district-heads-head-rail::before,
        .district-heads-head-rail::after {
            content: "";
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, rgba(244, 210, 122, 0), rgba(244, 210, 122, 0.36));
        }

        .district-heads-head-rail::after {
            background: linear-gradient(90deg, rgba(244, 210, 122, 0.36), rgba(244, 210, 122, 0));
        }

        .district-heads-head-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 18px;
            border-radius: 18px;
            background: rgba(255,255,255,0.08);
            color: #fff7ea;
            font-size: 0.92rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            white-space: nowrap;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.08);
        }

        .district-heads-head h2 {
            margin: 0;
            font-family: var(--district-text-font);
            font-size: clamp(2.2rem, 4.8vw, 4rem);
            line-height: 0.98;
            letter-spacing: -0.03em;
            font-weight: 700;
            text-align: left;
        }

        .district-heads-head-subtitle {
            margin: 0;
            max-width: 56ch;
            color: rgba(255, 248, 236, 0.92);
            font-size: clamp(1.02rem, 1.55vw, 1.18rem);
            line-height: 1.85;
            font-family: var(--district-text-font);
            text-align: left;
        }

        .district-heads-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .district-heads-grid.is-collapsed .district-head-card.is-hidden {
            display: none;
        }

        .district-head-card {
            position: relative;
            display: grid;
            grid-template-columns: minmax(150px, 34%) minmax(0, 1fr) 48px;
            gap: 0;
            align-items: stretch;
            min-height: 248px;
            padding: 14px;
            border-radius: 24px;
            background:
                radial-gradient(circle at 14% 10%, rgba(241, 207, 122, 0.11), transparent 26%),
                radial-gradient(circle at 92% 14%, rgba(255,255,255,0.08), transparent 25%),
                linear-gradient(180deg, rgba(255,255,255,0.105), rgba(255,255,255,0.045)),
                rgba(127, 51, 44, 0.14);
            border: 1px solid rgba(255, 231, 183, 0.075);
            box-shadow:
                0 12px 28px rgba(71, 20, 18, 0.11);
            transition:
                transform 220ms cubic-bezier(.2, .8, .2, 1),
                border-color 220ms cubic-bezier(.2, .8, .2, 1),
                box-shadow 220ms cubic-bezier(.2, .8, .2, 1),
                background 220ms cubic-bezier(.2, .8, .2, 1);
            overflow: hidden;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .district-head-card::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 14% 10%, rgba(241, 207, 122, 0.14), transparent 26%),
                radial-gradient(circle at 92% 14%, rgba(255,255,255,0.1), transparent 25%);
            pointer-events: none;
        }

        .district-head-card:hover,
        .district-head-card:focus-within {
            transform: translateY(-4px);
            border-color: rgba(241, 207, 122, 0.2);
            box-shadow:
                0 20px 44px rgba(71, 20, 18, 0.16);
            background:
                radial-gradient(circle at 14% 10%, rgba(241, 207, 122, 0.14), transparent 26%),
                radial-gradient(circle at 92% 14%, rgba(255,255,255,0.1), transparent 25%),
                linear-gradient(180deg, rgba(255,255,255,0.13), rgba(255,255,255,0.055)),
                rgba(127, 51, 44, 0.16);
        }

        .district-head-photo {
            position: relative;
            width: 100%;
            min-height: 100%;
            border-radius: 18px 0 0 18px;
            overflow: hidden;
            border: 1px solid rgba(255, 231, 183, 0.08);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.12),
                0 12px 24px rgba(71, 20, 18, 0.11);
            background:
                linear-gradient(145deg, rgba(241, 207, 122, 0.12), rgba(184, 90, 77, 0.16)),
                rgba(255,255,255,0.06);
            z-index: 1;
        }

        .district-head-photo::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: 1;
            background:
                linear-gradient(180deg, rgba(255,255,255,0.04), transparent 58%, rgba(71, 20, 18, 0.1)),
                radial-gradient(circle at top right, rgba(241, 207, 122, 0.1), transparent 28%);
            pointer-events: none;
        }

        .district-head-photo::after {
            content: "";
            position: absolute;
            inset: 0;
            z-index: 2;
            border-radius: inherit;
            border: 1px solid rgba(255,255,255,0.035);
            pointer-events: none;
        }

        .district-head-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .district-head-copy {
            display: grid;
            gap: 8px;
            align-content: center;
            padding: 18px 18px 18px 20px;
            min-width: 0;
            z-index: 1;
            position: relative;
        }

        .district-head-copy::before {
            content: "";
            position: absolute;
            left: 0;
            top: 14px;
            bottom: 14px;
            width: 1px;
            background: linear-gradient(180deg, rgba(255,255,255,0.14), rgba(244, 210, 122, 0.34), rgba(255,255,255,0.08));
            box-shadow: 0 0 14px rgba(244, 210, 122, 0.12);
        }

        .district-head-district {
            display: inline-flex;
            width: fit-content;
            max-width: 100%;
            align-items: center;
            padding: 7px 12px;
            border-radius: 999px;
            background:
                linear-gradient(135deg, rgba(241, 207, 122, 0.36), rgba(184, 90, 77, 0.52)),
                rgba(255,255,255,0.08);
            color: #fffaf0;
            font-size: 0.71rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            font-weight: 800;
            line-height: 1.2;
            text-shadow: 0 1px 1px rgba(0,0,0,0.14);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.15);
            border: 1px solid rgba(255, 231, 183, 0.13);
            font-family: var(--district-text-font);
        }

        .district-head-copy h3 {
            margin: 0;
            font-size: clamp(1.2rem, 1.7vw, 1.55rem);
            line-height: 1.06;
            letter-spacing: -0.04em;
            color: #fff7ea;
            font-family: var(--district-heading-font);
            font-weight: 700;
        }

        .district-head-copy p {
            margin: 0;
            color: rgba(255, 248, 236, 0.82);
            font-size: 0.96rem;
            line-height: 1.72;
            font-family: var(--district-text-font);
            max-width: 40ch;
        }

        .district-head-phone {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            margin-top: 8px;
            color: #fff7ea;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 600;
            letter-spacing: 0.01em;
            font-family: var(--district-text-font);
        }

        .district-head-phone svg {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
        }

        .district-head-phone:hover,
        .district-head-phone:focus-visible {
            color: #fffaf0;
            outline: none;
        }

        .district-head-wiki {
            width: 46px;
            height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.05);
            color: #fff7ea;
            flex-shrink: 0;
            text-decoration: none;
            z-index: 1;
            transition: transform 220ms cubic-bezier(.2, .8, .2, 1), background 220ms cubic-bezier(.2, .8, .2, 1), border-color 220ms cubic-bezier(.2, .8, .2, 1);
        }

        .district-head-wiki:hover,
        .district-head-wiki:focus-visible {
            transform: translateX(2px) translateY(-1px);
            background: rgba(255,255,255,0.12);
            border-color: rgba(244, 210, 122, 0.22);
            outline: none;
        }

        .district-heads-more {
            display: grid;
            justify-items: center;
            gap: 8px;
            margin-top: 18px;
        }

        .district-heads-more button {
            width: 52px;
            height: 52px;
            border-radius: 999px;
            border: 1px solid rgba(244, 210, 122, 0.44);
            background: rgba(255,255,255,0.08);
            color: #fff7ea;
            display: inline-grid;
            place-items: center;
            cursor: pointer;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.08), 0 10px 22px rgba(60, 12, 10, 0.12);
            transition: transform 220ms cubic-bezier(.2, .8, .2, 1), background 220ms cubic-bezier(.2, .8, .2, 1), border-color 220ms cubic-bezier(.2, .8, .2, 1);
            font-family: var(--district-text-font);
        }

        .district-heads-more button:hover,
        .district-heads-more button:focus-visible {
            transform: translateY(-2px);
            background: rgba(255,255,255,0.12);
            border-color: rgba(244, 210, 122, 0.6);
            outline: none;
        }

        .district-heads-more button svg {
            width: 22px;
            height: 22px;
            transition: transform 220ms cubic-bezier(.2, .8, .2, 1);
        }

        .district-heads-more button[aria-expanded="true"] svg {
            transform: rotate(180deg);
        }

        .district-heads-more-label {
            margin: 0;
            color: rgba(255, 248, 236, 0.8);
            font-size: 0.84rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-family: var(--district-text-font);
        }

        @media (max-width: 1080px) {
            .district-heads-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 720px) {
            .district-heads-app {
                padding: 10px;
            }

            .district-heads-shell {
                border-radius: 24px;
            }

            .district-heads-grid {
                grid-template-columns: 1fr;
            }

            .district-head-card {
                grid-template-columns: 1fr;
                min-height: 0;
                padding: 12px;
                gap: 0;
            }

            .district-head-photo {
                min-height: 220px;
                border-radius: 18px 18px 0 0;
            }

            .district-head-copy {
                padding: 16px 4px 12px;
            }

            .district-head-copy::before {
                left: 4px;
                top: 0;
                right: 4px;
                bottom: auto;
                width: auto;
                height: 1px;
            }

            .district-head-wiki {
                margin-top: 2px;
            }

            .district-heads-more {
                margin-top: 14px;
            }

        }
    </style>
<?php if (!$districtHeadsEmbed): ?>
</head>
<body class="district-heads-page">
<?php endif; ?>
    <section id="<?php echo htmlspecialchars($districtHeadsAppId, ENT_QUOTES, 'UTF-8'); ?>" class="district-heads-app">
        <div class="district-heads-shell">
            <header class="district-heads-head" data-float style="--float-delay: 0ms;">
                <div class="district-heads-head-rail" aria-hidden="true">
                    <span></span>
                    
                    <span></span>
                </div>
                <h2>District Coordination Heads</h2>
                <p class="district-heads-head-subtitle">Find the contact information of District Coordination Heads for each district. Reach out to the appropriate district leader for support, coordination, and district-related inquiries.</p>
            </header>

            <div class="district-heads-grid is-collapsed" id="districtHeadsGrid" aria-label="District coordination heads">
                <?php foreach ($districtHeads as $index => $head): ?>
                    <article class="district-head-card<?php echo $index >= 3 ? ' is-hidden' : ''; ?>" data-float style="--float-delay: <?php echo (int) (($index + 1) * 70); ?>ms;">
                        <div class="district-head-photo">
                            <img src="<?php echo htmlspecialchars($head['photo'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($head['name'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy" decoding="async">
                        </div>
                        <div class="district-head-copy">
                            <span class="district-head-district"><?php echo htmlspecialchars($head['district'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <h3><?php echo htmlspecialchars($head['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <?php if (trim((string) ($head['phone'] ?? '')) !== ''): ?>
                                <a class="district-head-phone" href="tel:<?php echo htmlspecialchars(preg_replace('/\D+/', '', (string) $head['phone']) ?: (string) $head['phone'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 0 0-1.01.24l-1.57 1.97c-2.83-1.49-5.15-3.8-6.62-6.63l1.97-1.57c.3-.3.4-.74.24-1.16-.37-1.11-.56-2.3-.56-3.53 0-.54-.45-.99-.99-.99H4.19C3.65 3.18 3.18 3.65 3.18 4.19c0 9.27 7.55 16.82 16.82 16.82.54 0 .99-.45.99-.99v-3.65c0-.54-.45-.99-.99-.99z"/></svg>
                                    <span><?php echo htmlspecialchars((string) $head['phone'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </a>
                            <?php endif; ?>
                        </div>
                        <a class="district-head-wiki" href="<?php echo htmlspecialchars($head['wiki'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo htmlspecialchars($head['name'] . ' on Wikipedia', ENT_QUOTES, 'UTF-8'); ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php if (count($districtHeads) > 3): ?>
                <div class="district-heads-more">
                    <button type="button" id="districtHeadsToggle" aria-controls="districtHeadsGrid" aria-expanded="false" aria-label="Show more district coordination heads">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M6 9l6 6 6-6"></path>
                        </svg>
                    </button>
                    <p class="district-heads-more-label">See more</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <script>
        (() => {
            const root = document.getElementById(<?php echo json_encode($districtHeadsAppId); ?>);
            if (!root) {
                return;
            }

            const floatItems = Array.from(root.querySelectorAll("[data-float]"));
            if (!floatItems.length) {
                return;
            }

            if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
                floatItems.forEach((item) => item.classList.add("is-visible"));
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

            floatItems.forEach((item) => observer.observe(item));

            const grid = root.querySelector("#districtHeadsGrid");
            const toggle = root.querySelector("#districtHeadsToggle");
            if (!grid || !toggle) {
                return;
            }

            const cards = Array.from(grid.querySelectorAll(".district-head-card"));
            const visibleCount = Math.min(3, cards.length);
            if (cards.length <= visibleCount) {
                toggle.closest(".district-heads-more")?.remove();
                return;
            }

            const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
            let isExpanded = false;
            let rotationStart = 0;
            let rotationTimer = null;

            const renderCollapsedWindow = (startIndex) => {
                const maxStart = Math.max(0, cards.length - visibleCount);
                rotationStart = Math.min(Math.max(0, startIndex), maxStart);
                grid.classList.add("is-collapsed");

                cards.forEach((card, index) => {
                    const isVisible = index >= rotationStart && index < rotationStart + visibleCount;
                    card.classList.toggle("is-hidden", !isVisible);
                });
            };

            const renderExpanded = () => {
                grid.classList.remove("is-collapsed");
                cards.forEach((card) => {
                    card.classList.remove("is-hidden");
                });
            };

            const stopRotation = () => {
                if (rotationTimer !== null) {
                    window.clearInterval(rotationTimer);
                    rotationTimer = null;
                }
            };

            const startRotation = () => {
                if (reduceMotion || rotationTimer !== null) {
                    return;
                }

                rotationTimer = window.setInterval(() => {
                    if (isExpanded) {
                        return;
                    }

                    const maxStart = Math.max(0, cards.length - visibleCount);
                    if (maxStart === 0) {
                        return;
                    }

                    const nextStart = rotationStart >= maxStart ? 0 : rotationStart + 1;
                    renderCollapsedWindow(nextStart);
                }, 8000);
            };

            renderCollapsedWindow(0);

            toggle.addEventListener("click", () => {
                isExpanded = !isExpanded;
                toggle.setAttribute("aria-expanded", isExpanded ? "true" : "false");

                if (isExpanded) {
                    stopRotation();
                    renderExpanded();
                } else {
                    renderCollapsedWindow(rotationStart);
                    startRotation();
                }

                const label = toggle.nextElementSibling;
                if (label) {
                    label.textContent = isExpanded ? "Show less" : "See more";
                }
                toggle.setAttribute(
                    "aria-label",
                    isExpanded ? "Show fewer district coordination heads" : "Show more district coordination heads"
                );
            });

            startRotation();
        })();
    </script>
<?php if (!$districtHeadsEmbed): ?>
</body>
</html>
<?php endif; ?>
