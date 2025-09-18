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


if (isset($_GET['delete_user'])) {
    $uid = intval($_GET['delete_user']);
    $conn->query("DELETE FROM users WHERE id=$uid");
}


$courses = $conn->query("SELECT courses.*, users.name as instructor 
                         FROM courses 
                         JOIN users ON courses.instructor_id = users.id");


$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_user"])) {
    $name     = trim($_POST["name"]);
    $email    = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $role     = trim($_POST["role"]);

    if (!empty($name) && !empty($email) && !empty($password) && !empty($role)) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);

        try {
            $stmt->execute();
            $message = "success";
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $message = "duplicate";
            } else {
                $message = "dberror";
            }
        }
        $stmt->close();
    } else {
        $message = "missing";
    }
}


$users = $conn->query("SELECT id, name, email, role FROM users");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    <table class="w-full border bg-white shadow mb-10">
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

  

  <div class="p-10">
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-2xl font-bold">Manage Users</h1>
      <button onclick="document.getElementById('userModal').classList.remove('hidden')" 
              class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
        Add User
      </button>
    </div>

    <table class="w-full border bg-white shadow">
      <tr class="bg-gray-200">
        <th class="p-2 border">Name</th>
        <th class="p-2 border">Email</th>
        <th class="p-2 border">Role</th>
        <th class="p-2 border">Action</th>
      </tr>
      <?php while($u = $users->fetch_assoc()): ?>
      <tr>
        <td class="border p-2"><?= htmlspecialchars($u['name']) ?></td>
        <td class="border p-2"><?= htmlspecialchars($u['email']) ?></td>
        <td class="border p-2"><?= htmlspecialchars($u['role']) ?></td>
        <td class="border p-2 text-center">
          <a href="?delete_user=<?= $u['id'] ?>" 
             onclick="return confirm('Are you sure you want to delete this user?')"
             class="text-red-600 font-semibold hover:underline">Delete</a>
        </td>
      </tr>
      <?php endwhile; ?>
    </table>
  </div>

  
  <div id="userModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6">
      <h2 class="text-xl font-bold mb-4">Add New User</h2>
      <form method="POST" class="space-y-4">
        <input type="hidden" name="add_user" value="1">
        <div>
          <label class="block text-gray-600 mb-1">Name</label>
          <input type="text" name="name" required
            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
        </div>
        <div>
          <label class="block text-gray-600 mb-1">Email</label>
          <input type="email" name="email" required
            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
        </div>
        <div>
          <label class="block text-gray-600 mb-1">Password</label>
          <input type="password" name="password" required
            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
        </div>
        <div>
          <label class="block text-gray-600 mb-1">Role</label>
          <select name="role" required
            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
            <option value="student">Student</option>
            <option value="instructor">Instructor</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" onclick="document.getElementById('userModal').classList.add('hidden')" 
                  class="px-4 py-2 bg-gray-300 rounded-lg">Cancel</button>
          <button type="submit" 
                  class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">Add User</button>
        </div>
      </form>
    </div>
  </div>

  
  <?php if ($message): ?>
  <script>
  <?php if ($message === "success"): ?>
    Swal.fire({ icon: 'success', title: 'User Added ✅', text: 'New user has been created!' })
      .then(() => { window.location = 'admin.php'; });
  <?php elseif ($message === "duplicate"): ?>
    Swal.fire({ icon: 'error', title: 'Oops...', text: 'Email already registered ❌' });
  <?php elseif ($message === "missing"): ?>
    Swal.fire({ icon: 'warning', title: 'Missing Fields', text: 'Please fill all fields ❌' });
  <?php else: ?>
    Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong with DB ❌' });
  <?php endif; ?>
  </script>
  <?php endif; ?>

</body>
</html>
