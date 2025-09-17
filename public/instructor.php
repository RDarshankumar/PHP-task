<?php
session_start();
require_once "../config/db_connection.php";

$instructor_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;

function flash_redirect($url, $msg = null) {
    if ($msg) {
        header("Location: {$url}?msg=" . urlencode($msg));
    } else {
        header("Location: {$url}");
    }
    exit;
}

$base_url = $_SERVER['PHP_SELF'];

function post($key) {
    return isset($_POST[$key]) ? trim($_POST[$key]) : null;
}


if (isset($_GET['delete_id'])) {
    $course_id = (int) $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM courses WHERE id = ? AND instructor_id = ?");
    $stmt->bind_param("ii", $course_id, $instructor_id);
    if ($stmt->execute()) {
        flash_redirect($base_url, "Course Deleted ✅");
    } else {
        flash_redirect($base_url, "Error deleting course: " . $stmt->error);
    }
}

if (isset($_POST["add_course"])) {
    $title = post('title');
    $description = post('description');
    $price_raw = post('price');

    $errors = [];
    if ($title === '' || $title === null) $errors[] = "Title is required.";
    if ($description === '' || $description === null) $errors[] = "Description is required.";
    if ($price_raw === '' || $price_raw === null) {
        $price = 0.00;
    } else {
        
        if (!is_numeric($price_raw)) $errors[] = "Price must be a number.";
        $price = (float) $price_raw;
    }

    if (!empty($errors)) {
      
        flash_redirect($base_url, implode(' ', $errors));
    }

   .
    $insert_sql = "INSERT INTO courses (title, description, instructor_id, price, status) VALUES (?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($insert_sql);
    if ($stmt) {
        $stmt->bind_param("ssid", $title, $description, $instructor_id, $price);
        if ($stmt->execute()) {
            flash_redirect($base_url, "Course Created ✅ Waiting for Admin Approval.");
        } else {
            $err = $stmt->error;
            $stmt->close();
            if (stripos($err, 'Unknown column') !== false && stripos($err, 'status') !== false) {
                $stmt2 = $conn->prepare("INSERT INTO courses (title, description, instructor_id, price) VALUES (?, ?, ?, ?)");
                if ($stmt2) {
                    $stmt2->bind_param("ssid", $title, $description, $instructor_id, $price);
                    if ($stmt2->execute()) {
                        flash_redirect($base_url, "Course Created ✅");
                    } else {
                        flash_redirect($base_url, "Error creating course (retry): " . $stmt2->error);
                    }
                } else {
                    flash_redirect($base_url, "Error preparing retry insert: " . $conn->error);
                }
            } else {
                flash_redirect($base_url, "Error creating course: " . $err);
            }
        }
    } else {
        flash_redirect($base_url, "Error preparing statement: " . $conn->error);
    }
}


if (isset($_POST['edit_course'])) {
    $course_id = (int) post('course_id');
    $title = post('title');
    $description = post('description');
    $price_raw = post('price');

    $errors = [];
    if ($title === '' || $title === null) $errors[] = "Title is required.";
    if ($description === '' || $description === null) $errors[] = "Description is required.";
    if (!is_numeric($price_raw)) $errors[] = "Price must be a number.";
    $price = (float)$price_raw;

    if (!empty($errors)) {
        flash_redirect($base_url, implode(' ', $errors));
    }

    $stmt = $conn->prepare("UPDATE courses SET title = ?, description = ?, price = ? WHERE id = ? AND instructor_id = ?");
    if (!$stmt) flash_redirect($base_url, "Error preparing update: " . $conn->error);
    $stmt->bind_param("ssdii", $title, $description, $price, $course_id, $instructor_id);
    if ($stmt->execute()) {
        flash_redirect($base_url, "Course Updated ✅");
    } else {
        flash_redirect($base_url, "Error updating course: " . $stmt->error);
    }
}

if (isset($_POST["add_lesson"])) {
    $course_id = (int) post('course_id');
    $lesson_title = post('lesson_title');
    $content = post('content');
    $video_url = post('video_url');

    if (empty($course_id) || $lesson_title === '' || $lesson_title === null || $content === '' || $content === null) {
        flash_redirect($base_url, "Please fill all lesson fields.");
    }

    $stmt = $conn->prepare("INSERT INTO lessons (course_id, title, content, video_url) VALUES (?, ?, ?, ?)");
    if (!$stmt) flash_redirect($base_url, "Error preparing lesson insert: " . $conn->error);
    $stmt->bind_param("isss", $course_id, $lesson_title, $content, $video_url);
    if ($stmt->execute()) {
        flash_redirect($base_url, "Lesson Added ✅");
    } else {
        flash_redirect($base_url, "Error adding lesson: " . $stmt->error);
    }
}

if (isset($_POST["add_quiz"])) {
    $course_id = (int) post('quiz_course_id');
    $quiz_title = post('quiz_title');

    if (empty($course_id) || $quiz_title === '' || $quiz_title === null) {
        flash_redirect($base_url, "Please select a course and enter quiz title.");
    }

    $stmt = $conn->prepare("INSERT INTO quizzes (course_id, title) VALUES (?, ?)");
    if (!$stmt) flash_redirect($base_url, "Error preparing quiz insert: " . $conn->error);
    $stmt->bind_param("is", $course_id, $quiz_title);
    if ($stmt->execute()) {
        flash_redirect($base_url, "Quiz Created ✅");
    } else {
        flash_redirect($base_url, "Error creating quiz: " . $stmt->error);
    }
}

if (isset($_GET['delete_quiz_id'])) {
    $quiz_id = (int) $_GET['delete_quiz_id'];
    $stmt = $conn->prepare("
        DELETE q FROM quizzes q
        JOIN courses c ON q.course_id = c.id
        WHERE q.id = ? AND c.instructor_id = ?
    ");
    if (!$stmt) flash_redirect($base_url, "Error preparing delete quiz: " . $conn->error);
    $stmt->bind_param("ii", $quiz_id, $instructor_id);
    if ($stmt->execute()) {
        flash_redirect($base_url, "Quiz Deleted ✅");
    } else {
        flash_redirect($base_url, "Error deleting quiz: " . $stmt->error);
    }
}

if (isset($_POST["add_question"])) {
    $quiz_id = (int) post('quiz_id');
    $question_text = post('question_text');
    $options = isset($_POST['options']) ? $_POST['options'] : [];
    $correct_answer = post('correct_answer');

    if (empty($quiz_id) || $question_text === '' || $question_text === null || empty($options) || $correct_answer === '') {
        flash_redirect($base_url, "Please fill all question fields.");
    }

    $options_json = json_encode(array_values($options)); // ensure numeric keys
    $stmt = $conn->prepare("INSERT INTO questions (quiz_id, question_text, options, correct_answer) VALUES (?, ?, ?, ?)");
    if (!$stmt) flash_redirect($base_url, "Error preparing question insert: " . $conn->error);
    $stmt->bind_param("isss", $quiz_id, $question_text, $options_json, $correct_answer);
    if ($stmt->execute()) {
        flash_redirect($base_url, "Question Added ✅");
    } else {
        flash_redirect($base_url, "Error adding question: " . $stmt->error);
    }
}


$stmt = $conn->prepare("SELECT * FROM courses WHERE instructor_id = ?");
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$courses_result = $stmt->get_result();
$courses = $courses_result->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare("SELECT id, title FROM courses WHERE instructor_id = ?");
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$pub_res = $stmt->get_result();
$published_courses = $pub_res->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare("SELECT q.*, c.title as course_title FROM quizzes q JOIN courses c ON q.course_id = c.id WHERE c.instructor_id = ?");
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$quiz_res = $stmt->get_result();
$quizzes = $quiz_res->fetch_all(MYSQLI_ASSOC);

$edit_course = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int) $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ? AND instructor_id = ?");
    $stmt->bind_param("ii", $edit_id, $instructor_id);
    $stmt->execute();
    $r = $stmt->get_result();
    $edit_course = $r->fetch_assoc();
    if (!$edit_course) {
        flash_redirect($base_url, "Course not found or you are not allowed to edit it.");
    }
}

$msg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Instructor Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">


<nav class="bg-green-600 p-4 shadow-lg flex justify-between items-center">
  <h1 class="text-white text-2xl font-bold">Instructor Dashboard</h1>
    <a href="index.php" class="bg-white text-green-600 font-semibold px-4 py-2 rounded-lg hover:bg-gray-200 transition">Logout</a>
</nav>

<div class="max-w-5xl mx-auto mt-10 space-y-10">

  <?php if ($msg): ?>
    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-3 rounded">
      <?= $msg ?>
    </div>
  <?php endif; ?>

  <div class="text-center">
    <h2 class="text-3xl font-semibold text-gray-700">Welcome, Instructor</h2>
  </div>

  <div class="bg-white shadow p-6 rounded-lg">
    <h1 class="text-2xl font-bold mb-4">➕ Add New Course</h1>
    <form method="POST" novalidate>
      <input type="text" name="title" placeholder="Course Title" required class="w-full mb-3 border p-2 rounded">
      <textarea name="description" placeholder="Course Description" required class="w-full mb-3 border p-2 rounded"></textarea>
      <input type="number" step="0.01" name="price" placeholder="Price" required class="w-full mb-3 border p-2 rounded">
      <button type="submit" name="add_course" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Save</button>
    </form>
  </div>

  <div class="bg-white shadow p-6 rounded-lg">
    <h1 class="text-2xl font-bold mb-4">📚 Manage My Courses</h1>
    <table class="w-full border text-center">
      <tr class="bg-gray-200">
        <th class="p-2 border">Title</th>
        <th class="p-2 border">Status</th>
        <th class="p-2 border">Price</th>
        <th class="p-2 border">Actions</th>
      </tr>
      <?php foreach($courses as $row): ?>
      <tr>
        <td class="border p-2"><?= htmlspecialchars($row['title']) ?></td>
        <td class="border p-2"><?= htmlspecialchars($row['status'] ?? '—') ?></td>
        <td class="border p-2">$<?= number_format((float)($row['price'] ?? 0),2) ?></td>
        <td class="border p-2">
         <a href="<?= htmlspecialchars($base_url . '?edit_id=' . $row['id']) ?>" class="text-blue-600">Edit</a> |
         <a href="<?= htmlspecialchars($base_url . '?delete_id=' . $row['id']) ?>" class="text-red-600" onclick="return confirm('Are you sure?')">Delete</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <div class="bg-white shadow p-6 rounded-lg">
    <h1 class="text-2xl font-bold mb-4">📖 Add Lesson</h1>
    <form method="POST" novalidate>
      <select name="course_id" required class="w-full mb-3 border p-2 rounded">
        <option value="">-- Select Course --</option>
        <?php foreach($published_courses as $c): ?>
          <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" name="lesson_title" placeholder="Lesson Title" required class="w-full mb-3 border p-2 rounded">
      <textarea name="content" placeholder="Lesson Content" required class="w-full mb-3 border p-2 rounded"></textarea>
      <input type="text" name="video_url" placeholder="Video URL" class="w-full mb-3 border p-2 rounded">
      <button type="submit" name="add_lesson" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save</button>
    </form>
  </div>

  <div class="bg-white shadow p-6 rounded-lg">
    <h1 class="text-2xl font-bold mb-4">📝 Add Quiz</h1>
    <form method="POST" novalidate>
      <select name="quiz_course_id" required class="w-full mb-3 border p-2 rounded">
        <option value="">-- Select Course --</option>
        <?php foreach($published_courses as $c): ?>
          <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" name="quiz_title" placeholder="Quiz Title" required class="w-full mb-3 border p-2 rounded">
      <button type="submit" name="add_quiz" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">Create Quiz</button>
    </form>
  </div>

  <div class="bg-white shadow p-6 rounded-lg">
    <h1 class="text-2xl font-bold mb-4">📊 My Quizzes</h1>
    <table class="w-full border text-center">
      <tr class="bg-gray-200">
        <th class="p-2 border">Quiz Title</th>
        <th class="p-2 border">Course</th>
        <th class="p-2 border">Actions</th>
      </tr>
      <?php foreach($quizzes as $q): ?>
      <tr>
        <td class="border p-2"><?= htmlspecialchars($q['title']) ?></td>
        <td class="border p-2"><?= htmlspecialchars($q['course_title']) ?></td>
        <td class="border p-2">
          <a href="add_question.php?quiz_id=<?= (int)$q['id'] ?>" class="text-blue-600">Add Questions</a> |
          <a href="<?= htmlspecialchars($base_url . '?delete_quiz_id=' . $q['id']) ?>" class="text-red-600" onclick="return confirm('Are you sure?')">Delete</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <?php if ($edit_course): ?>
   <div class="bg-white shadow p-6 rounded-lg">
    <h1 class="text-2xl font-bold mb-4">✏️ Edit Course</h1>
    <form method="POST" novalidate>
        <input type="hidden" name="course_id" value="<?= (int)$edit_course['id'] ?>">
        <input type="text" name="title" value="<?= htmlspecialchars($edit_course['title']) ?>" required class="w-full mb-3 border p-2 rounded">
        <textarea name="description" required class="w-full mb-3 border p-2 rounded"><?= htmlspecialchars($edit_course['description']) ?></textarea>
        <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($edit_course['price']) ?>" required class="w-full mb-3 border p-2 rounded">
        <button type="submit" name="edit_course" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Update</button>
        <a href="<?= htmlspecialchars($base_url) ?>" class="ml-3 inline-block text-sm text-gray-600 hover:underline">Cancel</a>
    </form>
   </div>
  <?php endif; ?>

</div>

</body>
</html>
