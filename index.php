<?php
session_start();
require_once 'header.php';

// ✅ Ensure helper functions exist before calling
if (!function_exists('getUpcomingEvents')) {
    function getUpcomingEvents($limit = 6) { return []; }
}
if (!function_exists('getRecentAnnouncements')) {
    function getRecentAnnouncements($limit = 6) { return []; }
}

// Fetch featured content
$featuredEvents = getUpcomingEvents(6);
$featuredAnnouncements = getRecentAnnouncements(6);
$totalClubs = 6;      // Example: ACM, ACES, CESA, MESA, ITSA, IEEE
$totalMembers = 1000; // Placeholder
$totalEvents = 500;   // Placeholder
?>

<!-- Hero Section -->
<section class="hero" id="home">
  <div class="hero-container">
    <div class="hero-content">
      <div class="hero-text" data-aos="fade-up">
        <h1 class="hero-title">
          Where Passion Meets <span class="highlight">Innovation</span>
        </h1>
        <p class="hero-description">
          Join thousands of students in managing clubs, events, and collaborations seamlessly.
          ClubSphere empowers you to connect, create, and achieve more together.
        </p>

        <div class="hero-buttons">
          <a href="dashboard/events.php" class="btn btn-primary">
            <i class="fas fa-rocket"></i> Explore Events
          </a>
          <a href="dashboard/announcements.php" class="btn btn-primary">
            <i class="fas fa-bullhorn"></i> Explore Announcements
          </a>
          <a href="clubs.php" class="btn btn-secondary">
            <i class="fas fa-users"></i> Browse Clubs
          </a>
        </div>
      </div>

      <!-- Neon Animated Text Boxes -->
      <div class="hero-visual" data-aos="fade-up" data-aos-delay="200">
        <div class="text-box-container">
          <div class="text-box"><span>CLUB</span></div>
          <div class="text-box"><span>EVENT</span></div>
          <div class="text-box"><span>CONNECT</span></div>
          <div class="text-box"><span>LEARN</span></div>
          <div class="text-box"><span>GROW</span></div>
          <div class="text-box"><span>INSPIRE</span></div>
        </div>
      </div>
    </div>
  </div>

  <div class="hero-bg">
    <div class="bg-particles" id="bgParticles"></div>
  </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
  <div class="container">
    <div class="stats-grid">
      <div class="stat-card" data-aos="fade-up" data-aos-delay="0">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-number" data-count="<?= $totalClubs; ?>">0</div>
        <div class="stat-label">Active Clubs</div>
      </div>

      <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
        <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
        <div class="stat-number" data-count="<?= $totalMembers; ?>">0</div>
        <div class="stat-label">Registered Members</div>
      </div>

      <div class="stat-card" data-aos="fade-up" data-aos-delay="200">
        <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
        <div class="stat-number" data-count="<?= $totalEvents; ?>">0</div>
        <div class="stat-label">Events Hosted</div>
      </div>

      <div class="stat-card" data-aos="fade-up" data-aos-delay="300">
        <div class="stat-icon"><i class="fas fa-trophy"></i></div>
        <div class="stat-number" data-count="100">0</div>
        <div class="stat-label">Success Rate</div>
      </div>
    </div>
  </div>
</section>

<!-- Features Section -->
<section class="features-section" id="features">
  <div class="container">
    <div class="section-header" data-aos="fade-up">
      <h2 class="section-title">Why Choose ClubSphere?</h2>
      <p class="section-description">
        Experience the future of club management with powerful features designed for modern student organizations.
      </p>
    </div>

    <div class="features-grid">
      <div class="feature-card" data-aos="fade-up">
        <div class="feature-icon"><i class="fas fa-calendar-check"></i></div>
        <h3 class="feature-title">Event Management</h3>
        <p class="feature-description">
          Create, manage, and promote events with ease. Track registrations, send reminders, and analyze attendance.
        </p>
      </div>

      <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
        <div class="feature-icon"><i class="fas fa-bullhorn"></i></div>
        <h3 class="feature-title">Smart Announcements</h3>
        <p class="feature-description">
          Reach your members with targeted announcements. Schedule posts and track engagement.
        </p>
      </div>

      <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
        <div class="feature-icon"><i class="fas fa-users-cog"></i></div>
        <h3 class="feature-title">Member Management</h3>
        <p class="feature-description">
          Keep track of memberships, roles, and participation. Build stronger communities with organized member data.
        </p>
      </div>

      <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
        <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
        <h3 class="feature-title">Analytics & Insights</h3>
        <p class="feature-description">
          Understand your club's performance with analytics. Track growth, engagement, and success metrics.
        </p>
      </div>

      <div class="feature-card" data-aos="fade-up" data-aos-delay="400">
        <div class="feature-icon"><i class="fas fa-mobile-alt"></i></div>
        <h3 class="feature-title">Mobile Responsive</h3>
        <p class="feature-description">
          Access ClubSphere anywhere. Fully optimized for all devices and screen sizes.
        </p>
      </div>

      <div class="feature-card" data-aos="fade-up" data-aos-delay="500">
        <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
        <h3 class="feature-title">Security & Privacy</h3>
        <p class="feature-description">
          Your data is protected with enterprise-grade encryption and privacy standards.
        </p>
      </div>
    </div>
  </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
  <div class="container">
    <div class="cta-content" data-aos="fade-up">
      <h2 class="cta-title">Ready to Transform Your Club Experience?</h2>
      <p class="cta-description">
        Join thousands of students already using ClubSphere to manage their clubs and events.
      </p>

      <div class="cta-buttons">
        <?php if (isLoggedIn()): ?>
          <a href="dashboard/user_dashboard.php" class="btn btn-primary">
            <i class="fas fa-tachometer-alt"></i> Go to Dashboard
          </a>
        <?php else: ?>
          <a href="login.php" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Get Started Free
          </a>
        <?php endif; ?>

        <a href="demo.php" class="btn btn-secondary">
          <i class="fas fa-play"></i> Watch Demo
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ✅ External Styles and Scripts -->
<link rel="stylesheet" href="assets/css/homepage.css">
<script src="assets/js/homepage.js"></script>

<?php require_once 'footer.php'; ?>


<!-- Custom Styles -->
<style>
    /* Hero Section */
    /* --- Container Setup --- */
.hero-visual {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 40px 20px;
  background: transparent; /* ✅ No background */
}

.text-box-container {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 24px;
  max-width: 800px;
  animation: fadeIn 1.5s ease forwards;
}

/* --- Box Style --- */
.text-box {
  position: relative;
  padding: 18px 40px;
  background: rgba(0, 255, 230, 0.05);
  border-radius: 20px;
  overflow: hidden;
  cursor: default;
  text-align: center;
  transition: transform 0.4s ease, background 0.4s ease;
  box-shadow: 0 0 10px rgba(0, 255, 230, 0.2);
}

.text-box span {
  position: relative;
  z-index: 2;
  color: #00ffe7;
  font-weight: 700;
  font-size: 1.3rem;
  letter-spacing: 2px;
  text-transform: uppercase;
  text-shadow: 0 0 8px #00ffe7, 0 0 15px #00b3ff;
}

/* --- Animated Border --- */
.text-box::before {
  content: "";
  position: absolute;
  inset: 0;
  border-radius: 20px;
  padding: 2px;
  background: linear-gradient(90deg, #00fff2, #007bff, #00fff2, #00fff2);
  background-size: 300% 300%;
  animation: borderSweep 4s linear infinite;
  -webkit-mask: 
    linear-gradient(#fff 0 0) content-box, 
    linear-gradient(#fff 0 0);
  -webkit-mask-composite: xor;
  mask-composite: exclude;
  z-index: 1;
}

/* --- Hover Animation --- */
.text-box:hover {
  transform: scale(1.08);
  background: rgba(0, 255, 230, 0.1);
  box-shadow: 0 0 20px rgba(0, 255, 230, 0.4);
}

/* --- Entry Animation --- */
.text-box {
  opacity: 0;
  transform: translateY(20px);
  animation: slideIn 0.8s ease forwards;
}
.text-box:nth-child(1) { animation-delay: 0.2s; }
.text-box:nth-child(2) { animation-delay: 0.4s; }
.text-box:nth-child(3) { animation-delay: 0.6s; }
.text-box:nth-child(4) { animation-delay: 0.8s; }
.text-box:nth-child(5) { animation-delay: 1s; }
.text-box:nth-child(6) { animation-delay: 1.2s; }

/* --- Keyframes --- */
@keyframes borderSweep {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

@keyframes slideIn {
  from { opacity: 0; transform: translateY(30px) scale(0.95); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.9); }
  to { opacity: 1; transform: scale(1); }
}

    .hero {
        min-height: 100vh;
        display: flex;
        align-items: center;
        position: relative;
        overflow: hidden;
        padding: 120px 0 80px;
    }
    
    .hero-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        width: 100%;
    }
    
    .hero-content {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        align-items: center;
        min-height: 60vh;
    }
    
    .hero-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 3.5rem;
        font-weight: 700;
        line-height: 1.1;
        margin-bottom: 20px;
        background: linear-gradient(90deg, #ffcf70, #f3a683);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    
    .hero-title .highlight {
        background: linear-gradient(90deg, #f3a683, #ff6b9d);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    
    .hero-description {
        font-size: 1.2rem;
        color: #e0e0e0;
        margin-bottom: 30px;
        line-height: 1.6;
    }
    
    .hero-buttons {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    .hero-visual {
        display: flex;
        justify-content: center;
        align-items: center;
    }
    
    .hero-sphere {
        width: 350px;
        height: 350px;
        position: relative;
        border-radius: 50%;
        background: radial-gradient(circle at 30% 30%, rgba(255, 207, 112, 0.2), rgba(243, 166, 131, 0.1));
        border: 2px solid rgba(255, 207, 112, 0.3);
        animation: float 6s ease-in-out infinite;
    }
    
    .sphere-layer {
        position: absolute;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: rotate 20s linear infinite;
    }
    
    .layer-1 {
        animation-duration: 25s;
    }
    
    .layer-2 {
        animation-duration: 30s;
        animation-direction: reverse;
    }
    
    .layer-3 {
        animation-duration: 35s;
    }
    
    .sphere-text {
        position: absolute;
        font-weight: 600;
        color: #ffcf70;
        font-size: 1rem;
        text-shadow: 0 0 10px rgba(255, 207, 112, 0.5);
    }
    
    .sphere-text:nth-child(1) { transform: rotate(0deg) translateY(-120px); }
    .sphere-text:nth-child(2) { transform: rotate(120deg) translateY(-120px); }
    .sphere-text:nth-child(3) { transform: rotate(240deg) translateY(-120px); }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(180deg); }
    }
    
    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    /* Stats Section */
    .stats-section {
        padding: 80px 0;
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 30px;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    .stat-card {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: 30px;
        text-align: center;
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(255, 207, 112, 0.2);
    }
    
    .stat-icon {
        font-size: 3rem;
        color: #ffcf70;
        margin-bottom: 15px;
    }
    
    .stat-number {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 3rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 10px;
    }
    
    .stat-label {
        font-size: 1.1rem;
        color: #ccc;
        font-weight: 500;
    }
    
    /* Section Styles */
    .section-header {
        text-align: center;
        margin-bottom: 60px;
    }
    
    .section-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 2.5rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 15px;
    }
    
    .section-description {
        font-size: 1.1rem;
        color: #ccc;
        max-width: 600px;
        margin: 0 auto;
        line-height: 1.6;
    }
    
    /* Events Grid */
    .events-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 30px;
        margin-bottom: 40px;
    }
    
    .event-card {
        background: rgba(255, 255, 255, 0.08);
        backdrop-filter: blur(15px);
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s ease;
    }
    
    .event-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(255, 207, 112, 0.2);
    }
    
    .event-image {
        position: relative;
        height: 200px;
        overflow: hidden;
    }
    
    .event-poster {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .event-card:hover .event-poster {
        transform: scale(1.05);
    }
    
    .event-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: linear-gradient(90deg, #ffcf70, #f3a683);
        color: #000;
        padding: 5px 12px;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .event-content {
        padding: 25px;
    }
    
    .event-meta {
        display: flex;
        gap: 15px;
        margin-bottom: 15px;
        font-size: 0.9rem;
        color: #ccc;
    }
    
    .event-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: #fff;
        margin-bottom: 10px;
    }
    
    .event-description {
        color: #ccc;
        line-height: 1.5;
        margin-bottom: 20px;
    }
    
    .event-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        font-size: 0.9rem;
        color: #ccc;
    }
    
    .event-actions {
        display: flex;
        gap: 10px;
    }
    
    /* Clubs Grid */
    .clubs-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        margin-bottom: 40px;
    }
    
    .club-card {
        background: rgba(255, 255, 255, 0.08);
        backdrop-filter: blur(15px);
        border-radius: 20px;
        padding: 30px;
        text-align: center;
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s ease;
    }
    
    .club-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(255, 207, 112, 0.2);
    }
    
    .club-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 2rem;
    }
    
    .club-name {
        font-size: 1.3rem;
        font-weight: 600;
        color: #fff;
        margin-bottom: 5px;
    }
    
    .club-code {
        color: #ccc;
        font-size: 0.9rem;
        margin-bottom: 20px;
    }
    
    .club-stats {
        display: flex;
        justify-content: space-around;
        margin-bottom: 25px;
    }
    
    .stat {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 0.9rem;
        color: #ccc;
    }
    
    .club-actions {
        display: flex;
        gap: 10px;
        justify-content: center;
    }
    
    /* Announcements Grid */
    .announcements-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 30px;
        margin-bottom: 40px;
    }
    
    .announcement-card {
        background: rgba(255, 255, 255, 0.08);
        backdrop-filter: blur(15px);
        border-radius: 20px;
        padding: 25px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s ease;
    }
    
    .announcement-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(255, 207, 112, 0.2);
    }
    
    .announcement-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .announcement-badge {
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .announcement-badge.urgent {
        background: #e74c3c;
        color: #fff;
    }
    
    .announcement-badge.high {
        background: #f39c12;
        color: #fff;
    }
    
    .announcement-badge.medium {
        background: #3498db;
        color: #fff;
    }
    
    .announcement-badge.low {
        background: #95a5a6;
        color: #fff;
    }
    
    .announcement-date {
        font-size: 0.9rem;
        color: #ccc;
    }
    
    .announcement-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: #fff;
        margin-bottom: 10px;
    }
    
    .announcement-content {
        color: #ccc;
        line-height: 1.5;
        margin-bottom: 20px;
    }
    
    .announcement-footer {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
        font-size: 0.9rem;
        color: #ccc;
    }
    
    /* Features Grid */
    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 30px;
        margin-bottom: 40px;
    }
    
    .feature-card {
        background: rgba(255, 255, 255, 0.08);
        backdrop-filter: blur(15px);
        border-radius: 20px;
        padding: 30px;
        text-align: center;
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s ease;
    }
    
    .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(255, 207, 112, 0.2);
    }
    
    .feature-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #ffcf70, #f3a683);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 2rem;
        color: #000;
    }
    
    .feature-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: #fff;
        margin-bottom: 15px;
    }
    
    .feature-description {
        color: #ccc;
        line-height: 1.6;
    }
    
    /* CTA Section */
    .cta-section {
        padding: 100px 0;
        background: linear-gradient(135deg, rgba(255, 207, 112, 0.1), rgba(243, 166, 131, 0.1));
        backdrop-filter: blur(10px);
    }
    
    .cta-content {
        text-align: center;
        max-width: 800px;
        margin: 0 auto;
    }
    
    .cta-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 2.5rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 20px;
    }
    
    .cta-description {
        font-size: 1.2rem;
        color: #ccc;
        margin-bottom: 40px;
        line-height: 1.6;
    }
    
    .cta-buttons {
        display: flex;
        gap: 20px;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    /* No Content */
    .no-content {
        text-align: center;
        padding: 60px 20px;
        color: #ccc;
    }
    
    .no-content i {
        font-size: 4rem;
        color: #ffcf70;
        margin-bottom: 20px;
    }
    
    .no-content h3 {
        font-size: 1.5rem;
        margin-bottom: 10px;
        color: #fff;
    }
    
    /* Section Footer */
    .section-footer {
        text-align: center;
        margin-top: 40px;
    }
    
    /* Button Styles */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 14px 28px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        cursor: pointer;
        border: none;
        font-size: 1rem;
    }
    
    .btn-primary {
        background: linear-gradient(90deg, #ffcf70, #f3a683);
        color: #000;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(255, 207, 112, 0.3);
    }
    
    .btn-secondary {
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .btn-secondary:hover {
        background: rgba(255, 255, 255, 0.2);
        border-color: rgba(255, 255, 255, 0.3);
    }
    
    .btn-outline {
        background: transparent;
        color: #ffcf70;
        border: 2px solid #ffcf70;
    }
    
    .btn-outline:hover {
        background: #ffcf70;
        color: #000;
    }
    
    /* Container */
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    /* Background Animation */
    .hero-bg {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
        z-index: -1;
    }
    
    .bg-particles {
        position: absolute;
        width: 100%;
        height: 100%;
    }
    
    /* Responsive Design */
    @media (max-width: 1024px) {
        .hero-content {
            grid-template-columns: 1fr;
            text-align: center;
        }
        
        .hero-title {
            font-size: 3rem;
        }
        
        .hero-sphere {
            width: 300px;
            height: 300px;
        }
    }
    
    @media (max-width: 768px) {
        .hero {
            padding: 100px 0 60px;
        }
        
        .hero-title {
            font-size: 2.5rem;
        }
        
        .hero-sphere {
            width: 250px;
            height: 250px;
        }
        
        .section-title {
            font-size: 2rem;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .events-grid,
        .clubs-grid,
        .announcements-grid,
        .features-grid {
            grid-template-columns: 1fr;
        }
        
        .cta-title {
            font-size: 2rem;
        }
    }
    
    @media (max-width: 480px) {
        .hero-title {
            font-size: 2rem;
        }
        
        .hero-sphere {
            width: 200px;
            height: 200px;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .hero-buttons,
        .cta-buttons {
            flex-direction: column;
            align-items: center;
        }
    }
</style>

<script>
// Counter animation
function animateCounters() {
    const counters = document.querySelectorAll('.stat-number');
    
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-count'));
        const duration = 20;
        const increment = target / (duration / 16);
        let current = 0;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            counter.textContent = Math.floor(current);
        }, 16);
    });
}

// Event registration
function registerForEvent(eventId) {
    if (!confirm('Are you sure you want to register for this event?')) {
        return;
    }
    
    fetch('api/events.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'register',
            event_id: eventId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Successfully registered for the event!', 'success');
            // Update participant count
            setTimeout(() => location.reload(), 2000);
        } else {
            showNotification(data.message || 'Registration failed', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

// Join club
function joinClub(clubCode) {
    if (!confirm('Are you sure you want to join this club?')) {
        return;
    }
    
    fetch('api/clubs.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'join',
            club_code: clubCode
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Successfully joined the club!', 'success');
        } else {
            showNotification(data.message || 'Join request failed', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

// Initialize animations when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Start counter animation when stats section is visible
    const statsSection = document.querySelector('.stats-section');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounters();
                observer.unobserve(entry.target);
            }
        });
    });
    
    if (statsSection) {
        observer.observe(statsSection);
    }
    
    // Particle background animation
    createParticleBackground();
});

// Create particle background
function createParticleBackground() {
    const particlesContainer = document.getElementById('bgParticles');
    const particleCount = 50;
    
    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.style.position = 'absolute';
        particle.style.width = Math.random() * 4 + 1 + 'px';
        particle.style.height = particle.style.width;
        particle.style.background = 'rgba(255, 207, 112, ' + (Math.random() * 0.5 + 0.2) + ')';
        particle.style.borderRadius = '50%';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.top = Math.random() * 100 + '%';
        particle.style.animation = 'float ' + (Math.random() * 10 + 10) + 's ease-in-out infinite';
        particle.style.animationDelay = Math.random() * 10 + 's';
        
        particlesContainer.appendChild(particle);
    }
}

// Notification system
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `flash-message flash-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" style="background: none; border: none; color: #fff; cursor: pointer;">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.querySelector('.flash-messages').appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 10);
}
</script>

<?php
require_once 'footer.php';
?>