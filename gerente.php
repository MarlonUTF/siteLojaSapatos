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
    <link rel="shortcut icon" type="svg" href="img/iconeMaleta.svg">
    <title>Ralver - Gerentes</title>
    <link rel="stylesheet" href="styles.css/gerente.css">
</head>
<body>
    <header>
        <div class="logo">
            <p class="textoLogo">Ralver</p>
            <p class="descricaoLogo">Gerentes</p>
        </div>
        <a href="sair.php" class="botaoSair">Sair</a>
    </header>

    <main>

        <?php 
            echo "<h1>Bem vindo(a)! <u>$usuario</u></h1>"
        ?>

        <div class="servicos">
            <a href="consultarVendas.php" class="servico">Consultar Vendas</a>
        </div>

    </main>

    <footer>
        <h3>Trabalho 3B - Banco de Dados</h3>
        <hr>

        <div class="divsFooter">

            <div class="divFooter">
                <h4>Alunos</h4>
                <p>Daniela, Marlon, Rafaela, Ryan e Talisson</p>
            </div>

            <div class="divFooter">
                <h4>Sobre o sistema:</h4>
                <p>Sistema desenvolvido para controle de comissões em uma loja de sapatos fictícia (Ralver)</p>
            </div>

        </div>

    </footer>
    
</body>
</html>