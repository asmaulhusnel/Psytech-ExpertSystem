<?php
session_start();
$conn = new mysqli("localhost", "root", "", "psytech");

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$alert = "";

// REGISTER
if (isset($_POST['register'])) {
  $username = trim($_POST['reg_username']);
  $email = trim($_POST['reg_email']);
  $password = trim($_POST['reg_password']);

  if (empty($username) || empty($email) || empty($password)) {
    $alert = "empty_fields";
  } elseif (strlen($password) < 8) {
    $alert = "weak_password";
  } else {
    $check = $conn->query("SELECT * FROM users WHERE username='$username'");
    if ($check->num_rows > 0) {
      $alert = "username_exists";
    } else {
      $hash = password_hash($password, PASSWORD_BCRYPT);
      $insert = $conn->query("INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$hash')");
      if ($insert) {
        $alert = "register_success";
      } else {
        $alert = "register_failed";
      }
    }
  }
}



// LOGIN
if (isset($_POST['login'])) {
  $username = trim($_POST['log_username']);
  $password = trim($_POST['log_password']);

  if (empty($username) || empty($password)) {
    $alert = "empty_fields";
  } else {
    $query = $conn->query("SELECT * FROM users WHERE username='$username'");
    if ($query->num_rows == 1) {
      $user = $query->fetch_assoc();
      if (password_verify($password, $user['password'])) {
        $_SESSION['username'] = $user['username'];
        $_SESSION['login_success'] = true;
        header("Location: ../Dashboard/index.php");
        exit();
      } else {
        $alert = "wrong_password";
      }
    } else {
      $alert = "user_not_found";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <script src="https://kit.fontawesome.com/64d58efce2.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="style.css" />
  <title>Registrasi & Login</title>
</head>

<body>
  <div class="container">
    <div class="forms-container">
      <div class="signin-signup">

        <!-- Login Form -->
        <form method="POST" class="sign-in-form">
          <h2 class="title">Sign in</h2>
          <div class="input-field">
            <i class="fas fa-user"></i>
            <input type="text" name="log_username" placeholder="Username" required />
          </div>
          <div class="input-field">
            <i class="fas fa-lock"></i>
            <input type="password" name="log_password" placeholder="Password" required />
          </div>
          <input type="submit" name="login" value="Login" class="btn solid" />
          <a href="../index.html">Kembali ke Menu Utama</a>
        </form>

        <!-- Register Form -->
        <form method="POST" class="sign-up-form">
          <h2 class="title">Sign up</h2>
          <div class="input-field">
            <i class="fas fa-user"></i>
            <input type="text" name="reg_username" placeholder="Username" required />
          </div>
          <div class="input-field">
            <i class="fas fa-envelope"></i>
            <input type="email" name="reg_email" placeholder="Email" required />
          </div>
          <div class="input-field">
            <i class="fas fa-lock"></i>
            <input type="password" name="reg_password" placeholder="Password" required />
          </div>
          <input type="submit" name="register" class="btn" value="Sign up" />
          <a href="../index.html">Kembali ke Menu Utama</a>
        </form>

      </div>
    </div>

    <div class="panels-container">
      <div class="panel left-panel">
        <div class="content">
          <h3>Daftar Sekarang</h3>
          <p>Belum punya akun? Silakan daftar di sini!</p>
          <button class="btn transparent" id="sign-up-btn">Sign up</button>
        </div>
        <img src="img/2.png" class="image" alt="" />
      </div>
      <div class="panel right-panel">
        <div class="content">
          <h3>Login</h3>
          <p>Sudah punya akun? Silakan login di sini!</p>
          <button class="btn transparent" id="sign-in-btn">Sign in</button>
        </div>
        <img src="img/1.png" class="image" alt="" />
      </div>
    </div>
  </div>

  <script src="app.js"></script>

  <!-- SweetAlert Handler -->
  <?php if (!empty($alert)): ?>
    <script>
      <?php if ($alert == "empty_fields"): ?>
        Swal.fire('Peringatan', 'Harap isi semua field!', 'warning');
      <?php elseif ($alert == "weak_password"): ?>
        Swal.fire('Password Lemah', 'Gunakan minimal 8 karakter untuk password!', 'error');
      <?php elseif ($alert == "username_exists"): ?>
        Swal.fire('Gagal', 'Username sudah terdaftar, gunakan yang lain!', 'error');
      <?php elseif ($alert == "register_success"): ?>
        Swal.fire({
          icon: 'success',
          title: 'Registrasi Berhasil',
          text: 'Silakan login sekarang!',
          timer: 2000,
          showConfirmButton: false
        }).then(() => {
          document.getElementById("sign-in-btn").click();
        });
      <?php elseif ($alert == "register_failed"): ?>
        Swal.fire('Error', 'Gagal mendaftarkan user.', 'error');
      <?php elseif ($alert == "user_not_found"): ?>
        Swal.fire('Gagal', 'Username tidak ditemukan!', 'error');
      <?php elseif ($alert == "wrong_password"): ?>
        Swal.fire('Gagal', 'Password salah!', 'error');
      <?php endif; ?>
    </script>
  <?php endif; ?>
</body>

</html>