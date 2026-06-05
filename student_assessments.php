<?php
session_start();
require_once __DIR__ . '/db.php';

// Check if user is logged in and is student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: Login.php');
    exit;
}

$student_id = $_SESSION['user_id'];
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

$mysqli = db_connect();

// Get course details if specific course selected
$course = null;
if ($course_id > 0) {
    $course_stmt = $mysqli->prepare("SELECT * FROM courses WHERE id = ?");
    $course_stmt->bind_param("i", $course_id);
    $course_stmt->execute();
    $course = $course_stmt->get_result()->fetch_assoc();
}

// Get all assessments/grades for student
$assessments_query = "
    SELECT 
        g.*,
        c.course_code,
        c.course_name,
        c.credits,
        CONCAT(t.first_name, ' ', t.surname) as teacher_name
    FROM grades g
    JOIN courses c ON g.course_id = c.id
    LEFT JOIN users t ON g.teacher_id = t.id
    WHERE g.student_id = ?
";
if ($course_id > 0) {
    $assessments_query .= " AND g.course_id = ?";
}
$assessments_query .= " ORDER BY g.academic_year DESC, g.semester DESC, c.course_code";

$stmt = $mysqli->prepare($assessments_query);
if ($course_id > 0) {
    $stmt->bind_param("ii", $student_id, $course_id);
} else {
    $stmt->bind_param("i", $student_id);
}
$stmt->execute();
$assessments = $stmt->get_result();

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
    <title>Assessment Details | Student Portal</title>
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
        
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .card-header {
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .assessment-item {
            background: #f8fafc;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .assessment-info h3 { font-size: 1rem; color: #333; }
        .assessment-info p { font-size: 0.85rem; color: #666; margin-top: 0.3rem; }
        
        .assessment-score { text-align: right; }
        .score-value { font-size: 1.5rem; font-weight: bold; }
        .score-label { font-size: 0.75rem; color: #666; }
        
        .btn-back {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .container { padding: 0 1rem; }
            .assessment-item { flex-direction: column; text-align: center; gap: 0.5rem; }
            .assessment-score { text-align: center; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h2>📚 Assessment Details</h2>
        <div>
            <span>👋 <?php echo e($_SESSION['username']); ?></span>
            <a href="logout.php" style="color: white; margin-left: 1rem;">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <?php if ($course): ?>
        <a href="student_results.php" class="btn-back">← Back to Results</a>
        
        <div class="card">
            <div class="card-header">
                <h2><?php echo e($course['course_code']); ?> - <?php echo e($course['course_name']); ?></h2>
                <p style="color: #666; margin-top: 0.5rem;">Credits: <?php echo $course['credits']; ?></p>
            </div>
            
            <?php if ($assessments->num_rows > 0): ?>
                <?php while ($assessment = $assessments->fetch_assoc()): ?>
                <div class="assessment-item">
                    <div class="assessment-info">
                        <h3><?php echo ucfirst(str_replace('_', ' ', $assessment['assessment_type'])); ?></h3>
                        <p>Teacher: <?php echo e($assessment['teacher_name']); ?></p>
                        <p>Date: <?php echo date('d M Y', strtotime($assessment['entered_date'])); ?></p>
                        <?php if ($assessment['comments']): ?>
                        <p style="color: #667eea;">💬 <?php echo e($assessment['comments']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="assessment-score">
                        <div class="score-value" style="color: <?php echo $assessment['grade'] == 'F' ? '#f56565' : '#48bb78'; ?>">
                            <?php echo $assessment['score'] . '%'; ?>
                        </div>
                        <div class="score-label">Grade: <?php echo e($assessment['grade']); ?></div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align: center; padding: 2rem; color: #999;">No assessment records found.</p>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-header">
                <h2>All Assessments</h2>
            </div>
            
            <?php 
            $current_course = '';
            while ($assessment = $assessments->fetch_assoc()): 
                if ($current_course != $assessment['course_code']):
                    if ($current_course != '') echo '</div>';
                    $current_course = $assessment['course_code'];
            ?>
            <div style="margin-bottom: 1.5rem;">
                <h3 style="color: #667eea; margin-bottom: 0.5rem;">
                    <?php echo e($assessment['course_code']); ?> - <?php echo e($assessment['course_name']); ?>
                </h3>
            <?php endif; ?>
            
            <div class="assessment-item">
                <div class="assessment-info">
                    <h3><?php echo ucfirst(str_replace('_', ' ', $assessment['assessment_type'])); ?></h3>
                    <p>Semester: <?php echo e($assessment['semester']); ?> | Year: <?php echo e($assessment['academic_year']); ?></p>
                </div>
                <div class="assessment-score">
                    <div class="score-value" style="color: <?php echo $assessment['grade'] == 'F' ? '#f56565' : '#48bb78'; ?>">
                        <?php echo $assessment['score'] . '%'; ?>
                    </div>
                    <div class="score-label">Grade: <?php echo e($assessment['grade']); ?></div>
                </div>
            </div>
            
            <?php endwhile; ?>
            <?php if ($current_course != '') echo '</div>'; ?>
            
            <?php if ($assessments->num_rows == 0): ?>
                <p style="text-align: center; padding: 2rem; color: #999;">No assessment records found.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>