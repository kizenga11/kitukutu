<?php
session_start();
include "../includes/config.php";
include "../includes/calendar_functions.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: ../login.php");
    exit();
}

$selected_year_id = (int)($_GET['academic_year_id'] ?? 0);
$selected_term_id = (int)($_GET['term_id'] ?? 0);
$form_level_filter = isset($_GET['form_level']) ? mysqli_real_escape_string($conn, $_GET['form_level']) : '';

$active_year = getActiveAcademicYear($conn);
$academic_years = mysqli_query($conn, "SELECT * FROM academic_years ORDER BY year_name DESC");

$terms = [];
if ($selected_year_id) {
    $terms = getTermsByYear($conn, $selected_year_id);
}

$term = null;
if ($selected_term_id) {
    $tq = mysqli_query($conn, "SELECT * FROM terms WHERE id='$selected_term_id'");
    $term = mysqli_fetch_assoc($tq);
}

$exams = [];
if ($term) {
    $exams_q = mysqli_query($conn, "
        SELECT id, exam_name, start_date
        FROM exams
        WHERE start_date >= '{$term['opening_date']}' AND start_date <= '{$term['closing_date']}'
        ORDER BY start_date ASC
    ");
    while ($e = mysqli_fetch_assoc($exams_q)) $exams[] = $e;
}

function grade($m){
    if($m >= 75) return 'A';
    if($m >= 65) return 'B';
    if($m >= 45) return 'C';
    if($m >= 30) return 'D';
    return 'F';
}
function points($g){
    return ['A'=>1,'B'=>2,'C'=>3,'D'=>4,'F'=>5][$g] ?? 0;
}
function division($p){
    if($p >= 7 && $p <= 17) return 'I';
    if($p >= 18 && $p <= 21) return 'II';
    if($p >= 22 && $p <= 25) return 'III';
    if($p >= 26 && $p <= 33) return 'IV';
    return '0';
}

$exam_ids = array_column($exams, 'id');
$exam_count = count($exams);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Term Ranking</title>
<style>
@media print{.no-print{display:none!important;}}
</style>
</head>
<body>

<form method="GET" class="no-print">
    <label>Academic Year
        <select name="academic_year_id" onchange="this.form.submit()">
            <option value="">Select Year</option>
            <?php if($academic_years) while($yr=mysqli_fetch_assoc($academic_years)): ?>
            <option value="<?= $yr['id'] ?>" <?= $selected_year_id==$yr['id']?'selected':'' ?>>
                <?= htmlspecialchars($yr['title'] ?: $yr['year_name']) ?>
                <?= ($active_year && $active_year['id']==$yr['id'])?' (Active)':'' ?>
            </option>
            <?php endwhile; ?>
        </select>
    </label>

    <label>Term
        <select name="term_id" onchange="this.form.submit()">
            <option value="">Select Term</option>
            <?php foreach($terms as $t): ?>
            <option value="<?= $t['id'] ?>" <?= $selected_term_id==$t['id']?'selected':'' ?>>
                <?= htmlspecialchars($t['term_name']) ?>
                (<?= date('d M',strtotime($t['opening_date'])) ?> - <?= date('d M',strtotime($t['closing_date'])) ?>)
            </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Form Level
        <select name="form_level">
            <option value="">All Forms</option>
            <?php foreach(['Form One','Form Two','Form Three','Form Four'] as $fl): ?>
            <option value="<?= $fl ?>" <?= $form_level_filter===$fl?'selected':'' ?>><?= str_replace('Form ','F. ',$fl) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <button type="submit">Load Ranking</button>
</form>

<?php if (!$selected_term_id): ?>
    <p class="no-print">Select academic year and term to load ranking</p>
<?php elseif (empty($exams)): ?>
    <p class="no-print">No exams found for this term</p>
<?php else: ?>

    <?php
    $form_levels = [];
    if ($form_level_filter) {
        $form_levels[] = $form_level_filter;
    } else {
        $fl_q = mysqli_query($conn,"
            SELECT DISTINCT s.form_level
            FROM marks m
            JOIN students s ON s.id = m.student_id
            WHERE m.exam_id IN (".implode(',',$exam_ids).") AND s.is_active=1
            ORDER BY s.form_level
        ");
        while ($fl_r = mysqli_fetch_assoc($fl_q)) $form_levels[] = $fl_r['form_level'];
    }

    foreach ($form_levels as $form_level):
        $flFilterSql = " AND s.form_level='$form_level'";

        $students_q = mysqli_query($conn,"
            SELECT DISTINCT s.id, s.first_name, s.second_name, s.last_name, s.sex, s.form_level, s.stream,
                CONCAT(s.first_name,' ',COALESCE(s.second_name,''),' ',s.last_name) AS full_name
            FROM marks m
            JOIN students s ON s.id = m.student_id
            WHERE m.exam_id IN (".implode(',',$exam_ids).") AND s.is_active=1 $flFilterSql
            ORDER BY s.stream, s.first_name
        ");

        if (!$students_q || mysqli_num_rows($students_q) === 0) continue;

        $ranking_data = [];

        $all_subs_q = mysqli_query($conn,"
            SELECT DISTINCT sub.short_name, sub.subject_name, sub.subject_code
            FROM student_subjects ss
            JOIN subjects sub ON sub.id = ss.subject_id
            WHERE ss.student_id IN (
                SELECT DISTINCT m.student_id
                FROM marks m
                JOIN students s ON s.id = m.student_id
                WHERE m.exam_id IN (".implode(',',$exam_ids).") AND s.is_active=1 $flFilterSql
            )
            ORDER BY sub.short_name
        ");
        $all_subject_codes = [];
        while ($as = mysqli_fetch_assoc($all_subs_q)) {
            $sn = $as['short_name'] ?: $as['subject_code'];
            if (!isset($all_subject_codes[$sn])) {
                $all_subject_codes[$sn] = $as['subject_name'];
            }
        }

        while ($st = mysqli_fetch_assoc($students_q)):
            $sid = $st['id'];

            $marks_q = mysqli_query($conn,"
                SELECT m.subject_id, sub.subject_name, sub.short_name, sub.subject_code, m.marks
                FROM marks m
                JOIN subjects sub ON sub.id = m.subject_id
                WHERE m.student_id = '$sid'
                  AND m.exam_id IN (".implode(',',$exam_ids).")
                  AND m.subject_id IN (SELECT subject_id FROM student_subjects WHERE student_id = '$sid')
                ORDER BY sub.short_name, m.exam_id
            ");

            $subject_groups = [];
            while ($mr = mysqli_fetch_assoc($marks_q)):
                $sn = $mr['short_name'] ?: $mr['subject_code'];
                $raw = $mr['marks'];
                if ($raw === 'A' || $raw === '' || $raw === null) continue;
                if (!is_numeric($raw)) continue;
                if (!isset($subject_groups[$sn])) {
                    $subject_groups[$sn] = [
                        'name' => $mr['subject_name'],
                        'code' => $sn,
                        'marks_sum' => 0,
                        'count' => 0,
                    ];
                }
                $subject_groups[$sn]['marks_sum'] += (float)$raw;
                $subject_groups[$sn]['count']++;
            endwhile;

            $subject_data = [];
            $total_marks_sum = 0;
            $subject_count = 0;
            $points_arr = [];

            foreach ($subject_groups as $sg):
                $avg = $sg['count'] > 0 ? round($sg['marks_sum'] / $sg['count'], 2) : 0;
                $grd = grade($avg);
                $pts = points($grd);
                $code = $sg['code'];

                $subject_data[] = [
                    'name' => $sg['name'],
                    'code' => $code,
                    'avg' => $avg,
                    'grade' => $grd,
                    'points' => $pts,
                    'exams' => $sg['count'],
                ];
                $total_marks_sum += $avg;
                $subject_count++;
                $points_arr[] = $pts;
                $all_subject_codes[$code] = $sg['name'];
            endforeach;

            sort($points_arr);
            $best7 = array_slice($points_arr, 0, 7);
            $total_points = array_sum($best7);
            $overall_avg = $subject_count > 0 ? round($total_marks_sum / $subject_count, 2) : 0;
            $grd_avg = $overall_avg ? grade($overall_avg) : '';

            if ($subject_count < 7) {
                $div = '';
                $total_points = 0;
            } else {
                $div = division($total_points);
            }

            $ranking_data[] = [
                'student' => $st,
                'subjects' => $subject_data,
                'subject_count' => $subject_count,
                'overall_avg' => $overall_avg,
                'grade' => $grd_avg,
                'total_points' => $total_points,
                'division' => $div,
            ];
        endwhile;

        usort($ranking_data, function($a, $b) {
            if ($a['overall_avg'] != $b['overall_avg'])
                return $b['overall_avg'] <=> $a['overall_avg'];
            return $a['total_points'] <=> $b['total_points'];
        });

        $rank = 1; $i = 1; $prev_avg = null;
        foreach ($ranking_data as &$rd) {
            if ($i > 1 && $rd['overall_avg'] != $prev_avg) $rank = $i;
            $rd['rank'] = $rank;
            $prev_avg = $rd['overall_avg'];
            $i++;
        }
        unset($rd);

        ksort($all_subject_codes);
    ?>
        <h4><?= str_replace('Form ','F. ',$form_level) ?> — <?= count($ranking_data) ?> student(s)</h4>

        <table border="1">
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Sex</th>
                <th>Stream</th>
                <th>Subj</th>
                <th>Avg</th>
                <th>Grade</th>
                <th>Points</th>
                <th>Div</th>
                <?php foreach ($all_subject_codes as $code => $sname): ?>
                <th><?= htmlspecialchars($code) ?></th>
                <?php endforeach; ?>
            </tr>

            <?php if (empty($ranking_data)): ?>
            <tr><td colspan="<?= 9 + count($all_subject_codes) ?>">No data</td></tr>
            <?php else: foreach ($ranking_data as $rd):
                $st = $rd['student'];
            ?>
            <tr>
                <td><?= $rd['rank'] ?></td>
                <td><?= htmlspecialchars($st['full_name']) ?></td>
                <td><?= $st['sex'] == 'Male' ? 'M' : 'F' ?></td>
                <td><?= htmlspecialchars($st['stream']) ?></td>
                <td><?= $rd['subject_count'] ?></td>
                <td><?= number_format($rd['overall_avg'], 2) ?></td>
                <td><?= $rd['grade'] ?></td>
                <td><?= $rd['total_points'] ?: '-' ?></td>
                <td><?= $rd['division'] ?: '-' ?></td>
                <?php
                $subj_map = [];
                foreach ($rd['subjects'] as $sb) $subj_map[$sb['code']] = $sb;
                foreach ($all_subject_codes as $code => $sname):
                    if (isset($subj_map[$code])):
                        $s = $subj_map[$code];
                ?>
                <td><?= number_format($s['avg'],1) ?> <?= $s['grade'] ?></td>
                <?php else: ?>
                <td>—</td>
                <?php endif; endforeach; ?>
            </tr>
            <?php endforeach; endif; ?>
        </table>

        <?php
        $total = count($ranking_data);
        $avg_sum = 0;
        $div_counts = ['I'=>0,'II'=>0,'III'=>0,'IV'=>0,'0'=>0];
        foreach ($ranking_data as $rd) {
            $avg_sum += $rd['overall_avg'];
            if ($rd['division'] && isset($div_counts[$rd['division']])) $div_counts[$rd['division']]++;
        }
        $form_avg = $total > 0 ? round($avg_sum / $total, 2) : 0;
        $form_grade = grade($form_avg);
        $div_parts = [];
        foreach (['I','II','III','IV','0'] as $d) {
            if (($div_counts[$d] ?? 0) > 0) $div_parts[] = "Div $d: {$div_counts[$d]}";
        }
        ?>
        <p>Form Avg: <?= number_format($form_avg, 2) ?> (<?= $form_grade ?>) — <?= implode(' | ', $div_parts) ?> — Total: <?= $total ?></p>

    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
