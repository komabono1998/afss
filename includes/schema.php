<?php
// Database schema + first-run seed content. Executed only when the database file does not exist yet.

function install_schema(PDO $db): void
{
    $db->exec("
    CREATE TABLE settings (k TEXT PRIMARY KEY, v TEXT);
    CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL, role TEXT DEFAULT 'admin', active INTEGER DEFAULT 1, created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE slides (
        id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, subtitle TEXT, image TEXT,
        link_text TEXT, link_url TEXT, sort INTEGER DEFAULT 0, active INTEGER DEFAULT 1
    );
    CREATE TABLE pages (
        id INTEGER PRIMARY KEY AUTOINCREMENT, slug TEXT UNIQUE NOT NULL, title TEXT NOT NULL,
        summary TEXT, body TEXT, image TEXT, menu_group TEXT DEFAULT 'none', sort INTEGER DEFAULT 0,
        active INTEGER DEFAULT 1
    );
    CREATE TABLE news (
        id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, slug TEXT UNIQUE NOT NULL,
        category TEXT DEFAULT 'News', excerpt TEXT, body TEXT, image TEXT,
        published_at TEXT, status TEXT DEFAULT 'published', featured INTEGER DEFAULT 0
    );
    CREATE TABLE events (
        id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, category TEXT DEFAULT 'School',
        start_date TEXT NOT NULL, end_date TEXT, time_text TEXT, location TEXT, description TEXT, image TEXT
    );
    CREATE TABLE jobs (
        id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, slug TEXT UNIQUE NOT NULL,
        department TEXT, type TEXT DEFAULT 'Full-time', location TEXT, description TEXT,
        requirements TEXT, deadline TEXT, status TEXT DEFAULT 'open', created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE team (
        id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, role TEXT, grp TEXT DEFAULT 'administration',
        bio TEXT, photo TEXT, sort INTEGER DEFAULT 0, email TEXT, phone TEXT, term TEXT
    );
    CREATE TABLE scholarships (
        id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, slug TEXT UNIQUE NOT NULL, type TEXT DEFAULT 'Bursary',
        summary TEXT, coverage TEXT, sponsor TEXT, eligibility TEXT, how_to_apply TEXT, documents TEXT, deadline TEXT,
        accepting INTEGER DEFAULT 1, sort INTEGER DEFAULT 0, active INTEGER DEFAULT 1
    );
    CREATE TABLE scholarship_apps (
        id INTEGER PRIMARY KEY AUTOINCREMENT, scholarship_id INTEGER, scholarship_title TEXT, name TEXT, current_class TEXT,
        school TEXT, guardian_name TEXT, phone TEXT, email TEXT, statement TEXT, supporting_file TEXT,
        status TEXT DEFAULT 'new', created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE newsletter_issues (
        id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, slug TEXT UNIQUE NOT NULL, issue_date TEXT NOT NULL, audience TEXT DEFAULT 'school',
        edition TEXT, summary TEXT, intro TEXT, cover TEXT, pdf TEXT, status TEXT DEFAULT 'draft', sent_at TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE newsletter_sections (
        id INTEGER PRIMARY KEY AUTOINCREMENT, issue_id INTEGER NOT NULL, heading TEXT, body TEXT, image TEXT, caption TEXT,
        layout TEXT DEFAULT 'image-right', sort INTEGER DEFAULT 0
    );
    CREATE TABLE alumni_events (
        id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, slug TEXT UNIQUE NOT NULL, category TEXT DEFAULT 'Event',
        event_date TEXT, end_date TEXT, time_text TEXT, venue TEXT, summary TEXT, description TEXT, image TEXT, contact_info TEXT,
        sort INTEGER DEFAULT 0, active INTEGER DEFAULT 1
    );
    CREATE TABLE alumni_members (
        id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT, phone TEXT, year_left TEXT, last_class TEXT, occupation TEXT,
        location TEXT, message TEXT, newsletter INTEGER DEFAULT 0, token TEXT, status TEXT DEFAULT 'new', created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        profession TEXT, workplace TEXT, photo TEXT, proof_file TEXT, consent INTEGER DEFAULT 0, consent_at TEXT
    );
    CREATE TABLE albums (
        id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, slug TEXT UNIQUE NOT NULL, category TEXT DEFAULT 'General',
        event_date TEXT, term TEXT, description TEXT, cover TEXT, active INTEGER DEFAULT 1
    );
    CREATE TABLE gallery (
        id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, category TEXT DEFAULT 'Campus', image TEXT NOT NULL, album_id INTEGER,
        sort INTEGER DEFAULT 0
    );
    CREATE TABLE admissions_apps (
        id INTEGER PRIMARY KEY AUTOINCREMENT, ref TEXT, first_name TEXT, last_name TEXT, gender TEXT, dob TEXT,
        nationality TEXT, level_applied TEXT, entry_class TEXT, combination TEXT, previous_school TEXT,
        index_number TEXT, aggregates TEXT, boarding TEXT, guardian_name TEXT, guardian_relation TEXT,
        guardian_phone TEXT, guardian_email TEXT, address TEXT, notes TEXT,
        admission_type TEXT, transfer_reason TEXT, results_file TEXT, recommendation_file TEXT,
        status TEXT DEFAULT 'new', created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE job_apps (
        id INTEGER PRIMARY KEY AUTOINCREMENT, job_id INTEGER, job_title TEXT, name TEXT, email TEXT, phone TEXT,
        qualification TEXT, cover_letter TEXT, cv_file TEXT, status TEXT DEFAULT 'new',
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT, phone TEXT, subject TEXT, message TEXT,
        status TEXT DEFAULT 'new', created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE subscribers (
        id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT UNIQUE NOT NULL, token TEXT, active INTEGER DEFAULT 1,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE newsletters (
        id INTEGER PRIMARY KEY AUTOINCREMENT, subject TEXT, body TEXT, recipients INTEGER DEFAULT 0,
        sent_ok INTEGER DEFAULT 0, sent_at TEXT DEFAULT CURRENT_TIMESTAMP
    );
    ");
}

function seed_content(PDO $db): void
{
    $set = $db->prepare('INSERT INTO settings (k,v) VALUES (?,?)');
    $defaults = [
        'school_name' => 'Archbishop Flynn Secondary School',
        'short_name' => 'AFSS',
        'tagline' => 'Sapientia et Servitium',
        'intro' => 'A co-educational boarding school for O and A level learners in Pader, northern Uganda, forming young people in wisdom, service and strong moral character.',
        'address' => '2 km from Pader town along Latanya Road, Pader, Uganda',
        'phone' => '+256 392 849 237',
        'phone2' => '+256 772 587 683',
        'email' => 'info@afss.ac.ug',
        'notify_email' => 'info@afss.ac.ug',
        'facebook' => '', 'twitter' => '', 'instagram' => '', 'youtube' => '', 'whatsapp' => '',
        'map_embed' => 'https://www.openstreetmap.org/export/embed.html?bbox=32.85%2C2.78%2C32.95%2C2.88&layer=mapnik',
        'primary_color' => '#0b4a24',
        'secondary_color' => '#d7141a',
        'accent_color' => '#f2b705',
        'logo' => '',
        'announcement' => 'Admissions are open — apply online today.',
        'admissions_open' => '1',
        'intake_text' => 'Next intake: Term I (February). Applications are reviewed on a rolling basis.',
        'stat_students' => '1,200+', 'stat_teachers' => '60+', 'stat_years' => '20+', 'stat_firstgrade' => '78',
        'stat_students_l' => 'Learners', 'stat_teachers_l' => 'Dedicated Staff',
        'stat_years_l' => 'Years of Service', 'stat_firstgrade_l' => 'First Grades in UCE 2023',
        'moes_reg' => 'PSS/A/209', 'licence' => 'ME/22/4831', 'uneb_centre' => 'U2848',
        'mission' => 'To promote equal opportunities for boys and girls, academic excellence, and human and moral formation, laying a strong and reliable academic, human and moral foundation for adult life.',
        'vision' => 'A centre of excellence that nurtures wise, responsible and service-minded citizens.',
        'gallery_categories' => "Sports Day
Music, Dance & Drama (MDD) Day
Visitation Day
Graduation
Religious Celebrations
Academic Events
Clubs & Societies
Campus Life",
        'footer_text' => 'Archbishop Flynn Secondary School — Sapientia et Servitium.',
    ];
    foreach ($defaults as $k => $v) $set->execute([$k, $v]);
    foreach (['scholarships_intro', 'scholarships_apply'] as $k) $set->execute([$k, scholarship_default($k)]);
    seed_scholarships($db);
    seed_alumni($db, true);

    $slide = $db->prepare('INSERT INTO slides (title,subtitle,link_text,link_url,sort) VALUES (?,?,?,?,?)');
    $slide->execute(['Welcome to Archbishop Flynn Secondary School', 'Wisdom and Service — educating the head, heart and hands in Pader, Uganda.', 'Apply for Admission', 'apply.php', 1]);
    $slide->execute(['Academic Excellence, Moral Formation', 'O and A level education in a safe, caring boarding community.', 'Explore Academics', 'page.php?slug=academics', 2]);
    $slide->execute(['Join Our Community', 'Bursaries available for academic improvement, sports, discipline, disability and orphans.', 'Admission Details', 'admissions.php', 3]);

    $page = $db->prepare('INSERT INTO pages (slug,title,summary,body,menu_group,sort) VALUES (?,?,?,?,?,?)');
    $pages = [
        ['about-us', 'About Us', 'Who we are, what we believe and where we are going.',
         '<p>Archbishop Flynn Secondary School (AFSS) is a co-educational boarding school offering Ordinary (O) and Advanced (A) level education, located two kilometres from Pader town along Latanya Road in northern Uganda. The school is registered with the Ministry of Education and Sports (PSS/A/209), licensed (ME/22/4831) and operates as UNEB centre U2848.</p><h2>Our Mission</h2><p>We promote equal opportunities for boys and girls, academic excellence, and human and moral formation, so that every learner leaves with a strong and reliable academic, human and moral foundation for adult life.</p><h2>Our Motto</h2><p><strong>Sapientia et Servitium</strong> — Wisdom and Service.</p><h2>Our Values</h2><ul><li>Faith and integrity</li><li>Academic excellence</li><li>Respect, equality and inclusion</li><li>Service to community</li><li>Discipline and responsibility</li></ul>', 'about', 1],
        ['administrators-message', "Administrator's Message", 'A word from the school administrator.', '<p>Welcome message to be updated from the admin panel (Content → Pages).</p>', 'about', 3],
        ['headteachers-message', "Head Teacher's Message", 'A word from the Head Teacher.', '<p>Welcome message to be updated from the admin panel (Content → Pages).</p>', 'about', 4],
        ['teaching-and-learning', 'Teaching and Learning', 'How we teach and how our learners grow.', '<p>Our dedicated, qualified teachers cover every subject of the national curriculum, and we invest heavily in mentoring teachers so that learners receive the best guidance. Reading is at the heart of our approach: learners at every class level are taught basic reading skills and supported through the school library.</p>', 'about', 5],
        ['partnerships', 'Partnerships and Collaborations', 'Working with partners to serve learners.', '<p>AFSS works with the Archdiocese, development partners, alumni and local communities. Partnership details to be updated from the admin panel.</p>', 'about', 6],
        ['academics', 'Academics', 'Academic affairs at AFSS.', '<p>AFSS offers the Uganda Certificate of Education (UCE) at O level and the Uganda Advanced Certificate of Education (UACE) at A level, delivered by qualified teachers across the sciences, humanities, languages, business and vocational studies.</p>', 'academics', 1],
        ['academic-programmes', 'Academic Programmes', 'O-level and A-level programmes and subject combinations.', '<h2>O Level (S.1 – S.4)</h2><p>Learners follow the national lower secondary curriculum leading to UCE.</p><h2>A Level (S.5 – S.6)</h2><p>Subject combinations in sciences, arts and business leading to UACE. Full list of combinations to be updated from the admin panel.</p>', 'academics', 2],
        ['departments', 'Departments', 'Our academic departments.', '<ul><li>Physical Sciences</li><li>Biological Sciences</li><li>Business Studies</li><li>Vocational Studies</li><li>Humanities</li><li>Languages</li></ul>', 'academics', 3],
        ['library-services', 'Library Services', 'Books, e-resources and reading culture.', '<p>The AFSS library supports learning with textbooks, readers and e-resources, and promotes a strong reading culture across all class levels.</p>', 'academics', 4],
        ['e-learning', 'e-Learning Platform', 'Learn anywhere with our e-campus.', '<p>The AFSS e-campus extends learning beyond the classroom. Details to be updated from the admin panel.</p>', 'academics', 5],
        ['admission-requirements', 'Minimum Entry Requirements', 'What you need to join AFSS.', '<h2>S.1 Entry</h2><p>Primary Leaving Examination (PLE) results slip and completed application form.</p><h2>S.5 Entry</h2><p>UCE results slip showing the minimum requirements for the chosen combination.</p><h2>Transfers</h2><p>Latest report card, transfer letter and a recommendation from the previous school.</p><p><em>Please confirm exact requirements with the school office; this text can be edited in the admin panel.</em></p>', 'admissions', 1],
        ['tuition-and-fees', 'Tuition and Fees Payment', 'Fees structure and payment options.', '<p>The fees structure and payment channels are published here. Bursaries are available for academic improvement, sports, discipline, disability and orphans.</p><p>Contact the school bursar for the current fees structure.</p>', 'admissions', 2],
        ['how-to-apply', 'How to Apply', 'Simple steps to apply.', '<ol><li>Complete the online application form.</li><li>Submit copies of your results slip and a recent passport photograph at the school office.</li><li>Receive your admission decision by phone or email.</li><li>Report on the stated date with the required items.</li></ol>', 'admissions', 3],
        ['clubs', 'School Clubs and Societies', 'Prefects, Interact, Debating, Patriotism, Writers and Scouting.', '<ul><li>Prefects Body</li><li>Interact Club</li><li>Debating Club</li><li>Patriotism Club</li><li>Writers Club</li><li>Scouting</li></ul>', 'students', 1],
        ['health-services', 'Health Services', 'Caring for learners’ wellbeing.', '<p>The school health unit provides first aid and basic care, and refers learners to nearby health facilities when necessary.</p>', 'students', 2],
        ['counselling', 'Counselling and Career Centre', 'Guidance for learning, life and careers.', '<p>The Student Counselling and Career Centre offers guidance and support for personal growth and career choices.</p>', 'students', 3],
        ['alumni', 'AFSS Alumni', 'Staying connected after graduation.', '<p>Alumni are an important part of the AFSS family. Reach out through the contact page to join the alumni network.</p>', 'students', 4],
        ['privacy', 'Privacy Policy', 'How we handle your data.', '<p>Information submitted through this website (applications, contact messages, newsletter sign-ups) is used only for school administration and communication and is not sold or shared with third parties.</p>', 'none', 99],
    ];
    foreach ($pages as $p) $page->execute($p);

    $team = $db->prepare('INSERT INTO team (name,role,grp,bio,sort) VALUES (?,?,?,?,?)');
    $rows = [
        ['Name to be updated', 'Chairperson, Board of Governors', 'board', 1],
        ['Name to be updated', 'Vice Chairperson', 'board', 2],
        ['Name to be updated', 'Foundation Body Representative', 'board', 3],
        ['Name to be updated', 'Parents’ Representative', 'board', 4],
        ['Name to be updated', 'Director / Administrator', 'director', 1],
        ['Name to be updated', 'Head Teacher', 'administration', 1],
        ['Name to be updated', 'Deputy Head Teacher (Academics)', 'administration', 2],
        ['Name to be updated', 'Deputy Head Teacher (Administration)', 'administration', 3],
        ['Name to be updated', 'PTA Chairperson', 'pta', 1],
        ['Name to be updated', 'PTA Vice Chairperson', 'pta', 2],
        ['Name to be updated', 'PTA Secretary', 'pta', 3],
        ['Name to be updated', 'PTA Treasurer', 'pta', 4],
        ['Name to be updated', 'PTA Committee Member', 'pta', 5],
        ['Name to be updated', 'PTA Committee Member', 'pta', 6],
        ['Name to be updated', 'Director of Studies', 'administration', 4],
        ['Name to be updated', 'Bursar', 'administration', 5],
    ];
    foreach ($rows as $r) $team->execute([$r[0], $r[1], $r[2], '', $r[3]]);

    $news = $db->prepare('INSERT INTO news (title,slug,category,excerpt,body,published_at,status,featured) VALUES (?,?,?,?,?,?,?,?)');
    $news->execute(['UCE 2024 Results', 'uce-2024-results', 'Academics',
        'A summary of the previous year’s Uganda Certificate of Education results, including 78 learners in the first grade.',
        '<p>Archbishop Flynn Secondary School celebrates the performance of its UCE candidates, with 78 learners attaining first grade. We congratulate the learners, parents and teachers for their hard work.</p><p><em>Edit this article in the admin panel to add the full results summary.</em></p>', '2024-02-17', 'published', 1]);
    $news->execute(['Prefects Election Result 2023', 'prefects-election-result-2023', 'Student Life',
        'The school announces the newly elected prefects body for the year.',
        '<p>Following a transparent election process, the new Prefects Body was announced, led by a Senior Five learner as Head Prefect. We wish them a fruitful term of service.</p>', '2023-07-08', 'published', 0]);
    $news->execute(['Archbishop John Baptist Odama', 'archbishop-john-baptist-odama', 'Stories',
        'Celebrating the patron saint of Archbishop Harry Flynn: Saint Joseph.',
        '<p>The school community celebrated Saint Joseph, patron of the school, in a Mass presided over by the Archbishop.</p>', '2023-06-24', 'published', 0]);
    $news->execute(['Lamaro Fortunate Lusaka: UN Ambassador for a Day', 'lamaro-fortunate-lusaka', 'Stories',
        'A Senior Three learner served as a United Nations Ambassador for a day.',
        '<p>Our learner Lamaro Fortunate Lusaka, a Senior Three student, represented young people as a United Nations Ambassador for a day — a proud moment for the entire AFSS family.</p>', '2023-06-24', 'published', 0]);

    $ev = $db->prepare('INSERT INTO events (title,category,start_date,end_date,time_text,location,description) VALUES (?,?,?,?,?,?,?)');
    $y = (int) date('Y');
    $ev->execute(['Open Day & Campus Tour', 'Admissions', date('Y-m-d', strtotime('+10 days')), null, '9:00 AM – 1:00 PM', 'AFSS Campus', 'Meet our teachers, tour the campus and learn about admissions. (Sample event — edit in admin)']);
    $ev->execute(['End of Term Examinations', 'Academic', date('Y-m-d', strtotime('+21 days')), date('Y-m-d', strtotime('+30 days')), '', 'AFSS Campus', 'End of term examinations for all classes. (Sample event — edit in admin)']);
    $ev->execute(['Parents & Guardians Day', 'School', date('Y-m-d', strtotime('+35 days')), null, '10:00 AM', 'AFSS Main Hall', 'Meet teachers and review your child’s progress. (Sample event — edit in admin)']);
    $ev->execute(['Term Closing Date', 'Calendar', date('Y-m-d', strtotime('+40 days')), null, '', 'AFSS Campus', 'Learners leave for the holidays. (Sample event — edit in admin)']);

    $job = $db->prepare('INSERT INTO jobs (title,slug,department,type,location,description,requirements,deadline,status) VALUES (?,?,?,?,?,?,?,?,?)');
    $job->execute(['General Application — Teaching and Support Staff', 'general-application', 'All Departments', 'Various', 'Pader, Uganda',
        '<p>AFSS is always interested in meeting committed educators and support staff. Submit your application and CV and we will contact you when a suitable vacancy arises.</p>',
        '<ul><li>Relevant academic or professional qualification</li><li>Commitment to the mission and values of the school</li><li>Willingness to live and work in a boarding community</li></ul>', null, 'open']);
}

/** Upgrades for databases created by earlier versions. Safe to run on every request. */
function migrate(PDO $db): void
{
    $cols = array_column($db->query('PRAGMA table_info(team)')->fetchAll(PDO::FETCH_ASSOC), 'name');
    foreach (['email', 'phone', 'term'] as $c) if (!in_array($c, $cols, true)) $db->exec("ALTER TABLE team ADD COLUMN $c TEXT");
    $db->exec("CREATE TABLE IF NOT EXISTS albums (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, slug TEXT UNIQUE NOT NULL, category TEXT DEFAULT 'General', event_date TEXT, term TEXT, description TEXT, cover TEXT, active INTEGER DEFAULT 1)");
    $gc = array_column($db->query('PRAGMA table_info(gallery)')->fetchAll(PDO::FETCH_ASSOC), 'name');
    if (!in_array('album_id', $gc, true)) $db->exec('ALTER TABLE gallery ADD COLUMN album_id INTEGER');
    if (!$db->query("SELECT 1 FROM settings WHERE k = 'gallery_categories'")->fetchColumn()) $db->exec("INSERT INTO settings (k,v) VALUES ('gallery_categories', 'Sports Day'||char(10)||'Music, Dance & Drama (MDD) Day'||char(10)||'Visitation Day'||char(10)||'Graduation'||char(10)||'Religious Celebrations'||char(10)||'Academic Events'||char(10)||'Clubs & Societies'||char(10)||'Campus Life')");
    foreach ($db->query("SELECT DISTINCT category FROM gallery WHERE album_id IS NULL")->fetchAll(PDO::FETCH_COLUMN) as $cat) {
        $cat = $cat ?: 'Campus Life';
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $cat), '-')) . '-' . substr(md5($cat), 0, 4);
        $db->prepare("INSERT OR IGNORE INTO albums (title,slug,category,event_date) VALUES (?,?,?,date('now'))")->execute([$cat, $slug, $cat]);
        $aid = $db->query("SELECT id FROM albums WHERE slug = " . $db->quote($slug))->fetchColumn();
        $db->prepare("UPDATE gallery SET album_id = ? WHERE album_id IS NULL AND COALESCE(category,'Campus Life') = ?")->execute([$aid, $cat]);
    }
    $db->exec("CREATE TABLE IF NOT EXISTS scholarships (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, slug TEXT UNIQUE NOT NULL, type TEXT DEFAULT 'Bursary', summary TEXT, coverage TEXT, sponsor TEXT, eligibility TEXT, how_to_apply TEXT, documents TEXT, deadline TEXT, accepting INTEGER DEFAULT 1, sort INTEGER DEFAULT 0, active INTEGER DEFAULT 1)");
    $db->exec("CREATE TABLE IF NOT EXISTS scholarship_apps (id INTEGER PRIMARY KEY AUTOINCREMENT, scholarship_id INTEGER, scholarship_title TEXT, name TEXT, current_class TEXT, school TEXT, guardian_name TEXT, phone TEXT, email TEXT, statement TEXT, supporting_file TEXT, status TEXT DEFAULT 'new', created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
    if (!$db->query("SELECT 1 FROM settings WHERE k = 'migr_scholarships'")->fetchColumn()) {
        if (!$db->query('SELECT 1 FROM scholarships LIMIT 1')->fetchColumn()) seed_scholarships($db);
        $db->exec("UPDATE pages SET active = 0 WHERE slug = 'scholarships'");
        $ins = $db->prepare('INSERT OR IGNORE INTO settings (k,v) VALUES (?,?)');
        foreach (['scholarships_intro', 'scholarships_apply'] as $k) $ins->execute([$k, scholarship_default($k)]);
        $db->exec("INSERT OR REPLACE INTO settings (k,v) VALUES ('migr_scholarships','1')");
    }
    $db->exec("CREATE TABLE IF NOT EXISTS newsletter_issues ( id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, slug TEXT UNIQUE NOT NULL, issue_date TEXT NOT NULL, edition TEXT, summary TEXT, intro TEXT, cover TEXT, pdf TEXT, status TEXT DEFAULT 'draft', sent_at TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP ); CREATE TABLE IF NOT EXISTS newsletter_sections ( id INTEGER PRIMARY KEY AUTOINCREMENT, issue_id INTEGER NOT NULL, heading TEXT, body TEXT, image TEXT, caption TEXT, layout TEXT DEFAULT 'image-right', sort INTEGER DEFAULT 0 );");
    $uc = array_column($db->query('PRAGMA table_info(users)')->fetchAll(PDO::FETCH_ASSOC), 'name');
    if (!in_array('active', $uc, true)) $db->exec('ALTER TABLE users ADD COLUMN active INTEGER DEFAULT 1');
    if (!$db->query("SELECT 1 FROM settings WHERE k = 'migr_theme'")->fetchColumn()) {
        $get = fn($k) => $db->query("SELECT v FROM settings WHERE k = " . $db->quote($k))->fetchColumn();
        if (in_array($get('primary_color'), [false, '#0b2a4a'], true)) $db->exec("INSERT OR REPLACE INTO settings (k,v) VALUES ('primary_color','#0b6b2e')");
        if (in_array($get('accent_color'), [false, '#c9962b'], true)) $db->exec("INSERT OR REPLACE INTO settings (k,v) VALUES ('accent_color','#f2b705')");
        $db->exec("INSERT OR IGNORE INTO settings (k,v) VALUES ('secondary_color','#d7141a')");
        $db->exec("INSERT OR REPLACE INTO settings (k,v) VALUES ('migr_theme','1')");
    }
    $db->exec("CREATE TABLE IF NOT EXISTS alumni_events (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, slug TEXT UNIQUE NOT NULL, category TEXT DEFAULT 'Event', event_date TEXT, end_date TEXT, time_text TEXT, venue TEXT, summary TEXT, description TEXT, image TEXT, contact_info TEXT, sort INTEGER DEFAULT 0, active INTEGER DEFAULT 1)");
    $db->exec("CREATE TABLE IF NOT EXISTS alumni_members (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT, phone TEXT, year_left TEXT, last_class TEXT, occupation TEXT, location TEXT, message TEXT, newsletter INTEGER DEFAULT 0, token TEXT, status TEXT DEFAULT 'new', created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
    $amc = array_column($db->query('PRAGMA table_info(alumni_members)')->fetchAll(PDO::FETCH_ASSOC), 'name');
    foreach (['profession' => 'TEXT', 'workplace' => 'TEXT', 'photo' => 'TEXT', 'proof_file' => 'TEXT', 'consent' => 'INTEGER DEFAULT 0', 'consent_at' => 'TEXT'] as $c => $def) if (!in_array($c, $amc, true)) $db->exec("ALTER TABLE alumni_members ADD COLUMN $c $def");
    $nc = array_column($db->query('PRAGMA table_info(newsletter_issues)')->fetchAll(PDO::FETCH_ASSOC), 'name');
    if (!in_array('audience', $nc, true)) $db->exec("ALTER TABLE newsletter_issues ADD COLUMN audience TEXT DEFAULT 'school'");
    if (!$db->query("SELECT 1 FROM settings WHERE k = 'migr_alumni'")->fetchColumn()) {
        seed_alumni($db, true);
        $db->exec("UPDATE pages SET active = 0 WHERE slug = 'alumni'");
        $db->exec("INSERT OR REPLACE INTO settings (k,v) VALUES ('migr_alumni','1')");
    }
    if (!$db->query("SELECT 1 FROM settings WHERE k = 'migr_theme2'")->fetchColumn()) {
        $pc = $db->query("SELECT v FROM settings WHERE k = 'primary_color'")->fetchColumn();
        if (in_array($pc, [false, '#0b6b2e'], true)) $db->exec("INSERT OR REPLACE INTO settings (k,v) VALUES ('primary_color','#0b4a24')");
        $db->exec("INSERT OR REPLACE INTO settings (k,v) VALUES ('migr_theme2','1')");
    }
    $ac = array_column($db->query('PRAGMA table_info(admissions_apps)')->fetchAll(PDO::FETCH_ASSOC), 'name');
    foreach (['admission_type', 'transfer_reason', 'results_file', 'recommendation_file'] as $c) if (!in_array($c, $ac, true)) $db->exec("ALTER TABLE admissions_apps ADD COLUMN $c TEXT");
    $done = $db->query("SELECT 1 FROM settings WHERE k = 'migr_pta'")->fetchColumn();
    if (!$done) {
        if (!$db->query("SELECT 1 FROM team WHERE grp = 'pta' LIMIT 1")->fetchColumn()) {
            $ins = $db->prepare("INSERT INTO team (name,role,grp,bio,sort) VALUES ('Name to be updated',?,'pta','',?)");
            foreach (['PTA Chairperson', 'PTA Vice Chairperson', 'PTA Secretary', 'PTA Treasurer', 'PTA Committee Member', 'PTA Committee Member'] as $i => $r) $ins->execute([$r, $i + 1]);
        }
        $db->exec("INSERT OR REPLACE INTO settings (k,v) VALUES ('migr_pta','1')");
    }
}


function scholarship_default(string $key): string
{
    return [
        'scholarships_intro' => 'AFSS believes no capable learner should miss out on education because of financial hardship. Below are the bursaries and scholarships offered, who qualifies and how to apply.',
        'scholarships_apply' => '<p>Read the requirements of the bursary or scholarship you are interested in, then apply online using the form on its page, or deliver your application to the school office. Applications are reviewed by the Scholarships &amp; Bursaries Committee, and outcomes are communicated to parents or guardians by phone or email.</p>',
    ][$key] ?? '';
}

/** Starter bursaries. The wording is a sample for the school to confirm and edit from the admin panel. */
function seed_scholarships(PDO $db): void
{
    $howto = '<ol><li>Read the eligibility criteria carefully.</li><li>Fill in the online application form on this page, or collect a paper form from the school office.</li><li>Attach or deliver the required supporting documents.</li><li>Wait for communication from the Scholarships &amp; Bursaries Committee.</li></ol>';
    $note = '<p><em>Sample wording — the school should confirm and edit these details in the admin panel.</em></p>';
    $rows = [
        ['Academic Improvement Bursary', 'Rewards learners who show strong academic progress and commitment.', 'Partial tuition support (amount set each year)', '<ul><li>Currently enrolled at AFSS or admitted for the coming term</li><li>Demonstrated improvement or strong performance in recent assessments</li><li>Good discipline record</li></ul>', '<ul><li>Recent report card or results slip</li><li>Letter from parent or guardian</li></ul>'],
        ['Sports Bursary', 'Supports talented learners who represent the school in sport.', 'Partial tuition support', '<ul><li>Proven talent in an approved sport</li><li>Recommendation from a sports teacher or coach</li><li>Maintains acceptable academic performance</li></ul>', '<ul><li>Evidence of participation or achievements</li><li>Recommendation letter from a coach or sports teacher</li></ul>'],
        ['Good Conduct (Discipline) Bursary', 'Recognises learners with an outstanding record of discipline and leadership.', 'Partial tuition support', '<ul><li>Clean disciplinary record</li><li>Positive contribution to school life or leadership roles</li><li>Recommendation from the Director of Studies or class teacher</li></ul>', '<ul><li>Teacher recommendation letter</li><li>Short personal statement</li></ul>'],
        ['Disability Support Bursary', 'Helps learners living with a disability to study without financial barriers.', 'Support determined case by case', '<ul><li>Learner living with a disability</li><li>Demonstrated financial need</li></ul>', '<ul><li>Medical or disability assessment letter</li><li>Letter from parent or guardian explaining the need</li></ul>'],
        ['Orphans Support Bursary', 'Supports orphaned and vulnerable learners to complete their education.', 'Support determined case by case', '<ul><li>Orphaned or vulnerable learner</li><li>Verified by a local leader or church/community authority</li></ul>', '<ul><li>Recommendation from the LC1 chairperson or parish priest</li><li>Death certificate or confirmation letter, where available</li></ul>'],
    ];
    $st = $db->prepare("INSERT OR IGNORE INTO scholarships (title,slug,type,summary,coverage,sponsor,eligibility,how_to_apply,documents,sort) VALUES (?,?,'Bursary',?,?,'AFSS',?,?,?,?)");
    foreach ($rows as $i => $r) $st->execute([$r[0], strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $r[0]), '-')), $r[1], $r[2], $r[3] . $note, $howto, $r[4], $i + 1]);
}

function alumni_default(string $key): string
{
    return [
        'alumni_name' => 'AFSS Old Students Association (AFSSOSA)',
        'alumni_intro' => 'Once an AFSS learner, always part of the family. Stay connected with classmates, support the school and take part in alumni activities.',
        'alumni_about' => '<p>The alumni association brings together former students of Archbishop Flynn Secondary School to keep the spirit of <em>Sapientia et Servitium</em> alive, support current learners and give back to the school community.</p>',
        'alumni_email' => '',
        'alumni_phone' => '',
        'alumni_payment_info' => '',
    ][$key] ?? '';
}

/** Starter alumni leadership and activities. Names and details are placeholders for the school to replace in the admin panel. */
function seed_alumni(PDO $db, bool $withSettings = false): void
{
    if ($withSettings) {
        $ins = $db->prepare('INSERT OR IGNORE INTO settings (k,v) VALUES (?,?)');
        foreach (['alumni_name', 'alumni_intro', 'alumni_about', 'alumni_email', 'alumni_phone'] as $k) $ins->execute([$k, alumni_default($k)]);
    }
    if (!$db->query("SELECT 1 FROM team WHERE grp IN ('alumni_patron','alumni_exec') LIMIT 1")->fetchColumn()) {
        $t = $db->prepare("INSERT INTO team (name,role,grp,bio,sort) VALUES ('Name to be updated',?,?,'',?)");
        $t->execute(['Patron', 'alumni_patron', 1]);
        foreach (['Chairperson', 'Vice Chairperson', 'General Secretary', 'Treasurer', 'Publicity Secretary', 'League Coordinator', 'Welfare Representative'] as $i => $r) $t->execute([$r, 'alumni_exec', $i + 1]);
    }
    if (!$db->query('SELECT 1 FROM alumni_events LIMIT 1')->fetchColumn()) {
        $e = $db->prepare('INSERT INTO alumni_events (title,slug,category,summary,description,contact_info,sort) VALUES (?,?,?,?,?,?,?)');
        $note = '<p><em>Sample description. Edit the details, dates and venue in the admin panel.</em></p>';
        $e->execute(['AFSSOSA League', 'afssosa-league', 'League', 'The alumni sports league: classes and teams compete for the trophy.', '<p>The AFSSOSA League brings alumni together on the field. Graduating classes and groups field teams in friendly competition that builds bonds across generations.</p>' . $note, 'Contact the League Coordinator to register your team.', 1]);
        $e->execute(['Annual General Meeting (AGM)', 'agm', 'AGM', 'Members meet to review the year, elect leaders and plan ahead.', '<p>Alumni gather for the Annual General Meeting to receive reports, elect the Executive Committee and decide the association\'s priorities.</p>' . $note, 'All paid-up and registered alumni are welcome.', 2]);
        $e->execute(['Alumni Dinner', 'alumni-dinner', 'Dinner', 'An evening of reunion, awards and fundraising for school projects.', '<p>The Alumni Dinner is a night to reconnect, celebrate achievements and raise support for the school and its learners.</p>' . $note, 'Ask the Treasurer or Publicity Secretary about tickets.', 3]);
    }
}
