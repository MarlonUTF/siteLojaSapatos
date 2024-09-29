<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fazer login na Ralver</title>
    <link rel="shortcut icon" type="svg" href="img/iconeSapatinhos.svg">
    <link rel="stylesheet" href="styles.css/login.css">
</head>
<body>

    <main>

        <div class="container">
            <h1 class="h1Login">Bem vindo novamente! :)</h1>
            <form action="testar_login.php" method="POST">
                <h2>Entrar: </h2>
                <div class="input">
                    <label for="cpf">CPF</label>
                    <input type="text" name="cpf" id="cpf" placeholder="CPF" class="input_user" required>
                </div><div class="input">
                    <label for="senha">Senha</label>
                    <input type="password" name="senha" id="senha" placeholder="Senha" class="input_user" required>
                </div>
                <input type="submit" class="submit" name="submit" value="Entrar">
            </form>
            <p>Não tem cadastro? <a class="link" href="#">Entrar em contato</a></p>
        </div>

    </main>

</body>
</html>