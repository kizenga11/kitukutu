<?php
session_start();
include "includes/config.php";

// Allow CLI execution directly, or admin access via browser
if (php_sapi_name() !== 'cli') {
    if (!isset($_SESSION['admin_id'])) {
        die("Access denied. Admin only.");
    }
    $check = mysqli_query($conn, "SELECT role FROM admins WHERE id='{$_SESSION['admin_id']}'");
    $d = mysqli_fetch_assoc($check);
    if (!$d || $d['role'] !== 'admin') {
        die("Access denied. Admin only.");
    }
}

echo "<pre style='font-size:13px;font-family:system-ui;'>";

// ─────────────────────────────────────────────────────────
// 1. CREATE TABLE
// ─────────────────────────────────────────────────────────
$create = "CREATE TABLE IF NOT EXISTS `lesson_plan_syllabus` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subject_id` int NOT NULL,
  `form_level` enum('Form One','Form Two','Form Three','Form Four') NOT NULL,
  `topic_name` varchar(255) NOT NULL,
  `main_competence` text NOT NULL,
  `specific_competence` text NOT NULL,
  `main_activity` text NOT NULL,
  `action_word` varchar(50) NOT NULL,
  `learning_activities` text DEFAULT NULL,
  `suggested_resources` text DEFAULT NULL,
  `no_of_periods` int DEFAULT NULL,
  `reference` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lps_subject` (`subject_id`),
  KEY `idx_lps_form` (`form_level`),
  CONSTRAINT `fk_lps_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if (mysqli_query($conn, $create)) {
    echo "✓ Table `lesson_plan_syllabus` created successfully.\n\n";
} else {
    echo "✗ Error creating table: " . mysqli_error($conn) . "\n\n";
    exit;
}

// ─────────────────────────────────────────────────────────
// 2. CLEAR EXISTING DATA (fresh seed)
// ─────────────────────────────────────────────────────────
mysqli_query($conn, "TRUNCATE TABLE lesson_plan_syllabus");

// ─────────────────────────────────────────────────────────
// 3. SEED DATA – TIE 2023 CURRICULUM
// ─────────────────────────────────────────────────────────
// subject_id mapping (from existing `subjects` table):
//   5 = Mathematics (GENERAL, COMPULSORY)
//   6 = Kiswahili (GENERAL, COMPULSORY)
//   7 = English (GENERAL, COMPULSORY)
//   8 = Geography (GENERAL, COMPULSORY)
//  11 = Physics (GENERAL, OPTIONAL)
//  12 = Chemistry (GENERAL, OPTIONAL)
//  13 = Biology (GENERAL, OPTIONAL)
//  26 = Historia ya Tanzania na Maadili (GENERAL, COMPULSORY)
//  14 = Mathematics (VOCATIONAL, COMPULSORY)
//  16 = English (VOCATIONAL, COMPULSORY)
//  15 = Business Studies (VOCATIONAL, COMPULSORY)
//  17 = Engineering Science (VOCATIONAL, COMPULSORY)
//  18 = Historia ya Tanzania na Maadili (VOCATIONAL, COMPULSORY)
//  19 = Technical Drawing (VOCATIONAL, COMPULSORY)
//  20 = Computer Application with CAD (VOCATIONAL, COMPULSORY)
//  21 = Life Skills (VOCATIONAL, COMPULSORY)
//  22 = Electrical Installation (VOCATIONAL, OPTIONAL)
//  23 = Electronics Repair (VOCATIONAL, OPTIONAL)
//  24 = Computer Programming (VOCATIONAL, OPTIONAL)
//  25 = Masonry and Bricklaying (VOCATIONAL, OPTIONAL)
//  27 = Computer Science (GENERAL, OPTIONAL)
//   9 = Business Studies (GENERAL, COMPULSORY)

$ref = fn($s, $f) => "Tanzania Institute of Education. (2023). $s for secondary schools student's book, $f. Tanzania Institute of Education.";
$f1 = 'Form One';
$f2 = 'Form Two';
$f3 = 'Form Three';
$f4 = 'Form Four';

function insert($conn, $sid, $fl, $topic, $mc, $sc, $ma, $aw, $nop, $ref, $la = null, $sr = null) {
    $topic = mysqli_real_escape_string($conn, $topic);
    $mc = mysqli_real_escape_string($conn, $mc);
    $sc = mysqli_real_escape_string($conn, $sc);
    $ma = mysqli_real_escape_string($conn, $ma);
    $aw = mysqli_real_escape_string($conn, $aw);
    $ref = mysqli_real_escape_string($conn, $ref);
    $fl = mysqli_real_escape_string($conn, $fl);
    $la = $la ? "'" . mysqli_real_escape_string($conn, $la) . "'" : 'NULL';
    $sr = $sr ? "'" . mysqli_real_escape_string($conn, $sr) . "'" : 'NULL';
    $nop = $nop ?: 'NULL';
    $q = "INSERT INTO lesson_plan_syllabus (subject_id, form_level, topic_name, main_competence, specific_competence, main_activity, action_word, learning_activities, suggested_resources, no_of_periods, reference) VALUES ($sid, '$fl', '$topic', '$mc', '$sc', '$ma', '$aw', $la, $sr, $nop, '$ref')";
    return mysqli_query($conn, $q);
}

$count = 0;

// ═════════════════════════════════════════════════════════
// MATHEMATICS – GENERAL (subject_id = 5)
// ═════════════════════════════════════════════════════════
$r = $ref('Mathematics', '%s');
$data = [];

$data[] = [5, $f1, 'Numbers', 'Demonstrate understanding of numbers', 'Identify and write numbers in words and figures', 'Explain the concept of whole numbers and place values', 'Explain', 8, sprintf($r, $f1)];
$data[] = [5, $f1, 'Fractions', 'Demonstrate competence in fractions', 'Add, subtract, multiply and divide fractions', 'Perform operations on fractions', 'Calculate', 10, sprintf($r, $f1)];
$data[] = [5, $f1, 'Decimals and Percentages', 'Apply decimals and percentages in real life', 'Convert between fractions, decimals and percentages', 'Convert fractions to decimals and percentages', 'Convert', 8, sprintf($r, $f1)];
$data[] = [5, $f1, 'Algebraic Expressions', 'Simplify and solve algebraic expressions', 'Simplify algebraic expressions by collecting like terms', 'Simplify given algebraic expressions', 'Simplify', 10, sprintf($r, $f1)];
$data[] = [5, $f1, 'Linear Equations', 'Solve linear equations in one variable', 'Solve linear equations using balancing method', 'Solve linear equations step by step', 'Solve', 8, sprintf($r, $f1)];
$data[] = [5, $f1, 'Geometry', 'Apply basic geometric concepts', 'Identify and classify angles and triangles', 'Classify different types of angles and triangles', 'Classify', 10, sprintf($r, $f1)];
$data[] = [5, $f1, 'Perimeter and Area', 'Calculate perimeter and area of plane figures', 'Calculate perimeter and area of rectangles and triangles', 'Calculate perimeter and area of given shapes', 'Calculate', 8, sprintf($r, $f1)];
$data[] = [5, $f1, 'Data Handling', 'Interpret and present data', 'Collect, organize and interpret data using tables and charts', 'Interpret data presented in tables and bar charts', 'Interpret', 6, sprintf($r, $f1)];
$data[] = [5, $f1, 'Time and Speed', 'Apply concepts of time, distance and speed', 'Calculate distance, speed and time', 'Calculate speed given distance and time', 'Calculate', 6, sprintf($r, $f1)];
$data[] = [5, $f1, 'Money and Currency', 'Apply financial arithmetic in real life', 'Calculate profit, loss and discount', 'Calculate profit and loss from given transactions', 'Calculate', 6, sprintf($r, $f1)];

$data[] = [5, $f2, 'Exponents and Radicals', 'Apply laws of exponents and radicals', 'Simplify expressions using laws of exponents', 'Simplify exponential expressions', 'Simplify', 10, sprintf($r, $f2)];
$data[] = [5, $f2, 'Algebraic Expressions II', 'Manipulate algebraic expressions', 'Factorise algebraic expressions', 'Factorise given algebraic expressions', 'Factorise', 10, sprintf($r, $f2)];
$data[] = [5, $f2, 'Linear Inequalities', 'Solve linear inequalities', 'Solve linear inequalities in one variable and represent on number line', 'Solve linear inequalities and graph them', 'Solve', 8, sprintf($r, $f2)];
$data[] = [5, $f2, 'Ratio and Proportion', 'Apply ratio and proportion in real life', 'Solve problems involving direct and inverse proportion', 'Calculate using direct proportion method', 'Calculate', 6, sprintf($r, $f2)];
$data[] = [5, $f2, 'Pythagoras Theorem', 'Apply Pythagoras theorem in right-angled triangles', 'Calculate the hypotenuse using Pythagoras theorem', 'Calculate the missing side of a right-angled triangle', 'Calculate', 6, sprintf($r, $f2)];
$data[] = [5, $f2, 'Trigonometry', 'Apply trigonometric ratios in right-angled triangles', 'Use sine, cosine and tangent to find angles and sides', 'Calculate angles using trigonometric ratios', 'Calculate', 8, sprintf($r, $f2)];
$data[] = [5, $f2, 'Coordinate Geometry', 'Plot points and draw graphs', 'Plot points on a Cartesian plane and find distance between two points', 'Plot given coordinates on a Cartesian plane', 'Plot', 6, sprintf($r, $f2)];
$data[] = [5, $f2, 'Volume and Surface Area', 'Calculate volume and surface area of solids', 'Calculate volume of cubes, cuboids and cylinders', 'Calculate the volume of a cylinder', 'Calculate', 8, sprintf($r, $f2)];
$data[] = [5, $f2, 'Similarity and Congruence', 'Apply similarity and congruence concepts', 'Identify and prove congruent triangles', 'Identify congruent triangles using SSS, SAS, ASA', 'Identify', 8, sprintf($r, $f2)];

$data[] = [5, $f3, 'Quadratic Equations', 'Solve quadratic equations', 'Solve quadratic equations by factorization and formula', 'Solve quadratic equations using the quadratic formula', 'Solve', 12, sprintf($r, $f3)];
$data[] = [5, $f3, 'Functions and Relations', 'Understand functions and their graphs', 'Define and evaluate functions', 'Evaluate a given function for specific values', 'Evaluate', 10, sprintf($r, $f3)];
$data[] = [5, $f3, 'Statistics', 'Interpret statistical data', 'Calculate mean, median and mode', 'Calculate the mean of grouped data', 'Calculate', 10, sprintf($r, $f3)];
$data[] = [5, $f3, 'Probability', 'Apply probability in real life', 'Calculate probability of simple events', 'Calculate the probability of an event occurring', 'Calculate', 8, sprintf($r, $f3)];
$data[] = [5, $f3, 'Sequences and Series', 'Identify patterns in sequences', 'Find the nth term of arithmetic progression', 'Determine the nth term of a given sequence', 'Determine', 8, sprintf($r, $f3)];
$data[] = [5, $f3, 'Circles', 'Apply circle theorems', 'Calculate circumference and area of a circle', 'Calculate the area of a circle given its radius', 'Calculate', 8, sprintf($r, $f3)];

$data[] = [5, $f4, 'Differentiation', 'Apply basic differentiation', 'Find derivatives of polynomial functions', 'Differentiate polynomial functions', 'Differentiate', 12, sprintf($r, $f4)];
$data[] = [5, $f4, 'Integration', 'Apply basic integration', 'Find integrals of simple polynomial functions', 'Integrate polynomial functions', 'Integrate', 12, sprintf($r, $f4)];
$data[] = [5, $f4, 'Vectors', 'Apply vector concepts', 'Add and subtract vectors in two dimensions', 'Calculate vector addition and subtraction', 'Calculate', 10, sprintf($r, $f4)];
$data[] = [5, $f4, 'Matrices', 'Perform matrix operations', 'Add, subtract and multiply matrices', 'Multiply two matrices', 'Calculate', 10, sprintf($r, $f4)];
$data[] = [5, $f4, 'Trigonometric Functions', 'Apply trigonometric functions', 'Graph sine, cosine and tangent functions', 'Sketch graphs of trigonometric functions', 'Sketch', 10, sprintf($r, $f4)];
$data[] = [5, $f4, 'Three-Dimensional Geometry', 'Apply 3D geometric concepts', 'Calculate surface area and volume of prisms, pyramids and spheres', 'Calculate the volume of a sphere', 'Calculate', 8, sprintf($r, $f4)];

// ═════════════════════════════════════════════════════════
// ENGLISH – GENERAL (subject_id = 7)
// ═════════════════════════════════════════════════════════
$r = $ref('English', '%s');
$data[] = [7, $f1, 'Parts of Speech', 'Demonstrate understanding of parts of speech', 'Identify and use nouns, verbs, adjectives and adverbs', 'Identify parts of speech in given sentences', 'Identify', 10, sprintf($r, $f1)];
$data[] = [7, $f1, 'Tenses', 'Use correct tenses in communication', 'Use present, past and future tenses correctly', 'Construct sentences using different tenses', 'Construct', 10, sprintf($r, $f1)];
$data[] = [7, $f1, 'Sentence Structure', 'Construct correct sentences', 'Identify subject, verb and object in sentences', 'Analyse sentence structure', 'Analyse', 8, sprintf($r, $f1)];
$data[] = [7, $f1, 'Reading Comprehension', 'Comprehend written texts', 'Read and answer questions from a passage', 'Answer comprehension questions from a given passage', 'Answer', 10, sprintf($r, $f1)];
$data[] = [7, $f1, 'Composition Writing', 'Write coherent paragraphs', 'Write a short paragraph on a given topic', 'Write a descriptive paragraph', 'Write', 8, sprintf($r, $f1)];
$data[] = [7, $f1, 'Vocabulary', 'Expand vocabulary for effective communication', 'Use new words in sentences', 'Define and use given vocabulary words in sentences', 'Define', 6, sprintf($r, $f1)];
$data[] = [7, $f1, 'Listening and Speaking', 'Communicate effectively in English', 'Pronounce words correctly and participate in discussions', 'Describe events and experiences orally', 'Describe', 8, sprintf($r, $f1)];

$data[] = [7, $f2, 'Active and Passive Voice', 'Use active and passive voice correctly', 'Convert sentences from active to passive voice', 'Transform active voice sentences to passive', 'Transform', 8, sprintf($r, $f2)];
$data[] = [7, $f2, 'Reported Speech', 'Use reported speech in communication', 'Convert direct speech to reported speech', 'Change direct speech to indirect speech', 'Change', 8, sprintf($r, $f2)];
$data[] = [7, $f2, 'Conditional Sentences', 'Use conditional sentences', 'Identify and construct type 1, 2 and 3 conditionals', 'Construct conditional sentences', 'Construct', 8, sprintf($r, $f2)];
$data[] = [7, $f2, 'Poetry', 'Analyse simple poems', 'Identify poetic devices in a poem', 'Analyse the rhyme scheme in a poem', 'Analyse', 6, sprintf($r, $f2)];
$data[] = [7, $f2, 'Letter Writing', 'Write formal and informal letters', 'Write a formal letter using correct format', 'Write a letter of application', 'Write', 8, sprintf($r, $f2)];
$data[] = [7, $f2, 'Summary Writing', 'Summarise written texts', 'Summarise a passage in own words', 'Summarise a given passage in three sentences', 'Summarise', 8, sprintf($r, $f2)];

$data[] = [7, $f3, 'Debate and Discussion', 'Participate in debates and discussions', 'Present arguments on a given topic', 'Argue for or against a motion', 'Argue', 8, sprintf($r, $f3)];
$data[] = [7, $f3, 'Public Speaking', 'Deliver effective speeches', 'Prepare and deliver a speech', 'Deliver a speech on a given topic', 'Deliver', 8, sprintf($r, $f3)];
$data[] = [7, $f3, 'Essay Writing', 'Write well-structured essays', 'Write a five-paragraph essay with introduction, body and conclusion', 'Write an argumentative essay', 'Write', 10, sprintf($r, $f3)];
$data[] = [7, $f3, 'Literature Analysis', 'Analyse prose and drama', 'Identify themes, characters and plot in a literary work', 'Analyse the main character in a novel', 'Analyse', 10, sprintf($r, $f3)];

$data[] = [7, $f4, 'Research and Report Writing', 'Conduct research and write reports', 'Write a research report with findings and recommendations', 'Write a report on a given topic', 'Write', 10, sprintf($r, $f4)];
$data[] = [7, $f4, 'Advanced Composition', 'Write complex compositions', 'Write an essay using varied sentence structures', 'Write a persuasive essay', 'Write', 10, sprintf($r, $f4)];
$data[] = [7, $f4, 'Critical Reading', 'Critically analyse texts', 'Evaluate arguments in a text and form own opinion', 'Critique the main argument in an article', 'Critique', 8, sprintf($r, $f4)];

// ═════════════════════════════════════════════════════════
// KISWAHILI – GENERAL (subject_id = 6)
// ═════════════════════════════════════════════════════════
$r = $ref('Kiswahili', '%s');
$data[] = [6, $f1, 'Msamiati', 'Kujenga msamiati wa Kiswahili', 'Kutumia msamiati katika mawasiliano', 'Eleza maana ya misamiati mipya', 'Eleza', 8, sprintf($r, $f1)];
$data[] = [6, $f1, 'Ngeli za Nomino', 'Kutumia ngeli za nomino kwa usahihi', 'Kutambua na kutumia ngeli za nomino', 'Tambua ngeli za nomino katika sentensi', 'Tambua', 10, sprintf($r, $f1)];
$data[] = [6, $f1, 'Wakati wa Kiswahili', 'Kutumia nyakati kwa usahihi', 'Kutambua na kutumia nyakati mbalimbali', 'Unga sentensi kwa kutumia nyakati', 'Unga', 10, sprintf($r, $f1)];
$data[] = [6, $f1, 'Uandishi wa Insha', 'Kuandika insha fupi', 'Kuandika insha ya aina mbalimbali', 'Andika insha ya maelezo', 'Andika', 8, sprintf($r, $f1)];
$data[] = [6, $f1, 'Kusoma na Kuelewa', 'Kuelewa maandishi mbalimbali', 'Kusoma na kujibu maswali kutoka kwenye kifungu', 'Jibu maswali kutoka kwenye kifungu ulichosoma', 'Jibu', 8, sprintf($r, $f1)];
$data[] = [6, $f1, 'Fasihi Simulizi', 'Kuthamini fasihi simulizi', 'Kutambua aina za fasihi simulizi', 'Eleza aina za fasihi simulizi', 'Eleza', 6, sprintf($r, $f1)];
$data[] = [6, $f1, 'Semi za Kiswahili', 'Kutumia semi za Kiswahili', 'Kutambua na kutumia methali, nahau na vitendawili', 'Fafanua maana ya methali', 'Fafanua', 8, sprintf($r, $f1)];

$data[] = [6, $f2, 'Viambishi na Utoaji wa Maneno', 'Kutumia viambishi katika uundaji wa maneno', 'Kutambua viambishi awali na tamati', 'Bainisha viambishi katika maneno', 'Bainisha', 8, sprintf($r, $f2)];
$data[] = [6, $f2, 'Ushairi', 'Kuchambua mashairi', 'Kutambua vipengele vya ushairi', 'Chambua vipengele vya kishairi katika shairi', 'Chambua', 8, sprintf($r, $f2)];
$data[] = [6, $f2, 'Uandishi wa Barua', 'Kuandika barua rasmi na zisizo rasmi', 'Kuandika barua rasmi kwa kufuata muundo', 'Andika barua rasmi ya maombi', 'Andika', 6, sprintf($r, $f2)];
$data[] = [6, $f2, 'Matumizi ya Kamusi', 'Kutumia kamusi kwa ufanisi', 'Kutafuta maana na matumizi ya maneno katika kamusi', 'Tumia kamusi kutafuta maana ya maneno', 'Tumia', 4, sprintf($r, $f2)];
$data[] = [6, $f2, 'Ufupisho na Muhtasari', 'Kuandika muhtasari wa maandishi', 'Kufupisha kifungu kwa kutumia maneno binafsi', 'Fupisha kifungu ulichopewa', 'Fupisha', 8, sprintf($r, $f2)];

$data[] = [6, $f3, 'Nahau na Vitendawili', 'Kutumia nahau na vitendawili vyema', 'Kutambua maana ya nahau na vitendawili', 'Eleza maana ya nahau katika sentensi', 'Eleza', 6, sprintf($r, $f3)];
$data[] = [6, $f3, 'Majina ya Vitu na Vitendo', 'Kutumia nomino na vitendo kwa usahihi', 'Kutambua aina za majina na vitendo', 'Tambua nomino na vitendo katika sentensi', 'Tambua', 6, sprintf($r, $f3)];
$data[] = [6, $f3, 'Insha za Kitasari', 'Kuandika insha za hoja, maelezo na mjadala', 'Kuandika insha ya mjadala', 'Andika insha ya mjadala kuhusu suala la kijamii', 'Andika', 10, sprintf($r, $f3)];
$data[] = [6, $f3, 'Uhakiki wa Fasihi', 'Kuhakiki kazi za fasihi', 'Kuchambua maudhui na mandhari katika riwaya', 'Chambua maudhui ya riwaya uliyoisoma', 'Chambua', 10, sprintf($r, $f3)];
$data[] = [6, $f3, 'Makala na Tahariri', 'Kuandika makala na tahariri', 'Kuandika makala kuhusu suala la kijamii', 'Andika makala kuhusu elimu ya wasichana', 'Andika', 8, sprintf($r, $f3)];

$data[] = [6, $f4, 'Fonolojia na Mofolojia', 'Kuelewa fonolojia na mofolojia ya Kiswahili', 'Kutambua sauti na muundo wa maneno', 'Bainisha sauti za Kiswahili', 'Bainisha', 8, sprintf($r, $f4)];
$data[] = [6, $f4, 'Semantiki na Matumizi ya Lugha', 'Kutumia semantiki katika mawasiliano', 'Kutambua maana dhahiri na maana fiche', 'Fafanua maana ya maneno katika muktadha', 'Fafanua', 8, sprintf($r, $f4)];
$data[] = [6, $f4, 'Tafsiri na Ukalimani', 'Kufanya tafsiri na ukalimani', 'Kutafsiri kifungu kutoka Kiswahili hadi Kiingereza', 'Tafsiri kifungu ulichopewa', 'Tafsiri', 10, sprintf($r, $f4)];
$data[] = [6, $f4, 'Uandishi wa Tasnifu', 'Kuandika tasnifu fupi ya utafiti', 'Kufanya utafiti mdogo na kuandika ripoti', 'Andika ripoti ya utafiti wako', 'Andika', 12, sprintf($r, $f4)];

// ═════════════════════════════════════════════════════════
// GEOGRAPHY – GENERAL (subject_id = 8)
// ═════════════════════════════════════════════════════════
$r = $ref('Geography', '%s');
$data[] = [8, $f1, 'The Solar System', 'Understand the solar system and its components', 'Describe the position and features of Earth in the solar system', 'Describe the planets in the solar system', 'Describe', 8, sprintf($r, $f1)];
$data[] = [8, $f1, 'Weather and Climate', 'Distinguish between weather and climate', 'Identify elements of weather and climate', 'Explain the difference between weather and climate', 'Explain', 8, sprintf($r, $f1)];
$data[] = [8, $f1, 'Map Reading and Interpretation', 'Read and interpret maps', 'Identify map symbols and scale', 'Interpret symbols on a topographic map', 'Interpret', 10, sprintf($r, $f1)];
$data[] = [8, $f1, 'Major Landforms', 'Identify major landforms on Earth', 'Describe the formation of mountains, valleys and plains', 'Describe the formation of fold mountains', 'Describe', 8, sprintf($r, $f1)];
$data[] = [8, $f1, 'Water Bodies', 'Identify major water bodies', 'Describe oceans, seas, lakes and rivers', 'Explain the importance of oceans', 'Explain', 6, sprintf($r, $f1)];
$data[] = [8, $f1, 'Population', 'Understand population distribution', 'Describe factors affecting population distribution', 'Explain population distribution in Tanzania', 'Explain', 6, sprintf($r, $f1)];

$data[] = [8, $f2, 'Rocks and Minerals', 'Classify rocks and minerals', 'Identify types of rocks and their formation', 'Classify rocks into igneous, sedimentary and metamorphic', 'Classify', 8, sprintf($r, $f2)];
$data[] = [8, $f2, 'Soil Formation', 'Understand soil formation processes', 'Describe soil profile and types of soil', 'Describe the layers of a soil profile', 'Describe', 6, sprintf($r, $f2)];
$data[] = [8, $f2, 'Agriculture', 'Understand agricultural systems', 'Identify types of agriculture and their characteristics', 'Compare subsistence and commercial farming', 'Compare', 8, sprintf($r, $f2)];
$data[] = [8, $f2, 'Forestry', 'Understand forestry and its importance', 'Identify types of forests and their uses', 'Describe the importance of forests to the environment', 'Describe', 6, sprintf($r, $f2)];
$data[] = [8, $f2, 'Transport and Communication', 'Understand transport and communication', 'Identify modes of transport and their advantages', 'Compare road, rail and air transport', 'Compare', 6, sprintf($r, $f2)];

$data[] = [8, $f3, 'Plate Tectonics', 'Understand plate tectonic theory', 'Describe the movement of tectonic plates', 'Explain the theory of continental drift', 'Explain', 8, sprintf($r, $f3)];
$data[] = [8, $f3, 'Earthquakes and Volcanoes', 'Understand earthquakes and volcanic activity', 'Describe causes and effects of earthquakes', 'Explain the formation of volcanoes', 'Explain', 8, sprintf($r, $f3)];
$data[] = [8, $f3, 'Climate Change', 'Understand climate change', 'Identify causes and effects of global warming', 'Explain how greenhouse gases cause global warming', 'Explain', 8, sprintf($r, $f3)];
$data[] = [8, $f3, 'Ecosystems and Biomes', 'Identify world ecosystems', 'Describe characteristics of major biomes', 'Describe the tropical rainforest biome', 'Describe', 8, sprintf($r, $f3)];
$data[] = [8, $f3, 'Urbanization', 'Understand urbanization', 'Describe causes and effects of urbanization', 'Explain the causes of rural-urban migration', 'Explain', 6, sprintf($r, $f3)];
$data[] = [8, $f3, 'Mining', 'Understand mining and mineral resources', 'Identify types of minerals and their uses', 'Describe the mining process of a selected mineral', 'Describe', 6, sprintf($r, $f3)];

$data[] = [8, $f4, 'Regional Geography of Tanzania', 'Understand the geography of Tanzania', 'Describe the physical and human geography of Tanzania', 'Describe the major economic activities in Tanzania', 'Describe', 10, sprintf($r, $f4)];
$data[] = [8, $f4, 'Regional Geography of Africa', 'Understand the geography of Africa', 'Describe selected African countries and their resources', 'Compare the economies of two African countries', 'Compare', 10, sprintf($r, $f4)];
$data[] = [8, $f4, 'Globalization', 'Understand globalization and its effects', 'Describe the impacts of globalization on developing countries', 'Explain the effects of globalization on Tanzania', 'Explain', 6, sprintf($r, $f4)];

// ═════════════════════════════════════════════════════════
// HISTORIA YA TANZANIA NA MAADILI – GENERAL (subject_id = 26)
// ═════════════════════════════════════════════════════════
$r = $ref('Historia ya Tanzania na Maadili', '%s');
$data[] = [26, $f1, 'Maana ya Historia', 'Kuelewa maana na umuhimu wa historia', 'Kufafanua historia na kueleza umuhimu wake', 'Eleza umuhimu wa kusoma historia', 'Eleza', 6, sprintf($r, $f1)];
$data[] = [26, $f1, 'Vyanzo vya Historia', 'Kutambua vyanzo vya historia', 'Kutambua aina za vyanzo vya historia', 'Tambua vyanzo vya historia simulizi', 'Tambua', 6, sprintf($r, $f1)];
$data[] = [26, $f1, 'Uhamiaji wa Wabantu', 'Kuelewa uhamiaji wa Wabantu Tanzania', 'Kueleza sababu za uhamiaji wa Wabantu', 'Eleza sababu za uhamiaji wa makabila ya Kibantu', 'Eleza', 8, sprintf($r, $f1)];
$data[] = [26, $f1, 'Maadili na Misingi ya Utaifa', 'Kuelewa maadili ya utaifa', 'Kueleza umuhimu wa uzalendo na utaifa', 'Fafanua dhana ya uzalendo', 'Fafanua', 6, sprintf($r, $f1)];
$data[] = [26, $f1, 'Haki na Wajibu', 'Kuelewa haki na wajibu wa raia', 'Kutambua haki na wajibu wa raia Tanzania', 'Eleza haki za msingi za raia', 'Eleza', 6, sprintf($r, $f1)];
$data[] = [26, $f1, 'Mawasiliano katika Jamii', 'Kuelewa umuhimu wa mawasiliano', 'Kueleza njia mbalimbali za mawasiliano', 'Eleza njia za mawasiliano katika jamii', 'Eleza', 4, sprintf($r, $f1)];
$data[] = [26, $f1, 'Ushirikiano wa Kimataifa', 'Kuelewa ushirikiano wa kimataifa', 'Kutambua mashirika ya kimataifa', 'Eleza majukumu ya Umoja wa Mataifa', 'Eleza', 6, sprintf($r, $f1)];

$data[] = [26, $f2, 'Maendeleo ya Teknolojia', 'Kuelewa maendeleo ya teknolojia kihistoria', 'Kueleza mabadiliko ya teknolojia katika historia', 'Eleza mabadiliko ya teknolojia ya kilimo', 'Eleza', 6, sprintf($r, $f2)];
$data[] = [26, $f2, 'Ukoloni Tanzania', 'Kuelewa athari za ukoloni Tanzania', 'Kueleza sababu na athari za ukoloni', 'Eleza sababu zilizosababisha ukoloni Tanzania', 'Eleza', 10, sprintf($r, $f2)];
$data[] = [26, $f2, 'Mapambano ya Uhuru', 'Kuelewa mapambano ya uhuru Tanzania', 'Kueleza hatua za mapambano ya uhuru', 'Eleza mchango wa Mwalimu Nyerere katika mapambano ya uhuru', 'Eleza', 8, sprintf($r, $f2)];
$data[] = [26, $f2, 'Katiba na Sheria', 'Kuelewa katiba na sheria za Tanzania', 'Kueleza vipengele vya katiba ya Tanzania', 'Fafanua nguzo za dola la Tanzania', 'Fafanua', 8, sprintf($r, $f2)];

$data[] = [26, $f3, 'Demokrasia Tanzania', 'Kuelewa demokrasia Tanzania', 'Kueleza maana na aina za demokrasia', 'Fafanua demokrasia ya vyama vingi', 'Fafanua', 8, sprintf($r, $f3)];
$data[] = [26, $f3, 'Uchaguzi Tanzania', 'Kuelewa mchakato wa uchaguzi', 'Kueleza hatua za mchakato wa uchaguzi', 'Eleza hatua za uchaguzi nchini Tanzania', 'Eleza', 8, sprintf($r, $f3)];
$data[] = [26, $f3, 'Utawala wa Sheria', 'Kuelewa utawala wa sheria', 'Kueleza umuhimu wa utawala wa sheria', 'Fafanua dhana ya utawala wa sheria', 'Fafanua', 6, sprintf($r, $f3)];
$data[] = [26, $f3, 'Sera za Maendeleo', 'Kuelewa sera za maendeleo Tanzania', 'Kueleza sera mbalimbali za maendeleo', 'Eleza sera ya elimu ya kufikia kila mtoto', 'Eleza', 6, sprintf($r, $f3)];

$data[] = [26, $f4, 'Jinsia na Maendeleo', 'Kuelewa usawa wa jinsia', 'Kueleza umuhimu wa usawa wa jinsia katika maendeleo', 'Eleza changamoto za usawa wa jinsia Tanzania', 'Eleza', 6, sprintf($r, $f4)];
$data[] = [26, $f4, 'Amani na Utatuzi wa Migogoro', 'Kuelewa amani na utatuzi wa migogoro', 'Kueleza sababu za migogoro na njia za utatuzi wake', 'Eleza njia za amani za utatuzi wa migogoro', 'Eleza', 6, sprintf($r, $f4)];
$data[] = [26, $f4, 'Maendeleo Endelevu', 'Kuelewa maendeleo endelevu', 'Kueleza malengo ya maendeleo endelevu', 'Fafanua malengo ya maendeleo endelevu (SDGs)', 'Fafanua', 6, sprintf($r, $f4)];

// ═════════════════════════════════════════════════════════
// BIOLOGY – GENERAL (subject_id = 13)
// ═════════════════════════════════════════════════════════
$r = $ref('Biology', '%s');
$data[] = [13, $f1, 'Introduction to Biology', 'Understand the scope of biology', 'Define biology and its branches', 'Explain the importance of studying biology', 'Explain', 4, sprintf($r, $f1)];
$data[] = [13, $f1, 'Cell Structure and Organization', 'Understand cell structure and function', 'Identify parts of a cell and their functions', 'Draw and label a plant cell', 'Draw', 10, sprintf($r, $f1)];
$data[] = [13, $f1, 'Classification of Living Things', 'Classify living organisms', 'Use the binomial system to name organisms', 'Classify given organisms into kingdoms', 'Classify', 8, sprintf($r, $f1)];
$data[] = [13, $f1, 'Nutrition', 'Understand nutrition in organisms', 'Describe the process of photosynthesis', 'Explain the importance of photosynthesis', 'Explain', 8, sprintf($r, $f1)];

$data[] = [13, $f2, 'Transport in Living Organisms', 'Understand transport systems', 'Describe the circulatory system in humans', 'Describe the structure of the human heart', 'Describe', 8, sprintf($r, $f2)];
$data[] = [13, $f2, 'Respiration', 'Understand respiration', 'Describe aerobic and anaerobic respiration', 'Compare aerobic and anaerobic respiration', 'Compare', 8, sprintf($r, $f2)];
$data[] = [13, $f2, 'Reproduction', 'Understand reproduction in organisms', 'Describe the human reproductive system', 'Describe the menstrual cycle', 'Describe', 10, sprintf($r, $f2)];

$data[] = [13, $f3, 'Genetics', 'Understand basic genetics', 'Describe Mendelian inheritance', 'Calculate genetic crosses using Punnett square', 'Calculate', 10, sprintf($r, $f3)];
$data[] = [13, $f3, 'Evolution', 'Understand evolution', 'Describe evidence for evolution', 'Explain natural selection', 'Explain', 8, sprintf($r, $f3)];
$data[] = [13, $f3, 'Ecology', 'Understand ecosystems', 'Describe the flow of energy in an ecosystem', 'Construct a food chain from given organisms', 'Construct', 8, sprintf($r, $f3)];

$data[] = [13, $f4, 'Human Health and Disease', 'Understand human health', 'Describe causes and prevention of common diseases', 'Explain the transmission and prevention of malaria', 'Explain', 8, sprintf($r, $f4)];
$data[] = [13, $f4, 'Biotechnology', 'Understand applications of biotechnology', 'Describe the role of microorganisms in biotechnology', 'Explain how yogurt is made using bacteria', 'Explain', 6, sprintf($r, $f4)];

// ═════════════════════════════════════════════════════════
// CHEMISTRY – GENERAL (subject_id = 12)
// ═════════════════════════════════════════════════════════
$r = $ref('Chemistry', '%s');
$data[] = [12, $f1, 'Introduction to Chemistry', 'Understand the basics of chemistry', 'Define chemistry and its importance', 'Explain the contribution of chemistry to daily life', 'Explain', 4, sprintf($r, $f1)];
$data[] = [12, $f1, 'Laboratory Equipment and Safety', 'Handle laboratory equipment safely', 'Identify common laboratory apparatus', 'Identify laboratory apparatus and their uses', 'Identify', 6, sprintf($r, $f1)];
$data[] = [12, $f1, 'States of Matter', 'Understand states of matter', 'Describe the properties of solids, liquids and gases', 'Explain changes of state using particle theory', 'Explain', 8, sprintf($r, $f1)];

$data[] = [12, $f2, 'Atomic Structure', 'Understand atomic structure', 'Describe the structure of an atom', 'Draw and label the atomic structure of given elements', 'Draw', 10, sprintf($r, $f2)];
$data[] = [12, $f2, 'Chemical Bonding', 'Understand chemical bonding', 'Describe ionic and covalent bonding', 'Explain the formation of ionic bonds', 'Explain', 10, sprintf($r, $f2)];
$data[] = [12, $f2, 'Acids, Bases and Salts', 'Understand acids, bases and salts', 'Identify acids and bases using indicators', 'Test substances using litmus paper', 'Test', 10, sprintf($r, $f2)];

$data[] = [12, $f3, 'Periodic Table', 'Understand the periodic table', 'Describe the arrangement of elements in the periodic table', 'Explain the trends in the periodic table', 'Explain', 10, sprintf($r, $f3)];
$data[] = [12, $f3, 'Chemical Reactions', 'Understand chemical reactions', 'Balance chemical equations', 'Write and balance chemical equations', 'Balance', 10, sprintf($r, $f3)];
$data[] = [12, $f3, 'Mole Concept', 'Understand the mole concept', 'Calculate number of moles in given samples', 'Calculate moles using mass and molar mass', 'Calculate', 8, sprintf($r, $f3)];

$data[] = [12, $f4, 'Organic Chemistry', 'Understand organic compounds', 'Identify functional groups in organic compounds', 'Name organic compounds using IUPAC nomenclature', 'Name', 10, sprintf($r, $f4)];
$data[] = [12, $f4, 'Electrochemistry', 'Understand electrochemistry', 'Describe electrolysis and its applications', 'Explain the process of electrolysis', 'Explain', 8, sprintf($r, $f4)];

// ═════════════════════════════════════════════════════════
// PHYSICS – GENERAL (subject_id = 11)
// ═════════════════════════════════════════════════════════
$r = $ref('Physics', '%s');
$data[] = [11, $f1, 'Introduction to Physics', 'Understand the scope of physics', 'Define physics and its branches', 'Explain the importance of physics in daily life', 'Explain', 4, sprintf($r, $f1)];
$data[] = [11, $f1, 'Measurement', 'Take accurate measurements', 'Measure length, mass and time using appropriate instruments', 'Measure the length of a given object using a ruler', 'Measure', 8, sprintf($r, $f1)];
$data[] = [11, $f1, 'Force', 'Understand force and its effects', 'Describe types of forces', 'Explain the effects of forces on objects', 'Explain', 6, sprintf($r, $f1)];
$data[] = [11, $f1, 'Pressure', 'Understand pressure', 'Calculate pressure from force and area', 'Calculate pressure exerted by an object', 'Calculate', 8, sprintf($r, $f1)];

$data[] = [11, $f2, 'Motion', 'Understand motion and its types', 'Describe speed, velocity and acceleration', 'Calculate acceleration from given data', 'Calculate', 10, sprintf($r, $f2)];
$data[] = [11, $f2, 'Work, Energy and Power', 'Understand work, energy and power', 'Calculate work done and power', 'Calculate kinetic energy of a moving object', 'Calculate', 8, sprintf($r, $f2)];
$data[] = [11, $f2, 'Heat Transfer', 'Understand heat transfer', 'Describe conduction, convection and radiation', 'Explain how heat is transferred through solids', 'Explain', 6, sprintf($r, $f2)];
$data[] = [11, $f2, 'Light', 'Understand properties of light', 'Describe reflection and refraction of light', 'Draw ray diagrams for reflection', 'Draw', 8, sprintf($r, $f2)];

$data[] = [11, $f3, 'Electricity', 'Understand electric circuits', 'Describe current, voltage and resistance', 'Calculate current using Ohm\'s law', 'Calculate', 10, sprintf($r, $f3)];
$data[] = [11, $f3, 'Magnetism', 'Understand magnetism', 'Describe magnetic fields and electromagnets', 'Explain how to make an electromagnet', 'Explain', 6, sprintf($r, $f3)];
$data[] = [11, $f3, 'Waves', 'Understand wave properties', 'Describe transverse and longitudinal waves', 'Explain the difference between transverse and longitudinal waves', 'Explain', 8, sprintf($r, $f3)];
$data[] = [11, $f3, 'Sound', 'Understand sound waves', 'Describe the properties of sound', 'Calculate the speed of sound given distance and time', 'Calculate', 6, sprintf($r, $f3)];

$data[] = [11, $f4, 'Modern Physics', 'Understand modern physics concepts', 'Describe radioactivity and its applications', 'Explain the uses of radioisotopes', 'Explain', 8, sprintf($r, $f4)];
$data[] = [11, $f4, 'Electronics', 'Understand basic electronics', 'Identify electronic components and their functions', 'Identify resistors, capacitors and transistors', 'Identify', 8, sprintf($r, $f4)];

// ═════════════════════════════════════════════════════════
// BUSINESS STUDIES – GENERAL (subject_id = 9)
// ═════════════════════════════════════════════════════════
$r = $ref('Business Studies', '%s');
$data[] = [9, $f1, 'Introduction to Business Studies', 'Understand the nature of business', 'Define business and its importance', 'Explain the role of business in society', 'Explain', 6, sprintf($r, $f1)];
$data[] = [9, $f1, 'Types of Business Activities', 'Identify business activities', 'Distinguish between production, distribution and consumption', 'Classify business activities into sectors', 'Classify', 6, sprintf($r, $f1)];
$data[] = [9, $f1, 'Entrepreneurship', 'Understand entrepreneurship', 'Describe qualities of an entrepreneur', 'Explain the characteristics of successful entrepreneurs', 'Explain', 6, sprintf($r, $f1)];
$data[] = [9, $f1, 'Money and Banking', 'Understand money and banking', 'Describe the functions of money', 'Explain the role of banks in the economy', 'Explain', 6, sprintf($r, $f1)];

$data[] = [9, $f2, 'Office Management', 'Understand office management', 'Describe office equipment and their uses', 'Identify different types of office equipment', 'Identify', 6, sprintf($r, $f2)];
$data[] = [9, $f2, 'Business Documents', 'Use business documents', 'Identify and prepare business documents', 'Prepare an invoice and a receipt', 'Prepare', 8, sprintf($r, $f2)];
$data[] = [9, $f2, 'Communication in Business', 'Understand business communication', 'Describe methods of business communication', 'Explain the importance of effective communication', 'Explain', 6, sprintf($r, $f2)];
$data[] = [9, $f2, 'Trade and Distribution', 'Understand trade and distribution channels', 'Describe wholesale and retail trade', 'Compare wholesale and retail trade', 'Compare', 6, sprintf($r, $f2)];

$data[] = [9, $f3, 'Accounting Principles', 'Understand basic accounting', 'Record transactions in ledger accounts', 'Prepare a simple ledger account', 'Prepare', 10, sprintf($r, $f3)];
$data[] = [9, $f3, 'Business Organizations', 'Understand forms of business ownership', 'Describe sole proprietorship, partnership and company', 'Compare advantages of sole proprietorship and partnership', 'Compare', 8, sprintf($r, $f3)];
$data[] = [9, $f3, 'Marketing', 'Understand marketing concepts', 'Describe the marketing mix (4Ps)', 'Explain the elements of the marketing mix', 'Explain', 8, sprintf($r, $f3)];

$data[] = [9, $f4, 'National Income', 'Understand national income', 'Describe methods of measuring national income', 'Calculate GDP using the expenditure method', 'Calculate', 8, sprintf($r, $f4)];
$data[] = [9, $f4, 'Taxation', 'Understand taxation', 'Describe types of taxes and their importance', 'Explain the difference between direct and indirect taxes', 'Explain', 6, sprintf($r, $f4)];
$data[] = [9, $f4, 'International Trade', 'Understand international trade', 'Describe balance of trade and balance of payments', 'Explain the importance of international trade', 'Explain', 6, sprintf($r, $f4)];

// ═════════════════════════════════════════════════════════
// COMPUTER SCIENCE – GENERAL (subject_id = 27)
// ═════════════════════════════════════════════════════════
$r = $ref('Computer Science', '%s');
$data[] = [27, $f1, 'Introduction to Computers', 'Understand the concept of a computer', 'Define a computer and its components', 'Identify the main parts of a computer', 'Identify', 6, sprintf($r, $f1)];
$data[] = [27, $f1, 'Computer Hardware', 'Identify computer hardware', 'Describe input, output and storage devices', 'Classify computer hardware into categories', 'Classify', 8, sprintf($r, $f1)];
$data[] = [27, $f1, 'Computer Software', 'Understand computer software', 'Distinguish between system and application software', 'Explain the functions of an operating system', 'Explain', 6, sprintf($r, $f1)];
$data[] = [27, $f1, 'Keyboard and Mouse Skills', 'Develop keyboard and mouse skills', 'Type using correct finger placement', 'Type a given paragraph using a word processor', 'Type', 8, sprintf($r, $f1)];
$data[] = [27, $f1, 'Introduction to Internet', 'Understand the internet', 'Describe the uses of the internet', 'Explain how to search for information online', 'Explain', 6, sprintf($r, $f1)];

$data[] = [27, $f2, 'Word Processing', 'Use word processing software', 'Format text and insert images in a document', 'Create and format a document', 'Create', 10, sprintf($r, $f2)];
$data[] = [27, $f2, 'Spreadsheets', 'Use spreadsheet software', 'Create tables and perform calculations', 'Create a spreadsheet with formulas', 'Create', 10, sprintf($r, $f2)];
$data[] = [27, $f2, 'Database Concepts', 'Understand databases', 'Define and describe database management systems', 'Explain the structure of a relational database', 'Explain', 6, sprintf($r, $f2)];
$data[] = [27, $f2, 'Presentation Software', 'Use presentation software', 'Create and deliver a presentation', 'Create a PowerPoint presentation on a given topic', 'Create', 8, sprintf($r, $f2)];

$data[] = [27, $f3, 'Programming Concepts', 'Understand programming', 'Write simple programs in a high-level language', 'Write a program that outputs a greeting message', 'Write', 12, sprintf($r, $f3)];
$data[] = [27, $f3, 'Data Structures', 'Understand data structures', 'Describe arrays, lists and stacks', 'Implement an array in a programming language', 'Implement', 10, sprintf($r, $f3)];
$data[] = [27, $f3, 'Computer Networks', 'Understand computer networks', 'Describe network topologies and protocols', 'Explain the difference between LAN and WAN', 'Explain', 8, sprintf($r, $f3)];

$data[] = [27, $f4, 'Database Design', 'Design and implement databases', 'Create tables and write SQL queries', 'Write SQL queries to retrieve data', 'Write', 12, sprintf($r, $f4)];
$data[] = [27, $f4, 'Web Development', 'Understand web development', 'Create web pages using HTML and CSS', 'Create a simple web page with HTML', 'Create', 10, sprintf($r, $f4)];
$data[] = [27, $f4, 'Software Engineering', 'Understand software development lifecycle', 'Describe stages of software development', 'Explain the waterfall model of SDLC', 'Explain', 6, sprintf($r, $f4)];

// ═════════════════════════════════════════════════════════
// ENGINEERING SCIENCE – VOCATIONAL (subject_id = 17)
// ═════════════════════════════════════════════════════════
$r = $ref('Engineering Science', '%s');
$data[] = [17, $f1, 'Introduction to Engineering Science', 'Understand the scope of engineering science', 'Define engineering science and its branches', 'Explain the importance of engineering science', 'Explain', 4, sprintf($r, $f1)];
$data[] = [17, $f1, 'Basic Measurements', 'Take accurate measurements', 'Measure length, mass, and volume using appropriate tools', 'Measure the volume of a liquid using a measuring cylinder', 'Measure', 8, sprintf($r, $f1)];
$data[] = [17, $f1, 'Hand Tools', 'Identify hand tools', 'Identify common hand tools and their uses', 'Identify tools used in metalwork', 'Identify', 6, sprintf($r, $f1)];
$data[] = [17, $f1, 'Safety in Engineering', 'Apply safety in engineering', 'Describe safety procedures in a workshop', 'Explain the importance of personal protective equipment', 'Explain', 4, sprintf($r, $f1)];

$data[] = [17, $f2, 'Materials Technology', 'Understand engineering materials', 'Describe properties of metals, plastics and wood', 'Classify materials into ferrous and non-ferrous metals', 'Classify', 8, sprintf($r, $f2)];
$data[] = [17, $f2, 'Basic Electricity', 'Understand basic electrical concepts', 'Describe current, voltage and resistance', 'Calculate resistance using Ohm\'s law', 'Calculate', 8, sprintf($r, $f2)];
$data[] = [17, $f2, 'Machine Tools', 'Understand machine tools', 'Describe the use of drilling and turning machines', 'Operate a drilling machine safely', 'Operate', 8, sprintf($r, $f2)];

$data[] = [17, $f3, 'Engineering Drawing', 'Read and interpret engineering drawings', 'Draw orthographic projections', 'Draw the orthographic projection of a given object', 'Draw', 10, sprintf($r, $f3)];
$data[] = [17, $f3, 'Thermodynamics', 'Understand basic thermodynamics', 'Describe heat transfer and temperature measurement', 'Explain the three modes of heat transfer', 'Explain', 8, sprintf($r, $f3)];
$data[] = [17, $f3, 'Fluid Mechanics', 'Understand fluid mechanics', 'Describe pressure in liquids and gases', 'Calculate pressure in a hydraulic system', 'Calculate', 8, sprintf($r, $f3)];

$data[] = [17, $f4, 'Mechanics of Machines', 'Understand machine mechanics', 'Describe levers, pulleys and gears', 'Calculate mechanical advantage of a lever', 'Calculate', 10, sprintf($r, $f4)];
$data[] = [17, $f4, 'Engineering Materials II', 'Understand material properties', 'Describe heat treatment of metals', 'Explain the process of hardening steel', 'Explain', 6, sprintf($r, $f4)];

// ═════════════════════════════════════════════════════════
// TECHNICAL DRAWING – VOCATIONAL (subject_id = 19)
// ═════════════════════════════════════════════════════════
$r = $ref('Technical Drawing', '%s');
$data[] = [19, $f1, 'Introduction to Technical Drawing', 'Understand technical drawing', 'Identify drawing instruments and their uses', 'Identify and use drawing instruments', 'Identify', 6, sprintf($r, $f1)];
$data[] = [19, $f1, 'Geometric Constructions', 'Construct geometric figures', 'Construct lines, angles and polygons', 'Construct a regular hexagon', 'Construct', 10, sprintf($r, $f1)];
$data[] = [19, $f1, 'Lettering and Dimensioning', 'Apply lettering and dimensioning', 'Write using standard technical lettering', 'Apply dimensioning to a drawing', 'Apply', 6, sprintf($r, $f1)];

$data[] = [19, $f2, 'Orthographic Projection', 'Draw orthographic projections', 'Draw front, top and side views of objects', 'Draw the orthographic views of a given block', 'Draw', 10, sprintf($r, $f2)];
$data[] = [19, $f2, 'Isometric Drawing', 'Draw isometric views', 'Draw isometric views of simple objects', 'Draw the isometric view of a block', 'Draw', 10, sprintf($r, $f2)];
$data[] = [19, $f2, 'Freehand Sketching', 'Sketch freehand drawings', 'Sketch objects freehand without instruments', 'Sketch a freehand drawing of a chair', 'Sketch', 6, sprintf($r, $f2)];

$data[] = [19, $f3, 'Sectional Views', 'Draw sectional views', 'Draw full and half sectional views', 'Draw the full sectional view of a given object', 'Draw', 10, sprintf($r, $f3)];
$data[] = [19, $f3, 'Development of Surfaces', 'Develop surfaces of solids', 'Develop the surface of a prism and cylinder', 'Develop the surface of a rectangular prism', 'Develop', 10, sprintf($r, $f3)];
$data[] = [19, $f3, 'Building Drawing', 'Read building drawings', 'Interpret floor plans and elevations', 'Read and interpret a given floor plan', 'Interpret', 8, sprintf($r, $f3)];

$data[] = [19, $f4, 'Machine Drawing', 'Draw machine parts', 'Draw assembled machine parts', 'Draw the assembly of a simple machine', 'Draw', 10, sprintf($r, $f4)];
$data[] = [19, $f4, 'Computer-Aided Drawing (CAD)', 'Use CAD software', 'Create drawings using CAD software', 'Draw a 2D object using CAD software', 'Draw', 12, sprintf($r, $f4)];

// ═════════════════════════════════════════════════════════
// COMPUTER APPLICATION WITH CAD – VOCATIONAL (subject_id = 20)
// ═════════════════════════════════════════════════════════
$r = $ref('Computer Application with CAD', '%s');
$data[] = [20, $f1, 'Introduction to Computers', 'Understand basic computer concepts', 'Identify computer components and their functions', 'Identify the main parts of a computer system', 'Identify', 6, sprintf($r, $f1)];
$data[] = [20, $f1, 'Operating System Basics', 'Use an operating system', 'Perform file management operations', 'Create and organize folders', 'Create', 6, sprintf($r, $f1)];
$data[] = [20, $f1, 'Typing Skills', 'Develop typing skills', 'Type with correct finger placement', 'Type a paragraph with accuracy', 'Type', 8, sprintf($r, $f1)];

$data[] = [20, $f2, 'Word Processing', 'Use word processing software', 'Format and edit documents', 'Create a professional letter using Word', 'Create', 10, sprintf($r, $f2)];
$data[] = [20, $f2, 'Spreadsheets', 'Use spreadsheet software', 'Create worksheets and use formulas', 'Create a budget spreadsheet with formulas', 'Create', 10, sprintf($r, $f2)];
$data[] = [20, $f2, 'Database Management', 'Use database software', 'Create tables and run queries', 'Create a database with two related tables', 'Create', 8, sprintf($r, $f2)];

$data[] = [20, $f3, 'Introduction to CAD', 'Understand CAD concepts', 'Describe the CAD interface and tools', 'Identify the main tools in the CAD workspace', 'Identify', 8, sprintf($r, $f3)];
$data[] = [20, $f3, '2D Drawing', 'Create 2D drawings', 'Draw lines, circles and rectangles in CAD', 'Draw a 2D floor plan using CAD', 'Draw', 12, sprintf($r, $f3)];
$data[] = [20, $f3, 'Editing in CAD', 'Edit CAD drawings', 'Use trim, extend and offset commands', 'Apply trim and extend to refine a drawing', 'Apply', 8, sprintf($r, $f3)];

$data[] = [20, $f4, '3D Modeling', 'Create 3D models', 'Create 3D solids using extrude and revolve', 'Create a 3D model of a simple object', 'Create', 12, sprintf($r, $f4)];
$data[] = [20, $f4, 'Rendering and Plotting', 'Render and output drawings', 'Apply materials and render a model', 'Render a 3D model with materials', 'Render', 8, sprintf($r, $f4)];

// ═════════════════════════════════════════════════════════
// LIFE SKILLS – VOCATIONAL (subject_id = 21)
// ═════════════════════════════════════════════════════════
$r = $ref('Life Skills', '%s');
$data[] = [21, $f1, 'Self Awareness', 'Develop self awareness', 'Identify personal strengths and weaknesses', 'Describe your personal strengths and areas for improvement', 'Describe', 6, sprintf($r, $f1)];
$data[] = [21, $f1, 'Communication Skills', 'Develop effective communication', 'Use verbal and non-verbal communication effectively', 'Demonstrate effective listening skills', 'Demonstrate', 8, sprintf($r, $f1)];
$data[] = [21, $f1, 'Decision Making', 'Make informed decisions', 'Apply the decision-making process', 'Make a decision using the six-step process', 'Apply', 6, sprintf($r, $f1)];
$data[] = [21, $f1, 'Goal Setting', 'Set personal goals', 'Set SMART goals for personal development', 'Write three SMART goals for the term', 'Write', 6, sprintf($r, $f1)];

$data[] = [21, $f2, 'Interpersonal Relationships', 'Build healthy relationships', 'Describe qualities of healthy relationships', 'Explain how to resolve conflicts with peers', 'Explain', 6, sprintf($r, $f2)];
$data[] = [21, $f2, 'Emotional Intelligence', 'Manage emotions effectively', 'Identify and manage emotions', 'Describe strategies for managing anger', 'Describe', 6, sprintf($r, $f2)];
$data[] = [21, $f2, 'Time Management', 'Manage time effectively', 'Create and follow a personal schedule', 'Create a weekly study timetable', 'Create', 4, sprintf($r, $f2)];
$data[] = [21, $f2, 'Peer Pressure', 'Resist negative peer pressure', 'Identify positive and negative peer influence', 'Explain ways to resist negative peer pressure', 'Explain', 6, sprintf($r, $f2)];

$data[] = [21, $f3, 'Financial Literacy', 'Manage personal finances', 'Prepare a personal budget', 'Create a monthly budget for a student', 'Create', 8, sprintf($r, $f3)];
$data[] = [21, $f3, 'Career Development', 'Plan for career development', 'Identify career options and required skills', 'Research a career that matches your interests', 'Research', 8, sprintf($r, $f3)];
$data[] = [21, $f3, 'Leadership Skills', 'Develop leadership qualities', 'Describe qualities of effective leaders', 'Demonstrate leadership in a group activity', 'Demonstrate', 6, sprintf($r, $f3)];
$data[] = [21, $f3, 'HIV and AIDS Awareness', 'Understand HIV and AIDS', 'Describe transmission and prevention of HIV', 'Explain ways to prevent HIV infection', 'Explain', 4, sprintf($r, $f3)];

$data[] = [21, $f4, 'Entrepreneurship', 'Develop entrepreneurial mindset', 'Identify business opportunities in the community', 'Write a simple business plan', 'Write', 10, sprintf($r, $f4)];
$data[] = [21, $f4, 'Community Service', 'Participate in community development', 'Plan and participate in a community service project', 'Plan a community clean-up activity', 'Plan', 6, sprintf($r, $f4)];
$data[] = [21, $f4, 'Mental Health', 'Maintain mental health', 'Identify signs of stress and coping strategies', 'Describe healthy ways to manage stress', 'Describe', 6, sprintf($r, $f4)];

// ═════════════════════════════════════════════════════════
// ELECTRICAL INSTALLATION – VOCATIONAL (subject_id = 22)
// ═════════════════════════════════════════════════════════
$r = $ref('Electrical Installation', '%s');
$data[] = [22, $f1, 'Introduction to Electrical Installation', 'Understand electrical installation', 'Define basic electrical terms', 'Explain voltage, current and resistance', 'Explain', 4, sprintf($r, $f1)];
$data[] = [22, $f1, 'Electrical Tools and Safety', 'Use electrical tools safely', 'Identify electrical tools and safety equipment', 'Identify tools used in electrical installation', 'Identify', 6, sprintf($r, $f1)];
$data[] = [22, $f1, 'Electrical Circuits', 'Understand simple circuits', 'Construct a simple series circuit', 'Connect a simple circuit with a bulb and switch', 'Connect', 8, sprintf($r, $f1)];

$data[] = [22, $f2, 'Wiring Systems', 'Understand wiring systems', 'Identify types of cables and their uses', 'Strip and join electrical cables', 'Strip', 8, sprintf($r, $f2)];
$data[] = [22, $f2, 'Lighting Installations', 'Install lighting systems', 'Connect lighting circuits with switches', 'Install a one-way lighting circuit', 'Install', 8, sprintf($r, $f2)];
$data[] = [22, $f2, 'Circuit Protection', 'Understand circuit protection', 'Describe fuses and circuit breakers', 'Explain the function of a circuit breaker', 'Explain', 6, sprintf($r, $f2)];

$data[] = [22, $f3, 'Power Supply Systems', 'Understand power supply', 'Describe single-phase and three-phase systems', 'Explain the difference between single and three phase', 'Explain', 8, sprintf($r, $f3)];
$data[] = [22, $f3, 'Motor Control', 'Understand motor control', 'Connect a direct-on-line motor starter', 'Wire a direct-on-line starter circuit', 'Wire', 10, sprintf($r, $f3)];
$data[] = [22, $f3, 'Earthing Systems', 'Understand earthing', 'Describe types of earthing systems', 'Explain the importance of earthing', 'Explain', 6, sprintf($r, $f3)];

$data[] = [22, $f4, 'Electrical Machines', 'Understand electrical machines', 'Describe transformers and their applications', 'Explain the working principle of a transformer', 'Explain', 8, sprintf($r, $f4)];
$data[] = [22, $f4, 'Installation Testing', 'Test electrical installations', 'Perform insulation resistance testing', 'Measure insulation resistance using a megger', 'Measure', 8, sprintf($r, $f4)];

// ═════════════════════════════════════════════════════════
// ELECTRONICS REPAIR – VOCATIONAL (subject_id = 23)
// ═════════════════════════════════════════════════════════
$r = $ref('Electronics Repair', '%s');
$data[] = [23, $f1, 'Introduction to Electronics', 'Understand electronics', 'Define electronics and its applications', 'Explain the importance of electronics in daily life', 'Explain', 4, sprintf($r, $f1)];
$data[] = [23, $f1, 'Electronic Components', 'Identify electronic components', 'Identify resistors, capacitors and diodes', 'Identify resistor values using color codes', 'Identify', 8, sprintf($r, $f1)];
$data[] = [23, $f1, 'Soldering and Desoldering', 'Perform soldering', 'Solder electronic components on a PCB', 'Solder resistors onto a PCB board', 'Solder', 8, sprintf($r, $f1)];

$data[] = [23, $f2, 'Power Supplies', 'Understand power supply circuits', 'Build a simple DC power supply', 'Construct a rectifier circuit', 'Construct', 10, sprintf($r, $f2)];
$data[] = [23, $f2, 'Amplifiers', 'Understand amplifier circuits', 'Build a simple audio amplifier', 'Construct a transistor amplifier', 'Construct', 10, sprintf($r, $f2)];
$data[] = [23, $f2, 'Multimeter Use', 'Use a multimeter', 'Measure voltage, current and resistance', 'Measure DC voltage using a multimeter', 'Measure', 6, sprintf($r, $f2)];

$data[] = [23, $f3, 'Digital Electronics', 'Understand digital electronics', 'Identify logic gates and their truth tables', 'Explain the function of AND, OR and NOT gates', 'Explain', 10, sprintf($r, $f3)];
$data[] = [23, $f3, 'Microcontrollers', 'Understand microcontrollers', 'Program a simple microcontroller', 'Write a program to blink an LED', 'Write', 12, sprintf($r, $f3)];
$data[] = [23, $f3, 'Sensors and Actuators', 'Understand sensors and actuators', 'Connect sensors to a microcontroller', 'Interface a temperature sensor with Arduino', 'Interface', 8, sprintf($r, $f3)];

$data[] = [23, $f4, 'Troubleshooting and Repair', 'Troubleshoot electronic circuits', 'Diagnose faults in electronic devices', 'Troubleshoot a faulty power supply', 'Troubleshoot', 10, sprintf($r, $f4)];
$data[] = [23, $f4, 'Communication Systems', 'Understand communication electronics', 'Describe AM and FM radio principles', 'Explain how a radio receiver works', 'Explain', 8, sprintf($r, $f4)];

// ═════════════════════════════════════════════════════════
// COMPUTER PROGRAMMING – VOCATIONAL (subject_id = 24)
// ═════════════════════════════════════════════════════════
$r = $ref('Computer Programming', '%s');
$data[] = [24, $f1, 'Introduction to Programming', 'Understand programming concepts', 'Define algorithms and flowcharts', 'Draw a flowchart for a simple process', 'Draw', 8, sprintf($r, $f1)];
$data[] = [24, $f1, 'Programming Environments', 'Set up a programming environment', 'Install and use an IDE', 'Write and run a hello world program', 'Write', 6, sprintf($r, $f1)];

$data[] = [24, $f2, 'Variables and Data Types', 'Use variables and data types', 'Declare variables and use data types', 'Write a program using variables', 'Write', 10, sprintf($r, $f2)];
$data[] = [24, $f2, 'Control Structures', 'Use control structures', 'Use if-else and switch statements', 'Write a program with conditional statements', 'Write', 10, sprintf($r, $f2)];
$data[] = [24, $f2, 'Loops', 'Use loops in programming', 'Use for, while and do-while loops', 'Write a program using loops', 'Write', 10, sprintf($r, $f2)];

$data[] = [24, $f3, 'Functions and Methods', 'Use functions in programming', 'Define and call functions', 'Write a program with user-defined functions', 'Write', 10, sprintf($r, $f3)];
$data[] = [24, $f3, 'Arrays and Strings', 'Use arrays and strings', 'Manipulate arrays and strings', 'Write a program to sort an array', 'Write', 10, sprintf($r, $f3)];
$data[] = [24, $f3, 'Object-Oriented Programming', 'Understand OOP concepts', 'Define classes and create objects', 'Write a program using classes and objects', 'Write', 12, sprintf($r, $f3)];

$data[] = [24, $f4, 'File Handling', 'Handle files in programming', 'Read from and write to files', 'Write a program that saves data to a file', 'Write', 10, sprintf($r, $f4)];
$data[] = [24, $f4, 'Database Programming', 'Connect programs to databases', 'Write SQL queries in a program', 'Write a program to retrieve data from MySQL', 'Write', 12, sprintf($r, $f4)];
$data[] = [24, $f4, 'Software Development Project', 'Develop a software project', 'Plan, code and test a small application', 'Develop a simple calculator application', 'Develop', 16, sprintf($r, $f4)];

// ═════════════════════════════════════════════════════════
// MASONRY AND BRICKLAYING – VOCATIONAL (subject_id = 25)
// ═════════════════════════════════════════════════════════
$r = $ref('Masonry and Bricklaying', '%s');
$data[] = [25, $f1, 'Introduction to Masonry', 'Understand masonry work', 'Identify masonry tools and materials', 'Identify tools used in bricklaying', 'Identify', 6, sprintf($r, $f1)];
$data[] = [25, $f1, 'Safety in Construction', 'Apply safety in construction', 'Describe safety practices on site', 'Explain the use of personal protective equipment', 'Explain', 4, sprintf($r, $f1)];

$data[] = [25, $f2, 'Brick Laying', 'Lay bricks correctly', 'Lay bricks in a straight line', 'Lay a single brick wall', 'Lay', 12, sprintf($r, $f2)];
$data[] = [25, $f2, 'Mixing Concrete', 'Mix concrete correctly', 'Mix concrete using correct ratios', 'Mix concrete in the ratio 1:2:4', 'Mix', 8, sprintf($r, $f2)];
$data[] = [25, $f2, 'Plastering', 'Apply plaster', 'Apply plaster to a wall surface', 'Plaster a small wall section', 'Plaster', 10, sprintf($r, $f2)];

$data[] = [25, $f3, 'Foundation and Footing', 'Construct foundations', 'Dig and prepare a foundation trench', 'Set out a foundation for a room', 'Set out', 10, sprintf($r, $f3)];
$data[] = [25, $f3, 'Wall Construction', 'Construct walls', 'Build a corner wall with bonding', 'Construct a wall with English bond', 'Construct', 12, sprintf($r, $f3)];
$data[] = [25, $f3, 'Lintels and Beams', 'Install lintels and beams', 'Cast and install a concrete lintel', 'Cast a reinforced concrete lintel', 'Cast', 8, sprintf($r, $f3)];

$data[] = [25, $f4, 'Roofing', 'Understand roofing', 'Install roof trusses and roofing sheets', 'Install a simple roof truss', 'Install', 12, sprintf($r, $f4)];
$data[] = [25, $f4, 'Floor and Wall Finishing', 'Apply floor and wall finishes', 'Lay floor tiles and wall tiles', 'Lay ceramic tiles on a floor', 'Lay', 12, sprintf($r, $f4)];

// ─────────────────────────────────────────────────────────
// INSERT DATA
// ─────────────────────────────────────────────────────────
$inserted = 0;
$skipped = 0;
foreach ($data as $row) {
    $sid = $row[0];
    $fl = $row[1];
    $topic = $row[2];
    $mc = $row[3];
    $sc = $row[4];
    $ma = $row[5];
    $aw = $row[6];
    $nop = $row[7];
    $ref = $row[8];

    if (insert($conn, $sid, $fl, $topic, $mc, $sc, $ma, $aw, $nop, $ref)) {
        $inserted++;
    } else {
        echo "  ✗ Error on [{$fl}] {$topic}: " . mysqli_error($conn) . "\n";
        $skipped++;
    }
}

echo "────────────────────────────────────────────────\n";
echo "✓ Inserted: $inserted rows\n";
if ($skipped) echo "⚠ Skipped: $skipped rows (errors)\n";
echo "✓ TABLE `lesson_plan_syllabus` is ready.\n";

// Print summary by subject
echo "\n── Summary by Subject ──\n";
$subj = mysqli_query($conn, "
    SELECT s.subject_name, lps.form_level, COUNT(*) AS cnt
    FROM lesson_plan_syllabus lps
    JOIN subjects s ON s.id = lps.subject_id
    GROUP BY s.subject_name, lps.form_level
    ORDER BY s.subject_name, lps.form_level
");
$total = 0;
while ($s = mysqli_fetch_assoc($subj)) {
    $bar = str_repeat('█', min(20, $s['cnt']));
    printf("  %-30s %-12s %3d %s\n", $s['subject_name'], $s['form_level'], $s['cnt'], $bar);
    $total += $s['cnt'];
}
echo "\nTotal syllabus entries: $total\n";
echo "</pre>";
