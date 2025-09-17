<?php
include __DIR__ . "/../config/db_connection.php"; 
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register Page</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="flex items-center justify-center min-h-screen bg-gray-100">

  <div class="w-full max-w-sm bg-white rounded-2xl shadow-lg p-6">
    <h1 class="text-2xl font-bold text-center text-gray-700 mb-6">Register</h1>
    
    <form method="POST" action="register.php" class="space-y-4">
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
          <option value="" disabled selected>Select Role</option>
          <option value="student">Student</option>
          <option value="instructor">Instructor</option>
          <option value="admin">Admin</option>
        </select>
      </div>

      <button type="submit"
        class="w-full bg-green-500 text-white py-2 rounded-lg hover:bg-green-600 transition">
        Register
      </button>
    </form>

    <p class="mt-4 text-sm text-center text-gray-600">
      Already have an account? 
      <a href="index.php" class="text-blue-500 hover:underline">Login</a>
    </p>
  </div>

<?php if ($message): ?>
<script>
<?php if ($message === "success"): ?>
  Swal.fire({
    icon: 'success',
    title: 'Registration Successful ✅',
    text: 'Your account has been created',
    confirmButtonText: 'Go to Login'
  }).then(() => {
    window.location = 'index.php';
  });
<?php elseif ($message === "duplicate"): ?>
  Swal.fire({
    icon: 'error',
    title: 'Oops...',
    text: 'Email already registered ❌'
  });
<?php elseif ($message === "missing"): ?>
  Swal.fire({
    icon: 'warning',
    title: 'Missing Fields',
    text: 'Please fill all fields ❌'
  });
<?php else: ?>
  Swal.fire({
    icon: 'error',
    title: 'Error',
    text: 'Something went wrong with DB ❌'
  });
<?php endif; ?>
</script>
<?php endif; ?>

</body>
</html>
