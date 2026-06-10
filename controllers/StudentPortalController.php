<?php
/* ──────────────────────────────────────────────
   Student Portal Controller
   File: controllers/StudentPortalController.php
   ────────────────────────────────────────────── */

require_once BASE_PATH . '/core/Controller.php';

class StudentPortalController extends Controller {
    private $db;
    
    public function __construct() {
        parent::__construct();
        $this->db = Database::getInstance();
    }
    
    /**
     * Student Dashboard - Shows after login and subject selection
     */
    public function dashboard() {
        $this->requireLogin();
        
        $studentId = $_SESSION['user_id'];
        $currentYear = date('Y');
        
        // Get student details
        $student = $this->db->fetchOne(
            "SELECT * FROM users WHERE id = ? AND role = 'student'",
            [$studentId]
        );
        
        // Get selected class for current year
        $classSelection = $this->db->fetchOne(
            "SELECT * FROM student_class_selections 
             WHERE student_id = ? AND academic_year = ?",
            [$studentId, $currentYear]
        );
        
        if (!$classSelection) {
            $this->redirectToRoute('student_select_class');
            return;
        }
        
        // Get registered courses for the student
        $courses = $this->db->fetchAll(
            "SELECT c.*, scr.status, scr.grade, scr.score
             FROM courses c
             INNER JOIN student_course_registration scr ON c.id = scr.course_id
             WHERE scr.student_id = ? AND scr.academic_year = ?
             ORDER BY c.course_name",
            [$studentId, $currentYear]
        );
        
        // Get available courses for the class level
        $availableCourses = $this->db->fetchAll(
            "SELECT c.*, cls.is_compulsory
             FROM courses c
             INNER JOIN class_level_subjects cls ON c.id = cls.course_id
             WHERE cls.class_level = ? AND cls.academic_year = ?
             AND c.id NOT IN (
                 SELECT course_id FROM student_course_registration 
                 WHERE student_id = ? AND academic_year = ?
             )
             ORDER BY cls.is_compulsory DESC, c.course_name",
            [$classSelection['class_level'], $currentYear, $studentId, $currentYear]
        );
        
        $this->render('student/dashboard', [
            'title' => 'Student Dashboard - Chigoneka School',
            'student' => $student,
            'classLevel' => $classSelection['class_level'],
            'myCourses' => $courses,
            'availableCourses' => $availableCourses,
            'academicYear' => $currentYear,
            'flash' => $this->getFlash()
        ]);
    }
    
    /**
     * Show class selection page
     */
    public function showClassSelection() {
        $this->requireLogin();
        
        $studentId = $_SESSION['user_id'];
        $currentYear = date('Y');
        
        // Check if class already selected for this year
        $existing = $this->db->fetchOne(
            "SELECT * FROM student_class_selections 
             WHERE student_id = ? AND academic_year = ?",
            [$studentId, $currentYear]
        );
        
        if ($existing) {
            $this->setFlash('info', 'You have already selected your class for this academic year');
            $this->redirectToRoute('student_dashboard');
            return;
        }
        
        $student = $this->db->fetchOne(
            "SELECT * FROM users WHERE id = ?",
            [$studentId]
        );
        
        $this->render('student/select_class', [
            'title' => 'Select Your Class - Chigoneka School',
            'student' => $student,
            'flash' => $this->getFlash()
        ]);
    }
    
    /**
     * Process class selection
     */
    public function selectClass() {
        $this->requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToRoute('student_select_class');
            return;
        }
        
        $classLevel = $_POST['class_level'] ?? '';
        $validClasses = ['Form 1', 'Form 2', 'Form 3', 'Form 4'];
        
        if (!in_array($classLevel, $validClasses)) {
            $this->setFlash('error', 'Please select a valid class level');
            $this->redirectToRoute('student_select_class');
            return;
        }
        
        $studentId = $_SESSION['user_id'];
        $currentYear = date('Y');
        
        // Save class selection
        $this->db->query(
            "INSERT INTO student_class_selections (student_id, class_level, academic_year) 
             VALUES (?, ?, ?)",
            [$studentId, $classLevel, $currentYear]
        );
        
        // Update user table if you have class_level column
        $this->db->query(
            "UPDATE users SET class_level = ? WHERE id = ?",
            [$classLevel, $studentId]
        );
        
        $_SESSION['class_level'] = $classLevel;
        
        $this->setFlash('success', "You have successfully selected {$classLevel}!");
        $this->redirectToRoute('student_select_subjects');
    }
    
    /**
     * Show subject/course selection based on class
     */
    public function showSubjectSelection() {
        $this->requireLogin();
        
        $studentId = $_SESSION['user_id'];
        $currentYear = date('Y');
        
        // Get selected class
        $classSelection = $this->db->fetchOne(
            "SELECT * FROM student_class_selections 
             WHERE student_id = ? AND academic_year = ?",
            [$studentId, $currentYear]
        );
        
        if (!$classSelection) {
            $this->redirectToRoute('student_select_class');
            return;
        }
        
        // Check if subjects already selected
        $hasSubjects = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM student_course_registration 
             WHERE student_id = ? AND academic_year = ?",
            [$studentId, $currentYear]
        );
        
        if ($hasSubjects['count'] > 0) {
            $this->setFlash('info', 'You have already selected your subjects for this year');
            $this->redirectToRoute('student_dashboard');
            return;
        }
        
        // Get available courses for this class level
        $courses = $this->db->fetchAll(
            "SELECT c.*, cls.is_compulsory
             FROM courses c
             INNER JOIN class_level_subjects cls ON c.id = cls.course_id
             WHERE cls.class_level = ? AND cls.academic_year = ?
             AND c.status = 'active'
             ORDER BY cls.is_compulsory DESC, c.course_name",
            [$classSelection['class_level'], $currentYear]
        );
        
        // Get subject limits
        $limits = $this->getSubjectLimits($classSelection['class_level']);
        
        $this->render('student/select_subjects', [
            'title' => 'Select Your Subjects - Chigoneka School',
            'classLevel' => $classSelection['class_level'],
            'courses' => $courses,
            'minSubjects' => $limits['min'],
            'maxSubjects' => $limits['max'],
            'compulsoryCount' => $this->getCompulsoryCount($courses),
            'flash' => $this->getFlash()
        ]);
    }
    
    /**
     * Process subject/course selection
     */
    public function selectSubjects() {
        $this->requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToRoute('student_select_subjects');
            return;
        }
        
        $studentId = $_SESSION['user_id'];
        $currentYear = date('Y');
        $selectedCourses = $_POST['courses'] ?? [];
        
        // Get class selection
        $classSelection = $this->db->fetchOne(
            "SELECT * FROM student_class_selections 
             WHERE student_id = ? AND academic_year = ?",
            [$studentId, $currentYear]
        );
        
        if (!$classSelection) {
            $this->redirectToRoute('student_select_class');
            return;
        }
        
        // Get available courses for validation
        $availableCourses = $this->db->fetchAll(
            "SELECT c.id, cls.is_compulsory
             FROM courses c
             INNER JOIN class_level_subjects cls ON c.id = cls.course_id
             WHERE cls.class_level = ? AND cls.academic_year = ?",
            [$classSelection['class_level'], $currentYear]
        );
        
        $availableIds = array_column($availableCourses, 'id');
        $compulsoryIds = array_column(
            array_filter($availableCourses, function($c) { return $c['is_compulsory'] == 1; }),
            'id'
        );
        
        // Validate all selected courses are available
        $invalidCourses = array_diff($selectedCourses, $availableIds);
        if (!empty($invalidCourses)) {
            $this->setFlash('error', 'One or more selected subjects are not valid for your class');
            $this->redirectToRoute('student_select_subjects');
            return;
        }
        
        // Check compulsory subjects are included
        $missingCompulsory = array_diff($compulsoryIds, $selectedCourses);
        if (!empty($missingCompulsory)) {
            $this->setFlash('error', 'You must select all compulsory subjects for your class');
            $this->redirectToRoute('student_select_subjects');
            return;
        }
        
        // Validate min/max subjects
        $limits = $this->getSubjectLimits($classSelection['class_level']);
        if (count($selectedCourses) < $limits['min']) {
            $this->setFlash('error', "Please select at least {$limits['min']} subjects");
            $this->redirectToRoute('student_select_subjects');
            return;
        }
        
        if (count($selectedCourses) > $limits['max']) {
            $this->setFlash('error', "You can only select up to {$limits['max']} subjects");
            $this->redirectToRoute('student_select_subjects');
            return;
        }
        
        // Save course registrations
        foreach ($selectedCourses as $courseId) {
            $this->db->query(
                "INSERT INTO student_course_registration 
                 (student_id, course_id, academic_year, semester, status) 
                 VALUES (?, ?, ?, ?, 'enrolled')",
                [$studentId, $courseId, $currentYear, 'Full Year']
            );
            
            // Update enrolled count in courses table
            $this->db->query(
                "UPDATE courses SET enrolled_count = enrolled_count + 1 
                 WHERE id = ?",
                [$courseId]
            );
        }
        
        $this->setFlash('success', 'Your subjects have been successfully registered!');
        $this->redirectToRoute('student_dashboard');
    }
    
    /**
     * Get subject selection limits based on class level
     */
    private function getSubjectLimits($classLevel) {
        switch ($classLevel) {
            case 'Form 1':
            case 'Form 2':
                return ['min' => 6, 'max' => 8];
            case 'Form 3':
                return ['min' => 7, 'max' => 9];
            case 'Form 4':
                return ['min' => 7, 'max' => 10];
            default:
                return ['min' => 6, 'max' => 9];
        }
    }
    
    /**
     * Count compulsory subjects
     */
    private function getCompulsoryCount($courses) {
        return count(array_filter($courses, function($c) {
            return $c['is_compulsory'] == 1;
        }));
    }
    
    /**
     * Drop a course
     */
    public function dropCourse() {
        $this->requireLogin();
        
        $courseId = $_GET['course_id'] ?? 0;
        $studentId = $_SESSION['user_id'];
        $currentYear = date('Y');
        
        // Check if course can be dropped (not compulsory)
        $course = $this->db->fetchOne(
            "SELECT c.*, cls.is_compulsory 
             FROM courses c
             INNER JOIN class_level_subjects cls ON c.id = cls.course_id
             WHERE c.id = ? AND cls.academic_year = ?",
            [$courseId, $currentYear]
        );
        
        if ($course['is_compulsory']) {
            $this->setFlash('error', 'Compulsory subjects cannot be dropped');
            $this->redirectToRoute('student_dashboard');
            return;
        }
        
        // Update registration status
        $this->db->query(
            "UPDATE student_course_registration 
             SET status = 'dropped' 
             WHERE student_id = ? AND course_id = ? AND academic_year = ?",
            [$studentId, $courseId, $currentYear]
        );
        
        // Decrease enrolled count
        $this->db->query(
            "UPDATE courses SET enrolled_count = enrolled_count - 1 WHERE id = ?",
            [$courseId]
        );
        
        $this->setFlash('success', 'Course has been dropped successfully');
        $this->redirectToRoute('student_dashboard');
    }
    
    /**
     * Require login middleware
     */
    private function requireLogin() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
            $this->setFlash('error', 'Please login to access the student portal');
            $this->redirectToRoute('student_login');
            exit();
        }
    }
}