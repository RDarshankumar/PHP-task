<?php
include "../config/db_connection.php";
$instructor_id = 1; 
$quiz_id = $_GET['quiz_id'] ?? 0;


$quiz_res = $conn->query("SELECT q.*, c.title as course_title FROM quizzes q JOIN courses c ON q.course_id=c.id WHERE q.id=$quiz_id AND c.instructor_id=$instructor_id");
$quiz = $quiz_res->fetch_assoc();


if (isset($_POST['add_question'])) {
    $question_text = $_POST['question_text'];
    $options = json_encode($_POST['options']);
    $correct_answer = $_POST['correct_answer'];

    $stmt = $conn->prepare("INSERT INTO questions (quiz_id, question_text, options, correct_answer) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $quiz_id, $question_text, $options, $correct_answer);
    if ($stmt->execute()) {
        echo "<script>alert('Question Added ✅');</script>";
    }
}


$questions = $conn->query("SELECT * FROM questions WHERE quiz_id=$quiz_id");


$submissions = $conn->query("SELECT s.*, u.name FROM submissions s JOIN users u ON s.student_id=u.id WHERE s.quiz_id=$quiz_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Questions - <?= htmlspecialchars($quiz['title']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6">

<div class="max-w-3xl mx-auto">



<button onclick="document.getElementById('modal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded mb-4">➕ Add Question</button>

<div id="modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center">
  <div class="bg-white p-6 rounded w-96 relative">
    <h2 class="text-xl font-bold mb-3">New Question</h2>
    <form method="POST">
      <textarea name="question_text" placeholder="Question" class="w-full mb-2 border p-2 rounded" required></textarea>
      <input type="text" name="options[]" placeholder="Option 1" class="w-full mb-2 border p-2 rounded" required>
      <input type="text" name="options[]" placeholder="Option 2" class="w-full mb-2 border p-2 rounded" required>
      <input type="text" name="options[]" placeholder="Option 3" class="w-full mb-2 border p-2 rounded" required>
      <input type="text" name="options[]" placeholder="Option 4" class="w-full mb-2 border p-2 rounded" required>
      <input type="text" name="correct_answer" placeholder="Correct Answer" class="w-full mb-2 border p-2 rounded" required>
      <div class="flex justify-end space-x-2">
        <button type="button" onclick="document.getElementById('modal').classList.add('hidden')" class="px-4 py-2 bg-gray-500 text-white rounded">Cancel</button>
        <button type="submit" name="add_question" class="px-4 py-2 bg-green-600 text-white rounded">Save</button>
      </div>
    </form>
  </div>
</div>


<h2 class="text-xl font-semibold mt-6 mb-2">Questions</h2>
<table class="w-full border text-center mb-6">
  <tr class="bg-gray-200">
    <th class="p-2 border">Question</th>
    <th class="p-2 border">Options</th>
    <th class="p-2 border">Correct Answer</th>
  </tr>
  <?php while($q = $questions->fetch_assoc()): 
        $opts = json_decode($q['options'], true);
  ?>
  <tr>
    <td class="border p-2"><?= htmlspecialchars($q['question_text']) ?></td>
    <td class="border p-2">
      <?php foreach($opts as $o) echo htmlspecialchars($o)."<br>"; ?>
    </td>
    <td class="border p-2 font-bold text-green-600"><?= htmlspecialchars($q['correct_answer']) ?></td>
  </tr>
  <?php endwhile; ?>
</table>


<h2 class="text-xl font-semibold mb-2">Student Submissions</h2>
<table class="w-full border text-center">
  <tr class="bg-gray-200">
    <th class="p-2 border">Student Name</th>
    <th class="p-2 border">Score</th>
    <th class="p-2 border">Submitted At</th>
  </tr>
  <?php while($s = $submissions->fetch_assoc()): ?>
  <tr>
    <td class="border p-2"><?= htmlspecialchars($s['name']) ?></td>
    <td class="border p-2"><?= $s['score'] ?></td>
    <td class="border p-2"><?= $s['submitted_at'] ?></td>
  </tr>
  <?php endwhile; ?>
</table>
<div class="max-w-3xl mx-auto">
<br>

<a href="instructor.php" class="inline-block mb-4 px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition">
  ← Go Back
</a>





</div>

</body>
</html>
