<?php
/**
 * Page d'erreur 404 personnalisée
 */
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page non trouvée - House After Party</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        :root {
            --bg-primary: #f8fafc;
            --bg-card: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --accent: #667eea;
        }
        
        [data-theme="dark"] {
            --bg-primary: #0f172a;
            --bg-card: #1e293b;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }
        
        /* Fond animé */
        .bg-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }
        
        .shape {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            animation: float 20s infinite;
        }
        
        .shape:nth-child(1) { width: 600px; height: 600px; top: -200px; left: -100px; animation-delay: 0s; }
        .shape:nth-child(2) { width: 400px; height: 400px; bottom: -100px; right: -100px; animation-delay: -5s; }
        .shape:nth-child(3) { width: 300px; height: 300px; top: 50%; left: 60%; animation-delay: -10s; }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(10deg); }
        }
        
        .container {
            text-align: center;
            padding: 40px;
            position: relative;
            z-index: 1;
        }
        
        .error-code {
            font-size: 180px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2, #f093fb);
            background-size: 200% 200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradient 5s ease infinite;
            line-height: 1;
            margin-bottom: 20px;
            text-shadow: 0 20px 40px rgba(102, 126, 234, 0.2);
        }
        
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .error-icon {
            font-size: 80px;
            margin-bottom: 30px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }
        
        h1 {
            font-size: 2em;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        p {
            font-size: 1.1em;
            color: var(--text-secondary);
            margin-bottom: 40px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 15px 30px;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 1em;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: var(--bg-card);
            color: var(--text-primary);
            border: 2px solid var(--accent);
        }
        
        .btn-secondary:hover {
            background: var(--accent);
            color: white;
        }
        
        .suggestions {
            margin-top: 60px;
            padding-top: 40px;
            border-top: 1px solid rgba(148, 163, 184, 0.2);
        }
        
        .suggestions h3 {
            font-size: 1em;
            color: var(--text-secondary);
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .quick-links {
            display: flex;
            gap: 30px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .quick-links a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .quick-links a:hover {
            transform: translateX(5px);
        }
        
        /* Animation de la maison */
        .house-animation {
            margin: 30px auto;
            position: relative;
            width: 120px;
            height: 120px;
        }
        
        .house {
            font-size: 80px;
            animation: shake 0.5s ease-in-out infinite;
        }
        
        .question-marks {
            position: absolute;
            top: -20px;
            right: -20px;
            font-size: 30px;
            animation: pop 1s ease infinite;
        }
        
        @keyframes shake {
            0%, 100% { transform: rotate(-3deg); }
            50% { transform: rotate(3deg); }
        }
        
        @keyframes pop {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
        }
        
        @media (max-width: 600px) {
            .error-code { font-size: 100px; }
            h1 { font-size: 1.5em; }
            .buttons { flex-direction: column; }
            .btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="bg-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    
    <div class="container">
        <div class="house-animation">
            <div class="house">🏠</div>
            <div class="question-marks">❓</div>
        </div>
        
        <div class="error-code">404</div>
        
        <h1>Oups ! La fête n'est pas ici...</h1>
        <p>La page que vous cherchez semble avoir quitté la soirée. Elle n'existe peut-être plus ou l'adresse est incorrecte.</p>
        
        <div class="buttons">
            <a href="index.php" class="btn btn-primary">
                🏠 Retour à l'accueil
            </a>
            <a href="javascript:history.back()" class="btn btn-secondary">
                ← Page précédente
            </a>
        </div>
        
        <div class="suggestions">
            <h3>Vous cherchez peut-être...</h3>
            <div class="quick-links">
                <a href="forms/Annonce.form.php">🏡 Voir les annonces</a>
                <a href="forms/blog.php">💬 Lire les avis</a>
                <a href="auth/connexion.php">🔐 Se connecter</a>
                <a href="forms/Reservation.form.php">📅 Réservations</a>
            </div>
        </div>
    </div>
    
    <script>
        // Détection du thème
        const theme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', theme);
    </script>
</body>
</html>
