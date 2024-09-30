<?php

require './vendor/autoload.php';

use Dompdf\Dompdf;

$dompdf = new Dompdf(['enable_remote' => true]);

$data1 = filter_input(INPUT_GET, 'data1', FILTER_DEFAULT);
$data2 = filter_input(INPUT_GET, 'data2', FILTER_DEFAULT);
$usuario = filter_input(INPUT_GET, 'usuario', FILTER_DEFAULT);

include_once './config.php';

if (!empty($data1) && !empty($data2)) {
    // Consulta para vendas
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
                v.dataVenda BETWEEN ? AND ? 
            ORDER BY 
                v.idVenda, v.dataVenda;";

    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("ss", $data1, $data2);
    $stmt->execute();
    $result = $stmt->get_result();

    // Converte as datas do filtro para o formato BR (dd/mm/yy)
    $dateTime1 = new DateTime($data1);
    $dateTime2 = new DateTime($data2);

    $dataFormatada1 = $dateTime1->format('d/m/y'); // Formato dd/mm/yy
    $dataFormatada2 = $dateTime2->format('d/m/y'); // Formato dd/mm/yy

    if ($result->num_rows > 0) {
        $html = "<!DOCTYPE html>";
        $html .= "<html lang='pt-br'>";
        $html .= "<head>";
        $html .= "<meta charset='UTF-8'>";
        $html .= "<title>Relatório de Vendas</title>";
        $html .= "<style>body { font-family: Arial, sans-serif; } table { width: 100%; border-collapse: collapse; margin: 0 auto;} th, td { border: 1px solid #ddd; padding: 8px; text-align: center; } th { background-color: #f2f2f2; } h1{text-align:center; }</style>";
        $html .= "</head>";
        $html .= "<body>";
        $html .= "<h1>";
        $html .= "<img src='http://localhost/siteLojaSapatos/img/logo.png' style='width: 100px; height: auto; display: inline-block; vertical-align: baseline; margin-right: 10px;'>";
        $html .= "Relatório de Vendas";
        $html .= "<h2 style='font-size: 20px; color: gray; text-align: center;'>($dataFormatada1 a $dataFormatada2)</h2>";
        $html .= "<p>Gerado por $usuario (Gerente)</p>";
        $html .= "<h3 style='text-align: center;'>Vendas: </h3>";
        $html .= "<table>";
        $html .= "<thead><tr><th>#</th><th>Data</th><th>Produto</th><th>Quantidade (Un.)</th><th>Vendedor</th><th>Cliente</th><th>Preço Unitário (R$)</th><th>Subtotal (R$)</th><th>Comissão Fun. (R$)</th></tr></thead>";
        $html .= "<tbody>";

        while ($row = $result->fetch_assoc()) {
            // Converte a data de venda para o formato BR (dd/mm/yy)
            $dataVendaBR = DateTime::createFromFormat('Y-m-d', $row['dataVenda'])->format('d/m/y');  // Formato dd/mm/yy
            
            $html .= "<tr>";
            $html .= "<td>" . htmlspecialchars($row['idVenda']) . "</td>";
            $html .= "<td>" . htmlspecialchars($dataVendaBR) . "</td>";  // Exibe a data no formato dd/mm/yy
            $html .= "<td>" . htmlspecialchars($row['nomeProduto']) . "</td>";
            $html .= "<td>" . htmlspecialchars($row['quantidadeProduto']) . "</td>";
            $html .= "<td>" . htmlspecialchars($row['Vendedor']) . "</td>";
            $html .= "<td>" . htmlspecialchars($row['Cliente']) . "</td>";
            $html .= "<td>" . htmlspecialchars($row['precoUnitarioProduto']) . "</td>";
            $html .= "<td>" . htmlspecialchars($row['Subtotal']) . "</td>";
            $html .= "<td>" . htmlspecialchars($row['ComissaoFuncionario']) . "</td>";
            $html .= "</tr>";
        }

        $html .= "</tbody>";
        $html .= "</table>";
        
        
        // Consulta para pagamento
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
                v.dataVenda BETWEEN ? AND ?
            GROUP BY 
                p1.nomePessoa, s.valorSalario;";

        $stmtPagamento = $conexao->prepare($sqlPagamento);
        $stmtPagamento->bind_param("ss", $data1, $data2);
        $stmtPagamento->execute();
        $resultPagamento = $stmtPagamento->get_result();

        $html .= "<br><br><br>";
        $html .= "<h3 style='text-align: center;'>Pagamento:</h3>";
        $html .= "<table>";
        $html .= "<thead><tr><th>Vendedor</th><th>Salário (R$)</th><th>Total de Comissões (R$)</th><th>Total a Receber (R$)</th></tr></thead>";
        $html .= "<tbody>";

        while ($rowPagamento = $resultPagamento->fetch_assoc()) {
            $html .= "<tr>";
            $html .= "<td>" . htmlspecialchars($rowPagamento['Vendedor']) . "</td>";
            $html .= "<td>" . htmlspecialchars($rowPagamento['Salario']) . "</td>";
            $html .= "<td>" . htmlspecialchars($rowPagamento['TotalComissoes']) . "</td>";
            $html .= "<td>" . htmlspecialchars($rowPagamento['TotalReceber']) . "</td>";
            $html .= "</tr>";
        }

        $html .= "</tbody>";
        $html .= "</table>";

        $html .= "<br><br><br>";
        $html .= "<hr>";
        $html .= "<h4 style='text-align: center;'>Trabalho 3B - Banco de Dados</h4>";
        $html .= "<p><b>Alunos:</b> Daniela, Marlon, Rafaela, Ryan e Talisson</p>";

        $html .= "</body>";
        $html .= "</html>";

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('relatorio_vendas.pdf', ["Attachment" => false]);
    } else {
        echo "<h2>Nenhuma venda encontrada no intervalo selecionado.</h2>";
    }
} else {
    echo "<h2>Por favor, informe o intervalo de datas.</h2>";
}

?>
