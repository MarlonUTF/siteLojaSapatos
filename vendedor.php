<?php 
    session_start();
    include_once('config.php');
    if((!isset($_SESSION['cpf']) == true) and (!isset($_SESSION['senha']) == true))
    {
        header('Location: login.php');
    }

    $cpfPessoa = $_SESSION['cpf'];
    
    $sqlNomePessoa = "SELECT p.nomePessoa FROM pessoa p WHERE p.cpf = '$cpfPessoa'";
    $resultado = $conexao->query($sqlNomePessoa);
    
    $row = $resultado->fetch_assoc();
    $usuario = $row['nomePessoa'];

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="svg" href="img/iconeHandshake.svg">
    <title>Ralver - Vendedores</title>
</head>
<body>
    <nav>
        <div class="logo">Ralver Sapatos</div>
        <a href="sair.php">Sair</a>
    </nav>
    <?php 
        echo "<h1>Bem vindo(a)! <u>$usuario</u></h1>"
    ?>
    <main>

    </main>
</body>
</html>