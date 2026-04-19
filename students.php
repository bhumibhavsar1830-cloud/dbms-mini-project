<?php
$page_title = 'Students';
require_once 'includes/db.php';

$msg = '';

// ADD with prepared statement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $roll  = trim($_POST['roll_no']);
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $div   = $_POST['division'];

    $result = db_query($conn,
        "INSERT INTO students (roll_no, name, email, phone, division) VALUES (?,?,?,?,?)",
        'sssss', $roll, $name, $email, $phone, $div
    );
    if ($result !== false) {
        $log_id = mysqli_insert_id($conn);
        db_query($conn, "INSERT INTO audit_log (action,table_name,record_id,details) VALUES ('INSERT','students',?,?)",
            'is', $log_id, "Added student: $name ($roll)");
        $msg = "<div class='alert alert-success'>✅ Student <strong>$name</strong> added.</div>";
    } else {
        $err = mysqli_error($conn);
        $msg = "<div class='alert alert-danger'>❌ " . (strpos($err,'Duplicate')!==false ? "Roll number already exists." : htmlspecialchars($err)) . "</div>";
    }
}

// DELETE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $s  = db_fetch_one($conn, "SELECT name, roll_no FROM students WHERE student_id=?", 'i', $id);
    if ($s) {
        db_query($conn, "DELETE FROM students WHERE student_id=?", 'i', $id);
        db_query($conn, "INSERT INTO audit_log (action,table_name,record_id,details) VALUES ('DELETE','students',?,?)",
            'is', $id, "Deleted: {$s['name']} ({$s['roll_no']})");
        header("Location: students.php?msg=deleted"); exit;
    }
}

$students = db_fetch_all($conn,
    "SELECT s.*,
            COUNT(DISTINCT a.attendance_id) AS att_records,
            COUNT(DISTINCT g.grade_id)      AS grade_records,
            ROUND(COALESCE(
              (SELECT AVG(att_pct) FROM (
                SELECT ROUND(SUM(status IN ('Present','Late'))/COUNT(*)*100,2) AS att_pct
                FROM attendance WHERE student_id=s.student_id GROUP BY subject_id) t
              ), 0), 1) AS avg_att
     FROM students s
     LEFT JOIN attendance a ON s.student_id=a.student_id
     LEFT JOIN grades g     ON s.student_id=g.student_id
     GROUP BY s.student_id ORDER BY s.division, s.roll_no");

require_once 'includes/header.php';
?>

<div class="page-header">
  <h1>Students</h1>
  <p>Manage student records — Group G3</p>
</div>

<?= $msg ?>
<?php if (isset($_GET['msg']) && $_GET['msg']==='deleted'): ?>
  <div class="alert alert-success">✅ Student deleted successfully.</div>
<?php endif; ?>

<!-- ADD STUDENT -->
<div class="panel">
  <div class="panel-header"><span class="panel-title">// ADD_STUDENT</span></div>
  <div class="panel-body">
    <form method="POST" class="form-grid">
      <div class="form-group">
        <label class="form-label">Roll Number *</label>
        <input class="form-input" type="text" name="roll_no" placeholder="e.g. 2240027" required>
      </div>
      <div class="form-group">
        <label class="form-label">Full Name *</label>
        <input class="form-input" type="text" name="name" placeholder="Surname First Middle" required>
      </div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input class="form-input" type="email" name="email" placeholder="student@example.com">
      </div>
      <div class="form-group">
        <label class="form-label">Phone</label>
        <input class="form-input" type="text" name="phone" placeholder="10-digit">
      </div>
      <div class="form-group">
        <label class="form-label">Division *</label>
        <select class="form-select" name="division" required>
          <option value="G1">G1</option>
          <option value="G2">G2</option>
          <option value="G3" selected>G3</option>
          <option value="G4">G4</option>
        </select>
      </div>
      <div class="form-group" style="justify-content:flex-end">
        <button type="submit" name="add_student" class="btn btn-primary" style="margin-top:auto">➕ Add Student</button>
      </div>
    </form>
  </div>
</div>

<!-- STUDENTS TABLE -->
<div class="panel">
  <div class="panel-header">
    <span class="panel-title">// ALL_STUDENTS</span>
    <span style="font-family:var(--font-mono);font-size:11px;color:var(--accent)"><?= count($students) ?> records</span>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th><th>Roll No</th><th>Name</th><th>Email</th>
          <th>Phone</th><th>Div</th><th>Avg Att</th><th>Records</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php $i=1; foreach ($students as $s):
          $attcls = $s['avg_att']>=75?'badge-ok':($s['avg_att']>0?'badge-warn':'badge-risk');
        ?>
        <tr>
          <td style="color:var(--text-3);font-family:var(--font-mono)"><?= $i++ ?></td>
          <td><strong><?= htmlspecialchars($s['roll_no']) ?></strong></td>
          <td><?= htmlspecialchars($s['name']) ?></td>
          <td style="font-size:12px;color:var(--text-3)"><?= htmlspecialchars($s['email'] ?? '—') ?></td>
          <td style="font-family:var(--font-mono);font-size:12px"><?= htmlspecialchars($s['phone'] ?? '—') ?></td>
          <td><span class="badge badge-blue"><?= $s['division'] ?></span></td>
          <td>
            <?php if ($s['avg_att'] > 0): ?>
              <span class="badge <?= $attcls ?>"><?= $s['avg_att'] ?>%</span>
            <?php else: ?>
              <span style="color:var(--text-3);font-family:var(--font-mono);font-size:11px">—</span>
            <?php endif; ?>
          </td>
          <td style="font-family:var(--font-mono);font-size:12px;color:var(--text-3)">
            <?= $s['att_records'] ?> att · <?= $s['grade_records'] ?> grades
          </td>
          <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap">
              <a href="reports.php?student_id=<?= $s['student_id'] ?>" class="btn btn-sm btn-ghost">📊</a>
              <a href="students.php?delete=<?= $s['student_id'] ?>"
                 class="btn btn-sm btn-danger"
                 onclick="return confirm('Delete <?= addslashes($s['name']) ?> and ALL records?')">🗑</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>

