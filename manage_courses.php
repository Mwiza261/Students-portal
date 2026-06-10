<?php
session_start();
require_once __DIR__ . '/db.php';

// Check if user is logged in and is staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: StaffLogin.php');
    exit;
}

$success_message = '';
$error_message = '';

$mysqli = db_connect();

// Handle Add Course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_course'])) {
    $course_code = trim($_POST['course_code']);
    $course_name = trim($_POST['course_name']);
    $description = trim($_POST['description']);
    $credits = intval($_POST['credits']);
    $department = trim($_POST['department']);
    $max_students = intval($_POST['max_students']);
    $semester = trim($_POST['semester']);
    
    $stmt = $mysqli->prepare("INSERT INTO courses (course_code, course_name, description, credits, department, max_students, semester, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
    $stmt->bind_param("sssisis", $course_code, $course_name, $description, $credits, $department, $max_students, $semester);
    
    if ($stmt->execute()) {
        $success_message = "Course '$course_name' added successfully!";
    } else {
        $error_message = "Failed to add course: " . $stmt->error;
    }
    $stmt->close();
}

// Handle Edit Course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_course'])) {
    $course_id = intval($_POST['course_id']);
    $course_code = trim($_POST['course_code']);
    $course_name = trim($_POST['course_name']);
    $description = trim($_POST['description']);
    $credits = intval($_POST['credits']);
    $department = trim($_POST['department']);
    $max_students = intval($_POST['max_students']);
    $semester = trim($_POST['semester']);
    $status = trim($_POST['status']);
    
    $stmt = $mysqli->prepare("UPDATE courses SET course_code=?, course_name=?, description=?, credits=?, department=?, max_students=?, semester=?, status=? WHERE id=?");
    $stmt->bind_param("sssisissi", $course_code, $course_name, $description, $credits, $department, $max_students, $semester, $status, $course_id);
    
    if ($stmt->execute()) {
        $success_message = "Course updated successfully!";
    } else {
        $error_message = "Failed to update course: " . $stmt->error;
    }
    $stmt->close();
}

// Handle Delete Course
if (isset($_GET['delete'])) {
    $course_id = intval($_GET['delete']);
    
    // Check if any students are registered
    $check_stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM student_courses WHERE course_id = ?");
    $check_stmt->bind_param("i", $course_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $count = $check_result->fetch_assoc()['count'];
    
    if ($count > 0) {
        $error_message = "Cannot delete course with registered students. Please remove students first or deactivate the course.";
    } else {
        $stmt = $mysqli->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->bind_param("i", $course_id);
        if ($stmt->execute()) {
            $success_message = "Course deleted successfully!";
        } else {
            $error_message = "Failed to delete course: " . $stmt->error;
        }
        $stmt->close();
    }
    $check_stmt->close();
}

// Get all courses
$courses_query = "SELECT * FROM courses ORDER BY course_code";
$courses_result = $mysqli->query($courses_query);

// Get course for editing
$edit_course = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_stmt = $mysqli->prepare("SELECT * FROM courses WHERE id = ?");
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();
    $edit_course = $edit_result->fetch_assoc();
    $edit_stmt->close();
}

$mysqli->close();

function e($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses | Staff Portal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fb;
            color: #333;
        }

        /* Navbar */
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo h2 {
            font-size: 1.5rem;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: background 0.3s;
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.1);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* Messages */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Buttons */
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a67d8;
        }

        .btn-success {
            background: #48bb78;
            color: white;
        }

        .btn-success:hover {
            background: #38a169;
        }

        .btn-warning {
            background: #ed8936;
            color: white;
        }

        .btn-warning:hover {
            background: #dd6b20;
        }

        .btn-danger {
            background: #f56565;
            color: white;
        }

        .btn-danger:hover {
            background: #e53e3e;
        }

        /* Form */
        .form-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .form-title {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 0.5rem;
            display: inline-block;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #555;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.7rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        /* Table */
        .courses-table {
            background: white;
            border-radius: 12px;
            overflow-x: auto;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #667eea;
            color: white;
            padding: 1rem;
            text-align: left;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #e1e4e8;
        }

        tr:hover {
            background: #f7fafc;
        }

        .status-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 2rem;
            width: 90%;
            max-width: 600px;
            border-radius: 12px;
            max-height: 80%;
            overflow-y: auto;
        }

        .close {
            float: right;
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar-container {
                flex-direction: column;
                gap: 1rem;
            }
            
            .container {
                padding: 0 1rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <div class="logo">
                <h2>📚 Staff Portal | Course Management</h2>
            </div>
            <div class="nav-links">
                <a href="manage_courses.php">Manage Courses</a>
                <a href="view_registrations.php">View Registrations</a>
                <div class="user-info">
                    <span>👋 Welcome, <?php echo e($_SESSION['username']); ?></span>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo e($success_message); ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo e($error_message); ?></div>
        <?php endif; ?>

        <!-- Add/Edit Course Form -->
        <div class="form-card">
            <h2 class="form-title"><?php echo $edit_course ? 'Edit Course' : 'Add New Course'; ?></h2>
            <form method="POST" action="">
                <?php if ($edit_course): ?>
                    <input type="hidden" name="course_id" value="<?php echo $edit_course['id']; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="course_code">Course Code *</label>
                        <input type="text" id="course_code" name="course_code" required 
                               value="<?php echo $edit_course ? e($edit_course['course_code']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="course_name">Course Name *</label>
                        <input type="text" id="course_name" name="course_name" required 
                               value="<?php echo $edit_course ? e($edit_course['course_name']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"><?php echo $edit_course ? e($edit_course['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="credits">Credits *</label>
                        <input type="number" id="credits" name="credits" required min="1" max="6"
                               value="<?php echo $edit_course ? $edit_course['credits'] : '3'; ?>">
                    </div>
                    <div class="form-group">
                        <label for="department">Department *</label>
                        <input type="text" id="department" name="department" required 
                               value="<?php echo $edit_course ? e($edit_course['department']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="max_students">Max Students</label>
                        <input type="number" id="max_students" name="max_students" min="1" max="200"
                               value="<?php echo $edit_course ? $edit_course['max_students'] : '50'; ?>">
                    </div>
                    <div class="form-group">
                        <label for="semester">Semester *</label>
                        <select id="semester" name="semester" required>
                            <option value="Semester 1" <?php echo ($edit_course && $edit_course['semester'] == 'Semester 1') ? 'selected' : ''; ?>>Semester 1</option>
                            <option value="Semester 2" <?php echo ($edit_course && $edit_course['semester'] == 'Semester 2') ? 'selected' : ''; ?>>Semester 2</option>
                        </select>
                    </div>
                </div>
                
                <?php if ($edit_course): ?>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="active" <?php echo $edit_course['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $edit_course['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                <?php endif; ?>
                
                <button type="submit" name="<?php echo $edit_course ? 'edit_course' : 'add_course'; ?>" class="btn btn-primary">
                    <?php echo $edit_course ? 'Update Course' : 'Add Course'; ?>
                </button>
                
                <?php if ($edit_course): ?>
                    <a href="manage_courses.php" class="btn">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Courses List -->
        <div class="courses-table">
            <h2 style="padding: 1rem; margin-bottom: 0;">All Courses</h2>
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Course Name</th>
                        <th>Credits</th>
                        <th>Department</th>
                        <th>Semester</th>
                        <th>Students</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($course = $courses_result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo e($course['course_code']); ?></strong></td>
                            <td><?php echo e($course['course_name']); ?></td>
                            <td><?php echo $course['credits']; ?></td>
                            <td><?php echo e($course['department']); ?></td>
                            <td><?php echo e($course['semester']); ?></td>
                            <td><?php echo $course['current_students']; ?>/<?php echo $course['max_students']; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $course['status']; ?>">
                                    <?php echo ucfirst($course['status']); ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <a href="?edit=<?php echo $course['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="javascript:void(0)" onclick="confirmDelete(<?php echo $course['id']; ?>, '<?php echo e($course['course_name']); ?>')" class="btn btn-danger btn-sm">Delete</a>
                                <a href="view_course_students.php?course_id=<?php echo $course['id']; ?>" class="btn btn-success btn-sm">View Students</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function confirmDelete(courseId, courseName) {
            if (confirm(`Are you sure you want to delete course "${courseName}"?\n\nThis action cannot be undone and will remove all associated data.`)) {
                window.location.href = `?delete=${courseId}`;
            }
        }
    </script>
</body>
</html>