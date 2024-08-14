<?php
session_start();
require_once 'check_auth.php';

if (is_logged_in()) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Energy Diary - Fuel Your Day, Every Day</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        :root {
            --primary-blue: #0039A6;
            --primary-red: #D62828;
            --accent-yellow: #FFC600;
            --bg-light: #F8F8F8;
            --text-dark: #333333;
            --text-light: #FFFFFF;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background-color: var(--bg-light);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .hero {
            background-color: var(--primary-blue);
            color: var(--text-light);
            padding: 100px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 50%;
            background-color: var(--primary-red);
            transform: skew(-20deg);
            z-index: 1;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .hero h1 {
            font-size: 4rem;
            font-weight: 900;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        
        .hero p {
            font-size: 1.4rem;
            max-width: 800px;
            margin: 0 auto 40px;
        }
        
        .cta-button {
            display: inline-block;
            background-color: var(--accent-yellow);
            color: var(--primary-blue);
            padding: 15px 30px;
            border-radius: 0;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.2rem;
            text-transform: uppercase;
            transition: all 0.3s ease;
        }
        
        .cta-button:hover {
            background-color: var(--text-light);
            color: var(--primary-red);
        }
        
        .section {
            padding: 80px 0;
        }
        
        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 50px;
            color: var(--primary-blue);
            text-transform: uppercase;
            font-weight: 900;
        }
        
        .features {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }
        
        .feature {
            flex-basis: calc(33.333% - 20px);
            background-color: var(--text-light);
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-top: 5px solid var(--primary-red);
        }
        
        .feature:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 8px rgba(0,0,0,0.2);
        }
        
        .feature i {
            font-size: 3rem;
            color: var(--primary-red);
            margin-bottom: 20px;
        }
        
        .feature h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: var(--primary-blue);
            text-transform: uppercase;
        }
        
        .how-it-works {
            background-color: var(--primary-blue);
            color: var(--text-light);
        }
        
        .how-it-works .section-title {
            color: var(--text-light);
        }
        
        .step {
            display: flex;
            align-items: center;
            margin-bottom: 50px;
            background-color: rgba(255,255,255,0.1);
            padding: 20px;
        }
        
        .step-number {
            font-size: 4rem;
            font-weight: 900;
            color: var(--accent-yellow);
            margin-right: 30px;
        }
        
        .step-content h3 {
            font-size: 1.8rem;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .benefits {
            background-color: var(--primary-red);
            color: var(--text-light);
        }
        
        .benefits .section-title {
            color: var(--text-light);
        }
        
        .benefit {
            background-color: rgba(255,255,255,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .benefit h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            text-transform: uppercase;
            color: var(--accent-yellow);
        }
        
        .testimonials {
            text-align: center;
        }
        
        .testimonial {
            max-width: 800px;
            margin: 0 auto 50px;
            background-color: var(--text-light);
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .testimonial-text {
            font-size: 1.2rem;
            font-style: italic;
            margin-bottom: 20px;
        }
        
        .testimonial-author {
            font-weight: bold;
            color: var(--primary-blue);
        }
        
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 3rem;
            }
            
            .feature {
                flex-basis: 100%;
            }
            
            .step {
                flex-direction: column;
                text-align: center;
            }
            
            .step-number {
                margin-right: 0;
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <main>
        <section class="hero">
            <div class="container hero-content">
                <h1>Charge Your Life</h1>
                <p>Energy Diary: Your personal power station for tracking, analyzing, and boosting daily energy levels.</p>
                <a href="register.php" class="cta-button">Power Up Now</a>
            </div>
        </section>

        <section class="section">
            <div class="container">
                <h2 class="section-title">Energy Amplifiers</h2>
                <div class="features">
                    <div class="feature">
                        <i class="fas fa-bolt"></i>
                        <h3>Dynamic Tracking</h3>
                        <p>Monitor physical, mental, emotional, and motivational energy with precision.</p>
                    </div>
                    <div class="feature">
                        <i class="fas fa-brain"></i>
                        <h3>Insight Boost</h3>
                        <p>Gain powerful insights into your energy patterns and receive tailored recommendations.</p>
                    </div>
                    <div class="feature">
                        <i class="fas fa-rocket"></i>
                        <h3>Performance Surge</h3>
                        <p>Optimize your daily routine to maximize productivity and well-being.</p>
                    </div>
                    <div class="feature">
                        <i class="fas fa-chart-line"></i>
                        <h3>Progress Amplification</h3>
                        <p>Visualize your energy trends and celebrate improvements with detailed reports.</p>
                    </div>
                    <div class="feature">
                        <i class="fas fa-shield-alt"></i>
                        <h3>Data Fortress</h3>
                        <p>Your information is secure and private, never shared with third parties.</p>
                    </div>
                    <div class="feature">
                        <i class="fas fa-mobile-alt"></i>
                        <h3>Always Charged</h3>
                        <p>Access your Energy Diary anytime, anywhere, on any device.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="section how-it-works">
            <div class="container">
                <h2 class="section-title">Power-Up Process</h2>
                <div class="step">
                    <div class="step-number">01</div>
                    <div class="step-content">
                        <h3>Daily Energy Check</h3>
                        <p>Log your energy levels across four key dimensions in just one minute each day.</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">02</div>
                    <div class="step-content">
                        <h3>Amplify with Comments</h3>
                        <p>Add context to your energy levels, identifying factors that influence your daily performance.</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">03</div>
                    <div class="step-content">
                        <h3>Pattern Recognition</h3>
                        <p>Review energy trends and correlations between different aspects of your life.</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">04</div>
                    <div class="step-content">
                        <h3>Supercharge Your Life</h3>
                        <p>Implement changes based on insights to optimize your energy and performance.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="section benefits">
            <div class="container">
                <h2 class="section-title">Energy Transformation</h2>
                <div class="features">
                    <div class="benefit">
                        <h3>Productivity Surge</h3>
                        <p>Harness your peak energy times to supercharge your daily output.</p>
                    </div>
                    <div class="benefit">
                        <h3>Well-being Boost</h3>
                        <p>Unlock the connection between habits and energy for enhanced health and happiness.</p>
                    </div>
                    <div class="benefit">
                        <h3>Self-awareness Amplification</h3>
                        <p>Gain a turbo-charged understanding of your body and mind for informed lifestyle choices.</p>
                    </div>
                    <div class="benefit">
                        <h3>Goal Acceleration</h3>
                        <p>Align your energy management with personal and professional goals for rapid success.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="section testimonials">
            <div class="container">
                <h2 class="section-title">Energy Champions</h2>
                <div class="testimonial">
                    <p class="testimonial-text">"Energy Diary has turbocharged my life! I've discovered energy patterns I never knew existed, and my productivity has gone through the roof!"</p>
                    <p class="testimonial-author">- Alex K., Tech Entrepreneur</p>
                </div>
                <div class="testimonial">
                    <p class="testimonial-text">"As a high-performance athlete, managing my energy is crucial. This app has become my secret weapon for optimizing training and recovery."</p>
                    <p class="testimonial-author">- Samantha T., Professional Triathlete</p>
                </div>
                <a href="register.php" class="cta-button">Join the Energy Elite</a>
            </div>
        </section>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>