<?php
$page_title = 'Dashboard';
require_once 'includes/db.php';

$total_students = db_fetch_one($conn, "SELECT COUNT(*) AS c FROM students")['c'] ?? 0;
$total_subjects  = db_fetch_one($conn, "SELECT COUNT(*) AS c FROM subjects")['c'] ?? 0;
$total_att       = db_fetch_one($conn, "SELECT COUNT(*) AS c FROM attendance")['c'] ?? 0;
$low_att         = db_fetch_one($conn,
    "SELECT COUNT(DISTINCT student_id) AS c FROM attendance_summary WHERE attendance_pct < 75")['c'] ?? 0;

$recent_att = db_fetch_all($conn,
    "SELECT s.name, s.roll_no, sub.subject_name, a.date, a.status
     FROM attendance a
     JOIN students s  ON a.student_id  = s.student_id
     JOIN subjects sub ON a.subject_id = sub.subject_id
     ORDER BY a.date DESC, a.attendance_id DESC LIMIT 8");

$overview = db_fetch_all($conn,
    "SELECT name, roll_no, division, subject_name, subject_code,
            total_classes, present_count, attendance_pct, att_status
     FROM attendance_summary ORDER BY attendance_pct ASC");

$top_students = db_fetch_all($conn,
    "SELECT s.name, s.roll_no,
            ROUND(AVG(gs.percentage),1) AS avg_pct,
            ROUND(SUM(gs.grade_point*sub.credits)/SUM(sub.credits),2) AS sgpa
     FROM grade_summary gs
     JOIN students s  ON gs.student_id=s.student_id
     JOIN subjects sub ON gs.subject_id=sub.subject_id
     GROUP BY s.student_id ORDER BY sgpa DESC LIMIT 5");

require_once 'includes/header.php';
?>

<div class="page-header">
  <h1>Dashboard</h1>
  <p>Academic Year 2024–25 | Semester IV | Division G3</p>
</div>

<!-- STATS -->
<div class="stats-grid">
  <div class="stat-card blue">
    <span class="stat-icon">👥</span>
    <span class="stat-number"><?= $total_students ?></span>
    <span class="stat-label">TOTAL_STUDENTS</span>
  </div>
  <div class="stat-card green">
    <span class="stat-icon">📚</span>
    <span class="stat-number"><?= $total_subjects ?></span>
    <span class="stat-label">SUBJECTS</span>
  </div>
  <div class="stat-card amber">
    <span class="stat-icon">📋</span>
    <span class="stat-number"><?= $total_att ?></span>
    <span class="stat-label">ATT_RECORDS</span>
  </div>
  <div class="stat-card red">
    <span class="stat-icon">⚠️</span>
    <span class="stat-number"><?= $low_att ?></span>
    <span class="stat-label">AT_RISK (&lt;75%)</span>
  </div>
</div>

<!-- QUICK ACTIONS -->
<div class="panel">
  <div class="panel-header">
    <span class="panel-title">// QUICK_ACTIONS</span>
  </div>
  <div class="panel-body">
    <div class="quick-actions">
      <a href="attendance.php" class="btn btn-primary">📅 Mark Attendance</a>
      <a href="grades.php"     class="btn btn-success">🏆 Enter Grades</a>
      <a href="students.php"   class="btn btn-amber">👤 Add Student</a>
      <a href="reports.php"    class="btn btn-ghost">📊 View Reports</a>
    </div>
  </div>
</div>

<!-- ATTENDANCE OVERVIEW -->
<div class="panel">
  <div class="panel-header">
    <span class="panel-title">// ATTENDANCE_OVERVIEW</span>
    <span style="font-size:11px;color:var(--text-3);font-family:var(--font-mono)">SPPU MIN: 75%</span>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Roll No</th><th>Name</th><th>Div</th><th>Subject</th>
          <th>Total</th><th>Present</th><th>Attendance</th><th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($overview as $r):
          $pct = $r['attendance_pct'];
          $cls = $pct >= 75 ? 'good' : ($pct >= 65 ? 'warn' : 'bad');
          $bcls = $r['att_status'] === 'Excellent' ? 'badge-excellent' :
                  ($r['att_status'] === 'Good' ? 'badge-ok' :
                  ($r['att_status'] === 'Warning' ? 'badge-warn' : 'badge-risk'));
        ?>
        <tr>
          <td><strong><?= htmlspecialchars($r['roll_no']) ?></strong></td>
          <td><?= htmlspecialchars($r['name']) ?></td>
          <td><span class="badge badge-blue"><?= $r['division'] ?></span></td>
          <td><?= htmlspecialchars($r['subject_name']) ?></td>
          <td><?= $r['total_classes'] ?></td>
          <td><?= $r['present_count'] ?></td>
          <td>
            <div class="pbar-wrap">
              <div class="pbar-track">
                <div class="pbar-fill <?= $cls ?>" style="width:<?= $pct ?>%"></div>
              </div>
              <span class="pbar-label"><?= $pct ?>%</span>
            </div>
          </td>
          <td><span class="badge <?= $bcls ?>"><?= $r['att_status'] ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$overview): ?>
        <tr><td colspan="8" style="text-align:center;color:var(--text-3);padding:24px;font-family:var(--font-mono)">// NO_DATA — Mark attendance to see overview</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- TOP STUDENTS & RECENT ATT (2-col) -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

  <div class="panel">
    <div class="panel-header"><span class="panel-title">// TOP_PERFORMERS</span></div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Rank</th><th>Name</th><th>SGPA</th><th>Avg %</th></tr></thead>
        <tbody>
          <?php $rank=1; foreach ($top_students as $t):
            $gcls = $t['sgpa'] >= 8 ? 'badge-grade-O' : ($t['sgpa'] >= 6 ? 'badge-grade-A' : 'badge-grade-B');
          ?>
          <tr>
            <td><strong style="color:var(--accent);font-family:var(--font-mono)">#<?= $rank++ ?></strong></td>
            <td><?= htmlspecialchars($t['name']) ?></td>
            <td><span class="badge <?= $gcls ?>"><?= $t['sgpa'] ?></span></td>
            <td><?= $t['avg_pct'] ?>%</td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$top_students): ?>
          <tr><td colspan="4" style="text-align:center;color:var(--text-3);padding:20px;font-family:var(--font-mono)">// NO_GRADE_DATA</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="panel">
    <div class="panel-header"><span class="panel-title">// RECENT_ATTENDANCE</span></div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Student</th><th>Subject</th><th>Date</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($recent_att as $r):
            $bcls = $r['status']==='Present'?'badge-present':($r['status']==='Late'?'badge-late':'badge-absent');
          ?>
          <tr>
            <td><?= htmlspecialchars($r['name']) ?></td>
            <td style="font-size:11px"><?= htmlspecialchars($r['subject_name']) ?></td>
            <td style="font-family:var(--font-mono);font-size:11px"><?= $r['date'] ?></td>
            <td><span class="badge <?= $bcls ?>"><?= $r['status'] ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once 'includes/footer.php'; ?>


