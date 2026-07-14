<?php 
require_once('db_config.php'); 
include('header.php'); 
?>

<style>
    .hero-section {
        width: 100%;
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 60px 5%;
        position: relative;
        background: url('https://images.unsplash.com/photo-1496442226666-8d4d0e62e6e9?w=1920&q=80') center/cover no-repeat;
        background-attachment: fixed;
    }
    
    /* Dark/light overlay using theme variable */
    .hero-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: var(--hero-overlay);
        z-index: 1;
    }
    
    .hero-content {
        position: relative;
        z-index: 2;
        max-width: 800px;
        animation: fadeInUp 1.2s ease;
    }
    
    @keyframes fadeInUp {
        0% {
            opacity: 0;
            transform: translateY(30px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .hero-content h1 {
        font-size: 4.5rem;
        font-weight: 800;
        letter-spacing: 3px;
        color: var(--text-primary);
        text-shadow: 0 4px 30px rgba(0,0,0,0.5);
        margin-bottom: 20px;
        line-height: 1.1;
    }
    
    .hero-content h1 span {
        color: var(--accent);
        position: relative;
    }
    
    .hero-content h1 span::after {
        content: '';
        position: absolute;
        bottom: 5px;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--accent);
        border-radius: 2px;
        width: 100%;
    }
    
    .hero-content p {
        font-size: 1.3rem;
        color: var(--text-secondary);
        margin-bottom: 35px;
        text-shadow: 0 2px 15px rgba(0,0,0,0.4);
        font-weight: 300;
        letter-spacing: 0.5px;
        line-height: 1.6;
    }
    
    .hero-button {
        display: inline-block;
        padding: 16px 45px;
        background: var(--accent);
        color: #fff;
        text-decoration: none;
        border-radius: 50px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        font-size: 14px;
        transition: all 0.4s ease;
        box-shadow: 0 8px 30px rgba(220, 53, 69, 0.35);
        border: 2px solid transparent;
    }
    
    .hero-button:hover {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 12px 40px rgba(220, 53, 69, 0.5);
        background: var(--accent-hover);
    }
    
    .hero-badge {
        display: inline-block;
        background: rgba(255, 255, 255, 0.08);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.12);
        padding: 6px 18px;
        border-radius: 50px;
        font-size: 12px;
        color: var(--text-secondary);
        margin-bottom: 20px;
        letter-spacing: 2px;
        text-transform: uppercase;
    }
    
    .hero-badge span {
        color: var(--accent);
        font-weight: 700;
    }
    
    .collection-showcase {
        text-align: center;
        padding: 100px 8%;
        background: var(--bg-primary);
    }
    
    .section-title {
        margin-bottom: 50px;
        font-size: 2.5rem;
        font-weight: 300;
        letter-spacing: 2px;
        color: var(--text-primary);
    }
    
    .nav-text-link {
        color: var(--text-primary);
        text-decoration: none;
        font-size: 1.5rem;
        font-weight: 300;
        transition: all 0.3s ease;
        padding: 10px 20px;
        border: 1px solid transparent;
    }
    
    .nav-text-link:hover {
        color: var(--accent);
        border-bottom: 2px solid var(--accent);
        padding-bottom: 8px;
    }
    
    @media (max-width: 768px) {
        .hero-content h1 {
            font-size: 2.8rem;
        }
        .hero-content p {
            font-size: 1rem;
        }
    }
</style>

<section class="hero-section">
    <div class="hero-content">
        <div class="hero-badge">✨ New Collection 2026</div>
        <h1>STYLE EACH <span>MOMENT</span></h1>
        <p>Affordable, daily fashion essentials designed for your lifestyle.</p>
        <a href="catalog.php" class="hero-button">Shop New Arrivals</a>
    </div>
</section>

<div class="collection-showcase">
    <h2 class="section-title">Explore Collections</h2>
    <div style="display: flex; justify-content: center; gap: 60px; flex-wrap: wrap;">
        <a href="catalog.php?category=Tops" class="nav-text-link">Tops</a>
        <a href="catalog.php?category=Bottoms" class="nav-text-link">Bottoms</a>
        <a href="catalog.php?category=Hoodies" class="nav-text-link">Hoodies</a>
        <a href="catalog.php?category=Shoes" class="nav-text-link">Shoes</a>
    </div>
</div>

<?php include('footer.php'); ?>