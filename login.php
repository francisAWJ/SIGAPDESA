<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - SIGAP Desa</title>

  <!-- Tailwind CSS -->
  <link href="./output.css" rel="stylesheet">

  <!-- FontAwesome untuk icon -->
  <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-b from-rose-500 to-rose-300 font-sans min-h-screen flex items-center justify-center">

  <div class="w-full max-w-md mx-4 sm:mx-auto bg-white rounded-xl shadow-2xl px-6 sm:px-8 py-8 sm:py-10">
    
    <!-- Logo -->
    <div class="flex justify-center mb-5">
      <img src="img/gmls_logo_red.png" alt="Logo GMLS" class="w-[150px] h-[130px]">
    </div>

    <!-- Title -->
    <h3 class="text-rose-700 font-bold text-center text-xl mb-6">
      Login ke SIGAP Desa
    </h3>

    <!-- Error -->
    <?php if (isset($_SESSION['error'])): ?>
      <div class="bg-red-100 text-red-700 text-center px-4 py-2 rounded mb-4">
        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
      </div>
    <?php endif; ?>

    <!-- Form -->
    <form action="proses_login.php" method="POST" class="space-y-4">
      
      <div>
        <label for="tipe" class="block text-sm font-medium text-gray-700 mb-1">
          <i class="fas fa-user-shield mr-1"></i> Login Sebagai:
        </label>
        <select name="tipe" id="tipe" required
          class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
          <option value="">-- Pilih Tipe Akun --</option>
          <option value="admin">Admin</option>
          <option value="warga">Warga</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          <i class="fas fa-id-card mr-1"></i> Username / NIK:
        </label>
        <input type="text" name="username" required
          placeholder="Masukkan username atau NIK"
          class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          <i class="fas fa-lock mr-1"></i> Password / Tanggal Lahir:
        </label>
        <input type="password" name="password" required
          placeholder="Contoh: 23082003"
          class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
      </div>

      <button type="submit"
        class="w-full bg-rose-500 hover:bg-rose-800 text-white font-medium py-2 rounded-md transition">
        Login
      </button>
    </form>

    <!-- Footer -->
    <p class="text-center mt-6 text-gray-500 text-sm">
      © <?= date('Y'); ?> SIGAP Desa - GMLS
    </p>
  </div>

  <!-- Animasi untuk login -->
  <style>
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>

</body>
</html>