<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Audit Master | NJIEZM.FR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Special+Elite&family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --nj-blue: #003366;
            --nj-yellow: #FFD700;
            --nj-red: #ff4757;
            --nj-green: #2ed573;
            --nj-dark: #1a1a1a;
        }

        body {
            background-color: #f0f2f5;
            font-family: 'Space Grotesk', sans-serif;
            color: var(--nj-dark);
            margin: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 900px;
            display: flex;
            min-height: 500px;
        }

        .login-left {
            background: var(--nj-blue);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            width: 40%;
        }

        .login-right {
            padding: 40px;
            width: 60%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .brand-font { font-family: 'Special Elite', cursive; }

        .form-control {
            border-radius: 0;
            border: 1px solid #ddd;
            padding: 12px 15px;
        }

        .form-control:focus {
            border-color: var(--nj-blue);
            box-shadow: 0 0 0 0.2rem rgba(0, 51, 102, 0.25);
        }

        .btn-login {
            background: var(--nj-yellow);
            color: var(--nj-blue);
            font-weight: bold;
            border: none;
            border-radius: 0;
            padding: 12px;
            text-transform: uppercase;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: #e6c200;
            color: var(--nj-blue);
        }

        .alert {
            border-radius: 0;
            border: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="brand-font fs-2 mb-4">NJIEZM</div>
            <h1 class="fs-4 mb-3">AUDIT MASTER</h1>
            <p class="mb-4">Plateforme d'audit professionnelle pour évaluer et améliorer vos processus.</p>
            <div class="mt-auto">
                <p class="small opacity-75">© {{ date('Y') }} NJIEZM.FR - Expertise Stratégique</p>
            </div>
        </div>
        <div class="login-right">
            <h2 class="mb-4">Connexion</h2>
            
            @if ($errors->any())
                <div class="alert alert-danger">
                    {{ $errors->first() }}
                </div>
            @endif
            
            <form method="POST" action="{{ route('login.authenticate') }}">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">Adresse e-mail</label>
                    <input type="email" class="form-control" id="email" name="email" required autofocus>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-login">Se connecter</button>
            </form>
            
            <!--div class="mt-4">
                <p class="small text-muted">Identifiants de démonstration :</p>
                <p class="small"><strong>Email:</strong> expert@njiemz.fr</p>
                <p class="small"><strong>Mot de passe:</strong> njiezm2024</p>
            </!--div-->
        </div>
    </div>
</body>
</html>