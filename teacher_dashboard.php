<?php
session_start();
require_once __DIR__ . '/db.php';

// Check if user is logged in and is teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: StaffLogin.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];
$teacher_name = $_SESSION['full_name'] ?? $_SESSION['username'];

$mysqli = db_connect();

// Get teacher's assigned courses
$assigned_courses_query = "
    SELECT c.*, tca.academic_year, tca.semester, tca.assignment_date,
           COUNT(DISTINCT sce.student_id) as enrolled_students
    FROM teacher_course_assignments tca
    JOIN courses c ON tca.course_id = c.id
    LEFT JOIN student_course_eligibility sce ON c.id = sce.course_id AND sce.status = 'approved'
    WHERE tca.teacher_id = ? AND tca.status = 'active'
    GROUP BY c.id
    ORDER BY c.course_code
";
$stmt = $mysqli->prepare($assigned_courses_query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$assigned_courses = $stmt->get_result();

// Get pending student requests
$pending_requests_query = "
    SELECT sce.*, u.first_name, u.surname, u.email, c.course_code, c.course_name
    FROM student_course_eligibility sce
    JOIN users u ON sce.student_id = u.id
    JOIN courses c ON sce.course_id = c.id
    WHERE sce.teacher_id = ? AND sce.status = 'pending'
    ORDER BY sce.assigned_date DESC
";
$stmt = $mysqli->prepare($pending_requests_query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$pending_requests = $stmt->get_result();

$mysqli->close();

function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard | Course Management</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .container { max-width: 1400px; margin: 2rem auto; padding: 0 2rem; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .stat-value { font-size: 2rem; font-weight: bold; color: #667eea; }
        .stat-label { color: #666; margin-top: 0.5rem; }
        
        .section-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary { background: #667eea; color: white; }
        .btn-success { background: #48bb78; color: white; }
        .btn-danger { background: #f56565; color: white; }
        .btn-warning { background: #ed8936; color: white; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; font-weight: 600; }
        tr:hover { background: #f7fafc; }
        
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
        }
        
        .course-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            transition: all 0.3s;
        }
        
        .course-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); transform: translateY(-2px); }
        .course-code { color: #667eea; font-weight: 600; font-size: 0.85rem; }
        .course-name { font-size: 1.1rem; font-weight: 600; margin: 0.5rem 0; }
        
        .badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
        }
        
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-approved { background: #d4edda; color: #155724; }
        
        @media (max-width: 768px) {
            .container { padding: 0 1rem; }
            .course-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h2>📚 Teacher Dashboard - Course Management</h2>
        <div>
            <span>👋 <?php echo e($teacher_name); ?></span>
            <a href="logout.php" style="color: white; margin-left: 1rem;">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $assigned_courses->num_rows; ?></div>
                <div class="stat-label">My Assigned Courses</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $pending_requests->num_rows; ?></div>
                <div class="stat-label">Pending Student Requests</div>
            </div>
        </div>
        
        <!-- My Assigned Courses -->
        <div class="section-card">
            <div class="section-header">
                <h2>📖 My Assigned Courses</h2>
                <a href="assign_course_to_students.php" class="btn btn-primary">+ Assign Course to Students</a>
            </div>
            
            <div class="course-grid">
                <?php while ($course = $assigned_courses->fetch_assoc()): ?>
                <div class="course-card">
                    <div class="course-code"><?php echo e($course['course_code']); ?></div>
                    <div class="course-name"><?php echo e($course['course_name']); ?></div>
                    <div class="course-details" style="font-size: 0.85rem; color: #666;">
                        <p>Credits: <?php echo $course['credits']; ?></p>
                        <p>Enrolled: <?php echo $course['enrolled_students']; ?> students</p>
                        <p>Semester: <?php echo e($course['semester']); ?></p>
                    </div>
                    <div style="margin-top: 1rem;">
                        <a href="manage_course_students.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary btn-sm">Manage Students</a>
                        <a href="view_course_requests.php?course_id=<?php echo $course['id']; ?>" class="btn btn-warning btn-sm">View Requests</a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        
        <!-- Pending Requests -->
        <?php if ($pending_requests->num_rows > 0): ?>
        <div class="section-card">
            <div class="section-header">
                <h2>⏳ Pending Student Registration Requests</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Request Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($request = $pending_requests->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo e($request['first_name'] . ' ' . $request['surname']); ?></td>
                        <td><?php echo e($request['email']); ?></td>
                        <td><?php echo e($request['course_code'] . ' - ' . $request['course_name']); ?></td>
                        <td><?php echo date('d M Y', strtotime($request['assigned_date'])); ?></td>
                        <td>
                            <a href="process_request.php?id=<?php echo $request['id']; ?>&action=approve" class="btn btn-success btn-sm">Approve</a>
                            <a href="process_request.php?id=<?php echo $request['id']; ?>&action=reject" class="btn btn-danger btn-sm">Reject</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>