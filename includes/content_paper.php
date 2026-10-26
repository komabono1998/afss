<?php
/**
 * Website content from the "AFSS Website Enhancement Content Paper": school history, vision and mission, values,
 * departments, admissions steps and FAQ, AFRUDEC, student life, safeguarding and partners.
 * Applied once by migrate(); afterwards everything is edited in the admin panel (Content → Pages, Site Settings).
 */
function content_paper_pages(): array
{
    return [
        'about-us' => ['About Us', 'Who we are, our story, what we believe and where we are going.', 'about', 1, <<<'HTML'
<p>Archbishop Flynn Secondary School (AFSS) is a co-educational secondary school located in Pader District, Northern Uganda. Established in 2009, the school seeks to provide quality education grounded in the Christian tradition and to prepare learners to become responsible, productive and service-minded members of society.</p>
<h2>Our story</h2>
<p>The school's founding vision emerged from a desire to expand access to quality education for young people in Northern Uganda, a region whose communities experienced years of conflict and disruption. Monsignor Mathew Ray Odong, a priest of the Archdiocese of Gulu, helped advance this vision with support from friends and benefactors in the United States. The school was named in honour of Archbishop Harry John Flynn, whose friendship and support formed part of its founding story.</p>
<p>Since its establishment, AFSS has sought to combine academic learning with Christian formation, student development and community service. Its story is part of a wider effort to create opportunities for young people and strengthen the future of communities in Northern Uganda.</p>
<h2>Vision</h2>
<p>To be a leading school through a dedicated commitment to quality and holistic education.</p>
<h2>Mission</h2>
<p>To provide excellence in holistic and quality education rooted in the Christian tradition, with the aim of producing responsible and productive professionals for community transformation.</p>
<h2>Our motto</h2>
<p><strong>Sapientia et Servitium</strong>: Wisdom and Service.</p>
<p>Our motto expresses the belief that knowledge carries responsibility. We encourage learners to grow in understanding and to put their abilities to constructive use in their families, workplaces, churches and communities.</p>
<h2>Our values</h2>
<ul>
  <li><strong>Fear of God:</strong> a life of faith, prayer and reverence that shapes how we treat others.</li>
  <li><strong>Charity:</strong> care and generosity towards those in need.</li>
  <li><strong>Integrity:</strong> doing what is right, even when no one is watching.</li>
  <li><strong>Honesty:</strong> truthfulness in words, work and examinations.</li>
  <li><strong>Accountability:</strong> taking responsibility for our choices and our resources.</li>
  <li><strong>Professionalism:</strong> excellence, diligence and good conduct in all we do.</li>
  <li><strong>Respect:</strong> dignity for every person, and equal opportunity for boys and girls.</li>
</ul>
<h2>Registration</h2>
<p>The school is registered with the Ministry of Education and Sports (PSS/A/209), licensed (ME/22/4831) and operates as UNEB centre U2848. It is located two kilometres from Pader town along Latanya Road.</p>
HTML],
        'partnerships' => ['Partnerships and Collaborations', 'Institutional partners, supporters and how to work with the school.', 'about', 6, <<<'HTML'
<p>The school values the relationships that help advance its educational mission. We work with the Archdiocese of Gulu, the Ministry of Education and Sports, development partners, alumni (AFSSOSA), parents and the people of Pader and the wider Acholi sub-region.</p>
<h2>Hope for Uganda</h2>
<p>Hope for Uganda is an important partner in the school's history and development. We gratefully acknowledge the friends and benefactors whose support helped bring Archbishop Flynn Secondary School into being and continues to strengthen it.</p>
<h2>How partners help</h2>
<ul>
  <li><strong>Scholarships and bursaries</strong> for learners who could not otherwise attend.</li>
  <li><strong>Learning resources:</strong> textbooks, library stock, science equipment and computers.</li>
  <li><strong>Mentorship and career talks</strong> from professionals and alumni.</li>
  <li><strong>Student wellbeing</strong> programmes, including health and counselling.</li>
  <li><strong>Sustainability projects</strong> such as AFRUDEC.</li>
</ul>
<h2>Work with us</h2>
<p>Interested in supporting education and learner development at AFSS? Contact the school to discuss appropriate partnership opportunities. Any donation or partnership uses verified school channels and clear financial accountability. See our <a href="donate.php">Donate &amp; Partner page</a> or <a href="contact.php">contact the school</a>.</p>
HTML],
        'afrudec' => ['AFRUDEC: Learning, Agriculture and Sustainability', 'Agricultural production, food security and sustainability linked to the school.', 'about', 7, <<<'HTML'
<p>The Archbishop Flynn Rural Development Education Center (AFRUDEC) is associated with the school's agricultural production and rural development work. Its activities connect the school with agriculture, food production and sustainability, and show the role practical initiatives can play in supporting an educational institution and its wider community.</p>
<h2>Agricultural production</h2>
<p>AFRUDEC supports crop, livestock and fish production and the farming methods used on the school's land. Current production details are shared with the community as they are confirmed each year.</p>
<h2>Food security</h2>
<p>Agricultural production contributes to food provision for the school community, helping to keep boarding life well supplied.</p>
<h2>Practical learning</h2>
<p>AFRUDEC offers a setting for learners to understand agriculture, entrepreneurship, environmental stewardship and rural livelihoods.</p>
<h2>Sustainability</h2>
<p>Sustainability is part of the school's thinking: soil health, water management, responsible use of resources, tree planting, climate adaptation and waste reduction.</p>
<h2>Community value</h2>
<p>Beyond the school gate, AFRUDEC reflects a wider commitment to education, food security and rural development in Pader District and Northern Uganda.</p>
HTML],
        'academics' => ['Academics', 'Explore O-Level and A-Level education, departments and learning resources.', 'academics', 1, <<<'HTML'
<p>At AFSS, teaching and learning aim to develop knowledge, skills, curiosity, discipline and independent thinking. Our academic programme prepares learners for national examinations and for further education, training and future responsibilities.</p>
<h2>Ordinary Level</h2>
<p>The O-Level programme provides a broad foundation across the prescribed secondary curriculum. Learners build subject knowledge, literacy, numeracy, practical understanding and study habits as they prepare for the Uganda Certificate of Education (UCE).</p>
<h2>Advanced Level</h2>
<p>The A-Level programme allows learners to pursue approved subject combinations in greater depth. It strengthens analytical thinking, subject mastery and preparation for higher education and other post-secondary pathways, ending with the Uganda Advanced Certificate of Education (UACE).</p>
<h2>Teaching and learning resources</h2>
<p>Learners are supported by the school library, e-learning resources and academic notices. Contact the school office for what learners can access and who to ask for help.</p>
HTML],
        'academic-programmes' => ['Academic Programmes', 'O-Level and A-Level programmes and subject combinations.', 'academics', 2, <<<'HTML'
<h2>Ordinary Level (Senior 1 to Senior 4)</h2>
<p>A broad foundation across the secondary curriculum, preparing learners for the Uganda Certificate of Education.</p>
<h2>Advanced Level (Senior 5 to Senior 6)</h2>
<p>Approved subject combinations studied in greater depth, preparing learners for UACE and for higher education and other pathways.</p>
<p>For the subject options and A-Level combinations available in the coming intake, please contact the school's admissions office.</p>
HTML],
        'departments' => ['Departments', 'Our academic departments.', 'academics', 3, <<<'HTML'
<h3>Physical Sciences</h3><p>Supports learning in mathematics and physical science subjects.</p>
<h3>Biological Sciences</h3><p>Develops understanding of living systems, health, environment and scientific inquiry.</p>
<h3>Business Studies</h3><p>Builds understanding of business, commerce, enterprise and financial concepts.</p>
<h3>Vocational Studies</h3><p>Supports practical learning and applied skills in the subjects the school offers.</p>
<h3>Humanities</h3><p>Explores people, places, societies, history and the relationships that shape communities.</p>
<h3>Languages</h3><p>Develops communication, reading, writing, comprehension and language skills.</p>
HTML],
        'how-to-apply' => ['How to Apply', 'Six simple steps to join AFSS.', 'admissions', 3, <<<'HTML'
<p>We welcome enquiries from families and learners interested in joining Archbishop Flynn Secondary School. Our admissions process helps applicants understand the entry requirements, available places, required documents and next steps.</p>
<ol>
  <li><strong>Step 1:</strong> Contact the school or visit the admissions office to confirm current vacancies, entry requirements and application dates.</li>
  <li><strong>Step 2:</strong> Use the school's online application (the <em>Apply Online</em> button) or obtain the official application form.</li>
  <li><strong>Step 3:</strong> Complete the form accurately and submit all required supporting documents.</li>
  <li><strong>Step 4:</strong> Follow the school's instructions for assessment, interview or placement, where applicable.</li>
  <li><strong>Step 5:</strong> Receive and review the official admission decision and fee guidance.</li>
  <li><strong>Step 6:</strong> Complete registration and reporting requirements by the stated deadline.</li>
</ol>
<h2>Documents to have ready</h2>
<p>Depending on the entry level, this may include previous school reports, examination results, identification or birth details, transfer documents, passport photographs and completed forms. The admissions office confirms the exact checklist for each intake.</p>
HTML],
        'admissions-faq' => ['Admissions FAQ', 'Answers to common admissions questions.', 'admissions', 5, <<<'HTML'
<h3>Does the school offer O-Level and A-Level?</h3>
<p>Yes. The school provides both levels. Please contact admissions to confirm the classes and subject options available for the intended intake.</p>
<h3>Is boarding available?</h3>
<p>Yes, the school has boarding provision. Contact the admissions office for current arrangements, requirements and charges.</p>
<h3>Are scholarships available?</h3>
<p>The school has published information about bursary and scholarship categories. Availability, eligibility, award values and deadlines are confirmed for each intake. See the <a href="scholarships.php">Scholarships &amp; Bursaries</a> page.</p>
<h3>How do I confirm fees?</h3>
<p>Request the latest approved fees structure directly from the school and use only official payment instructions.</p>
<h3>How do I apply?</h3>
<p>Use the <a href="apply.php">online application</a>, or contact the school through its official channels for the current application form and instructions.</p>
HTML],
        'scholarships' => ['Scholarships and Bursaries', 'Support for deserving learners.', 'admissions', 4, <<<'HTML'
<p>AFSS recognises that financial circumstances can affect a learner's ability to access and continue education. The school has published information about bursary support associated with academic performance, sport, discipline, disability and orphanhood. Support is subject to the school's approved criteria, available resources and the application process for the relevant academic year.</p>
<h2>What applicants should do</h2>
<ul>
  <li>Read the current eligibility requirements and application dates.</li>
  <li>Obtain the official bursary application form from the school, or apply online.</li>
  <li>Submit accurate information and the evidence requested.</li>
  <li>Observe confidentiality and safeguarding requirements when providing sensitive documents.</li>
  <li>Wait for written communication from the authorised school office.</li>
</ul>
<p class="notice"><strong>Please note:</strong> submitting an application does not guarantee an award. Scholarships and bursaries are subject to eligibility, assessment and available funding.</p>
HTML],
        'downloads' => ['Downloads and Resources', 'Prospectus, forms, fees, calendar and policies.', 'admissions', 6, <<<'HTML'
<p>Approved school documents are shared through the school office and posted here as they are published. Each document carries its title, date and a contact for questions.</p>
<ul>
  <li>School prospectus</li>
  <li>Current admissions form and instructions</li>
  <li>Current fees structure and payment guidance</li>
  <li>Academic calendar and term dates</li>
  <li>Approved school policies</li>
  <li>Scholarship and bursary application guidance</li>
  <li>Student handbook</li>
  <li>Examination notices and official results communications</li>
</ul>
<p>To request any of these documents, please <a href="contact.php">contact the school</a> or call <strong>{{school_phone}}</strong>.</p>
HTML],
        'student-leadership' => ['Student Leadership', 'The Prefects Body and student participation in school life.', 'students', 5, <<<'HTML'
<p>Learning extends beyond lessons. AFSS encourages learners to develop confidence, responsibility, teamwork, service and a sense of belonging through student leadership, clubs, cultural activities, sport, faith formation and appropriate guidance and support.</p>
<p>The <strong>Prefects Body</strong> provides a channel for student leadership and participation in school life. Prefects model good conduct, help staff keep the school running smoothly and carry the voice of learners to the administration. Updates on elections and student initiatives are shared on the News page.</p>
HTML],
        'faith-formation' => ['Faith Formation', 'Prayer, reflection and service in the Catholic tradition.', 'students', 6, <<<'HTML'
<p>Rooted in the school's Catholic tradition, faith formation encourages reflection, compassion, service, respect and responsible living. It is part of the daily rhythm of school life, through prayer, Mass, religious celebrations and service activities, and it shapes the character we hope to see in every learner.</p>
HTML],
        'safeguarding' => ['Health, Safety and Safeguarding', 'Student health, safety, dignity and how to raise a concern.', 'students', 7, <<<'HTML'
<p>Student health, safety and dignity are central to a supportive learning environment. This page explains where to find help and how concerns are handled.</p>
<h2>Health and wellbeing</h2>
<p>See our <a href="health.php">Health Services</a> and <a href="counselling.php">Counselling</a> pages to request help. Learners and parents can use the Seek help and Seek counselling forms at any time.</p>
<h2>Raising a concern</h2>
<p>Anyone, including parents and stakeholders, can raise a concern through the <a href="whistleblower.php">Report a Concern</a> page, openly or anonymously. Reports are handled confidentially by the school's authorised leadership.</p>
<h2>Safeguarding and privacy</h2>
<p>The school is committed to protecting learners from harm. We publish only approved operational information, and we handle personal information as explained in our <a href="page.php?slug=privacy">Privacy Policy</a>. Identifiable photographs of learners are used only with permission.</p>
<h2>Emergencies</h2>
<p>In an emergency, call the school on <strong>{{school_phone}}</strong>.</p>
HTML],
    ];
}

/** Apply the content paper once: pages are inserted or refreshed, settings and slides updated. */
function apply_content_paper(PDO $db): void
{
    $get = $db->prepare('SELECT id FROM pages WHERE slug = ?');
    $upd = $db->prepare('UPDATE pages SET title = ?, summary = ?, body = ? WHERE slug = ?');
    $ins = $db->prepare('INSERT INTO pages (slug, title, summary, body, menu_group, sort) VALUES (?,?,?,?,?,?)');
    foreach (content_paper_pages() as $slug => [$title, $summary, $group, $sort, $body]) {
        $get->execute([$slug]);
        if ($get->fetchColumn()) $upd->execute([$title, $summary, $body, $slug]);
        else $ins->execute([$slug, $title, $summary, $body, $group, $sort]);
    }
    $set = $db->prepare('INSERT OR REPLACE INTO settings (k,v) VALUES (?,?)');
    foreach ([
        'vision' => 'To be a leading school through a dedicated commitment to quality and holistic education.',
        'mission' => 'To provide excellence in holistic and quality education rooted in the Christian tradition, with the aim of producing responsible and productive professionals for community transformation.',
        'welcome_text' => "Welcome to Archbishop Flynn Secondary School. Our school brings together academic learning, character formation, faith, leadership and service. Guided by our motto, Sapientia et Servitium, we encourage every learner to pursue knowledge, act with integrity, respect others and use their abilities to make a positive difference.\n\nWe believe education is more than passing examinations. It is a journey that equips young people with the knowledge, skills, values and confidence to navigate life, contribute to their communities and prepare for further study and meaningful work.",
        'founders_title' => "Our Founder's Story",
        'founders_story' => "Archbishop Flynn Secondary School was established in 2009 from a desire to expand access to quality education for young people in Northern Uganda, a region whose communities experienced years of conflict and disruption.\n\nMonsignor Mathew Ray Odong, a priest of the Archdiocese of Gulu, helped advance this vision with support from friends and benefactors in the United States. The school was named in honour of Archbishop Harry John Flynn, whose friendship and support formed part of its founding story.\n\nSince then, AFSS has combined academic learning with Christian formation, student development and community service, part of a wider effort to create opportunities for young people and strengthen the future of communities in Northern Uganda.",
        'alumni_intro' => "Wherever life has taken you, you remain part of the Archbishop Flynn Secondary School story. We invite former students to reconnect, share their experiences and help inspire the next generation. Through mentorship, career talks, school activities and approved giving initiatives, alumni can contribute to a stronger learning community.",
    ] as $k => $v) $set->execute([$k, $v]);
    $db->prepare('UPDATE slides SET title = ?, subtitle = ? WHERE id = 1')->execute([
        'Wisdom for Life. Service to Humanity.',
        'Quality, holistic education rooted in Christian values in Pader District, Northern Uganda, nurturing knowledgeable, responsible and compassionate young people.',
    ]);
}
