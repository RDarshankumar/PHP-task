<?php
session_start();
require_once "../config/db_connection.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request method.');
}

$quiz_id = intval($_POST['quiz_id'] ?? 0);
$course_id = intval($_POST['course_id'] ?? 0);
$answers = $_POST['answers'] ?? [];

if ($quiz_id <= 0 || $course_id <= 0) {
    die('Invalid parameters.');
}

if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === "student") {
    $student_id = intval($_SESSION['user_id']);
    $student_name = $_SESSION['user_name'];
} else {
    $student_id = null;
    $student_name = "Guest User";
}

if (!$student_id) {
    die("Error: Please login as student before submitting quiz.");
}

$check_sub = $conn->prepare("SELECT id FROM submissions WHERE student_id = ? AND quiz_id = ?");
$check_sub->bind_param("ii", $student_id, $quiz_id);
$check_sub->execute();
if ($check_sub->get_result()->num_rows > 0) {
    header("Location: view_course.php?id=" . $course_id);
    exit;
}

$qs = $conn->prepare("SELECT id, correct_answer FROM questions WHERE quiz_id = ?");
$qs->bind_param("i", $quiz_id);
$qs->execute();
$qres = $qs->get_result();

$total = 0;
$correct = 0;
$answers_to_insert = [];

while ($row = $qres->fetch_assoc()) {
    $qid = intval($row['id']);
    $total++;
    $correct_answer = strtolower(trim($row['correct_answer'] ?? ""));
    $user_sel = strtolower(trim($answers[$qid] ?? ""));

    $is_correct = ($correct_answer && $user_sel === $correct_answer) ? 1 : 0;
    if ($is_correct) $correct++;

    $answers_to_insert[] = [$qid, $user_sel, $is_correct];
}

$score = $total > 0 ? round(($correct / $total) * 100) : 0;

$conn->begin_transaction();
try {
    $ins = $conn->prepare("INSERT INTO submissions (student_id, quiz_id, score, submitted_at) VALUES (?, ?, ?, NOW())");
    $ins->bind_param("iii", $student_id, $quiz_id, $score);
    $ins->execute();
    $submission_id = $conn->insert_id;

    if ($conn->query("SHOW TABLES LIKE 'submission_answers'")->num_rows > 0) {
        $ins_ans = $conn->prepare("INSERT INTO submission_answers (submission_id, question_id, selected_option, is_correct) VALUES (?, ?, ?, ?)");
        foreach ($answers_to_insert as $r) {
            $ins_ans->bind_param("iisi", $submission_id, $r[0], $r[1], $r[2]);
            $ins_ans->execute();
        }
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    die("Error: " . $e->getMessage());
}

header("Location: view_course.php?id=" . $course_id);
exit;
