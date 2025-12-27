<?php
session_start();

// Logout logic
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Login logic
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli('localhost', 'root', '', 'cms_db');
    if ($conn->connect_error) {
        $error = "Erro no banco de dados";
    } else {
        $pass = $_POST['password'] ?? '';
        $stmt = $conn->prepare("SELECT password FROM users WHERE username = 'admin'");
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($row = $res->fetch_assoc()) {
            if (password_verify($pass, $row['password'])) {
                $_SESSION['user'] = true;
                header("Location: admin.php");
                exit;
            }
        }
        $error = 'Senha incorreta!';
        $conn->close();
    }
}

// Redirect if already logged in
if (!empty($_SESSION['user'])) {
    header("Location: admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-sm">
        <h1 class="text-2xl font-bold mb-6 text-center text-gray-800">Acesso Administrativo</h1>
        
        <?php if($error): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-sm text-center"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-bold mb-2 text-gray-600">Senha</label>
                <input type="password" name="password" required class="w-full p-3 border rounded focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded hover:bg-blue-700 transition">Entrar</button>
        </form>
        <div class="mt-6 text-center">
            <a href="index.php" class="text-sm text-gray-500 hover:text-gray-800">← Voltar ao site</a>
        </div>
    </div>
</body>
</html>
