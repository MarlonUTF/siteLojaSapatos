<?php
    session_start();

    if (isset($_POST['submit']) && !empty($_POST['cpf']) && !empty($_POST['senha'])) {
        include_once('config.php');
        $cpf = $_POST['cpf'];
        $senha = $_POST['senha'];

        $sql = "SELECT f.pessoa_cpf, f.senhaFuncionario FROM funcionario f WHERE f.pessoa_cpf = '$cpf' AND f.senhaFuncionario = '$senha'";
        $resultado = $conexao->query($sql);

        if (mysqli_num_rows($resultado) < 1) {
            // // Se CPF e senha não forem encontrados
            // echo 'Credenciais inválidas!';
            unset($_SESSION['cpf']);
            unset($_SESSION['senha']);
            header('Location: login.php');
            exit();
        } else {
            $_SESSION['cpf'] = $cpf;
            $_SESSION['senha'] = $senha;
            // Consultar o cargo do funcionário
            $sqlCargo = "SELECT c.nomeCargo FROM cargo c JOIN funcionario f ON f.cargoIdCargo = c.IdCargo WHERE f.pessoa_cpf = '$cpf'";
            $cargo = $conexao->query($sqlCargo);

            // Verificar se a consulta retornou resultados
            if ($cargo && $row = $cargo->fetch_assoc()) {
                $nomeCargo = $row['nomeCargo'];

                // Redirecionar com base no cargo
                if ($nomeCargo === 'Gerente') {
                    header('Location: gerente.php');
                    exit(); 
                } elseif ($nomeCargo === 'Vendedor') {
                    header('Location: vendedor.php');
                    exit();
                } else {
                    echo 'Cargo não reconhecido';
                }
            // } else {
            //     echo 'Cargo não encontrado';
                header('Location: login.php');
                exit(); // Encerra o script após o redirecionamento
            }
        }
    } else {
        // Redireciona para a página de login se os campos estiverem vazios
        header('Location: login.html');
        exit(); // Encerra o script após o redirecionamento
    }
?>