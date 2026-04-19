<?php
$page_title = 'Attendance';
require_once 'includes/db.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    $subject_id = (int)$_POST['subject_id'];
    $date       = $_POST['att_date'];
    $statuses   = $_POST['status'] ?? [];

    // Validate date
    if ($date > date('Y-m-d')) {
        $msg = "<div class='alert alert-danger'>❌ Cannot mark attendance for a future date.</div>";
    } else {
        $success = 0; $fail = 0;
        $stmt = mysqli_prepare($conn,
            "INSERT INTO attendance (student_id, subject_id, date, status)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE status=VALUES(status)");
        foreach ($statuses as $student_id => $status) {
            $sid = (int)$student_id;
            mysqli_stmt_bind_param($stmt, 'iiss', $sid, $subject_id, $date, $status);
            mysqli_stmt_execute($stmt) ? $success++ : $fail++;
        }
        mysqli_stmt_close($stmt);
        $msg = "<div class='alert alert-success'>✅ Marked <strong>$success</strong> records" .
               ($fail ? " · <span style='color:var(--red)'>$fail errors</span>" : "") . ".</div>";
    }
}

$subjects      = db_fetch_all($conn, "SELECT * FROM subjects ORDER BY subject_name");
$students_list = db_fetch_all($conn, "SELECT * FROM students ORDER BY division, roll_no");

// Filter
$f_subject = isset($_GET['f_subject']) ? (int)$_GET['f_subject'] : 0;
$f_date    = isset($_GET['f_date'])    ? $_GET['f_date'] : '';

$where = "WHERE 1=1";
$params = []; $types = '';
if ($f_subject) { $where .= " AND a.subject_id=?"; $params[] = $f_subject; $types .= 'i'; }
if ($f_date)    { $where .= " AND a.date=?";        $params[] = $f_date;    $types .= 's'; }

$att_records = db_fetch_all($conn,
    "SELECT s.roll_no, s.name, s.division, sub.subject_name, a.date, a.status, a.attendance_id
     FROM attendance a
     JOIN students s  ON a.student_id=s.student_id
     JOIN subjects sub ON a.subject_id=sub.subject_id
     $where ORDER BY a.date DESC, s.roll_no LIMIT 100",
    $types, ...$params);

require_once 'includes/header.php';
?>

<div class="page-header">
  <h1>Attendance</h1>
  <p>Mark and view daily attendance records</p>
</div>

<?= $msg ?>

<!-- MARK -->
<div class="panel">
  <div class="panel-header"><span class="panel-title">// MARK_ATTENDANCE</span></div>
  <div class="panel-body">
    <form method="POST">
      <div class="form-grid" style="margin-bottom:20px">
        <div class="form-group">
          <label class="form-label">Subject *</label>
          <select class="form-select" name="subject_id" required>
            <option value="">— Select Subject —</option>
            <?php foreach ($subjects as $sub): ?>
            <option value="<?= $sub['subject_id'] ?>"><?= htmlspecialchars($sub['subject_name']) ?> (<?= $sub['subject_code'] ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Date *</label>
          <input class="form-input" type="date" name="att_date"
                 max="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
        </div>
      </div>

      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>#</th><th>Roll No</th><th>Name</th><th>Division</th>
              <th style="color:var(--green)">Present</th>
              <th style="color:var(--red)">Absent</th>
              <th style="color:var(--amber)">Late</th>
            </tr>
          </thead>
          <tbody>
            <?php $i=1; foreach ($students_list as $st): ?>
            <tr>
              <td style="color:var(--text-3);font-family:var(--font-mono)"><?= $i++ ?></td>
              <td><strong><?= htmlspecialchars($st['roll_no']) ?></strong></td>
              <td><?= htmlspecialchars($st['name']) ?></td>
              <td><span class="badge badge-blue"><?= $st['division'] ?></span></td>
              <td><input class="att-radio" type="radio" name="status[<?= $st['student_id'] ?>]" value="Present" checked></td>
              <td><input class="att-radio" type="radio" name="status[<?= $st['student_id'] ?>]" value="Absent"></td>
              <td><input class="att-radio" type="radio" name="status[<?= $st['student_id'] ?>]" value="Late"></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div style="margin-top:16px;display:flex;gap:10px;align-items:center">
        <button type="submit" name="mark_attendance" class="btn btn-primary">✅ Submit Attendance</button>
        <span style="font-size:12px;color:var(--text-3);font-family:var(--font-mono)">// Re-submitting overwrites existing record for that date.</span>
      </div>
    </form>
  </div>
</div>

<!-- VIEW -->
<div class="panel">
  <div class="panel-header"><span class="panel-title">// VIEW_RECORDS</span></div>
  <div class="panel-body">
    <form method="GET" class="form-grid" style="margin-bottom:16px">
      <div class="form-group">
        <label class="form-label">Filter Subject</label>
        <select class="form-select" name="f_subject">
          <option value="">All Subjects</option>
          <?php foreach ($subjects as $sub): ?>
          <option value="<?= $sub['subject_id'] ?>" <?= $f_subject==$sub['subject_id']?'selected':'' ?>>
            <?= htmlspecialchars($sub['subject_name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Filter Date</label>
        <input class="form-input" type="date" name="f_date" value="<?= $f_date ?>">
      </div>
      <div class="form-group" style="justify-content:flex-end">
        <button type="submit" class="btn btn-primary" style="margin-top:auto">🔍 Filter</button>
        <a href="attendance.php" class="btn btn-ghost" style="margin-top:6px">Reset</a>
      </div>
    </form>

    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr><th>Roll No</th><th>Name</th><th>Div</th><th>Subject</th><th>Date</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php foreach ($att_records as $r):
            $bcls = $r['status']==='Present'?'badge-present':($r['status']==='Late'?'badge-late':'badge-absent');
          ?>
          <tr>
            <td><strong><?= htmlspecialchars($r['roll_no']) ?></strong></td>
            <td><?= htmlspecialchars($r['name']) ?></td>
            <td><span class="badge badge-blue"><?= $r['division'] ?></span></td>
            <td><?= htmlspecialchars($r['subject_name']) ?></td>
            <td style="font-family:var(--font-mono);font-size:12px"><?= $r['date'] ?></td>
            <td><span class="badge <?= $bcls ?>"><?= $r['status'] ?></span></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$att_records): ?>
          <tr><td colspan="6" style="text-align:center;color:var(--text-3);padding:24px;font-family:var(--font-mono)">// NO_RECORDS_FOUND</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>

