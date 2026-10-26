<?php
/**
 * Table exports in CSV, Excel (.xlsx) and PDF with no external libraries.
 * Every admin export goes through export_send() so all lists offer the same three formats.
 */

function export_formats(): array { return ['csv' => 'CSV', 'xlsx' => 'Excel', 'pdf' => 'PDF']; }

/** Row of "Export: CSV | Excel | PDF" links for a list URL such as "inbox.php?t=messages". */
function export_buttons(string $base): string
{
    $sep = str_contains($base, '?') ? '&' : '?';
    $h = '<span class="exports no-print">⬇ Export:';
    foreach (export_formats() as $f => $label) $h .= ' <a class="btn sm ghost" href="' . e($base . $sep . 'export=' . $f) . '">' . $label . '</a>';
    return $h . '</span>';
}

function export_label(string $col): string { return ucwords(str_replace('_', ' ', $col)); }

/** Send the table as a download and stop. $rows is a list of lists in the same order as $headers. */
function export_send(string $format, string $title, array $headers, array $rows, string $basename): never
{
    $format = in_array($format, ['csv', 'xlsx', 'pdf'], true) ? $format : 'csv';
    $rows = array_map(fn($r) => array_map(fn($v) => is_scalar($v) ? (string) $v : '', array_values($r)), $rows);
    $file = trim(preg_replace('/[^A-Za-z0-9_-]+/', '-', $basename), '-') . '-' . date('Ymd');
    header('X-Content-Type-Options: nosniff');

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $file . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $headers);
        foreach ($rows as $r) fputcsv($out, array_map(fn($v) => preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v, $r));
        exit;
    }
    if ($format === 'xlsx') {
        $data = xlsx_build($title, $headers, $rows);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $file . '.xlsx"');
        header('Content-Length: ' . strlen($data));
        echo $data;
        exit;
    }
    $data = pdf_build($title, $headers, $rows);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $file . '.pdf"');
    header('Content-Length: ' . strlen($data));
    echo $data;
    exit;
}

// ---------------------------------------------------------------- XLSX
function xlsx_col(int $i): string
{
    $s = '';
    for ($i++; $i > 0; $i = intdiv($i - 1, 26)) $s = chr(65 + ($i - 1) % 26) . $s;
    return $s;
}
function xml_text(string $s): string
{
    return htmlspecialchars(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s), ENT_XML1 | ENT_QUOTES, 'UTF-8');
}
function xlsx_build(string $title, array $headers, array $rows): string
{
    $fill = 'FF' . strtoupper(ltrim(preg_match('/^#[0-9a-fA-F]{6}$/', setting('primary_color')) ? setting('primary_color') : '#0b4a24', '#'));
    $widths = [];
    foreach ($headers as $i => $h) $widths[$i] = mb_strlen($h);
    foreach ($rows as $r) foreach ($r as $i => $v) $widths[$i] = max($widths[$i] ?? 0, min(mb_strlen($v), 60));

    $cell = fn(int $c, int $r, string $v, int $style) => '<c r="' . xlsx_col($c) . $r . '" t="inlineStr"' . ($style ? ' s="' . $style . '"' : '') . '><is><t xml:space="preserve">' . xml_text($v) . '</t></is></c>';
    $sheetData = '<row r="1">';
    foreach ($headers as $c => $h) $sheetData .= $cell($c, 1, $h, 1);
    $sheetData .= '</row>';
    foreach ($rows as $ri => $r) {
        $sheetData .= '<row r="' . ($ri + 2) . '">';
        foreach ($r as $c => $v) $sheetData .= $cell($c, $ri + 2, $v, 0);
        $sheetData .= '</row>';
    }
    $cols = '<cols>';
    foreach ($headers as $i => $_) $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . min(60, max(10, $widths[$i] + 3)) . '" customWidth="1"/>';
    $cols .= '</cols>';

    $ns = 'xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"';
    $sheetName = substr(preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $title) ?: 'Export', 0, 31);
    $files = [
        '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
        '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
        'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook ' . $ns . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="' . xml_text($sheetName) . '" sheetId="1" r:id="rId1"/></sheets></workbook>',
        'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
        'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet ' . $ns . '><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="' . $fill . '"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>',
        'xl/worksheets/sheet1.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet ' . $ns . '><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>' . $cols . '<sheetData>' . $sheetData . '</sheetData></worksheet>',
    ];
    return zip_store($files);
}
/** Minimal ZIP writer (stored, no compression) so no zip extension is needed. */
function zip_store(array $files): string
{
    $out = ''; $cd = ''; $n = 0;
    $t = getdate();
    $dt = ($t['hours'] << 11) | ($t['minutes'] << 5) | intdiv($t['seconds'], 2);
    $dd = (($t['year'] - 1980) << 9) | ($t['mon'] << 5) | $t['mday'];
    foreach ($files as $name => $data) {
        $crc = crc32($data); $len = strlen($data); $off = strlen($out);
        $out .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, $dt, $dd, $crc, $len, $len, strlen($name), 0) . $name . $data;
        $cd .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, $dt, $dd, $crc, $len, $len, strlen($name), 0, 0, 0, 0, 0, $off) . $name;
        $n++;
    }
    return $out . $cd . pack('VvvvvVVv', 0x06054b50, 0, 0, $n, $n, strlen($cd), strlen($out), 0);
}

// ---------------------------------------------------------------- PDF
function pdf_build(string $title, array $headers, array $rows): string
{
    $W = 842; $H = 595; $M = 28; $fs = 7.5; $lead = 9.5; $maxLines = 5;
    $cp = fn($s) => (string) iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', (string) $s);
    $esc = fn($s) => str_replace(['\\', '(', ')', "\r"], ['\\\\', '\\(', '\\)', ''], $s);
    $hex = preg_match('/^#[0-9a-fA-F]{6}$/', setting('primary_color')) ? setting('primary_color') : '#0b4a24';
    [$cr, $cg, $cb] = array_map(fn($x) => round(hexdec($x) / 255, 3), str_split(ltrim($hex, '#'), 2));

    $n = max(1, count($headers));
    $weights = [];
    foreach ($headers as $i => $h) $weights[$i] = max(6, min(mb_strlen($h), 14));
    foreach ($rows as $r) foreach ($r as $i => $v) $weights[$i] = max($weights[$i] ?? 6, min(mb_strlen($v), 36));
    $usable = $W - 2 * $M;
    $sum = array_sum($weights) ?: 1;
    $cw = [];
    foreach ($weights as $i => $w) $cw[$i] = max(38, $usable * $w / $sum);
    $scale = $usable / array_sum($cw);
    foreach ($cw as $i => $w) $cw[$i] = $w * $scale;
    $chars = fn($i) => max(3, (int) floor(($cw[$i] - 6) / ($fs * 0.56)));
    $wrap = function ($text, $i) use ($cp, $chars, $maxLines) {
        $text = trim(preg_replace('/\s+/', ' ', $cp($text)));
        if ($text === '') return [''];
        $lines = explode("\n", wordwrap($text, $chars($i), "\n", true));
        if (count($lines) > $maxLines) { $lines = array_slice($lines, 0, $maxLines); $lines[$maxLines - 1] = rtrim(substr($lines[$maxLines - 1], 0, -3)) . '...'; }
        return $lines;
    };

    $pages = []; $cur = '';
    $meta = $cp(setting('school_name') . ' - generated ' . date('j M Y H:i') . ' - ' . count($rows) . ' record(s)');
    $top = function () use (&$cur, $W, $H, $M, $esc, $cp, $title, $meta, $headers, $cw, $fs, $lead, $cr, $cg, $cb, $wrap, $n) {
        $cur = "BT /F2 13 Tf $M " . ($H - $M - 6) . ' Td (' . $esc($cp($title)) . ") Tj ET\n";
        $cur .= "BT /F1 7.5 Tf 0.4 g $M " . ($H - $M - 20) . ' Td (' . $esc($meta) . ") Tj ET\n0 g\n";
        // header row
        $lines = 1; $cells = [];
        foreach ($headers as $i => $h) { $cells[$i] = $wrap($h, $i); $lines = max($lines, count($cells[$i])); }
        $h = $lines * $lead + 5; $y = $H - $M - 30; $x = $M;
        $cur .= "$cr $cg $cb rg $M " . ($y - $h) . ' ' . ($W - 2 * $M) . " $h re f\n1 g\n";
        foreach ($cells as $i => $ls) {
            foreach ($ls as $k => $l) $cur .= "BT /F2 $fs Tf " . ($x + 3) . ' ' . ($y - 9 - $k * $lead) . ' Td (' . $esc($l) . ") Tj ET\n";
            $x += $cw[$i];
        }
        $cur .= "0 g\n";
        return $y - $h;
    };
    $y = $top(); $zebra = false;
    foreach ($rows as $r) {
        $cells = []; $lines = 1;
        foreach ($r as $i => $v) { $cells[$i] = $wrap($v, $i); $lines = max($lines, count($cells[$i])); }
        $h = $lines * $lead + 4;
        if ($y - $h < $M + 16) { $pages[] = $cur; $y = $top(); $zebra = false; }
        if ($zebra) $cur .= "0.955 g $M " . ($y - $h) . ' ' . ($W - 2 * $M) . " $h re f\n0 g\n";
        $zebra = !$zebra;
        $x = $M;
        foreach ($cells as $i => $ls) {
            foreach ($ls as $k => $l) if ($l !== '') $cur .= "BT /F1 $fs Tf " . ($x + 3) . ' ' . ($y - 8 - $k * $lead) . ' Td (' . $esc($l) . ") Tj ET\n";
            $x += $cw[$i];
        }
        $y -= $h;
    }
    if (!$rows) $cur .= "BT /F1 9 Tf $M " . ($y - 16) . " Td (No records.) Tj ET\n";
    $pages[] = $cur;

    $total = count($pages);
    $objs = [1 => '<< /Type /Catalog /Pages 2 0 R >>', 3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>', 4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>'];
    $kids = [];
    foreach ($pages as $p => $content) {
        $content .= "BT /F1 7 Tf 0.4 g " . ($W - $M - 60) . ' ' . ($M - 6) . ' Td (Page ' . ($p + 1) . ' of ' . $total . ") Tj ET\n";
        $pid = 5 + $p * 2; $cid = $pid + 1;
        $objs[$pid] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 $W $H] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents $cid 0 R >>";
        $objs[$cid] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream";
        $kids[] = "$pid 0 R";
    }
    $objs[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $total . ' >>';
    ksort($objs);
    $pdf = "%PDF-1.4\n"; $offsets = [];
    foreach ($objs as $num => $body) { $offsets[$num] = strlen($pdf); $pdf .= "$num 0 obj\n$body\nendobj\n"; }
    $xref = strlen($pdf); $max = max(array_keys($objs)) + 1;
    $pdf .= "xref\n0 $max\n0000000000 65535 f \n";
    for ($i = 1; $i < $max; $i++) $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
    return $pdf . "trailer\n<< /Size $max /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
}
