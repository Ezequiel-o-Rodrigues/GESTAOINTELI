<?php
require_once __DIR__ . '/comissao-comanda.php';

function buscarComandaAbertaDoGarcom(PDO $db, int $comandaId) {
    $stmt = $db->prepare(
        "SELECT id FROM comandas
         WHERE id = ? AND status = 'aberta'
         FOR UPDATE"
    );
    $stmt->execute([$comandaId]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
