<?php
session_start();
require_once("connect.php");
// --- 2. Vérifier si le formulaire est soumis ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $password = $_POST['pass'];

    // --- 3. Vérifier si l'utilisateur existe ---
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if ($user) {
        // --- 4. Vérifier le mot de passe ---
        if (password_verify($password, $user['password'])) {
            // Mot de passe correct

            // Stocker l'ID et le username dans la session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            // Redirection vers dashboard
            header("Location: home.php");
            exit();
        } else {
            $error = "Mot de passe incorrect.";
        }
    } else {
        $error = "Nom d'utilisateur introuvable.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
<div class="container">
        <form action="login.php" method="POST">

            <?php if(isset($error)): ?>
                <p style="color:#f87171; text-align:center;"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <p>
                <input type="text" name="username" placeholder="Username" required>
            </p>

            <p>
                <input type="password" name="pass" placeholder="Password" required>
            </p>

            <input type="submit" value="Login">

        </form>
    <a href="signin.html">Sign Up</a>
</div>
</body>
</html>
