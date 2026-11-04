// modules/admin/admin.js

function openUserModal(user) {
    const modalEl = document.getElementById('userModal');
    const userId = document.getElementById('userId');
    const userNome = document.getElementById('userNome');
    const userEmail = document.getElementById('userEmail');
    const userSenha = document.getElementById('userSenha');
    const userPerfil = document.getElementById('userPerfil');
    const userAtivo = document.getElementById('userAtivo');

    if (!modalEl || !userId || !userNome || !userEmail || !userSenha || !userPerfil || !userAtivo) {
        console.error('Não foi possível abrir o formulário: os campos de usuário estão indisponíveis.');
        return;
    }

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
    if (!window.bootstrap || !window.bootstrap.Modal) {
        console.error('Não foi possível abrir o formulário: Bootstrap Modal não foi carregado.');
        return;
    }

    window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
}

document.addEventListener('DOMContentLoaded', function () {
    const btnNovo = document.getElementById('btn-novo-usuario');
    if (btnNovo) btnNovo.addEventListener('click', () => openUserModal(null));

    document.querySelectorAll('[data-edit-user]').forEach((button) => {
        button.addEventListener('click', function () {
            openUserModal({
                id: this.dataset.id,
                nome: this.dataset.nome,
                email: this.dataset.email,
                perfil: this.dataset.perfil,
                ativo: this.dataset.ativo === '1'
            });
        });
    });
});
