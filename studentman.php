
```php
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include 'db_connect.php';
$user = $conn->query("SELECT * FROM users WHERE id = {$_SESSION['user_id']}")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        .sidebar { background-color: #343a40; height: 100vh; padding: 20px; }
        .sidebar a { color: white; display: block; padding: 10px; margin: 5px 0; text-decoration: none; }
        .sidebar a:hover { background-color: #495057; }
        .content { padding: 20px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar">
                <h4 class="text-white">SMS Dashboard</h4>
                <a href="index.php?page=dashboard">Dashboard</a>
                <?php if ($user['role'] == 'admin'): ?>
                    <a href="index.php?page=enrollment">Enrollment</a>
                    <a href="index.php?page=timetable">Timetable Management</a>
                <?php endif; ?>
                <a href="index.php?page=attendance">Attendance</a>
                <a href="index.php?page=grades">Grades</a>
                <a href="index.php?page=messages">Messages</a>
                <a href="logout.php">Logout</a>
            </div>
            <!-- Content -->
            <div class="col-md-10 content">
                <?php
                $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
                switch ($page) {
                    case 'enrollment':
                        include 'enrollment.php';
                        break;
                    case 'attendance':
                        include 'attendance.php';
                        break;
                    case 'grades':
                        include 'grades.php';
                        break;
                    case 'messages':
                        include 'messages.php';
                        break;
                    case 'timetable':
                        include 'timetable.php';
                        break;
                    default:
                        include 'dashboard.php';
                        break;
                }
                ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

#### 3. Database Connection (`db_connect.php`)
Connects to the MySQL database.

```php
<?php
$host = 'localhost';
$db = 'sms';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
```

#### 4. Login Page (`login.php`)
Handles user authentication.

```php
<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        header("Location: index.php");
    } else {
        $error = "Invalid credentials";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title text-center">Login</h3>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
```

#### 5. Dashboard (`dashboard.php`)
Displays a welcome message and system overview.

```php
<div class="card">
    <div class="card-body">
        <h2>Welcome, <?php echo $_SESSION['role']; ?>!</h2>
        <p>This is the Student Management System. Use the sidebar to navigate through features.</p>
        <ul>
            <li><strong>Enrollment</strong>: Manage student data (Admin only).</li>
            <li><strong>Attendance</strong>: Track student attendance.</li>
            <li><strong>Grades</strong>: Manage student grades.</li>
            <li><strong>Messages</strong>: Communicate with users.</li>
            <li><strong>Timetable</strong>: View or manage class schedules (Admin creates, others view).</li>
        </ul>
    </div>
</div>
```

#### 6. Enrollment (`enrollment.php`)
Allows admins to add and view students.

```php
<?php
include 'db_connect.php';
if ($_SESSION['role'] != 'admin') {
    echo "<div class='alert alert-danger'>Access denied</div>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $student_id = $_POST['student_id'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    $conn->beginTransaction();
    try {
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'student')");
        $stmt->execute([$username, $password]);
        $user_id = $conn->lastInsertId();
        $stmt = $conn->prepare("INSERT INTO students (user_id, name, email, student_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $name, $email, $student_id]);
        $conn->commit();
        $success = "Student added successfully";
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

$students = $conn->query("SELECT * FROM students")->fetchAll();
?>

<div class="card">
    <div class="card-body">
        <h2>Student Enrollment</h2>
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" class="mb-4">
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="student_id" class="form-label">Student ID</label>
                <input type="text" class="form-control" id="student_id" name="student_id" required>
            </div>
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Add Student</button>
        </form>
        <h3>Student List</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Email</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo $student['student_id']; ?></td>
                        <td><?php echo $student['name']; ?></td>
                        <td><?php echo $student['email']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
```

#### 7. Attendance (`attendance.php`)
Allows teachers to record attendance and users to view records.

```php
<?php
include 'db_connect.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SESSION['role'] == 'teacher') {
    $student_id = $_POST['student_id'];
    $date = $_POST['date'];
    $status = $_POST['status'];
    $class_id = $_POST['class_id'];
    try {
        $stmt = $conn->prepare("INSERT INTO attendance (student_id, date, status, class_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$student_id, $date, $status, $class_id]);
        $success = "Attendance recorded";
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

$students = $conn->query("SELECT * FROM students")->fetchAll();
$attendance = $conn->query("SELECT a.*, s.name FROM attendance a JOIN students s ON a.student_id = s.id")->fetchAll();
?>

<div class="card">
    <div class="card-body">
        <h2>Attendance Tracking</h2>
        <?php if ($_SESSION['role'] == 'teacher'): ?>
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" class="mb-4">
                <div class="mb-3">
                    <label for="student_id" class="form-label">Student</label>
                    <select class="form-control" id="student_id" name="student_id" required>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>"><?php echo $student['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="date" name="date" required>
                </div>
                <div class="mb-3">
                    <label for="class_id" class="form-label">Class ID</label>
                    <input type="text" class="form-control" id="class_id" name="class_id" required>
                </div>
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-control" id="status" name="status" required>
                        <option value="present">Present</option>
                        <option value="absent">Absent</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Record Attendance</button>
            </form>
        <?php endif; ?>
        <h3>Attendance Records</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Date</th>
                    <th>Class ID</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attendance as $record): ?>
                    <tr>
                        <td><?php echo $record['name']; ?></td>
                        <td><?php echo $record['date']; ?></td>
                        <td><?php echo $record['class_id']; ?></td>
                        <td><?php echo ucfirst($record['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
```

#### 8. Grades (`grades.php`)
Manages student grades.

```php
<?php
include 'db_connect.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SESSION['role'] == 'teacher') {
    $student_id = $_POST['student_id'];
    $subject = $_POST['subject'];
    $grade = $_POST['grade'];
    $semester = $_POST['semester'];
    try {
        $stmt = $conn->prepare("INSERT INTO grades (student_id, subject, grade, semester) VALUES (?, ?, ?, ?)");
        $stmt->execute([$student_id, $subject, $grade, $semester]);
        $success = "Grade recorded";
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

$students = $conn->query("SELECT * FROM students")->fetchAll();
$grades = $conn->query("SELECT g.*, s.name FROM grades g JOIN students s ON g.student_id = s.id")->fetchAll();
?>

<div class="card">
    <div class="card-body">
        <h2>Grade Management</h2>
        <?php if ($_SESSION['role'] == 'teacher'): ?>
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" class="mb-4">
                <div class="mb-3">
                    <label for="student_id" class="form-label">Student</label>
                    <select class="form-control" id="student_id" name="student_id" required>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>"><?php echo $student['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="subject" class="form-label">Subject</label>
                    <input type="text" class="form-control" id="subject" name="subject" required>
                </div>
                <div class="mb-3">
                    <label for="grade" class="form-label">Grade</label>
                    <input type="text" class="form-control" id="grade" name="grade" required>
                </div>
                <div class="mb-3">
                    <label for="semester" class="form-label">Semester</label>
                    <input type="text" class="form-control" id="semester" name="semester" required>
                </div>
                <button type="submit" class="btn btn-primary">Record Grade</button>
            </form>
        <?php endif; ?>
        <h3>Grade Records</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Subject</th>
                    <th>Grade</th>
                    <th>Semester</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grades as $grade): ?>
                    <tr>
                        <td><?php echo $grade['name']; ?></td>
                        <td><?php echo $grade['subject']; ?></td>
                        <td><?php echo $grade['grade']; ?></td>
                        <td><?php echo $grade['semester']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
```

#### 9. Messages (`messages.php`)
Handles communication between users.

```php
<?php
include 'db_connect.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $receiver_id = $_POST['receiver_id'];
    $message = $_POST['message'];
    try {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $receiver_id, $message]);
        $success = "Message sent";
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

$users = $conn->query("SELECT * FROM users WHERE id != {$_SESSION['user_id']}")->fetchAll();
$messages = $conn->query("SELECT m.*, u.username AS sender FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.receiver_id = {$_SESSION['user_id']} OR m.sender_id = {$_SESSION['user_id']} ORDER BY m.sent_at DESC")->fetchAll();
?>

<div class="card">
    <div class="card-body">
        <h2>Messages</h2>
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" class="mb-4">
            <div class="mb-3">
                <label for="receiver_id" class="form-label">Recipient</label>
                <select class="form-control" id="receiver_id" name="receiver_id" required>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>"><?php echo $user['username']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="message" class="form-label">Message</label>
                <textarea class="form-control" id="message" name="message" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Send Message</button>
        </form>
        <h3>Message History</h3>
        <div class="list-group">
            <?php foreach ($messages as $msg): ?>
                <div class="list-group-item">
                    <strong>From: <?php echo $msg['sender']; ?></strong> at <?php echo $msg['sent_at']; ?>
                    <p><?php echo $msg['message']; ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
```

#### 10. Timetable Management (`timetable.php`)
Implements the new feature for creating and viewing class schedules.

```php
<?php
include 'db_connect.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SESSION['role'] == 'admin') {
    $class_id = $_POST['class_id'];
    $day = $_POST['day'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $subject = $_POST['subject'];
    $teacher_id = $_POST['teacher_id'];
    try {
        $stmt = $conn->prepare("INSERT INTO timetable (class_id, day, start_time, end_time, subject, teacher_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$class_id, $day, $start_time, $end_time, $subject, $teacher_id]);
        $success = "Timetable entry added";
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

$teachers = $conn->query("SELECT * FROM users WHERE role = 'teacher'")->fetchAll();
$timetable = $conn->query("SELECT t.*, u.username AS teacher FROM timetable t JOIN users u ON t.teacher_id = u.id" . ($_SESSION "role" != 'admin' ? " WHERE t.teacher_id = {$_SESSION['user_id']}" : ""))->fetchAll();
?>

<div class="card">
    <div class="card-body">
        <h2>Timetable Management</h2>
        <?php if ($_SESSION['role'] == 'admin'): ?>
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" class="mb-4">
                <div class="mb-3">
                    <label for="class_id" class="form-label">Class ID</label>
                    <input type="text" class="form-control" id="class_id" name="class_id" required>
                </div>
                <div class="mb-3">
                    <label for="day" class="form-label">Day</label>
                    <select class="form-control" id="day" name="day" required>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="start_time" class="form-label">Start Time</label>
                    <input type="time" class="form-control" id="start_time" name="start_time" required>
                </div>
                <div class="mb-3">
                    <label for="end_time" class="form-label">End Time</label>
                    <input type="time" class="form-control" id="end_time" name="end_time" required>
                </div>
                <div class="mb-3">
                    <label for="subject" class="form-label">Subject</label>
                    <input type="text" class="form-control" id="subject" name="subject" required>
                </div>
                <div class="mb-3">
                    <label for="teacher_id" class="form-label">Teacher</label>
                    <select class="form-control" id="teacher_id" name="teacher_id" required>
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?php echo $teacher['id']; ?>"><?php echo $teacher['username']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Add Timetable Entry</button>
            </form>
        <?php endif; ?>
        <h3>Timetable</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Class ID</th>
                    <th>Day</th>
                    <th>Time</th>
                    <th>Subject</th>
                    <th>Teacher</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($timetable as $entry): ?>
                    <tr>
                        <td><?php echo $entry['class_id']; ?></td>
                        <td><?php echo $entry['day']; ?></td>
                        <td><?php echo $entry['start_time'] . ' - ' . $entry['end_time']; ?></td>
                        <td><?php echo $entry['subject']; ?></td>
                        <td><?php echo $entry['teacher']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
```

#### 11. Logout (`logout.php`)
Ends the user session.

```php
<?php
session_start();
session_destroy();
header("Location: login.php");
exit;
?>
```

#### 12. CSS (Embedded in `index.php`)
The CSS is included in `index.php` using Bootstrap and custom styles for a responsive sidebar and content layout.