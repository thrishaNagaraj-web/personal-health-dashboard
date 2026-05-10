<?php
require_once 'includes/config.php';
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php'); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthDash - Your Health, Tracked Simply</title>
    <!-- Use Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        html { scroll-behavior: smooth; }
        body { margin: 0; padding: 0; overflow-x: hidden; }
        
        .fade-in { opacity: 0; transform: translateY(24px); transition: opacity 0.6s ease, transform 0.6s ease; }
        .fade-in.visible { opacity: 1; transform: translateY(0); }

        .landing-nav {
            position: sticky; top: 0; z-index: 100;
            display: flex; justify-content: space-between; align-items: center;
            padding: 1rem 5%; background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px); border-bottom: 1px solid var(--border);
        }
        [data-theme="dark"] .landing-nav { background: rgba(15, 23, 42, 0.8); border-bottom: 1px solid var(--border); }

        .landing-nav .logo { display: flex; align-items: center; gap: 0.5rem; font-weight: 700; font-size: 1.5rem; color: var(--color-primary); }
        .landing-nav .logo svg { width: 24px; height: 24px; fill: currentColor; }

        .landing-nav .actions { display: flex; align-items: center; gap: 1rem; }

        .hero-section {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            padding: 6rem 5% 4rem; text-align: center; min-height: 80vh; gap: 2rem;
        }
        @media (min-width: 768px) {
            .hero-section { flex-direction: row; text-align: left; }
            .hero-content { flex: 1; padding-right: 2rem; }
            .hero-visual { flex: 1; }
        }
        .hero-title { font-size: 3.5rem; line-height: 1.1; margin-bottom: 1rem; font-weight: 700; }
        .hero-subtitle { font-size: 1.2rem; color: var(--text-muted); margin-bottom: 2rem; line-height: 1.6; max-width: 600px; }
        .hero-ctas { display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
        @media (min-width: 768px) { .hero-ctas { justify-content: flex-start; } }
        .hero-checks { display: flex; gap: 1.5rem; color: var(--text-muted); font-size: 0.9rem; flex-wrap: wrap; justify-content: center; }
        @media (min-width: 768px) { .hero-checks { justify-content: flex-start; } }

        .hero-preview {
            background: var(--surface); padding: 1.5rem; border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            transform: perspective(1000px) rotateX(5deg) rotateY(-5deg);
            transition: transform 0.3s ease;
            display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;
        }
        [data-theme="dark"] .hero-preview { box-shadow: 0 20px 40px rgba(0,0,0,0.4); }
        .hero-preview:hover { transform: perspective(1000px) rotateX(0deg) rotateY(0deg); }
        
        .mock-card {
            background: var(--surface); padding: 1rem; border-radius: 8px; border: 1px solid var(--border);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05); text-align: center;
        }
        .mock-card h4 { margin: 0 0 0.5rem; font-size: 0.8rem; color: var(--text-muted); }
        .mock-card .val { font-size: 1.5rem; font-weight: bold; margin-bottom: 0.5rem; }
        .mock-bar { height: 6px; background: var(--border); border-radius: 999px; overflow: hidden; }
        .mock-fill { height: 100%; border-radius: 999px; }

        .features-section { padding: 5rem 5%; background: var(--surface); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); }
        .features-grid {
            display: grid; grid-template-columns: 1fr; gap: 2rem; margin-top: 3rem; max-width: 1000px; margin-inline: auto;
        }
        @media (min-width: 768px) { .features-grid { grid-template-columns: repeat(3, 1fr); } }
        .feature-card {
            background: var(--bg); padding: 2rem; border-radius: 12px; text-align: center; border: 1px solid var(--border);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .feature-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .feature-icon { font-size: 3rem; display: block; margin-bottom: 1rem; }
        .feature-card h3 { margin: 0 0 0.5rem; font-size: 1.25rem; }
        .feature-card p { margin: 0; color: var(--text-muted); font-size: 0.95rem; line-height: 1.5; }

        .how-works { padding: 5rem 5%; max-width: 900px; margin: 0 auto; text-align: center; }
        .stepper {
            display: flex; flex-direction: column; gap: 2rem; margin-top: 3rem; position: relative;
        }
        @media (min-width: 768px) {
            .stepper { flex-direction: row; justify-content: space-between; }
            .stepper::before {
                content: ''; position: absolute; top: 25px; left: 10%; right: 10%; height: 2px;
                background: var(--border); z-index: 0;
            }
        }
        .step { position: relative; z-index: 1; flex: 1; display: flex; flex-direction: column; align-items: center; }
        .step-num {
            width: 50px; height: 50px; background: var(--color-primary); color: white; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem;
            margin-bottom: 1rem; border: 4px solid var(--bg);
        }
        .step h4 { margin: 0 0 0.5rem; }
        .step p { margin: 0; color: var(--text-muted); font-size: 0.9rem; }

        .stats-banner {
            background: var(--color-primary); color: white; padding: 4rem 5%;
            display: flex; flex-direction: column; gap: 2rem; text-align: center;
        }
        @media (min-width: 768px) { .stats-banner { flex-direction: row; justify-content: center; gap: 5rem; } }
        .stat-item { font-size: 1.5rem; font-weight: 600; display: flex; flex-direction: column; gap: 0.5rem; }
        .stat-item span { font-size: 3rem; }

        .cta-section { padding: 6rem 5%; text-align: center; background: var(--surface); border-top: 1px solid var(--border); }
        .cta-section h2 { font-size: 2.5rem; margin-bottom: 2rem; margin-top: 0; }
        
        .footer { padding: 2rem 5%; text-align: center; color: var(--text-muted); border-top: 1px solid var(--border); background: var(--bg); }
        .footer-links { margin-top: 1rem; display: flex; justify-content: center; gap: 1rem; }
        .footer-links a { color: var(--text-main); text-decoration: none; font-weight: 500; }
    </style>
</head>
<body>

<nav class="landing-nav">
    <div class="logo">
        <svg viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
        HealthDash
    </div>
    <div class="actions">
        <button id="themeToggle" style="background: transparent; border: none; cursor: pointer; font-size: 1.25rem;">🌙</button>
        <a href="login.php" class="btn btn-outline" style="padding: 0.4rem 1rem;">Login</a>
        <a href="registration.php" class="btn btn-primary" style="padding: 0.4rem 1rem;">Sign Up</a>
    </div>
</nav>

<section class="hero-section fade-in">
    <div class="hero-content">
        <h1 class="hero-title">Your Health,<br>Tracked Simply.</h1>
        <p class="hero-subtitle">Log water, calories, weight, sleep and mood. Get AI-powered weekly insights. All in one place.</p>
        <div class="hero-ctas">
            <a href="registration.php" class="btn btn-primary" style="font-size: 1.1rem; padding: 0.8rem 1.5rem;">Get Started Free</a>
            <a href="login.php" class="btn btn-outline" style="font-size: 1.1rem; padding: 0.8rem 1.5rem;">Login</a>
        </div>
        <div class="hero-checks">
            <span>✓ Free forever</span>
            <span>✓ No app needed</span>
            <span>✓ Private & secure</span>
        </div>
    </div>
    <div class="hero-visual">
        <div class="hero-preview">
            <div class="mock-card">
                <h4>💧 Water</h4>
                <div class="val" style="color: #3498db;">1840ml</div>
                <div class="mock-bar"><div class="mock-fill" style="width: 75%; background: #3498db;"></div></div>
            </div>
            <div class="mock-card">
                <h4>🔥 Calories</h4>
                <div class="val" style="color: #e67e22;">1650<span style="font-size:0.8rem;">kcal</span></div>
                <div class="mock-bar"><div class="mock-fill" style="width: 82%; background: #e67e22;"></div></div>
            </div>
            <div class="mock-card">
                <h4>⚖️ Weight</h4>
                <div class="val" style="color: #8b5cf6;">68.2kg</div>
                <div class="mock-bar"><div class="mock-fill" style="width: 40%; background: #8b5cf6;"></div></div>
            </div>
            <div class="mock-card">
                <h4>🏃 Exercise</h4>
                <div class="val" style="color: #2ecc71;">35m</div>
                <div class="mock-bar"><div class="mock-fill" style="width: 100%; background: #2ecc71;"></div></div>
            </div>
        </div>
    </div>
</section>

<section class="features-section fade-in">
    <h2 style="text-align: center; font-size: 2.2rem; margin-top: 0;">Everything you need to stay healthy</h2>
    <div class="features-grid">
        <div class="feature-card">
            <span class="feature-icon">💧</span>
            <h3>Water Tracking</h3>
            <p>Log daily intake, hit your hydration goals seamlessly.</p>
        </div>
        <div class="feature-card">
            <span class="feature-icon">🔥</span>
            <h3>Calorie Logging</h3>
            <p>Track meals and stay within your diet physical targets.</p>
        </div>
        <div class="feature-card">
            <span class="feature-icon">⚖️</span>
            <h3>Weight Trends</h3>
            <p>Visualize progress with beautiful charts tracing trends.</p>
        </div>
        <div class="feature-card">
            <span class="feature-icon">😴</span>
            <h3>Sleep Analysis</h3>
            <p>Understand your sleep patterns effortlessly over time.</p>
        </div>
        <div class="feature-card">
            <span class="feature-icon">😊</span>
            <h3>Mood Journal</h3>
            <p>Track how you feel day by day effectively tracking sentiment.</p>
        </div>
        <div class="feature-card">
            <span class="feature-icon">🤖</span>
            <h3>AI Insights</h3>
            <p>Get weekly summaries powered gracefully by Groq AI.</p>
        </div>
    </div>
</section>

<section class="how-works fade-in">
    <h2 style="font-size: 2.2rem;">Start in 3 simple steps</h2>
    <div class="stepper">
        <div class="step">
            <div class="step-num">1</div>
            <h4>Create your free account</h4>
            <p>Getting started takes absolutely mere seconds 📝</p>
        </div>
        <div class="step">
            <div class="step-num">2</div>
            <h4>Log your daily health data</h4>
            <p>Track exactly what you need seamlessly 📊</p>
        </div>
        <div class="step">
            <div class="step-num">3</div>
            <h4>Get insights and improve</h4>
            <p>Unlock custom generative logic and win 🚀</p>
        </div>
    </div>
</section>

<section class="stats-banner fade-in">
    <div class="stat-item"><span>6</span> Metrics Tracked</div>
    <div class="stat-item"><span>✨</span> AI-Powered Insights</div>
    <div class="stat-item"><span>🔒</span> 100% Private</div>
</section>

<section class="cta-section fade-in">
    <h2>Ready to take control of your health?</h2>
    <a href="registration.php" class="btn btn-primary" style="font-size: 1.25rem; padding: 1rem 2rem;">Start Tracking Free &rarr;</a>
</section>

<footer class="footer">
    <p>© 2025 HealthDash. Built with ❤️ for your wellbeing.</p>
    <div class="footer-links">
        <a href="login.php">Login</a>
        <a href="registration.php">Sign Up</a>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const themeBtn = document.getElementById('themeToggle');
    const updateIcon = (theme) => themeBtn.textContent = theme === 'dark' ? '☀️' : '🌙';
    
    // Check initial preference natively
    if (localStorage.getItem('theme') === 'dark') {
        updateIcon('dark');
    }
    
    themeBtn.addEventListener('click', () => {
        let target = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', target);
        localStorage.setItem('theme', target);
        updateIcon(target);
    });

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('visible');
            }
        });
    }, { threshold: 0.15 });

    document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));
});
</script>
</body>
</html>
