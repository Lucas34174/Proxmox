<?php
session_start();
// signin.php
require_once('connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Récupérer les champs
    $firstname = trim($_POST['firstname']);
    $lastname  = trim($_POST['lastname']);
    $username  = trim(string: $_POST['username']);
    $email     = trim($_POST['email']);
    $password  = $_POST['pass'];
    $confpass  = $_POST['confpass'];

    // --- 3. Vérification mot de passe ---
    if ($password !== $confpass) {
        die("Erreur : les mots de passe ne correspondent pas.");
    }

    // --- 4. Hasher le mot de passe ---
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    try {
        // --- 5. Commencer transaction ---
        $pdo->beginTransaction();

        // --- 6. Inserer dans users ---
        $stmtUser = $pdo->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
        $stmtUser->execute([
            ':username' => $username,
            ':password' => $passwordHash
        ]);

        // Récupérer l'ID généré
        $userId = $pdo->lastInsertId();

        // --- 7. Inserer dans personalInfo ---
        $stmtInfo = $pdo->prepare("INSERT INTO personalInfo (firstname, lastname, email, user_id) VALUES (:firstname, :lastname, :email, :user_id)");
        $stmtInfo->execute([
            ':firstname' => $firstname,
            ':lastname'  => $lastname,
            ':email'     => $email,
            ':user_id'   => $userId
        ]);

        // --- 8. Valider transaction ---
        $pdo->commit();
        // Stocker l'ID et le username dans la session
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
        
        // --- 9. Redirection vers page d'accueil ---
        header("Location: home.php");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Erreur lors de l'inscription : " . $e->getMessage());
    }
}
?>
