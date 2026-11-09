<?php
// index.php - Version Premium Style Airbnb
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';


// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Afficher la page de connexion pour les utilisateurs non connectés
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Logements Tunisie</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --airbnb: #FF385C;
            --airbnb-dark: #E31C5F;
            --airbnb-light: #FF7096;
            --black: #222222;
            --gray-dark: #717171;
            --gray: #DDDDDD;
            --gray-light: #F7F7F7;
            --white: #FFFFFF;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
            --shadow-md: 0 6px 16px rgba(0,0,0,0.12);
            --shadow-lg: 0 12px 28px rgba(0,0,0,0.15);
            --radius-sm: 12px;
            --radius-md: 16px;
            --radius-lg: 24px;
            --transition: all 0.25s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--white);
            background: #070816;
            min-height: 100vh;
        }

        /* Navigation temporaire pour la page de connexion */
        .nav-login {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: transparent;
            border-bottom: none;
            padding: 24px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 80px;
            box-shadow: none;
        }

        .nav-login .logo {
            font-family: 'Playfair Display', serif;
            font-size: 26px;
            font-weight: 700;
            color: var(--white);
            text-decoration: none;
        }

        .nav-login .nav-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .nav-login .nav-link {
            padding: 10px 16px;
            border-radius: 24px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            color: rgba(255,255,255,0.9);
            border: 1px solid rgba(255,255,255,0.25);
            background: rgba(255,255,255,0.08);
            transition: var(--transition);
        }

        .nav-login .nav-link:hover {
            border-color: var(--white);
            color: var(--white);
            background: rgba(255,255,255,0.14);
        }

        /* Hero Section pour la connexion */
        .hero-login {
            position: relative;
            min-height: 100vh;
            background: url('https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1920&q=80') center/cover no-repeat fixed;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            overflow: hidden;
            padding-top: 60px;
            padding-bottom: 60px;
        }

        .hero-login::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
        }

        .hero-login::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.15));
            pointer-events: none;
        }

        @keyframes slowZoom {
            0% { transform: scale(1); }
            100% { transform: scale(1.05); }
        }

        @keyframes slowZoom {
            0% { transform: scale(1); }
            100% { transform: scale(1.1); }
        }

        .login-content {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            max-width: 1200px;
            width: 100%;
            padding: 0 40px;
            align-items: center;
        }

        .login-left {
            color: white;
            text-align: center;
        }

        .login-left h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(48px, 8vw, 72px);
            font-weight: 700;
            margin-bottom: 24px;
            line-height: 1.1;
        }

        .login-left p {
            font-size: 20px;
            margin-bottom: 40px;
            opacity: 0.9;
            font-weight: 400;
        }

        .features-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
            text-align: left;
            max-width: 400px;
            margin: 0 auto;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-md);
            backdrop-filter: blur(10px);
        }

        .feature-item i {
            font-size: 24px;
            color: var(--airbnb);
        }

        .feature-item div h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .feature-item div p {
            font-size: 14px;
            opacity: 0.8;
        }

        .login-right {
            background: rgba(255,255,255,0.94);
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: var(--radius-lg);
            padding: 32px;
            box-shadow: 0 30px 90px rgba(0,0,0,0.18);
            max-width: 420px;
            width: 100%;
            backdrop-filter: blur(12px);
        }

        .login-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .login-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--black);
            margin-bottom: 6px;
        }

        .login-header p {
            color: var(--gray-dark);
            font-size: 14px;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .register-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            width: 100%;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        .form-group input {
            padding: 14px 16px;
        }

        .register-btn {
            padding: 12px 24px;
        }

        .divider {
            margin: 24px 0;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--black);
        }

        .form-group input {
            padding: 12px 14px;
            border: 2px solid var(--gray);
            border-radius: var(--radius-md);
            font-size: 14px;
            outline: none;
            transition: var(--transition);
            background: var(--white);
        }

        .form-group input:focus {
            border-color: var(--airbnb);
            box-shadow: 0 0 0 3px rgba(255, 56, 92, 0.1);
        }

        .login-btn {
            background: linear-gradient(135deg, var(--airbnb), var(--airbnb-light));
            color: var(--white);
            border: none;
            padding: 14px 20px;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 12px rgba(255, 56, 92, 0.2);
            margin-top: 6px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 56, 92, 0.3);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
            text-align: center;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--gray);
        }

        .divider span {
            padding: 0 12px;
            color: var(--gray-dark);
            font-size: 12px;
            font-weight: 500;
        }

        .register-link {
            text-align: center;
            padding-top: 16px;
            border-top: 1px solid var(--gray);
        }

        .register-link p {
            color: var(--gray-dark);
            margin-bottom: 12px;
            font-size: 12px;
        }

        .register-btn {
            background: var(--white);
            color: var(--airbnb);
            border: 2px solid var(--airbnb);
            padding: 10px 20px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            transition: var(--transition);
            display: inline-block;
        }

        .register-btn:hover {
            background: var(--airbnb);
            color: var(--white);
            transform: translateY(-1px);
        }

        /* Responsive */
        @media (max-width: 968px) {
            .login-content {
                grid-template-columns: 1fr;
                gap: 32px;
                text-align: center;
            }

            .login-left h1 {
                font-size: 44px;
            }

            .features-list {
                text-align: center;
            }

            .feature-item {
                justify-content: center;
                padding: 14px;
            }
        }

        @media (max-width: 640px) {
            .nav-login {
                padding: 0 16px;
            }

            .login-content {
                padding: 0 16px;
            }

            .login-right {
                padding: 24px 20px;
            }

            .login-left h1 {
                font-size: 32px;
            }

            .login-left p {
                font-size: 16px;
                margin-bottom: 20px;
            }

            .features-list {
                gap: 14px;
            }

            .register-grid {
                grid-template-columns: 1fr;
            }
        }

        .form-group input:focus {
            border-color: var(--airbnb);
            background: white;
            box-shadow: 0 0 0 3px rgba(255, 56, 92, 0.1);
        }

        .login-btn {
            background: linear-gradient(135deg, var(--airbnb), var(--airbnb-light));
            color: white;
            border: none;
            padding: 18px 24px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 10px;
            box-shadow: 0 4px 12px rgba(255, 56, 92, 0.2);
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 56, 92, 0.3);
        }

        .register-link {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e1e5e9;
        }

        .register-link p {
            color: var(--gray-dark);
            margin-bottom: 15px;
        }

        .register-btn {
            background: white;
            color: var(--airbnb);
            border: 2px solid var(--airbnb);
            padding: 14px 28px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
            display: inline-block;
        }

        .register-btn:hover {
            background: var(--airbnb);
            color: white;
            transform: translateY(-1px);
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-top: 50px;
            text-align: center;
        }

        .feature {
            padding: 20px;
        }

        .feature i {
            font-size: 32px;
            color: var(--airbnb);
            margin-bottom: 15px;
        }

        .feature h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: var(--black);
        }

        .feature p {
            color: var(--gray-dark);
            font-size: 14px;
        }

        @media (max-width: 640px) {
            .login-container {
                padding: 40px 30px;
            }
            .features {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }

        /* Modal amélioré pour la page de connexion */
        .modal-overlay {
          display: none;
          position: fixed;
          inset: 0;
          background: rgba(0,0,0,0.6);
          backdrop-filter: blur(4px);
          z-index: 2000;
          align-items: center;
          justify-content: center;
          padding: 20px;
        }

        .modal-overlay.show {
          display: flex;
          animation: modalFadeIn 0.3s ease-out;
        }

        @keyframes modalFadeIn {
          from { opacity: 0; }
          to { opacity: 1; }
        }

        .modal {
          background: white;
          border-radius: 24px;
          width: 100%;
          max-width: 480px;
          max-height: 90vh;
          overflow-y: auto;
          padding: 40px;
          position: relative;
          box-shadow: 0 20px 60px rgba(0,0,0,0.15);
          animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
          from {
            opacity: 0;
            transform: translateY(-20px) scale(0.95);
          }
          to {
            opacity: 1;
            transform: translateY(0) scale(1);
          }
        }

        .modal-close {
          position: absolute;
          top: 24px;
          right: 24px;
          background: #f5f5f5;
          border: none;
          width: 40px;
          height: 40px;
          border-radius: 50%;
          cursor: pointer;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 18px;
          color: #666;
          transition: all 0.2s ease;
        }

        .modal-close:hover {
          background: var(--airbnb);
          color: white;
          transform: rotate(90deg);
        }

        .modal h2 {
          font-family: 'Playfair Display', serif;
          font-size: 32px;
          font-weight: 700;
          margin-bottom: 8px;
          color: #222;
          text-align: center;
        }

        .modal h2::after {
          content: '';
          display: block;
          width: 60px;
          height: 3px;
          background: linear-gradient(90deg, var(--airbnb), var(--airbnb-light));
          margin: 12px auto 0;
          border-radius: 2px;
        }

        .modal .form-group { margin-bottom: 20px; }
        .modal .form-group label {
          display: block;
          font-size: 14px;
          font-weight: 600;
          margin-bottom: 8px;
          color: #222;
        }

        .modal .form-group input, .modal .form-group select {
          width: 100%;
          padding: 16px 20px;
          border: 2px solid #e1e5e9;
          border-radius: 12px;
          font-size: 16px;
          outline: none;
          transition: all 0.2s ease;
          background: #fafbfc;
        }

        .modal .form-group input:focus, .modal .form-group select:focus {
          border-color: var(--airbnb);
          background: white;
          box-shadow: 0 0 0 3px rgba(255, 56, 92, 0.1);
        }

        .btn-primary {
          width: 100%;
          padding: 18px 24px;
          background: linear-gradient(135deg, var(--airbnb), var(--airbnb-light));
          color: white;
          border: none;
          border-radius: 12px;
          font-size: 16px;
          font-weight: 600;
          cursor: pointer;
          transition: all 0.2s ease;
          margin-top: 8px;
          box-shadow: 0 4px 12px rgba(255, 56, 92, 0.2);
        }

        .btn-primary:hover {
          transform: translateY(-2px);
          box-shadow: 0 6px 20px rgba(255, 56, 92, 0.3);
        }

        .btn-primary:active {
          transform: translateY(0);
        }

        .switch-form {
          text-align: center;
          margin-top: 24px;
          font-size: 14px;
          color: #666;
        }

        .switch-form a {
          color: var(--airbnb);
          text-decoration: none;
          font-weight: 600;
          transition: color 0.2s ease;
        }

        .switch-form a:hover {
          color: var(--airbnb-dark);
          text-decoration: underline;
        }

        /* Account Types Selection améliorée */
        .account-types {
          display: flex;
          flex-direction: column;
          gap: 12px;
          margin-top: 8px;
        }

        .account-type-option {
          position: relative;
        }

        .account-type-option input[type="radio"] {
          position: absolute;
          opacity: 0;
          width: 0;
          height: 0;
        }

        .account-type-option label {
          display: flex;
          align-items: center;
          gap: 16px;
          padding: 20px;
          border: 2px solid #e1e5e9;
          border-radius: 16px;
          cursor: pointer;
          transition: all 0.3s ease;
          background: #fafbfc;
          position: relative;
          overflow: hidden;
        }

        .account-type-option label::before {
          content: '';
          position: absolute;
          top: 0;
          left: -100%;
          width: 100%;
          height: 100%;
          background: linear-gradient(90deg, transparent, rgba(255, 56, 92, 0.05), transparent);
          transition: left 0.5s ease;
        }

        .account-type-option input[type="radio"]:checked + label {
          border-color: var(--airbnb);
          background: linear-gradient(135deg, rgba(255, 56, 92, 0.05), rgba(255, 112, 150, 0.05));
          transform: translateY(-2px);
          box-shadow: 0 8px 25px rgba(255, 56, 92, 0.15);
        }

        .account-type-option input[type="radio"]:checked + label::before {
          left: 100%;
        }

        .account-type-option label:hover {
          border-color: var(--airbnb);
          background: rgba(255, 56, 92, 0.02);
          transform: translateY(-1px);
          box-shadow: 0 4px 15px rgba(255, 56, 92, 0.1);
        }

        .type-icon {
          width: 48px;
          height: 48px;
          border-radius: 50%;
          background: linear-gradient(135deg, #f5f5f5, #e1e5e9);
          display: flex;
          align-items: center;
          justify-content: center;
          color: #666;
          font-size: 18px;
          transition: all 0.3s ease;
          flex-shrink: 0;
          position: relative;
          z-index: 2;
        }

        .account-type-option input[type="radio"]:checked + label .type-icon {
          background: linear-gradient(135deg, var(--airbnb), var(--airbnb-light));
          color: white;
          transform: scale(1.1);
          box-shadow: 0 4px 12px rgba(255, 56, 92, 0.3);
        }

        .type-info {
          flex: 1;
          position: relative;
          z-index: 2;
        }

        .type-info strong {
          display: block;
          font-size: 16px;
          font-weight: 700;
          color: #222;
          margin-bottom: 4px;
        }

        .type-info small {
          font-size: 14px;
          color: #666;
          font-weight: 500;
          line-height: 1.4;
        }

        /* Styles pour les conteneurs de formulaires */
        .form-container {
          display: none;
          animation: fadeIn 0.3s ease-in-out;
        }

        .form-container.active {
          display: block;
        }

        @keyframes fadeIn {
          from { opacity: 0; transform: translateY(10px); }
          to { opacity: 1; transform: translateY(0); }
        }
    </style>
    </head>
    <body>

    <!-- Navigation temporaire -->
    <nav class="nav-login">
        <a href="index.php" class="logo"><i class="fa-solid fa-house-chimney"></i> Logements</a>
        <div class="nav-right">
        </div>
    </nav>

    <!-- Hero Section pour la connexion -->
    <section class="hero-login">
        <div class="login-content">
            <!-- Côté gauche : Présentation -->
            <div class="login-left">
                <h1>Découvrez la<br><span style="color: var(--airbnb);">Tunisie</span> autrement</h1>
                <p>Des logements uniques, sélectionnés avec soin pour des séjours inoubliables</p>

                <div class="features-list">
                    <div class="feature-item">
                        <i class="fa-solid fa-search"></i>
                        <div>
                            <h4>Recherche Facile</h4>
                            <p>Trouvez le logement parfait selon vos critères</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="fa-solid fa-shield-alt"></i>
                        <div>
                            <h4>Sécurisé</h4>
                            <p>Réservations sécurisées et paiement protégé</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="fa-solid fa-star"></i>
                        <div>
                            <h4>Qualité Garantie</h4>
                            <p>Logements vérifiés et avis clients</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Côté droit : Formulaire de connexion -->
            <div class="login-right">
                <!-- Formulaire de connexion -->
                <div id="loginForm" class="form-container active">
                    <div class="login-header">
                        <h2>Se connecter</h2>
                        <p>Connectez-vous à votre compte</p>
                    </div>

                    <form method="POST" action="api/login.php" class="login-form">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" required placeholder="votre@email.com">
                        </div>
                        <div class="form-group">
                            <label>Mot de passe</label>
                            <input type="password" name="password" required placeholder="Votre mot de passe">
                        </div>
                        <button type="submit" class="login-btn">Se connecter</button>
                    </form>

                    <div class="divider">
                        <span>ou</span>
                    </div>

                    <div class="register-link">
                        <p>Nouveau sur Logements ?</p>
                        <a href="#" onclick="showRegister()" class="register-btn">Créer un compte</a>
                    </div>
                </div>

                <!-- Formulaire d'inscription -->
                <div id="registerForm" class="form-container">
                    <div class="login-header">
                        <h2>S'inscrire</h2>
                        <p>Créez votre compte Logements</p>
                    </div>

                    <form method="POST" action="api/register.php" class="login-form">
                        <div class="register-grid">
                            <div class="form-group"><label>Prénom</label><input type="text" name="prenom" required></div>
                            <div class="form-group"><label>Nom</label><input type="text" name="nom" required></div>
                        </div>
                        <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
                        <div class="form-group"><label>Téléphone</label><input type="tel" name="telephone"></div>
                        <div class="form-group"><label>Mot de passe</label><input type="password" name="password" required></div>
                        <div class="form-group">
                            <label>Type de compte</label>
                            <div class="account-types">
                                <div class="account-type-option">
                                    <input type="radio" id="type_client" name="type_client" value="client" checked>
                                    <label for="type_client">
                                        <div class="type-icon"><i class="fa-solid fa-user"></i></div>
                                        <div class="type-info">
                                            <strong>Client (Voyageur)</strong>
                                            <small>Je cherche à louer un logement</small>
                                        </div>
                                    </label>
                                </div>
                                <div class="account-type-option">
                                    <input type="radio" id="type_proprietaire" name="type_client" value="proprietaire">
                                    <label for="type_proprietaire">
                                        <div class="type-icon"><i class="fa-solid fa-home"></i></div>
                                        <div class="type-info">
                                            <strong>Propriétaire</strong>
                                            <small>Je propose mon logement en location</small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn-primary">S'inscrire</button>
                    </form>

                    <div class="switch-form">Déjà un compte ? <a href="#" onclick="showLogin()">Se connecter</a></div>
                </div>
            </div>
        </div>
    </section>

    <script>
    function showRegister() {
        document.getElementById('loginForm').classList.remove('active');
        document.getElementById('registerForm').classList.add('active');
    }

    function showLogin() {
        document.getElementById('registerForm').classList.remove('active');
        document.getElementById('loginForm').classList.add('active');
    }

    // Gestion des erreurs de connexion
    <?php if(isset($_GET['error'])): ?>
        setTimeout(() => {
            alert('Email ou mot de passe incorrect');
        }, 500);
    <?php endif; ?>

    // Gestion du succès d'inscription
    <?php if(isset($_GET['register']) && $_GET['register'] === 'success'): ?>
        // Removed green message
    <?php endif; ?>
    </script>

    </body>
    </html>
    <?php
    exit(); // Arrêter l'exécution pour ne pas afficher le reste de la page
}

// Si l'utilisateur est connecté, continuer avec la page normale
require_once 'includes/header.php';

$type = $_GET['type'] ?? '';
$ville = $_GET['ville'] ?? '';
$prix_max = $_GET['prix_max'] ?? '';

/* Recherche « avancée » : ville ou budget — pas le seul filtre type (barre catégories) */
$isSearch = !empty($ville) || (!empty($prix_max) && is_numeric($prix_max));

$filtres = [];
if($type && $type != 'all') {
    if (in_array($type, ['mer', 'montagne', 'luxe'], true)) {
        $filtres['theme'] = $type;
    } else {
        $filtres['type'] = $type;
    }
}
if($ville) $filtres['ville'] = $ville;
if($prix_max && is_numeric($prix_max)) $filtres['prix_max'] = $prix_max;

$limit = $isSearch ? 100 : (($type && $type !== 'all') ? 48 : 12);
$logements = getLogements($filtres, $limit);
$stats = getStats();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Logements Tunisie - Location de vacances et appartements</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<style>
  :root {
    --airbnb: #FF385C;
    --airbnb-dark: #E31C5F;
    --airbnb-light: #FF7096;
    --black: #222222;
    --gray-dark: #717171;
    --gray: #DDDDDD;
    --gray-light: #F7F7F7;
    --white: #FFFFFF;
    --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
    --shadow-md: 0 6px 16px rgba(0,0,0,0.12);
    --shadow-lg: 0 12px 28px rgba(0,0,0,0.15);
    --radius-sm: 12px;
    --radius-md: 16px;
    --radius-lg: 24px;
    --transition: all 0.25s ease;
  }

  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  body {
    font-family: 'Inter', sans-serif;
    color: var(--black);
    background: var(--white);
  }

  body.search-active {
    background: var(--gray-light);
    color: var(--black);
  }

  body.search-active .section-premium,
  body.search-active .stats-premium,
  body.search-active .inspiration-grid,
  body.search-active .cta-banner {
    background: transparent;
  }

  body.search-active .section-premium {
    padding-top: 24px;
  }

  body.search-active .section-header,
  body.search-active .section-title-premium,
  body.search-active .section-link {
    color: var(--black);
  }

  body.search-active .section-link {
    color: var(--airbnb);
  }

  body.search-active .card-premium {
    background: var(--white);
    border: 1px solid var(--gray);
    box-shadow: var(--shadow-sm);
  }

  body.search-active .card-premium:hover {
    box-shadow: var(--shadow-md);
    border-color: rgba(255, 56, 92, 0.2);
  }

  body.search-active .card-info {
    background: transparent;
  }

  body.search-active .hero-premium {
    background: linear-gradient(135deg, #fff5f7 0%, #ffe7f1 40%, #fff 100%);
  }

  body.search-active .hero-premium::before {
    display: none;
  }

  body.search-active .hero-premium {
    min-height: auto;
    padding: 40px 0;
  }

  /* Custom Scrollbar */
  ::-webkit-scrollbar {
    width: 8px;
    height: 8px;
  }
  ::-webkit-scrollbar-track {
    background: var(--gray-light);
    border-radius: 10px;
  }
  ::-webkit-scrollbar-thumb {
    background: var(--gray-dark);
    border-radius: 10px;
  }
  ::-webkit-scrollbar-thumb:hover {
    background: var(--black);
  }

  /* Hero Section Premium */
  .hero-premium {
    position: relative;
    min-height: 85vh;
    background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
  }

  .hero-premium::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1920&q=80') center/cover;
    opacity: 0.3;
    animation: slowZoom 20s ease-out infinite;
  }

  .hero-premium.search-active {
    min-height: 100vh;
  }

  .hero-premium.search-active::before {
    opacity: 0.25;
  }

  @keyframes slowZoom {
    0% { transform: scale(1); }
    100% { transform: scale(1.1); }
  }

  .hero-content-premium {
    position: relative;
    z-index: 2;
    text-align: center;
    color: white;
    max-width: 800px;
    padding: 0 20px;
  }

  .hero-content-premium h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(48px, 8vw, 72px);
    font-weight: 700;
    margin-bottom: 24px;
    line-height: 1.1;
    text-shadow: 0 2px 20px rgba(0,0,0,0.3);
  }

  .hero-content-premium p {
    font-size: 18px;
    margin-bottom: 40px;
    opacity: 0.95;
    font-weight: 400;
  }

  /* Hero Search Bar */
  .hero-search {
    background: white;
    border-radius: 60px;
    padding: 8px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    box-shadow: var(--shadow-lg);
    max-width: 800px;
    margin: 0 auto;
  }

  .hero-search-item {
    flex: 1;
    min-width: 140px;
    padding: 14px 24px;
    border-radius: 48px;
    transition: var(--transition);
    background: white;
    position: relative;
  }

  .hero-search-item:hover {
    background: var(--gray-light);
  }

  .hero-search-label {
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
    color: var(--black);
    display: block;
  }

  .hero-search-input {
    font-size: 14px;
    color: var(--gray-dark);
    border: none;
    outline: none;
    width: 100%;
    background: transparent;
    font-family: 'Inter', sans-serif;
  }

  .hero-search-input:focus {
    color: var(--black);
  }

  /* Style spécifique pour les selects */
  .hero-search-input[type="text"],
  .hero-search-input[type="date"] {
    cursor: text;
  }

  .hero-search-input[type="text"]::placeholder,
  .hero-search-input[type="date"]:not(:valid)::before {
    color: var(--gray-dark);
    opacity: 0.7;
  }

  /* Style pour le select de destination */
  #heroDestination {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23717171' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 8px center;
    background-size: 14px;
    padding-right: 32px;
  }

  .hero-search-input option {
    color: var(--black);
    background: var(--white);
    padding: 8px 12px;
  }

  .hero-search-btn {
    background: var(--airbnb);
    color: white;
    border: none;
    border-radius: 48px;
    padding: 0 32px;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
  }

  .hero-search-btn:hover {
    background: var(--airbnb-dark);
    transform: scale(1.02);
  }

  /* Responsive Design */
  @media (max-width: 768px) {
    .hero-search {
      max-width: 100%;
      padding: 6px;
      gap: 6px;
    }

    .hero-search-item {
      min-width: 120px;
      padding: 12px 16px;
    }

    .hero-search-label {
      font-size: 11px;
      margin-bottom: 2px;
    }

    .hero-search-input {
      font-size: 13px;
    }

    #heroDestination {
      background-position: right 6px center;
      background-size: 12px;
      padding-right: 24px;
    }

    .hero-search-btn {
      padding: 0 24px;
      font-size: 14px;
    }
  }

  @media (max-width: 480px) {
    .hero-search {
      flex-direction: column;
      gap: 4px;
    }

    .hero-search-item {
      min-width: unset;
      width: 100%;
    }

    .hero-search-btn {
      width: 100%;
      padding: 14px;
      margin-top: 8px;
    }
  }

  /* Categories Section */
  .categories-premium {
    padding: 20px 40px;
    border-bottom: 1px solid var(--gray);
    background: white;
    position: sticky;
    top: 80px;
    z-index: 100;
    overflow-x: auto;
    white-space: nowrap;
    scrollbar-width: none;
  }

  .categories-premium::-webkit-scrollbar {
    display: none;
  }

  .categories-wrapper {
    display: inline-flex;
    gap: 32px;
  }

  .cat-premium {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 8px 0;
    cursor: pointer;
    opacity: 0.6;
    transition: var(--transition);
    border-bottom: 2px solid transparent;
  }

  .cat-premium:hover {
    opacity: 1;
  }

  .cat-premium.active {
    opacity: 1;
    border-bottom-color: var(--black);
  }

  .cat-premium i {
    font-size: 24px;
  }

  .cat-premium span {
    font-size: 12px;
    font-weight: 500;
  }

  /* Section Title */
  .section-premium {
    padding: 48px 40px;
  }

  .section-header {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    flex-wrap: wrap;
    margin-bottom: 32px;
  }

  .section-title-premium {
    font-family: 'Playfair Display', serif;
    font-size: 32px;
    font-weight: 600;
    letter-spacing: -0.5px;
  }

  .section-link {
    color: var(--airbnb);
    text-decoration: none;
    font-weight: 500;
    transition: var(--transition);
  }

  .section-link:hover {
    text-decoration: underline;
  }

  /* Grid Layout */
  .grid-premium {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 28px;
  }

  /* Card Premium — style overlay comme les cartes inspiration */
  .card-premium {
    position: relative;
    cursor: pointer;
    transition: var(--transition);
    border-radius: var(--radius-md);
    overflow: hidden;
    aspect-ratio: 1/1;
  }

  .card-premium:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
  }

  .card-image-wrapper {
    position: absolute;
    inset: 0;
    overflow: hidden;
  }

  .card-image-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
  }

  .card-premium:hover .card-image-wrapper img {
    transform: scale(1.05);
  }

  .card-fav {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255,255,255,0.9);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
    z-index: 3;
  }

  .card-fav:hover {
    transform: scale(1.1);
    background: white;
  }

  .card-fav i {
    font-size: 18px;
    color: var(--black);
  }

  .card-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    box-shadow: var(--shadow-sm);
    z-index: 3;
  }

  .card-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 40px 20px 18px;
    background: linear-gradient(transparent, rgba(0,0,0,0.65));
    color: white;
    z-index: 2;
  }

  .card-title {
    font-weight: 600;
    font-size: 17px;
    line-height: 1.35;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin-bottom: 4px;
    color: white;
  }

  .card-type {
    font-size: 12px;
    font-weight: 600;
    color: rgba(255,255,255,0.85);
    margin-bottom: 4px;
    letter-spacing: 0.02em;
  }

  .card-location {
    font-size: 13px;
    color: rgba(255,255,255,0.8);
    margin-bottom: 6px;
  }

  .card-location i {
    color: rgba(255,255,255,0.8);
  }

  .card-price {
    font-size: 15px;
    color: white;
  }

  .card-price strong {
    font-weight: 700;
    color: white;
  }

  .card-price span {
    color: rgba(255,255,255,0.8);
    font-size: 13px;
  }

  /* Stats Section */
  .stats-premium {
    background: var(--gray-light);
    padding: 64px 40px;
    margin: 20px 40px;
    border-radius: var(--radius-lg);
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 32px;
    text-align: center;
  }

  .stat-premium h3 {
    font-family: 'Playfair Display', serif;
    font-size: 44px;
    font-weight: 700;
    color: var(--airbnb);
    margin-bottom: 8px;
  }

  .stat-premium p {
    color: var(--gray-dark);
    font-size: 14px;
  }

  /* Inspiration Section */
  .inspiration-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-top: 32px;
  }

  .inspiration-card {
    position: relative;
    border-radius: var(--radius-md);
    overflow: hidden;
    aspect-ratio: 1/1;
    cursor: pointer;
  }

  .inspiration-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
  }

  .inspiration-card:hover img {
    transform: scale(1.05);
  }

  .inspiration-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 24px;
    background: linear-gradient(transparent, rgba(0,0,0,0.6));
    color: white;
  }

  .inspiration-overlay h4 {
    font-size: 20px;
    font-weight: 600;
  }

  /* CTA Banner */
  .cta-banner {
    background: linear-gradient(135deg, var(--airbnb), var(--airbnb-dark));
    margin: 20px 40px 40px;
    border-radius: var(--radius-lg);
    padding: 56px 48px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 24px;
    color: white;
  }

  .cta-banner h2 {
    font-family: 'Playfair Display', serif;
    font-size: 36px;
    margin-bottom: 12px;
  }

  .cta-banner p {
    opacity: 0.9;
    font-size: 16px;
  }

  .cta-btn {
    background: white;
    color: var(--airbnb);
    padding: 14px 32px;
    border-radius: 48px;
    text-decoration: none;
    font-weight: 600;
    transition: var(--transition);
    display: inline-block;
  }

  .cta-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
  }

  /* Responsive */
  @media (max-width: 968px) {
    .section-premium { padding: 32px 20px; }
    .stats-premium { margin: 20px; padding: 40px 20px; }
    .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .inspiration-grid { grid-template-columns: repeat(2, 1fr); }
    .cta-banner { margin: 20px; padding: 32px 24px; flex-direction: column; text-align: center; }
    .cta-banner h2 { font-size: 28px; }
    .hero-search { flex-direction: column; border-radius: 24px; background: transparent; box-shadow: none; }
    .hero-search-item { background: white; border-radius: 48px; }
    .hero-search-btn { padding: 16px; justify-content: center; }
  }

  @media (max-width: 640px) {
    .grid-premium { grid-template-columns: 1fr; }
    .inspiration-grid { grid-template-columns: 1fr; }
  }

  /* Animation fade in */
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

  .animate-fade {
    animation: fadeInUp 0.6s ease-out;
  }

  #homeListingsAnchor {
    scroll-margin-top: 140px;
  }

  .grid-premium.listings-grid-loading {
    opacity: 0.45;
    pointer-events: none;
    transition: opacity 0.2s ease;
  }

  /* Page résultats — alignée sur hero-search & cartes premium */
  .search-page-panel {
    max-width: 1120px;
    margin: 0 auto 40px;
  }

  body.search-active .section-premium > .section-header {
    max-width: 1120px;
    margin-left: auto;
    margin-right: auto;
  }

  body.search-active .section-premium .grid-premium {
    max-width: 1120px;
    margin-left: auto;
    margin-right: auto;
  }

  .search-panel-card {
    background: var(--white);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    border: 1px solid var(--gray);
    padding: 28px 32px 32px;
    position: relative;
    overflow: hidden;
  }

  .search-panel-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--airbnb), var(--airbnb-light));
    border-radius: var(--radius-lg) var(--radius-lg) 0 0;
  }

  .search-panel-head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px 24px;
    margin-bottom: 24px;
    padding-top: 4px;
  }

  .search-panel-kicker {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    color: var(--airbnb);
    margin-bottom: 6px;
  }

  .search-results-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(22px, 4vw, 30px);
    font-weight: 600;
    color: var(--black);
    letter-spacing: -0.4px;
    line-height: 1.25;
  }

  .search-results-title .highlight {
    color: var(--airbnb);
    font-weight: 700;
  }

  .search-panel-meta {
    text-align: right;
  }

  .search-results-count {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--gray-dark);
    font-size: 14px;
    font-weight: 500;
  }

  .search-results-count strong {
    color: var(--black);
    font-weight: 700;
  }

  .search-panel-card .hero-search {
    margin-top: 0;
    max-width: none;
  }

  .search-panel-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid var(--gray);
  }

  .search-filter-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
  }

  .search-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: var(--gray-light);
    border: 1px solid var(--gray);
    border-radius: 999px;
    font-size: 13px;
    color: var(--black);
  }

  .search-chip i {
    color: var(--airbnb);
    font-size: 12px;
  }

  .refine-reset {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 22px;
    background: var(--white);
    color: var(--airbnb);
    border: 1px solid var(--gray);
    border-radius: 48px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
  }

  .refine-reset:hover {
    border-color: var(--airbnb);
    background: rgba(255, 56, 92, 0.06);
    box-shadow: var(--shadow-md);
  }

  .search-empty {
    grid-column: 1 / -1;
    text-align: center;
    padding: 56px 32px;
    background: var(--white);
    border: 1px dashed var(--gray);
    border-radius: var(--radius-lg);
    color: var(--gray-dark);
  }

  .search-empty-icon {
    width: 88px;
    height: 88px;
    margin: 0 auto 20px;
    border-radius: 50%;
    background: linear-gradient(145deg, var(--gray-light), var(--white));
    border: 1px solid var(--gray);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--airbnb);
    font-size: 32px;
    box-shadow: var(--shadow-sm);
  }

  .search-empty h3 {
    font-family: 'Playfair Display', serif;
    font-size: 22px;
    color: var(--black);
    margin-bottom: 8px;
    font-weight: 600;
  }

  .search-empty p {
    font-size: 15px;
    margin-bottom: 20px;
    max-width: 360px;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.5;
  }

  .search-empty .refine-reset {
    margin-top: 4px;
  }

  @media (max-width: 768px) {
    .search-panel-card {
      padding: 22px 20px 24px;
    }

    .search-panel-meta {
      text-align: left;
      width: 100%;
    }

    .search-panel-actions {
      flex-direction: column;
      align-items: stretch;
    }

    .refine-reset {
      width: 100%;
    }
  }

  /* Welcome mode for login success */
  .welcome-mode h1,
  .welcome-mode p {
    opacity: 0;
  }
</style>
</head>
<body<?php echo $isSearch ? ' class="search-active"' : ''; ?>>

<?php if(!$isSearch): ?>
<!-- Hero Section Premium -->
<section class="hero-premium">
  <div class="hero-content-premium<?php echo isset($_GET['success']) && $_GET['success'] == '1' ? ' welcome-mode' : ''; ?>">
    <h1>Découvrez la<br><span style="color: var(--airbnb);">Tunisie</span> autrement</h1>
    <p>Des logements uniques, sélectionnés avec soin pour des séjours inoubliables</p>
    
    <form method="GET" action="index.php" class="hero-search">
      <div class="hero-search-item">
        <div class="hero-search-label">DESTINATION</div>
        <select class="hero-search-input" name="ville" id="heroDestination">
          <option value="">Où allez-vous ?</option>
          <option value="Tunis" <?php echo $ville === 'Tunis' ? 'selected' : ''; ?>>Tunis</option>
          <option value="Ariana" <?php echo $ville === 'Ariana' ? 'selected' : ''; ?>>Ariana</option>
          <option value="Ben Arous" <?php echo $ville === 'Ben Arous' ? 'selected' : ''; ?>>Ben Arous</option>
          <option value="La Manouba" <?php echo $ville === 'La Manouba' ? 'selected' : ''; ?>>La Manouba</option>
          <option value="Nabeul" <?php echo $ville === 'Nabeul' ? 'selected' : ''; ?>>Nabeul</option>
          <option value="Zaghouan" <?php echo $ville === 'Zaghouan' ? 'selected' : ''; ?>>Zaghouan</option>
          <option value="Bizerte" <?php echo $ville === 'Bizerte' ? 'selected' : ''; ?>>Bizerte</option>
          <option value="Béja" <?php echo $ville === 'Béja' ? 'selected' : ''; ?>>Béja</option>
          <option value="Jendouba" <?php echo $ville === 'Jendouba' ? 'selected' : ''; ?>>Jendouba</option>
          <option value="Le Kef" <?php echo $ville === 'Le Kef' ? 'selected' : ''; ?>>Le Kef</option>
          <option value="Siliana" <?php echo $ville === 'Siliana' ? 'selected' : ''; ?>>Siliana</option>
          <option value="Kairouan" <?php echo $ville === 'Kairouan' ? 'selected' : ''; ?>>Kairouan</option>
          <option value="Kasserine" <?php echo $ville === 'Kasserine' ? 'selected' : ''; ?>>Kasserine</option>
          <option value="Sidi Bouzid" <?php echo $ville === 'Sidi Bouzid' ? 'selected' : ''; ?>>Sidi Bouzid</option>
          <option value="Sousse" <?php echo $ville === 'Sousse' ? 'selected' : ''; ?>>Sousse</option>
          <option value="Monastir" <?php echo $ville === 'Monastir' ? 'selected' : ''; ?>>Monastir</option>
          <option value="Mahdia" <?php echo $ville === 'Mahdia' ? 'selected' : ''; ?>>Mahdia</option>
          <option value="Sfax" <?php echo $ville === 'Sfax' ? 'selected' : ''; ?>>Sfax</option>
          <option value="Gafsa" <?php echo $ville === 'Gafsa' ? 'selected' : ''; ?>>Gafsa</option>
          <option value="Tozeur" <?php echo $ville === 'Tozeur' ? 'selected' : ''; ?>>Tozeur</option>
          <option value="Kebili" <?php echo $ville === 'Kebili' ? 'selected' : ''; ?>>Kebili</option>
          <option value="Gabès" <?php echo $ville === 'Gabès' ? 'selected' : ''; ?>>Gabès</option>
          <option value="Medenine" <?php echo $ville === 'Medenine' ? 'selected' : ''; ?>>Medenine</option>
          <option value="Tataouine" <?php echo $ville === 'Tataouine' ? 'selected' : ''; ?>>Tataouine</option>
        </select>
      </div>
      <div class="hero-search-item">
        <div class="hero-search-label">TYPE</div>
        <select class="hero-search-input" name="type" id="heroType">
          <option value="" <?php echo $type === '' ? 'selected' : ''; ?>>Tous les logements</option>
          <option value="villa" <?php echo $type === 'villa' ? 'selected' : ''; ?>>Villa</option>
          <option value="appartement" <?php echo $type === 'appartement' ? 'selected' : ''; ?>>Appartement</option>
          <option value="studio" <?php echo $type === 'studio' ? 'selected' : ''; ?>>Studio</option>
          <option value="maison" <?php echo $type === 'maison' ? 'selected' : ''; ?>>Maison</option>
        </select>
      </div>
      <div class="hero-search-item">
        <div class="hero-search-label">PRIX MAX</div>
        <input type="number" min="0" step="10" class="hero-search-input" name="prix_max" id="heroPrixMax" placeholder="ex: 500" value="<?php echo htmlspecialchars($prix_max); ?>">
      </div>
      <button type="submit" class="hero-search-btn">
        <i class="fa-solid fa-magnifying-glass"></i> Rechercher
      </button>
    </form>
  </div>
</section>
<?php endif; ?>

<?php if(!$isSearch): ?>
<!-- Categories Premium -->
<div class="categories-premium">
  <div class="categories-wrapper">
    <div class="cat-premium <?php echo empty($type) ? 'active' : ''; ?>" data-filter="all" onclick="filterCat(event, this, 'all')" role="button" tabindex="0" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();filterCat(event,this,'all');}">
      <i class="fa-solid fa-border-all"></i>
      <span>Tous</span>
    </div>
    <div class="cat-premium <?php echo $type == 'villa' ? 'active' : ''; ?>" data-filter="villa" onclick="filterCat(event, this, 'villa')" role="button" tabindex="0" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();filterCat(event,this,'villa');}">
      <i class="fa-solid fa-tree"></i>
      <span>Villas</span>
    </div>
    <div class="cat-premium <?php echo $type == 'appartement' ? 'active' : ''; ?>" data-filter="appartement" onclick="filterCat(event, this, 'appartement')" role="button" tabindex="0" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();filterCat(event,this,'appartement');}">
      <i class="fa-solid fa-building"></i>
      <span>Appartements</span>
    </div>
    <div class="cat-premium <?php echo $type == 'studio' ? 'active' : ''; ?>" data-filter="studio" onclick="filterCat(event, this, 'studio')" role="button" tabindex="0" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();filterCat(event,this,'studio');}">
      <i class="fa-solid fa-bed"></i>
      <span>Studios</span>
    </div>
    <div class="cat-premium <?php echo $type == 'maison' ? 'active' : ''; ?>" data-filter="maison" onclick="filterCat(event, this, 'maison')" role="button" tabindex="0" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();filterCat(event,this,'maison');}">
      <i class="fa-solid fa-home"></i>
      <span>Maisons</span>
    </div>
    <div class="cat-premium <?php echo $type == 'mer' ? 'active' : ''; ?>" data-filter="mer" onclick="filterCat(event, this, 'mer')" role="button" tabindex="0" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();filterCat(event,this,'mer');}">
      <i class="fa-solid fa-water"></i>
      <span>Bord de mer</span>
    </div>
    <div class="cat-premium <?php echo $type == 'montagne' ? 'active' : ''; ?>" data-filter="montagne" onclick="filterCat(event, this, 'montagne')" role="button" tabindex="0" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();filterCat(event,this,'montagne');}">
      <i class="fa-solid fa-mountain"></i>
      <span>Montagne</span>
    </div>
    <div class="cat-premium <?php echo $type == 'luxe' ? 'active' : ''; ?>" data-filter="luxe" onclick="filterCat(event, this, 'luxe')" role="button" tabindex="0" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();filterCat(event,this,'luxe');}">
      <i class="fa-solid fa-gem"></i>
      <span>Luxe</span>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Logements Section -->
<section class="section-premium">
  <?php if($isSearch): ?>
  <div class="search-page-panel">
    <div class="search-panel-card">
      <div class="search-panel-head">
        <div>
          <p class="search-panel-kicker">Résultats de recherche</p>
          <h1 class="search-results-title">
            <?php
              $criteria = [];
              if ($ville) {
                  $criteria[] = "<span class='highlight'>" . htmlspecialchars($ville) . "</span>";
              }
              if ($type) {
                  $criteria[] = "<span class='highlight'>" . ucfirst(htmlspecialchars($type)) . "s</span>";
              }
              if ($prix_max) {
                  $criteria[] = "<span class='highlight'>max. " . htmlspecialchars($prix_max) . " TND / nuit</span>";
              }
              if (!empty($criteria)) {
                  echo "Logements à " . implode(' · ', $criteria);
              } else {
                  echo "Tous les <span class='highlight'>logements</span> disponibles";
              }
            ?>
          </h1>
        </div>
        <div class="search-panel-meta">
          <p class="search-results-count">
            <strong><?php echo count($logements); ?></strong>
            logement<?php echo count($logements) > 1 ? 's' : ''; ?> trouvé<?php echo count($logements) > 1 ? 's' : ''; ?>
          </p>
        </div>
      </div>

      <form method="GET" action="index.php" class="hero-search" id="searchResultsForm">
        <div class="hero-search-item">
          <div class="hero-search-label">DESTINATION</div>
          <select class="hero-search-input" name="ville" id="searchResultDestination" aria-label="Destination">
            <option value="">Où allez-vous ?</option>
            <option value="Tunis" <?php echo $ville === 'Tunis' ? 'selected' : ''; ?>>Tunis</option>
            <option value="Ariana" <?php echo $ville === 'Ariana' ? 'selected' : ''; ?>>Ariana</option>
            <option value="Ben Arous" <?php echo $ville === 'Ben Arous' ? 'selected' : ''; ?>>Ben Arous</option>
            <option value="La Manouba" <?php echo $ville === 'La Manouba' ? 'selected' : ''; ?>>La Manouba</option>
            <option value="Nabeul" <?php echo $ville === 'Nabeul' ? 'selected' : ''; ?>>Nabeul</option>
            <option value="Zaghouan" <?php echo $ville === 'Zaghouan' ? 'selected' : ''; ?>>Zaghouan</option>
            <option value="Bizerte" <?php echo $ville === 'Bizerte' ? 'selected' : ''; ?>>Bizerte</option>
            <option value="Béja" <?php echo $ville === 'Béja' ? 'selected' : ''; ?>>Béja</option>
            <option value="Jendouba" <?php echo $ville === 'Jendouba' ? 'selected' : ''; ?>>Jendouba</option>
            <option value="Le Kef" <?php echo $ville === 'Le Kef' ? 'selected' : ''; ?>>Le Kef</option>
            <option value="Siliana" <?php echo $ville === 'Siliana' ? 'selected' : ''; ?>>Siliana</option>
            <option value="Kairouan" <?php echo $ville === 'Kairouan' ? 'selected' : ''; ?>>Kairouan</option>
            <option value="Kasserine" <?php echo $ville === 'Kasserine' ? 'selected' : ''; ?>>Kasserine</option>
            <option value="Sidi Bouzid" <?php echo $ville === 'Sidi Bouzid' ? 'selected' : ''; ?>>Sidi Bouzid</option>
            <option value="Sousse" <?php echo $ville === 'Sousse' ? 'selected' : ''; ?>>Sousse</option>
            <option value="Monastir" <?php echo $ville === 'Monastir' ? 'selected' : ''; ?>>Monastir</option>
            <option value="Mahdia" <?php echo $ville === 'Mahdia' ? 'selected' : ''; ?>>Mahdia</option>
            <option value="Sfax" <?php echo $ville === 'Sfax' ? 'selected' : ''; ?>>Sfax</option>
            <option value="Gafsa" <?php echo $ville === 'Gafsa' ? 'selected' : ''; ?>>Gafsa</option>
            <option value="Tozeur" <?php echo $ville === 'Tozeur' ? 'selected' : ''; ?>>Tozeur</option>
            <option value="Kebili" <?php echo $ville === 'Kebili' ? 'selected' : ''; ?>>Kebili</option>
            <option value="Gabès" <?php echo $ville === 'Gabès' ? 'selected' : ''; ?>>Gabès</option>
            <option value="Medenine" <?php echo $ville === 'Medenine' ? 'selected' : ''; ?>>Medenine</option>
            <option value="Tataouine" <?php echo $ville === 'Tataouine' ? 'selected' : ''; ?>>Tataouine</option>
          </select>
        </div>
        <div class="hero-search-item">
          <div class="hero-search-label">TYPE</div>
          <select class="hero-search-input" name="type" id="searchResultType" aria-label="Type de logement">
            <option value="" <?php echo $type === '' ? 'selected' : ''; ?>>Tous les logements</option>
            <option value="villa" <?php echo $type === 'villa' ? 'selected' : ''; ?>>Villa</option>
            <option value="appartement" <?php echo $type === 'appartement' ? 'selected' : ''; ?>>Appartement</option>
            <option value="studio" <?php echo $type === 'studio' ? 'selected' : ''; ?>>Studio</option>
            <option value="maison" <?php echo $type === 'maison' ? 'selected' : ''; ?>>Maison</option>
          </select>
        </div>
        <div class="hero-search-item">
          <div class="hero-search-label">PRIX MAX</div>
          <input type="number" min="0" step="10" class="hero-search-input" name="prix_max" id="searchResultPrixMax" placeholder="ex: 500" value="<?php echo htmlspecialchars($prix_max); ?>" aria-label="Prix maximum par nuit">
        </div>
        <button type="submit" class="hero-search-btn">
          <i class="fa-solid fa-magnifying-glass"></i> Actualiser
        </button>
      </form>

      <div class="search-panel-actions">
        <div class="search-filter-chips">
          <?php if ($ville): ?>
            <span class="search-chip"><i class="fa-solid fa-location-dot"></i><?php echo htmlspecialchars($ville); ?></span>
          <?php endif; ?>
          <?php if ($type): ?>
            <span class="search-chip"><i class="fa-solid fa-house"></i><?php echo ucfirst(htmlspecialchars($type)); ?></span>
          <?php endif; ?>
          <?php if ($prix_max !== '' && $prix_max !== null): ?>
            <span class="search-chip"><i class="fa-solid fa-tag"></i>≤ <?php echo htmlspecialchars($prix_max); ?> TND</span>
          <?php endif; ?>
          <?php if (!$ville && !$type && ($prix_max === '' || $prix_max === null)): ?>
            <span class="search-chip"><i class="fa-solid fa-layer-group"></i> Aucun filtre actif</span>
          <?php endif; ?>
        </div>
        <a href="index.php" class="refine-reset"><i class="fa-solid fa-arrow-left"></i> Retour à l’accueil</a>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if(!$isSearch): ?>
  <div class="section-header" id="homeListingsAnchor">
    <h2 class="section-title-premium">Hébergements populaires</h2>
    <a href="index.php" class="section-link" onclick="return voirToutLogements(event)">Voir tout <i class="fa-solid fa-arrow-right"></i></a>
  </div>
  <?php endif; ?>

  <?php if($isSearch): ?>
  <div class="section-header" style="margin-bottom: 24px;">
    <h2 class="section-title-premium">Annonces correspondantes</h2>
  </div>
  <?php endif; ?>

  <div class="grid-premium animate-fade" id="listingsGrid">
    <?php if(empty($logements)): ?>
      <?php if($isSearch): ?>
      <div class="search-empty">
        <div class="search-empty-icon" aria-hidden="true">
          <i class="fa-solid fa-magnifying-glass"></i>
        </div>
        <h3>Aucun logement ne correspond</h3>
        <p>Élargissez la destination, changez le type ou augmentez le budget, puis actualisez la recherche.</p>
        <a href="index.php" class="refine-reset"><i class="fa-solid fa-house"></i> Voir tous les logements</a>
      </div>
      <?php else: ?>
      <div class="search-empty">
        <div class="search-empty-icon" aria-hidden="true">
          <i class="fa-solid fa-house-chimney"></i>
        </div>
        <h3>Aucun logement pour le moment</h3>
        <p>Revenez bientôt : de nouvelles annonces sont ajoutées régulièrement.</p>
      </div>
      <?php endif; ?>
    <?php else: ?>
      <?php foreach($logements as $l): 
    // Récupérer la photo principale du logement
    $sql_photo = "SELECT url FROM photo WHERE id_logement = :id_logement AND est_principale = 1 LIMIT 1";
    $stmt_photo = Database::execute($sql_photo, [':id_logement' => $l['id_logement']]);
    $photo = $stmt_photo->fetch();
    
    // Si pas de photo principale, prendre n'importe quelle photo
    if (!$photo) {
        $sql_photo = "SELECT url FROM photo WHERE id_logement = :id_logement LIMIT 1";
        $stmt_photo = Database::execute($sql_photo, [':id_logement' => $l['id_logement']]);
        $photo = $stmt_photo->fetch();
    }
    
    // Image par défaut si aucune photo n'existe
    $imageUrl = $photo ? $photo['url'] : 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600&q=80';
?>
<div class="card-premium" onclick="window.location.href='logement-detail.php?id=<?php echo $l['id_logement']; ?>'">
    <div class="card-image-wrapper">
        <img src="<?php echo $imageUrl; ?>" alt="<?php echo htmlspecialchars($l['titre']); ?>" loading="lazy">
    </div>
    <button class="card-fav" onclick="event.stopPropagation(); toggleFavori(this, <?php echo $l['id_logement']; ?>)">
        <i class="fa-regular fa-heart"></i>
    </button>
    <?php if($l['prix_nuit'] < 150): ?>
        <span class="card-badge">🔥 Prix doux</span>
    <?php endif; ?>
    <div class="card-info">
        <div class="card-title"><?php echo htmlspecialchars($l['titre']); ?></div>
        <div class="card-type"><?php echo htmlspecialchars(typeLogementLabel($l['type'] ?? '')); ?></div>
        <div class="card-location">
            <i class="fa-solid fa-location-dot" style="font-size: 12px; margin-right: 4px;"></i>
            <?php echo htmlspecialchars($l['region_ville']); ?>
        </div>
        <div class="card-price">
            <strong><?php echo number_format($l['prix_nuit'], 0); ?> TND</strong> <span>/ nuit</span>
        </div>
    </div>
</div>
<?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>
<?php if(!$isSearch): ?>
<!-- Stats Section -->
<div class="stats-premium">
  <div class="stats-grid">
    <div class="stat-premium">
      <h3><?php echo number_format($stats['logements']); ?>+</h3>
      <p>Logements disponibles</p>
    </div>
    <div class="stat-premium">
      <h3><?php echo number_format($stats['clients']); ?>+</h3>
      <p>Voyageurs satisfaits</p>
    </div>
    <div class="stat-premium">
      <h3>24</h3>
      <p>Gouvernorats</p>
    </div>
    <div class="stat-premium">
      <h3>4.92 ★</h3>
      <p>Note moyenne</p>
    </div>
  </div>
</div>

<!-- Inspiration Section -->
<section class="section-premium">
  <div class="section-header">
    <h2 class="section-title-premium">Inspiration pour votre séjour</h2>
  </div>
  <div class="inspiration-grid">
    <div class="inspiration-card" onclick="window.location.href='index.php?ville=Gammarth'">
      <img src="uploads/logements/gammarth.jpg" alt="Gammarth">
      <div class="inspiration-overlay">
        <h4>Gammarth</h4>
        <p>À partir de 120 TND/nuit</p>
      </div>
    </div>

    <div class="inspiration-card" onclick="window.location.href='index.php?ville=Sidi Bou Said'">
      <img src="uploads/logements/sidibou.webp" alt="Sidi Bou Said">
      <div class="inspiration-overlay">
        <h4>Sidi Bou Saïd</h4>
        <p>À partir de 180 TND/nuit</p>
      </div>
    </div>

    <div class="inspiration-card" onclick="window.location.href='index.php?ville=Belvédère'">
      <img src="uploads/logements/belvidere.jpg" alt="Belvédère">
      <div class="inspiration-overlay">
        <h4>Belvédère</h4>
        <p>À partir de 150 TND/nuit</p>
      </div>
    </div>

    <div class="inspiration-card" onclick="window.location.href='index.php?ville=Kerkennah'">
      <img src="uploads/logements/kerkennah.avif" alt="Kerkennah">
      <div class="inspiration-overlay">
        <h4>Kerkennah</h4>
        <p>À partir de 100 TND/nuit</p>
      </div>
    </div>
  </div>
</section>
<!-- CTA Banner -->
<div class="cta-banner">
  <div>
    <h2>Chercher une correspondance</h2>
    <p>Échangez avec les propriétaires et trouvez le logement idéal</p>
  </div>
  <a href="correspondances.php" class="cta-btn">Commencer <i class="fa-solid fa-arrow-right"></i></a>
</div>
<?php endif; ?>

<script>
function searchHero() {
  const destination = document.getElementById('heroDestination').value;
  if(destination) {
    window.location.href = 'index.php?ville=' + encodeURIComponent(destination);
  }
}

var homeCategoryAjax = <?php echo $isSearch ? 'false' : 'true'; ?>;

function labelTypeLogement(t) {
  if (!t) return 'Logement';
  var map = { villa: 'Villa', appartement: 'Appartement', studio: 'Studio', maison: 'Maison', loft: 'Loft', autre: 'Autre' };
  var k = String(t).toLowerCase();
  return map[k] || (t.charAt(0).toUpperCase() + t.slice(1).toLowerCase());
}

function voirToutLogements(e) {
  if (typeof homeCategoryAjax === 'undefined' || !homeCategoryAjax) {
    return true;
  }
  e.preventDefault();
  var el = document.querySelector('.cat-premium[data-filter="all"]');
  if (el) {
    filterCat(e, el, 'all');
  } else {
    window.location.href = 'index.php';
  }
  return false;
}

function escapeHtml(str) {
  if (str == null) return '';
  var d = document.createElement('div');
  d.textContent = str;
  return d.innerHTML;
}

function syncCategoryActive(type) {
  var want = type === 'all' || !type ? 'all' : type;
  document.querySelectorAll('.cat-premium').forEach(function (c) {
    c.classList.toggle('active', c.getAttribute('data-filter') === want);
  });
}

function renderHomeListingCards(logements) {
  var defaultImg = 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600&q=80';
  return logements.map(function (l) {
    var badge = Number(l.prix_nuit) < 150
      ? '<span class="card-badge">🔥 Prix doux</span>'
      : '';
    var price = Math.round(Number(l.prix_nuit)).toLocaleString('fr-FR');
    return (
      '<div class="card-premium" onclick="window.location.href=\'logement-detail.php?id=' +
      l.id_logement +
      '\'">' +
      '<div class="card-image-wrapper">' +
      '<img src="' + escapeHtml(l.image_url || defaultImg) + '" alt="' + escapeHtml(l.titre) + '" loading="lazy">' +
      '</div>' +
      '<button type="button" class="card-fav" onclick="event.stopPropagation(); toggleFavori(this, ' +
      l.id_logement +
      ')"><i class="fa-regular fa-heart"></i></button>' +
      badge +
      '<div class="card-info">' +
      '<div class="card-title">' + escapeHtml(l.titre) + '</div>' +
      '<div class="card-type">' + escapeHtml(labelTypeLogement(l.type)) + '</div>' +
      '<div class="card-location">' +
      '<i class="fa-solid fa-location-dot" style="font-size: 12px; margin-right: 4px;"></i>' +
      escapeHtml(l.region_ville) +
      '</div>' +
      '<div class="card-price"><strong>' +
      price +
      ' TND</strong> <span>/ nuit</span></div>' +
      '</div></div>'
    );
  }).join('');
}

function loadHomeListings(type, opts) {
  opts = opts || {};
  var scroll = opts.scroll !== false;
  var grid = document.getElementById('listingsGrid');
  if (!grid) return Promise.resolve();

  grid.classList.add('listings-grid-loading');
  var q = type === 'all' || !type ? '' : '?type=' + encodeURIComponent(type);

  return fetch('api/listings_home.php' + q, { credentials: 'same-origin' })
    .then(function (r) {
      if (!r.ok) throw new Error('HTTP ' + r.status);
      return r.json();
    })
    .then(function (data) {
      if (!data.ok || !data.logements) {
        grid.innerHTML =
          '<div class="search-empty" style="grid-column:1/-1">' +
          '<div class="search-empty-icon" aria-hidden="true"><i class="fa-solid fa-magnifying-glass"></i></div>' +
          '<h3>Aucun logement dans cette catégorie</h3>' +
          '<p>Essayez une autre catégorie ou revenez à « Tous ».</p></div>';
        return;
      }
      if (data.logements.length === 0) {
        grid.innerHTML =
          '<div class="search-empty" style="grid-column:1/-1">' +
          '<div class="search-empty-icon" aria-hidden="true"><i class="fa-solid fa-magnifying-glass"></i></div>' +
          '<h3>Aucun logement dans cette catégorie</h3>' +
          '<p>Essayez une autre catégorie ou revenez à « Tous ».</p></div>';
        return;
      }
      grid.innerHTML = renderHomeListingCards(data.logements);
    })
    .catch(function () {
      grid.innerHTML =
        '<div class="search-empty" style="grid-column:1/-1"><p>Impossible de charger les logements. Réessayez.</p></div>';
    })
    .finally(function () {
      grid.classList.remove('listings-grid-loading');
      if (scroll) {
        var anchor = document.getElementById('homeListingsAnchor');
        if (anchor) anchor.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
}

function filterCat(e, element, type) {
  if (e && e.preventDefault) e.preventDefault();

  if (!homeCategoryAjax) {
    syncCategoryActive(type);
    if (type === 'all') window.location.href = 'index.php';
    else window.location.href = 'index.php?type=' + encodeURIComponent(type);
    return;
  }

  syncCategoryActive(type);

  var nextUrl = type === 'all' ? 'index.php' : 'index.php?type=' + encodeURIComponent(type);
  if (window.history && window.history.pushState) {
    window.history.pushState({ homeType: type }, '', nextUrl);
  }

  loadHomeListings(type, { scroll: true });
}

window.addEventListener('popstate', function () {
  if (!homeCategoryAjax) return;
  var params = new URLSearchParams(window.location.search);
  var t = params.get('type') || 'all';
  syncCategoryActive(t);
  loadHomeListings(t, { scroll: false });
});

function toggleFavori(btn, id) {
  btn.querySelector('i').classList.toggle('fa-regular');
  btn.querySelector('i').classList.toggle('fa-solid');
}

// Animation au scroll
const observerOptions = { threshold: 0.1, rootMargin: '0px 0px -50px 0px' };
const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if(entry.isIntersecting) {
      entry.target.classList.add('animate-fade');
      observer.unobserve(entry.target);
    }
  });
}, observerOptions);

document.querySelectorAll('.grid-premium, .stats-premium, .inspiration-grid').forEach(el => {
  observer.observe(el);
});

// Welcome message animation for login success
if (document.querySelector('.welcome-mode')) {
  // Create welcome message
  const welcomeDiv = document.createElement('div');
  welcomeDiv.style.position = 'absolute';
  welcomeDiv.style.top = '45%';
  welcomeDiv.style.left = '50%';
  welcomeDiv.style.transform = 'translate(-50%, -50%)';
  welcomeDiv.style.fontSize = 'clamp(32px, 5vw, 48px)';
  welcomeDiv.style.fontWeight = '700';
  welcomeDiv.style.color = 'white';
  welcomeDiv.style.textAlign = 'center';
  welcomeDiv.style.zIndex = '10';
  welcomeDiv.style.fontFamily = 'Playfair Display, serif';
  welcomeDiv.style.opacity = '1';
  welcomeDiv.style.transition = 'opacity 1s ease';

  document.querySelector('.hero-premium').appendChild(welcomeDiv);

  // Typing animation
  const text = '<?php echo isset($_SESSION["user_role"]) && $_SESSION["user_role"] === "proprietaire" ? "Bienvenue à votre espace propriétaire" : (isset($_SESSION["user_role"]) && $_SESSION["user_role"] === "admin" ? "Bienvenue à votre espace admin" : "Bienvenue à votre espace locataire"); ?>';
  let i = 0;
  const typeWriter = () => {
    if (i < text.length) {
      welcomeDiv.textContent += text.charAt(i);
      i++;
      setTimeout(typeWriter, 100); // Speed of typing
    } else {
      // After typing is complete, wait 2 seconds then fade out
      setTimeout(() => {
        welcomeDiv.style.opacity = '0';
        
        const heroH1 = document.querySelector('.hero-content-premium h1');
        const heroP = document.querySelector('.hero-content-premium p');
        
        heroH1.style.transition = 'opacity 1s ease';
        heroP.style.transition = 'opacity 1s ease';
        heroH1.style.opacity = '1';
        heroP.style.opacity = '1';
        
        // Remove welcome div after fade out
        setTimeout(() => {
          welcomeDiv.remove();
        }, 1000);
      }, 2000);
    }
  };
  typeWriter();
}
</script>

<?php require_once 'includes/footer.php'; ?>