<?php
/**
 * Read spreadsheets uploaded by teachers: .xlsx (pure PHP, no zip extension needed) and .csv.
 * Both return the first sheet as a list of rows, each a list of strings.
 */

function import_read(string $path, string $ext): array
{
    return strtolower($ext) === 'xlsx' ? xlsx_read($path) : csv_read($path);
}

function csv_read(string $path): array
{
    $raw = file_get_contents($path);
    if ($raw === false) throw new RuntimeException('Could not read the file.');
    if (str_starts_with($raw, "\xEF\xBB\xBF")) $raw = substr($raw, 3);
    if (!mb_check_encoding($raw, 'UTF-8')) $raw = (string) iconv('CP1252', 'UTF-8//IGNORE', $raw);
    $first = strtok($raw, "\n") ?: '';
    $delim = substr_count($first, ';') > substr_count($first, ',') ? ';' : (substr_count($first, "\t") > substr_count($first, ',') ? "\t" : ',');
    $h = fopen('php://temp', 'w+'); fwrite($h, $raw); rewind($h);
    $rows = [];
    while (($r = fgetcsv($h, 0, $delim)) !== false) {
        if ($r === [null]) continue;
        $rows[] = array_map(fn($v) => trim((string) $v), $r);
        if (count($rows) > 20000) throw new RuntimeException('The file has more than 20,000 rows. Please split it.');
    }
    fclose($h);
    return $rows;
}

/** Extract named entries from a zip file (stored or deflated) without ZipArchive. */
function zip_entries(string $path, array $want): array
{
    $d = file_get_contents($path);
    $end = strrpos($d, "PK\x05\x06");
    if ($d === false || $end === false) throw new RuntimeException('This is not a valid .xlsx file.');
    $e = unpack('vdisk/vcd/vn1/vn2/Vsize/Voff', substr($d, $end + 4, 16));
    $pos = $e['off']; $out = [];
    for ($i = 0; $i < $e['n2']; $i++) {
        if (substr($d, $pos, 4) !== "PK\x01\x02") break;
        $c = unpack('vmade/vneed/vflags/vmethod/vtime/vdate/Vcrc/Vcsize/Vusize/vnlen/velen/vclen/vdisk/viattr/Veattr/Voff', substr($d, $pos + 4, 42));
        $name = substr($d, $pos + 46, $c['nlen']);
        $pos += 46 + $c['nlen'] + $c['elen'] + $c['clen'];
        if (!in_array($name, $want, true) && !preg_match('#^xl/worksheets/sheet\d+\.xml$#', $name)) continue;
        $l = unpack('vnlen/velen', substr($d, $c['off'] + 26, 4));
        $data = substr($d, $c['off'] + 30 + $l['nlen'] + $l['elen'], $c['csize']);
        if ($c['usize'] > 60 * 1048576) throw new RuntimeException('Spreadsheet is too large.');
        $out[$name] = $c['method'] === 8 ? gzinflate($data) : ($c['method'] === 0 ? $data : null);
        if ($out[$name] === false || $out[$name] === null) throw new RuntimeException('Could not unpack the spreadsheet.');
    }
    return $out;
}

function xlsx_col_index(string $ref): int
{
    preg_match('/^([A-Z]+)/', $ref, $m);
    $n = 0;
    foreach (str_split($m[1] ?? 'A') as $ch) $n = $n * 26 + ord($ch) - 64;
    return $n - 1;
}

function xlsx_read(string $path): array
{
    $files = zip_entries($path, ['xl/sharedStrings.xml', 'xl/workbook.xml', 'xl/_rels/workbook.xml.rels']);
    $sheetFile = null;
    // pick the first sheet listed in the workbook
    if (isset($files['xl/workbook.xml'], $files['xl/_rels/workbook.xml.rels'])) {
        $wb = @simplexml_load_string($files['xl/workbook.xml']);
        $rels = @simplexml_load_string($files['xl/_rels/workbook.xml.rels']);
        if ($wb && $rels && isset($wb->sheets->sheet[0])) {
            $rid = (string) $wb->sheets->sheet[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            foreach ($rels->Relationship as $rel) if ((string) $rel['Id'] === $rid) $sheetFile = 'xl/' . ltrim((string) $rel['Target'], '/');
        }
    }
    if (!$sheetFile || !isset($files[$sheetFile])) {
        $names = array_filter(array_keys($files), fn($n) => str_starts_with($n, 'xl/worksheets/'));
        sort($names);
        $sheetFile = $names[0] ?? null;
    }
    if (!$sheetFile) throw new RuntimeException('No worksheet found in the file.');

    $shared = [];
    if (!empty($files['xl/sharedStrings.xml'])) {
        $ss = @simplexml_load_string($files['xl/sharedStrings.xml']);
        if ($ss) foreach ($ss->si as $si) {
            $t = '';
            if (isset($si->t)) $t = (string) $si->t;
            else foreach ($si->r as $r) $t .= (string) $r->t;
            $shared[] = $t;
        }
    }
    $sheet = @simplexml_load_string($files[$sheetFile]);
    if (!$sheet) throw new RuntimeException('The worksheet could not be read.');
    $rows = [];
    foreach ($sheet->sheetData->row as $row) {
        $cells = [];
        foreach ($row->c as $c) {
            $i = xlsx_col_index((string) $c['r']);
            $t = (string) $c['t'];
            if ($t === 's') $v = $shared[(int) $c->v] ?? '';
            elseif ($t === 'inlineStr') { $v = ''; if (isset($c->is->t)) $v = (string) $c->is->t; else foreach ($c->is->r as $r) $v .= (string) $r->t; }
            else $v = (string) $c->v;
            $cells[$i] = trim($v);
        }
        if (!$cells) continue;
        $max = max(array_keys($cells));
        $line = [];
        for ($i = 0; $i <= $max; $i++) $line[] = $cells[$i] ?? '';
        $rows[] = $line;
        if (count($rows) > 20000) throw new RuntimeException('The file has more than 20,000 rows. Please split it.');
    }
    return $rows;
}

/** Excel stores dates as serial numbers (days since 1899-12-30); accept those and ordinary text dates. */
function import_date(string $v): ?string
{
    $v = trim($v);
    if ($v === '') return null;
    if (preg_match('/^\d{4,6}(\.\d+)?$/', $v) && (float) $v > 1000 && (float) $v < 80000) return gmdate('Y-m-d', (int) (((float) $v - 25569) * 86400));
    if (preg_match('#^(\d{1,2})[/.\-](\d{1,2})[/.\-](\d{4})$#', $v, $m)) return checkdate((int) $m[2], (int) $m[1], (int) $m[3]) ? sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]) : null; // day first
    $t = strtotime($v);
    return $t ? date('Y-m-d', $t) : null;
}
