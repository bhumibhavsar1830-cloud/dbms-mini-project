
CREATE TABLE IF NOT EXISTS subjects (
    subject_id   INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(20)  NOT NULL UNIQUE,
    subject_name VARCHAR(100) NOT NULL,
    credits      TINYINT      DEFAULT 3,
    semester     TINYINT      DEFAULT 4,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS students (
    student_id  INT AUTO_INCREMENT PRIMARY KEY,
    roll_no     VARCHAR(20)         NOT NULL UNIQUE,
    name        VARCHAR(100)        NOT NULL,
    email       VARCHAR(100)        UNIQUE,
    phone       VARCHAR(15),
    division    ENUM('G1','G2','G3','G4') NOT NULL DEFAULT 'G3',
    year        TINYINT             DEFAULT 2,
    photo_url   VARCHAR(255),
    created_at  TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP           DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_division (division),
    INDEX idx_roll    (roll_no)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id    INT  NOT NULL,
    subject_id    INT  NOT NULL,
    date          DATE NOT NULL,
    status        ENUM('Present','Absent','Late') NOT NULL DEFAULT 'Absent',
    marked_by     VARCHAR(50) DEFAULT 'Admin',
    created_at    TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uniq_att (student_id, subject_id, date),
    INDEX idx_date       (date),
    INDEX idx_status     (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS grades (
    grade_id    INT AUTO_INCREMENT PRIMARY KEY,
    student_id  INT            NOT NULL,
    subject_id  INT            NOT NULL,
    exam_type   ENUM('IA1','IA2','ESE','Practical','Assignment') NOT NULL,
    marks       DECIMAL(5,2)   NOT NULL,
    max_marks   DECIMAL(5,2)   NOT NULL DEFAULT 100,
    semester    TINYINT        DEFAULT 4,
    remarks     VARCHAR(200),
    created_at  TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uniq_grade (student_id, subject_id, exam_type, semester),
    INDEX idx_exam_type (exam_type),
    INDEX idx_semester  (semester)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_log (
    log_id     INT AUTO_INCREMENT PRIMARY KEY,
    action     VARCHAR(50) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id  INT,
    details    TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action (action),
    INDEX idx_table  (table_name)
) ENGINE=InnoDB;
CREATE OR REPLACE VIEW attendance_summary AS
SELECT
    s.student_id,
    s.roll_no,
    s.name,
    s.division,
    sub.subject_id,
    sub.subject_name,
    sub.subject_code,
    COUNT(a.attendance_id)                                                AS total_classes,
    SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END)               AS present_count,
    SUM(CASE WHEN a.status = 'Absent'  THEN 1 ELSE 0 END)               AS absent_count,
    SUM(CASE WHEN a.status = 'Late'    THEN 1 ELSE 0 END)               AS late_count,
    ROUND(SUM(CASE WHEN a.status IN ('Present','Late') THEN 1 ELSE 0 END)
          / COUNT(a.attendance_id) * 100, 2)                             AS attendance_pct,
    CASE
        WHEN ROUND(SUM(CASE WHEN a.status IN ('Present','Late') THEN 1 ELSE 0 END)
             / COUNT(a.attendance_id) * 100, 2) >= 85 THEN 'Excellent'
        WHEN ROUND(SUM(CASE WHEN a.status IN ('Present','Late') THEN 1 ELSE 0 END)
             / COUNT(a.attendance_id) * 100, 2) >= 75 THEN 'Good'
        WHEN ROUND(SUM(CASE WHEN a.status IN ('Present','Late') THEN 1 ELSE 0 END)
             / COUNT(a.attendance_id) * 100, 2) >= 65 THEN 'Warning'
        ELSE 'At Risk'
    END                                                                   AS att_status
FROM students s
JOIN attendance a  ON s.student_id = a.student_id
JOIN subjects  sub ON a.subject_id  = sub.subject_id
GROUP BY s.student_id, sub.subject_id;

CREATE OR REPLACE VIEW grade_summary AS
SELECT
    s.student_id,
    s.roll_no,
    s.name,
    s.division,
    sub.subject_id,
    sub.subject_name,
    sub.subject_code,
    sub.credits,
    ROUND(SUM(g.marks) / SUM(g.max_marks) * 100, 2) AS percentage,
    CASE
        WHEN ROUND(SUM(g.marks)/SUM(g.max_marks)*100,2) >= 90 THEN 'O'
        WHEN ROUND(SUM(g.marks)/SUM(g.max_marks)*100,2) >= 75 THEN 'A+'
        WHEN ROUND(SUM(g.marks)/SUM(g.max_marks)*100,2) >= 65 THEN 'A'
        WHEN ROUND(SUM(g.marks)/SUM(g.max_marks)*100,2) >= 55 THEN 'B+'
        WHEN ROUND(SUM(g.marks)/SUM(g.max_marks)*100,2) >= 45 THEN 'B'
        WHEN ROUND(SUM(g.marks)/SUM(g.max_marks)*100,2) >= 40 THEN 'C'
        ELSE 'F'
    END AS grade,
    CASE
        WHEN ROUND(SUM(g.marks)/SUM(g.max_marks)*100,2) >= 90 THEN 10
        WHEN ROUND(SUM(g.marks)/SUM(g.max_marks)*100,2) >= 75 THEN 9
        WHEN ROUND(SUM(g.marks)/SUM(g.max_marks)*100,2) >= 65 THEN 8
        WHEN ROUND(SUM(g.marks)/SUM(g.max_marks)*100,2) >= 55 THEN 7
        WHEN ROUND(SUM(g.marks)/SUM(g.max_marks)*100,2) >= 45 THEN 6
        WHEN ROUND(SUM(g.marks)/SUM(g.max_marks)*100,2) >= 40 THEN 5
        ELSE 0
    END AS grade_point
FROM students s
JOIN grades  g   ON s.student_id = g.student_id
JOIN subjects sub ON g.subject_id = sub.subject_id
GROUP BY s.student_id, sub.subject_id;
DROP PROCEDURE IF EXISTS GetAttendanceReport;
DELIMITER $$
CREATE PROCEDURE GetAttendanceReport(IN p_student_id INT)
BEGIN
    SELECT
        sub.subject_name,
        sub.subject_code,
        sub.credits,
        COUNT(*)                                                            AS total,
        SUM(status IN ('Present','Late'))                                  AS present,
        SUM(status = 'Absent')                                             AS absent,
        ROUND(SUM(status IN ('Present','Late')) / COUNT(*) * 100, 2)      AS percentage,
        CASE
            WHEN ROUND(SUM(status IN ('Present','Late'))/COUNT(*)*100,2) >= 85 THEN '✅ Excellent'
            WHEN ROUND(SUM(status IN ('Present','Late'))/COUNT(*)*100,2) >= 75 THEN '👍 Good'
            WHEN ROUND(SUM(status IN ('Present','Late'))/COUNT(*)*100,2) >= 65 THEN '⚠️ Warning'
            ELSE '🚨 At Risk — Detain Risk'
        END AS remark
    FROM attendance a
    JOIN subjects sub ON a.subject_id = sub.subject_id
    WHERE a.student_id = p_student_id
    GROUP BY sub.subject_id
    ORDER BY percentage DESC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS CalculateSGPA;
DELIMITER $$
CREATE PROCEDURE CalculateSGPA(IN p_student_id INT, IN p_semester INT)
BEGIN
    SELECT
        s.name,
        s.roll_no,
        s.division,
        p_semester AS semester,
        ROUND(SUM(gs.grade_point * sub.credits) / SUM(sub.credits), 2) AS sgpa,
        SUM(sub.credits)                                                  AS total_credits,
        COUNT(DISTINCT gs.subject_id)                                     AS subjects_count
    FROM grade_summary gs
    JOIN students s  ON gs.student_id = s.student_id
    JOIN subjects sub ON gs.subject_id = sub.subject_id
    WHERE gs.student_id = p_student_id
      AND sub.semester = p_semester
    GROUP BY s.student_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS GetClassRanking;
DELIMITER $$
CREATE PROCEDURE GetClassRanking(IN p_semester INT)
BEGIN
    SELECT
        s.roll_no,
        s.name,
        s.division,
        ROUND(AVG(gs.percentage), 2)   AS avg_percentage,
        ROUND(SUM(gs.grade_point * sub.credits) / SUM(sub.credits), 2) AS sgpa,
        RANK() OVER (ORDER BY AVG(gs.percentage) DESC) AS class_rank
    FROM grade_summary gs
    JOIN students s  ON gs.student_id = s.student_id
    JOIN subjects sub ON gs.subject_id = sub.subject_id
    WHERE sub.semester = p_semester
    GROUP BY s.student_id
    ORDER BY class_rank;
END$$
DELIMITER ;

-- ============================================================
-- TRIGGERS
-- ============================================================

DROP TRIGGER IF EXISTS before_attendance_insert;
DELIMITER $$
CREATE TRIGGER before_attendance_insert
BEFORE INSERT ON attendance
FOR EACH ROW
BEGIN
    IF NEW.date > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot mark attendance for a future date.';
    END IF;
    INSERT INTO audit_log (action, table_name, record_id, details)
    VALUES ('INSERT', 'attendance', NEW.student_id,
            CONCAT('Subject:', NEW.subject_id, ' Date:', NEW.date, ' Status:', NEW.status));
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS before_grade_insert;
DELIMITER $$
CREATE TRIGGER before_grade_insert
BEFORE INSERT ON grades
FOR EACH ROW
BEGIN
    IF NEW.marks > NEW.max_marks THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Marks cannot exceed maximum marks.';
    END IF;
    IF NEW.marks < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Marks cannot be negative.';
    END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS after_grade_insert;
DELIMITER $$
CREATE TRIGGER after_grade_insert
AFTER INSERT ON grades
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (action, table_name, record_id, details)
    VALUES ('INSERT', 'grades', NEW.student_id,
            CONCAT('Subject:', NEW.subject_id, ' Exam:', NEW.exam_type,
                   ' Marks:', NEW.marks, '/', NEW.max_marks));
END$$
DELIMITER ;

-- ============================================================
-- SAMPLE DATA
-- ============================================================

INSERT IGNORE INTO subjects (subject_code, subject_name, credits, semester) VALUES
('22414', 'Database Management System',  3, 4),
('22415', 'Theory of Computation',       3, 4),
('22416', 'Computer Networks',           3, 4),
('22417', 'Software Engineering',        3, 4),
('22418', 'Artificial Intelligence',     3, 4);

INSERT IGNORE INTO students (roll_no, name, email, phone, division) VALUES
('2240021', 'Bhavsar Bhumi Anupam', 'bhumi@sagms.dev',  '9876543210', 'G3'),
('2240022', 'Bhor Aditya Namdev',   'aditya@sagms.dev', '9876543211', 'G3'),
('2240023', 'Chaure Yash Dipak',    'yash@sagms.dev',   '9876543212', 'G3'),
('2240024', 'Patil Sneha Ramesh',   'sneha@sagms.dev',  '9876543213', 'G3'),
('2240025', 'Shinde Rohan Vijay',   'rohan@sagms.dev',  '9876543214', 'G3'),
('2240026', 'Jadhav Priya Suresh',  'priya@sagms.dev',  '9876543215', 'G3');

INSERT IGNORE INTO attendance (student_id, subject_id, date, status) VALUES
(1,1,'2025-01-06','Present'),(1,1,'2025-01-08','Present'),(1,1,'2025-01-10','Absent'),
(1,1,'2025-01-13','Present'),(1,1,'2025-01-15','Present'),(1,1,'2025-01-17','Present'),
(1,1,'2025-01-20','Present'),(1,1,'2025-01-22','Present'),(1,1,'2025-01-24','Present'),
(2,1,'2025-01-06','Present'),(2,1,'2025-01-08','Absent'), (2,1,'2025-01-10','Absent'),
(2,1,'2025-01-13','Present'),(2,1,'2025-01-15','Absent'), (2,1,'2025-01-17','Present'),
(2,1,'2025-01-20','Absent'), (2,1,'2025-01-22','Present'),(2,1,'2025-01-24','Present'),
(3,1,'2025-01-06','Absent'), (3,1,'2025-01-08','Present'),(3,1,'2025-01-10','Present'),
(3,1,'2025-01-13','Present'),(3,1,'2025-01-15','Present'),(3,1,'2025-01-17','Present'),
(3,1,'2025-01-20','Present'),(3,1,'2025-01-22','Present'),(3,1,'2025-01-24','Present'),
(1,2,'2025-01-07','Present'),(1,2,'2025-01-09','Present'),(1,2,'2025-01-14','Present'),
(2,2,'2025-01-07','Absent'), (2,2,'2025-01-09','Present'),(2,2,'2025-01-14','Present'),
(3,2,'2025-01-07','Present'),(3,2,'2025-01-09','Present'),(3,2,'2025-01-14','Present');

INSERT IGNORE INTO grades (student_id, subject_id, exam_type, marks, max_marks, semester) VALUES
(1,1,'IA1',38,50,4),(1,1,'IA2',42,50,4),(1,1,'ESE',72,100,4),(1,1,'Practical',38,50,4),
(2,1,'IA1',28,50,4),(2,1,'IA2',30,50,4),(2,1,'ESE',55,100,4),(2,1,'Practical',30,50,4),
(3,1,'IA1',45,50,4),(3,1,'IA2',47,50,4),(3,1,'ESE',88,100,4),(3,1,'Practical',46,50,4),
(1,2,'IA1',35,50,4),(1,2,'IA2',40,50,4),(1,2,'ESE',68,100,4),
(2,2,'IA1',30,50,4),(2,2,'IA2',32,50,4),(2,2,'ESE',60,100,4),
(3,2,'IA1',44,50,4),(3,2,'IA2',48,50,4),(3,2,'ESE',90,100,4),
(4,1,'IA1',40,50,4),(4,1,'IA2',44,50,4),(4,1,'ESE',78,100,4),
(5,1,'IA1',32,50,4),(5,1,'IA2',35,50,4),(5,1,'ESE',62,100,4),
(6,1,'IA1',48,50,4),(6,1,'IA2',49,50,4),(6,1,'ESE',93,100,4);
