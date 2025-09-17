<?php
include "../config/db_connection.php";


if (isset($_GET['approve'])) {
    $id = intval($_GET['approve']);
    $conn->query("UPDATE courses SET status='published' WHERE id=$id");
}
if (isset($_GET['reject'])) {
    $id = intval($_GET['reject']);
    $conn->query("UPDATE courses SET status='archived' WHERE id=$id");
}


$courses = $conn->query("SELECT courses.*, users.name as instructor 
                         FROM courses 
                         JOIN users ON courses.instructor_id = users.id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">

 
  <nav class="bg-red-600 p-4 shadow-lg flex justify-between items-center">
    <h1 class="text-white text-2xl font-bold">Learning Management System</h1>
    <a href="index.php" 
       class="bg-white text-red-600 font-semibold px-4 py-2 rounded-lg hover:bg-gray-200 transition">
       Logout
    </a>
  </nav>

  
  <div class="flex justify-center mt-10">
    <h2 class="text-3xl font-semibold text-gray-700">Welcome, Admin</h2>
  </div>

  
  <div class="p-10">
    <h1 class="text-2xl font-bold mb-4">Manage Courses</h1>
    <table class="w-full border bg-white shadow">
      <tr class="bg-gray-200">
        <th class="p-2 border">Title</th>
        <th class="p-2 border">Instructor</th>
        <th class="p-2 border">Status</th>
        <th class="p-2 border">Actions</th>
      </tr>
      <?php while($row = $courses->fetch_assoc()): ?>
      <tr>
        <td class="border p-2"><?= htmlspecialchars($row['title']) ?></td>
        <td class="border p-2"><?= htmlspecialchars($row['instructor']) ?></td>
        <td class="border p-2"><?= htmlspecialchars($row['status']) ?></td>
        <td class="border p-2">
          <a href="?approve=<?= $row['id'] ?>" class="text-green-600 font-semibold hover:underline">Approve</a> |
          <a href="?reject=<?= $row['id'] ?>" class="text-red-600 font-semibold hover:underline"
             onclick="return confirm('Are you sure you want to reject this course?')">Reject</a>
        </td>
      </tr>
      <?php endwhile; ?>
    </table>
  </div>

</body>
</html>
