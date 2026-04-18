<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - SIGAP Desa</title>

  <!-- SB Admin & FontAwesome -->
  <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="css/sb-admin-2.min.css" rel="stylesheet">

  <style>
    body {
      background: linear-gradient(135deg, #b30000, #e74c3c);
      font-family: 'Segoe UI', sans-serif;
    }
    .login-container {
      max-width: 420px;
      margin: 100px auto;
      padding: 40px 30px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 0 20px rgba(0,0,0,0.2);
      animation: fadeIn 0.8s ease;
    }
    .login-logo {
      display: flex;
      justify-content: center;
      margin-bottom: 20px;
    }
    .login-logo img {
      width: 150px;
      height: 130px;
    }
    .btn-sigap {
      background-color: #b30000;
      border: none;
    }
    .btn-sigap:hover {
      background-color: #900000;
    }
    h3 {
      color: #b30000;
      font-weight: 700;
      text-align: center;
      margin-bottom: 25px;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>

  <div class="login-container">
    <div class="login-logo">
      <img src="img/gmls_logo_red.png" alt="Logo GMLS">
    </div>

    <h3>Login ke SIGAP Desa</h3>

    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert-danger text-center">
        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
      </div>
    <?php endif; ?>

    <form action="proses_login.php" method="POST">
      <div class="form-group">
        <label for="tipe"><i class="fas fa-user-shield"></i> Login Sebagai:</label>
        <select name="tipe" id="tipe" class="form-control" required>
          <option value="">-- Pilih Tipe Akun --</option>
          <option value="admin">Admin</option>
          <option value="warga">Warga</option>
        </select>
      </div>

      <div class="form-group">
        <label><i class="fas fa-id-card"></i> Username / NIK:</label>
        <input type="text" name="username" class="form-control" placeholder="Masukkan username atau NIK" required>
      </div>

      <div class="form-group">
        <label><i class="fas fa-lock"></i> Password / Tanggal Lahir:</label>
        <input type="password" name="password" class="form-control" placeholder="Contoh: 23082003" required>
      </div>

      <button type="submit" class="btn btn-sigap btn-block text-white">Login</button>
    </form>

    <p class="text-center mt-4 text-muted small">© <?= date('Y'); ?> SIGAP Desa - GMLS</p>
  </div>

</body>
</html>
