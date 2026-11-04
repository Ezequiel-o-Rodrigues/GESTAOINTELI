<?php
function obterTaxaComissao(PDO $db): float
{
    $stmt = $db->prepare("SELECT valor FROM configuracoes_sistema WHERE chave = 'commission_rate'");
    $stmt->execute();
    $rate = $stmt->fetchColumn();

    if ($rate === false || !is_numeric($rate) || (float) $rate < 0 || (float) $rate > 1) {
        throw new RuntimeException('A taxa de comissão não está configurada corretamente.');
    }

    return (float) $rate;
}

function buscarGarcomAtivo(PDO $db, int $usuarioId): array
{
    $stmt = $db->prepare("SELECT id, nome FROM usuarios WHERE id = ? AND perfil = 'garcom' AND ativo = 1");
    $stmt->execute([$usuarioId]);
    $garcom = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$garcom) {
        throw new DomainException('É necessário estar autenticado com uma conta ativa de garçom para lançar itens.');
    }

    return $garcom;
}

function inserirItemComissao(
    PDO $db,
    int $comandaId,
    int $produtoId,
    int $quantidade,
    float $precoUnitario,
    ?int $garcomUsuarioId,
    ?string $garcomNome,
    float $taxaComissao
): void {
    $subtotal = round($precoUnitario * $quantidade, 2);
    $comissao = round($subtotal * $taxaComissao, 2);
    $stmt = $db->prepare(
        'INSERT INTO itens_comanda
            (comanda_id, produto_id, quantidade, preco_unitario, subtotal, garcom_usuario_id,
             garcom_nome, comissao_percentual, comissao_valor)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $comandaId,
        $produtoId,
        $quantidade,
        $precoUnitario,
        $subtotal,
        $garcomUsuarioId,
        $garcomNome,
        $taxaComissao,
        $comissao
    ]);
}

function atualizarTotalComanda(PDO $db, int $comandaId): void
{
    $stmt = $db->prepare(
        'SELECT
            (SELECT COALESCE(SUM(subtotal), 0) FROM itens_comanda
             WHERE comanda_id = ? AND cancelado_em IS NULL)
            +
            (SELECT COALESCE(SUM(subtotal), 0) FROM itens_livres WHERE comanda_id = ?)'
    );
    $stmt->execute([$comandaId, $comandaId]);
    $total = $stmt->fetchColumn();

    $stmt = $db->prepare("UPDATE comandas SET valor_total = ? WHERE id = ? AND status = 'aberta'");
    $stmt->execute([$total, $comandaId]);
}

function cancelarItemComanda(PDO $db, int $comandaId, int $itemId, int $usuarioId): bool
{
    $stmt = $db->prepare(
        'UPDATE itens_comanda
         SET cancelado_em = NOW(), cancelado_por = ?
         WHERE id = ? AND comanda_id = ? AND cancelado_em IS NULL'
    );
    $stmt->execute([$usuarioId, $itemId, $comandaId]);

    return $stmt->rowCount() === 1;
}

function ajustarQuantidadeItemComanda(
    PDO $db,
    int $comandaId,
    int $itemId,
    int $novaQuantidade,
    int $usuarioId
): bool {
    $stmt = $db->prepare(
        'SELECT ic.produto_id, ic.quantidade, ic.preco_unitario, ic.garcom_usuario_id,
                ic.garcom_nome, ic.comissao_percentual, p.estoque_atual
         FROM itens_comanda ic
         JOIN produtos p ON p.id = ic.produto_id
         WHERE ic.id = ? AND ic.comanda_id = ? AND ic.cancelado_em IS NULL
         FOR UPDATE'
    );
    $stmt->execute([$itemId, $comandaId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$item) {
        return false;
    }

    $quantidadeAtual = (int) $item['quantidade'];
    if ($novaQuantidade === $quantidadeAtual) {
        return true;
    }

    if ($novaQuantidade > $quantidadeAtual) {
        $garcom = buscarGarcomAtivo($db, $usuarioId);
        $incremento = $novaQuantidade - $quantidadeAtual;

        $stmt = $db->prepare(
            "SELECT COALESCE(SUM(ic.quantidade), 0)
             FROM itens_comanda ic
             JOIN comandas c ON c.id = ic.comanda_id
             WHERE ic.produto_id = ? AND ic.cancelado_em IS NULL AND c.status = 'aberta'"
        );
        $stmt->execute([(int) $item['produto_id']]);
        $quantidadeReservada = (int) $stmt->fetchColumn();
        if ($quantidadeReservada + $incremento > (int) $item['estoque_atual']) {
            throw new OutOfBoundsException('Estoque insuficiente para aumentar a quantidade.');
        }

        inserirItemComissao(
            $db,
            $comandaId,
            (int) $item['produto_id'],
            $incremento,
            (float) $item['preco_unitario'],
            (int) $garcom['id'],
            $garcom['nome'],
            obterTaxaComissao($db)
        );
    } else {
        $stmt = $db->prepare(
            'UPDATE itens_comanda SET cancelado_em = NOW(), cancelado_por = ?
             WHERE id = ? AND cancelado_em IS NULL'
        );
        $stmt->execute([$usuarioId, $itemId]);

        inserirItemComissao(
            $db,
            $comandaId,
            (int) $item['produto_id'],
            $novaQuantidade,
            (float) $item['preco_unitario'],
            $item['garcom_usuario_id'] === null ? null : (int) $item['garcom_usuario_id'],
            $item['garcom_nome'] === null ? null : (string) $item['garcom_nome'],
            (float) $item['comissao_percentual']
        );
    }

    atualizarTotalComanda($db, $comandaId);
    return true;
}
?>
