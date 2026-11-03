// modules/admin/admin.js

function openUserModal(user) {
    const modalEl = document.getElementById('userModal');
    const userId = document.getElementById('userId');
    const userNome = document.getElementById('userNome');
    const userEmail = document.getElementById('userEmail');
    const userSenha = document.getElementById('userSenha');
    const userPerfil = document.getElementById('userPerfil');
    const userAtivo = document.getElementById('userAtivo');

    if (!user) {
        userId.value = '';
        userNome.value = '';
        userEmail.value = '';
        userSenha.value = '';
        userPerfil.value = 'caixa';
        userAtivo.checked = true;
    } else {
        userId.value = user.id || '';
        userNome.value = user.nome || '';
        userEmail.value = user.email || '';
        userSenha.value = '';
        userPerfil.value = user.perfil || 'caixa';
        userAtivo.checked = !!user.ativo;
    }

    userSenha.required = !userId.value;
    const title = document.getElementById('userModalTitle');
    const icon = document.createElement('i');
    icon.className = `bi ${user ? 'bi-pencil-square' : 'bi-person-plus'}`;
    icon.setAttribute('aria-hidden', 'true');
    title.replaceChildren(icon, document.createTextNode(` ${user ? 'Editar usuário' : 'Novo usuário'}`));
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

document.addEventListener('DOMContentLoaded', function () {
    const btnNovo = document.getElementById('btn-novo-usuario');
    if (btnNovo) btnNovo.addEventListener('click', () => openUserModal(null));

    document.addEventListener('click', function (event) {
        const button = event.target.closest('[data-edit-user]');
        if (!button) return;

        openUserModal({
            id: button.dataset.id,
            nome: button.dataset.nome,
            email: button.dataset.email,
            perfil: button.dataset.perfil,
            ativo: button.dataset.ativo === '1'
        });
    });
});
