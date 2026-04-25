-- ============================================================
-- SAGMS - InfinityFree Compatible Schema
-- SPPU AIDS Group G3 | 2nd Year DBMS Mini Project
-- VIEWs, PROCEDUREs, TRIGGERs removed (not supported on free plan)
-- ============================================================

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
    INDEX idx_date   (date),
    INDEX idx_status (status)
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
