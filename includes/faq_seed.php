<?php
/** Starter questions for the FAQ & Help Centre. Staff edit, add or remove them under Admin → FAQ & Help Centre. */
function faq_categories(): array
{
    return [
        'Admissions & Applications', 'Fees & Scholarships', 'Boarding & Daily Life', 'Academics',
        'Student Welfare & Safety', 'Alumni', 'Giving & Partnerships', 'Jobs & Careers', 'Website & Contact',
    ];
}

function faq_seed(): array
{
    // [category, question, answer (HTML), link text, link url]
    return [
        ['Admissions & Applications', 'What levels does Archbishop Flynn Secondary School offer?',
            '<p>AFSS is a co-educational secondary school offering <strong>Ordinary Level (Senior 1 to Senior 4)</strong> and <strong>Advanced Level (Senior 5 to Senior 6)</strong>, leading to the UCE and UACE national examinations set by UNEB.</p>', 'Academic programmes', 'page.php?slug=academic-programmes'],
        ['Admissions & Applications', 'How do I apply for a place?',
            '<p>Use the <strong>Apply Online</strong> form on this website. You choose whether it is a <em>new admission</em> or a <em>transfer from another school</em>, fill in the learner and guardian details and attach the required documents. You can also contact the admissions office for a paper form.</p>', 'Apply online', 'apply.php'],
        ['Admissions & Applications', 'What is the difference between a new admission and a transfer?',
            '<p>A <strong>new admission</strong> is a learner joining AFSS for the first time, for example from primary school into Senior 1, or after completing O level. A <strong>transfer</strong> is a learner moving from another secondary school. For a transfer you also give the reason for transferring and attach a <strong>recommendation letter from the former school</strong>.</p>', 'Start an application', 'apply.php'],
        ['Admissions & Applications', 'Which documents do I need to attach?',
            '<p>Attach a copy of the learner\'s <strong>results (PDF)</strong>. Transfer applicants also attach the recommendation letter from the former school. The admissions office may ask for further documents, such as a birth record, passport photographs or previous school reports, depending on the entry class.</p>', 'Admission requirements', 'page.php?slug=admission-requirements'],
        ['Admissions & Applications', 'When is the next intake and when do I apply?',
            '<p>{{intake}} Applications are reviewed as they arrive, so earlier is better. If online applications are switched off, the website says so and you can contact the school directly.</p>', 'How to apply', 'page.php?slug=how-to-apply'],
        ['Admissions & Applications', 'How will I know if my application was received?',
            '<p>After you submit, the website shows a confirmation and the school receives your application in its admissions inbox. The admissions office then contacts you by phone or email. If you have not heard anything after a reasonable time, call <strong>{{phone}}</strong> or message us on WhatsApp.</p>', 'Contact the school', 'contact.php'],
        ['Admissions & Applications', 'Can a learner join in the middle of the year?',
            '<p>It depends on available places in the class and on the learner\'s previous results. Please contact the admissions office to find out whether a place is available.</p>', 'Contact admissions', 'contact.php'],
        ['Admissions & Applications', 'What are the entry requirements?',
            '<p>Entry requirements depend on the class applied for. They are published on the Minimum Entry Requirements page and confirmed by the admissions office for each intake.</p>', 'Entry requirements', 'page.php?slug=admission-requirements'],

        ['Fees & Scholarships', 'How much are the school fees?',
            '<p>Fees depend on the class and on whether the learner is a boarder or a day scholar, and they can change from year to year. To avoid wrong figures, please <strong>ask the Bursar</strong> for the current approved fees structure and use only the official payment instructions.</p>', 'Tuition and fees', 'page.php?slug=tuition-and-fees'],
        ['Fees & Scholarships', 'How and where do I pay fees?',
            '<p>Pay only through the channels the school publishes officially, and keep the receipt or payment reference. If you are unsure, confirm with the Bursar by phone before sending any money. Never pay to a personal number you have not verified.</p>', 'Tuition and fees', 'page.php?slug=tuition-and-fees'],
        ['Fees & Scholarships', 'Are scholarships or bursaries available?',
            '<p>Yes. The school offers bursary support linked to <strong>academic performance, sport, discipline, disability and orphanhood</strong>. Each award has its own eligibility criteria and how-to-apply steps, and you can apply online. Awards depend on eligibility, assessment and available funding, so applying does not guarantee support.</p>', 'Scholarships & bursaries', 'scholarships.php'],
        ['Fees & Scholarships', 'Who qualifies for a bursary?',
            '<p>Eligibility is listed on each award\'s page. In general, the school looks at financial need together with the category of the award (for example academic improvement, sporting talent, good discipline, disability or orphanhood).</p>', 'See the awards', 'scholarships.php'],
        ['Fees & Scholarships', 'Can fees be paid in instalments?',
            '<p>Please discuss payment arrangements with the Bursar before the term starts. Any arrangement should be agreed with the school in writing.</p>', 'Contact the school', 'contact.php'],

        ['Boarding & Daily Life', 'Is the school a boarding school?',
            '<p>Yes. AFSS is a co-educational boarding school, and day scholars are accepted by arrangement. Boys and girls study together and live in separate, supervised accommodation.</p>', 'About AFSS', 'page.php?slug=about-us'],
        ['Boarding & Daily Life', 'What is a typical school day like?',
            '<p>The day starts with rising, morning devotion and assembly, followed by lessons, lunch, afternoon lessons and remedial classes, games and clubs, then supervised preparation (prep) in the evening.</p>', 'Teaching and learning', 'page.php?slug=teaching-and-learning'],
        ['Boarding & Daily Life', 'What clubs and activities can learners join?',
            '<p>Learners can join the Prefects Body, Interact Club, Debating Club, Patriotism Club, Writers Club and Scouting, as well as sports and cultural activities. See the Clubs page for what each one does.</p>', 'Clubs & societies', 'clubs.php'],
        ['Boarding & Daily Life', 'Is there religious life at the school?',
            '<p>AFSS is a Catholic school. Prayer, Mass and religious celebrations are part of school life, and every learner is treated with respect whatever their Christian tradition.</p>', 'Faith formation', 'page.php?slug=faith-formation'],
        ['Boarding & Daily Life', 'How does student leadership work?',
            '<p>Student voice is carried by the Prefects Body and the UNSA school chapter, guided by patron teachers. The Student Leadership page explains the structure and how leaders are chosen.</p>', 'Student leadership', 'page.php?slug=student-leadership'],
        ['Boarding & Daily Life', 'When can parents visit?',
            '<p>Visitation days are announced on the school calendar. Please check the Events page and confirm with the school office before travelling.</p>', 'Events & calendar', 'events.php'],

        ['Academics', 'Which examinations do learners sit?',
            '<p>Learners sit the Uganda Certificate of Education (UCE) at the end of Senior 4 and the Uganda Advanced Certificate of Education (UACE) at the end of Senior 6. AFSS is UNEB centre <strong>{{uneb}}</strong>.</p>', 'Academics', 'page.php?slug=academics'],
        ['Academics', 'What departments does the school have?',
            '<p>The school has departments of Physical Sciences, Biological Sciences, Business Studies, Vocational Studies, Humanities and Languages.</p>', 'Departments', 'page.php?slug=departments'],
        ['Academics', 'Does the school have a library and e-learning?',
            '<p>Yes. Learners use the school library and e-learning resources to support their lessons. Ask the school office who can use each service and how to get help.</p>', 'Library services', 'page.php?slug=library-services'],
        ['Academics', 'How is progress assessed and reported?',
            '<p>Learners have regular tests and assignments, term examinations and, for candidates, mock examinations. Reports are issued each term, and parents meet teachers at PTA meetings.</p>', 'Teaching and learning', 'page.php?slug=teaching-and-learning'],
        ['Academics', 'Who are the teachers?',
            '<p>You can meet the teaching team, with their subjects and short biographies, on the Our Staff page.</p>', 'Our staff', 'staff.php'],

        ['Student Welfare & Safety', 'What health services are available?',
            '<p>The school has health services run by the school nurse for learners who are unwell. Learners and parents can use the <strong>Seek help</strong> form on the Health Services page at any time.</p>', 'Health services', 'health.php'],
        ['Student Welfare & Safety', 'How can a learner get counselling or career guidance?',
            '<p>Counselling and career guidance are available through the Counselling page, where learners and parents can send a confidential request to the counsellor.</p>', 'Counselling', 'counselling.php'],
        ['Student Welfare & Safety', 'How do I report a concern, anonymously if I want?',
            '<p>Use the <strong>Report a Concern</strong> page. You can choose a type of concern or type your own, and you may stay anonymous. You receive a tracking key so that you can follow up later without giving your name.</p>', 'Report a concern', 'whistleblower.php'],
        ['Student Welfare & Safety', 'How does the school protect learners?',
            '<p>The school is committed to protecting learners from harm. See the Health, Safety and Safeguarding page and the Privacy Policy for how concerns and personal information are handled.</p>', 'Safeguarding', 'page.php?slug=safeguarding'],
        ['Student Welfare & Safety', 'Who do I call in an emergency?',
            '<p>Call the school on <strong>{{phone}}</strong>. For a learner who is unwell, speak to the school nurse or the class teacher.</p>', 'Contact', 'contact.php'],

        ['Alumni', 'How do I join the alumni association?',
            '<p>Fill in the registration form on the Alumni page with your details, photo, years at AFSS and (if required) proof of payment, and confirm that you will abide by the AFSSOSA constitution. Your membership is reviewed and approved by the alumni office.</p>', 'Join the alumni', 'alumni.php#join'],
        ['Alumni', 'How do I get my alumni membership card?',
            '<p>Once approved you receive a membership number. On the Alumni page, open <strong>Get my card</strong>, enter your membership number and the email you registered with, and your card opens ready to print or save as PDF.</p>', 'Get my card', 'alumni.php#card'],
        ['Alumni', 'How can someone check that an alumni card is genuine?',
            '<p>Enter the membership number on the verify page, or scan the QR code on the back of the card. The page confirms the name, class and whether the membership is still valid.</p>', 'Verify a card', 'verify.php'],
        ['Alumni', 'What alumni events are there?',
            '<p>Activities include the AFSSOSA League, the Annual General Meeting and the Alumni Dinner. Dates and countdowns appear on the Alumni page.</p>', 'Alumni activities', 'alumni.php#events'],

        ['Giving & Partnerships', 'How can I support the school?',
            '<p>Well-wishers, companies, churches and partners can donate or partner with the school through the Donate and Partner page, which lists the official ways to give and the causes you can support.</p>', 'Donate & partner', 'donate.php'],
        ['Giving & Partnerships', 'How do I know a donation channel is genuine?',
            '<p>Use only the bank accounts, mobile money merchant codes and payment links shown on the Donate page, and confirm with the school if in doubt. We never ask for donations by private message.</p>', 'Donate page', 'donate.php'],
        ['Giving & Partnerships', 'What is AFRUDEC?',
            '<p>The Archbishop Flynn Rural Development Education Center (AFRUDEC) is linked to the school\'s agriculture, food security and sustainability work.</p>', 'AFRUDEC', 'page.php?slug=afrudec'],

        ['Jobs & Careers', 'Are there job vacancies?',
            '<p>Open vacancies are listed on the Careers page. You can read the description and requirements, then apply online with your CV.</p>', 'Careers', 'careers.php'],
        ['Jobs & Careers', 'How do I apply for a job?',
            '<p>Open the vacancy, complete the short form and attach your CV (PDF or Word). Shortlisted candidates are contacted by the school.</p>', 'View vacancies', 'careers.php'],

        ['Website & Contact', 'How do I contact the school?',
            '<p>Phone <strong>{{phone}}</strong>, email <a href="mailto:{{email}}">{{email}}</a>, or visit us: {{address}}. You can also use the contact form or chat on WhatsApp.</p>', 'Contact page', 'contact.php'],
        ['Website & Contact', 'Is there a WhatsApp support line?',
            '<p>Yes. Use the green <strong>WhatsApp support</strong> button on the website to start a chat with the school\'s support line.</p>', 'Contact page', 'contact.php'],
        ['Website & Contact', 'Where can I find news, events and the newsletter?',
            '<p>The News page has stories and announcements, the Events page has the calendar (with Uganda public holidays and countdowns), and the Monthly Newsletter page has each issue. You can subscribe by email from any page.</p>', 'News & stories', 'news.php'],
        ['Website & Contact', 'Who are the Achievers of the month?',
            '<p>Each month the school recognises a staff member and a student. See them in the Achievers Corner.</p>', 'Achievers Corner', 'achievers.php'],
        ['Website & Contact', 'Where can I see photos of school life?',
            '<p>The Photo Gallery is organised by event, such as sports day, music, dance and drama day, and visitation day. You can filter by year or event.</p>', 'Photo gallery', 'gallery.php'],
        ['Website & Contact', 'How is my personal information used?',
            '<p>We collect only what is needed to handle your enquiry or application. Read the Privacy Policy for details.</p>', 'Privacy policy', 'page.php?slug=privacy'],
    ];
}

/** Fills the {{phone}}-style tokens used in the answers. */
function faq_tokens(string $html): string
{
    return strtr($html, [
        '{{phone}}' => e(setting('phone')), '{{email}}' => e(setting('email')), '{{address}}' => e(setting('address')),
        '{{uneb}}' => e(setting('uneb_centre') ?: 'U2848'), '{{intake}}' => e(setting('intake_text')),
    ]);
}
