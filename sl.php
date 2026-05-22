<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function svg_data_uri(string $title, string $tag, array $colors): string
{
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 720 420" role="img" aria-label="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">'
        . '<defs>'
        . '<linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">'
        . '<stop offset="0%" stop-color="' . $colors[0] . '"/>'
        . '<stop offset="100%" stop-color="' . $colors[1] . '"/>'
        . '</linearGradient>'
        . '</defs>'
        . '<rect width="720" height="420" rx="34" fill="url(#bg)"/>'
        . '<circle cx="610" cy="80" r="68" fill="rgba(255,255,255,0.16)"/>'
        . '<circle cx="132" cy="332" r="124" fill="rgba(255,255,255,0.09)"/>'
        . '<path d="M86 302C152 240 231 224 314 244C394 262 469 259 548 200L640 286V420H74Z" fill="rgba(8,18,34,0.14)"/>'
        . '<path d="M96 326C168 262 247 256 326 276C406 294 493 279 592 224" stroke="rgba(255,255,255,0.55)" stroke-width="8" stroke-linecap="round" fill="none"/>'
        . '<rect x="56" y="54" width="122" height="34" rx="17" fill="rgba(255,255,255,0.16)"/>'
        . '<text x="117" y="77" text-anchor="middle" font-family="Segoe UI, Arial, sans-serif" font-size="18" fill="#ffffff" letter-spacing="2">' . htmlspecialchars(strtoupper($tag), ENT_QUOTES, 'UTF-8') . '</text>'
        . '<text x="56" y="152" font-family="Segoe UI, Arial, sans-serif" font-size="42" font-weight="700" fill="#ffffff">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</text>'
        . '<text x="56" y="190" font-family="Segoe UI, Arial, sans-serif" font-size="20" fill="rgba(255,255,255,0.82)">District project preview</text>'
        . '</svg>';

    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
}

function project_slug(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-') ?: 'project';
}

function detect_base64_image_mime(string $binary): string
{
    if (str_starts_with($binary, "\x89PNG")) {
        return 'image/png';
    }

    if (str_starts_with($binary, "\xFF\xD8\xFF")) {
        return 'image/jpeg';
    }

    if (str_starts_with($binary, 'GIF87a') || str_starts_with($binary, 'GIF89a')) {
        return 'image/gif';
    }

    if (str_starts_with($binary, 'RIFF') && substr($binary, 8, 4) === 'WEBP') {
        return 'image/webp';
    }

    if (str_starts_with($binary, 'BM')) {
        return 'image/bmp';
    }

    if (str_contains(substr($binary, 0, 256), '<svg')) {
        return 'image/svg+xml';
    }

    return 'image/png';
}

function normalize_project_image(?string $value, string $fallbackTitle, string $fallbackTag, array $fallbackColors): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return svg_data_uri($fallbackTitle, $fallbackTag, $fallbackColors);
    }

    if (preg_match('/^<img[^>]+src=["\']([^"\']+)["\']/i', $value, $matches) === 1) {
        $value = trim($matches[1]);
    }

    if (preg_match('#^data:image/[^;]+;base64,(.+)$#is', $value, $matches) === 1) {
        $value = trim($matches[1]);
    }

    $compact = preg_replace('/\s+/', '', $value) ?? '';
    if ($compact !== '' && preg_match('/^[A-Za-z0-9+\/=]+$/', $compact) === 1) {
        $decoded = base64_decode($compact, true);
        if ($decoded !== false) {
            $mime = detect_base64_image_mime($decoded);
            return 'data:' . $mime . ';base64,' . $compact;
        }
    }

    if (
        preg_match('#^https?://#i', $value) === 1 ||
        str_starts_with($value, '/') ||
        str_starts_with($value, './') ||
        str_starts_with($value, '../')
    ) {
        return $value;
    }

    if (str_starts_with($value, 'data:image/')) {
        return $value;
    }

    return svg_data_uri($fallbackTitle, $fallbackTag, $fallbackColors);
}

function load_projects_by_district(PDO $pdo, array $districts, array $colorPairs): array
{
    $districtIndexMap = [];
    foreach ($districts as $index => $district) {
        $districtIndexMap[$district['id']] = $index;
    }

    $projectsByDistrict = [];
    $statement = $pdo->query(
        'SELECT district_id, project_name, project_slug, project_image, project_description, category, status, budget, display_order
         FROM district_projects
         WHERE is_active = 1
         ORDER BY district_id, display_order, id'
    );

    foreach ($statement->fetchAll() as $row) {
        $districtId = strtolower(trim((string) ($row['district_id'] ?? '')));
        if ($districtId === '' || !isset($districtIndexMap[$districtId])) {
            continue;
        }

        $district = $districts[$districtIndexMap[$districtId]];
        $districtIndex = $districtIndexMap[$districtId];
        $projectIndex = count($projectsByDistrict[$districtId] ?? []);
        $pair = $colorPairs[($districtIndex + $projectIndex) % count($colorPairs)];
        $title = trim((string) ($row['project_name'] ?? ''));
        if ($title === '') {
            continue;
        }

        $image = normalize_project_image(
            (string) ($row['project_image'] ?? ''),
            $title,
            $district['name'],
            $pair
        );

        $projectsByDistrict[$districtId][] = [
            'title' => $title,
            'slug' => trim((string) ($row['project_slug'] ?? '')) ?: project_slug($title),
            'category' => trim((string) ($row['category'] ?? 'General')) ?: 'General',
            'caption' => trim((string) ($row['project_description'] ?? 'Project description is coming soon.')) ?: 'Project description is coming soon.',
            'status' => trim((string) ($row['status'] ?? 'Planning')) ?: 'Planning',
            'budget' => trim((string) ($row['budget'] ?? 'Budget on request')) ?: 'Budget on request',
            'image' => $image,
        ];
    }

    return $projectsByDistrict;
}

$districts = [
    ['id' => 'jaffna', 'name' => 'Jaffna', 'province' => 'Northern', 'accent' => '#57a3ff', 'points' => '328,124 321,123 318,134 298,120 304,113 304,119 309,119 315,113 312,111 320,106 328,107 329,104 324,99 316,101 308,109 307,106 301,107 293,115 295,126 309,135 305,135 291,125 281,124 266,109 260,113 252,93 262,85 275,81 308,82 312,96 318,97 320,94 329,100 332,99 330,97 336,97 355,119 345,135'],
    ['id' => 'kilinochchi', 'name' => 'Kilinochchi', 'province' => 'Northern', 'accent' => '#6bc7a8', 'points' => '430,181 442,181 432,202 425,196 420,196 408,208 344,211 338,217 332,238 312,248 307,246 306,225 309,220 299,211 290,208 293,193 302,188 311,189 317,182 324,180 326,173 321,160 301,142 321,155 329,154 337,159 344,165 342,168 349,172 343,175 353,180 366,176 368,179 373,178 381,166 388,166 390,163 394,168 404,166'],
    ['id' => 'mullaitivu', 'name' => 'Mullaitivu', 'province' => 'Northern', 'accent' => '#48b8c8', 'points' => '519,304 522,303 524,311 486,322 483,319 486,302 471,293 464,295 464,289 451,281 442,269 436,269 420,282 410,274 389,271 380,277 382,281 389,280 391,289 390,297 381,306 359,313 327,313 324,300 335,284 329,243 335,238 340,218 345,213 412,210 421,198 433,204 439,188 448,179 455,185 448,182 444,185 456,193 472,212 482,214 483,216 473,217 474,227 492,239 493,226 503,240 508,261 500,259 497,270 500,272 507,269 509,277 512,272 515,274 521,285 518,287 524,292 517,289 512,296'],
    ['id' => 'mannar', 'name' => 'Mannar', 'province' => 'Northern', 'accent' => '#f3ab61', 'points' => '338,352 322,371 322,376 333,393 301,401 286,397 286,431 282,436 274,429 252,425 261,394 261,378 254,363 256,355 253,351 257,341 254,334 263,317 272,315 274,305 283,302 293,290 296,268 302,265 306,249 314,250 327,244 327,259 333,270 333,282 321,301 324,313 328,316 366,314 378,325 381,334 381,343 377,346 356,352'],
    ['id' => 'vavuniya', 'name' => 'Vavuniya', 'province' => 'Northern', 'accent' => '#7c7bff', 'points' => '430,381 416,394 394,389 382,400 377,416 365,425 359,425 341,404 340,395 324,375 336,355 355,355 383,345 381,326 369,313 382,308 394,294 391,279 383,279 384,275 405,275 418,285 440,271 451,284 462,291 463,297 472,296 484,304 481,314 484,326 462,337 435,340 447,347 451,359'],
    ['id' => 'trincomalee', 'name' => 'Trincomalee', 'province' => 'Eastern', 'accent' => '#46d5bc', 'points' => '588,438 604,453 619,454 627,461 631,450 623,450 623,441 630,435 633,444 635,446 638,443 637,448 642,454 648,480 642,467 635,469 644,488 647,490 649,487 654,514 638,510 622,511 618,520 611,523 608,538 599,540 601,524 584,507 575,510 553,502 544,505 530,482 533,467 546,448 534,438 534,430 527,421 522,405 525,395 516,367 524,350 516,340 488,323 520,315 521,320 529,325 537,316 536,310 546,322 542,321 539,325 548,326 564,350 565,342 569,344 579,365 573,368 581,373 585,370 589,376 583,373 580,378 589,383 591,398 594,400 597,397 594,383 605,399 603,413 609,429 608,422 600,420 600,438 596,438 596,431 589,429 575,441 584,445'],
    ['id' => 'anuradhapura', 'name' => 'Anuradhapura', 'province' => 'North Central', 'accent' => '#f5bc52', 'points' => '460,586 451,591 438,618 429,617 417,623 407,623 406,608 400,600 400,593 390,586 388,568 361,550 310,530 283,513 275,518 270,509 262,474 267,472 284,448 284,436 289,428 287,399 300,404 337,395 340,407 348,413 351,422 357,427 365,428 379,417 385,400 392,393 399,391 407,397 417,396 454,359 449,346 438,340 461,340 490,327 515,342 521,349 514,364 522,392 519,405 525,423 531,430 532,440 543,450 529,470 529,486 520,491 517,500 495,504 488,510 477,547 478,560 486,560 478,568 477,576'],
    ['id' => 'polonnaruwa', 'name' => 'Polonnaruwa', 'province' => 'North Central', 'accent' => '#67c8ff', 'points' => '634,593 633,596 617,595 608,604 604,637 609,648 604,672 585,666 569,653 517,654 509,662 504,659 495,664 483,662 482,636 492,632 495,603 504,588 495,582 489,588 485,579 479,576 481,568 489,560 487,557 480,558 480,545 490,511 500,505 516,504 527,489 535,492 543,507 554,505 577,513 583,510 598,523 598,542 607,542 611,538 613,525 619,523 623,514 630,515 626,575'],
    ['id' => 'puttalam', 'name' => 'Puttalam', 'province' => 'North Western', 'accent' => '#f39270', 'points' => '254,752 255,768 248,772 232,770 222,713 216,698 216,694 219,699 223,714 228,713 227,705 216,688 216,683 222,679 219,673 218,642 198,582 191,544 195,533 192,519 198,516 205,498 205,507 199,514 203,516 209,509 207,517 202,521 203,532 198,539 199,555 204,560 201,562 203,579 210,584 220,584 216,628 217,636 225,641 222,604 227,571 216,557 228,543 229,537 218,514 221,514 223,503 235,489 234,486 230,488 231,459 237,453 237,436 240,431 250,427 274,432 283,440 278,455 259,474 259,479 266,493 270,516 275,521 284,516 299,526 303,552 313,561 310,584 312,593 305,591 273,625 266,646 258,658 260,664 253,667 246,685 246,717'],
    ['id' => 'batticaloa', 'name' => 'Batticaloa', 'province' => 'Eastern', 'accent' => '#6cb5ff', 'points' => '695,594 702,599 696,602 697,614 707,624 711,623 712,630 718,637 730,642 733,651 733,654 729,650 726,653 722,644 704,637 696,653 711,655 712,649 720,652 716,656 722,657 731,667 733,681 737,683 738,675 741,674 748,683 745,686 752,691 746,694 751,699 746,697 744,700 748,707 754,706 742,709 752,718 725,741 717,710 682,710 662,692 668,678 665,674 645,668 630,651 612,647 607,640 612,602 619,597 634,599 637,594 628,574 634,513 655,517 661,532 664,549 654,524 646,527 648,537 642,535 640,540 656,545 663,563 667,561 667,543 676,575 686,584 694,580 690,592 682,596 682,612 688,621 681,625 692,627 695,624 687,607 688,598'],
    ['id' => 'kurunegala', 'name' => 'Kurunegala', 'province' => 'North Western', 'accent' => '#ca7eff', 'points' => '427,713 427,720 419,726 420,733 416,733 412,739 401,737 396,747 388,748 381,739 370,738 366,741 365,755 347,761 328,773 322,772 309,757 298,759 291,765 272,759 257,768 256,743 249,718 248,687 255,668 262,665 260,659 268,648 275,626 296,602 301,601 306,594 314,594 316,563 305,551 302,528 325,540 358,551 376,565 385,568 388,588 398,595 398,601 403,607 403,622 408,626 408,634 422,658 420,669 429,688 429,693 422,698'],
    ['id' => 'matale', 'name' => 'Matale', 'province' => 'Central', 'accent' => '#ff878e', 'points' => '542,656 542,668 532,703 534,715 520,723 503,721 488,731 475,722 469,722 466,726 467,735 453,738 436,729 429,721 430,712 424,699 431,694 431,682 423,670 425,658 420,653 421,647 410,633 410,626 432,619 439,620 450,603 453,592 469,581 480,578 489,591 493,585 501,588 492,604 490,630 479,636 482,665 496,666 501,662 509,665 516,657'],
    ['id' => 'kandy', 'name' => 'Kandy', 'province' => 'Central', 'accent' => '#5a95ff', 'points' => '461,819 448,816 437,825 421,825 433,850 430,863 424,864 413,843 400,838 401,827 394,816 410,812 422,800 414,780 390,750 396,750 403,739 414,741 417,735 422,734 422,725 427,722 445,738 461,742 469,736 471,724 490,733 504,723 521,725 529,718 535,718 534,751 544,782 534,791 505,795 491,791 486,776 477,770 479,790'],
    ['id' => 'ampara', 'name' => 'Ampara', 'province' => 'Eastern', 'accent' => '#f0ad4f', 'points' => '773,850 773,859 780,856 780,860 770,879 772,889 768,886 764,889 772,895 767,901 766,917 753,936 756,947 750,958 744,959 746,963 740,971 740,980 718,969 714,949 719,877 719,859 714,850 715,829 695,822 686,813 683,801 694,787 694,782 677,753 672,734 676,726 669,723 649,737 645,746 631,756 629,776 620,778 610,757 606,753 601,754 594,736 593,727 598,711 596,696 587,693 567,709 557,707 549,696 548,685 541,681 544,656 570,656 580,666 604,675 611,657 610,650 626,652 642,669 665,677 660,693 679,711 687,714 715,712 723,743 755,723 761,739 770,729 780,764 778,768 781,770 778,799 775,797 766,806 771,817 776,814 778,807 777,823 772,822 770,828 773,839 781,839 782,846 777,846 778,853'],
    ['id' => 'gampaha', 'name' => 'Gampaha', 'province' => 'Western', 'accent' => '#7287ff', 'points' => '306,854 292,872 286,871 278,862 259,863 236,854 237,835 228,803 234,818 238,817 240,807 237,797 230,790 232,774 250,774 273,761 290,768 302,760 308,760 325,776 322,791 313,797 317,811 328,811 317,830 322,851'],
    ['id' => 'kegalle', 'name' => 'Kegalle', 'province' => 'Sabaragamuwa', 'accent' => '#42c8cb', 'points' => '392,848 392,867 408,886 409,895 406,897 380,885 364,892 335,867 334,855 325,852 320,835 320,828 331,811 317,808 316,798 325,791 328,775 368,755 369,741 381,742 390,756 402,766 404,774 412,781 419,800 418,804 408,810 392,814 399,829 397,837 400,842 399,846'],
    ['id' => 'badulla', 'name' => 'Badulla', 'province' => 'Uva', 'accent' => '#cf84ff', 'points' => '606,850 602,864 593,868 575,886 578,896 570,897 558,909 558,923 566,924 560,941 564,954 559,961 552,962 550,970 545,963 545,953 540,952 529,935 518,935 503,941 496,938 493,931 504,927 502,920 495,913 492,914 493,898 498,893 492,876 482,873 491,871 500,860 511,857 518,851 531,832 532,814 528,813 531,803 527,795 537,792 547,781 537,755 538,721 535,701 539,684 547,688 548,699 562,712 567,712 586,696 594,697 596,710 591,735 599,755 607,757 613,772 604,775 590,797 582,792 573,804 588,825 599,824 609,836'],
    ['id' => 'nuwaraeliya', 'name' => 'Nuwara Eliya', 'province' => 'Central', 'accent' => '#6fd887', 'points' => '490,883 496,895 490,897 489,907 450,915 416,909 406,901 412,895 412,888 394,864 393,850 400,848 405,842 410,843 423,866 429,867 432,864 436,850 423,827 429,825 436,828 450,818 462,821 482,789 478,772 485,779 491,794 502,797 524,794 528,803 525,812 529,816 527,834 513,853 500,857 489,869 480,872 481,876 490,877'],
    ['id' => 'colombo', 'name' => 'Colombo', 'province' => 'Western', 'accent' => '#29c69b', 'points' => '287,899 283,898 278,905 267,910 255,910 247,906 243,910 231,871 236,856 244,862 260,866 279,865 285,873 294,874 309,854 331,855 332,871 322,881 328,897 308,897 305,905 298,898 292,901'],
    ['id' => 'monaragala', 'name' => 'Monaragala', 'province' => 'Uva', 'accent' => '#f27f9f', 'points' => '652,1006 646,1013 619,1020 613,1028 597,1007 587,1013 583,1005 557,1004 556,1009 546,1017 549,1025 547,1028 522,1038 519,1030 503,1017 499,985 519,938 529,938 537,952 543,955 543,966 550,973 554,969 554,963 560,963 566,956 563,939 568,923 560,921 560,910 573,898 579,898 577,888 594,870 600,870 604,866 611,842 611,835 605,827 598,821 591,824 576,802 583,794 589,801 601,788 607,775 615,773 623,782 626,777 630,778 633,758 647,747 650,739 672,725 669,734 675,755 691,781 691,786 681,800 683,813 692,823 711,828 713,832 711,850 717,865 711,929 713,964 694,970 675,987 677,998 674,1006'],
    ['id' => 'ratnapura', 'name' => 'Ratnapura', 'province' => 'Sabaragamuwa', 'accent' => '#ff6f8c', 'points' => '512,941 516,939 514,946 497,983 497,997 501,1019 516,1030 526,1056 513,1052 507,1046 485,1046 478,1040 459,1035 460,1030 455,1029 453,1022 441,1023 445,1014 442,1010 423,1010 415,1019 408,1021 405,1016 394,1014 379,1005 354,966 358,964 357,958 346,949 333,930 324,900 331,896 325,881 334,869 364,895 379,887 403,898 404,903 414,911 445,917 467,916 489,909 489,914 501,925 490,931 494,939 501,944'],
    ['id' => 'kalutara', 'name' => 'Kalutara', 'province' => 'Western', 'accent' => '#ff9a68', 'points' => '357,1010 352,1015 354,1025 351,1026 340,1017 324,1023 311,1022 301,1012 283,1007 279,1001 271,1003 265,991 264,982 268,980 265,968 245,909 266,913 279,907 283,901 291,904 297,900 305,908 310,899 322,900 330,930 343,949 355,959 356,963 351,966 374,1002'],
    ['id' => 'hambantota', 'name' => 'Hambantota', 'province' => 'Southern', 'accent' => '#ffd05a', 'points' => '540,1098 531,1101 529,1100 534,1095 524,1094 524,1102 514,1104 507,1110 496,1110 486,1120 478,1121 472,1129 466,1114 459,1113 456,1109 464,1102 466,1085 463,1082 455,1084 457,1075 447,1073 443,1066 458,1037 479,1043 485,1049 507,1049 511,1054 526,1059 529,1056 523,1040 548,1030 551,1027 548,1019 558,1007 580,1007 587,1016 596,1010 614,1031 620,1022 645,1016 654,1008 674,1009 680,998 677,988 695,972 715,966 717,971 739,982 699,1021 685,1024 677,1036 663,1046 658,1046 655,1054 640,1060 634,1067 600,1079 603,1074 596,1072 592,1082 580,1084 577,1090 552,1092'],
    ['id' => 'galle', 'name' => 'Galle', 'province' => 'Southern', 'accent' => '#48b0ff', 'points' => '406,1036 402,1040 384,1029 377,1035 390,1045 394,1055 392,1060 382,1058 379,1061 382,1068 376,1080 389,1097 381,1105 386,1114 379,1118 376,1131 338,1118 335,1110 329,1111 301,1086 281,1048 279,1024 271,1006 278,1003 282,1009 301,1015 308,1024 328,1025 333,1020 340,1020 353,1029 357,1024 354,1016 365,1008 376,1005 390,1015 406,1020 399,1025'],
    ['id' => 'matara', 'name' => 'Matara', 'province' => 'Southern', 'accent' => '#60de8c', 'points' => '454,1107 459,1116 464,1116 468,1129 465,1133 447,1136 437,1142 425,1138 397,1138 392,1129 379,1131 381,1120 388,1115 384,1106 392,1098 379,1081 385,1067 382,1061 393,1062 396,1058 393,1045 380,1037 382,1032 405,1042 409,1036 402,1027 403,1024 416,1021 423,1013 442,1013 439,1025 450,1024 453,1026 452,1030 458,1032 450,1051 440,1063 447,1076 454,1075 452,1084 455,1087 463,1085 461,1102'],
];

$colorPairs = [
    ['#8f463d', '#c96558'],
    ['#a6564d', '#d67a69'],
    ['#7b3e39', '#b85a4d'],
    ['#9d5748', '#df9385'],
    ['#8f463d', '#e6b3ab'],
];

try {
    $projectsByDistrict = load_projects_by_district(getDbConnection(), $districts, $colorPairs);
} catch (Throwable $exception) {
    $projectsByDistrict = [];
}

foreach ($districts as $index => &$district) {
    $district['summary'] = 'Explore development initiatives, community investments, and project highlights across ' . $district['name'] . ' District.';
    $district['projects'] = $projectsByDistrict[$district['id']] ?? [];
}
unset($district);

$districtData = json_encode($districts, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$slEmbed = !empty($GLOBALS['SL_EMBED']);
$slAppId = $slEmbed ? 'sl-app-' . substr(md5((string) mt_rand()), 0, 8) : 'sl-app';
$requestedDistrictId = isset($_GET['district']) ? strtolower((string) $_GET['district']) : 'hambantota';
$requestedProjectSlug = isset($_GET['project']) ? strtolower((string) $_GET['project']) : '';
$selectedProjectDistrict = null;
$selectedProject = null;

foreach ($districts as $district) {
    if ($district['id'] !== $requestedDistrictId) {
        continue;
    }

    $selectedProjectDistrict = $district;
    foreach ($district['projects'] as $project) {
        if ($project['slug'] === $requestedProjectSlug) {
            $selectedProject = $project;
            break;
        }
    }
    break;
}
?>
<?php if (!$slEmbed): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sri Lanka District Project Map</title>
    <link rel="icon" type="image/x-icon" href="images/slpp.ico">
<?php endif; ?>
    <style>
        html {
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
        }

        * {
            box-sizing: border-box;
        }

        body {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        :root {
            --hero-red: #b85a4d;
            --hero-red-deep: #8f463d;
            --hero-ink: #fff7ea;
            --hero-muted: rgba(255, 240, 234, 0.82);
            --nav-glass: rgba(255, 246, 243, 0.12);
            --nav-glass-strong: rgba(255, 246, 243, 0.18);
            --nav-border: rgba(255, 214, 205, 0.1);
            --bg: #f7e8e4;
            --panel: rgba(255, 248, 246, 0.26);
            --panel-strong: rgba(255, 248, 246, 0.34);
            --line: rgba(164, 82, 71, 0.12);
            --line-strong: rgba(164, 82, 71, 0.2);
            --text: #8d5140;
            --muted: #946252;
            --blue: #c96558;
            --shadow-xl: 0 30px 70px rgba(110, 44, 36, 0.14);
            --shadow-lg: 0 18px 42px rgba(110, 44, 36, 0.12);
            --shadow-md: 0 10px 22px rgba(110, 44, 36, 0.1);
            --radius-xl: 34px;
            --radius-lg: 26px;
            --radius-md: 18px;
            --ease: 220ms cubic-bezier(.2, .8, .2, 1);
            --font: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
        }

        [data-float] {
            opacity: 0;
            transform: translate3d(0, 38px, 0) scale(0.965);
            filter: blur(16px);
            transition:
                opacity 860ms cubic-bezier(.18, .84, .22, 1),
                transform 860ms cubic-bezier(.18, .84, .22, 1),
                filter 860ms cubic-bezier(.18, .84, .22, 1);
            transition-delay: var(--float-delay, 0ms);
            will-change: transform, opacity, filter;
        }

        [data-float].is-visible {
            opacity: 1;
            transform: translateY(0) scale(1);
            filter: blur(0);
        }

                body {
                    margin: 0;
                    min-height: 100vh;
                    font-family: var(--font);
                    color: var(--text);
                    background:
                        radial-gradient(circle at top left, rgba(201, 104, 86, 0.24), transparent 24%),
                        radial-gradient(circle at top right, rgba(228, 191, 109, 0.22), transparent 28%),
                        linear-gradient(180deg, #9f4f47 0%, #b76052 18%, #c77158 34%, #da975f 54%, #e6b765 72%, #efcf93 88%, #f3dcc2 100%);
                    content-visibility: auto;
                }

                .workspace {
                    width: 100%;
                    min-height: auto;
                    margin: 0 auto;
                    padding: 14px 18px;
                    display: grid;
                    grid-template-columns: minmax(0, 1.2fr) minmax(380px, 0.8fr);
                    gap: 24px;
                    align-items: stretch;
                }
            margin: 0 auto;
            padding: 14px 18px;
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(380px, 0.8fr);
            gap: 24px;
            align-items: stretch;
        }

        .panel {
            background: transparent;
            backdrop-filter: none;
            border: 0;
            border-radius: 0;
            box-shadow: none;
            overflow: hidden;
        }

        .map-stage {
            position: relative;
            min-height: clamp(450px, 66vh, 680px);
            padding: 0 12px 0 0;
            display: flex;
            flex-direction: column;
            background: transparent;
        }

        .map-stage,
        .side-panel,
        .project-detail-hero,
        .back-link,
        .read-more {
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .map-stage::before {
            content: "";
            position: absolute;
            top: 18px;
            right: -12px;
            width: 1px;
            height: calc(100% - 36px);
            background: linear-gradient(180deg, transparent, rgba(164, 82, 71, 0.34), transparent);
            pointer-events: none;
        }

        .side-header {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 9px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,0.16);
            border: 0;
            color: var(--hero-red-deep);
            font-size: 0.78rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.24);
        }

        .eyebrow::before {
            content: "";
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: linear-gradient(135deg, #d67a69, #8f463d);
            box-shadow: 0 0 0 6px rgba(201, 101, 88, 0.12);
        }

        .side-header h2 {
            margin: 0;
            letter-spacing: -0.04em;
        }

        .side-header p {
            margin: 8px 0 0;
            color: var(--muted);
            max-width: 52ch;
            line-height: 1.65;
        }

        .map-shell {
            position: relative;
            z-index: 1;
            min-height: clamp(420px, 60vh, 620px);
            flex: 1 1 auto;
            border-radius: 0;
            border: 0;
            background: transparent;
            overflow: hidden;
        }

        .map-shell::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(201, 101, 88, 0.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(201, 101, 88, 0.035) 1px, transparent 1px);
            background-size: 46px 46px;
            mask-image: linear-gradient(to bottom, transparent, black 18%, black 82%, transparent);
            pointer-events: none;
        }

        .map-shell::after {
            content: "";
            position: absolute;
            inset: 8% 8% 10%;
            z-index: 0;
            background:
                radial-gradient(circle at 50% 82%, rgba(255, 244, 241, 0.16) 0 7%, transparent 8%),
                radial-gradient(ellipse 12% 18% at 50% 67%, rgba(184, 90, 77, 0.1) 0 70%, transparent 72%),
                radial-gradient(ellipse 10% 15% at 38% 73%, rgba(184, 90, 77, 0.08) 0 70%, transparent 72%),
                radial-gradient(ellipse 10% 15% at 62% 73%, rgba(184, 90, 77, 0.08) 0 70%, transparent 72%);
            opacity: 0.56;
            filter: blur(4px);
            transform: translateY(6px);
            pointer-events: none;
        }

        .tooltip {
            position: absolute;
            z-index: 6;
            top: 0;
            left: 0;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(122, 46, 40, 0.88);
            color: #fff;
            font-size: 0.88rem;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transform: translate(-50%, calc(-100% - 12px));
            transition: opacity var(--ease);
            box-shadow: 0 16px 34px rgba(110, 44, 36, 0.18);
            backdrop-filter: blur(14px);
        }

        .tooltip.visible {
            opacity: 1;
        }

        .map-canvas {
            position: relative;
            z-index: 1;
            padding: 14px;
            width: 100%;
            height: 100%;
        }

        .map-helper {
            position: absolute;
            top: 22px;
            right: 22px;
            z-index: 4;
            width: min(300px, calc(100% - 44px));
            padding: 14px 16px 14px 16px;
            border-radius: 20px;
            background: linear-gradient(145deg, rgba(134, 63, 53, 0.92), rgba(103, 40, 34, 0.88));
            border: 1px solid rgba(255, 214, 205, 0.16);
            box-shadow: 0 18px 34px rgba(110, 44, 36, 0.16);
            color: #fff7ea;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            pointer-events: none;
            opacity: 0;
            transform: translateY(10px);
            animation: mapHelperReveal 700ms cubic-bezier(.2, .8, .2, 1) 240ms forwards;
        }

        .map-helper-toggle {
            display: none;
        }

        @media (max-width: 780px) {
            .map-canvas {
                display: block;
                padding: 12px 12px 14px;
            }

            .map-canvas svg {
                width: 100%;
                height: auto;
                display: block;
            }

            .map-helper-toggle {
                position: absolute;
                top: 16px;
                right: 16px;
                left: auto;
                z-index: 6;
                width: 42px;
                height: 42px;
                padding: 0;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border: 1px solid rgba(255, 214, 205, 0.16);
                border-radius: 999px;
                background:
                    linear-gradient(160deg, rgba(128, 53, 44, 0.92), rgba(92, 31, 27, 0.94)),
                    rgba(94, 33, 28, 0.92);
                color: #ffdca0;
                box-shadow:
                    inset 0 1px 0 rgba(255,255,255,0.12),
                    0 14px 24px rgba(82, 22, 18, 0.18);
                backdrop-filter: blur(14px);
                -webkit-backdrop-filter: blur(14px);
                cursor: pointer;
                transition: transform var(--ease), box-shadow var(--ease), border-color var(--ease), background var(--ease);
            }

            .map-helper-toggle:hover,
            .map-helper-toggle:focus-visible,
            .map-helper-toggle[aria-expanded="true"] {
                outline: none;
                transform: translateY(-1px);
                border-color: rgba(255, 214, 205, 0.22);
                box-shadow:
                    inset 0 1px 0 rgba(255,255,255,0.14),
                    0 18px 28px rgba(82, 22, 18, 0.22);
            }

            .map-helper-toggle svg {
                width: 18px;
                height: 18px;
                display: block;
                filter: drop-shadow(0 0 10px rgba(255, 208, 90, 0.2));
            }

            .map-helper {
                position: absolute;
                top: 66px;
                left: 16px;
                right: auto;
                z-index: 5;
                width: min(252px, calc(100% - 32px));
                padding: 11px 12px 12px;
                border-radius: 18px;
                background:
                    linear-gradient(160deg, rgba(128, 53, 44, 0.9), rgba(92, 31, 27, 0.92)),
                    rgba(94, 33, 28, 0.9);
                border: 1px solid rgba(255, 214, 205, 0.16);
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
                transform: translateY(-8px) scale(0.98);
                animation: none;
                box-shadow:
                    inset 0 1px 0 rgba(255,255,255,0.12),
                    0 16px 28px rgba(82, 22, 18, 0.18);
                backdrop-filter: blur(14px);
                -webkit-backdrop-filter: blur(14px);
                transition:
                    opacity 220ms ease,
                    transform 220ms ease,
                    visibility 220ms ease;
            }

            .map-helper.is-open {
                opacity: 1;
                visibility: visible;
                pointer-events: auto;
                transform: translateY(0) scale(1);
            }

            .map-helper-head {
                gap: 8px;
                margin-bottom: 7px;
            }

            .map-helper strong {
                font-size: 0.7rem;
                line-height: 1.1;
                letter-spacing: 0.15em;
            }

            .map-helper span {
                font-size: 0.79rem;
                line-height: 1.48;
                color: rgba(255, 246, 231, 0.92);
                max-width: 25ch;
            }

            .map-helper-icon {
                width: 24px;
                height: 24px;
                flex: 0 0 24px;
                border-radius: 8px;
            }

            .map-helper-icon svg {
                width: 13px;
                height: 13px;
            }
        }

        .map-helper-head {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 6px;
        }

        .map-helper-icon {
            width: 28px;
            height: 28px;
            flex: 0 0 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: linear-gradient(145deg, rgba(255, 208, 90, 0.2), rgba(255, 208, 90, 0.08));
            border: 1px solid rgba(255, 214, 205, 0.18);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.12);
        }

        .map-helper-icon svg {
            width: 16px;
            height: 16px;
            display: block;
            margin: 0;
            color: #ffd05a;
            filter: drop-shadow(0 0 10px rgba(255, 208, 90, 0.18));
        }

        .map-helper strong {
            display: block;
            margin: 0;
            font-size: 0.77rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(255, 231, 195, 0.88);
        }

        .map-helper span {
            display: block;
            font-size: 0.94rem;
            line-height: 1.6;
            color: rgba(255, 247, 234, 0.96);
            text-wrap: balance;
        }

        .map-shell:hover .map-helper {
            transform: translateY(6px);
            box-shadow: 0 22px 40px rgba(110, 44, 36, 0.2);
        }

        @keyframes mapHelperReveal {
            from {
                opacity: 0;
                transform: translateY(16px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes mapIconPulse {
            0%,
            100% {
                transform: translateY(0) scale(1);
                box-shadow:
                    inset 0 1px 0 rgba(255, 255, 255, 0.12),
                    0 0 0 0 rgba(255, 208, 90, 0.14);
            }
            50% {
                transform: translateY(-1px) scale(1.06);
                box-shadow:
                    inset 0 1px 0 rgba(255, 255, 255, 0.12),
                    0 0 0 6px rgba(255, 208, 90, 0.04);
            }
        }

        .map-canvas::before {
            content: "";
            position: absolute;
            inset: 4% 4% 5%;
            z-index: 0;
            background:
                radial-gradient(circle at 50% 78%, rgba(255, 244, 241, 0.14) 0 8%, transparent 8.5%),
                radial-gradient(ellipse 10% 14% at 50% 66%, rgba(184, 90, 77, 0.1) 0 68%, transparent 70%),
                radial-gradient(ellipse 8% 12% at 40% 71%, rgba(184, 90, 77, 0.08) 0 68%, transparent 70%),
                radial-gradient(ellipse 8% 12% at 60% 71%, rgba(184, 90, 77, 0.08) 0 68%, transparent 70%);
            background-repeat: no-repeat;
            background-size: 240px 240px, 240px 240px, 240px 240px, 240px 240px;
            background-position:
                8% 22%,
                8% 22%,
                8% 22%,
                8% 22%;
            opacity: 0.72;
            filter: blur(0.6px);
            pointer-events: none;
        }

        .map-canvas::after {
            content: "";
            position: absolute;
            inset: 0;
            z-index: 0;
            background:
                radial-gradient(circle at 50% 78%, rgba(255, 244, 241, 0.12) 0 8%, transparent 8.5%),
                radial-gradient(ellipse 10% 14% at 50% 66%, rgba(184, 90, 77, 0.08) 0 68%, transparent 70%),
                radial-gradient(ellipse 8% 12% at 40% 71%, rgba(184, 90, 77, 0.07) 0 68%, transparent 70%),
                radial-gradient(ellipse 8% 12% at 60% 71%, rgba(184, 90, 77, 0.07) 0 68%, transparent 70%),
                radial-gradient(circle at 14% 22%, rgba(255, 255, 255, 0.16), transparent 10%),
                radial-gradient(circle at 86% 76%, rgba(255, 255, 255, 0.14), transparent 11%);
            background-repeat: no-repeat;
            background-size: 240px 240px, 240px 240px, 240px 240px, 240px 240px, auto, auto;
            background-position:
                88% 76%,
                88% 76%,
                88% 76%,
                center,
                center;
            pointer-events: none;
        }

        svg {
            width: min(100%, 650px);
            height: auto;
            display: block;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .island-outline {
            fill: rgba(246, 234, 218, 0.97);
            stroke: none;
            filter: drop-shadow(0 22px 38px rgba(90, 32, 28, 0.12));
        }

        .district {
            fill: rgba(230, 210, 186, 0.60);
            stroke: rgba(184, 90, 77, 0.22);
            stroke-width: 1.15;
            vector-effect: non-scaling-stroke;
            cursor: pointer;
            transition: transform var(--ease), filter var(--ease), fill var(--ease), stroke var(--ease);
            transform-box: fill-box;
            transform-origin: center;
        }

        .district:hover,
        .district:focus-visible {
            fill: rgba(228, 191, 109, 0.52);
            stroke: rgba(184, 90, 77, 0.38);
            transform: translateY(-2px) scale(1.015);
            filter: drop-shadow(0 8px 18px rgba(228, 191, 109, 0.22));
            outline: none;
        }

        .district.active {
            fill: rgba(184, 90, 77, 0.88);
            stroke: rgba(228, 191, 109, 0.50);
            stroke-width: 1.4;
            filter: drop-shadow(0 12px 24px rgba(184, 90, 77, 0.28));
            transform: translateY(-2px) scale(1.02);
        }

        .project-card h3 {
            margin: 0;
        }

        .project-card p {
            margin: 0;
            color: var(--muted);
            line-height: 1.55;
        }

        .hint-meta,
        .project-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 10px;
            border-radius: 999px;
            background: rgba(201, 101, 88, 0.1);
            color: #af6558;
            font-size: 0.76rem;
        }

        .side-panel {
            min-height: clamp(450px, 66vh, 680px);
            max-height: clamp(450px, 66vh, 680px);
            height: 100%;
            padding: 4px 0 4px 10px;
            display: grid;
            grid-template-rows: auto auto minmax(0, 1fr);
            gap: 12px;
            background: transparent;
        }

        .district-overview {
            padding: 14px 16px;
            border-radius: 24px;
            background:
                linear-gradient(145deg, rgba(255,255,255,0.2), rgba(255,255,255,0.08)),
                rgba(184, 90, 77, 0.08);
            border: 0;
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.22),
                0 16px 34px rgba(110, 44, 36, 0.08);
        }

        .district-overview .eyebrow {
            margin-bottom: 12px;
        }

        .district-overview h2 {
            margin: 0 0 8px;
            font-size: clamp(1.3rem, 2vw, 1.75rem);
            letter-spacing: -0.04em;
        }

        .district-overview p {
            margin: 0;
            color: var(--muted);
            line-height: 1.58;
            font-size: 0.94rem;
        }

        .overview-meta {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .mini-card {
            padding: 10px 12px;
            border-radius: 18px;
            background:
                linear-gradient(145deg, rgba(255,255,255,0.22), rgba(255,255,255,0.08)),
                rgba(184, 90, 77, 0.07);
            border: 0;
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.3),
                0 12px 24px rgba(88, 33, 28, 0.08);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .mini-card label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.75rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #a27a5b;
        }

        .mini-card strong {
            font-size: 0.95rem;
        }

        .project-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
            min-height: 0;
            height: 100%;
            max-height: 100%;
            align-self: stretch;
            overflow-y: auto;
            overflow-x: hidden;
            align-content: start;
            padding-right: 8px;
            padding-bottom: 4px;
        }

        .project-card {
            position: relative;
            padding: 13px;
            border-radius: 26px;
            background:
                linear-gradient(145deg, rgba(255,255,255,0.24), rgba(255,255,255,0.08)),
                rgba(184, 90, 77, 0.08);
            border: 0;
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.38),
                0 14px 30px rgba(88, 33, 28, 0.08);
            transition: transform var(--ease), box-shadow var(--ease), border-color var(--ease);
            overflow: hidden;
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .project-card:hover {
            transform: translateY(-3px);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.42),
                0 20px 36px rgba(88, 33, 28, 0.12);
        }

        .project-card::before {
            content: none;
        }

        .project-card::after {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(145deg, rgba(186, 97, 83, 0.06), transparent 48%);
            pointer-events: none;
        }

        .project-row {
            display: grid;
            grid-template-columns: 164px minmax(0, 1fr) auto;
            gap: 14px;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .project-visual {
            width: 164px;
            height: 120px;
            border-radius: 22px;
            overflow: hidden;
            flex-shrink: 0;
            position: relative;
            background:
                radial-gradient(circle at top left, rgba(255,255,255,0.44), transparent 40%),
                linear-gradient(145deg, rgba(255,248,246,0.82), rgba(244,222,216,0.58));
            border: 0;
            box-shadow:
                inset 0 0 0 1px rgba(255,255,255,0.24),
                0 12px 26px rgba(145, 92, 73, 0.08);
        }

        .project-visual-backdrop,
        .project-detail-backdrop {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            filter: blur(20px) saturate(1.1);
            transform: scale(1.14);
            opacity: 0.42;
        }

        .project-visual::after,
        .project-detail-visual::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255,255,255,0.16), rgba(255,255,255,0.06));
            pointer-events: none;
        }

        .project-visual img {
            position: relative;
            z-index: 1;
            width: 100%;
            height: 100%;
            padding: 10px;
            display: block;
            object-fit: contain;
            object-position: center;
            image-rendering: -webkit-optimize-contrast;
            image-rendering: high-quality;
            transform: translateZ(0);
            transition: transform var(--ease), filter var(--ease);
            filter: drop-shadow(0 16px 24px rgba(128, 74, 58, 0.14));
        }

        .project-card:hover .project-visual img {
            transform: scale(1.04) rotate(-1deg) translateZ(0);
        }

        .project-copy {
            min-width: 0;
        }

        .project-name {
            margin: 0;
            font-size: 0.98rem;
            line-height: 1.22;
            letter-spacing: -0.03em;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .project-line {
            margin-top: 10px;
            color: var(--muted);
            font-size: 0.84rem;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .read-more {
            width: 50px;
            height: 50px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border: 0;
            border-radius: 18px;
            padding: 0;
            background:
                linear-gradient(145deg, rgba(186, 97, 83, 0.18), rgba(143, 70, 61, 0.12)),
                rgba(255, 248, 246, 0.16);
            color: #9d5748;
            cursor: pointer;
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.28),
                0 12px 28px rgba(176, 113, 89, 0.1);
            transition: background var(--ease), transform var(--ease), box-shadow var(--ease), border-color var(--ease);
        }

        .read-more:hover {
            background:
                linear-gradient(145deg, rgba(186, 97, 83, 0.26), rgba(143, 70, 61, 0.18)),
                rgba(255, 248, 246, 0.22);
            transform: translateY(-2px) scale(1.02);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.38),
                0 18px 30px rgba(176, 113, 89, 0.14);
        }

        .read-more:focus-visible {
            outline: 2px solid rgba(201, 101, 88, 0.32);
            outline-offset: 3px;
        }

        .read-more svg {
            width: 20px;
            height: 20px;
        }

        .project-grid::-webkit-scrollbar {
            width: 8px;
        }

        .project-grid::-webkit-scrollbar-thumb {
            background: rgba(201, 101, 88, 0.28);
            border-radius: 999px;
        }

        .project-grid::-webkit-scrollbar-track {
            background: rgba(244, 222, 216, 0.38);
            border-radius: 999px;
        }

        .project-empty {
            padding: 28px 20px;
            border-radius: 18px;
            background: rgba(255, 248, 246, 0.10);
            border: 1px solid rgba(255, 214, 205, 0.12);
            color: var(--muted);
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 8px;
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.14),
                0 12px 24px rgba(88, 33, 28, 0.06);
            backdrop-filter: blur(18px);
        }

        .project-empty svg {
            width: 36px;
            height: 36px;
            color: rgba(244, 210, 122, 0.6);
            margin-bottom: 4px;
        }

        .project-empty strong {
            display: block;
            font-size: 0.92rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: rgba(255, 231, 195, 0.88);
        }

        .project-empty span {
            display: block;
            font-size: 0.85rem;
            color: rgba(255, 247, 234, 0.56);
            max-width: 24ch;
            line-height: 1.55;
        }

        .project-detail-page {
            min-height: 100vh;
            padding: 26px 24px 38px;
            background:
                radial-gradient(circle at left top, rgba(194, 94, 82, 0.18), transparent 24%),
                radial-gradient(circle at right top, rgba(143, 70, 61, 0.16), transparent 26%),
                linear-gradient(145deg, rgba(139, 67, 61, 0.18), rgba(183, 96, 82, 0.12), rgba(244, 229, 223, 0.18));
        }

        .project-detail-shell {
            width: min(1380px, 100%);
            margin: 0 auto;
            display: grid;
            gap: 22px;
        }

        .project-detail-hero {
            overflow: hidden;
            border-radius: 36px;
            background:
                linear-gradient(145deg, rgba(255,255,255,0.22), rgba(255,255,255,0.08)),
                rgba(184, 90, 77, 0.08);
            border: 0;
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.34),
                0 28px 70px rgba(88, 33, 28, 0.14);
            display: grid;
            grid-template-columns: minmax(560px, 1.15fr) minmax(420px, 0.85fr);
        }

        .project-detail-visual {
            min-height: 640px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 34px;
            background:
                radial-gradient(circle at top left, rgba(255, 240, 234, 0.16), transparent 24%),
                radial-gradient(circle at bottom right, rgba(124, 41, 36, 0.16), transparent 28%),
                linear-gradient(145deg, rgba(139, 67, 61, 0.84), rgba(183, 96, 82, 0.76), rgba(161, 78, 69, 0.7));
            position: relative;
            overflow: hidden;
            border-right: 0;
        }

        .project-detail-visual::before {
            content: "";
            position: absolute;
            inset: 18px;
            border-radius: 28px;
            background:
                radial-gradient(circle at top left, rgba(255, 240, 234, 0.12), transparent 26%),
                radial-gradient(circle at bottom right, rgba(124, 41, 36, 0.18), transparent 28%),
                linear-gradient(145deg, rgba(255, 248, 238, 0.12), rgba(255,255,255,0.04));
            border: 0;
            pointer-events: none;
        }

        .project-detail-frame {
            position: relative;
            z-index: 1;
            width: min(100%, 760px);
            aspect-ratio: 16 / 10;
            display: grid;
            align-items: center;
            padding: 18px;
            border-radius: 30px;
            background:
                linear-gradient(180deg, rgba(255,255,255,0.22), rgba(255,255,255,0.08)),
                rgba(255, 248, 238, 0.12);
            border: 0;
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.24),
                0 28px 60px rgba(61, 18, 17, 0.22);
            overflow: hidden;
        }

        .project-detail-frame::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(180deg, rgba(255,255,255,0.16), rgba(255,255,255,0)),
                linear-gradient(90deg, rgba(61, 18, 17, 0.18), rgba(61, 18, 17, 0.04) 58%, rgba(61, 18, 17, 0.16));
            pointer-events: none;
        }

        .project-detail-orbit {
            position: absolute;
            top: 18px;
            left: 18px;
            z-index: 3;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,0.12);
            border: 0;
            color: var(--hero-ink);
            font-size: 0.76rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .project-detail-orbit::before {
            content: "";
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: linear-gradient(135deg, #d67a69, #fff2ee);
            box-shadow: 0 0 0 7px rgba(255, 214, 205, 0.12);
        }

        .project-detail-visual img {
            position: relative;
            z-index: 2;
            width: 100%;
            height: 100%;
            max-width: 100%;
            max-height: 100%;
            display: block;
            object-fit: contain;
            object-position: center;
            image-rendering: -webkit-optimize-contrast;
            image-rendering: high-quality;
            border-radius: 22px;
            box-shadow: 0 24px 58px rgba(145, 92, 73, 0.14);
            transform: translateZ(0);
            filter: drop-shadow(0 26px 34px rgba(128, 74, 58, 0.15));
        }

        .project-detail-body {
            padding: 46px 42px;
            display: grid;
            align-content: center;
            gap: 20px;
            background:
                radial-gradient(circle at top right, rgba(255, 240, 234, 0.18), transparent 22%),
                linear-gradient(180deg, rgba(255,255,255,0.3), rgba(255,255,255,0.12));
        }

        .project-detail-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
        }

        .project-detail-head h1 {
            margin: 10px 0 0;
            font-size: clamp(2.4rem, 4.4vw, 4.5rem);
            line-height: 0.98;
            letter-spacing: -0.05em;
            color: var(--hero-red-deep);
        }

        .project-detail-copy {
            margin: 0;
            max-width: 58ch;
            color: var(--muted);
            line-height: 1.82;
            font-size: 1.12rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            width: fit-content;
            padding: 14px 20px;
            border-radius: 999px;
            background:
                linear-gradient(145deg, rgba(186, 97, 83, 0.16), rgba(143, 70, 61, 0.16)),
                rgba(255, 248, 238, 0.16);
            border: 0;
            color: #9d5748;
            text-decoration: none;
            font-weight: 600;
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.34),
                0 12px 28px rgba(176, 113, 89, 0.1);
            transition: transform var(--ease), box-shadow var(--ease), border-color var(--ease), background var(--ease);
        }

        .back-link:hover {
            transform: translateY(-2px);
            border-color: rgba(201, 101, 88, 0.22);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.38),
                0 18px 30px rgba(176, 113, 89, 0.14);
        }

        .back-link:focus-visible {
            outline: 2px solid rgba(201, 101, 88, 0.3);
            outline-offset: 3px;
        }

        @media (max-width: 1240px) {
            .workspace {
                grid-template-columns: 1fr;
            }

            .map-stage,
            .side-panel {
                min-height: auto;
                max-height: none;
            }
        }

        @media (max-width: 780px) {
            body {
                background: #f7e8e4;
            }

            .workspace {
                padding: 10px;
                gap: 16px;
            }

            .map-stage,
            .side-panel {
                padding: 0;
                border-radius: 0;
            }

            .map-stage::before {
                display: none;
            }

            .side-header {
                flex-direction: column;
                align-items: start;
            }

            .map-shell {
                min-height: 430px;
            }

            .overview-meta {
                grid-template-columns: 1fr;
            }

            .project-grid {
                grid-template-columns: 1fr;
            }

            .project-row {
                grid-template-columns: 136px minmax(0, 1fr) auto;
                gap: 10px;
            }

            .project-visual {
                width: 136px;
                height: 102px;
            }

            .project-visual img {
                padding: 6px;
            }

            .project-line {
                -webkit-line-clamp: 2;
            }

            .project-detail-hero {
                grid-template-columns: 1fr;
            }

            .project-detail-visual {
                min-height: 380px;
                padding: 18px;
                border-right: 0;
                border-bottom: 0;
            }

            .project-detail-frame {
                width: 100%;
                aspect-ratio: 16 / 11;
                padding: 12px;
                border-radius: 24px;
            }

            .project-detail-body {
                padding: 24px 20px;
            }

            .project-detail-head h1 {
                font-size: clamp(2rem, 8vw, 3rem);
            }

            .read-more {
                width: 50px;
                height: 50px;
            }

            .project-detail-page {
                padding: 10px;
            }

            .project-detail-body {
                padding: 18px;
            }
        }


        /* Gold + red smart-glass UI refinement: visual only, functions unchanged */
        :root {
            --hero-red: #9f3f36;
            --hero-red-deep: #6f241f;
            --hero-gold: #f4d27a;
            --hero-gold-soft: rgba(244, 210, 122, 0.78);
            --hero-gold-faint: rgba(244, 210, 122, 0.14);
            --hero-white: #fffaf0;
            --glass-red: rgba(126, 34, 28, 0.34);
            --glass-red-soft: rgba(126, 34, 28, 0.18);
            --glass-border: rgba(244, 210, 122, 0.18);
            --text: #fffaf0;
            --muted: rgba(255, 250, 240, 0.76);
            --line: rgba(244, 210, 122, 0.20);
            --line-strong: rgba(244, 210, 122, 0.42);
            --shadow-xl: 0 24px 70px rgba(60, 12, 10, 0.18);
            --shadow-lg: 0 18px 44px rgba(60, 12, 10, 0.14);
            --shadow-md: 0 10px 24px rgba(60, 12, 10, 0.12);
        }

        body {
            color: var(--hero-white);
            background:
                radial-gradient(circle at 12% 10%, rgba(244, 210, 122, 0.18), transparent 26%),
                radial-gradient(circle at 88% 18%, rgba(255, 255, 255, 0.08), transparent 24%),
                linear-gradient(135deg, #5e1714 0%, #8d2f28 38%, #b85a4d 100%);
        }

        .sl-app {
            color: var(--hero-white);
        }

        .workspace {
            min-height: clamp(520px, 70vh, 720px);
            padding: 18px;
            grid-template-columns: minmax(0, 1.08fr) 1px minmax(390px, 0.92fr);
            gap: 22px;
            align-items: stretch;
            position: relative;
        }

        .workspace::before {
            content: "";
            grid-column: 2;
            grid-row: 1;
            width: 2px;
            min-height: 100%;
            border-radius: 999px;
            background: linear-gradient(
                180deg,
                transparent 0%,
                rgba(244, 210, 122, 0.72) 18%,
                rgba(255, 246, 200, 0.92) 50%,
                rgba(244, 210, 122, 0.72) 82%,
                transparent 100%
            );
            box-shadow:
                0 0 12px rgba(244, 210, 122, 0.2),
                0 0 28px rgba(244, 210, 122, 0.16);
        }

        .map-stage {
            grid-column: 1;
        }

        .side-panel {
            grid-column: 3;
        }

        .map-stage,
        .side-panel {
            min-height: clamp(560px, 74vh, 780px);
            height: 100%;
            max-height: clamp(560px, 74vh, 780px);
            padding: 16px;
            border-radius: 34px;
            background:
                linear-gradient(145deg, rgba(255,255,255,0.12), rgba(255,255,255,0.045)),
                rgba(75, 13, 11, 0.18);
            border: 1px solid var(--glass-border);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.16),
                0 22px 58px rgba(60, 12, 10, 0.14);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            overflow: hidden;
        }

        .map-stage::before {
            display: none;
        }

        .map-shell {
            min-height: 100%;
            height: 100%;
            border-radius: 28px;
            background:
                radial-gradient(circle at 18% 14%, rgba(255, 255, 255, 0.1), transparent 18%),
                radial-gradient(circle at 84% 18%, rgba(244, 210, 122, 0.12), transparent 24%),
                radial-gradient(circle at 50% 86%, rgba(255,255,255,0.05), transparent 24%),
                linear-gradient(165deg, rgba(144, 46, 38, 0.34), rgba(84, 18, 15, 0.2)),
                rgba(92, 20, 17, 0.24);
            border: 1px solid rgba(244, 210, 122, 0.18);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.12),
                inset 0 -28px 44px rgba(79, 15, 13, 0.12);
        }

        .map-shell::before {
            background-image:
                linear-gradient(rgba(244, 210, 122, 0.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(244, 210, 122, 0.06) 1px, transparent 1px);
            opacity: 0.85;
        }

        .map-shell::after {
            inset: 7% 7% 9%;
            background:
                radial-gradient(circle at 22% 20%, rgba(255, 255, 255, 0.08), transparent 16%),
                radial-gradient(circle at 78% 26%, rgba(244, 210, 122, 0.08), transparent 18%),
                radial-gradient(ellipse 18% 14% at 50% 84%, rgba(84, 18, 15, 0.16) 0 70%, transparent 72%);
            opacity: 0.7;
            filter: blur(8px);
        }

        .map-canvas {
            min-height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 18px 18px;
        }

        svg {
            width: min(100%, 670px);
            max-height: calc(clamp(560px, 74vh, 780px) - 72px);
        }

        .island-outline {
            fill: rgba(246, 234, 218, 0.97);
            filter: drop-shadow(0 24px 38px rgba(60, 12, 10, 0.16));
        }

        .district {
            fill: rgba(230, 210, 186, 0.60);
            stroke: rgba(184, 90, 77, 0.22);
            stroke-width: 1;
        }

        .district:hover,
        .district:focus-visible {
            fill: rgba(228, 191, 109, 0.52);
            stroke: rgba(228, 191, 109, 0.50);
            filter: drop-shadow(0 10px 18px rgba(228, 191, 109, 0.20));
        }

        .district.active {
            fill: rgba(184, 90, 77, 0.88);
            stroke: rgba(228, 191, 109, 0.60);
            stroke-width: 1.4;
            filter: drop-shadow(0 14px 28px rgba(184, 90, 77, 0.30));
        }

        .tooltip {
            color: var(--hero-white);
            background: rgba(74, 16, 14, 0.86);
            border: 1px solid rgba(244, 210, 122, 0.22);
        }

        .district-overview,
        .mini-card,
        .project-card,
        .project-empty {
            color: var(--hero-white);
            background:
                linear-gradient(145deg, rgba(255,255,255,0.13), rgba(255,255,255,0.045)),
                rgba(84, 18, 15, 0.22);
            border: 1px solid rgba(244, 210, 122, 0.12);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.14),
                0 14px 34px rgba(60, 12, 10, 0.10);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .district-overview {
            padding: 18px;
        }

        .district-overview h2,
        .project-name,
        .mini-card strong,
        .project-detail-head h1 {
            color: var(--hero-white);
            text-shadow: 0 1px 18px rgba(60, 12, 10, 0.18);
        }

        .district-overview p,
        .project-card p,
        .project-line,
        .project-detail-copy,
        .side-header p,
        .project-empty {
            color: var(--muted);
        }

        .eyebrow,
        .mini-card label,
        .tag,
        .read-more,
        .back-link {
            color: var(--hero-gold);
        }

        .eyebrow {
            background: rgba(244, 210, 122, 0.10);
            border: 1px solid rgba(244, 210, 122, 0.14);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.14);
        }

        .eyebrow::before,
        .project-detail-orbit::before {
            background: linear-gradient(135deg, #fff6c8, var(--hero-gold));
            box-shadow: 0 0 0 6px rgba(244, 210, 122, 0.12);
        }

        .overview-meta {
            gap: 12px;
        }

        .project-grid {
            gap: 12px;
            padding-right: 6px;
        }

        .project-card {
            min-height: 150px;
            display: flex;
            align-items: center;
        }

        .project-card:hover {
            transform: translateY(-2px);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.18),
                0 18px 38px rgba(60, 12, 10, 0.14);
        }

        .project-row {
            width: 100%;
            grid-template-columns: 154px minmax(0, 1fr) 48px;
        }

        .project-visual {
            width: 154px;
            height: 112px;
            border: 1px solid rgba(244, 210, 122, 0.10);
            background:
                radial-gradient(circle at top left, rgba(244,210,122,0.14), transparent 42%),
                rgba(255,255,255,0.08);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.12);
        }

        .read-more {
            width: 48px;
            height: 48px;
            border: 1px solid rgba(244, 210, 122, 0.14);
            background: rgba(244, 210, 122, 0.10);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.14);
        }

        .read-more:hover {
            background: rgba(244, 210, 122, 0.18);
            box-shadow: 0 12px 28px rgba(244, 210, 122, 0.10);
        }

        .project-grid::-webkit-scrollbar-thumb {
            background: rgba(244, 210, 122, 0.42);
        }

        .project-grid::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.06);
        }

        .project-detail-page {
            color: var(--hero-white);
            background:
                radial-gradient(circle at 12% 10%, rgba(244, 210, 122, 0.18), transparent 26%),
                linear-gradient(135deg, #5e1714 0%, #8d2f28 42%, #b85a4d 100%);
        }

        .project-detail-hero,
        .project-detail-frame,
        .project-detail-visual::before {
            border: 1px solid rgba(244, 210, 122, 0.14);
        }

        .project-detail-visual {
            background:
                radial-gradient(circle at top left, rgba(244, 210, 122, 0.16), transparent 24%),
                linear-gradient(145deg, rgba(96, 24, 20, 0.88), rgba(154, 52, 44, 0.76));
        }

        .project-detail-body {
            background:
                radial-gradient(circle at top right, rgba(244, 210, 122, 0.10), transparent 24%),
                rgba(255,255,255,0.05);
        }

        .tag {
            background: rgba(244, 210, 122, 0.10);
            border: 1px solid rgba(244, 210, 122, 0.12);
        }

        @media (max-width: 1240px) {
            .workspace {
                grid-template-columns: 1fr;
            }

            .workspace::before {
                display: none;
            }

            .map-stage,
            .side-panel {
                grid-column: 1;
                max-height: none;
                min-height: auto;
            }
        }

        @media (max-width: 780px) {
            body {
                background: linear-gradient(135deg, #5e1714 0%, #9f3f36 100%);
            }

            .workspace {
                padding: 10px;
                gap: 14px;
            }

            .map-stage,
            .side-panel {
                padding: 12px;
                border-radius: 26px;
            }

            .map-shell {
                min-height: clamp(440px, 74vh, 620px);
            }

            .map-canvas {
                min-height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px 14px 16px;
            }

            .map-canvas > svg {
                width: min(100%, 360px);
                max-width: 100%;
                max-height: none;
            }

            .map-helper span {
                font-size: 0.86rem;
            }

            .project-row {
                grid-template-columns: 118px minmax(0, 1fr) 44px;
            }

            .project-visual {
                width: 118px;
                height: 92px;
            }
        }

        /* Professional project detail 360 upgrade - UI only */
        .project-detail-page {
            min-height: 100vh;
            padding: clamp(20px, 3vw, 46px);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--hero-white, #fffaf0);
            background:
                radial-gradient(circle at 10% 10%, rgba(244, 210, 122, 0.18), transparent 28%),
                radial-gradient(circle at 88% 18%, rgba(255, 255, 255, 0.08), transparent 26%),
                linear-gradient(135deg, #5e1714 0%, #8d2f28 46%, #b85a4d 100%);
        }

        .project-detail-shell {
            width: min(1680px, 96vw);
            min-height: min(820px, calc(100vh - 72px));
            margin: 0 auto;
            display: grid;
            grid-template-rows: auto 1fr;
            gap: clamp(16px, 2vw, 26px);
        }

        .project-detail-hero {
            min-height: min(760px, calc(100vh - 150px));
            width: 100%;
            display: grid;
            grid-template-columns: minmax(520px, 1.18fr) minmax(420px, 0.82fr);
            overflow: hidden;
            border-radius: clamp(30px, 3vw, 46px);
            border: 1px solid rgba(244, 210, 122, 0.16);
            background:
                linear-gradient(145deg, rgba(255,255,255,0.12), rgba(255,255,255,0.04)),
                rgba(84, 18, 15, 0.34);
            box-shadow:
                0 42px 110px rgba(31, 6, 5, 0.28),
                inset 0 1px 0 rgba(255,255,255,0.14);
            backdrop-filter: blur(26px);
            -webkit-backdrop-filter: blur(26px);
        }

        .project-detail-visual {
            min-height: 100%;
            padding: clamp(24px, 3vw, 48px);
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                radial-gradient(circle at 18% 12%, rgba(244, 210, 122, 0.16), transparent 30%),
                radial-gradient(circle at 80% 80%, rgba(255,255,255,0.08), transparent 30%),
                linear-gradient(145deg, rgba(90, 22, 18, 0.88), rgba(154, 52, 44, 0.72));
            border-right: 1px solid rgba(244, 210, 122, 0.12);
            overflow: hidden;
        }

        .project-detail-visual::before {
            inset: clamp(12px, 1.5vw, 22px);
            border-radius: clamp(24px, 2.5vw, 36px);
            border: 1px solid rgba(244, 210, 122, 0.10);
            background:
                radial-gradient(circle at 10% 12%, rgba(244,210,122,0.12), transparent 28%),
                linear-gradient(145deg, rgba(255,255,255,0.09), rgba(255,255,255,0.02));
        }

        .project-detail-frame {
            width: min(100%, 900px);
            height: min(620px, 66vh);
            aspect-ratio: auto;
            padding: 12px;
            display: block;
            border-radius: clamp(24px, 2.3vw, 34px);
            border: 1px solid rgba(244, 210, 122, 0.16);
            background:
                linear-gradient(180deg, rgba(255,255,255,0.15), rgba(255,255,255,0.04)),
                rgba(255,255,255,0.06);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.16),
                0 34px 80px rgba(32, 6, 5, 0.24);
            cursor: zoom-in;
        }

        .project-detail-frame::before {
            display: none;
        }

        .project-detail-visual img {
            width: 100%;
            height: 100%;
            border-radius: clamp(18px, 1.8vw, 26px);
            object-fit: cover;
            object-position: center;
            box-shadow: none;
            filter: saturate(1.05) contrast(1.03);
        }

        .project-detail-orbit {
            top: 22px;
            left: 22px;
            padding: 10px 14px;
            border: 1px solid rgba(244, 210, 122, 0.18);
            background: rgba(78, 16, 14, 0.42);
            color: #fffaf0;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        .view-360-btn {
            position: absolute;
            right: 24px;
            bottom: 24px;
            z-index: 5;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 18px;
            border-radius: 999px;
            border: 1px solid rgba(244, 210, 122, 0.28);
            background: linear-gradient(145deg, rgba(244, 210, 122, 0.18), rgba(184, 90, 77, 0.18)), rgba(63, 12, 10, 0.52);
            color: #fffaf0;
            font-weight: 800;
            font-size: 0.78rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            cursor: pointer;
            box-shadow: 0 16px 34px rgba(31, 6, 5, 0.24), inset 0 1px 0 rgba(255,255,255,0.16);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            transition: transform var(--ease), background var(--ease), border-color var(--ease), box-shadow var(--ease);
        }

        .view-360-btn:hover,
        .view-360-btn:focus-visible {
            transform: translateY(-2px);
            border-color: rgba(244, 210, 122, 0.44);
            background: linear-gradient(145deg, rgba(244, 210, 122, 0.28), rgba(184, 90, 77, 0.26)), rgba(63, 12, 10, 0.62);
            outline: none;
        }

        .project-detail-body {
            min-height: 100%;
            padding: clamp(34px, 4vw, 68px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: clamp(18px, 2vw, 28px);
            background:
                radial-gradient(circle at 86% 16%, rgba(244, 210, 122, 0.12), transparent 30%),
                linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0.035));
        }

        .project-detail-head h1 {
            margin: 16px 0 0;
            max-width: 10ch;
            font-size: clamp(3.2rem, 5.4vw, 6.6rem);
            line-height: 0.94;
            letter-spacing: -0.07em;
            color: #fffaf0;
            text-shadow: 0 20px 54px rgba(30, 6, 5, 0.32);
        }

        .project-detail-copy {
            max-width: 58ch;
            margin: 0;
            color: rgba(255, 250, 240, 0.78);
            font-size: clamp(1.05rem, 1.2vw, 1.26rem);
            line-height: 1.85;
        }

        .project-meta {
            display: none !important;
        }

        .back-link {
            justify-self: start;
            padding: 12px 22px;
            border-radius: 999px;
            border: 1px solid rgba(244, 210, 122, 0.24);
            background: linear-gradient(145deg, rgba(244, 210, 122, 0.14), rgba(184, 90, 77, 0.14)), rgba(63, 12, 10, 0.34);
            color: #fffaf0;
            font-weight: 800;
            text-decoration: none;
            box-shadow: 0 14px 34px rgba(31, 6, 5, 0.18), inset 0 1px 0 rgba(255,255,255,0.16);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .back-link:hover,
        .back-link:focus-visible {
            transform: translateY(-2px);
            background: linear-gradient(145deg, rgba(244, 210, 122, 0.24), rgba(184, 90, 77, 0.22)), rgba(63, 12, 10, 0.44);
            border-color: rgba(244, 210, 122, 0.38);
            outline: none;
        }

        .viewer360 {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: none;
            background: radial-gradient(circle at 20% 12%, rgba(244, 210, 122, 0.12), transparent 30%), rgba(18, 3, 3, 0.94);
            cursor: grab;
            touch-action: none;
        }

        .viewer360.active { display: block; }
        .viewer360.dragging { cursor: grabbing; }

        .viewer360-inner {
            position: absolute;
            inset: clamp(18px, 3vw, 42px);
            overflow: hidden;
            border-radius: clamp(24px, 3vw, 44px);
            border: 1px solid rgba(244, 210, 122, 0.18);
            background: rgba(255,255,255,0.04);
            box-shadow: 0 40px 120px rgba(0,0,0,0.45), inset 0 1px 0 rgba(255,255,255,0.12);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .viewer360 img {
            position: absolute;
            top: 50%;
            left: 50%;
            width: auto;
            height: auto;
            max-width: none;
            max-height: none;
            user-select: none;
            pointer-events: none;
            transform: translate(-50%, -50%) scale(1);
            transform-origin: center;
            filter: saturate(1.06) contrast(1.04);
        }

        .viewer360-toolbar {
            position: absolute;
            top: clamp(26px, 4vw, 58px);
            left: 50%;
            z-index: 3;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 999px;
            border: 1px solid rgba(244, 210, 122, 0.18);
            background: rgba(63, 12, 10, 0.58);
            color: #fffaf0;
            font-size: 0.8rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .viewer360-toolbar span {
            white-space: nowrap;
        }

        .viewer360-control {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            border: 1px solid rgba(244, 210, 122, 0.2);
            background: rgba(255, 255, 255, 0.08);
            color: #fffaf0;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            transition: background var(--ease), border-color var(--ease), transform var(--ease);
        }

        .viewer360-control:hover,
        .viewer360-control:focus-visible {
            background: rgba(255, 255, 255, 0.14);
            border-color: rgba(244, 210, 122, 0.36);
            transform: translateY(-1px);
            outline: none;
        }

        .viewer360-close {
            position: absolute;
            top: clamp(26px, 4vw, 58px);
            right: clamp(26px, 4vw, 58px);
            z-index: 4;
            width: 46px;
            height: 46px;
            border-radius: 999px;
            border: 1px solid rgba(244, 210, 122, 0.22);
            background: rgba(63, 12, 10, 0.58);
            color: #fffaf0;
            font-size: 1.3rem;
            cursor: pointer;
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        @media (max-width: 980px) {
            .project-detail-page { padding: 14px; align-items: start; }
            .project-detail-shell { width: 100%; min-height: auto; }
            .project-detail-hero { grid-template-columns: 1fr; min-height: auto; }
            .project-detail-visual { min-height: 460px; border-right: 0; border-bottom: 1px solid rgba(244, 210, 122, 0.12); }
            .project-detail-frame { height: min(560px, 58vh); }
            .project-detail-head h1 { max-width: none; font-size: clamp(2.5rem, 11vw, 4.2rem); }
        }

        @media (max-width: 640px) {
            .project-detail-visual { min-height: 340px; padding: 16px; }
            .project-detail-frame { height: 300px; padding: 8px; }
            .view-360-btn { right: 16px; bottom: 16px; padding: 10px 14px; }
        }

        /* Wikipedia-style professional project detail refinement - UI only */
        .project-detail-page {
            min-height: 100vh;
            padding: clamp(18px, 2.6vw, 42px);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--hero-white, #fffaf0);
            background:
                radial-gradient(circle at 12% 8%, rgba(244, 210, 122, 0.14), transparent 26%),
                radial-gradient(circle at 88% 18%, rgba(255, 255, 255, 0.07), transparent 25%),
                linear-gradient(135deg, #5e1714 0%, #8d2f28 45%, #b85a4d 100%);
        }

        .project-detail-shell {
            width: min(1480px, 94vw);
            min-height: auto;
            margin: 0 auto;
            display: grid;
            grid-template-rows: auto 1fr;
            gap: clamp(14px, 1.8vw, 22px);
        }

        .project-detail-hero {
            width: 100%;
            min-height: min(720px, calc(100vh - 138px));
            display: grid;
            grid-template-columns: minmax(420px, 0.92fr) minmax(520px, 1.08fr);
            align-items: stretch;
            border-radius: clamp(28px, 2.6vw, 42px);
            overflow: hidden;
            border: 1px solid rgba(244, 210, 122, 0.13);
            background:
                linear-gradient(145deg, rgba(255,255,255,0.10), rgba(255,255,255,0.035)),
                rgba(84, 18, 15, 0.30);
            box-shadow:
                0 34px 86px rgba(31, 6, 5, 0.22),
                inset 0 1px 0 rgba(255,255,255,0.12);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
        }

        .project-detail-visual {
            min-height: 100%;
            padding: clamp(22px, 2.5vw, 38px);
            display: flex;
            align-items: center;
            justify-content: center;
            border-right: 1px solid rgba(244, 210, 122, 0.10);
            background:
                radial-gradient(circle at 18% 12%, rgba(244, 210, 122, 0.12), transparent 28%),
                linear-gradient(145deg, rgba(90, 22, 18, 0.72), rgba(154, 52, 44, 0.56));
        }

        .project-detail-visual::before {
            inset: clamp(10px, 1.4vw, 18px);
            border-radius: clamp(22px, 2.2vw, 34px);
            border: 1px solid rgba(244, 210, 122, 0.08);
            background:
                radial-gradient(circle at 10% 12%, rgba(244,210,122,0.09), transparent 28%),
                linear-gradient(145deg, rgba(255,255,255,0.07), rgba(255,255,255,0.018));
        }

        .project-detail-frame {
            position: relative;
            z-index: 1;
            width: min(100%, 660px);
            height: min(440px, 54vh);
            aspect-ratio: auto;
            display: block;
            padding: 10px;
            border-radius: clamp(22px, 2vw, 30px);
            border: 1px solid rgba(244, 210, 122, 0.14);
            background:
                linear-gradient(180deg, rgba(255,255,255,0.13), rgba(255,255,255,0.035)),
                rgba(255,255,255,0.055);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.13),
                0 28px 66px rgba(32, 6, 5, 0.19);
            overflow: hidden;
            cursor: zoom-in;
        }

        .project-detail-frame::before { display: none; }

        .project-detail-visual img {
            width: 100%;
            height: 100%;
            border-radius: clamp(16px, 1.6vw, 24px);
            object-fit: cover;
            object-position: center;
            box-shadow: none;
            filter: saturate(1.04) contrast(1.02);
        }

        .project-detail-orbit {
            top: 18px;
            left: 18px;
            padding: 8px 12px;
            border: 1px solid rgba(244, 210, 122, 0.16);
            background: rgba(78, 16, 14, 0.38);
            color: #fffaf0;
            font-size: 0.70rem;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        .view-360-btn {
            position: absolute;
            right: 18px;
            bottom: 18px;
            z-index: 5;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 13px;
            border-radius: 999px;
            border: 1px solid rgba(244, 210, 122, 0.22);
            background: linear-gradient(145deg, rgba(244, 210, 122, 0.13), rgba(184, 90, 77, 0.14)), rgba(63, 12, 10, 0.46);
            color: #fffaf0;
            font-weight: 800;
            font-size: 0.68rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            cursor: pointer;
            box-shadow: 0 12px 26px rgba(31, 6, 5, 0.18), inset 0 1px 0 rgba(255,255,255,0.12);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            transition: transform var(--ease), background var(--ease), border-color var(--ease), box-shadow var(--ease);
        }

        .view-360-btn:hover,
        .view-360-btn:focus-visible {
            transform: translateY(-2px);
            border-color: rgba(244, 210, 122, 0.34);
            background: linear-gradient(145deg, rgba(244, 210, 122, 0.22), rgba(184, 90, 77, 0.20)), rgba(63, 12, 10, 0.56);
            outline: none;
        }

        .project-detail-body {
            min-height: 100%;
            padding: clamp(34px, 4vw, 64px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: clamp(18px, 2vw, 26px);
            background:
                radial-gradient(circle at 88% 14%, rgba(244, 210, 122, 0.09), transparent 26%),
                linear-gradient(180deg, rgba(255,255,255,0.085), rgba(255,255,255,0.026));
        }

        .project-detail-head { display: block; }

        .project-detail-head h1 {
            margin: 14px 0 0;
            max-width: 14ch;
            font-size: clamp(2.65rem, 4.7vw, 5.4rem);
            line-height: 0.98;
            letter-spacing: -0.06em;
            color: #fffaf0;
            text-shadow: 0 18px 46px rgba(30, 6, 5, 0.28);
        }

        .project-detail-article {
            max-width: 68ch;
            display: grid;
            gap: 18px;
            color: rgba(255, 250, 240, 0.82);
        }

        .project-detail-article p {
            margin: 0;
            font-size: clamp(0.98rem, 1.05vw, 1.12rem);
            line-height: 1.86;
            color: rgba(255, 250, 240, 0.78);
        }

        .project-detail-article h2 {
            margin: 6px 0 -6px;
            padding-top: 14px;
            border-top: 1px solid rgba(244, 210, 122, 0.16);
            color: rgba(244, 210, 122, 0.92);
            font-size: 0.92rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .project-detail-copy {
            max-width: 68ch;
            margin: 0;
            font-size: clamp(0.98rem, 1.05vw, 1.12rem);
            line-height: 1.86;
            color: rgba(255, 250, 240, 0.78);
        }

        .project-meta { display: none !important; }

        .back-link {
            justify-self: start;
            padding: 11px 20px;
            border-radius: 999px;
            border: 1px solid rgba(244, 210, 122, 0.22);
            background: linear-gradient(145deg, rgba(244, 210, 122, 0.12), rgba(184, 90, 77, 0.12)), rgba(63, 12, 10, 0.30);
            color: #fffaf0;
            font-weight: 800;
            text-decoration: none;
            box-shadow: 0 12px 28px rgba(31, 6, 5, 0.14), inset 0 1px 0 rgba(255,255,255,0.13);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .back-link:hover,
        .back-link:focus-visible {
            transform: translateY(-2px);
            background: linear-gradient(145deg, rgba(244, 210, 122, 0.21), rgba(184, 90, 77, 0.18)), rgba(63, 12, 10, 0.40);
            border-color: rgba(244, 210, 122, 0.34);
            outline: none;
        }

        @media (max-width: 1100px) {
            .project-detail-page { padding: 14px; align-items: start; }
            .project-detail-shell { width: 100%; min-height: auto; }
            .project-detail-hero { grid-template-columns: 1fr; min-height: auto; }
            .project-detail-visual { min-height: 430px; border-right: 0; border-bottom: 1px solid rgba(244, 210, 122, 0.10); }
            .project-detail-frame { width: min(100%, 760px); height: min(440px, 54vh); }
            .project-detail-head h1 { max-width: none; font-size: clamp(2.35rem, 10vw, 4.1rem); }
        }

        @media (max-width: 640px) {
            .project-detail-visual { min-height: 310px; padding: 16px; }
            .project-detail-frame { height: 270px; padding: 8px; }
            .view-360-btn { right: 14px; bottom: 14px; padding: 8px 12px; font-size: 0.64rem; }
            .project-detail-body { padding: 24px 18px; }
            .project-detail-article { gap: 14px; }
        }

    

        /* Long-description Wikipedia-style detail layout - final override */
        .project-detail-page {
            align-items: flex-start;
            padding-top: clamp(20px, 3vh, 42px);
        }

        .project-detail-shell {
            width: min(1500px, 94vw);
            min-height: auto;
        }

        .project-detail-hero {
            min-height: auto;
            max-height: none;
            grid-template-columns: minmax(360px, 0.42fr) minmax(520px, 0.58fr);
            align-items: stretch;
        }

        .project-detail-visual {
            min-height: 620px;
            padding: clamp(22px, 2.2vw, 34px);
        }

        .project-detail-frame {
            width: min(100%, 560px);
            height: min(390px, 48vh);
            margin: 0 auto;
        }

        .project-detail-body {
            justify-content: flex-start;
            align-content: start;
            padding: clamp(34px, 4vw, 64px);
            overflow: hidden;
        }

        .project-detail-head h1 {
            max-width: 16ch;
            font-size: clamp(2.2rem, 3.5vw, 4.25rem);
            line-height: 1.08;
            letter-spacing: -0.045em;
            font-family: Georgia, "Times New Roman", serif;
            font-weight: 700;
            text-wrap: balance;
        }

        .project-detail-article {
            width: 100%;
            max-width: 74ch;
            max-height: min(430px, 48vh);
            overflow-y: auto;
            padding: 4px 18px 8px 0;
            display: grid;
            gap: 12px;
            scrollbar-width: thin;
            scrollbar-color: rgba(244, 210, 122, 0.44) rgba(255,255,255,0.06);
        }

        .project-detail-article::-webkit-scrollbar { width: 7px; }
        .project-detail-article::-webkit-scrollbar-track { background: rgba(255,255,255,0.055); border-radius: 999px; }
        .project-detail-article::-webkit-scrollbar-thumb { background: rgba(244, 210, 122, 0.44); border-radius: 999px; }

        .project-detail-article p {
            text-align: left;
            font-size: clamp(0.97rem, 0.96vw, 1.03rem);
            line-height: 1.82;
            color: rgba(255, 250, 240, 0.86);
        }

        .project-detail-article h2 {
            position: sticky;
            top: 0;
            z-index: 2;
            margin: 0 0 2px;
            padding: 0 0 10px;
            border-bottom: 1px solid rgba(244, 210, 122, 0.18);
            background: linear-gradient(180deg, rgba(117, 37, 31, 0.98), rgba(117, 37, 31, 0.82) 78%, rgba(117, 37, 31, 0));
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .project-detail-copy { display: none; }

        @media (max-width: 1100px) {
            .project-detail-hero { grid-template-columns: 1fr; }
            .project-detail-visual { min-height: auto; }
            .project-detail-frame { width: min(100%, 720px); height: min(420px, 52vh); }
            .project-detail-article { max-height: none; overflow: visible; padding-right: 0; }
            .project-detail-article h2 { position: static; background: transparent; }
        }

        @media (max-width: 640px) {
            .project-detail-frame { height: 260px; }
            .project-detail-head h1 { font-size: clamp(2.25rem, 10vw, 3.4rem); }
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
<?php if (!$slEmbed): ?>
</head>
<body>
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
        
        const images = document.querySelectorAll('img[src]');
        const videos = document.querySelectorAll('video');
        totalResources = images.length + videos.length;
        
        const checkAllLoaded = function() {
            if (totalResources === 0 || resourcesLoaded >= totalResources) {
                setTimeout(function() {
                    loader.classList.add('is-loaded');
                    document.body.classList.remove('is-loading');
                }, 500);
            }
        };
        
        window.addEventListener('load', function() {
            if (totalResources === 0) {
                checkAllLoaded();
            }
        });
        
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
        
        setTimeout(function() {
            if (!loader.classList.contains('is-loaded')) {
                loader.classList.add('is-loaded');
                document.body.classList.remove('is-loading');
            }
        }, 5000);
    })();
</script>
<?php endif; ?>
<?php if (!$slEmbed && $selectedProject && $selectedProjectDistrict): ?>
    <section class="project-detail-page">
        <div class="project-detail-shell">
            <a class="back-link" href="index.php#districts" data-float>&larr; Back</a>
            <article class="panel project-detail-hero" data-float style="--float-delay: 70ms;">
                <div class="project-detail-visual" data-float style="--float-delay: 140ms;">
                    <div class="project-detail-backdrop" style="background-image:url('<?php echo htmlspecialchars($selectedProject['image'], ENT_QUOTES, 'UTF-8'); ?>')"></div>
                    <div class="project-detail-frame">
                        <span class="project-detail-orbit">Image Preview</span>
                        <button class="view-360-btn" type="button" data-view-360 aria-label="Open image viewer">Open image</button>
                        <img data-project-hero-image src="<?php echo htmlspecialchars($selectedProject['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($selectedProject['title'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
                <div class="project-detail-body" data-float style="--float-delay: 210ms;">
                    <div class="project-detail-head">
                        <div>
                            <span class="eyebrow"><?php echo htmlspecialchars($selectedProjectDistrict['name'], ENT_QUOTES, 'UTF-8'); ?> District</span>
                            <h1><?php echo htmlspecialchars($selectedProject['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
                        </div>
                    </div>
                    <?php
                        $projectDescription = trim((string) ($selectedProject['caption'] ?? ''));
                        $projectDescription = $projectDescription !== '' ? $projectDescription : 'Project description is coming soon.';
                        $descriptionParagraphs = preg_split('/\\R{2,}/', $projectDescription) ?: [];
                        if (count($descriptionParagraphs) <= 1) {
                            $descriptionParagraphs = preg_split('/(?<=[.!?])\\s+(?=[A-Z0-9])/', $projectDescription) ?: [$projectDescription];
                            if (count($descriptionParagraphs) > 3) {
                                $descriptionParagraphs = array_chunk($descriptionParagraphs, 3);
                                $descriptionParagraphs = array_map(static fn(array $chunk): string => implode(' ', $chunk), $descriptionParagraphs);
                            }
                        }
                        $descriptionParagraphs = array_values(array_filter(array_map('trim', $descriptionParagraphs), static fn(string $paragraph): bool => $paragraph !== ''));
                    ?>
                    <article class="project-detail-article" aria-label="Project description">
                        <h2>Overview</h2>
                        <?php foreach ($descriptionParagraphs as $paragraph): ?>
                            <p><?php echo htmlspecialchars($paragraph, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endforeach; ?>

                    </article>
                </div>
            </article>
        </div>
    </section>
<?php else: ?>
    <section id="<?php echo htmlspecialchars($slAppId, ENT_QUOTES, 'UTF-8'); ?>" class="sl-app">
        <main class="workspace">
        <section class="panel map-stage" data-float>
            <div class="map-shell" id="mapShell">
                <div class="tooltip" id="tooltip"></div>
                <button class="map-helper-toggle" type="button" aria-label="Show district selection help" aria-controls="mapHelper" aria-expanded="false">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.8"></circle>
                        <path d="M12 10v5.2" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"></path>
                        <circle cx="12" cy="7.4" r="1.15" fill="currentColor"></circle>
                        <circle cx="12" cy="17.7" r="1.1" fill="currentColor"></circle>
                    </svg>
                </button>
                <div class="map-helper" id="mapHelper" aria-hidden="true">
                    <div class="map-helper-head">
                        <span class="map-helper-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path d="M4.5 6.5 9 4l6 2.5L19.5 4v13.5L15 20l-6-2.5L4.5 20z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"></path>
                                <path d="M9 4v13.5M15 6.5V20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path>
                                <circle cx="15" cy="10" r="1.6" fill="currentColor"></circle>
                            </svg>
                        </span>
                        <strong>Select Your District</strong>
                    </div>
                    <span>Select a district to view key development projects, public investments, and regional progress highlights.</span>
                </div>
                <div class="map-canvas">
                    <svg viewBox="140 40 690 1140" role="img" aria-labelledby="mapTitle mapDesc">
                        <title id="mapTitle">Sri Lanka district project map</title>
                        <desc id="mapDesc">Interactive Sri Lanka map with 25 district shapes and project information per district.</desc>
                        <polygon
                            class="island-outline"
                            points="772,901 767,930 759,937 759,950 754,961 750,963 750,971 745,972 744,984 730,996 728,1002 717,1008 702,1024 687,1028 680,1039 671,1043 655,1059 643,1063 631,1073 584,1088 576,1095 556,1094 537,1105 527,1105 516,1108 509,1114 499,1114 489,1123 481,1124 478,1131 466,1137 430,1146 424,1142 396,1142 390,1135 371,1134 357,1127 336,1122 332,1115 327,1115 312,1104 297,1088 278,1052 275,1026 262,995 261,969 238,910 233,884 227,872 227,865 233,855 233,836 223,803 223,790 229,782 229,770 212,699 212,681 216,677 214,642 206,625 195,586 188,548 188,516 194,514 206,484 212,480 212,457 208,446 210,430 217,432 220,456 228,456 235,450 233,435 238,427 248,423 250,413 256,404 257,380 250,357 246,356 245,322 215,294 199,287 187,287 185,274 199,271 228,276 255,298 256,312 269,312 271,302 281,298 291,285 293,264 298,262 298,255 303,247 302,221 298,215 286,214 288,193 291,189 309,184 318,178 319,165 309,155 305,155 299,148 286,141 240,137 236,150 232,151 210,144 210,114 219,111 222,102 232,102 233,91 251,88 262,80 274,77 294,76 309,79 313,74 345,76 366,107 383,125 464,186 498,224 507,239 518,275 526,283 526,288 533,297 533,305 544,311 544,315 550,319 554,329 564,339 572,341 581,362 590,369 593,378 599,382 601,389 609,397 608,414 613,422 613,432 603,433 603,446 608,450 618,450 619,437 627,431 641,437 649,472 654,480 657,510 665,534 669,535 676,566 682,574 697,577 697,588 700,594 705,596 701,612 708,619 715,620 720,633 740,643 744,654 757,670 766,695 776,742 785,766 782,786 784,862 780,864 780,872 776,879 776,897"
                        ></polygon>
                        <?php foreach ($districts as $district): ?>
                            <polygon
                                class="district<?php echo $district['id'] === ($selectedProjectDistrict['id'] ?? $requestedDistrictId ?: 'hambantota') ? ' active' : ''; ?>"
                                data-id="<?php echo htmlspecialchars($district['id'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-name="<?php echo htmlspecialchars($district['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-province="<?php echo htmlspecialchars($district['province'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-accent="<?php echo htmlspecialchars($district['accent'], ENT_QUOTES, 'UTF-8'); ?>"
                                points="<?php echo htmlspecialchars($district['points'], ENT_QUOTES, 'UTF-8'); ?>"
                                tabindex="0"
                            >
                                <title><?php echo htmlspecialchars($district['name'], ENT_QUOTES, 'UTF-8'); ?></title>
                            </polygon>
                        <?php endforeach; ?>
                    </svg>
                </div>
            </div>
        </section>

        <aside class="panel side-panel" data-float style="--float-delay: 80ms;">
            <section class="district-overview" data-float style="--float-delay: 140ms;">
                <span class="eyebrow">Selected District</span>
                <h2 id="districtName">Hambantota</h2>
                <p id="districtSummary">Explore development initiatives, community investments, and project highlights across Hambantota District.</p>
            </section>

            <section class="overview-meta" data-float style="--float-delay: 210ms;">
                <div class="mini-card">
                    <label>Province</label>
                    <strong id="districtProvince">Southern</strong>
                </div>
                <div class="mini-card">
                    <label>Projects</label>
                    <strong id="districtProjects">3 concepts</strong>
                </div>
            </section>

            <section class="project-grid" id="projectGrid"></section>
        </aside>
        </main>
    </section>
<?php endif; ?>
<?php if (!$slEmbed && $selectedProject && $selectedProjectDistrict): ?>
    <div id="viewer360" class="viewer360" aria-hidden="true">
        <div class="viewer360-inner">
            <img id="viewer360Image" src="" alt="Project image enlarged view" loading="lazy" decoding="async">
        </div>
        <div class="viewer360-toolbar">
            <button class="viewer360-control" type="button" data-zoom-out aria-label="Zoom out">-</button>
            <button class="viewer360-control" type="button" data-zoom-reset aria-label="Reset zoom">100%</button>
            <button class="viewer360-control" type="button" data-zoom-in aria-label="Zoom in">+</button>
            <span>Scroll to zoom. Drag to move.</span>
        </div>
        <button class="viewer360-close" type="button" aria-label="Close image viewer">&times;</button>
    </div>
<?php endif; ?>

    <script>
        (() => {
            const floatItems = Array.from(document.querySelectorAll("[data-float]"));
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
        })();
    </script>

<?php if (!(!$slEmbed && $selectedProject && $selectedProjectDistrict)): ?>
    <script>
        (() => {
            const root = document.getElementById(<?php echo json_encode($slAppId); ?>);
            if (!root) {
                return;
            }

            const districtData = <?php echo $districtData; ?>;
            const districtMap = new Map(districtData.map((district) => [district.id, district]));

            const elements = {
                mapStage: root.querySelector(".map-stage"),
                mapShell: root.querySelector("#mapShell"),
                tooltip: root.querySelector("#tooltip"),
                mapHelper: root.querySelector("#mapHelper"),
                mapHelperToggle: root.querySelector(".map-helper-toggle"),
                districtName: root.querySelector("#districtName"),
                districtSummary: root.querySelector("#districtSummary"),
                districtProvince: root.querySelector("#districtProvince"),
                districtProjects: root.querySelector("#districtProjects"),
                projectGrid: root.querySelector("#projectGrid"),
                sidePanel: root.querySelector(".side-panel"),
                districts: Array.from(root.querySelectorAll(".district"))
            };

            let selectedDistrictId = <?php echo json_encode($selectedProjectDistrict['id'] ?? $requestedDistrictId ?: 'hambantota'); ?>;
            const mobileHelperQuery = window.matchMedia("(max-width: 780px)");
            let helperAutoCloseTimer = null;

            function stopHelperAutoClose() {
                if (helperAutoCloseTimer) {
                    window.clearTimeout(helperAutoCloseTimer);
                    helperAutoCloseTimer = null;
                }
            }

            function closeMobileHelper() {
                stopHelperAutoClose();
                if (!elements.mapHelper || !elements.mapHelperToggle || !mobileHelperQuery.matches) {
                    return;
                }

                elements.mapHelper.classList.remove("is-open");
                elements.mapHelper.setAttribute("aria-hidden", "true");
                elements.mapHelperToggle.setAttribute("aria-expanded", "false");
            }

            function scheduleHelperAutoClose() {
                stopHelperAutoClose();
                if (!mobileHelperQuery.matches) {
                    return;
                }

                helperAutoCloseTimer = window.setTimeout(closeMobileHelper, 3200);
            }

            function openMobileHelper() {
                if (!elements.mapHelper || !elements.mapHelperToggle || !mobileHelperQuery.matches) {
                    return;
                }

                elements.mapHelper.classList.add("is-open");
                elements.mapHelper.setAttribute("aria-hidden", "false");
                elements.mapHelperToggle.setAttribute("aria-expanded", "true");
                scheduleHelperAutoClose();
            }

            function toggleMobileHelper() {
                if (!elements.mapHelper || !elements.mapHelperToggle || !mobileHelperQuery.matches) {
                    return;
                }

                if (elements.mapHelper.classList.contains("is-open")) {
                    closeMobileHelper();
                } else {
                    openMobileHelper();
                }
            }

            function escapeHtml(value) {
                return String(value)
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#39;");
            }

            function renderProjectGrid(projects) {
                if (!projects.length) {
                    elements.projectGrid.innerHTML = `<div class="project-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01" stroke-linecap="round"/></svg><strong>Coming Soon</strong><span>Development projects for this district will be announced shortly. Stay tuned for updates.</span></div>`;
                    return;
                }

                elements.projectGrid.innerHTML = projects.map((project, index) => `
                    <article class="project-card" data-float style="--float-delay:${index * 70}ms;">
                        <div class="project-row">
                            <div class="project-visual">
                                <div class="project-visual-backdrop" style="background-image:url('${project.image}')"></div>
                                <img src="${project.image}" alt="${escapeHtml(project.title)}" loading="lazy" decoding="async">
                            </div>
                            <div class="project-copy">
                                <h3 class="project-name">${escapeHtml(project.title)}</h3>
                                <p class="project-line">${escapeHtml(project.caption)}</p>
                            </div>
                            <a class="read-more" href="sl.php?district=${encodeURIComponent(selectedDistrictId)}&project=${encodeURIComponent(project.slug)}" aria-label="Open ${escapeHtml(project.title)}">
                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="M8 5l8 7-8 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                            </a>
                        </div>
                    </article>
                `).join("");

                const cards = Array.from(elements.projectGrid.querySelectorAll("[data-float]"));
                requestAnimationFrame(() => {
                    cards.forEach((card, index) => {
                        window.setTimeout(() => {
                            card.classList.add("is-visible");
                        }, index * 70);
                    });
                });
            }

            function syncSidePanelHeight() {
                if (!elements.mapStage || !elements.sidePanel) {
                    return;
                }

                const mapHeight = elements.mapStage.getBoundingClientRect().height;
                if (mapHeight > 0) {
                    elements.sidePanel.style.height = `${mapHeight}px`;
                    elements.sidePanel.style.minHeight = `${mapHeight}px`;
                    elements.sidePanel.style.maxHeight = `${mapHeight}px`;
                }
            }

            function syncProjectGridHeight() {
                if (!elements.sidePanel || !elements.projectGrid) {
                    return;
                }

                const panelStyles = window.getComputedStyle(elements.sidePanel);
                const rowGap = Number.parseFloat(panelStyles.rowGap || panelStyles.gap || "0") || 0;
                const paddingTop = Number.parseFloat(panelStyles.paddingTop || "0") || 0;
                const paddingBottom = Number.parseFloat(panelStyles.paddingBottom || "0") || 0;
                const panelHeight = elements.sidePanel.clientHeight;
                const overviewHeight = elements.sidePanel.querySelector(".district-overview")?.getBoundingClientRect().height || 0;
                const metaHeight = elements.sidePanel.querySelector(".overview-meta")?.getBoundingClientRect().height || 0;
                const availableHeight = panelHeight - paddingTop - paddingBottom - overviewHeight - metaHeight - (rowGap * 2);

                if (availableHeight > 0) {
                    elements.projectGrid.style.height = `${availableHeight}px`;
                    elements.projectGrid.style.maxHeight = `${availableHeight}px`;
                }
            }

            function renderDistrict(id) {
                const district = districtMap.get(id);
                if (!district) {
                    return;
                }

                selectedDistrictId = id;

                elements.districts.forEach((item) => {
                    const isActive = item.dataset.id === id;
                    item.classList.toggle("active", isActive);
                });

                elements.districtName.textContent = district.name;
                elements.districtSummary.textContent = district.summary;
                elements.districtProvince.textContent = district.province;
                elements.districtProjects.textContent = `${district.projects.length} concepts`;

                renderProjectGrid(district.projects);
                syncSidePanelHeight();
                syncProjectGridHeight();
                elements.projectGrid.scrollTop = 0;
                closeMobileHelper();
            }

            function getPointerPosition(event, district) {
                if (event.type.startsWith("key") || event.type === "focus") {
                    const box = district.getBoundingClientRect();
                    return { x: box.left + box.width / 2, y: box.top };
                }

                return { x: event.clientX, y: event.clientY };
            }

            function showTooltip(event, district) {
                const mapRect = elements.mapShell.getBoundingClientRect();
                const point = getPointerPosition(event, district);
                elements.tooltip.textContent = district.dataset.name;
                elements.tooltip.style.left = `${point.x - mapRect.left}px`;
                elements.tooltip.style.top = `${point.y - mapRect.top}px`;
                elements.tooltip.classList.add("visible");
            }

            function hideTooltip() {
                elements.tooltip.classList.remove("visible");
            }

            elements.districts.forEach((district) => {
                district.addEventListener("mouseenter", (event) => showTooltip(event, district));
                district.addEventListener("mousemove", (event) => showTooltip(event, district));
                district.addEventListener("mouseleave", hideTooltip);
                district.addEventListener("focus", (event) => showTooltip(event, district));
                district.addEventListener("blur", hideTooltip);
                district.addEventListener("click", () => renderDistrict(district.dataset.id));
                district.addEventListener("keydown", (event) => {
                    if (event.key === "Enter" || event.key === " ") {
                        event.preventDefault();
                        renderDistrict(district.dataset.id);
                        showTooltip(event, district);
                    }
                });
            });

            if (elements.mapHelper && elements.mapHelperToggle) {
                elements.mapHelperToggle.addEventListener("click", (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    toggleMobileHelper();
                });

                elements.mapHelper.addEventListener("click", (event) => {
                    event.stopPropagation();
                });

                document.addEventListener("click", (event) => {
                    if (!mobileHelperQuery.matches || !elements.mapHelper.classList.contains("is-open")) {
                        return;
                    }

                    const target = event.target;
                    if (!(target instanceof Node)) {
                        return;
                    }

                    if (elements.mapHelper.contains(target) || elements.mapHelperToggle.contains(target)) {
                        return;
                    }

                    closeMobileHelper();
                });

                document.addEventListener("keydown", (event) => {
                    if (event.key === "Escape") {
                        closeMobileHelper();
                    }
                });

                const handleMobileHelperModeChange = () => {
                    stopHelperAutoClose();
                    if (!mobileHelperQuery.matches) {
                        elements.mapHelper.classList.remove("is-open");
                        elements.mapHelper.setAttribute("aria-hidden", "true");
                        elements.mapHelperToggle.setAttribute("aria-expanded", "false");
                    }
                };

                if (typeof mobileHelperQuery.addEventListener === "function") {
                    mobileHelperQuery.addEventListener("change", handleMobileHelperModeChange);
                } else if (typeof mobileHelperQuery.addListener === "function") {
                    mobileHelperQuery.addListener(handleMobileHelperModeChange);
                }
            }

            window.addEventListener("resize", () => {
                syncSidePanelHeight();
                syncProjectGridHeight();
            });
            syncSidePanelHeight();
            syncProjectGridHeight();
            renderDistrict(selectedDistrictId);
        })();
    </script>
<?php endif; ?>
<?php if (!$slEmbed && $selectedProject && $selectedProjectDistrict): ?>
    <script>
        (() => {
            const viewer = document.getElementById("viewer360");
            const viewerImg = document.getElementById("viewer360Image");
            const viewerInner = viewer?.querySelector(".viewer360-inner");
            const heroImg = document.querySelector("[data-project-hero-image]");
            const openButton = document.querySelector("[data-view-360]");
            const closeButton = document.querySelector(".viewer360-close");
            const zoomInButton = viewer?.querySelector("[data-zoom-in]");
            const zoomOutButton = viewer?.querySelector("[data-zoom-out]");
            const zoomResetButton = viewer?.querySelector("[data-zoom-reset]");

            if (!viewer || !viewerImg || !viewerInner || !heroImg || !openButton || !closeButton || !zoomInButton || !zoomOutButton || !zoomResetButton) {
                return;
            }

            let scale = 1;
            let minScale = 1;
            let posX = 0;
            let posY = 0;
            let startX = 0;
            let startY = 0;
            let dragging = false;
            const maxScale = 4;

            function clampPosition() {
                const bounds = viewerInner.getBoundingClientRect();
                const naturalWidth = viewerImg.naturalWidth || bounds.width;
                const naturalHeight = viewerImg.naturalHeight || bounds.height;
                const renderedWidth = naturalWidth * scale;
                const renderedHeight = naturalHeight * scale;
                const maxX = Math.max(0, (renderedWidth - bounds.width) / 2);
                const maxY = Math.max(0, (renderedHeight - bounds.height) / 2);

                posX = Math.min(maxX, Math.max(-maxX, posX));
                posY = Math.min(maxY, Math.max(-maxY, posY));
            }

            function applyTransform() {
                clampPosition();
                viewerImg.style.transform = `translate(calc(-50% + ${posX}px), calc(-50% + ${posY}px)) scale(${scale})`;
                zoomResetButton.textContent = `${Math.round((scale / minScale) * 100)}%`;
            }

            function updateBaseScale() {
                const bounds = viewerInner.getBoundingClientRect();
                const naturalWidth = viewerImg.naturalWidth || bounds.width;
                const naturalHeight = viewerImg.naturalHeight || bounds.height;

                if (!bounds.width || !bounds.height || !naturalWidth || !naturalHeight) {
                    minScale = 1;
                    return;
                }

                minScale = Math.min(bounds.width / naturalWidth, bounds.height / naturalHeight, 1);
                scale = Math.max(scale, minScale);
            }

            function resetView() {
                updateBaseScale();
                scale = minScale;
                posX = 0;
                posY = 0;
                applyTransform();
            }

            function openViewer() {
                viewerImg.src = heroImg.currentSrc || heroImg.src;
                viewer.classList.add("active");
                viewer.setAttribute("aria-hidden", "false");
                document.body.style.overflow = "hidden";
                if (viewerImg.complete) {
                    resetView();
                }
            }

            function closeViewer() {
                viewer.classList.remove("active", "dragging");
                viewer.setAttribute("aria-hidden", "true");
                document.body.style.overflow = "";
                dragging = false;
            }

            heroImg.addEventListener("click", openViewer);
            openButton.addEventListener("click", openViewer);
            closeButton.addEventListener("click", closeViewer);
            zoomInButton.addEventListener("click", () => {
                scale = Math.min(maxScale, scale + 0.25);
                applyTransform();
            });
            zoomOutButton.addEventListener("click", () => {
                scale = Math.max(minScale, scale - 0.25);
                applyTransform();
            });
            zoomResetButton.addEventListener("click", resetView);

            viewer.addEventListener("click", (event) => {
                if (event.target === viewer) {
                    closeViewer();
                }
            });

            viewerInner.addEventListener("pointerdown", (event) => {
                if (!viewer.classList.contains("active")) {
                    return;
                }
                dragging = true;
                viewer.classList.add("dragging");
                startX = event.clientX - posX;
                startY = event.clientY - posY;
                viewerInner.setPointerCapture(event.pointerId);
            });

            viewerInner.addEventListener("pointermove", (event) => {
                if (!dragging) {
                    return;
                }
                posX = event.clientX - startX;
                posY = event.clientY - startY;
                applyTransform();
            });

            function stopDragging(event) {
                dragging = false;
                viewer.classList.remove("dragging");
                try {
                    viewerInner.releasePointerCapture(event.pointerId);
                } catch (error) {}
            }

            viewerInner.addEventListener("pointerup", stopDragging);
            viewerInner.addEventListener("pointercancel", stopDragging);
            viewerInner.addEventListener("pointerleave", stopDragging);

            viewer.addEventListener("wheel", (event) => {
                if (!viewer.classList.contains("active")) {
                    return;
                }
                event.preventDefault();
                scale += event.deltaY * -0.0012;
                scale = Math.min(Math.max(minScale, scale), Math.max(minScale, maxScale));
                applyTransform();
            }, { passive: false });

            viewerImg.addEventListener("dblclick", () => {
                if (scale > minScale + 0.05) {
                    resetView();
                    return;
                }
                scale = Math.min(maxScale, minScale + 1);
                applyTransform();
            });

            viewerImg.addEventListener("load", resetView);
            window.addEventListener("resize", () => {
                if (viewer.classList.contains("active")) {
                    resetView();
                }
            });

            document.addEventListener("keydown", (event) => {
                if (event.key === "Escape") {
                    closeViewer();
                }
            });
        })();
    </script>
<?php endif; ?>
<?php if (!$slEmbed): ?>
</body>
</html>
<?php endif; ?>
