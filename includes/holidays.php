<?php
/**
 * Public holidays of Uganda for a given year, computed (nothing to maintain each year).
 * Fixed dates come from the Public Holidays Act. Good Friday and Easter Monday follow Easter. Eid al-Fitr and Eid al-Adha
 * are worked out from the Islamic calendar and can move by a day when the moon is sighted, so they carry a note.
 * Returns ['Y-m-d' => ['name' => ..., 'note' => ...]].
 */
function uganda_holidays(int $year): array
{
    $h = [
        sprintf('%d-01-01', $year) => 'New Year\'s Day',
        sprintf('%d-01-26', $year) => 'NRM Liberation Day',
        sprintf('%d-02-16', $year) => 'Archbishop Janani Luwum Day',
        sprintf('%d-03-08', $year) => 'International Women\'s Day',
        sprintf('%d-05-01', $year) => 'Labour Day',
        sprintf('%d-06-03', $year) => 'Uganda Martyrs\' Day',
        sprintf('%d-06-09', $year) => 'National Heroes\' Day',
        sprintf('%d-10-09', $year) => 'Independence Day',
        sprintf('%d-12-25', $year) => 'Christmas Day',
        sprintf('%d-12-26', $year) => 'Boxing Day',
    ];
    $out = [];
    foreach ($h as $d => $n) $out[$d] = ['name' => $n, 'note' => ''];

    $easter = (new DateTimeImmutable("$year-03-21"))->modify('+' . easter_days($year) . ' days');
    $out[$easter->modify('-2 days')->format('Y-m-d')] = ['name' => 'Good Friday', 'note' => ''];
    $out[$easter->modify('+1 day')->format('Y-m-d')] = ['name' => 'Easter Monday', 'note' => ''];

    // Islamic (tabular) calendar -> Gregorian, for the two Eid holidays
    $islamicToJd = fn(int $y, int $m, int $d): int => $d + (int) ceil(29.5 * ($m - 1)) + ($y - 1) * 354 + intdiv(3 + 11 * $y, 30) + 1948440 - 1;
    $base = (int) round(($year - 622) * 33 / 32);
    for ($iy = $base - 1; $iy <= $base + 2; $iy++) {
        foreach ([[10, 1, 'Eid al-Fitr'], [12, 10, 'Eid al-Adha']] as [$im, $id, $name]) {
            [$gm, $gd, $gy] = array_map('intval', explode('/', jdtogregorian($islamicToJd($iy, $im, $id))));
            if ($gy === $year) $out[sprintf('%04d-%02d-%02d', $gy, $gm, $gd)] = ['name' => $name, 'note' => 'Date confirmed by moon sighting and may shift by a day'];
        }
    }
    ksort($out);
    return $out;
}
