<!-- MODAL OBRIGATÓRIO DE TROCA DE SENHA APÓS RESET -->
<div id="modal-force-password" class="modal-overlay active" style="z-index: 9999; visibility: visible; opacity: 1;">
    <div class="modal-content" style="border-top: 5px solid var(--primary, #BC0000);">
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <div style="width: 70px; height: 70px; border-radius: 50%; background: rgba(188,0,0,0.08); display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem auto; border: 2px solid rgba(188,0,0,0.15);">
                <i class="fas fa-shield-alt" style="color: var(--primary, #BC0000); font-size: 2rem;"></i>
            </div>
            <h2 style="margin: 0; font-size: 1.5rem; color: #1a1a2e;">Definir Nova Senha</h2>
            <p style="margin: 0.5rem 0 0 0; font-size: 0.88rem; color: #666; line-height: 1.4;">
                Sua senha foi redefinida recentemente pelo administrador. Por motivos de segurança, crie uma senha personalizada antes de acessar o sistema.
            </p>
        </div>

        <form id="form-force-password" onsubmit="event.preventDefault(); submitForcePasswordChange();">
            <div class="form-group">
                <label for="force-nova-senha" style="display: block; font-size: 0.75rem; font-weight: 700; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.4rem;">
                    <i class="fas fa-key"></i> Nova Senha
                </label>
                <input type="password" id="force-nova-senha" placeholder="Mínimo 4 caracteres" required minlength="4"
                    style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
            </div>

            <div class="form-group">
                <label for="force-confirmar-senha" style="display: block; font-size: 0.75rem; font-weight: 700; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.4rem;">
                    <i class="fas fa-check-double"></i> Confirmar Nova Senha
                </label>
                <input type="password" id="force-confirmar-senha" placeholder="Digite a senha novamente" required minlength="4"
                    style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" id="btn-submit-force-password" class="btn-primary" 
                    style="width: 100%; padding: 0.85rem; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px; border-radius: 8px; border: none; background: var(--primary, #BC0000); color: #fff; cursor: pointer; transition: background 0.2s;">
                    <i class="fas fa-save"></i> Atualizar Senha e Entrar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
async function submitForcePasswordChange() {
    const novaSenha = document.getElementById('force-nova-senha').value;
    const confirmarSenha = document.getElementById('force-confirmar-senha').value;

    if (novaSenha !== confirmarSenha) {
        if (window.showToast) {
            window.showToast('As senhas não coincidem.', 'error');
        } else {
            alert('As senhas não coincidem.');
        }
        return;
    }

    const btn = document.getElementById('btn-submit-force-password');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gravando...';

    try {
        const response = await fetch(`${window.API_URL}?action=change_own_password`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nova_senha: novaSenha })
        });
        const data = await response.json();

        if (data.success) {
            if (window.showToast) {
                window.showToast(data.message, 'success');
            }
            setTimeout(() => {
                window.location.reload();
            }, 1200);
        } else {
            if (window.showToast) {
                window.showToast(data.message || 'Erro ao alterar a senha.', 'error');
            } else {
                alert(data.message || 'Erro ao alterar a senha.');
            }
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    } catch (err) {
        console.error(err);
        if (window.showToast) {
            window.showToast('Erro ao comunicar com o servidor.', 'error');
        } else {
            alert('Erro ao comunicar com o servidor.');
        }
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}
</script>
