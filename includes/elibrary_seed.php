<?php
/**
 * e-Library: links to free, openly available O and A level learning material. The school does not host or copy these
 * resources; each card opens the publisher's own website. Staff can add, edit or hide any entry under Admin → e-Library.
 */
const ELIB_LEVELS = ['O & A Level', 'O Level', 'A Level', 'Teachers'];
const ELIB_SUBJECTS = ['All subjects', 'Mathematics', 'Physics', 'Chemistry', 'Biology', 'General science', 'English & literature', 'Economics & business', 'History & geography', 'ICT & computing', 'Languages'];
const ELIB_KINDS = ['Official curriculum', 'Past papers & exams', 'Textbooks', 'Notes & revision', 'Videos & lessons', 'Simulations & tools', 'Free courses', 'Reading & books'];

function elib_seed(): array
{
    // [title, description, url, source, level, subject, kind, featured]
    return [
        ['NCDC Resource Centre: secondary school', 'Official downloads from the National Curriculum Development Centre for secondary schools, including assessment guidelines and e-learning materials.', 'https://ncdc.go.ug/resource/?_cart_1_2=secondary', 'National Curriculum Development Centre (Uganda)', 'O & A Level', 'All subjects', 'Official curriculum', 1],
        ['NCDC: National Curriculum Development Centre', 'The home of Uganda\'s curriculum. Syllabi, news and teaching guidance for O and A level.', 'https://ncdc.go.ug/', 'National Curriculum Development Centre (Uganda)', 'Teachers', 'All subjects', 'Official curriculum', 0],
        ['UNEB: Uganda National Examinations Board', 'The official source for UCE and UACE examination information, regulations, results and past papers.', 'https://uneb.ac.ug/', 'UNEB (Uganda)', 'O & A Level', 'All subjects', 'Past papers & exams', 1],
        ['Ministry of Education and Sports', 'Official policy, circulars and learning resources from Uganda\'s Ministry of Education and Sports.', 'https://www.education.go.ug/', 'Ministry of Education and Sports (Uganda)', 'Teachers', 'All subjects', 'Official curriculum', 0],

        ['Khan Academy', 'Free video lessons and practice exercises in mathematics, physics, chemistry, biology, economics and more. Learn at your own pace.', 'https://www.khanacademy.org/', 'Khan Academy', 'O & A Level', 'All subjects', 'Videos & lessons', 1],
        ['Khan Academy: economics and finance', 'Free lessons on microeconomics, macroeconomics and personal finance, useful for A level economics.', 'https://www.khanacademy.org/economics-finance-domain', 'Khan Academy', 'A Level', 'Economics & business', 'Videos & lessons', 0],
        ['OpenStax', 'Free, peer-reviewed textbooks in physics, chemistry, biology, mathematics and economics. Read online or download. Some books are advanced and best for A level.', 'https://openstax.org/', 'OpenStax, Rice University', 'A Level', 'General science', 'Textbooks', 1],
        ['Siyavula', 'Free, open mathematics and science textbooks written for African learners, with worked examples and practice.', 'https://www.siyavula.com/', 'Siyavula', 'O Level', 'General science', 'Textbooks', 1],
        ['LibreTexts', 'A large library of free open textbooks and notes across sciences, mathematics, engineering and social science.', 'https://www.libretexts.org/', 'LibreTexts', 'A Level', 'General science', 'Textbooks', 0],
        ['Wikibooks', 'Free, community-written open textbooks on many subjects, from mathematics to computing.', 'https://en.wikibooks.org/wiki/Main_Page', 'Wikimedia Foundation', 'O & A Level', 'All subjects', 'Textbooks', 0],

        ['BBC Bitesize', 'Short revision notes, videos and quizzes. Based on the UK curriculum, so use it alongside the Ugandan syllabus.', 'https://www.bbc.co.uk/bitesize', 'BBC', 'O Level', 'All subjects', 'Notes & revision', 1],
        ['BBC Bitesize: A level and equivalent', 'Revision guides and videos for advanced-level study (UK curriculum).', 'https://www.bbc.co.uk/bitesize/levels/z98jmp3', 'BBC', 'A Level', 'All subjects', 'Notes & revision', 0],
        ['Oak National Academy', 'Free, complete video lessons with slides and worksheets across school subjects (UK curriculum).', 'https://www.thenational.academy/', 'Oak National Academy', 'O Level', 'All subjects', 'Videos & lessons', 0],
        ['Physics & Maths Tutor', 'Free revision notes, topic questions and past exam papers for A level sciences and mathematics.', 'https://www.physicsandmathstutor.com/', 'Physics & Maths Tutor', 'A Level', 'Physics', 'Notes & revision', 0],
        ['The Physics Classroom', 'Clear, free tutorials, animations and practice problems for physics concepts.', 'https://www.physicsclassroom.com/', 'The Physics Classroom', 'O & A Level', 'Physics', 'Notes & revision', 0],
        ['Chemguide', 'Detailed, free explanations of chemistry topics for advanced-level learners.', 'https://www.chemguide.co.uk/', 'Chemguide', 'A Level', 'Chemistry', 'Notes & revision', 0],
        ['Maths Is Fun', 'Plain-language maths explanations, examples and puzzles from basic to advanced.', 'https://www.mathsisfun.com/', 'Maths Is Fun', 'O Level', 'Mathematics', 'Notes & revision', 0],
        ['tutor2u', 'Free study notes, videos and quizzes for economics, business and other subjects (some content is paid).', 'https://www.tutor2u.net/', 'tutor2u', 'A Level', 'Economics & business', 'Notes & revision', 0],
        ['World History Encyclopedia', 'Free, well-sourced articles, maps and timelines on world and African history.', 'https://www.worldhistory.org/', 'World History Encyclopedia', 'O & A Level', 'History & geography', 'Reading & books', 0],

        ['PhET Interactive Simulations', 'Free, interactive science and maths simulations: circuits, waves, chemical reactions, cells and more. Many work offline once downloaded.', 'https://phet.colorado.edu/', 'University of Colorado Boulder', 'O & A Level', 'General science', 'Simulations & tools', 1],
        ['GeoGebra', 'Free tools for graphing, geometry, algebra and calculus, with ready-made classroom activities.', 'https://www.geogebra.org/', 'GeoGebra', 'O & A Level', 'Mathematics', 'Simulations & tools', 0],
        ['Desmos graphing calculator', 'A free online graphing calculator for functions, tables and equations.', 'https://www.desmos.com/calculator', 'Desmos', 'O & A Level', 'Mathematics', 'Simulations & tools', 0],
        ['Kolibri', 'A free learning platform that works offline on school computers, with video lessons and exercises. Ideal where internet is limited.', 'https://learningequality.org/kolibri/', 'Learning Equality', 'Teachers', 'All subjects', 'Simulations & tools', 0],

        ['Crash Course (YouTube)', 'Fast, engaging video courses in chemistry, biology, physics, economics, history, literature and more.', 'https://www.youtube.com/@crashcourse', 'Crash Course', 'O & A Level', 'All subjects', 'Videos & lessons', 0],
        ['3Blue1Brown (YouTube)', 'Beautiful visual explanations of mathematics concepts such as calculus and linear algebra.', 'https://www.youtube.com/@3blue1brown', '3Blue1Brown', 'A Level', 'Mathematics', 'Videos & lessons', 0],
        ['MIT OpenCourseWare', 'Free university course materials. Excellent for stretching strong A level learners and for teachers.', 'https://ocw.mit.edu/', 'Massachusetts Institute of Technology', 'A Level', 'All subjects', 'Free courses', 0],
        ['Alison free online courses', 'Free certificate courses in ICT, business, languages and more.', 'https://www.alison.com/', 'Alison', 'A Level', 'ICT & computing', 'Free courses', 0],
        ['edX', 'Free-to-audit university courses from leading institutions.', 'https://www.edx.org/', 'edX', 'A Level', 'All subjects', 'Free courses', 0],

        ['BBC Learning English', 'Free lessons, vocabulary, grammar and listening practice to improve English.', 'https://www.bbc.co.uk/learningenglish', 'BBC', 'O & A Level', 'English & literature', 'Notes & revision', 0],
        ['Project Gutenberg', 'Over 70,000 free e-books, including classic literature that is out of copyright.', 'https://www.gutenberg.org/', 'Project Gutenberg', 'O & A Level', 'English & literature', 'Reading & books', 0],
        ['Internet Archive', 'A huge free library of books, audio and video. Borrow or read online.', 'https://archive.org/', 'Internet Archive', 'O & A Level', 'All subjects', 'Reading & books', 0],
        ['Worldreader', 'Free e-books and reading programmes for readers in Africa and beyond.', 'https://www.worldreader.org/', 'Worldreader', 'O Level', 'English & literature', 'Reading & books', 0],
        ['Deutsche Welle: learn German', 'Free courses for learning German, useful for the language options.', 'https://www.dw.com/en/learn-german/s-2469', 'Deutsche Welle', 'O & A Level', 'Languages', 'Free courses', 0],
    ];
}
