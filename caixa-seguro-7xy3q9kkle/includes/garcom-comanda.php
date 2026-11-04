<?php
function buscarComandaAbertaDoGarcom(PDO $db, int $comandaId, int $garcomId) {
    $stmt = $db->prepare(
        "SELECT id FROM comandas
         WHERE id = ? AND garcom_usuario_id = ? AND status = 'aberta'
         FOR UPDATE"
    );
    $stmt->execute([$comandaId, $garcomId]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function atualizarTotalComanda(PDO $db, int $comandaId) {
    $stmt = $db->prepare('SELECT COALESCE(SUM(subtotal), 0) FROM itens_comanda WHERE comanda_id = ?');
    $stmt->execute([$comandaId]);
    $total = $stmt->fetchColumn();

    $stmt = $db->prepare('UPDATE comandas SET valor_total = ? WHERE id = ? AND status = \'aberta\'');
    $stmt->execute([$total, $comandaId]);
}
?>
