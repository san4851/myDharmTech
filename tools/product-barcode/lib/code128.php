<?php
declare(strict_types=1);

/** Code 128 bar/space widths (values 0–106). Stop is 13 modules. */
function code128_patterns(): array
{
    return [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312',
        '132212', '221213', '221312', '231212', '112232', '122132', '122231', '113222',
        '123122', '123221', '223211', '221132', '221231', '213212', '223112', '312131',
        '311222', '321122', '321221', '312212', '322112', '322211', '212123', '212321',
        '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121',
        '313121', '211331', '231131', '213113', '213311', '213131', '311123', '311321',
        '331121', '312113', '312311', '332111', '314111', '221411', '431111', '111224',
        '111422', '121124', '121421', '141122', '141221', '112214', '112412', '122114',
        '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112',
        '421211', '212141', '214121', '412121', '111143', '111341', '131141', '114113',
        '114311', '411113', '411311', '113141', '114131', '311141', '411131', '211412',
        '211214', '211232', '2331112',
    ];
}

function code128_svg(string $digits, int $module = 2, int $height = 72): string
{
    if (!preg_match('/^\d+$/', $digits)) {
        throw new InvalidArgumentException('Code 128 payload must be numeric.');
    }
    if (strlen($digits) % 2 === 1) {
        $digits = '0' . $digits;
    }

    $values = [105]; // Start Code C (numeric pairs)
    $len = strlen($digits);
    for ($i = 0; $i < $len; $i += 2) {
        $values[] = (int) substr($digits, $i, 2);
    }
    $sum = $values[0];
    $count = count($values);
    for ($i = 1; $i < $count; $i++) {
        $sum += $values[$i] * $i;
    }
    $values[] = $sum % 103;
    $values[] = 106; // Stop

    $patterns = code128_patterns();
    $quiet = 10 * $module;
    $x = $quiet;
    $rects = '';
    foreach ($values as $value) {
        $pattern = $patterns[$value];
        $isBar = true;
        $plen = strlen($pattern);
        for ($i = 0; $i < $plen; $i++) {
            $w = ((int) $pattern[$i]) * $module;
            if ($isBar) {
                $rects .= '<rect x="' . $x . '" y="0" width="' . $w . '" height="' . $height . '" fill="#000"/>';
            }
            $x += $w;
            $isBar = !$isBar;
        }
    }
    $width = $x + $quiet;
    return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '" role="img" aria-label="Code 128 barcode">' . $rects . '</svg>';
}
