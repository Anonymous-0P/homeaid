<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HomeAid - Professional Home Services at Your Fingertips</title>
    <?php
    // Include service icons helper and database connection
    require_once 'includes/service_icons.php';
    require_once 'config/db.php';
    
    // Fetch services from database
    $db_services = [];
    try {
        $result = $conn->query("SELECT id, name, description, icon_key FROM services ORDER BY name");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $db_services[] = $row;
            }
        }
    } catch (Exception $e) {
        // Continue with empty array if database error
    }
    ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --accent-color: #3b82f6;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --bg-light: #f9fafb;
            --white: #ffffff;
            --gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            overflow-x: hidden;
        }

        /* Animated Background */
        .animated-bg {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: -1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            opacity: 0.03;
        }

        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .shape {
            position: absolute;
            background: var(--accent-color);
            opacity: 0.1;
            animation: float 20s infinite ease-in-out;
        }

        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            left: 10%;
            top: 20%;
            border-radius: 50%;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            right: 20%;
            top: 60%;
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            animation-delay: 2s;
        }

        .shape:nth-child(3) {
            width: 100px;
            height: 100px;
            left: 50%;
            bottom: 20%;
            border-radius: 63% 37% 54% 46% / 55% 48% 52% 45%;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            25% { transform: translateY(-30px) rotate(90deg); }
            50% { transform: translateY(0) rotate(180deg); }
            75% { transform: translateY(30px) rotate(270deg); }
        }

        /* Navigation */
        nav {
            position: fixed;
            width: 100%;
            top: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            padding: 1rem 0;
            transition: all 0.3s ease;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: var(--primary-color);
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            padding-top: 80px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            width: 100%;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .hero-content {
            animation: slideInLeft 1s ease;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .hero-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            color: var(--text-light);
            margin-bottom: 2.5rem;
            line-height: 1.8;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1rem 2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: var(--gradient);
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
        }

        .btn-outline {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            background: transparent;
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .hero-visual {
            position: relative;
            animation: slideInRight 1s ease;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .hero-illustration {
            width: 100%;
            height: 500px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(102, 126, 234, 0.3);
        }

        .service-icons-floating {
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 2rem;
            padding: 2rem;
        }

        .floating-icon {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            animation: bounce 3s infinite ease-in-out;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .floating-icon:nth-child(1) { animation-delay: 0s; }
        .floating-icon:nth-child(2) { animation-delay: 0.5s; }
        .floating-icon:nth-child(3) { animation-delay: 1s; }
        .floating-icon:nth-child(4) { animation-delay: 1.5s; }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        /* Stats Section */
        .stats-section {
            padding: 4rem 0;
            background: var(--bg-light);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
        }

        .stat-card {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 0.95rem;
        }

        /* Services Section */
        .services-section {
            padding: 6rem 0;
            position: relative;
        }

        /* About Section */
        .about-section {
            padding: 6rem 0;
            background: var(--bg-light);
        }
        .about-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 3rem;
            align-items: center;
        }
        .about-title {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 1rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .about-text {
            color: var(--text-light);
            font-size: 1.05rem;
        }
        .about-highlights {
            margin-top: 1.5rem;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }
        .about-item {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 1rem 1.25rem;
            display: flex;
            gap: .75rem;
            align-items: flex-start;
            box-shadow: 0 10px 25px rgba(0,0,0,0.04);
        }
        .about-item .icon {
            font-size: 1.25rem;
        }

        /* Contact Section */
        .contact-section {
            padding: 6rem 0;
            background: #fff;
        }
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2.5rem;
        }
        .contact-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
        }
        .contact-card h3 { margin-bottom: .75rem; }
        .contact-list { list-style: none; padding: 0; margin: 0; }
        .contact-list li { display: flex; gap: .75rem; align-items: center; padding: .5rem 0; color: var(--text-light); }
        .contact-list .icon { font-size: 1.1rem; }
        .contact-form .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .contact-form label { display:block; font-weight:600; margin:.5rem 0 .25rem; color: var(--text-dark); }
        .contact-form input, .contact-form textarea {
            width: 100%;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: .85rem 1rem;
            font-family: inherit;
            font-size: 1rem;
            outline: none;
            transition: border-color .2s ease, box-shadow .2s ease;
        }
        .contact-form input:focus, .contact-form textarea:focus { border-color: var(--primary-color); box-shadow: 0 0 0 4px rgba(37,99,235,.08); }
        .contact-form textarea { min-height: 140px; resize: vertical; }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .section-subtitle {
            font-size: 1.2rem;
            color: var(--text-light);
        }

        .service-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }

        .service-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--gradient);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .service-card:hover::before {
            transform: scaleX(1);
        }

        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
        }

        .service-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 1.5rem;
            transition: all 0.3s ease;
        }

        .service-card:hover .service-icon {
            transform: scale(1.1) rotate(5deg);
            background: var(--gradient);
        }

        .service-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text-dark);
        }

        .service-card p {
            color: var(--text-light);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        /* How It Works Section */
        .how-it-works {
            padding: 6rem 0;
            background: var(--bg-light);
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 3rem;
            margin-top: 4rem;
        }

        .step-card {
            text-align: center;
            position: relative;
        }

        .step-number {
            width: 60px;
            height: 60px;
            background: var(--gradient);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0 auto 1.5rem;
            position: relative;
            z-index: 2;
        }

        .step-card:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 30px;
            left: 60%;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, var(--primary-color), transparent);
            z-index: 1;
        }

        .step-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-dark);
        }

        .step-description {
            color: var(--text-light);
            line-height: 1.6;
        }

        /* CTA Section */
        .cta-section {
            padding: 6rem 0;
            background: var(--gradient);
            position: relative;
            overflow: hidden;
        }

        .cta-content {
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .cta-title {
            font-size: 3rem;
            font-weight: 800;
            color: white;
            margin-bottom: 1.5rem;
        }

        .cta-subtitle {
            font-size: 1.3rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2.5rem;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-white {
            background: white;
            color: var(--primary-color);
            padding: 1rem 2.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .btn-white:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }

        .cta-pattern {
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: -150px;
            right: -150px;
        }

        /* Footer */
        footer {
            padding: 3rem 0;
            background: var(--text-dark);
            color: white;
            text-align: center;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .footer-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .steps-grid {
                grid-template-columns: 1fr;
            }

            .step-card::after {
                display: none;
            }

            .nav-links {
                display: none;
            }

            .footer-content {
                flex-direction: column;
                gap: 1rem;
            }

            .about-grid { grid-template-columns: 1fr; }
            .about-highlights { grid-template-columns: 1fr; }
            .contact-grid { grid-template-columns: 1fr; }
            .contact-form .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="animated-bg">
        <div class="floating-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>
    </div>

    <nav>
        <div class="nav-container">
            <a href="/homeaid/index.php" class="logo">HomeAid</a>
            <ul class="nav-links">
                <li><a href="#services">Services</a></li>
                <li><a href="#how-it-works">How It Works</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
        </div>
    </nav>

    <main>
        <section class="hero">
            <div class="container">
                <div class="hero-grid">
                    <div class="hero-content">
                        <!-- <span class="hero-badge">🏆 Trusted by 50,000+ Happy Customers</span> -->
                        <h1 class="hero-title">Your Home Deserves the Best Care</h1>
                        <p class="hero-subtitle">Connect with verified professionals for all your home service needs. Quick, reliable, and hassle-free solutions at competitive prices.</p>
                        <div class="hero-buttons">
                            <a href="customer/register.php" class="btn btn-primary">
                                <span>👤</span>
                                Get Started as Customer
                            </a>
                            <a href="provider/register.php" class="btn btn-outline">
                                <span>🔧</span>
                                Join as Service Provider
                            </a>
                        </div>
                    </div>
                    <div class="hero-visual">
                        <div class="hero-illustration">
                            <div class="service-icons-floating">
                                <div class="floating-icon">🔧</div>
                                <div class="floating-icon">⚡</div>
                                <div class="floating-icon">🏠</div>
                                <div class="floating-icon">🧹</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- <section class="stats-section">
            <div class="container">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number">50K+</div>
                        <div class="stat-label">Happy Customers</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">1000+</div>
                        <div class="stat-label">Verified Providers</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">4.8★</div>
                        <div class="stat-label">Average Rating</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Support Available</div>
                    </div>
                </div>
            </div>
        </section> -->

        <section class="services-section" id="services">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Our Premium Services</h2>
                    <p class="section-subtitle">Professional solutions for every home need</p>
                </div>
                <div class="service-grid">
                    <?php if (!empty($db_services)): ?>
                        <?php foreach ($db_services as $service): ?>
                            <div class="service-card">
                                <div class="service-icon"><?php echo ServiceIcons::getIconByKey($service['icon_key']); ?></div>
                                <h3><?php echo htmlspecialchars($service['name']); ?></h3>
                                <p><?php echo htmlspecialchars($service['description']); ?></p>
                                <a href="services/<?php echo str_replace(' ', '', strtolower($service['name'])); ?>.php" class="btn btn-primary">Book Now</a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Fallback services if database is empty -->
                        <div class="service-card">
                            <div class="service-icon">🔧</div>
                            <h3>Plumbing</h3>
                            <p>Expert plumbers for leak repairs, pipe installation, and water heater services.</p>
                            <a href="services/plumbing.php" class="btn btn-primary">Book Now</a>
                        </div>
                        <div class="service-card">
                            <div class="service-icon">⚡</div>
                            <h3>Electrical</h3>
                            <p>Licensed electricians for wiring, repairs, and smart home installations.</p>
                            <a href="services/electrician.php" class="btn btn-primary">Book Now</a>
                        </div>
                        <div class="service-card">
                            <div class="service-icon">�</div>
                            <h3>Home Repair</h3>
                            <p>Skilled handymen for painting, carpentry, and general maintenance.</p>
                            <a href="#" class="btn btn-primary">Book Now</a>
                        </div>
                        <div class="service-card">
                            <div class="service-icon">🧹</div>
                            <h3>Cleaning</h3>
                            <p>Professional deep cleaning for homes, offices, and post-construction sites.</p>
                            <a href="#" class="btn btn-primary">Book Now</a>
                        </div>
                        <div class="service-card">
                            <div class="service-icon">❄️</div>
                            <h3>HVAC Services</h3>
                            <p>AC repair, installation, and maintenance for year-round comfort.</p>
                            <a href="#" class="btn btn-primary">Book Now</a>
                        </div>
                        <div class="service-card">
                            <div class="service-icon">�</div>
                            <h3>Gardening</h3>
                            <p>Landscaping, lawn care, and garden maintenance services.</p>
                            <a href="#" class="btn btn-primary">Book Now</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="how-it-works" id="how-it-works">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">How It Works</h2>
                    <p class="section-subtitle">Get your home services in 3 simple steps</p>
                </div>
                <div class="steps-grid">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <h3 class="step-title">Book a Service</h3>
                        <p class="step-description">Choose your required service and schedule a convenient time. Get instant quotes from verified providers.</p>
                    </div>
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <h3 class="step-title">Get Matched</h3>
                        <p class="step-description">We connect you with the best professionals in your area based on ratings, expertise, and availability.</p>
                    </div>
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <h3 class="step-title">Job Done Right</h3>
                        <p class="step-description">Experienced professionals complete your service. Pay securely and leave a review to help others.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="about-section" id="about">
            <div class="container">
                <div class="about-grid">
                    <div>
                        <h2 class="about-title">About HomeAid</h2>
                        <p class="about-text">HomeAid connects customers with verified, reliable service providers for everything from plumbing and electrical work to cleaning and repairs. Our mission is to make home care effortless, transparent, and trustworthy—so you can focus on what matters most.</p>
                        <div class="about-highlights">
                            <div class="about-item"><span class="icon">✅</span><div><strong>Verified Pros</strong><div style="color:var(--text-light)">Background-checked providers with ratings and reviews</div></div></div>
                            <div class="about-item"><span class="icon">🕒</span><div><strong>Quick Booking</strong><div style="color:var(--text-light)">Fast scheduling with clear pricing—no surprises</div></div></div>
                            <div class="about-item"><span class="icon">🛟</span><div><strong>Reliable Support</strong><div style="color:var(--text-light)">We’re here to help throughout your service</div></div></div>
                            <div class="about-item"><span class="icon">🌟</span><div><strong>Top Rated</strong><div style="color:var(--text-light)">Thousands of happy customers and growing</div></div></div>
                        </div>
                    </div>
                    <div class="contact-card">
                        <h3>Why Choose Us</h3>
                        <p style="color:var(--text-light)">We combine a curated network of professionals with simple booking and transparent communication, giving you a smooth experience from request to completion.</p>
                        <ul class="contact-list" style="margin-top: .75rem;">
                            <li><span class="icon">🔒</span> Secure and private</li>
                            <li><span class="icon">💬</span> Clear updates and notifications</li>
                            <li><span class="icon">💳</span> Flexible payment options</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <section class="contact-section" id="contact">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Get in Touch</h2>
                    <p class="section-subtitle">We’d love to hear from you</p>
                </div>
                <div class="contact-grid">
                    <div class="contact-card">
                        <h3>Contact Information</h3>
                        <ul class="contact-list" style="margin-top:.5rem;">
                            <li><span class="icon">📍</span> 123 HomeAid Street, Your City</li>
                            <li><span class="icon">✉️</span> <a href="mailto:admin@homeaid.com">admin@homeaid.com</a></li>
                            <li><span class="icon">☎️</span> +1 (555) 123-4567</li>
                            <li><span class="icon">⏰</span> Mon–Sat, 9:00 AM – 7:00 PM</li>
                        </ul>
                    </div>
                    <div class="contact-card">
                        <h3>Send a Message</h3>
                        <form class="contact-form" id="contactForm">
                            <div class="form-row">
                                <div>
                                    <label for="cname">Your Name</label>
                                    <input id="cname" type="text" placeholder="John Doe" required>
                                </div>
                                <div>
                                    <label for="cemail">Email</label>
                                    <input id="cemail" type="email" placeholder="you@example.com" required>
                                </div>
                            </div>
                            <div>
                                <label for="cmsg">Message</label>
                                <textarea id="cmsg" placeholder="How can we help?" required></textarea>
                            </div>
                            <div style="margin-top:1rem;">
                                <button type="submit" class="btn btn-primary">Send Message</button>
                                <span id="contactStatus" style="margin-left:.75rem; font-size:.95rem;"></span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <section class="cta-section">
            <div class="cta-pattern"></div>
            <div class="container">
                <div class="cta-content">
                    <h2 class="cta-title">Ready to Transform Your Home?</h2>
                    <p class="cta-subtitle">Join thousands of satisfied customers who trust HomeAid for their home services</p>
                    <div class="cta-buttons">
                        <a href="customer/register.php" class="btn btn-white">Start Your First Booking</a>
                        <a href="provider/register.php" class="btn btn-outline" style="border-color: white; color: white;">Become a Provider</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="footer-content">
            <div class="logo" style="color: white;">HomeAid</div>
            <ul class="footer-links">
                <li><a href="#">Privacy Policy</a></li>
                <li><a href="#">Terms of Service</a></li>
                <li><a href="#">Support</a></li>
                <li><a href="#">Careers</a></li>
            </ul>
            <p style="color: rgba(255, 255, 255, 0.7);">© 2024 HomeAid. All rights reserved.</p>
        </div>
    </footer>

    <script>
    // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navbar scroll effect
        let lastScroll = 0;
        window.addEventListener('scroll', () => {
            const nav = document.querySelector('nav');
            const currentScroll = window.pageYOffset;
            
            if (currentScroll > 50) {
                nav.style.padding = '0.5rem 0';
                nav.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
            } else {
                nav.style.padding = '1rem 0';
                nav.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.05)';
            }
            
            lastScroll = currentScroll;
        });

        // Intersection Observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = 'fadeInUp 0.8s ease forwards';
                }
            });
        }, observerOptions);

        // Observe all cards
        document.querySelectorAll('.service-card, .stat-card, .step-card').forEach(el => {
            observer.observe(el);
        });

        // Add fadeInUp animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;
        document.head.appendChild(style);

        // Contact form submission
        const contactForm = document.getElementById('contactForm');
        if (contactForm) {
            const statusEl = document.getElementById('contactStatus');
            const setStatus = (text, color) => { if (statusEl) { statusEl.textContent = text; statusEl.style.color = color; } };
            contactForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                setStatus('Sending...', '#6b7280');
                const name = document.getElementById('cname').value.trim();
                const email = document.getElementById('cemail').value.trim();
                const message = document.getElementById('cmsg').value.trim();
                try {
                    const resp = await fetch('api/contact.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ name, email, message }).toString()
                    });
                    const data = await resp.json().catch(() => ({ ok: false, error: 'Unexpected response' }));
                    if (resp.ok && data.ok) {
                        setStatus('Message sent. We\'ll get back to you soon.', '#059669');
                        contactForm.reset();
                    } else {
                        setStatus(data.error || 'Failed to send. Try again later.', '#b91c1c');
                    }
                } catch (err) {
                    setStatus('Network error. Please try again.', '#b91c1c');
                }
            });
        }
    </script>
</body>
</html>