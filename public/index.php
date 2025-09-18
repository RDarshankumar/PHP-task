<?php
session_start();
include __DIR__ . "/../config/db_connection.php";

$message = "";
$redirect = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user["password"])) {
           
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["user_name"] = $user["name"];
            $_SESSION["user_role"] = $user["role"];

            
            try {
                $token = bin2hex(random_bytes(16)); 
            } catch (Exception $e) {
                $token = bin2hex(openssl_random_pseudo_bytes(16));
            }
            $_SESSION['token'] = $token;
            
            $_SESSION['token_expires'] = time() + 3600; 

            
            $role = $user["role"];
            if ($role === "student") {
                $redirect = "student.php";
            } elseif ($role === "instructor") {
                $redirect = "instructor.php";
            } elseif ($role === "admin") {
                $redirect = "admin.php";
            } else {
                $redirect = "index.php";
            }

            $clientUser = [
                'id' => (int)$user['id'],
                'name' => $user['name'],
                'role' => $user['role'],
            ];
            $jsUser = json_encode($clientUser, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
            $jsToken = json_encode($token);
            $jsRedirect = json_encode($redirect);

            echo '<!doctype html><html><head><meta charset="utf-8"><title>Logging in…</title>';
            echo '<meta name="viewport" content="width=device-width,initial-scale=1" />';
            echo '<script>';
            echo "try {\n";
            echo "  const user = {$jsUser};\n";
            echo "  user.token = {$jsToken};\n";
            echo "  user.token_expires = " . json_encode(time() + 3600) . ";\n";
            echo "  // save user object and token in localStorage\n";
            echo "  localStorage.setItem('user', JSON.stringify(user));\n";
            echo "  localStorage.setItem('token', user.token);\n";
            echo "  // optional: save a small flag\n";
            echo "  localStorage.setItem('isLoggedIn', '1');\n";
            echo "} catch (e) {\n";
            echo "  console.error('Could not save login info to localStorage', e);\n";
            echo "}\n";
            echo "window.location.href = {$jsRedirect};\n";
            echo '</script></head><body>';
            echo '<p style="font-family:system-ui, Arial, sans-serif; text-align:center; padding:20px;">Logging in…</p>';
            echo '</body></html>';
            exit();
        } else {
            $message = "invalid";
        }
    } else {
        $message = "invalid";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login Page</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="flex items-center justify-center min-h-screen bg-gray-100">

  <div class="w-full max-w-sm bg-white rounded-2xl shadow-lg p-6">
    <h1 class="text-2xl font-bold text-center text-gray-700 mb-6">LMS Login </h1>
   
    
    <form method="POST" action="index.php" class="space-y-4">
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

      <button type="submit"
        class="w-full bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-600 transition">
        Login
      </button>
    </form>
    <p class="mt-4 text-sm text-center text-gray-600">
      Don’t have an account? 
      <a href="register.php" class="text-blue-500 hover:underline">Go to Signup Page</a>
    </p>
  </div>

<?php if ($message === "invalid"): ?>
<script>
  Swal.fire({
    icon: 'error',
    title: 'Invalid Email or Password ❌',
    text: 'Please try again'
  });
</script>
<?php endif; ?>

</body>
</html>
