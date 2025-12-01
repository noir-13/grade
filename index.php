<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kolehiyo ng Lungsod ng Dasmariñas Grade System</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="verdantDesignSystem.css">
    <style>
        /* Premium Hero Section */
        .hero-section {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            overflow: hidden;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); /* Light BG */
        }

        /* Custom Carousel Styles from reference.html */
        .list .container {
            display: none;
            opacity: 0;
            transition: opacity 1s ease;
            width: 100%;
            height: 100%;
        }

        .list .container.active {
            display: block;
            opacity: 1;
        }

        .list .container.active .hero-text-content {
            opacity: 0;
            transform: translateX(30px);
            animation: fadeInUp 1s forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .list .container.active .hero-img-content img {
            opacity: 0;
            transform: scale(0.9);
            animation: showImg 1s forwards 0.3s;
        }

        @keyframes showImg {
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .hero-img {
            width: 100%;
            height: 500px; /* Fixed height */
            object-fit: cover; /* Maintain aspect ratio */
            border-radius: 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        }

        .carouselBtn {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 1rem;
            z-index: 10;
        }

        .carouselBtn button {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 1px solid var(--vds-forest);
            background: transparent;
            color: var(--vds-forest);
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .carouselBtn button:hover {
            background: var(--vds-forest);
            color: white;
            box-shadow: 0 5px 15px rgba(13, 59, 46, 0.2);
        }

        .hero-title {
            font-size: 4rem;
            font-weight: 800;
            color: var(--vds-forest);
            letter-spacing: -1.5px;
            line-height: 1.1;
            margin-bottom: 1.5rem;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            color: var(--vds-text-muted);
            font-weight: 400;
            line-height: 1.6;
            margin-bottom: 2.5rem;
        }

        .feature-icon-box {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--vds-sage), #ffffff);
            color: var(--vds-forest);
            border-radius: 24px;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        .vds-card:hover .feature-icon-box {
            transform: scale(1.1) rotate(5deg);
            background: var(--vds-forest);
            color: white;
        }

        .team-img {
            width: 140px;
            height: 140px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 1.5rem;
            border: 4px solid white;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .vds-card:hover .team-img {
            transform: scale(1.05);
            border-color: var(--vds-sage);
        }

        /* Floating shapes for background */
        .shape {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--vds-sage), transparent);
            opacity: 0.1;
            z-index: 0;
        }
        .shape-1 { width: 500px; height: 500px; top: -100px; right: -100px; }
        .shape-2 { width: 300px; height: 300px; bottom: 50px; left: -50px; }

        @media (max-width: 992px) {
            .hero-title { font-size: 3rem; }
            .hero-section { text-align: center; padding-top: 80px; }
            .hero-img { margin-top: 3rem; }
        }
    </style>
</head>
<body class="home" style="display: flex; flex-direction: column; min-height: 100vh;">

    <?php include 'navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        
        <div class="vds-container position-relative list" style="z-index: 2;">
            
            <!-- Slide 1 -->
            <div class="container active">
                <div class="row align-items-center">
                    <div class="col-lg-6 hero-text-content">
                        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-4" style="background: rgba(13, 59, 46, 0.1); border: 1px solid rgba(13, 59, 46, 0.1);">
                            <span style="width: 8px; height: 8px; background: var(--vds-forest); border-radius: 50%;"></span>
                            <span style="color: var(--vds-forest); font-size: 0.9rem; font-weight: 600;">System Operational</span>
                        </div>
                        <h1 class="hero-title">
                            Elevating Academic<br>Performance
                        </h1>
                        <p class="hero-subtitle">
                            Experience the next generation of grade management. 
                            Secure, transparent, and designed for the future of education at Kolehiyo ng Lungsod ng Dasmariñas.
                        </p>
                        <div class="d-flex gap-3 flex-wrap">
                            <a href="login.php" class="vds-btn vds-btn-primary" style="padding: 16px 40px; font-size: 1.1rem; border-radius: 16px;">
                                Access Portal <i class="bi bi-arrow-right ms-2"></i>
                            </a>
                            <a href="register.php" class="vds-btn vds-btn-secondary" style="padding: 16px 40px; font-size: 1.1rem; border-radius: 16px; background: white; border: 1px solid var(--vds-sage);">
                                New Account
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-6 hero-img-content">
                        <img src="assets/kld.png" alt="KLD Building" class="hero-img">
                    </div>
                </div>
            </div>

            <!-- Slide 2 -->
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6 hero-text-content">
                        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-4" style="background: rgba(13, 59, 46, 0.1); border: 1px solid rgba(13, 59, 46, 0.1);">
                            <span style="width: 8px; height: 8px; background: var(--vds-forest); border-radius: 50%;"></span>
                            <span style="color: var(--vds-forest); font-size: 0.9rem; font-weight: 600;">Real-Time Analytics</span>
                        </div>
                        <h1 class="hero-title">
                            Data-Driven<br>Decisions
                        </h1>
                        <p class="hero-subtitle">
                            Empower students and faculty with instant access to academic progress.
                            Visualize performance trends and make informed choices for a better future.
                        </p>
                        <div class="d-flex gap-3 flex-wrap">
                            <a href="login.php" class="vds-btn vds-btn-primary" style="padding: 16px 40px; font-size: 1.1rem; border-radius: 16px;">
                                Get Started <i class="bi bi-arrow-right ms-2"></i>
                            </a>
                            <a href="#about" class="vds-btn vds-btn-secondary" style="padding: 16px 40px; font-size: 1.1rem; border-radius: 16px; background: white; border: 1px solid var(--vds-sage);">
                                Learn More
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-6 hero-img-content">
                        <img src="assets/room.png" alt="Classroom" class="hero-img">
                    </div>
                </div>
            </div>

            <!-- Slide 3 -->
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6 hero-text-content">
                        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-4" style="background: rgba(13, 59, 46, 0.1); border: 1px solid rgba(13, 59, 46, 0.1);">
                            <span style="width: 8px; height: 8px; background: var(--vds-forest); border-radius: 50%;"></span>
                            <span style="color: var(--vds-forest); font-size: 0.9rem; font-weight: 600;">Campus Life</span>
                        </div>
                        <h1 class="hero-title">
                            Seamless<br>Digital Experience
                        </h1>
                        <p class="hero-subtitle">
                            Access your portal from any device, anywhere.
                            A unified platform connecting students, teachers, and administrators in one ecosystem.
                        </p>
                        <div class="d-flex gap-3 flex-wrap">
                            <a href="register.php" class="vds-btn vds-btn-primary" style="padding: 16px 40px; font-size: 1.1rem; border-radius: 16px;">
                                Join Now <i class="bi bi-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-6 hero-img-content">
                        <img src="assets/gym.png" alt="Gymnasium" class="hero-img">
                    </div>
                </div>
            </div>

        </div>

        <div class="carouselBtn">
            <button class="prev-btn"><i class="bi bi-chevron-left"></i></button>
            <button class="next-btn"><i class="bi bi-chevron-right"></i></button>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const slides = document.querySelectorAll('.list .container');
            let currentSlide = 0;
            let slideInterval;

            function showSlide(index) {
                slides.forEach(slide => {
                    slide.classList.remove('active');
                    void slide.offsetWidth; // Trigger reflow to restart animations
                });
                slides[index].classList.add('active');
            }

            function nextSlide() {
                currentSlide++;
                if (currentSlide >= slides.length) {
                    currentSlide = 0;
                }
                showSlide(currentSlide);
            }

            function prevSlide() {
                currentSlide--;
                if (currentSlide < 0) {
                    currentSlide = slides.length - 1;
                }
                showSlide(currentSlide);
            }

            function startSlideShow() {
                slideInterval = setInterval(nextSlide, 6000);
            }

            function resetSlideShow() {
                clearInterval(slideInterval);
                startSlideShow();
            }

            const next = document.querySelector('.next-btn');
            if (next) {
                next.addEventListener('click', () => {
                    nextSlide();
                    resetSlideShow();
                });
            }

            const prev = document.querySelector('.prev-btn');
            if (prev) {
                prev.addEventListener('click', () => {
                    prevSlide();
                    resetSlideShow();
                });
            }
            
            // Start auto play
            startSlideShow();
        });
    </script>

    <!-- About / Features Section -->
    <section id="about" class="vds-section" style="background: white;">
        <div class="vds-container">
            <div class="row g-5 align-items-center">
                <div class="col-lg-5">
                    <span class="vds-label" style="color: var(--vds-forest);">Innovation First</span>
                    <h2 class="vds-h2 mb-4" style="font-size: 3rem;">Redefining<br>Efficiency</h2>
                    <p class="vds-text-lead mb-4" style="color: var(--vds-text-muted);">
                        We've reimagined how academic data is handled. From instant computations to real-time analytics, every feature is crafted to save time and reduce errors.
                    </p>
                    <div class="d-flex gap-4 mt-5">
                        <div>
                            <h3 class="vds-h2 mb-0" style="color: var(--vds-forest);">99%</h3>
                            <p class="small text-muted">Accuracy Rate</p>
                        </div>
                        <div>
                            <h3 class="vds-h2 mb-0" style="color: var(--vds-forest);">0s</h3>
                            <p class="small text-muted">Paperwork</p>
                        </div>
                        <div>
                            <h3 class="vds-h2 mb-0" style="color: var(--vds-forest);">24/7</h3>
                            <p class="small text-muted">Accessibility</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="vds-grid-2">
                        <!-- Feature 1 -->
                        <div class="vds-card p-5 border-0" style="background: var(--vds-vapor);">
                            <div class="feature-icon-box">
                                <i class="bi bi-lightning-charge"></i>
                            </div>
                            <h4 class="vds-h4 mb-3">Lightning Fast</h4>
                            <p class="vds-text-muted mb-0">Automated grade calculations that happen in milliseconds, not hours.</p>
                        </div>
                        <!-- Feature 2 -->
                        <div class="vds-card p-5 border-0" style="background: white; box-shadow: 0 20px 40px rgba(0,0,0,0.05);">
                            <div class="feature-icon-box">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <h4 class="vds-h4 mb-3">Bank-Grade Security</h4>
                            <p class="vds-text-muted mb-0">Your data is protected by industry-standard encryption protocols.</p>
                        </div>
                        <!-- Feature 3 -->
                        <div class="vds-card p-5 border-0" style="background: white; box-shadow: 0 20px 40px rgba(0,0,0,0.05);">
                            <div class="feature-icon-box">
                                <i class="bi bi-phone"></i>
                            </div>
                            <h4 class="vds-h4 mb-3">Mobile Optimized</h4>
                            <p class="vds-text-muted mb-0">A seamless experience across all your devices, anywhere, anytime.</p>
                        </div>
                        <!-- Feature 4 -->
                        <div class="vds-card p-5 border-0" style="background: var(--vds-vapor);">
                            <div class="feature-icon-box">
                                <i class="bi bi-graph-up-arrow"></i>
                            </div>
                            <h4 class="vds-h4 mb-3">Smart Analytics</h4>
                            <p class="vds-text-muted mb-0">Visual insights that help track performance trends over time.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section id="team" class="vds-section" style="background: linear-gradient(to bottom, #ffffff, var(--vds-vapor));">
        <div class="vds-container text-center">
            <span class="vds-label">The Visionaries</span>
            <h2 class="vds-h2 mb-5">Meet Our Team</h2>
            
            <div class="row justify-content-center g-4">
                <?php
                $team = [
                    ['name' => 'Rogie Mar U. Ramos', 'role' => 'Front-End Developer', 'img' => 'assets/rogie.png', 'id' => 'rogie'],
                    ['name' => 'Arsyl F. Salva', 'role' => 'Back-End Developer', 'img' => 'assets/arsyl.png', 'id' => 'arsyl'],
                    ['name' => 'Jhon Messiah M. Romero', 'role' => 'System Analyst', 'img' => 'assets/jm.png', 'id' => 'jm'],
                    ['name' => 'Kevin L. Selibio', 'role' => 'Administrator', 'img' => 'assets/kevin.png', 'id' => 'kevin'],
                    ['name' => 'Renzo Nathaniel D. Ortega', 'role' => 'Process Manager', 'img' => 'assets/renzo.png', 'id' => 'renzo']
                ];

                foreach ($team as $member) {
                    echo '
                    <div class="col-md-6 col-lg-2"> <!-- Adjusted col size for better fit -->
                        <a href="team.php#'.$member['id'].'" class="text-decoration-none">
                            <div class="vds-card p-4 h-100 text-center border-0 hover-lift" style="background: white; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
                                <img src="'.$member['img'].'" alt="'.$member['name'].'" class="team-img">
                                <h5 class="vds-h4" style="font-size: 1.1rem; margin-bottom: 0.5rem;">'.$member['name'].'</h5>
                                <p class="vds-text-muted small mb-0" style="color: var(--vds-forest);">'.$member['role'].'</p>
                            </div>
                        </a>
                    </div>';
                }
                ?>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="vds-section" style="background: white;">
        <div class="vds-container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="text-center mb-5">
                        <span class="vds-label">Support</span>
                        <h2 class="vds-h2">Common Questions</h2>
                    </div>
                    
                    <div class="accordion accordion-flush" id="faqAccordion">
                        <?php
                        $faqs = [
                            "How do I reset my password?" => "Click on the 'Forgot Password' link on the login page. You'll receive an OTP via email to verify your identity and set a new password.",
                            "Is the system accessible off-campus?" => "Yes, the KLD Grade System is a cloud-based web application accessible from any device with an internet connection.",
                            "How are grades computed?" => "Grades are automatically calculated based on the raw scores input by teachers, following the official KLD grading system formulas.",
                            "Who do I contact for technical issues?" => "You can use the contact form below or email the IT support team directly at support@kld.edu.ph.",
                            "Can I view my grades from previous semesters?" => "Yes, your student dashboard maintains a complete history of your academic records across all semesters.",
                            "How do I update my personal information?" => "Navigate to the 'Profile' section in your dashboard. Some fields may be editable, while others require administrative approval.",
                            "Is my data secure?" => "Absolutely. We use industry-standard encryption and security protocols to ensure your personal and academic data remains confidential.",
                            "What browser should I use?" => "The system is optimized for modern browsers like Google Chrome, Microsoft Edge, Firefox, and Safari.",
                            "Can parents access the portal?" => "Currently, the portal is designed for students and faculty. However, students can print their grade reports to share with parents.",
                            "How often is the system updated?" => "We perform regular maintenance and updates to ensure optimal performance. Scheduled maintenance is announced in advance."
                        ];

                        $i = 1;
                        foreach ($faqs as $question => $answer) {
                            echo '
                            <div class="accordion-item border-0 mb-3">
                                <h2 class="accordion-header" id="heading'.$i.'">
                                    <button class="accordion-button collapsed p-4" type="button" data-bs-toggle="collapse" data-bs-target="#collapse'.$i.'" style="background: var(--vds-vapor); border-radius: 16px; font-weight: 600; color: var(--vds-forest); box-shadow: none;">
                                        '.$question.'
                                    </button>
                                </h2>
                                <div id="collapse'.$i.'" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body p-4" style="color: var(--vds-text-muted); line-height: 1.6;">
                                        '.$answer.'
                                    </div>
                                </div>
                            </div>';
                            $i++;
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="vds-section" style="background-color: var(--vds-forest); color: white;">
        <div class="vds-container">
            <div class="row align-items-center">
                <div class="col-lg-5 mb-5 mb-lg-0">
                    <span class="vds-label" style="color: var(--vds-sage);">Get in Touch</span>
                    <h2 class="vds-h2 mb-4" style="color: white;">We're Here to Help</h2>
                    <p class="vds-text-lead mb-5" style="color: rgba(255,255,255,0.8);">
                        Have questions about the system? Our support team is ready to assist you.
                    </p>
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="feature-icon-box" style="width: 50px; height: 50px; font-size: 1.2rem; margin-bottom: 0; background: rgba(255,255,255,0.1); color: white;">
                            <i class="bi bi-envelope"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">Email Us</h5>
                            <p class="mb-0 small" style="color: rgba(255,255,255,0.7);">support@kld.edu.ph</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="feature-icon-box" style="width: 50px; height: 50px; font-size: 1.2rem; margin-bottom: 0; background: rgba(255,255,255,0.1); color: white;">
                            <i class="bi bi-geo-alt"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">Visit Us</h5>
                            <p class="mb-0 small" style="color: rgba(255,255,255,0.7);">KLD Campus, Dasmariñas City</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 offset-lg-1">
                    <div class="vds-glass p-5" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
                        <form>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="vds-form-group">
                                        <label class="vds-label" style="color: rgba(255,255,255,0.8);">Name</label>
                                        <input type="text" class="vds-input" placeholder="Your Name" style="background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); color: white;">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="vds-form-group">
                                        <label class="vds-label" style="color: rgba(255,255,255,0.8);">Email</label>
                                        <input type="email" class="vds-input" placeholder="Your Email" style="background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); color: white;">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="vds-form-group">
                                        <label class="vds-label" style="color: rgba(255,255,255,0.8);">Message</label>
                                        <textarea class="vds-input" rows="4" placeholder="How can we help?" style="background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); color: white;"></textarea>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="vds-btn vds-btn-primary w-100" style="background: white; color: var(--vds-forest);">Send Message</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>
    <?php include 'includes/legal_modals.php'; ?>
    
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
