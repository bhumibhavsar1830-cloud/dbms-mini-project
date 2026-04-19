<?php
$page_title = 'Reports';
require_once 'includes/db.php';

$students        = db_fetch_all($conn, "SELECT * FROM students ORDER BY roll_no");
$selected_id     = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$student_info    = null;
$att_report      = [];
$grade_report    = [];
$sgpa_data       = null;

if ($selected_id) {
    $student_info = db_fetch_one($conn, "SELECT * FROM students WHERE student_id=?", 'i', $selected_id);

    // Attendance via stored procedure
    if ($student_info) {
        mysqli_multi_query($conn, "CALL GetAttendanceReport($selected_id)");
        $r = mysqli_store_result($conn);
        if ($r) { while ($row = mysqli_fetch_assoc($r)) $att_report[] = $row; }
        while (mysqli_more_results($conn)) mysqli_next_result($conn);

        // SGPA
        mysqli_multi_query($conn, "CALL CalculateSGPA($selected_id, 4)");
        $r2 = mysqli_store_result($conn);
        if ($r2) $sgpa_data = mysqli_fetch_assoc($r2);
        while (mysqli_more_results($conn)) mysqli_next_result($conn);

        $grade_report = db_fetch_all($conn,
            "SELECT sub.subject_name, sub.subject_code, g.exam_type, g.marks, g.max_marks, g.remarks,
                    ROUND(g.marks/g.max_marks*100,2) AS pct
             FROM grades g JOIN subjects sub ON g.subject_id=sub.subject_id
             WHERE g.student_id=? ORDER BY sub.subject_name, g.exam_type",
            'i', $selected_id);
    }
}

// Class ranking via stored procedure
$ranking = [];
mysqli_multi_query($conn, "CALL GetClassRanking(4)");
$rr = mysqli_store_result($conn);
if ($rr) { while ($row = mysqli_fetch_assoc($rr)) $ranking[] = $row; }
while (mysqli_more_results($conn)) mysqli_next_result($conn);

// Subject-wise attendance
$sub_att = db_fetch_all($conn,
    "SELECT sub.subject_name, sub.subject_code,
            COUNT(*) AS total,
            SUM(status IN ('Present','Late')) AS present,
            ROUND(SUM(status IN ('Present','Late'))/COUNT(*)*100,2) AS pct
     FROM attendance a JOIN subjects sub ON a.subject_id=sub.subject_id
     GROUP BY sub.subject_id ORDER BY pct DESC");

// Audit log
$audit = db_fetch_all($conn,
    "SELECT action, table_name, details, created_at FROM audit_log ORDER BY created_at DESC LIMIT 15");

require_once 'includes/header.php';
?>

<div class="page-header">
  <h1>Reports</h1>
  <p>Performance analysis · Stored Procedures · Audit Log</p>
</div>

<!-- STUDENT SELECTOR -->
<div class="panel">
  <div class="panel-header"><span class="panel-title">// STUDENT_REPORT</span></div>
  <div class="panel-body">
    <form method="GET" class="form-grid">
      <div class="form-group">
        <label class="form-label">Select Student</label>
        <select class="form-select" name="student_id">
          <option value="">— Class Overview —</option>
          <?php foreach ($students as $st): ?>
          <option value="<?= $st['student_id'] ?>" <?= $selected_id==$st['student_id']?'selected':'' ?>>
            <?= htmlspecialchars($st['roll_no'].' — '.$st['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="justify-content:flex-end">
        <button type="submit" class="btn btn-primary" style="margin-top:auto">📊 Generate</button>
      </div>
    </form>
  </div>
</div>

<?php if ($student_info): ?>

<!-- STUDENT INFO -->
<div class="panel">
  <div class="panel-header">
    <span class="panel-title">// PROFILE: <?= htmlspecialchars(strtoupper($student_info['name'])) ?></span>
  </div>
  <div class="panel-body">
    <div class="report-meta">
      <div class="report-meta-item">
        <span class="report-meta-key">Name</span>
        <span class="report-meta-val"><?= htmlspecialchars($student_info['name']) ?></span>
      </div>
      <div class="report-meta-item">
        <span class="report-meta-key">Roll No</span>
        <span class="report-meta-val" style="font-family:var(--font-mono)"><?= htmlspecialchars($student_info['roll_no']) ?></span>
      </div>
      <div class="report-meta-item">
        <span class="report-meta-key">Division</span>
        <span class="report-meta-val"><?= $student_info['division'] ?></span>
      </div>
      <div class="report-meta-item">
        <span class="report-meta-key">Email</span>
        <span class="report-meta-val" style="font-size:13px"><?= htmlspecialchars($student_info['email'] ?? '—') ?></span>
      </div>
    </div>

    <!-- SGPA -->
    <?php if ($sgpa_data && isset($sgpa_data['sgpa'])): ?>
    <div style="margin-bottom:20px">
      <div class="sgpa-card">
        <div>
          <span class="sgpa-label">SGPA · SEM IV</span>
          <div style="display:flex;align-items:baseline;gap:6px">
            <span class="sgpa-val"><?= $sgpa_data['sgpa'] ?></span>
            <span class="sgpa-denom">/ 10.0</span>
          </div>
        </div>
        <div style="margin-left:24px;font-family:var(--font-mono);font-size:12px;color:var(--text-3)">
          <div><?= $sgpa_data['total_credits'] ?> credits</div>
          <div><?= $sgpa_data['subjects_count'] ?> subjects</div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- ATTENDANCE REPORT (Stored Procedure) -->
    <h3 style="font-family:var(--font-mono);font-size:12px;color:var(--text-3);letter-spacing:.08em;margin-bottom:10px">
      // PROC: GetAttendanceReport(<?= $selected_id ?>)
    </h3>
    <div class="table-wrap" style="margin-bottom:24px">
      <table class="data-table">
        <thead>
          <tr><th>Subject</th><th>Code</th><th>Credits</th><th>Total</th><th>Present</th><th>Absent</th><th>%</th><th>Remark</th></tr>
        </thead>
        <tbody>
          <?php foreach ($att_report as $r):
            $pct=$r['percentage']; $cls=$pct>=75?'good':($pct>=65?'warn':'bad');
          ?>
          <tr>
            <td><?= htmlspecialchars($r['subject_name']) ?></td>
            <td style="font-family:var(--font-mono);font-size:11px;color:var(--text-3)"><?= $r['subject_code'] ?></td>
            <td style="font-family:var(--font-mono)"><?= $r['credits'] ?></td>
            <td style="font-family:var(--font-mono)"><?= $r['total'] ?></td>
            <td style="font-family:var(--font-mono);color:var(--green)"><?= $r['present'] ?></td>
            <td style="font-family:var(--font-mono);color:var(--red)"><?= $r['absent'] ?></td>
            <td>
              <div class="pbar-wrap">
                <div class="pbar-track"><div class="pbar-fill <?= $cls ?>" style="width:<?= $pct ?>%"></div></div>
                <span class="pbar-label"><?= $pct ?>%</span>
              </div>
            </td>
            <td style="font-size:12px"><?= $r['remark'] ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$att_report): ?>
          <tr><td colspan="8" style="text-align:center;color:var(--text-3);padding:16px;font-family:var(--font-mono)">// NO_ATTENDANCE_DATA</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- GRADE DETAIL -->
    <h3 style="font-family:var(--font-mono);font-size:12px;color:var(--text-3);letter-spacing:.08em;margin-bottom:10px">// GRADE_DETAIL</h3>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Subject</th><th>Code</th><th>Exam</th><th>Marks</th><th>Max</th><th>%</th><th>Remarks</th></tr></thead>
        <tbody>
          <?php foreach ($grade_report as $g): ?>
          <tr>
            <td><?= htmlspecialchars($g['subject_name']) ?></td>
            <td style="font-family:var(--font-mono);font-size:11px;color:var(--text-3)"><?= $g['subject_code'] ?></td>
            <td><span class="badge badge-blue"><?= $g['exam_type'] ?></span></td>
            <td style="font-family:var(--font-mono)"><?= $g['marks'] ?></td>
            <td style="font-family:var(--font-mono);color:var(--text-3)"><?= $g['max_marks'] ?></td>
            <td style="font-family:var(--font-mono)"><?= $g['pct'] ?>%</td>
            <td style="font-size:11px;color:var(--text-3)"><?= htmlspecialchars($g['remarks'] ?? '—') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php else: ?>

<!-- CLASS RANKING -->
<div class="panel">
  <div class="panel-header">
    <span class="panel-title">// PROC: GetClassRanking(4) — SEM IV</span>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>Rank</th><th>Roll No</th><th>Name</th><th>Division</th><th>SGPA</th><th>Avg %</th></tr>
      </thead>
      <tbody>
        <?php foreach ($ranking as $r):
          $scls = $r['sgpa']>=8?'badge-grade-O':($r['sgpa']>=6?'badge-grade-A':'badge-grade-B');
        ?>
        <tr>
          <td>
            <span style="font-family:var(--font-mono);font-weight:700;color:<?= $r['class_rank']==1?'#fbbf24':($r['class_rank']==2?'#94a3b8':($r['class_rank']==3?'#cd7c4e':'var(--text-3)')) ?>">
              #<?= $r['class_rank'] ?>
            </span>
          </td>
          <td><strong><?= htmlspecialchars($r['roll_no']) ?></strong></td>
          <td><?= htmlspecialchars($r['name']) ?></td>
          <td><span class="badge badge-blue"><?= $r['division'] ?></span></td>
          <td><span class="badge <?= $scls ?>"><?= $r['sgpa'] ?></span></td>
          <td><?= $r['avg_percentage'] ?>%</td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$ranking): ?>
        <tr><td colspan="6" style="text-align:center;color:var(--text-3);padding:24px;font-family:var(--font-mono)">// ADD_GRADES_TO_SEE_RANKING</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- SUBJECT ATT -->
<div class="panel">
  <div class="panel-header"><span class="panel-title">// SUBJECT_ATTENDANCE_ANALYSIS</span></div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Subject</th><th>Code</th><th>Total Records</th><th>Present</th><th>Class Avg %</th></tr></thead>
      <tbody>
        <?php foreach ($sub_att as $s):
          $cls=$s['pct']>=75?'good':($s['pct']>=65?'warn':'bad');
        ?>
        <tr>
          <td><?= htmlspecialchars($s['subject_name']) ?></td>
          <td style="font-family:var(--font-mono);font-size:11px;color:var(--text-3)"><?= $s['subject_code'] ?></td>
          <td style="font-family:var(--font-mono)"><?= $s['total'] ?></td>
          <td style="font-family:var(--font-mono);color:var(--green)"><?= $s['present'] ?></td>
          <td>
            <div class="pbar-wrap">
              <div class="pbar-track"><div class="pbar-fill <?= $cls ?>" style="width:<?= $s['pct'] ?>%"></div></div>
              <span class="pbar-label"><?= $s['pct'] ?>%</span>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- AUDIT LOG -->
<div class="panel">
  <div class="panel-header"><span class="panel-title">// AUDIT_LOG</span></div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Action</th><th>Table</th><th>Details</th><th>Timestamp</th></tr></thead>
      <tbody>
        <?php foreach ($audit as $a):
          $acls=$a['action']==='INSERT'?'badge-ok':($a['action']==='DELETE'?'badge-risk':'badge-warn');
        ?>
        <tr>
          <td><span class="badge <?= $acls ?>"><?= $a['action'] ?></span></td>
          <td style="font-family:var(--font-mono);font-size:11px"><?= $a['table_name'] ?></td>
          <td style="font-size:12px;color:var(--text-3)"><?= htmlspecialchars($a['details']) ?></td>
          <td style="font-family:var(--font-mono);font-size:11px;color:var(--text-3)"><?= $a['created_at'] ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$audit): ?>
        <tr><td colspan="4" style="text-align:center;color:var(--text-3);padding:16px;font-family:var(--font-mono)">// NO_AUDIT_ENTRIES</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>

