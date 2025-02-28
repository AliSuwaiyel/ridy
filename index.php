
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="emb.png" type="image/icon type">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <title>RIDY</title>
    <style>
        :root {
            --font-family: <?= $currentLang === 'ar' ? '"Tajawal", sans-serif' : '"Noto Sans Arabic", sans-serif' ?>;
            --primary: #406692;
            --background: #F8FAFC;
            --surface: #FFFFFF;
            --border: #E2E8F0;
            --text: #1E293B;
            --secondary-text: #64748B;
        }

        [data-theme="dark"] {
            --primary:rgb(98, 138, 184);
            --background: #0F172A;
            --surface: #1E293B;
            --border: #334155;
            --text: #F8FAFC;
            --secondary-text: #94A3B8;
        }
        h6 {
            /* Existing styles */
            color: var(--text);
            text-align: center;
            background: var(--surface);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 0.8rem 1.5rem;
            border: 1px solid var(--border);
            width: fit-content;
            margin: 1.5rem auto 0;
            font-size: 0.9rem;
            font-weight: 400;
            /* New additions for animation */
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
    
        h6::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                120deg,
                transparent,
                rgba(255, 255, 255, 0.3),
                transparent
            );
            transition: 0.5s;
        }
    
        h6:hover::before {
            left: 100%;
        }
    
        h6:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 243, 255, 0.4);
        }
        a
        {
            font-size: 0.9rem;
            font-weight: 400;
            font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            text-decoration: none;
        }
        a:hover
        {
            
        }

        /* Modify body's flex-direction */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #676767;
opacity: 1;
background-image: radial-gradient(circle at center center, #000000, #676767), repeating-radial-gradient(circle at center center, #000000, #000000, 11px, transparent 22px, transparent 11px);
background-blend-mode: multiply;
            color: var(--text);
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column; /* Added */
            justify-content: center;
            align-items: center;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .login-container {
            background: var(--surface);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 400px;
            border: 1px solid var(--border);
        }

        .logo {
            width: 380px;
            margin-bottom: 1rem;
            transition: transform 0.2s ease;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .theme-toggle {
            position: fixed;
            top: 2rem;
            right: 2rem;
            background: var(--primary);
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .login-button {
            background: #406692;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .login-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                120deg,
                transparent,
                rgba(255, 255, 255, 0.3),
                transparent
            );
            transition: 0.5s;
        }

        .login-button:hover::before {
            left: 100%;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 243, 255, 0.4);
        }

        @media (max-width: 768px) {
            .login-container {
                width: 90%;
                padding: 2rem;
            }
            
            .logo {
                width: 260px;
            }
        }


        .form-group-search {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .form-group-search input {
            padding: 0.875rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: var(--surface);
            color: var(--text);
        }
        
        .form-group-search input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .container2 {
    background-color: #676767;
opacity: 0.8;
background-image: radial-gradient(circle at center center, #000000, #676767), repeating-radial-gradient(circle at center center, #000000, #000000, 11px, transparent 22px, transparent 11px);
background-blend-mode: multiply;
}
    </style>
</head>
<body>
    <button class="theme-toggle" onclick="toggleTheme()">
        <span id="theme-icon">🌙</span>
    </button>

    <div class="login-container">
        <a href="https://t.me/RIDYridyBOT">
            <img src="logo.png" alt="Ridy Logo" class="logo">
        </a>
        <form onsubmit="login(); return false;">
            <div class="form-group-search">
                <input type="text" placeholder="Username" id="un">
                <input type="password" placeholder="Password" id="pw">
            </div>
            <input type="submit" class="login-button" value="Go To Dashboard">
        </form>
        
        <script>
        function login() {
    const username = document.getElementById('un').value;
    const password = document.getElementById('pw').value;

    if (username === '' || password === '') {
        alert('Please fill in both username and password');
        return;
    }
    else if (username === "a" || username === "A" && password === "a") {
        // Make an AJAX call to a PHP script that sets the session
        
          window.location.href = 'web-dash.php';
          
    }
    else {
        alert("Wrong Credentials");
    }
}
        </script>
        
        
            
    </div>
    <a href="https://alialsuwaiyel.erbut.me/" target="_blank"><h6>By Ali AlSuwaiyel</h6></a>

        

    

    <script>
        function toggleTheme() {
            const body = document.body;
            const themeIcon = document.getElementById('theme-icon');
            
            if(body.getAttribute('data-theme') === 'dark') {
                body.removeAttribute('data-theme');
                themeIcon.textContent = '🌙';
                localStorage.setItem('theme', 'light');
            } else {
                body.setAttribute('data-theme', 'dark');
                themeIcon.textContent = '☀️';
                localStorage.setItem('theme', 'dark');
            }
        }

        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        if(savedTheme === 'dark') {
            document.body.setAttribute('data-theme', 'dark');
            document.getElementById('theme-icon').textContent = '☀️';
        }
    </script>
    </div>
</body>
</html>