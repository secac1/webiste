<?php
$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($user = (new Auth($conn))->login($_POST['username'], $_POST['password'])) {
        $_SESSION = ['iduser' => $user['iduser'], 'namauser' => $user['namauser'], 'role' => $user['role']];
        redirect('index.php?page=dashboard');
    }
    $error = "Username atau password salah!";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Restoran</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <h2>Restoran</h2>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error); ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-group"><label>Username</label><input type="text" name="username" required></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
            <button type="submit" class="btn btn-primary">Masuk</button>
        </form>
    </div>
</body>
</html>