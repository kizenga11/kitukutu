<?php
session_start();
include "../includes/config.php";
include "../includes/teaching_docs_functions.php";
include "../includes/calendar_functions.php";
if (!isset($_SESSION['teacher_id'])) { header("Location: ../login.php"); exit(); }
$teacher_id = intval($_SESSION['teacher_id']);

$active_year = getActiveAcademicYear($conn);
$year_id = $active_year['id'] ?? 0;
$assignments = getTeacherAssignments($conn, $teacher_id);
$subject_ids = array_column($assignments, 'subject_id');
$sid_list = empty($subject_ids) ? '0' : implode(',', $subject_ids);

$syllabi = mysqli_query($conn, "SELECT ss.*, sub.subject_name FROM subject_syllabus ss JOIN subjects sub ON sub.id = ss.subject_id WHERE ss.subject_id IN ($sid_list) AND ss.is_active = 1 ORDER BY sub.subject_name");

$resources = mysqli_query($conn, "SELECT sr.*, sub.subject_name FROM subject_resources sr JOIN subjects sub ON sub.id = sr.subject_id WHERE sr.subject_id IN ($sid_list) AND sr.is_active = 1 ORDER BY sub.subject_name, sr.resource_type");

$schemes = mysqli_query($conn, "SELECT sw.*, sub.subject_name, tr.term_name FROM scheme_of_work sw JOIN subjects sub ON sub.id = sw.subject_id LEFT JOIN terms tr ON tr.id = sw.term_id WHERE sw.subject_id IN ($sid_list) AND sw.teacher_id = $teacher_id ORDER BY sw.created_at DESC");

$curriculum = mysqli_query($conn, "SELECT c.*, sub.subject_name FROM curriculum c JOIN subjects sub ON sub.id = c.subject_id WHERE c.is_active = 1 AND c.subject_id IN ($sid_list) ORDER BY sub.subject_name, c.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Teaching & Learning Documents</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#f3f4f6;font-family:system-ui,-apple-system,sans-serif;padding:14px;color:#111827;}
.page-title{font-size:18px;font-weight:800;color:#0b2b3f;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.panel{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:14px;margin-bottom:12px;}
.sec-hdr{font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;display:flex;align-items:center;gap:6px;}
.doc-card{display:flex;gap:10px;padding:8px 10px;margin-bottom:6px;border-radius:10px;align-items:flex-start;background:#fafafa;}
.doc-card:last-child{margin-bottom:0;}
.doc-icon{font-size:18px;color:#6366f1;flex-shrink:0;margin-top:2px;}
.doc-info{flex:1;min-width:0;}
.doc-title{font-size:13px;font-weight:700;}
.doc-meta{font-size:11px;color:#6b7280;margin-top:1px;}
.doc-link{font-size:11px;font-weight:600;color:#6366f1;text-decoration:none;}
.doc-link:hover{text-decoration:underline;}
.subj-tag{display:inline-block;background:#ede9fe;color:#5b21b6;padding:1px 8px;border-radius:12px;font-size:10px;font-weight:700;}
.type-tag{display:inline-block;padding:1px 8px;border-radius:12px;font-size:10px;font-weight:700;}
.empty-msg{text-align:center;padding:20px;color:#9ca3af;font-size:13px;}
.empty-msg i{font-size:1.8rem;display:block;margin-bottom:6px;opacity:.3;}
.scheme-row{display:flex;gap:10px;padding:8px 10px;margin-bottom:4px;border-radius:10px;align-items:center;background:#fafafa;}
.scheme-row:last-child{margin-bottom:0;}
.scheme-info{flex:1;}
.scheme-title{font-size:13px;font-weight:700;}
.scheme-meta{font-size:10px;color:#6b7280;}
.btn-sm{padding:4px 10px;font-size:11px;border-radius:6px;border:none;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:3px;}
.btn-primary-sm{background:#dbeafe;color:#1d4ed8;}
.badge-draft{background:#f3f4f6;color:#6b7280;padding:2px 10px;border-radius:20px;font-size:10px;font-weight:700;}
.badge-submitted{background:#fef3c7;color:#92400e;padding:2px 10px;border-radius:20px;font-size:10px;font-weight:700;}
.badge-approved{background:#d1fae5;color:#065f46;padding:2px 10px;border-radius:20px;font-size:10px;font-weight:700;}
.uc-banner{display:flex;align-items:center;gap:8px;background:#fef3c7;border:1px solid #fde68a;border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:13px;font-weight:600;color:#92400e;}
</style>
</head>
<body>

<div class="uc-banner"><i class="bi bi-tools"></i> 🚧 Under Construction — This section is being updated.</div>

<div class="page-title"><i class="bi bi-book"></i> Teaching & Learning Documents</div>

<div class="row g-3">
    <div class="col-md-6">

        <!-- Curriculum -->
        <div class="panel">
            <div class="sec-hdr"><i class="bi bi-book"></i> Curriculum by Subject</div>
            <?php if (mysqli_num_rows($curriculum) == 0): ?>
            <div class="empty-msg"><i class="bi bi-file-earmark-x"></i>No curriculum documents for your subjects yet.</div>
            <?php else: ?>
            <?php while ($c = mysqli_fetch_assoc($curriculum)): ?>
            <div class="doc-card">
                <div class="doc-icon"><i class="bi <?= getDocIcon($c['doc_type']) ?>"></i></div>
                <div class="doc-info">
                    <div class="doc-title"><?= htmlspecialchars($c['title']) ?> <span class="subj-tag"><?= htmlspecialchars($c['subject_name']) ?></span></div>
                    <div class="doc-meta"><?= htmlspecialchars($c['form_level'] ?? 'All Forms') ?></div>
                    <?php if ($c['description']): ?><div class="doc-meta"><?= htmlspecialchars($c['description']) ?></div><?php endif; ?>
                    <?php if ($c['doc_type'] === 'file' && $c['file_path']): ?>
                    <a class="doc-link" href="../uploads/curriculum/<?= urlencode($c['file_path']) ?>" target="_blank"><i class="bi bi-download"></i> Download</a>
                    <?php elseif ($c['doc_type'] === 'link' && $c['link_url']): ?>
                    <a class="doc-link" href="<?= htmlspecialchars($c['link_url']) ?>" target="_blank"><i class="bi bi-box-arrow-up-right"></i> Open Link</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <!-- Syllabus -->
        <div class="panel">
            <div class="sec-hdr"><i class="bi bi-journal-text"></i> Subject Syllabus</div>
            <?php if (mysqli_num_rows($syllabi) == 0): ?>
            <div class="empty-msg"><i class="bi bi-journal-x"></i>No syllabus for your subjects yet.</div>
            <?php else: ?>
            <?php while ($s = mysqli_fetch_assoc($syllabi)): ?>
            <div class="doc-card">
                <div class="doc-icon"><i class="bi bi-file-earmark-text"></i></div>
                <div class="doc-info">
                    <div class="doc-title"><?= htmlspecialchars($s['title']) ?> <span class="subj-tag"><?= htmlspecialchars($s['subject_name']) ?></span></div>
                    <div class="doc-meta"><?= htmlspecialchars($s['form_level'] ?? 'All Forms') ?></div>
                    <?php if ($s['doc_type'] === 'file' && $s['file_path']): ?>
                    <a class="doc-link" href="../uploads/syllabus/<?= urlencode($s['file_path']) ?>" target="_blank"><i class="bi bi-download"></i> Download</a>
                    <?php elseif ($s['doc_type'] === 'link' && $s['link_url']): ?>
                    <a class="doc-link" href="<?= htmlspecialchars($s['link_url']) ?>" target="_blank"><i class="bi bi-box-arrow-up-right"></i> Open Link</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <!-- Books & Notes -->
        <div class="panel">
            <div class="sec-hdr"><i class="bi bi-journal-richtext"></i> Books & Notes</div>
            <?php if (mysqli_num_rows($resources) == 0): ?>
            <div class="empty-msg"><i class="bi bi-journal-x"></i>No resources for your subjects yet.</div>
            <?php else: ?>
            <?php while ($r = mysqli_fetch_assoc($resources)):
                $tcolors = ['book'=>['bg'=>'#dbeafe','fg'=>'#1d4ed8'],'note'=>['bg'=>'#fef3c7','fg'=>'#92400e'],'reference'=>['bg'=>'#ede9fe','fg'=>'#5b21b6'],'other'=>['bg'=>'#f3f4f6','fg'=>'#374151']];
                $tc = $tcolors[$r['resource_type']] ?? $tcolors['other'];
            ?>
            <div class="doc-card">
                <div class="doc-icon"><i class="bi <?= $r['resource_type'] === 'book' ? 'bi-book' : ($r['resource_type'] === 'note' ? 'bi-sticky' : 'bi-folder') ?>"></i></div>
                <div class="doc-info">
                    <div class="doc-title"><?= htmlspecialchars($r['title']) ?> <span class="subj-tag"><?= htmlspecialchars($r['subject_name']) ?></span></div>
                    <div>
                        <span class="type-tag" style="background:<?= $tc['bg'] ?>;color:<?= $tc['fg'] ?>;"><?= ucfirst($r['resource_type']) ?></span>
                        <span style="font-size:10px;color:#6b7280;margin-left:4px;"><?= htmlspecialchars($r['form_level'] ?? 'All') ?></span>
                    </div>
                    <?php if ($r['description']): ?><div class="doc-meta"><?= htmlspecialchars($r['description']) ?></div><?php endif; ?>
                    <?php if ($r['doc_type'] === 'file' && $r['file_path']): ?>
                    <a class="doc-link" href="../uploads/resources/<?= urlencode($r['file_path']) ?>" target="_blank"><i class="bi bi-download"></i> Download</a>
                    <?php elseif ($r['doc_type'] === 'link' && $r['link_url']): ?>
                    <a class="doc-link" href="<?= htmlspecialchars($r['link_url']) ?>" target="_blank"><i class="bi bi-box-arrow-up-right"></i> Open Link</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
            <?php endif; ?>
        </div>

    </div>

    <div class="col-md-6">

        <!-- Scheme of Work -->
        <div class="panel">
            <div class="sec-hdr"><i class="bi bi-calendar-week"></i> My Schemes of Work</div>
            <?php if (mysqli_num_rows($schemes) == 0): ?>
            <div class="empty-msg"><i class="bi bi-calendar-week"></i>No schemes yet. Contact admin to create one.</div>
            <?php else: ?>
            <?php $statuses = ['draft'=>'Draft','submitted'=>'Submitted','approved'=>'Approved']; ?>
            <?php while ($s = mysqli_fetch_assoc($schemes)): ?>
            <div class="scheme-row">
                <div class="scheme-info">
                    <div class="scheme-title"><?= htmlspecialchars($s['title']) ?> <span class="subj-tag"><?= htmlspecialchars($s['subject_name']) ?></span></div>
                    <div class="scheme-meta"><?= htmlspecialchars($s['term_name'] ?? '') ?> · <?= htmlspecialchars($s['form_level'] ?? 'All') ?> · <?= intval($s['total_weeks']) ?> weeks</div>
                </div>
                <span class="badge-<?= $s['status'] ?>"><?= $statuses[$s['status']] ?? $s['status'] ?></span>
                <a href="scheme_of_work.php?id=<?= $s['id'] ?>" target="mainFrame" class="btn-sm btn-primary-sm"><i class="bi bi-eye"></i> View</a>
            </div>
            <?php endwhile; ?>
            <?php endif; ?>
            <div style="margin-top:8px;text-align:right;">
                <a href="lesson_plan.php" target="mainFrame" class="btn-sm btn-primary-sm"><i class="bi bi-plus-lg"></i> New Lesson Plan</a>
            </div>
        </div>

        <!-- My Subjects Quick Links -->
        <div class="panel">
            <div class="sec-hdr"><i class="bi bi-bookmark"></i> My Subjects</div>
            <?php if (empty($assignments)): ?>
            <div class="empty-msg"><i class="bi bi-book"></i>No subject assignments yet.</div>
            <?php else: ?>
            <?php foreach ($assignments as $a): ?>
            <div class="doc-card">
                <div class="doc-icon"><i class="bi bi-bookmark-fill" style="color:#6366f1;"></i></div>
                <div class="doc-info">
                    <div class="doc-title"><?= htmlspecialchars($a['subject_name']) ?></div>
                    <div class="doc-meta"><?= htmlspecialchars($a['form_level']) ?> · <?= htmlspecialchars($a['class_stream'] ?? '') ?> · <?= htmlspecialchars($a['stream']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
