<?php
include "../config/db_connection.php";
session_start();
$student_id = intval($_SESSION['student_id'] ?? 1);

if (!isset($_GET['quiz_id']) || !isset($_GET['course_id'])) {
    die("Missing parameters.");
}
$quiz_id = intval($_GET['quiz_id']);
$course_id = intval($_GET['course_id']);

$q = $conn->prepare("SELECT id, title, course_id FROM quizzes WHERE id = ? AND course_id = ?");
$q->bind_param("ii", $quiz_id, $course_id);
$q->execute();
$qres = $q->get_result();
if ($qres->num_rows === 0) {
    die("Quiz not found for this course.");
}
$quiz = $qres->fetch_assoc();

$sub_chk = $conn->prepare("SELECT id FROM submissions WHERE student_id = ? AND quiz_id = ?");
$sub_chk->bind_param("ii", $student_id, $quiz_id);
$sub_chk->execute();
if ($sub_chk->get_result()->num_rows > 0) {
    header("Location: view_course.php?id=" . $course_id);
    exit;
}

$qs = $conn->prepare("SELECT id, question_text, options FROM questions WHERE quiz_id = ? ORDER BY id ASC");
$qs->bind_param("i", $quiz_id);
$qs->execute();
$questions_result = $qs->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Attempt Quiz - <?= htmlspecialchars($quiz['title']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6">
<div class="max-w-4xl mx-auto bg-white shadow-lg rounded-lg p-6">
  <h1 class="text-3xl font-bold mb-4"><?= htmlspecialchars($quiz['title']) ?></h1>

  <form method="POST" action="submit_quiz.php">
    <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">
    <input type="hidden" name="course_id" value="<?= $course_id ?>">

    <?php while ($qrow = $questions_result->fetch_assoc()):
        $qid = intval($qrow['id']);
        $opts_raw = $qrow['options'] ?? '[]';
        $decoded = json_decode($opts_raw, true);
        if (!is_array($decoded)) $decoded = [];
    ?>
      <div class="mb-4 p-3 border rounded">
        <p class="font-bold"><?= htmlspecialchars($qrow['question_text']) ?></p>

        <?php if (count($decoded) === 0): ?>
          <p class="text-sm text-red-500">No options available for this question.</p>
        <?php else: ?>
          <?php foreach ($decoded as $optIndex => $opt): 
              if (is_array($opt)) {
                  $display = $opt['text'] ?? $opt['option'] ?? $opt['value'] ?? $opt['label'] ?? json_encode($opt);
              } else {
                  $display = (string)$opt;
              }
              $val = $display;
          ?>
            <label class="block mt-1">
              <input type="radio" name="answers[<?= $qid ?>]" value="<?= htmlspecialchars($val) ?>"> <?= htmlspecialchars($display) ?>
            </label>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    <?php endwhile; ?>

    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Submit Quiz</button>
  </form>
</div>
</body>
</html>
