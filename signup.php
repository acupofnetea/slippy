<?php
include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = mysqli_real_escape_string($conn, $_POST['name']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $division_id = $_POST['division_id'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role_id  = 4; // Default Employee

    // Upload foto profil (opsional)
    $photo_name = "";
    if (!empty($_FILES['photo']['name'])) {
        $photo_name = time() . "_" . basename($_FILES['photo']['name']);
        $target = "uploads/" . $photo_name;
        move_uploaded_file($_FILES['photo']['tmp_name'], $target);
    }

    $sql = "INSERT INTO users (name, username, email, password, division_id, photo, role_id)
            VALUES ('$name', '$username', '$email', '$password', '$division_id', '$photo_name', '$role_id')";
    
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Registrasi berhasil! Silakan login.'); window.location='signin.php';</script>";
    } else {
        echo "<script>alert('Terjadi kesalahan: " . mysqli_error($conn) . "');</script>";
    }
}

// Ambil daftar divisi
$divisions = mysqli_query($conn, "SELECT * FROM divisions ORDER BY division_name ASC");
?>
<!DOCTYPE html>
<html lang="id" class="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar Akun | Slippy</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Modern Premium Design System */
    :root {
      /* Light Theme */
      --bg-primary: #f8fafc;
      --bg-secondary: #ffffff;
      --text-primary: #0f172a;
      --text-secondary: #64748b;
      --accent-primary: linear-gradient(135deg, #ff6b35, #ff8e35);
      --accent-secondary: #ff6b35;
      --accent-tertiary: #ff8e35;
      --border-primary: rgba(0, 0, 0, 0.05);
      --border-secondary: rgba(0, 0, 0, 0.1);
      --card-bg: rgba(255, 255, 255, 0.95);
      --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.03);
      --shadow-md: 0 4px 20px rgba(0, 0, 0, 0.08);
      --shadow-lg: 0 8px 30px rgba(0, 0, 0, 0.12);
      --radius-sm: 8px;
      --radius-md: 12px;
      --radius-lg: 16px;
      --transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
      --glass: rgba(255, 255, 255, 0.7);
    }

    .dark {
      /* Dark Theme */
      --bg-primary: #0f172a;
      --bg-secondary: #1e293b;
      --text-primary: #f8fafc;
      --text-secondary: #94a3b8;
      --accent-primary: linear-gradient(135deg, #ff6b35, #ff8e35);
      --accent-secondary: #ff6b35;
      --accent-tertiary: #ff8e35;
      --border-primary: rgba(255, 255, 255, 0.05);
      --border-secondary: rgba(255, 255, 255, 0.1);
      --card-bg: rgba(15, 23, 42, 0.95);
      --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.2);
      --shadow-md: 0 4px 20px rgba(0, 0, 0, 0.25);
      --shadow-lg: 0 8px 30px rgba(0, 0, 0, 0.3);
      --glass: rgba(15, 23, 42, 0.7);
    }
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    @font-face {
      font-family: 'SF Pro Display';
      src: url('https://fonts.cdnfonts.com/css/sf-pro-display') format('woff2');
    }

    body {
      font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Inter', sans-serif;
      background: var(--bg-primary);
      color: var(--text-primary);
      overflow-x: hidden;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      line-height: 1.5;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      transition: var(--transition);
    }
    
    /* Signup Container */
    .signup-container {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 2rem;
      position: relative;
      overflow: hidden;
      min-height: 100vh;
    }
    
    .signup-card {
      background: var(--glass);
      border: 1px solid var(--border-primary);
      border-radius: var(--radius-lg);
      padding: 3rem;
      width: 100%;
      max-width: 480px;
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      box-shadow: var(--shadow-lg);
      position: relative;
      z-index: 2;
      animation: fadeInUp 0.8s ease-out;
    }
    
    .logo {
      font-size: 2rem;
      font-weight: 700;
      letter-spacing: -0.5px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      color: var(--text-primary);
      text-decoration: none;
      transition: var(--transition);
      margin-bottom: 1.5rem;
    }

    .logo span {
      background: var(--accent-primary);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      font-weight: 700;
      font-size: 2rem;
    }
    
    .signup-header {
      text-align: center;
      margin-bottom: 2rem;
    }
    
    .signup-header h1 {
      font-size: 1.8rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
      letter-spacing: -0.5px;
    }
    
    .signup-header p {
      color: var(--text-secondary);
      font-size: 1rem;
    }
    
    /* Form Styles */
    .form-group {
      margin-bottom: 1.5rem;
      position: relative;
    }
    
    .form-label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 500;
      font-size: 0.9rem;
      color: var(--text-primary);
    }
    
    .form-input {
      width: 100%;
      padding: 0.9rem 1rem;
      background: var(--card-bg);
      border: 1px solid var(--border-secondary);
      border-radius: var(--radius-sm);
      font-size: 0.95rem;
      color: var(--text-primary);
      transition: var(--transition);
      outline: none;
    }
    
    .form-input:focus {
      border-color: var(--accent-secondary);
      box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
    }
    
    .form-input::placeholder {
      color: var(--text-secondary);
      opacity: 0.7;
    }
    
    .form-select {
      width: 100%;
      padding: 0.9rem 1rem;
      background: var(--card-bg);
      border: 1px solid var(--border-secondary);
      border-radius: var(--radius-sm);
      font-size: 0.95rem;
      color: var(--text-primary);
      transition: var(--transition);
      outline: none;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 1rem center;
      background-size: 1rem;
    }
    
    .form-select:focus {
      border-color: var(--accent-secondary);
      box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
    }
    
    .file-upload {
      position: relative;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      border: 2px dashed var(--border-secondary);
      border-radius: var(--radius-sm);
      padding: 1.5rem;
      transition: var(--transition);
      cursor: pointer;
      margin-bottom: 1.5rem;
    }
    
    .file-upload:hover {
      border-color: var(--accent-secondary);
    }
    
    .file-upload input {
      position: absolute;
      width: 100%;
      height: 100%;
      top: 0;
      left: 0;
      opacity: 0;
      cursor: pointer;
    }
    
    .file-upload-icon {
      width: 48px;
      height: 48px;
      background: var(--accent-primary);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 1rem;
    }
    
    .file-upload-icon svg {
      width: 24px;
      height: 24px;
      color: white;
    }
    
    .file-upload-text {
      text-align: center;
      color: var(--text-secondary);
      font-size: 0.9rem;
    }
    
    .file-upload-text span {
      color: var(--accent-secondary);
      font-weight: 600;
    }
    
    .preview-container {
      margin-bottom: 1.5rem;
      text-align: center;
    }
    
    .preview-container img {
      width: 120px;
      height: 120px;
      object-fit: cover;
      border-radius: 50%;
      border: 3px solid var(--accent-secondary);
      display: none;
      margin: 0 auto;
      box-shadow: var(--shadow-md);
    }
    
    /* Button Styles */
    .btn {
      text-decoration: none;
      padding: 0.9rem 1.75rem;
      border-radius: var(--radius-sm);
      font-weight: 500;
      transition: var(--transition);
      font-size: 0.95rem;
      border: none;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      letter-spacing: -0.2px;
      will-change: transform;
      width: 100%;
    }

    .btn-primary {
      background: var(--accent-primary);
      color: white;
      box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
      position: relative;
      overflow: hidden;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(255, 107, 53, 0.4);
    }

    .btn-primary::after {
      content: '';
      position: absolute;
      top: -50%;
      left: -60%;
      width: 200%;
      height: 200%;
      background: linear-gradient(
        to right,
        rgba(255, 255, 255, 0) 0%,
        rgba(255, 255, 255, 0.1) 50%,
        rgba(255, 255, 255, 0) 100%
      );
      transform: rotate(30deg);
      transition: var(--transition);
    }

    .btn-primary:hover::after {
      left: 100%;
    }
    
    /* Links */
    .signup-links {
      text-align: center;
      margin-top: 1.5rem;
      font-size: 0.9rem;
      color: var(--text-secondary);
    }
    
    .signup-links a {
      color: var(--accent-secondary);
      text-decoration: none;
      font-weight: 500;
      transition: var(--transition);
    }
    
    .signup-links a:hover {
      text-decoration: underline;
    }
    
    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      margin-top: 1.5rem;
      color: var(--text-secondary);
      text-decoration: none;
      font-size: 0.9rem;
      transition: var(--transition);
    }
    
    .back-link:hover {
      color: var(--accent-secondary);
    }
    
    /* Theme Toggle */
    .theme-toggle {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: var(--glass);
      border: 1px solid var(--border-secondary);
      cursor: pointer;
      transition: var(--transition);
      position: absolute;
      top: 2rem;
      right: 2rem;
      z-index: 3;
    }

    .theme-toggle:hover {
      border-color: var(--accent-secondary);
    }

    .theme-toggle svg {
      width: 18px;
      height: 18px;
      color: var(--text-primary);
    }
    
    /* 3D Elements */
    .floating-3d {
      position: absolute;
      width: 100px;
      height: 100px;
      z-index: 1;
      animation: float3d 15s infinite ease-in-out;
      opacity: 0.7;
    }
    
    .floating-3d:nth-child(1) {
      top: 15%;
      left: 10%;
      animation-delay: 0s;
    }
    
    .floating-3d:nth-child(2) {
      top: 65%;
      right: 15%;
      animation-delay: 2s;
    }
    
    .floating-3d:nth-child(3) {
      bottom: 20%;
      left: 25%;
      animation-delay: 4s;
    }
    
    .floating-3d:nth-child(4) {
      top: 40%;
      right: 30%;
      animation-delay: 6s;
    }
    
    /* Premium Background Effects */
    .accent-shapes {
      position: absolute;
      width: 100%;
      height: 100%;
      top: 0;
      left: 0;
      overflow: hidden;
      z-index: 0;
      opacity: 0.6;
    }

    .accent-circle {
      position: absolute;
      border-radius: 50%;
      background: linear-gradient(135deg, rgba(255, 107, 53, 0.15), rgba(255, 142, 53, 0.1));
      filter: blur(80px);
      animation: float 25s ease-in-out infinite;
      will-change: transform;
    }

    .accent-circle:nth-child(1) {
      width: 500px;
      height: 500px;
      top: 10%;
      left: 5%;
      animation-delay: 0s;
    }

    .accent-circle:nth-child(2) {
      width: 600px;
      height: 600px;
      top: 60%;
      right: 10%;
      animation-delay: 5s;
      animation-duration: 30s;
    }

    .accent-circle:nth-child(3) {
      width: 400px;
      height: 400px;
      bottom: 15%;
      left: 20%;
      animation-delay: 10s;
      animation-duration: 35s;
    }

    .accent-circle:nth-child(4) {
      width: 300px;
      height: 300px;
      top: 30%;
      right: 25%;
      animation-delay: 7s;
      animation-duration: 40s;
    }

    .accent-grid {
      position: absolute;
      width: 100%;
      height: 100%;
      top: 0;
      left: 0;
      background-image: 
        radial-gradient(circle at 1px 1px, var(--border-primary) 1px, transparent 0);
      background-size: 40px 40px;
      opacity: 0.15;
      z-index: 0;
    }
    
    /* Premium Animations */
    @keyframes float {
      0%, 100% {
        transform: translate(0px, 0px) rotate(0deg);
      }
      25% {
        transform: translate(40px, -60px) rotate(5deg);
      }
      50% {
        transform: translate(-30px, 30px) rotate(-5deg);
      }
      75% {
        transform: translate(20px, -20px) rotate(3deg);
      }
    }

    @keyframes fadeInUp {
      from {
        transform: translateY(20px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
      }
      to {
        opacity: 1;
      }
    }
    
    /* 3D Element Animation */
    @keyframes float3d {
      0%, 100% {
        transform: translateY(0) rotateY(0);
      }
      25% {
        transform: translateY(-20px) rotateY(90deg);
      }
      50% {
        transform: translateY(0) rotateY(180deg);
      }
      75% {
        transform: translateY(20px) rotateY(270deg);
      }
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
      .signup-card {
        padding: 2rem;
        margin: 1rem;
      }
      
      .signup-header h1 {
        font-size: 1.5rem;
      }
      
      .floating-3d {
        transform: scale(0.7);
      }
    }

    @media (max-width: 480px) {
      .signup-card {
        padding: 1.5rem;
      }
      
      .signup-header h1 {
        font-size: 1.3rem;
      }
      
      .floating-3d {
        transform: scale(0.5);
      }
    }
  </style>
</head>
<body>
<div class="signup-container">
  <button class="theme-toggle" aria-label="Toggle theme">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
    </svg>
  </button>
  
  <!-- 3D Elements -->
  <div class="floating-3d" style="background: linear-gradient(135deg, rgba(255, 107, 53, 0.7), rgba(255, 142, 53, 0.5)); border-radius: 50%;"></div>
  <div class="floating-3d" style="background: linear-gradient(135deg, rgba(255, 142, 53, 0.7), rgba(255, 107, 53, 0.5)); border-radius: 20%; transform: rotate(45deg);"></div>
  <div class="floating-3d" style="background: linear-gradient(135deg, rgba(255, 107, 53, 0.6), rgba(255, 142, 53, 0.4)); border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;"></div>
  <div class="floating-3d" style="background: linear-gradient(135deg, rgba(255, 142, 53, 0.6), rgba(255, 107, 53, 0.4)); border-radius: 10px; transform: rotate(15deg);"></div>
  
  <div class="accent-shapes">
    <div class="accent-circle"></div>
    <div class="accent-circle"></div>
    <div class="accent-circle"></div>
    <div class="accent-circle"></div>
  </div>
  <div class="accent-grid"></div>
  
  <div class="signup-card">
    <div class="logo">Sli<span>ppy</span></div>
    
    <div class="signup-header">
      <h1>Buat Akun Slippy</h1>
      <p>Yuk daftar dan mulai kelola tim kamu 🎯</p>
    </div>
    
    <form method="POST" enctype="multipart/form-data">
      <div class="form-group">
        <label class="form-label" for="name">Nama Lengkap</label>
        <input type="text" id="name" name="name" class="form-input" placeholder="Nama Lengkap" required>
      </div>
      
      <div class="form-group">
        <label class="form-label" for="username">Username</label>
        <input type="text" id="username" name="username" class="form-input" placeholder="Username" required>
      </div>
      
      <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <input type="email" id="email" name="email" class="form-input" placeholder="Email" required>
      </div>
      
      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <input type="password" id="password" name="password" class="form-input" placeholder="Password" required>
      </div>
      
      <div class="form-group">
        <label class="form-label" for="division_id">Divisi</label>
        <select id="division_id" name="division_id" class="form-select" required>
          <option value="">Pilih Divisi</option>
          <?php while($row = mysqli_fetch_assoc($divisions)): ?>
            <option value="<?= $row['id']; ?>"><?= htmlspecialchars($row['division_name']); ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      
      <div class="form-group">
        <label class="form-label">Foto Profil (opsional)</label>
        <div class="preview-container">
          <img id="preview" src="#" alt="Preview Foto Profil">
        </div>
        <div class="file-upload">
          <div class="file-upload-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </div>
          <div class="file-upload-text">
            Klik untuk upload foto atau <span>drag & drop</span>
          </div>
          <input type="file" name="photo" accept="image/*" onchange="previewImage(event)">
        </div>
      </div>
      
      <button type="submit" class="btn btn-primary">Daftar Sekarang</button>
    </form>
    
    <div class="signup-links">
      Sudah punya akun? <a href="signin.php">Login di sini</a>
    </div>
    
    <a href="index.php" class="back-link">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
      </svg>
      Kembali ke Beranda
    </a>
  </div>
</div>

<script>
  // Theme Toggle
  const themeToggle = document.querySelector('.theme-toggle');
  const html = document.documentElement;

  themeToggle.addEventListener('click', () => {
    html.classList.toggle('dark');
    localStorage.setItem('theme', html.classList.contains('dark') ? 'dark' : 'light');
  });

  // Check for saved theme preference
  if (localStorage.getItem('theme') === 'dark' || 
      (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    html.classList.add('dark');
  } else {
    html.classList.remove('dark');
  }

  // Interactive blob effects
  const circles = document.querySelectorAll('.accent-circle');
  window.addEventListener('mousemove', (e) => {
    const x = e.clientX / window.innerWidth;
    const y = e.clientY / window.innerHeight;
    
    circles.forEach((circle, i) => {
      const speed = (i + 1) * 0.0005;
      circle.style.transform = `translate(${x * 20 * speed}px, ${y * 20 * speed}px)`;
    });
  });

  // Interactive 3D elements
  const floatingElements = document.querySelectorAll('.floating-3d');
  window.addEventListener('mousemove', (e) => {
    const x = e.clientX / window.innerWidth;
    const y = e.clientY / window.innerHeight;
    
    floatingElements.forEach((element, i) => {
      const speed = (i + 1) * 0.0003;
      element.style.transform += ` translate(${x * 10 * speed}px, ${y * 10 * speed}px)`;
    });
  });

  // Image Preview
  function previewImage(event) {
    const preview = document.getElementById('preview');
    const file = event.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        preview.src = e.target.result;
        preview.style.display = 'block';
      };
      reader.readAsDataURL(file);
    }
  }

  // Drag and drop functionality
  const fileUpload = document.querySelector('.file-upload');
  const fileInput = document.querySelector('.file-upload input');

  fileUpload.addEventListener('dragover', (e) => {
    e.preventDefault();
    fileUpload.style.borderColor = 'var(--accent-secondary)';
    fileUpload.style.backgroundColor = 'rgba(255, 107, 53, 0.05)';
  });

  fileUpload.addEventListener('dragleave', () => {
    fileUpload.style.borderColor = 'var(--border-secondary)';
    fileUpload.style.backgroundColor = 'transparent';
  });

  fileUpload.addEventListener('drop', (e) => {
    e.preventDefault();
    fileUpload.style.borderColor = 'var(--border-secondary)';
    fileUpload.style.backgroundColor = 'transparent';
    
    if (e.dataTransfer.files.length) {
      fileInput.files = e.dataTransfer.files;
      previewImage({ target: { files: e.dataTransfer.files } });
    }
  });
</script>
</body>
</html>