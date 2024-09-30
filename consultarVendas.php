<?php 
session_start();
include_once('config.php');

if (!isset($_SESSION['cpf']) || !isset($_SESSION['senha'])) {
    header('Location: login.php');
    exit();
}

$cpfPessoa = $_SESSION['cpf'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/svg+xml" href="img/iconeMaleta.svg">
    <title>Consultar Vendas</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.2/html2pdf.bundle.min.js" integrity="sha512-MpDFIChbcXl2QgipQrt1VcPHMldRILetapBl5MPCA9Y8r7qvlwx1/Mc9hNTzY+kS5kX6PdoDq41ws1HiVNLdZA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="gerarRelatorio.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="styles.css/consultarVendas.css">
</head>
<body>
    <header>
        <div class="logo">
            <p class="textoLogo">Ralver</p>
            <p class="descricaoLogo">Gerentes</p>
        </div>
        <div class="botoes">
            <a href="gerente.php" class="botaoVoltar">Voltar</a>
            <a href="sair.php" class="botaoSair">Sair</a>
        </div>
    </header>

    <main>
        <div class="pesquisar">
            <h1>Consultar Período de vendas</h1>
            <form action="" method="GET">
                <p>Data inicial: <input class="data" type="date" name="dat1" required></p>
                <p>Data final: <input class="data" type="date" name="dat2" required></p>
                <input class="pesquisa" type="submit" value="Pesquisar">
            </form>
        </div>

        <div class="relatorioVendas" id="relatorioVendas">
            <?php
            if (isset($_GET['dat1']) && isset($_GET['dat2'])) {
                $data1 = $_GET['dat1'];
                $data2 = $_GET['dat2'];
                $sql = "SELECT 
                            v.idVenda,
                            v.dataVenda,
                            p.nomeProduto,
                            vhp.quantidadeProduto,
                            p1.nomePessoa AS Vendedor,
                            p2.nomePessoa AS Cliente,
                            p.precoUnitarioProduto,
                            vhp.quantidadeProduto * p.precoUnitarioProduto AS Subtotal,
                            ROUND((vhp.quantidadeProduto * p.precoUnitarioProduto * (CAST(f.comissaoFuncionario AS DECIMAL) / 100)), 2) AS ComissaoFuncionario
                        FROM
                            venda v
                        JOIN venda_has_produto vhp ON
                            v.idVenda = vhp.venda_idVenda
                        JOIN funcionario f ON
                            f.pessoa_cpf = v.funcionario_pessoa_cpf
                        JOIN pessoa p1 ON
                            p1.cpf = f.pessoa_cpf
                        JOIN cliente c ON
                            c.pessoa_cpf = v.cliente_pessoa_cpf
                        JOIN pessoa p2 ON
                            c.pessoa_cpf = p2.cpf
                        JOIN produto p ON 
                            p.idProduto = vhp.produto_idProduto 
                        WHERE 
                            v.dataVenda BETWEEN '$data1' AND '$data2' 
                        ORDER BY 
                            v.idVenda, v.dataVenda;";
                $stmt = $conexao->prepare($sql);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    echo "<div class='infoRelatorio1'>";
                    echo "<h2 class='tituloTabela'>Vendas no intervalo de $data1 a $data2:</h2>";
                    echo "<table class='table'>";
                    echo "<thead>";
                    echo "<tr>";
                    echo "<th scope='coluna'>#</th>";
                    echo "<th scope='coluna'>Data</th>";
                    echo "<th scope='coluna'>Produto</th>";
                    echo "<th scope='coluna'>Quantidade (Un.)</th>";
                    echo "<th scope='coluna'>Vendedor</th>";
                    echo "<th scope='coluna'>Cliente</th>";
                    echo "<th scope='coluna'>Preco unitário (R$)</th>";
                    echo "<th scope='coluna'>Subtotal (R$)</th>";
                    echo "<th scope='coluna'>Comissão do Funcionário (R$)</th>";
                    echo "</tr>";
                    echo "</thead>";
                    echo "<tbody>";
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row["idVenda"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["dataVenda"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["nomeProduto"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["quantidadeProduto"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["Vendedor"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["Cliente"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["precoUnitarioProduto"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["Subtotal"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["ComissaoFuncionario"]) . "</td>";
                        echo "</tr>";
                    }
                    echo "</tbody>";
                    echo "</table>";
                    echo "</div>";

                    
                    $sql_total = "SELECT 
                                    v.dataVenda, 
                                    p1.nomePessoa AS Vendedor,
                                    SUM(vhp.quantidadeProduto * p.precoUnitarioProduto) AS TotalVendas 
                                  FROM 
                                    venda v 
                                  JOIN venda_has_produto vhp ON 
                                    v.idVenda = vhp.venda_idVenda 
                                  JOIN produto p ON 
                                    p.idProduto = vhp.produto_idProduto 
                                  JOIN funcionario f ON 
                                    f.pessoa_cpf = v.funcionario_pessoa_cpf 
                                  JOIN pessoa p1 ON 
                                    p1.cpf = f.pessoa_cpf 
                                  WHERE 
                                    v.dataVenda BETWEEN '$data1' AND '$data2' 
                                  GROUP BY 
                                    v.dataVenda, p1.nomePessoa 
                                  ORDER BY 
                                    v.dataVenda;";
                    $stmt_total = $conexao->prepare($sql_total);
                    $stmt_total->execute();
                    $result_total = $stmt_total->get_result();
                    
                    $vendedores = [];
                    $datas = [];
                    $vendedoresAdicionados = [];

                    while ($row = $result_total->fetch_assoc()) {
                        $data = htmlspecialchars($row["dataVenda"]);
                        $vendedor = htmlspecialchars($row["Vendedor"]);
                        $total = floatval($row["TotalVendas"]);

                        if (!in_array($data, $datas)) {
                            $datas[] = $data;
                        }

                        if (!in_array($vendedor, $vendedoresAdicionados)) {
                            $vendedores[$vendedor] = array_fill(0, count($datas), 0);
                            $vendedoresAdicionados[] = $vendedor; 
                        } else {
                            // Certifica-se que o array do vendedor tenha a quantidade correta de entradas
                            $currentCount = count($vendedores[$vendedor]);
                            $newCount = count($datas);
                            if ($newCount > $currentCount) {
                                // Preenche com zeros os novos índices, caso haja novas datas
                                $vendedores[$vendedor] = array_pad($vendedores[$vendedor], $newCount, 0);
                            }
                        }

                        $index = array_search($data, $datas);
                        if ($index !== false) {
                            $vendedores[$vendedor][$index] += $total;
                        }
                    }

                    if (empty($vendedores)) {
                        echo "<h2>Nenhuma venda encontrada para os vendedores neste intervalo.</h2>";
                    } else {
                        $cores = [
                            'rgba(75, 192, 192, 0.6)',
                            'rgba(255, 99, 132, 0.6)',
                            'rgba(255, 206, 86, 0.6)',
                            'rgba(54, 162, 235, 0.6)',
                            'rgba(153, 102, 255, 0.6)',
                            'rgba(255, 159, 64, 0.6)',
                        ];

                        echo "<div class='ladoDireito'>";
                        echo "<div class='infoRelatorio2'>";
                        echo "<h2 class='tituloTabela'>Gráfico de vendas: </h2>";
                        echo "<canvas id='graficoVendas'></canvas>";
                        echo "<script>";
                        echo "const ctx = document.getElementById('graficoVendas').getContext('2d');";
                        echo "const labels = " . json_encode($datas) . ";";
                        echo "const data = {";
                        echo "    labels: labels,";
                        echo "    datasets: [";
                        $i = 0;
                        foreach ($vendedores as $vendedor => $valores) {
                            if (count($valores) > 0) {
                                echo "    {";
                                echo "        label: '$vendedor',";
                                echo "        data: " . json_encode($valores) . ",";
                                echo "        backgroundColor: '{$cores[$i % count($cores)]}',";
                                echo "    },";
                                $i++;
                            }
                        }
                        echo "    ]";
                        echo "};";
                        echo "const config = {";
                        echo "    type: 'bar',";
                        echo "    data: data,";
                        echo "    options: {";
                        echo "        scales: {";
                        echo "            y: {";
                        echo "                beginAtZero: true";
                        echo "            }";
                        echo "        }";
                        echo "    }";
                        echo "};";
                        echo "const graficoVendas = new Chart(ctx, config);";
                        echo "</script>";
                        echo "</div>";
                    }
                } else {
                    echo "<h2>Nenhuma venda encontrada no intervalo selecionado.</h2>";
                }



                $sqlPagamento = "
                    SELECT 
                        p1.nomePessoa AS Vendedor,
                        s.valorSalario AS Salario, 
                        SUM(ROUND((vhp.quantidadeProduto * p.precoUnitarioProduto * (CAST(f.comissaoFuncionario AS DECIMAL) / 100)), 2)) AS TotalComissoes,
                        (s.valorSalario + SUM(ROUND((vhp.quantidadeProduto * p.precoUnitarioProduto * (CAST(f.comissaoFuncionario AS DECIMAL) / 100)), 2))) AS TotalReceber
                    FROM 
                        venda v
                    JOIN 
                        venda_has_produto vhp ON v.idVenda = vhp.venda_idVenda
                    JOIN 
                        funcionario f ON f.pessoa_cpf = v.funcionario_pessoa_cpf
                    JOIN 
                        pessoa p1 ON p1.cpf = f.pessoa_cpf
                    JOIN 
                        produto p ON p.idProduto = vhp.produto_idProduto
                    JOIN 
                        cargo c ON c.idCargo = f.cargoIdCargo
                    JOIN 
                        salario s ON s.idSalario = c.salario_idSalario
                    WHERE 
                        v.dataVenda BETWEEN '$data1' AND '$data2'
                    GROUP BY 
                        p1.nomePessoa, s.valorSalario;";

                $stmtPagamento = $conexao->prepare($sqlPagamento);
                $stmtPagamento->execute();
                $resultPagamento = $stmtPagamento->get_result();

                if ($resultPagamento->num_rows > 0) {
                    echo "<div class='infoRelatorio3'>";
                    echo "<h2 class='tituloTabela'>Pagamento referente ao período: </h2>";
                    echo "<table>";
                    echo "<thead>";
                    echo "<tr>";
                    echo "<th>Vendedor</th>";
                    echo "<th>Salário (R$)</th>";
                    echo "<th>Total em Comissões (R$)</th>";
                    echo "<th>Total a Receber (R$)</th>";
                    echo "</tr>";
                    echo "</thead>";
                    echo "<tbody>";
                    
                    while ($row = $resultPagamento->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['Vendedor']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['Salario']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['TotalComissoes']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['TotalReceber']) . "</td>";
                        echo "</tr>";
                    }
                    
                    echo "</tbody>";
                    echo "</table>";
                    echo "</div>";

                    $cpfPessoa = $_SESSION['cpf'];
    
                    $sqlNomePessoa = "SELECT p.nomePessoa FROM pessoa p WHERE p.cpf = '$cpfPessoa'";
                    $resultado = $conexao->query($sqlNomePessoa);
                    
                    $row = $resultado->fetch_assoc();
                    $usuario = $row['nomePessoa'];
                    
                    echo "<div class='alinharBotao'>";
                    echo "<a class='gerarRelaorio' href='gerar_pdf.php?data1=".$data1."&data2=".$data2."&usuario=".$usuario."' target= '_blank'>Gerar Relatório</a>";
                    echo "</div>";
                    echo "</div>";
                }

                
            }

            ?>
        </div>
    </main>
</body>
</html>
