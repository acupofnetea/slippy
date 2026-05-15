<?php session_start(); ?>
<!DOCTYPE html>
<html lang="id" class="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Slippy — Premium HR & Payroll Suite</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.min.js"></script>
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
    /* Premium Header */
    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.5rem 6rem;
      background: var(--glass);
      position: fixed;
      width: 100%;
      top: 0;
      z-index: 100;
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-bottom: 1px solid var(--border-primary);
      transition: var(--transition);
      will-change: transform;
      box-shadow: var(--shadow-sm);
    }

    header.scrolled {
      padding: 1rem 6rem;
      box-shadow: var(--shadow-md);
    }

    .logo {
      font-size: 1.5rem;
      font-weight: 700;
      letter-spacing: -0.5px;
      display: flex;
      align-items: center;
      gap: 8px;
      color: var(--text-primary);
      text-decoration: none;
      transition: var(--transition);
    }

    .logo span {
      background: var(--accent-primary);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      font-weight: 700;
      font-size: 1.5rem;
    }

    nav {
      display: flex;
      align-items: center;
      gap: 1.5rem;
    }

    nav a {
      text-decoration: none;
      color: var(--text-primary);
      font-weight: 500;
      transition: var(--transition);
      position: relative;
      font-size: 0.95rem;
      letter-spacing: -0.2px;
      opacity: 0.9;
    }

    nav a:hover {
      opacity: 1;
      color: var(--accent-secondary);
    }

    nav a::after {
      content: '';
      position: absolute;
      bottom: -6px;
      left: 0;
      width: 0;
      height: 2px;
      background: var(--accent-secondary);
      transition: var(--transition);
    }

    nav a:hover::after {
      width: 100%;
    }
    /* Premium Buttons */
    .btn {
      text-decoration: none;
      padding: 0.75rem 1.75rem;
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

    .btn-outline {
      border: 1px solid var(--border-secondary);
      color: var(--text-primary);
      background: var(--glass);
      backdrop-filter: blur(5px);
    }

    .btn-outline:hover {
      border-color: var(--accent-secondary);
      color: var(--accent-secondary);
      transform: translateY(-2px);
      box-shadow: var(--shadow-sm);
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
      margin-left: 1rem;
    }

    .theme-toggle:hover {
      border-color: var(--accent-secondary);
    }

    .theme-toggle svg {
      width: 18px;
      height: 18px;
      color: var(--text-primary);
    }
    /* Premium Hero Section */
    .hero {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      text-align: center;
      padding: 18rem 2rem 10rem;
      position: relative;
      overflow: hidden;
      animation: fadeIn 1s ease-out;
    }

    .hero-content {
      position: relative;
      z-index: 2;
      max-width: 900px;
      margin: 0 auto;
    }

    .hero h1 {
      font-size: 4rem;
      line-height: 1.1;
      font-weight: 700;
      letter-spacing: -2px;
      margin-bottom: 2rem;
      color: var(--text-primary);
      animation: slideIn 0.8s ease-out;
      background: linear-gradient(to right, var(--text-primary), var(--accent-secondary));
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }

    .hero p {
      font-size: 1.25rem;
      color: var(--text-secondary);
      margin-bottom: 3rem;
      max-width: 600px;
      line-height: 1.7;
      font-weight: 400;
      letter-spacing: -0.2px;
      opacity: 0.9;
      animation: fadeIn 1.2s ease-out;
      margin: 0 auto 3rem;
    }

    .hero-buttons {
      display: flex;
      gap: 1.5rem;
      justify-content: center;
      animation: fadeIn 1.4s ease-out;
    }

    .hero-features {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 2rem;
      max-width: 1200px;
      margin: 4rem auto 0;
      animation: fadeInUp 1.6s ease-out;
    }

    .feature-card {
      background: var(--glass);
      border: 1px solid var(--border-primary);
      border-radius: var(--radius-md);
      padding: 2rem;
      backdrop-filter: blur(10px);
      transition: var(--transition);
    }

    .feature-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--shadow-md);
      border-color: var(--accent-secondary);
    }

    .feature-icon {
      width: 48px;
      height: 48px;
      background: var(--accent-primary);
      border-radius: var(--radius-sm);
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 1.5rem;
    }

    .feature-icon svg {
      width: 24px;
      height: 24px;
      color: white;
    }
    
    /* 3D Animation Container */
    #three-container {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 1;
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
    /* Premium Footer */
    footer {
      text-align: center;
      padding: 4rem 2rem;
      border-top: 1px solid var(--border-primary);
      color: var(--text-secondary);
      font-size: 0.9rem;
      letter-spacing: -0.2px;
      background: var(--glass);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      position: relative;
      z-index: 2;
    }

    .footer-content {
      max-width: 800px;
      margin: 0 auto;
    }

    footer p:first-child {
      margin-bottom: 0.5rem;
      color: var(--text-primary);
      font-weight: 500;
    }

    footer span {
      background: var(--accent-primary);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      font-weight: 600;
    }

    .footer-links {
      display: flex;
      justify-content: center;
      gap: 2rem;
      margin: 2rem 0;
    }

    .footer-links a {
      color: var(--text-secondary);
      text-decoration: none;
      transition: var(--transition);
    }

    .footer-links a:hover {
      color: var(--accent-secondary);
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

    @keyframes slideIn {
      from {
        transform: translateY(30px);
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
    
    /* 3D Cube Animation */
    .cube-container {
      position: absolute;
      width: 200px;
      height: 200px;
      perspective: 800px;
      z-index: 1;
    }
    
    .cube {
      position: relative;
      width: 100%;
      height: 100%;
      transform-style: preserve-3d;
      animation: rotateCube 20s infinite linear;
    }
    
    .cube-face {
      position: absolute;
      width: 200px;
      height: 200px;
      background: linear-gradient(135deg, rgba(255, 107, 53, 0.8), rgba(255, 142, 53, 0.6));
      border: 1px solid rgba(255, 255, 255, 0.2);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      font-weight: bold;
      color: white;
      opacity: 0.9;
      backdrop-filter: blur(5px);
    }
    
    .cube-face-front { transform: rotateY(0deg) translateZ(100px); }
    .cube-face-back { transform: rotateY(180deg) translateZ(100px); }
    .cube-face-right { transform: rotateY(90deg) translateZ(100px); }
    .cube-face-left { transform: rotateY(-90deg) translateZ(100px); }
    .cube-face-top { transform: rotateX(90deg) translateZ(100px); }
    .cube-face-bottom { transform: rotateX(-90deg) translateZ(100px); }
    
    @keyframes rotateCube {
      0% { transform: rotateX(0) rotateY(0) rotateZ(0); }
      100% { transform: rotateX(360deg) rotateY(360deg) rotateZ(360deg); }
    }
    
    /* 3D Pyramid Animation */
    .pyramid-container {
      position: absolute;
      width: 150px;
      height: 150px;
      perspective: 1000px;
      z-index: 1;
    }
    
    .pyramid {
      position: relative;
      width: 100%;
      height: 100%;
      transform-style: preserve-3d;
      animation: rotatePyramid 15s infinite linear;
    }
    
    .pyramid-face {
      position: absolute;
      width: 0;
      height: 0;
      border-left: 75px solid transparent;
      border-right: 75px solid transparent;
      border-bottom: 130px solid rgba(255, 142, 53, 0.7);
      opacity: 0.8;
    }
    
    .pyramid-face:nth-child(1) { transform: rotateY(0deg) translateZ(43px); }
    .pyramid-face:nth-child(2) { transform: rotateY(90deg) translateZ(43px); }
    .pyramid-face:nth-child(3) { transform: rotateY(180deg) translateZ(43px); }
    .pyramid-face:nth-child(4) { transform: rotateY(270deg) translateZ(43px); }
    
    @keyframes rotatePyramid {
      0% { transform: rotateX(0) rotateY(0); }
      100% { transform: rotateX(360deg) rotateY(360deg); }
    }
    
    /* 3D Sphere Animation */
    .sphere-container {
      position: absolute;
      width: 120px;
      height: 120px;
      perspective: 800px;
      z-index: 1;
    }
    
    .sphere {
      position: relative;
      width: 100%;
      height: 100%;
      transform-style: preserve-3d;
      animation: rotateSphere 25s infinite linear;
    }
    
    .sphere-ring {
      position: absolute;
      width: 120px;
      height: 120px;
      border: 3px solid rgba(255, 107, 53, 0.7);
      border-radius: 50%;
      opacity: 0.7;
    }
    
    .sphere-ring:nth-child(1) { transform: rotateX(0deg); }
    .sphere-ring:nth-child(2) { transform: rotateX(30deg); }
    .sphere-ring:nth-child(3) { transform: rotateX(60deg); }
    .sphere-ring:nth-child(4) { transform: rotateX(90deg); }
    .sphere-ring:nth-child(5) { transform: rotateX(120deg); }
    .sphere-ring:nth-child(6) { transform: rotateX(150deg); }
    
    @keyframes rotateSphere {
      0% { transform: rotateX(0) rotateY(0); }
      100% { transform: rotateX(360deg) rotateY(360deg); }
    }
    
    /* Floating 3D Elements */
    .floating-3d {
      position: absolute;
      width: 100px;
      height: 100px;
      z-index: 1;
      animation: float3d 15s infinite ease-in-out;
    }
    
    .floating-3d:nth-child(1) {
      top: 20%;
      left: 10%;
      animation-delay: 0s;
    }
    
    .floating-3d:nth-child(2) {
      top: 60%;
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

    /* Responsive Design */
    @media (max-width: 1024px) {
      header {
        padding: 1.5rem 3rem;
      }
      .hero {
        padding: 15rem 2rem 8rem;
      }
      .hero h1 {
        font-size: 3.25rem;
      }
      .cube-container, .pyramid-container, .sphere-container, .floating-3d {
        transform: scale(0.8);
      }
    }

    @media (max-width: 768px) {
      header {
        padding: 1.25rem 2rem;
      }
      .hero {
        padding: 12rem 2rem 6rem;
      }
      .hero h1 {
        font-size: 2.75rem;
        letter-spacing: -1.5px;
      }
      .hero p {
        font-size: 1.1rem;
      }
      .hero-buttons {
        flex-direction: column;
        width: 100%;
        gap: 1rem;
      }
      .btn {
        width: 100%;
      }
      .hero-features {
        grid-template-columns: 1fr;
      }
      .cube-container, .pyramid-container, .sphere-container, .floating-3d {
        transform: scale(0.6);
      }
    }

    @media (max-width: 480px) {
      header {
        padding: 1rem 1.5rem;
      }
      .hero h1 {
        font-size: 2.25rem;
      }
      .hero p {
        font-size: 1rem;
      }
      .cube-container, .pyramid-container, .sphere-container, .floating-3d {
        transform: scale(0.4);
      }
    }
  </style>
</head>
<body>
<header>
    <div class="logo" style="animation: fadeIn 1s ease-out">Sli<span>ppy</span></div>
    <nav>
      <?php if(isset($_SESSION['user_name'])): ?>
        <a href="logout.php" class="btn btn-primary">Logout</a>
      <?php else: ?>
        <a href="signup.php" class="btn btn-primary">Sign Up</a>
        <a href="signin.php" class="btn btn-outline">Sign In</a>
      <?php endif; ?>
      <button class="theme-toggle" aria-label="Toggle theme">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
      </button>
    </nav>
</header>
<section class="hero">
  <div id="three-container"></div>
  
  <!-- 3D Elements -->
  <div class="cube-container" style="top: 15%; left: 5%;">
    <div class="cube">
      <div class="cube-face cube-face-front">S</div>
      <div class="cube-face cube-face-back">L</div>
      <div class="cube-face cube-face-right">I</div>
      <div class="cube-face cube-face-left">P</div>
      <div class="cube-face cube-face-top">P</div>
      <div class="cube-face cube-face-bottom">Y</div>
    </div>
  </div>
  
  <div class="pyramid-container" style="top: 65%; right: 10%;">
    <div class="pyramid">
      <div class="pyramid-face"></div>
      <div class="pyramid-face"></div>
      <div class="pyramid-face"></div>
      <div class="pyramid-face"></div>
    </div>
  </div>
  
  <div class="sphere-container" style="bottom: 20%; left: 20%;">
    <div class="sphere">
      <div class="sphere-ring"></div>
      <div class="sphere-ring"></div>
      <div class="sphere-ring"></div>
      <div class="sphere-ring"></div>
      <div class="sphere-ring"></div>
      <div class="sphere-ring"></div>
    </div>
  </div>
  
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
  
  <div class="hero-content">
    <h1>Modern HR & Payroll Suite</h1>
    <p style="animation: fadeIn 1.2s ease-out">Transform your workforce management with our premium HR & payroll platform. Designed for enterprises that demand excellence.</p>
    
    <?php if(!isset($_SESSION['user_name'])): ?>
      <div class="hero-buttons">
        <a href="signup.php" class="btn btn-primary">Start your journey!</a>
        <a href="signin.php" class="btn btn-outline">Back to Work!</a>
      </div>
    <?php else: ?>
      <?php
      // Tentukan URL dashboard berdasarkan role
      $dashboard_url = 'dashboard.php'; // default
      if (isset($_SESSION['role_name'])) {
          switch (strtolower($_SESSION['role_name'])) {
              case 'owner':
                  $dashboard_url = 'Owner/index.php';
                  break;
              case 'hr':
                  $dashboard_url = 'HR/index.php';
                  break;
              case 'leader':
                  $dashboard_url = 'Leader/index.php';
                  break;
              case 'employee':
                  $dashboard_url = 'Employee/index.php';
                  break;
              default:
                  $dashboard_url = 'dashboard.php';
                  break;
          }
      }
      ?>
      <a href="<?php echo $dashboard_url; ?>" class="btn btn-primary">Go to Dashboard</a>
    <?php endif; ?>

    <div class="hero-features">
      <div class="feature-card">
        <div class="feature-icon">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <h3>Automated Payroll</h3>
        <p>Effortless payroll processing with tax compliance and direct deposit.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
        </div>
        <h3>People Management</h3>
        <p>Comprehensive employee records, onboarding, and offboarding.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
        </div>
        <h3>Time Tracking</h3>
        <p>Accurate time and attendance tracking</p>
      </div>
    </div>
  </div>
</section>

<footer style="animation: fadeIn 1.5s ease-out">
  <div class="footer-content">
    <p>© <?= date('Y'); ?> <span>Slippy</span></p>
    <p>Enterprise-grade HR & Payroll Platform</p>
    <div class="footer-links">
      <a href="#">Privacy</a>
      <a href="#">Terms</a>
      <a href="#">Security</a>
      <a href="#">Contact</a>
    </div>
  </div>
</footer>
<script>
  // Premium Interactive Effects
  window.addEventListener('scroll', () => {
    const header = document.querySelector('header');
    if (window.scrollY > 10) {
      header.classList.add('scrolled');
    } else {
      header.classList.remove('scrolled');
    }
  });

  // Theme Toggle
  const themeToggle = document.querySelector('.theme-toggle');
  const html = document.documentElement;

  themeToggle.addEventListener('click', () => {
    html.classList.toggle('dark');
    localStorage.setItem('theme', html.classList.contains('dark') ? 'dark' : 'light');
    update3DColors();
  });

  // Check for saved theme preference
  if (localStorage.getItem('theme') === 'dark' || 
      (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    html.classList.add('dark');
  } else {
    html.classList.remove('dark');
  }

  // Animate elements when they come into view
  const animateOnScroll = () => {
    const elements = document.querySelectorAll('.feature-card');
    elements.forEach(el => {
      const rect = el.getBoundingClientRect();
      if (rect.top <= window.innerHeight * 0.8) {
        el.style.animation = 'fadeInUp 0.6s ease-out forwards';
      }
    });
  };

  window.addEventListener('scroll', animateOnScroll);
  animateOnScroll();

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

  // Smooth scrolling for all links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();
      document.querySelector(this.getAttribute('href'))?.scrollIntoView({
        behavior: 'smooth'
      });
    });
  });
  
  // Three.js 3D Animation
  let scene, camera, renderer, particles;
  
  function initThreeJS() {
    // Create scene
    scene = new THREE.Scene();
    
    // Create camera
    camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
    camera.position.z = 5;
    
    // Create renderer
    renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setClearColor(0x000000, 0);
    document.getElementById('three-container').appendChild(renderer.domElement);
    
    // Create particle system
    const particleCount = 1000;
    const particles = new THREE.BufferGeometry();
    const positions = new Float32Array(particleCount * 3);
    const colors = new Float32Array(particleCount * 3);
    
    for (let i = 0; i < particleCount * 3; i += 3) {
      // Random positions
      positions[i] = (Math.random() - 0.5) * 20;
      positions[i + 1] = (Math.random() - 0.5) * 20;
      positions[i + 2] = (Math.random() - 0.5) * 10;
      
      // Orange gradient colors
      colors[i] = 1.0;     // R
      colors[i + 1] = 0.42; // G
      colors[i + 2] = 0.21; // B
    }
    
    particles.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    particles.setAttribute('color', new THREE.BufferAttribute(colors, 3));
    
    // Create particle material
    const particleMaterial = new THREE.PointsMaterial({
      size: 0.05,
      vertexColors: true,
      transparent: true,
      opacity: 0.8
    });
    
    // Create particle system
    const particleSystem = new THREE.Points(particles, particleMaterial);
    scene.add(particleSystem);
    
    // Add ambient light
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
    scene.add(ambientLight);
    
    // Add directional light
    const directionalLight = new THREE.DirectionalLight(0xff6b35, 0.8);
    directionalLight.position.set(1, 1, 1);
    scene.add(directionalLight);
    
    // Animation loop
    function animate() {
      requestAnimationFrame(animate);
      
      // Rotate particle system
      particleSystem.rotation.x += 0.001;
      particleSystem.rotation.y += 0.002;
      
      // Move particles
      const positions = particleSystem.geometry.attributes.position.array;
      for (let i = 0; i < positions.length; i += 3) {
        positions[i + 1] += 0.001;
        if (positions[i + 1] > 10) {
          positions[i + 1] = -10;
        }
      }
      particleSystem.geometry.attributes.position.needsUpdate = true;
      
      renderer.render(scene, camera);
    }
    
    animate();
    
    // Handle window resize
    window.addEventListener('resize', () => {
      camera.aspect = window.innerWidth / window.innerHeight;
      camera.updateProjectionMatrix();
      renderer.setSize(window.innerWidth, window.innerHeight);
    });
  }
  
  // Update 3D colors based on theme
  function update3DColors() {
    if (scene) {
      const isDark = html.classList.contains('dark');
      
      // Update particle colors
      const particleSystem = scene.children.find(child => child instanceof THREE.Points);
      if (particleSystem) {
        const colors = particleSystem.geometry.attributes.color.array;
        
        for (let i = 0; i < colors.length; i += 3) {
          if (isDark) {
            // Dark theme - slightly different orange
            colors[i] = 0.9;     // R
            colors[i + 1] = 0.35; // G
            colors[i + 2] = 0.15; // B
          } else {
            // Light theme - standard orange
            colors[i] = 1.0;     // R
            colors[i + 1] = 0.42; // G
            colors[i + 2] = 0.21; // B
          }
        }
        particleSystem.geometry.attributes.color.needsUpdate = true;
      }
      
      // Update directional light
      const directionalLight = scene.children.find(child => child instanceof THREE.DirectionalLight);
      if (directionalLight) {
        directionalLight.color.set(isDark ? 0xff8e35 : 0xff6b35);
      }
    }
  }
  
  // Initialize Three.js when page loads
  window.addEventListener('load', initThreeJS);
</script>
</body>
</html>