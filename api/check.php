<?php
  require_once 'config.php';

  $login = filter_var(trim($_POST['login']), FILTER_SANITIZE_STRING);
  $name = filter_var(trim($_POST['name']), FILTER_SANITIZE_STRING);
  $pass = filter_var(trim($_POST['pass']), FILTER_SANITIZE_STRING);
  
  if(mb_strlen($login) < 5 || mb_strlen($login) > 90) {
    header('Location: /auth/register.php?error=' . urlencode("Недопустимая длина логина (от 5 до 90 символов)"));
    exit();
  } else if(mb_strlen($name) < 2 || mb_strlen($name) > 50) {
    header('Location: /auth/register.php?error=' . urlencode("Недопустимая длина имени (от 2 до 50 символов)"));
    exit();
  } else if(mb_strlen($pass) < 8 || mb_strlen($pass) > 16) {
    header('Location: /auth/register.php?error=' . urlencode("Недопустимая длина пароля (от 8 до 16 символов)"));
    exit();
  }
  
  if (!preg_match('/[A-Za-z]/', $pass) || !preg_match('/[0-9]/', $pass)) {
    header('Location: /auth/register.php?error=' . urlencode("Пароль должен содержать как буквы, так и цифры"));
    exit();
  }

  try {
    $pdo = getDbConnection();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE login = :login");
    $stmt->execute([':login' => $login]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
      header('Location: /auth/register.php?error=' . urlencode("Пользователь с таким логином уже существует"));
      exit();
    }
    
    $pass = md5($pass."hiferhifurie");
    
    $stmt = $pdo->prepare("INSERT INTO users (login, name, pass) VALUES (:login, :name, :pass)");
    $stmt->execute([
      ':login' => $login,
      ':name' => $name,
      ':pass' => $pass
    ]);
    
    header('Location: /auth/login.php?success=1');
    
  } catch (PDOException $e) {
    header('Location: /auth/register.php?error=' . urlencode("Ошибка подключения к базе данных: " . $e->getMessage()));
    exit();
  }
?>