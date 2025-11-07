<?php
/**
 * ClubSphere - Enhanced Footer File
 * Features: Dynamic content, social links, performance metrics
 * 
 * @version 2.0
 * @author ClubSphere Development Team
 */

// Get current year and version
$currentYear = date('Y');
$appVersion = config('app.version', '1.0.0');
$isDevelopment = config('app.debug', false);

// Get performance metrics if development mode
$loadTime = '';
if ($isDevelopment && class_exists('Database')) {
    $db = Database::getInstance();
    $stats = $db->getStats();
    $loadTime = number_format($stats['connection_time'], 4);
}

?>
    </main>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <!-- Footer Content -->
            <div class="footer-content">
                <!-- Brand Section -->
                <div class="footer-section footer-brand">
                    <div class="logo">
                        <div class="logo-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="logo-text">ClubSphere</div>
                    </div>
                    <p class="footer-description">
                        Empowering students to connect, collaborate, and create through innovative club management solutions.
                    </p>
                    <div class="social-links">
                        <a href="#" class="social-link" title="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-link" title="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-link" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-link" title="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" class="social-link" title="GitHub">
                            <i class="fab fa-github"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="footer-section">
                    <h3 class="footer-title">Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="events.php">Events</a></li>
                        <li><a href="announcements.php">Announcements</a></li>
                        <li><a href="clubs.php">Clubs</a></li>
                        <li><a href="about.php">About Us</a></li>
                    </ul>
                </div>
                
                <!-- Clubs -->
                <div class="footer-section">
                    <h3 class="footer-title">Popular Clubs</h3>
                    <ul class="footer-links">
                        <li><a href="clubs/acm.php">ACM Student Chapter</a></li>
                        <li><a href="clubs/aces.php">ACES Association</a></li>
                        <li><a href="clubs/cesa.php">CESA Chapter</a></li>
                        <li><a href="clubs/mesa.php">MESA Organization</a></li>
                        <li><a href="clubs/itsa.php">ITSA Community</a></li>
                        <li><a href="clubs/ieee.php">IEEE Student Branch</a></li>
                    </ul>
                </div>
                
                <!-- Resources -->
                <div class="footer-section">
                    <h3 class="footer-title">Resources</h3>
                    <ul class="footer-links">
                        <li><a href="help.php">Help Center</a></li>
                        <li><a href="faq.php">FAQ</a></li>
                        <li><a href="documentation.php">Documentation</a></li>
                        <li><a href="api.php">API Reference</a></li>
                        <li><a href="contact.php">Contact Support</a></li>
                    </ul>
                </div>
                
                <!-- Legal -->
                <div class="footer-section">
                    <h3 class="footer-title">Legal</h3>
                    <ul class="footer-links">
                        <li><a href="terms.php">Terms of Service</a></li>
                        <li><a href="privacy.php">Privacy Policy</a></li>
                        <li><a href="cookies.php">Cookie Policy</a></li>
                        <li><a href="accessibility.php">Accessibility</a></li>
                        <li><a href="security.php">Security</a></li>
                    </ul>
                </div>
            </div>
            
            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <div class="footer-info">
                    <p>&copy; <?php echo $currentYear; ?> ClubSphere. All rights reserved.</p>
                    <p>Version <?php echo $appVersion; ?></p>
                    <?php if ($isDevelopment && $loadTime): ?>
                        <p>Load time: <?php echo $loadTime; ?>s</p>
                    <?php endif; ?>
                </div>
                
                <div class="footer-extra">
                    <a href="sitemap.php">Sitemap</a>
                    <span>|</span>
                    <a href="status.php">System Status</a>
                    <?php if ($isDevelopment): ?>
                        <span>|</span>
                        <a href="phpinfo.php">PHP Info</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Back to Top Button -->
    <button class="back-to-top" id="backToTop" title="Back to Top">
        <i class="fas fa-arrow-up"></i>
    </button>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
        </div>
    </div>
    
    <!-- JavaScript Libraries -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Back to Top Button
        const backToTopBtn = document.getElementById('backToTop');
        
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopBtn.classList.add('show');
            } else {
                backToTopBtn.classList.remove('show');
            }
        });
        
        backToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        // Loading Overlay
        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }
        
        function hideLoading() {
            document.getElementById('loadingOverlay').style.display = 'none';
        }
        
        // Add loading to form submissions
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                showLoading();
            });
        });
        
        // Add loading to navigation links
        document.querySelectorAll('a[href]').forEach(link => {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href && !href.startsWith('#') && !href.startsWith('javascript:')) {
                    showLoading();
                }
            });
        });
        
        // Hide loading when page is fully loaded
        window.addEventListener('load', function() {
            hideLoading();
        });
        
        // Error handling for images
        document.querySelectorAll('img').forEach(img => {
            img.addEventListener('error', function() {
                this.src = '<?php echo baseUrl('assets/images/placeholder.jpg'); ?>';
            });
        });
        
        // Initialize AOS animations
        if (typeof AOS !== 'undefined') {
            AOS.init({
                duration: 800,
                once: true,
                offset: 100
            });
        }
        
        // Performance monitoring
        if ('performance' in window) {
            window.addEventListener('load', function() {
                setTimeout(function() {
                    const perfData = performance.getEntriesByType('navigation')[0];
                    const loadTime = perfData.loadEventEnd - perfData.loadEventStart;
                    console.log('Page load time:', loadTime, 'ms');
                    
                    // Send to analytics if in production
                    <?php if (!config('app.debug')): ?>
                        // gtag('event', 'page_load_time', {
                        //     value: loadTime,
                        //     event_category: 'performance'
                        // });
                    <?php endif; ?>
                }, 0);
            });
        }
        
        // Service Worker Registration (for PWA)
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('sw.js')
                    .then(function(registration) {
                        console.log('ServiceWorker registration successful');
                    })
                    .catch(function(err) {
                        console.log('ServiceWorker registration failed');
                    });
            });
        }
        
        // Dark mode toggle (if implemented)
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isDark);
        }
        
        // Load dark mode preference
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
        }
        
        // Lazy loading for images
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
        
        // Console easter egg
        console.log(`
        🌟 Welcome to ClubSphere! 🌟
        
        Built with ❤️ by the ClubSphere Development Team
        Version: <?php echo $appVersion; ?>
        Environment: <?php echo config('app.env'); ?>
        
        Interested in contributing? Check out our GitHub!
        https://github.com/clubsphere/clubsphere
        `);
    </script>
    
    <!-- Custom Styles for Footer -->
    <style>
        /* Footer Styles */
        .footer {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(15px);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #e0e0e0;
            padding: 60px 0 20px;
            margin-top: 80px;
        }
        
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .footer-section {
            min-width: 0;
        }
        
        .footer-brand .logo {
            margin-bottom: 20px;
        }
        
        .footer-description {
            color: #ccc;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .social-links {
            display: flex;
            gap: 12px;
        }
        
        .social-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: #e0e0e0;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .social-link:hover {
            background: #ffcf70;
            color: #000;
            transform: translateY(-2px);
        }
        
        .footer-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.1rem;
            font-weight: 600;
            color: #ffcf70;
            margin-bottom: 20px;
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer-links li {
            margin-bottom: 12px;
        }
        
        .footer-links a {
            color: #ccc;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .footer-links a:hover {
            color: #ffcf70;
            transform: translateX(5px);
        }
        
        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .footer-info {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .footer-info p {
            margin: 0;
            color: #aaa;
            font-size: 0.9rem;
        }
        
        .footer-extra {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .footer-extra a {
            color: #aaa;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        
        .footer-extra a:hover {
            color: #ffcf70;
        }
        
        /* Back to Top Button */
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #ffcf70, #f3a683);
            border: none;
            border-radius: 50%;
            color: #000;
            font-size: 1.2rem;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 999;
            box-shadow: 0 4px 20px rgba(255, 207, 112, 0.3);
        }
        
        .back-to-top.show {
            opacity: 1;
            visibility: visible;
        }
        
        .back-to-top:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(255, 207, 112, 0.4);
        }
        
        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            backdrop-filter: blur(5px);
        }
        
        .loading-spinner {
            color: #ffcf70;
            font-size: 3rem;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Responsive Design */
        @media (max-width: 1024px) {
            .footer-content {
                grid-template-columns: 1fr 1fr;
                gap: 30px;
            }
            
            .footer-brand {
                grid-column: 1 / -1;
            }
        }
        
        @media (max-width: 768px) {
            .footer {
                padding: 40px 0 20px;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }
            
            .footer-info {
                justify-content: center;
            }
            
            .back-to-top {
                bottom: 20px;
                right: 20px;
                width: 45px;
                height: 45px;
                font-size: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .footer-content {
                gap: 20px;
            }
            
            .social-links {
                justify-content: center;
            }
            
            .footer-links {
                text-align: center;
            }
        }
        
        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .footer {
                background: rgba(0, 0, 0, 0.4);
            }
        }
    </style>
    
    <!-- Google Analytics (if in production) -->
    <?php if (config('analytics.enabled') && config('analytics.google_analytics')): ?>
        <!-- Global site tag (gtag.js) - Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo config('analytics.google_analytics'); ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?php echo config('analytics.google_analytics'); ?>');
        </script>
    <?php endif; ?>
    
</body>
</html>

<?php
// End output buffering and flush content
ob_end_flush();

// Log page visit for analytics
if (isLoggedIn() && !defined('PAGE_VISIT_LOGGED')) {
    define('PAGE_VISIT_LOGGED', true);
    logActivity(
        $currentUser['id'], 
        'PAGE_VISIT', 
        'Visited: ' . currentUrl(),
        'Page',
        0
    );
}
?>