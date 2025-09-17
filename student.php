<?php
include "../config/db_connection.php";

$courses = $conn->query("SELECT * FROM courses WHERE status='published'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">

  <nav class="bg-blue-600 p-4 shadow-lg flex justify-between items-center">
    <h1 class="text-white text-2xl font-bold">Learning Management System</h1>
    <a href="index.php" 
       class="bg-white text-blue-600 font-semibold px-4 py-2 rounded-lg hover:bg-gray-200 transition">
       Logout
    </a>
  </nav>

  <div class="flex justify-center mt-10">
    <h2 class="text-3xl font-semibold text-gray-700">Welcome, Student</h2>
  </div>

  <div class="p-10">
    <h1 class="text-2xl font-bold mb-6">Available Courses</h1>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <?php while($c = $courses->fetch_assoc()): ?>
        <div class="bg-white shadow p-4 rounded-lg">
          <h2 class="text-xl font-bold"><?= htmlspecialchars($c['title']) ?></h2>
          <p class="text-gray-600"><?= htmlspecialchars($c['description']) ?></p>
          <p class="mt-2 text-green-600 font-semibold">$<?= number_format($c['price'], 2) ?></p>
          <a href="./view_course.php?id=<?= $c['id'] ?>" 
               class="mt-3 inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
            View
          </a>
        </div>
      <?php endwhile; ?>
    </div>
  </div>

</body>
</html>
