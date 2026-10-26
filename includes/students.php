<?php
/** Student database helpers: registration numbers, filters, class permissions, change log. */

const STUDENT_STATUSES = ['present' => 'Present', 'dismissed' => 'Dismissed', 'transferred' => 'Transferred out', 'completed' => 'Completed'];

function stream_label(array $c): string { return trim($c['class_name'] . ' ' . ($c['stream_name'] ?? '')); }

/** All active classes/streams, ordered, each with a printable 'label'. */
function class_streams(bool $onlyActive = true): array
{
    $rows = rows('SELECT * FROM class_streams' . ($onlyActive ? ' WHERE active = 1' : '') . ' ORDER BY sort, class_name, stream_name');
    foreach ($rows as &$r) $r['label'] = stream_label($r);
    return $rows;
}

/** Streams the signed-in user may add/edit students in. Class teachers: only their own. Others with manage rights: all. */
function manageable_streams(): array
{
    $u = admin_user();
    if (!$u || !can('students_manage')) return [];
    $all = class_streams();
    if ($u['role'] !== 'class_teacher') return $all;
    return array_values(array_filter($all, fn($c) => (int) $c['teacher_id'] === (int) $u['id']));
}
function can_manage_student(?int $classStreamId): bool
{
    $u = admin_user();
    if (!$u || !can('students_manage')) return false;
    if ($u['role'] !== 'class_teacher') return true;
    return $classStreamId !== null && in_array($classStreamId, array_map(fn($c) => (int) $c['id'], manageable_streams()), true);
}

/** Next registration number, e.g. AFSS/2026/0042. Unique per year; the UNIQUE index backs this up. */
function next_reg_no(): array
{
    $prefix = preg_replace('/[^A-Za-z0-9]/', '', setting('reg_prefix', 'AFSS')) ?: 'AFSS';
    $year = (int) date('Y');
    $seq = (int) val('SELECT COALESCE(MAX(reg_seq), 0) FROM students WHERE reg_year = ?', [$year]) + 1;
    return [sprintf('%s/%d/%04d', $prefix, $year, $seq), $year, $seq];
}

function student_log(int $studentId, string $action, string $detail = ''): void
{
    q('INSERT INTO student_log (student_id,user_name,action,detail) VALUES (?,?,?,?)', [$studentId, admin_user()['name'] ?? 'system', $action, $detail]);
}

/** Insert a student with an automatic registration number. Returns [id, reg_no]. */
function add_student(array $d, ?int $classStreamId, string $source = 'manual'): array
{
    for ($try = 0; $try < 6; $try++) {
        [$reg, $year, $seq] = next_reg_no();
        try {
            q('INSERT INTO students (reg_no,reg_year,reg_seq,first_name,last_name,other_names,gender,dob,district,class_stream_id,guardian_name,guardian_phone,guardian_email,external_id,application_ref,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [$reg, $year, $seq, $d['first_name'], $d['last_name'] ?? '', $d['other_names'] ?? '', $d['gender'] ?? '', $d['dob'] ?? null, $d['district'] ?? '', $classStreamId,
                    $d['guardian_name'] ?? '', $d['guardian_phone'] ?? '', $d['guardian_email'] ?? '', $d['external_id'] ?? '', $d['application_ref'] ?? '', admin_user()['id'] ?? null]);
            $id = (int) db()->lastInsertId();
            student_log($id, 'created', "Registered as $reg ($source)");
            return [$id, $reg];
        } catch (PDOException $ex) {
            if (!str_contains($ex->getMessage(), 'UNIQUE')) throw $ex; // someone else took this number: try the next one
        }
    }
    throw new RuntimeException('Could not allocate a registration number. Please try again.');
}

/** Build WHERE + params from request filters (q, cs, district, status, gender). */
function student_filters(array $g, bool $defaultPresent = true): array
{
    $w = []; $p = [];
    $q = trim((string) ($g['q'] ?? ''));
    foreach (array_filter(preg_split('/\s+/', $q)) as $tok) {
        $w[] = '(s.first_name LIKE ? OR s.last_name LIKE ? OR s.other_names LIKE ? OR s.reg_no LIKE ? OR s.external_id LIKE ? OR s.guardian_name LIKE ? OR s.guardian_phone LIKE ?)';
        for ($i = 0; $i < 7; $i++) $p[] = "%$tok%";
    }
    if (!empty($g['cs'])) {
        if ($g['cs'] === 'none') $w[] = 's.class_stream_id IS NULL';
        elseif (str_starts_with((string) $g['cs'], 'c:')) { $w[] = 'c.class_name = ?'; $p[] = substr($g['cs'], 2); }   // whole class, all streams
        else { $w[] = 's.class_stream_id = ?'; $p[] = (int) $g['cs']; }
    }
    if (!empty($g['class'])) { $w[] = 'c.class_name = ? COLLATE NOCASE'; $p[] = (string) $g['class']; }
    if (isset($g['stream']) && $g['stream'] !== '') { $w[] = 'c.stream_name = ? COLLATE NOCASE'; $p[] = (string) $g['stream']; }
    if (!empty($g['district'])) { $w[] = 's.district = ?'; $p[] = $g['district']; }
    if (!empty($g['gender']) && in_array($g['gender'], ['Male', 'Female'], true)) { $w[] = 's.gender = ?'; $p[] = $g['gender']; }
    $st = $g['status'] ?? ($defaultPresent ? 'present' : '');
    if ($st !== '' && $st !== 'all' && isset(STUDENT_STATUSES[$st])) { $w[] = 's.status = ?'; $p[] = $st; }
    return [$w ? ' WHERE ' . implode(' AND ', $w) : '', $p];
}

const STUDENT_SELECT = 'SELECT s.*, c.class_name, c.stream_name FROM students s LEFT JOIN class_streams c ON c.id = s.class_stream_id';

function student_name(array $s): string { return trim(($s['last_name'] ?? '') . ' ' . ($s['first_name'] ?? '') . ' ' . ($s['other_names'] ?? '')); }

/** Columns for exports and the API. $guardian adds guardian contact details. */
function student_export_table(array $students, bool $guardian = true): array
{
    $h = ['Reg No', 'Surname', 'First name', 'Other names', 'Gender', 'Date of birth', 'District', 'Class', 'Stream', 'Status', 'Status note', 'LIN / ID'];
    if ($guardian) array_push($h, 'Guardian', 'Guardian phone', 'Guardian email');
    $h[] = 'Registered';
    $rows = [];
    foreach ($students as $s) {
        $r = [$s['reg_no'], $s['last_name'], $s['first_name'], $s['other_names'], $s['gender'], $s['dob'] ? fmt_date($s['dob'], 'Y-m-d') : '', $s['district'], $s['class_name'] ?? '', $s['stream_name'] ?? '',
            STUDENT_STATUSES[$s['status']] ?? $s['status'], $s['status_reason'], $s['external_id']];
        if ($guardian) array_push($r, $s['guardian_name'], $s['guardian_phone'], $s['guardian_email']);
        $r[] = substr((string) $s['created_at'], 0, 10);
        $rows[] = $r;
    }
    return [$h, $rows];
}

/** Map spreadsheet header text to our fields. Case/spacing-insensitive, several common names each. */
function student_import_map(array $header): array
{
    $syn = [
        'last_name' => ['surname', 'lastname', 'last name', 'family name'],
        'first_name' => ['first name', 'firstname', 'given name', 'given names', 'forename'],
        'other_names' => ['other names', 'other name', 'middle name', 'middle names', 'othernames'],
        'full_name' => ['name', 'full name', 'fullname', 'student name', 'learner name', 'names'],
        'gender' => ['gender', 'sex'],
        'dob' => ['date of birth', 'dob', 'birth date', 'birthdate', 'd.o.b', 'date of birth (dd/mm/yyyy)'],
        'district' => ['district', 'home district', 'district of origin'],
        'guardian_name' => ['guardian', 'guardian name', 'parent', 'parent name', 'parent/guardian', 'parent / guardian', 'next of kin'],
        'guardian_phone' => ['guardian phone', 'phone', 'parent phone', 'contact', 'telephone', 'tel', 'phone number', 'guardian contact', 'parent contact', 'guardian telephone'],
        'guardian_email' => ['guardian email', 'email', 'parent email', 'e-mail'],
        'external_id' => ['lin', 'lin / id number', 'lin/id number', 'id number', 'learner id', 'uneb index', 'index number', 'learner identification number'],
    ];
    $map = [];
    foreach ($header as $i => $h) {
        $k = preg_replace('/\s+/', ' ', mb_strtolower(trim((string) $h)));
        foreach ($syn as $field => $names) if (in_array($k, $names, true) && !isset($map[$field])) { $map[$field] = $i; break; }
    }
    return $map;
}

/** Turn one spreadsheet row into a student array, or an error string. Returns [data|null, errors[], warnings[]]. */
function student_import_row(array $row, array $map): array
{
    $get = fn($f) => isset($map[$f]) ? trim((string) ($row[$map[$f]] ?? '')) : '';
    $d = ['last_name' => $get('last_name'), 'first_name' => $get('first_name'), 'other_names' => $get('other_names')];
    if ($d['first_name'] === '' && $d['last_name'] === '' && $get('full_name') !== '') {
        $parts = preg_split('/\s+/', $get('full_name'));          // convention: "Surname Firstname [Other names]"
        $d['last_name'] = array_shift($parts);
        $d['first_name'] = (string) array_shift($parts);
        $d['other_names'] = implode(' ', $parts);
    }
    $err = []; $warn = [];
    if ($d['first_name'] === '' && $d['last_name'] === '') return [null, ['no name'], []];
    if ($d['first_name'] === '') { $d['first_name'] = $d['last_name']; $d['last_name'] = ''; $warn[] = 'only one name given'; }
    $g = mb_strtolower($get('gender'));
    $d['gender'] = in_array($g, ['m', 'male', 'boy', 'b'], true) ? 'Male' : (in_array($g, ['f', 'female', 'girl', 'g'], true) ? 'Female' : '');
    if ($get('gender') !== '' && $d['gender'] === '') $warn[] = 'gender "' . $get('gender') . '" not recognised';
    $d['dob'] = import_date($get('dob'));
    if ($get('dob') !== '' && !$d['dob']) $warn[] = 'date of birth "' . $get('dob') . '" not understood';
    $dist = match_district($get('district'));
    $d['district'] = $dist ?? $get('district');
    if ($get('district') !== '' && !$dist) $warn[] = 'district "' . $get('district') . '" is not a known Uganda district';
    foreach (['guardian_name', 'guardian_phone', 'guardian_email', 'external_id'] as $f) $d[$f] = $get($f);
    return [$d, $err, $warn];
}

function student_exists(array $d): bool
{
    if (($d['external_id'] ?? '') !== '' && val('SELECT COUNT(*) FROM students WHERE external_id = ?', [$d['external_id']])) return true;
    return (bool) val('SELECT COUNT(*) FROM students WHERE lower(first_name) = lower(?) AND lower(COALESCE(last_name,\'\')) = lower(?) AND COALESCE(dob,\'\') = COALESCE(?,\'\')', [$d['first_name'], $d['last_name'] ?? '', $d['dob']]);
}
