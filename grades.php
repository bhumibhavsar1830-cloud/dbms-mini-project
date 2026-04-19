<?php
$page_title = 'Grades';
require_once 'includes/db.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_grade'])) {
    $student_id = (int)$_POST['student_id'];
    $subject_id = (int)$_POST['subject_id'];
    $exam_type  = $_POST['exam_type'];
    $marks      = (float)$_POST['marks'];
    $max_marks  = (float)$_POST['max_marks'];
    $semester   = (int)$_POST['semester'];
    $remarks    = trim($_POST['remarks'] ?? '');

    if ($marks > $max_marks)
        $msg = "<div class='alert alert-danger'>❌ Marks ($marks) cannot exceed Max Marks ($max_marks).</div>";
    elseif ($marks < 0)
        $msg = "<div class='alert alert-danger'>❌ Marks cannot be negative.</div>";
    else {
        $result = db_query($conn,
            "INSERT INTO grades (student_id,subject_id,exam_type,marks,max_marks,semester,remarks)
             VALUES (?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE marks=VALUES(marks), max_marks=VALUES(max_marks), remarks=VALUES(remarks)",
            'iisddis', $student_id, $subject_id, $exam_type, $marks, $max_marks, $semester, $remarks);
        if ($result !== false)
            $msg = "<div class='alert alert-success'>✅ Grade saved successfully.</div>";
        else
            $msg = "<div class='alert alert-danger'>❌ " . htmlspecialchars(mysqli_error($conn)) . "</div>";
    }
}

$students = db_fetch_all($conn, "SELECT * FROM students ORDER BY roll_no");
$subjects  = db_fetch_all($conn, "SELECT * FROM subjects ORDER BY subject_name");

$all_grades = db_fetch_all($conn,
    "SELECT s.roll_no, s.name, sub.subject_name, sub.subject_code,
            g.exam_type, g.marks, g.max_marks, g.semester, g.remarks,
            ROUND(g.marks/g.max_marks*100,2) AS pct
     FROM grades g
     JOIN students s   ON g.student_id=s.student_id
     JOIN subjects sub ON g.subject_id=sub.subject_id
     ORDER BY s.roll_no, sub.subject_name, g.exam_type");

$grade_summary = db_fetch_all($conn,
    "SELECT roll_no, name, subject_name, subject_code, percentage, grade
     FROM grade_summary ORDER BY roll_no, subject_name");

function getGradeBadge(string $grade): string {
    if ($grade === 'O' || $grade === 'A+') return 'badge-grade-O';
    if ($grade === 'A' || $grade === 'B+') return 'badge-grade-A';
    if ($grade === 'F') return 'badge-grade-F';
    return 'badge-grade-B';
}

require_once 'includes/header.php';
?>

<div class="page-header">
  <h1>Grades</h1>
  <p>Enter and view marks · SPPU grading scale O/A+/A/B+/B/C/F</p>
</div>

<?= $msg ?>

<!-- ADD GRADE -->
<div class="panel">
  <div class="panel-header"><span class="panel-title">// ENTER_GRADE</span></div>
  <div class="panel-body">
    <form method="POST" class="form-grid">
      <div class="form-group">
        <label class="form-label">Student *</label>
        <select class="form-select" name="student_id" required>
          <option value="">— Select —</option>
          <?php foreach ($students as $st): ?>
          <option value="<?= $st['student_id'] ?>"><?= htmlspecialchars($st['roll_no'].' — '.$st['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Subject *</label>
        <select class="form-select" name="subject_id" required>
          <option value="">— Select —</option>
          <?php foreach ($subjects as $sub): ?>
          <option value="<?= $sub['subject_id'] ?>"><?= htmlspecialchars($sub['subject_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Exam Type *</label>
        <select class="form-select" name="exam_type" required>
          <option value="IA1">IA1 — Internal 1</option>
          <option value="IA2">IA2 — Internal 2</option>
          <option value="ESE">ESE — End Semester</option>
          <option value="Practical">Practical</option>
          <option value="Assignment">Assignment</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Marks Obtained *</label>
        <input class="form-input" type="number" name="marks" step="0.5" min="0" required>
      </div>
      <div class="form-group">
        <label class="form-label">Max Marks *</label>
        <input class="form-input" type="number" name="max_marks" step="0.5" min="1" value="100" required>
      </div>
      <div class="form-group">
        <label class="form-label">Semester</label>
        <select class="form-select" name="semester">
          <option value="3">Sem III</option>
          <option value="4" selected>Sem IV</option>
          <option value="5">Sem V</option>
          <option value="6">Sem VI</option>
        </select>
      </div>
      <div class="form-group span-full">
        <label class="form-label">Remarks (optional)</label>
        <input class="form-input" type="text" name="remarks" placeholder="e.g. Absent in IA1, granted makeup">
      </div>
      <div class="form-group">
        <button type="submit" name="add_grade" class="btn btn-success">💾 Save Grade</button>
      </div>
    </form>
  </div>
</div>

<!-- GRADE SUMMARY -->
<div class="panel">
  <div class="panel-header"><span class="panel-title">// SUBJECT_GRADE_SUMMARY</span></div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>Roll No</th><th>Name</th><th>Subject</th><th>Code</th><th>Overall %</th><th>Grade</th></tr>
      </thead>
      <tbody>
        <?php foreach ($grade_summary as $g): ?>
        <tr>
          <td><strong><?= htmlspecialchars($g['roll_no']) ?></strong></td>
          <td><?= htmlspecialchars($g['name']) ?></td>
          <td><?= htmlspecialchars($g['subject_name']) ?></td>
          <td style="font-family:var(--font-mono);font-size:11px;color:var(--text-3)"><?= $g['subject_code'] ?></td>
          <td>
            <?php $pct = $g['percentage']; $cls = $pct>=75?'good':($pct>=55?'warn':'bad'); ?>
            <div class="pbar-wrap">
              <div class="pbar-track"><div class="pbar-fill <?= $cls ?>" style="width:<?= $pct ?>%"></div></div>
              <span class="pbar-label"><?= $pct ?>%</span>
            </div>
          </td>
          <td><span class="badge <?= getGradeBadge($g['grade']) ?>"><?= $g['grade'] ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$grade_summary): ?>
        <tr><td colspan="6" style="text-align:center;color:var(--text-3);padding:24px;font-family:var(--font-mono)">// NO_GRADE_DATA</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
    <p style="padding:12px 14px;font-family:var(--font-mono);font-size:10px;color:var(--text-3)">
      SCALE: O≥90 | A+≥75 | A≥65 | B+≥55 | B≥45 | C≥40 | F&lt;40
    </p>
  </div>
</div>

<!-- ALL GRADE RECORDS -->
<div class="panel">
  <div class="panel-header">
    <span class="panel-title">// ALL_GRADE_RECORDS</span>
    <span style="font-family:var(--font-mono);font-size:11px;color:var(--accent)"><?= count($all_grades) ?> entries</span>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>#</th><th>Roll No</th><th>Student</th><th>Subject</th><th>Exam</th><th>Marks</th><th>Max</th><th>%</th><th>Remarks</th></tr>
      </thead>
      <tbody>
        <?php $i=1; foreach ($all_grades as $g):
          $pct=$g['pct']; $grade=$pct>=90?'O':($pct>=75?'A+':($pct>=65?'A':($pct>=55?'B+':($pct>=45?'B':($pct>=40?'C':'F')))));
        ?>
        <tr>
          <td style="color:var(--text-3);font-family:var(--font-mono)"><?= $i++ ?></td>
          <td><strong><?= htmlspecialchars($g['roll_no']) ?></strong></td>
          <td><?= htmlspecialchars($g['name']) ?></td>
          <td><?= htmlspecialchars($g['subject_name']) ?></td>
          <td><span class="badge badge-blue"><?= $g['exam_type'] ?></span></td>
          <td style="font-family:var(--font-mono)"><?= $g['marks'] ?></td>
          <td style="font-family:var(--font-mono);color:var(--text-3)"><?= $g['max_marks'] ?></td>
          <td><span class="badge <?= getGradeBadge($grade) ?>"><?= $grade ?> · <?= $pct ?>%</span></td>
          <td style="font-size:11px;color:var(--text-3)"><?= htmlspecialchars($g['remarks'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>

