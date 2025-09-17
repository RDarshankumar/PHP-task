<?php

session_start();
require_once "../config/db_connection.php";

$student_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : (isset($_SESSION['student_id']) ? intval($_SESSION['student_id']) : null);

if ($student_id === null) {

  $return = urlencode($_SERVER['REQUEST_URI']);
    header("Location: index.php?redirect={$return}");
    exit;
}


$student_name = $_SESSION['user_name'] ?? null;
if (!$student_name) {
    $tmp = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $tmp->bind_param("i", $student_id);
    $tmp->execute();
    $tr = $tmp->get_result();
    if ($tr && $tr->num_rows) {
        $student_name = $tr->fetch_assoc()['name'];
    }
}
if (!$student_name) $student_name = 'Student';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: Course ID missing.");
}
$course_id = intval($_GET['id']);

try {
    $course_check = $conn->prepare("SELECT id, title, status FROM courses WHERE id = ? AND status = 'published'");
    $course_check->bind_param("i", $course_id);
    $course_check->execute();
    $course_res = $course_check->get_result();
    if ($course_res->num_rows === 0) {
        die("Error: Course does not exist or is not published.");
    }
    $course = $course_res->fetch_assoc();

    $u = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $u->bind_param("i", $student_id);
    $u->execute();
    $ures = $u->get_result();
    if ($ures->num_rows === 0) {
        session_unset();
        session_destroy();
        header("Location: index.php?msg=" . urlencode("Please login again."));
        exit;
    }

    $check = $conn->prepare("SELECT id FROM enrollments WHERE course_id = ? AND student_id = ?");
    $check->bind_param("ii", $course_id, $student_id);
    $check->execute();
    $result = $check->get_result();
    $enrolled = false;
    $enroll_message = '';
    if ($result->num_rows == 0) {
        $enroll = $conn->prepare("INSERT INTO enrollments (course_id, student_id, enrolled_at) VALUES (?, ?, NOW())");
        $enroll->bind_param("ii", $course_id, $student_id);
        if ($enroll->execute()) {
            $enrolled = true;
            $enroll_message = "🎉 You are now enrolled in this course!";
        } else {
            $enroll_message = "Could not enroll automatically (DB error).";
        }
    } else {
        $enrolled = true;
        $enroll_message = "You are already enrolled in this course.";
    }

    $lessons = $conn->prepare("SELECT id, title, content, video_url FROM lessons WHERE course_id = ? ORDER BY id ASC");
    $lessons->bind_param("i", $course_id);
    $lessons->execute();
    $lessons_result = $lessons->get_result();
    $total_lessons = $lessons_result->num_rows;

    $completed_lessons = 0;
    $progress_percent = 0;
    if ($total_lessons > 0) {
        $progress_query = $conn->prepare("SELECT COUNT(*) as completed FROM lesson_progress WHERE course_id = ? AND student_id = ?");
        $progress_query->bind_param("ii", $course_id, $student_id);
        $progress_query->execute();
        $progress_data = $progress_query->get_result()->fetch_assoc();
        $completed_lessons = intval($progress_data['completed'] ?? 0);
        $progress_percent = round(($completed_lessons / $total_lessons) * 100);
    }

    if (isset($_GET['complete_lesson'])) {
        $lesson_id = intval($_GET['complete_lesson']);
        $chk_l = $conn->prepare("SELECT id FROM lessons WHERE id = ? AND course_id = ?");
        $chk_l->bind_param("ii", $lesson_id, $course_id);
        $chk_l->execute();
        $chk_res = $chk_l->get_result();
        if ($chk_res->num_rows > 0) {
            $check_progress = $conn->prepare("SELECT id FROM lesson_progress WHERE student_id = ? AND course_id = ? AND lesson_id = ?");
            $check_progress->bind_param("iii", $student_id, $course_id, $lesson_id);
            $check_progress->execute();
            $done = $check_progress->get_result();
            if ($done->num_rows == 0) {
                $insert_progress = $conn->prepare("INSERT INTO lesson_progress (student_id, course_id, lesson_id, completed_at) VALUES (?, ?, ?, NOW())");
                $insert_progress->bind_param("iii", $student_id, $course_id, $lesson_id);
                $insert_progress->execute();
            }
        }
        header("Location: view_course.php?id=" . $course_id);
        exit;
    }

    $quizzes_stmt = $conn->prepare("SELECT id, title FROM quizzes WHERE course_id = ? ORDER BY id ASC");
    $quizzes_stmt->bind_param("i", $course_id);
    $quizzes_stmt->execute();
    $quizzes_result = $quizzes_stmt->get_result();

} catch (Exception $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}

$msg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($course['title']) ?> - Course View</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6">
<div class="max-w-4xl mx-auto bg-white shadow-lg rounded-lg p-6">

  <div class="flex items-center justify-between mb-4">
    <div>
      <h1 class="text-2xl font-semibold text-gray-700"><?= htmlspecialchars($course['title']) ?></h1>
      <p class="text-sm text-gray-500">Welcome, <strong id="student-name-server"><?= htmlspecialchars($student_name) ?></strong></p>
    </div>
    <div>
      <?php if ($enroll_message): ?>
        <div class="p-2 bg-green-100 text-green-700 rounded"><?= htmlspecialchars($enroll_message) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="w-full bg-gray-200 rounded-full h-4 mb-6">
    <div class="bg-blue-600 h-4 rounded-full" style="width: <?= $progress_percent ?>%"></div>
  </div>
  <p class="mb-4 font-semibold text-gray-700">Progress: <?= $progress_percent ?>%</p>

  <ul class="space-y-4">
    <?php
    $lessons->execute();
    $lessons_result = $lessons->get_result();
    while ($l = $lessons_result->fetch_assoc()):
        $lesson_id = intval($l['id']);
        $is_done = $conn->prepare("SELECT id FROM lesson_progress WHERE student_id = ? AND course_id = ? AND lesson_id = ?");
        $is_done->bind_param("iii", $student_id, $course_id, $lesson_id);
        $is_done->execute();
        $done = $is_done->get_result()->num_rows > 0;
    ?>
    <li class="p-4 border rounded flex justify-between items-center">
      <div>
        <h2 class="text-xl font-bold"><?= htmlspecialchars($l['title']) ?></h2>
        <p class="text-gray-600"><?= nl2br(htmlspecialchars($l['content'])) ?></p>
        <?php if (!empty($l['video_url'])): ?>
          <a href="<?= htmlspecialchars($l['video_url']) ?>" target="_blank" class="text-blue-500 underline">Watch Video</a>
        <?php endif; ?>
      </div>
      <?php if ($done): ?>
        <span class="text-green-600 font-bold">Completed ✅</span>
      <?php else: ?>
        <a href="?id=<?= $course_id ?>&complete_lesson=<?= $lesson_id ?>" 
           class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
          Mark Complete
        </a>
      <?php endif; ?>
    </li>
    <?php endwhile; ?>
  </ul>

  <?php if ($quizzes_result->num_rows > 0): ?>
    <h2 class="text-2xl font-bold mt-8 mb-4">📝 Quizzes</h2>
    <ul class="space-y-4">
      <?php while ($quiz = $quizzes_result->fetch_assoc()):
          $q_id = intval($quiz['id']);
          $sub_stmt = $conn->prepare("SELECT score FROM submissions WHERE student_id = ? AND quiz_id = ?");
          $sub_stmt->bind_param("ii", $student_id, $q_id);
          $sub_stmt->execute();
          $sub_res = $sub_stmt->get_result();
          $submitted = $sub_res->num_rows > 0;
          $score = $submitted ? intval($sub_res->fetch_assoc()['score']) : null;
      ?>
        <li class="p-4 border rounded flex justify-between items-center">
          <div>
            <h3 class="text-xl font-bold"><?= htmlspecialchars($quiz['title']) ?></h3>
            <?php if ($submitted): ?>
              <p class="text-green-600 font-semibold">You scored: <?= $score ?>%</p>
            <?php endif; ?>
          </div>
          <?php if (!$submitted): ?>
            <a href="take_quiz.php?quiz_id=<?= $q_id ?>&course_id=<?= $course_id ?>" 
               class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition">
              Attempt Quiz
            </a>
          <?php else: ?>
            <span class="text-gray-600 italic">Completed ✅</span>
          <?php endif; ?>
        </li>
      <?php endwhile; ?>
    </ul>
  <?php endif; ?>

</div>

<script>
  try {
    const stored = localStorage.getItem('user');
    if (stored) {
      const user = JSON.parse(stored);
      if (user && user.name) {
        document.getElementById('student-name-server').innerText = user.name;
      }
    } else if (localStorage.getItem('user_name')) {
      document.getElementById('student-name-server').innerText = localStorage.getItem('user_name');
    }
  } catch(e) {
    console.error(e);
  }
</script>

</body>
</html>
