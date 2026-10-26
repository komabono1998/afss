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
        bio TEXT, photo TEXT, sort INTEGER DEFAULT 0, email TEXT, phone TEXT, term TEXT, subjects TEXT
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
        profession TEXT, workplace TEXT, photo TEXT, proof_file TEXT, consent INTEGER DEFAULT 0, consent_at TEXT,
        member_no TEXT, member_year INTEGER, member_seq INTEGER, approved_at TEXT, approved_by TEXT, valid_from TEXT, valid_to TEXT, reject_reason TEXT, card_token TEXT, notes TEXT
    );
    CREATE UNIQUE INDEX ux_alumni_member_no ON alumni_members(member_no);
    CREATE TABLE alumni_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT, member_id INTEGER, user_name TEXT, action TEXT, detail TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE class_streams (
        id INTEGER PRIMARY KEY AUTOINCREMENT, class_name TEXT NOT NULL, stream_name TEXT DEFAULT '', level TEXT DEFAULT 'O Level',
        teacher_id INTEGER, sort INTEGER DEFAULT 0, active INTEGER DEFAULT 1
    );
    CREATE TABLE students (
        id INTEGER PRIMARY KEY AUTOINCREMENT, reg_no TEXT UNIQUE NOT NULL, reg_year INTEGER, reg_seq INTEGER,
        first_name TEXT NOT NULL, last_name TEXT, other_names TEXT, gender TEXT, dob TEXT, district TEXT,
        class_stream_id INTEGER, guardian_name TEXT, guardian_phone TEXT, guardian_email TEXT, external_id TEXT,
        status TEXT DEFAULT 'present', status_reason TEXT, status_date TEXT, notes TEXT, application_ref TEXT,
        created_by INTEGER, created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE student_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT, student_id INTEGER, user_name TEXT, action TEXT, detail TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE whistle_reports (
        id INTEGER PRIMARY KEY AUTOINCREMENT, ref TEXT UNIQUE, secret_hash TEXT, category TEXT, category_other TEXT, details TEXT,
        incident_date TEXT, location TEXT, people_involved TEXT, anonymous INTEGER DEFAULT 1, reporter_name TEXT, reporter_contact TEXT,
        relationship TEXT, attachment TEXT, status TEXT DEFAULT 'new', response TEXT, notes TEXT, followups TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE donation_methods (
        id INTEGER PRIMARY KEY AUTOINCREMENT, type TEXT DEFAULT 'bank', title TEXT NOT NULL, bank_name TEXT, account_name TEXT, account_number TEXT,
        branch TEXT, swift TEXT, currency TEXT, merchant_code TEXT, link_url TEXT, instructions TEXT, sort INTEGER DEFAULT 0, active INTEGER DEFAULT 1
    );
    CREATE TABLE donation_causes (
        id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, summary TEXT, image TEXT, target TEXT, sort INTEGER DEFAULT 0, active INTEGER DEFAULT 1
    );
    CREATE TABLE donor_pledges (
        id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, organisation TEXT, email TEXT, phone TEXT, kind TEXT, cause TEXT, amount_text TEXT,
        reference TEXT, message TEXT, status TEXT DEFAULT 'new', created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE clubs (
        id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, slug TEXT UNIQUE NOT NULL, summary TEXT, description TEXT, activities TEXT,
        meeting_time TEXT, venue TEXT, patron TEXT, leaders TEXT, how_to_join TEXT, image TEXT, sort INTEGER DEFAULT 0, active INTEGER DEFAULT 1
    );
    CREATE TABLE welfare_services (
        id INTEGER PRIMARY KEY AUTOINCREMENT, type TEXT DEFAULT 'health', title TEXT NOT NULL, description TEXT, sort INTEGER DEFAULT 0, active INTEGER DEFAULT 1
    );
    CREATE TABLE help_requests (
        id INTEGER PRIMARY KEY AUTOINCREMENT, ref TEXT UNIQUE, kind TEXT DEFAULT 'health', topic TEXT, topic_other TEXT, urgent INTEGER DEFAULT 0, anonymous INTEGER DEFAULT 0,
        name TEXT, class_name TEXT, role_text TEXT, contact TEXT, preferred_time TEXT, message TEXT, status TEXT DEFAULT 'new', notes TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP
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
        guardian_phone TEXT, guardian_email TEXT, address TEXT, notes TEXT, district TEXT,
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
        'donate_intro' => donate_default('donate_intro'), 'donate_thanks' => donate_default('donate_thanks'), 'partnership_text' => donate_default('partnership_text'), 'partnership_types' => donate_default('partnership_types'), 'donate_button' => '1',
        'reg_prefix' => 'AFSS',
        'api_enabled' => '0', 'api_key_hash' => '', 'api_key_hint' => '', 'api_guardian' => '0',
        'whistle_intro' => whistle_default('whistle_intro'), 'whistle_categories' => whistle_default('whistle_categories'),
        'footer_text' => 'Archbishop Flynn Secondary School — Sapientia et Servitium.',
    ];
    foreach ($defaults as $k => $v) $set->execute([$k, $v]);
    foreach (['scholarships_intro', 'scholarships_apply'] as $k) $set->execute([$k, scholarship_default($k)]);
    seed_scholarships($db);
    seed_alumni($db, true);
    seed_giving($db);
    seed_welfare($db);
    $wi = $db->prepare('INSERT OR IGNORE INTO settings (k,v) VALUES (?,?)');
    foreach (welfare_settings() as $wk => $wv) $wi->execute([$wk, $wv]);

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
    $am = array_column($db->query('PRAGMA table_info(alumni_members)')->fetchAll(PDO::FETCH_ASSOC), 'name');
    foreach (['member_no' => 'TEXT', 'member_year' => 'INTEGER', 'member_seq' => 'INTEGER', 'approved_at' => 'TEXT', 'approved_by' => 'TEXT', 'valid_from' => 'TEXT', 'valid_to' => 'TEXT', 'reject_reason' => 'TEXT', 'card_token' => 'TEXT', 'notes' => 'TEXT'] as $c => $def)
        if (!in_array($c, $am, true)) $db->exec("ALTER TABLE alumni_members ADD COLUMN $c $def");
    $db->exec('CREATE UNIQUE INDEX IF NOT EXISTS ux_alumni_member_no ON alumni_members(member_no)');
    $db->exec('CREATE TABLE IF NOT EXISTS alumni_log (id INTEGER PRIMARY KEY AUTOINCREMENT, member_id INTEGER, user_name TEXT, action TEXT, detail TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)');
    if (!$db->query("SELECT 1 FROM settings WHERE k = 'migr_alumni_cards'")->fetchColumn()) {
        $db->exec("UPDATE alumni_members SET status = 'approved' WHERE status = 'verified'");   // old wording
        $db->exec("UPDATE alumni_members SET status = 'rejected' WHERE status = 'ignored'");
        $ia = $db->prepare('INSERT OR IGNORE INTO settings (k,v) VALUES (?,?)');
        foreach (['alumni_prefix', 'alumni_validity_months', 'alumni_card_note', 'alumni_card_signatory'] as $k) $ia->execute([$k, alumni_default($k)]);
        $db->exec("INSERT OR REPLACE INTO settings (k,v) VALUES ('migr_alumni_cards','1')");
    }
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
    $db->exec("CREATE TABLE IF NOT EXISTS class_streams ( id INTEGER PRIMARY KEY AUTOINCREMENT, class_name TEXT NOT NULL, stream_name TEXT DEFAULT '', level TEXT DEFAULT 'O Level', teacher_id INTEGER, sort INTEGER DEFAULT 0, active INTEGER DEFAULT 1 ); CREATE TABLE IF NOT EXISTS students ( id INTEGER PRIMARY KEY AUTOINCREMENT, reg_no TEXT UNIQUE NOT NULL, reg_year INTEGER, reg_seq INTEGER, first_name TEXT NOT NULL, last_name TEXT, other_names TEXT, gender TEXT, dob TEXT, district TEXT, class_stream_id INTEGER, guardian_name TEXT, guardian_phone TEXT, guardian_email TEXT, external_id TEXT, status TEXT DEFAULT 'present', status_reason TEXT, status_date TEXT, notes TEXT, application_ref TEXT, created_by INTEGER, created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP ); CREATE TABLE IF NOT EXISTS student_log ( id INTEGER PRIMARY KEY AUTOINCREMENT, student_id INTEGER, user_name TEXT, action TEXT, detail TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP ); CREATE TABLE IF NOT EXISTS whistle_reports ( id INTEGER PRIMARY KEY AUTOINCREMENT, ref TEXT UNIQUE, secret_hash TEXT, category TEXT, category_other TEXT, details TEXT, incident_date TEXT, location TEXT, people_involved TEXT, anonymous INTEGER DEFAULT 1, reporter_name TEXT, reporter_contact TEXT, relationship TEXT, attachment TEXT, status TEXT DEFAULT 'new', response TEXT, notes TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP );");
    $adc = array_column($db->query('PRAGMA table_info(admissions_apps)')->fetchAll(PDO::FETCH_ASSOC), 'name');
    if (!in_array('district', $adc, true)) $db->exec('ALTER TABLE admissions_apps ADD COLUMN district TEXT');
    $wbc = array_column($db->query('PRAGMA table_info(whistle_reports)')->fetchAll(PDO::FETCH_ASSOC), 'name');
    if (!in_array('followups', $wbc, true)) $db->exec('ALTER TABLE whistle_reports ADD COLUMN followups TEXT');
    $ins2 = $db->prepare('INSERT OR IGNORE INTO settings (k,v) VALUES (?,?)');
    foreach (['reg_prefix' => 'AFSS', 'api_enabled' => '0', 'api_key_hash' => '', 'api_key_hint' => '', 'api_guardian' => '0', 'whistle_intro' => whistle_default('whistle_intro'), 'whistle_categories' => whistle_default('whistle_categories')] as $k2 => $v2) $ins2->execute([$k2, $v2]);
    $db->exec("CREATE TABLE IF NOT EXISTS donation_methods ( id INTEGER PRIMARY KEY AUTOINCREMENT, type TEXT DEFAULT 'bank', title TEXT NOT NULL, bank_name TEXT, account_name TEXT, account_number TEXT, branch TEXT, swift TEXT, currency TEXT, merchant_code TEXT, link_url TEXT, instructions TEXT, sort INTEGER DEFAULT 0, active INTEGER DEFAULT 1 ); CREATE TABLE IF NOT EXISTS donation_causes ( id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, summary TEXT, image TEXT, target TEXT, sort INTEGER DEFAULT 0, active INTEGER DEFAULT 1 ); CREATE TABLE IF NOT EXISTS donor_pledges ( id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, organisation TEXT, email TEXT, phone TEXT, kind TEXT, cause TEXT, amount_text TEXT, reference TEXT, message TEXT, status TEXT DEFAULT 'new', created_at TEXT DEFAULT CURRENT_TIMESTAMP );");
    $db->exec("CREATE TABLE IF NOT EXISTS clubs ( id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, slug TEXT UNIQUE NOT NULL, summary TEXT, description TEXT, activities TEXT, meeting_time TEXT, venue TEXT, patron TEXT, leaders TEXT, how_to_join TEXT, image TEXT, sort INTEGER DEFAULT 0, active INTEGER DEFAULT 1 ); CREATE TABLE IF NOT EXISTS welfare_services ( id INTEGER PRIMARY KEY AUTOINCREMENT, type TEXT DEFAULT 'health', title TEXT NOT NULL, description TEXT, sort INTEGER DEFAULT 0, active INTEGER DEFAULT 1 ); CREATE TABLE IF NOT EXISTS help_requests ( id INTEGER PRIMARY KEY AUTOINCREMENT, ref TEXT UNIQUE, kind TEXT DEFAULT 'health', topic TEXT, topic_other TEXT, urgent INTEGER DEFAULT 0, anonymous INTEGER DEFAULT 0, name TEXT, class_name TEXT, role_text TEXT, contact TEXT, preferred_time TEXT, message TEXT, status TEXT DEFAULT 'new', notes TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP );");
    if (!$db->query("SELECT 1 FROM settings WHERE k = 'migr_welfare'")->fetchColumn()) {
        $wi = $db->prepare('INSERT OR IGNORE INTO settings (k,v) VALUES (?,?)');
        foreach (welfare_settings() as $wk => $wv) $wi->execute([$wk, $wv]);
        seed_welfare($db);
        $db->exec("INSERT OR REPLACE INTO settings (k,v) VALUES ('migr_welfare','1')");
    }
    $tmc = array_column($db->query('PRAGMA table_info(team)')->fetchAll(PDO::FETCH_ASSOC), 'name');
    if (!in_array('subjects', $tmc, true)) $db->exec('ALTER TABLE team ADD COLUMN subjects TEXT');
    if (!$db->query("SELECT 1 FROM settings WHERE k = 'migr_giving'")->fetchColumn()) {
        $gi = $db->prepare('INSERT OR IGNORE INTO settings (k,v) VALUES (?,?)');
        foreach (['donate_intro', 'donate_thanks', 'partnership_text', 'partnership_types'] as $gk) $gi->execute([$gk, donate_default($gk)]);
        $gi->execute(['donate_button', '1']);
        seed_giving($db);
    seed_welfare($db);
    $wi = $db->prepare('INSERT OR IGNORE INTO settings (k,v) VALUES (?,?)');
    foreach (welfare_settings() as $wk => $wv) $wi->execute([$wk, $wv]);
        $db->exec("INSERT OR REPLACE INTO settings (k,v) VALUES ('migr_giving','1')");
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
        'alumni_prefix' => 'AFSSOSA',
        'alumni_validity_months' => '12',
        'alumni_card_note' => 'This card is the property of the association. Present it at alumni events. If found, please return it to the school.',
        'alumni_card_signatory' => 'Chairperson',
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

function whistle_default(string $key): string
{
    return [
        'whistle_intro' => 'Parents, students, staff, alumni and community members can report any concern about the safety, welfare or integrity of our school. You may give your name or stay completely anonymous. Every report is treated seriously and in confidence.',
        'whistle_categories' => "Corruption or misuse of school funds\nBribery for admissions, marks or bursaries\nFee or bursary irregularities\nBullying or harassment\nSexual abuse or exploitation\nChild protection concern\nCorporal punishment or physical abuse\nTeacher misconduct or absenteeism\nExamination malpractice\nDrug or substance abuse\nDiscrimination or exclusion\nUnsafe buildings or facilities\nPoor sanitation, health or feeding",
    ][$key] ?? '';
}

function donate_default(string $key): string
{
    return [
        'donate_intro' => 'Your gift helps young people in Pader and beyond to learn, grow and serve. Every contribution, large or small, goes towards bursaries, learning resources and school development. Thank you for standing with Archbishop Flynn Secondary School.',
        'donate_thanks' => 'Thank you! Your message has been received. A member of the school team will contact you shortly.',
        'partnership_text' => '<p>We welcome partnerships with churches, companies, NGOs, government programmes, alumni groups and individuals who share our commitment to quality, values-based education. Partners can support bursaries, infrastructure, equipment, teacher training, mentorship and more.</p><p>Tell us how you would like to help and we will agree a plan that works for you.</p>',
        'partnership_types' => "Make a donation\nSponsor a student (bursary)\nCorporate or institutional partnership\nDonate books, equipment or materials\nOffer skills, mentorship or training\nSupport a school project\nOther",
    ][$key] ?? '';
}

/** Giving options start switched OFF with empty details, so nothing wrong is ever shown publicly until the school fills them in. */
function seed_giving(PDO $db): void
{
    if (!$db->query('SELECT 1 FROM donation_methods LIMIT 1')->fetchColumn()) {
        $m = $db->prepare("INSERT INTO donation_methods (type,title,currency,instructions,sort,active) VALUES (?,?,?,?,?,0)");
        $m->execute(['bank', 'Bank transfer (UGX)', 'UGX', 'Please use your name and the purpose of your gift as the payment reference.', 1]);
        $m->execute(['mtn', 'MTN Mobile Money', 'UGX', 'Pay the merchant code from your Mobile Money menu. Use your name as the reference.', 2]);
        $m->execute(['airtel', 'Airtel Money', 'UGX', 'Pay the merchant code from your Airtel Money menu. Use your name as the reference.', 3]);
        $m->execute(['link', 'Give online', '', 'Opens a secure payment page.', 4]);
    }
    if (!$db->query('SELECT 1 FROM donation_causes LIMIT 1')->fetchColumn()) {
        $c = $db->prepare('INSERT INTO donation_causes (title,summary,sort) VALUES (?,?,?)');
        $c->execute(['Bursary fund', 'Keep capable learners in school when families cannot afford fees, including orphans and learners with disabilities.', 1]);
        $c->execute(['Library and learning resources', 'Textbooks, readers, science equipment and e-learning tools for every classroom.', 2]);
        $c->execute(['School development', 'Classrooms, dormitories, sanitation and safe learning spaces.', 3]);
    }
}

/** Editable defaults for the Health and Counselling pages (stored in settings, changed under Student Welfare → Welfare Page Text). */
function welfare_settings(): array
{
    return [
        'clubs_intro' => 'Clubs and societies are where our learners discover talents, build friendships and learn to lead and serve. Every learner is encouraged to join at least one.',
        'health_intro' => 'The school health unit looks after the wellbeing of every learner. If you or your child is unwell, injured or worried about health, we are here to help.',
        'health_hours' => 'Monday to Friday, during school hours. A staff member is available on call for boarders after hours.',
        'health_location' => 'School health unit (sick bay), on the school campus.',
        'health_contact' => '',
        'health_parents_note' => 'Parents and guardians, please tell the school nurse in writing about any ongoing health condition, allergy or regular medication when your child joins, and update us if anything changes. In an emergency we will contact you using the numbers on file, so please keep them up to date.',
        'health_topics' => "Feeling unwell / need to see the nurse\nInjury\nOngoing health condition or medication\nMental or emotional health\nHealth information or advice\nDisability or special-needs support\nSick leave or health report\nOther (type your own)",
        'counselling_intro' => 'Everyone needs someone to talk to. Our counselling and career centre offers a safe, respectful and confidential space for learners, and support for parents and guardians too.',
        'counselling_hours' => 'By appointment during school hours. Urgent requests are handled first.',
        'counselling_location' => 'Student Counselling and Career Centre, on the school campus.',
        'counselling_contact' => '',
        'counselling_promise' => 'What you share stays private. We only involve others if someone is in danger of serious harm, and we will talk to you first whenever it is safe to do so. You can ask for help without giving your name.',
        'counselling_topics' => "Stress or worry\nBullying or harassment\nFamily or home difficulties\nGrief or loss\nPeer pressure\nRelationships\nAcademic pressure or exam anxiety\nCareer or subject choice\nAlcohol, drugs or substance use\nFeeling sad, hopeless or unsafe\nOther (type your own)",
        'emergency_info' => "In an emergency, call the school on the number on this page, or call the police and ambulance on 999 or 112.\nChildren and young people can also call Sauti 116, the free Uganda Child Helpline.",
    ];
}

/** Starter clubs (the six listed on the old website) and welfare services. Descriptions are sample text for the school to replace. */
function seed_welfare(PDO $db): void
{
    if (!$db->query('SELECT 1 FROM clubs LIMIT 1')->fetchColumn()) {
        $note = '<p><em>Sample description. The patron can edit this in the admin panel.</em></p>';
        $join = 'Speak to the patron or a club leader, or ask your class teacher. New members are welcome at the start of each term.';
        $rows = [
            ['Prefects Body', 'prefects-body', 'Learner leaders who help keep the school orderly, caring and united.', '<p>The Prefects Body represents learners, supports discipline and welfare, and works with teachers to keep the school a good place to learn and live.</p>' . $note, "Leading by example\nSupervising duties and school routines\nRepresenting learners' concerns to staff"],
            ['Interact Club', 'interact-club', 'A service club where young people serve their community.', '<p>Interact is a service-minded club for young people. Members plan and carry out projects that help the school and the surrounding community.</p>' . $note, "Community service projects\nClean-up and tree-planting drives\nVisits to those in need"],
            ['Debating Club', 'debating-club', 'Learn to think clearly, speak with confidence and listen well.', '<p>The Debating Club trains learners in research, argument and public speaking, and takes part in school and inter-school debates.</p>' . $note, "Weekly practice debates\nInter-school competitions\nPublic-speaking training"],
            ['Patriotism Club', 'patriotism-club', 'Building love for country, civic responsibility and good citizenship.', '<p>Members learn about Uganda’s history, values and institutions and practise responsible citizenship in school and community life.</p>' . $note, "Civic education talks\nNational days and celebrations\nCommunity and leadership activities"],
            ['Writers Club', 'writers-club', 'A home for poets, storytellers, journalists and creative thinkers.', '<p>The Writers Club encourages reading and writing. Members share their work, take part in writing competitions and contribute to school publications.</p>' . $note, "Creative-writing workshops\nPoetry and storytelling sessions\nContributing to the school newsletter"],
            ['Scouting', 'scouting', 'Adventure, teamwork, service and practical life skills.', '<p>Scouting builds character through outdoor activities, teamwork and service, guided by the Scout promise and law.</p>' . $note, "Camping and outdoor skills\nFirst aid and survival basics\nService to others"],
        ];
        $st = $db->prepare('INSERT INTO clubs (name,slug,summary,description,activities,how_to_join,sort) VALUES (?,?,?,?,?,?,?)');
        foreach ($rows as $i => $r) $st->execute([$r[0], $r[1], $r[2], $r[3], $r[4], $join, $i + 1]);
    }
    if (!$db->query('SELECT 1 FROM welfare_services LIMIT 1')->fetchColumn()) {
        $st = $db->prepare('INSERT INTO welfare_services (type,title,description,sort) VALUES (?,?,?,?)');
        foreach ([
            ['health', 'First aid and minor treatment', 'Care for everyday illnesses, cuts, sprains and other minor injuries.'],
            ['health', 'Sick bay for rest and observation', 'A quiet place for learners who are unwell to rest while we monitor them.'],
            ['health', 'Referral to health facilities', 'When a learner needs more than we can give on campus, we refer them to a nearby health facility and inform parents or guardians.'],
            ['health', 'Health education and hygiene', 'Talks and campaigns on hygiene, nutrition, sanitation and disease prevention.'],
            ['health', 'Support for ongoing conditions', 'Help for learners with long-term conditions or regular medication, in partnership with their families.'],
            ['health', 'Emergency response', 'Quick action in emergencies and immediate contact with parents or guardians.'],
            ['counselling', 'Individual counselling', 'One-to-one conversations in a private, respectful setting.'],
            ['counselling', 'Career guidance and subject choice', 'Help with choosing subject combinations, courses and career paths.'],
            ['counselling', 'Study skills and exam support', 'Practical help with time management, revision and coping with exam stress.'],
            ['counselling', 'Peer support and mentoring', 'Trained peer supporters and mentors who listen and help learners feel less alone.'],
            ['counselling', 'Support through loss and crisis', 'Care for learners facing bereavement, family difficulty or other upsetting events.'],
            ['counselling', 'Parent and guardian consultations', 'Guidance for families on supporting their child’s wellbeing and learning.'],
        ] as $i => $r) $st->execute([$r[0], $r[1], $r[2], $i + 1]);
    }
    // replace the old one-line placeholder bodies of the three pages with short intros (only if the school has not edited them)
    $old = [
        'clubs' => ['<ul><li>Prefects Body</li><li>Interact Club</li><li>Debating Club</li><li>Patriotism Club</li><li>Writers Club</li><li>Scouting</li></ul>', 'Prefects, Interact, Debating, Patriotism, Writers and Scouting.'],
        'health-services' => ['<p>The school health unit provides first aid and basic care, and refers learners to nearby health facilities when necessary.</p>', 'Caring for learners’ wellbeing.'],
        'counselling' => ['<p>The Student Counselling and Career Centre offers guidance and support for personal growth and career choices.</p>', 'Guidance for learning, life and careers.'],
    ];
    $s = welfare_settings();
    $new = ['clubs' => $s['clubs_intro'], 'health-services' => $s['health_intro'], 'counselling' => $s['counselling_intro']];
    $up = $db->prepare('UPDATE pages SET body = ? WHERE slug = ? AND body = ?');
    foreach ($old as $slug => [$body]) $up->execute(['<p>' . htmlspecialchars($new[$slug], ENT_QUOTES, 'UTF-8') . '</p>', $slug, $body]);
}
