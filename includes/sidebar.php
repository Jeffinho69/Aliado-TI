<?php
// Define qual página está ativa
$pagina_atual = basename($_SERVER['PHP_SELF']);
// Garante que a variável nível existe
$nivel = $_SESSION['user_nivel'] ?? 'usuario';
?>
<div class="sidebar d-flex flex-column" style="height: 100vh; overflow-y: auto;">
    
    <div class="sidebar-header text-center py-4 border-bottom border-secondary">
        <img src="../assets/img/camara.png" alt="Brasão Câmara" width="70" height="70" style="max-height: 70px; width: auto; object-fit: contain;" class="mb-2">
        
        <h6 class="text-white fw-bold mb-0 text-uppercase" style="font-size: 0.85rem; letter-spacing: 1px;">
            Câmara Municipal
        </h6>
        <small class="text-white-50" style="font-size: 0.7rem;">Vitória de Santo Antão</small>
        
        <div class="mt-3 pt-3 border-top border-secondary border-opacity-25">
            
            <div class="fw-bold text-info"><i class="fas fa-shield-alt"></i>Aliado TI</div>
        </div>
    </div>

    <div class="flex-grow-1 mt-3">
        <div class="text-uppercase small fw-bold text-white-50 px-4 mt-2 mb-1">Menu Principal</div>

        <a href="chamados.php" class="<?= ($pagina_atual == 'chamados.php' || $pagina_atual == 'ver_chamado.php' || $pagina_atual == 'abrir_chamado.php') ? 'active' : '' ?>">
            <i class="fas fa-headset"></i> Service Desk
        </a>
        <a href="transcricao.php" class="<?= $pagina_atual == 'transcricao.php' ? 'active' : '' ?>">
            <i class="fas fa-robot"></i> Central IA
        </a>
        
        <a href="chat_ia.php" class="<?= $pagina_atual == 'chat_ia.php' ? 'active' : '' ?>">
            <i class="fas fa-robot"></i> Assistente Virtual
        </a>

        <?php if($nivel != 'usuario'): ?>
            <div class="text-uppercase small fw-bold text-white-50 px-4 mt-4 mb-1">Gerência TI</div>
            
            <a href="inventario.php" class="<?= $pagina_atual == 'inventario.php' ? 'active' : '' ?>">
                <i class="fas fa-boxes"></i> Inventário
            </a>
            <a href="monitoramento.php" class="<?= $pagina_atual == 'monitoramento.php' ? 'active' : '' ?>">
                <i class="fas fa-network-wired"></i> Monitoramento
            </a>
            <a href="cofre.php" class="<?= $pagina_atual == 'cofre.php' ? 'active' : '' ?>">
                <i class="fas fa-key"></i> Cofre de Senhas
            </a>
            <a href="manutencao.php" class="<?= $pagina_atual == 'manutencao.php' ? 'active' : '' ?>">
                <i class="fas fa-clipboard-check"></i> Manutenção
            </a>
            <a href="relatorios.php" class="<?= $pagina_atual == 'relatorios.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-pie"></i> Relatórios
            </a>
            <a href="utilitarios.php" class="<?= $pagina_atual == 'utilitarios.php' ? 'active' : '' ?>">
                <i class="fas fa-tools"></i> Utilitários
            </a>
            
            <?php if($nivel == 'admin'): ?>
                <a href="usuarios.php" class="<?= $pagina_atual == 'usuarios.php' ? 'active' : '' ?>">
                    <i class="fas fa-users-cog"></i> Usuários
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="p-3 mt-auto border-top border-secondary">
        <a href="../logout.php" class="btn btn-danger w-100 text-white fw-bold">
            <i class="fas fa-sign-out-alt me-2"></i> Sair do Sistema
        </a>
    </div>
</div>