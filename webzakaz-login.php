<?php
require_once __DIR__ . '/vendor/autoload.php';
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}


session_start();

// If already logged in, redirect to main page
if (isset($_SESSION['webzakaz_cashier_id'])) {
    header('Location: webzakaz.php');
    exit();
}



// If already logged in, redirect to main page
if (isset($_SESSION['webzakaz_cashier_id'])) {
    header('Location: webzakaz.php');
    exit();
}

$backendUrl = 'http://localhost:3003';
$error = '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход | WebZakaz</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 400px;
            width: 100%;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 18px;
            text-align: center;
            letter-spacing: 4px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #5cb85c;
        }

        .error-message {
            background-color: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background-color: #5cb85c;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background-color: #4cae4c;
        }

        .submit-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .loading {
            display: none;
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🍽️ WebZakaz</h1>
            <p>Введите пинкод для входа</p>
        </div>

        <div id="error-message" class="error-message"></div>

        <form id="login-form">
            <div class="form-group">
                <label for="pin">Пинкод кассира</label>
                <input type="password" id="pin" name="pin" maxlength="10" autocomplete="off" autofocus required>
            </div>

            <button type="submit" class="submit-btn" id="submit-btn">Войти</button>
        </form>

        <div class="loading" id="loading">Вход...</div>
    </div>

    <script>
        const BACKEND_URL = '<?= $backendUrl ?>';

        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const pin = document.getElementById('pin').value;
            const submitBtn = document.getElementById('submit-btn');
            const loading = document.getElementById('loading');
            const errorMsg = document.getElementById('error-message');

            submitBtn.disabled = true;
            loading.style.display = 'block';
            errorMsg.classList.remove('show');

            try {
                const response = await fetch(`${BACKEND_URL}/api/webzakaz/auth/login`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify({ pin })
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = 'webzakaz.php';
                } else {
                    errorMsg.textContent = data.message || 'Неверный пинкод';
                    errorMsg.classList.add('show');
                    document.getElementById('pin').value = '';
                    document.getElementById('pin').focus();
                }
            } catch (error) {
                console.error('Login error:', error);
                errorMsg.textContent = 'Ошибка подключения к серверу';
                errorMsg.classList.add('show');
            } finally {
                submitBtn.disabled = false;
                loading.style.display = 'none';
            }
        });

        // Auto-focus on pin input
        document.getElementById('pin').focus();
    </script>
</body>
</html>

ENV['BACKEND_URL'] ?? 'http://localhost:3003';
$error = '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход | WebZakaz</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 400px;
            width: 100%;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 18px;
            text-align: center;
            letter-spacing: 4px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #5cb85c;
        }

        .error-message {
            background-color: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background-color: #5cb85c;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background-color: #4cae4c;
        }

        .submit-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .loading {
            display: none;
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🍽️ WebZakaz</h1>
            <p>Введите пинкод для входа</p>
        </div>

        <div id="error-message" class="error-message"></div>

        <form id="login-form">
            <div class="form-group">
                <label for="pin">Пинкод кассира</label>
                <input type="password" id="pin" name="pin" maxlength="10" autocomplete="off" autofocus required>
            </div>

            <button type="submit" class="submit-btn" id="submit-btn">Войти</button>
        </form>

        <div class="loading" id="loading">Вход...</div>
    </div>

    <script>
        const BACKEND_URL = '<?= $backendUrl ?>';

        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const pin = document.getElementById('pin').value;
            const submitBtn = document.getElementById('submit-btn');
            const loading = document.getElementById('loading');
            const errorMsg = document.getElementById('error-message');

            submitBtn.disabled = true;
            loading.style.display = 'block';
            errorMsg.classList.remove('show');

            try {
                const response = await fetch(`${BACKEND_URL}/api/webzakaz/auth/login`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify({ pin })
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = 'webzakaz.php';
                } else {
                    errorMsg.textContent = data.message || 'Неверный пинкод';
                    errorMsg.classList.add('show');
                    document.getElementById('pin').value = '';
                    document.getElementById('pin').focus();
                }
            } catch (error) {
                console.error('Login error:', error);
                errorMsg.textContent = 'Ошибка подключения к серверу';
                errorMsg.classList.add('show');
            } finally {
                submitBtn.disabled = false;
                loading.style.display = 'none';
            }
        });

        // Auto-focus on pin input
        document.getElementById('pin').focus();
    </script>
</body>
</html>



