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

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Inter', sans-serif;
      background: var(--bg-primary);
      color: var(--text-primary);
      overflow-x: hidden;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      line-height: 1.5;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      transition: background 0.3s ease, color 0.3s ease;
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
      transition: padding 0.3s ease, box-shadow 0.3s ease;
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
      transition: color 0.2s ease, transform 0.2s ease;
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
      transition: width 0.3s ease;
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
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      font-size: 0.95rem;
      border: none;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      letter-spacing: -0.2px;
    }

    .btn-primary {
      background: var(--accent-primary);
      color: white;
      box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(255, 107, 53, 0.4);
    }

    .btn-outline {
      border: 1px solid var(--border-secondary);
      color: var(--text-primary);
      background: var(--glass);
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
      transition: border-color 0.2s ease;
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
    }

    .hero-content {
      position: relative;
      z-index: 2;
      max-width: 900px;
      margin: 0 auto;
      animation: fadeInUp 0.6s ease-out;
    }

    .hero h1 {
      font-size: 4rem;
      line-height: 1.1;
      font-weight: 700;
      letter-spacing: -2px;
      margin-bottom: 2rem;
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
      margin: 0 auto 3rem;
    }

    .hero-buttons {
      display: flex;
      gap: 1.5rem;
      justify-content: center;
    }

    .hero-features {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 2rem;
      max-width: 1200px;
      margin: 4rem auto 0;
    }

    .feature-card {
      background: var(--glass);
      border: 1px solid var(--border-primary);
      border-radius: var(--radius-md);
      padding: 2rem;
      backdrop-filter: blur(10px);
      transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
      opacity: 0;
      transform: translateY(20px);
    }

    .feature-card.visible {
      opacity: 1;
      transform: translateY(0);
      transition: opacity 0.5s ease, transform 0.5s ease;
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

    /* Lightweight Background Effects — no blur, just soft gradient blobs */
    .accent-shapes {
      position: absolute;
      width: 100%;
      height: 100%;
      top: 0;
      left: 0;
      overflow: hidden;
      z-index: 0;
      pointer-events: none;
    }

    .accent-circle {
      position: absolute;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(255, 107, 53, 0.12), rgba(255, 142, 53, 0.05) 70%, transparent);
    }

    .accent-circle:nth-child(1) {
      width: 400px;
      height: 400px;
      top: 5%;
      left: 0%;
      animation: gentleFloat 30s ease-in-out infinite;
    }

    .accent-circle:nth-child(2) {
      width: 350px;
      height: 350px;
      bottom: 10%;
      right: 5%;
      animation: gentleFloat 35s ease-in-out infinite reverse;
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
      pointer-events: none;
    }

    /* Decorative shapes — pure CSS, lightweight */
    .deco-shape {
      position: absolute;
      z-index: 1;
      opacity: 0.15;
      pointer-events: none;
    }
    .deco-shape-1 {
      width: 120px;
      height: 120px;
      top: 18%;
      left: 8%;
      border: 3px solid var(--accent-secondary);
      border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
      animation: gentleFloat 20s ease-in-out infinite;
    }
    .deco-shape-2 {
      width: 80px;
      height: 80px;
      top: 60%;
      right: 12%;
      border: 3px solid var(--accent-tertiary);
      border-radius: 50%;
      animation: gentleFloat 25s ease-in-out infinite reverse;
    }
    .deco-shape-3 {
      width: 60px;
      height: 60px;
      bottom: 25%;
      left: 20%;
      border: 3px solid var(--accent-secondary);
      border-radius: 10px;
      transform: rotate(45deg);
      animation: gentleFloat 22s ease-in-out infinite;
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
      transition: color 0.2s ease;
    }

    .footer-links a:hover {
      color: var(--accent-secondary);
    }
    /* Lightweight Animations */
    @keyframes gentleFloat {
      0%, 100% {
        transform: translate(0px, 0px);
      }
      50% {
        transform: translate(15px, -15px);
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
      .deco-shape { display: none; }
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
    }
  </style>
</head>
<body>
<header>
    <div class="logo">Sli<span>ppy</span></div>
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
  <!-- Lightweight decorative shapes -->
  <div class="deco-shape deco-shape-1"></div>
  <div class="deco-shape deco-shape-2"></div>
  <div class="deco-shape deco-shape-3"></div>

  <div class="accent-shapes">
    <div class="accent-circle"></div>
    <div class="accent-circle"></div>
  </div>
  <div class="accent-grid"></div>
  
  <div class="hero-content">
    <h1>Modern HR & Payroll Suite</h1>
    <p>Transform your workforce management with our premium HR & payroll platform. Designed for enterprises that demand excellence.</p>
    
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

<footer>
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
  // Header scroll effect
  window.addEventListener('scroll', () => {
    const header = document.querySelector('header');
    header.classList.toggle('scrolled', window.scrollY > 10);
  }, { passive: true });

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

  // Animate feature cards on scroll (using IntersectionObserver — much lighter than scroll listener)
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, i) => {
      if (entry.isIntersecting) {
        setTimeout(() => entry.target.classList.add('visible'), i * 100);
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.2 });

  document.querySelectorAll('.feature-card').forEach(card => observer.observe(card));

  // Smooth scrolling for anchor links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();
      document.querySelector(this.getAttribute('href'))?.scrollIntoView({
        behavior: 'smooth'
      });
    });
  });
</script>
</body>
</html>