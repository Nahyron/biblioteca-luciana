<?php
/**
 * PÁGINA INICIAL DO SISTEMA - LOGIN
 * Ponto de entrada com duas opções: Acesso Admin ou Aluno.
 */
require_once dirname(__DIR__) . '/config/config.php';
require_once SRC_PATH . '/Infrastructure/Auth/SessionAuth.php';

use App\Infrastructure\Auth\SessionAuth;

// Se já estiver autenticado, redireciona direto para o sistema
if (SessionAuth::isAuthenticated()) {
    header('Location: controle.php');
    exit;
}

$erro = $_GET['erro'] ?? null;
$mensagemErro = match($erro) {
    'credenciais_invalidas' => 'Usuário ou senha incorretos.',
    'campos_vazios'         => 'Preencha todos os campos.',
    'erro_servidor'         => 'Erro interno. Tente novamente.',
    default                 => null
};
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso ao Sistema — <?php echo APP_NAME_SHORT; ?></title>
    <link rel="icon" href="favicon.jpg" type="image/jpeg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* ================================================================
           DESIGN SYSTEM — PÁGINA DE LOGIN
           Paleta: Preto profundo + Vermelho Carmim + Branco limpo
           Filosofia: Sharp geometry, sem arredondamentos genéricos,
           tipografia bold como elemento visual principal.
        ================================================================ */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --primary:      #BC0000;
            --primary-glow: rgba(188, 0, 0, 0.35);
            --dark-bg:      #0a0a0c;
            --dark-surface: #111115;
            --dark-card:    #16161a;
            --dark-border:  rgba(255, 255, 255, 0.07);
            --text-primary: #f0f0f0;
            --text-muted:   #6b7280;
            --text-dim:     #374151;
            --white:        #ffffff;
            --success:      #22c55e;
            --error:        #ef4444;
            --ease-spring:  cubic-bezier(0.34, 1.56, 0.64, 1);
            --ease-smooth:  cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--dark-bg);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            overflow: hidden;
        }

        /* ──────────────────────────────────────────
           PAINEL ESQUERDO — IDENTIDADE VISUAL
        ────────────────────────────────────────── */
        .brand-panel {
            flex: 1;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            padding: 5rem 4.5rem;
            background: var(--dark-surface);
            border-right: 1px solid var(--dark-border);
            overflow: hidden;
        }

        /* Canvas de animação neural */
        #neural-canvas {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        /* Gradiente superior para garantir legibilidade do texto */
        .brand-panel::after {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse at 15% 85%, rgba(188, 0, 0, 0.15) 0%, transparent 55%),
                radial-gradient(ellipse at 85% 15%, rgba(188, 0, 0, 0.08) 0%, transparent 45%);
            pointer-events: none;
            z-index: 0;
        }

        .tds25-badge {
            position: absolute;
            top: 2.5rem;
            left: 4.5rem;
            font-size: 0.72rem;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.35);
            letter-spacing: 2px;
            text-transform: uppercase;
            z-index: 10;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            background: var(--primary);
            color: white;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            padding: 0.5rem 1rem;
            margin-bottom: 2.5rem;
            position: relative;
            z-index: 1;
            clip-path: polygon(0 0, calc(100% - 10px) 0, 100% 100%, 10px 100%);
        }

        .brand-headline {
            font-size: clamp(2.8rem, 5vw, 4.2rem);
            font-weight: 900;
            line-height: 1.05;
            letter-spacing: -2px;
            color: var(--white);
            position: relative;
            z-index: 1;
            margin-bottom: 1.5rem;
        }

        .brand-headline span {
            color: var(--primary);
            display: block;
        }

        .brand-desc {
            font-size: 1rem;
            color: var(--text-muted);
            max-width: 380px;
            line-height: 1.7;
            position: relative;
            z-index: 1;
            margin-bottom: 3rem;
        }

        /* Rodapé do painel de marca */
        .brand-stats {
            display: flex;
            gap: 2.5rem;
            position: relative;
            z-index: 1;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--white);
            letter-spacing: -1px;
        }

        .stat-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-divider {
            width: 1px;
            background: var(--dark-border);
            align-self: stretch;
        }

        /* ──────────────────────────────────────────
           PAINEL DIREITO — SELEÇÃO DE ACESSO
        ────────────────────────────────────────── */
        .access-panel {
            width: 520px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3.5rem 3.5rem;
            background: var(--dark-card);
            position: relative;
            overflow-y: auto;
        }

        .access-header {
            margin-bottom: 3rem;
        }

        .access-header h2 {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--white);
            letter-spacing: -0.5px;
            margin-bottom: 0.5rem;
        }

        .access-header p {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        /* ──────────────────────────────────────────
           CARD DE OPÇÃO DE ACESSO
        ────────────────────────────────────────── */
        .access-option {
            border: 1px solid var(--dark-border);
            background: var(--dark-surface);
            padding: 1.6rem 1.8rem;
            display: flex;
            align-items: center;
            gap: 1.4rem;
            cursor: pointer;
            transition: border-color 0.25s, background 0.25s, transform 0.2s;
            margin-bottom: 1rem;
            text-decoration: none;
            color: inherit;
            position: relative;
            overflow: hidden;
        }

        .access-option::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--primary);
            transform: scaleY(0);
            transition: transform 0.25s var(--ease-smooth);
            transform-origin: center;
        }

        .access-option:hover {
            border-color: rgba(188, 0, 0, 0.4);
            background: rgba(188, 0, 0, 0.04);
            transform: translateX(4px);
        }

        .access-option:hover::before {
            transform: scaleY(1);
        }

        .option-icon {
            width: 52px;
            height: 52px;
            background: rgba(188, 0, 0, 0.12);
            border: 1px solid rgba(188, 0, 0, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: var(--primary);
            flex-shrink: 0;
            transition: background 0.25s, transform 0.25s var(--ease-spring);
        }

        .access-option:hover .option-icon {
            background: rgba(188, 0, 0, 0.22);
            transform: scale(1.08) rotate(-2deg);
        }

        .option-info {
            flex: 1;
        }

        .option-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 0.2rem;
        }

        .option-desc {
            font-size: 0.82rem;
            color: var(--text-muted);
        }

        .option-arrow {
            color: var(--text-dim);
            transition: color 0.2s, transform 0.2s;
        }

        .access-option:hover .option-arrow {
            color: var(--primary);
            transform: translateX(3px);
        }

        /* ──────────────────────────────────────────
           SEPARADOR
        ────────────────────────────────────────── */
        .divider {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.5rem 0;
            color: var(--text-dim);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--dark-border);
        }

        /* ──────────────────────────────────────────
           FORMULÁRIO DE LOGIN
        ────────────────────────────────────────── */
        #login-form-wrapper {
            display: none;
            animation: slideDown 0.35s var(--ease-smooth) forwards;
        }

        #login-form-wrapper.visible {
            display: block;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.9rem 1rem;
            background: var(--dark-bg);
            border: 1px solid var(--dark-border);
            color: var(--white);
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-input:focus {
            border-color: rgba(188, 0, 0, 0.6);
            box-shadow: 0 0 0 3px rgba(188, 0, 0, 0.12);
        }

        .form-input::placeholder {
            color: var(--text-dim);
        }

        .btn-login {
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: white;
            border: none;
            font-size: 0.9rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            cursor: pointer;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
            font-family: 'Inter', sans-serif;
        }

        .btn-login:hover {
            background: #991500;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(188, 0, 0, 0.35);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-back {
            width: 100%;
            padding: 0.9rem;
            background: transparent;
            color: var(--text-muted);
            border: 1px solid var(--dark-border);
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            margin-top: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.25s var(--ease-smooth);
            font-family: 'Inter', sans-serif;
        }

        .btn-back:hover {
            color: var(--white);
            border-color: rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.03);
        }

        /* ──────────────────────────────────────────
           ALERTA DE ERRO
        ────────────────────────────────────────── */
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-left: 3px solid var(--error);
            padding: 0.85rem 1rem;
            font-size: 0.85rem;
            color: #fca5a5;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        /* ──────────────────────────────────────────
           RODAPÉ DO PAINEL
        ────────────────────────────────────────── */
        .access-footer {
            margin-top: 3rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--dark-border);
            font-size: 0.78rem;
            color: var(--text-dim);
            text-align: center;
        }

        /* ──────────────────────────────────────────
           RESPONSIVIDADE
        ────────────────────────────────────────── */
        @media (max-width: 860px) {
            body { flex-direction: column; overflow: auto; }
            .brand-panel { padding: 3rem 2rem; flex: none; }
            .brand-headline { font-size: 2.2rem; }
            .brand-stats { display: none; }
            .access-panel { width: 100%; padding: 2.5rem 2rem; }
        }
    </style>
</head>
<body>

    <!-- PAINEL ESQUERDO: Identidade Visual -->
    <section class="brand-panel" id="brand-panel">
        <!-- Canvas da animação de rede neural interativa -->
        <canvas id="neural-canvas"></canvas>

        <div class="tds25-badge">feito pela turma TDS25</div>

        <div class="brand-badge">
            <i class="fas fa-shield-halved"></i>
            Sistema de Controle de Acesso
        </div>

        <h1 class="brand-headline">
            Sistema de Gestão
            <span>Biblioteca</span>
        </h1>

        <p class="brand-desc">
            Plataforma inteligente de reconhecimento facial e controle de entrada de alunos
            com tecnologia de inteligência artificial em tempo real.
        </p>

        <div class="brand-stats">
            <div class="stat-item">
                <span class="stat-value">128</span>
                <span class="stat-label">Pontos Faciais</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <span class="stat-value">99.9%</span>
                <span class="stat-label">Disponibilidade</span>
            </div>
        </div>
    </section>

    <!-- PAINEL DIREITO: Seleção de Acesso -->
    <section class="access-panel">
        <div class="access-header">
            <h2>Como deseja acessar?</h2>
            <p>Selecione o tipo de acesso abaixo</p>
        </div>

        <?php if ($mensagemErro): ?>
            <div class="alert-error">
                <i class="fas fa-circle-exclamation"></i>
                <?php echo htmlspecialchars($mensagemErro); ?>
            </div>
        <?php endif; ?>

        <!-- Opção 1: Login Admin -->
        <div class="access-option" id="btn-open-login" onclick="abrirFormLogin()" role="button" tabindex="0">
            <div class="option-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="option-info">
                <div class="option-title">Login Administrativo/Professor</div>
                <div class="option-desc">Painel completo — Dashboard, alunos, turmas e relatórios</div>
            </div>
            <i class="fas fa-chevron-right option-arrow"></i>
        </div>

        <!-- Formulário de Login (oculto por padrão, expande ao clicar) -->
        <div id="login-form-wrapper">
            <form action="auth.php" method="POST" id="form-login">
                <div class="form-group">
                    <label class="form-label" for="usuario">
                        <i class="fas fa-user"></i> Usuário
                    </label>
                    <input
                        class="form-input"
                        type="text"
                        id="usuario"
                        name="usuario"
                        placeholder="Digite seu usuário"
                        required
                        autocomplete="username"
                        <?php if ($mensagemErro): ?>autofocus<?php endif; ?>
                    >
                </div>
                <div class="form-group">
                    <label class="form-label" for="senha">
                        <i class="fas fa-lock"></i> Senha
                    </label>
                    <input
                        class="form-input"
                        type="password"
                        id="senha"
                        name="senha"
                        placeholder="Digite sua senha"
                        required
                        autocomplete="current-password"
                    >
                </div>
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Entrar no Sistema
                </button>
                <button type="button" class="btn-back" onclick="fecharFormLogin()">
                    <i class="fas fa-arrow-left"></i> Voltar às opções
                </button>
            </form>
        </div>

        <div class="divider" id="divider-or">ou</div>

        <!-- Opção 2: Entrar como Aluno -->
        <a class="access-option" href="totem.php" id="btn-aluno">
            <div class="option-icon" style="background: rgba(34,197,94,0.1); border-color: rgba(34,197,94,0.25); color: #22c55e;">
                <i class="fas fa-face-smile"></i>
            </div>
            <div class="option-info">
                <div class="option-title">Entrar como Aluno</div>
                <div class="option-desc">Acesso rápido ao reconhecimento facial — sem login necessário</div>
            </div>
            <i class="fas fa-chevron-right option-arrow"></i>
        </a>

        <div class="access-footer">
            © <?php echo date('Y'); ?> <?php echo APP_NAME; ?> · Sistema Biométrico de Acesso
        </div>
    </section>

    <script>
        /* ── Login form UI ── */
        const loginWrapper = document.getElementById('login-form-wrapper');
        const btnOpenLogin = document.getElementById('btn-open-login');
        const dividerOr    = document.getElementById('divider-or');
        const btnAluno     = document.getElementById('btn-aluno');

        function abrirFormLogin() {
            btnOpenLogin.style.display = 'none';
            dividerOr.style.display    = 'none';
            btnAluno.style.display     = 'none';
            loginWrapper.classList.add('visible');
            document.getElementById('usuario').focus();
        }

        function fecharFormLogin() {
            loginWrapper.classList.remove('visible');
            btnOpenLogin.style.display = 'flex';
            dividerOr.style.display    = 'flex';
            btnAluno.style.display     = 'flex';
        }

        // Abre o formulário automaticamente se houver erro de login
        <?php if ($mensagemErro && $erro !== 'campos_vazios'): ?>
            document.addEventListener('DOMContentLoaded', abrirFormLogin);
        <?php endif; ?>

        // Suporte a teclado no card de opção
        document.getElementById('btn-open-login').addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') abrirFormLogin();
        });

        /* ── Animação de Rede Neural Interativa ── */
        (() => {
            const canvas  = document.getElementById('neural-canvas');
            const panel   = document.getElementById('brand-panel');
            const ctx     = canvas.getContext('2d');

            const PRIMARY       = '188, 0, 0';
            const NODE_COLOR    = `rgba(${PRIMARY}, 0.75)`;
            const LINE_COLOR    = `rgba(${PRIMARY}, 0.18)`;
            const PULSE_COLOR   = `rgba(${PRIMARY}, 0.55)`;
            const NODE_COUNT    = 52;
            const CONNECT_DIST  = 145;
            const MOUSE_RADIUS  = 130;
            const MOUSE_FORCE   = 0.018;
            const SPEED         = 0.42;

            let mouse = { x: -9999, y: -9999 };
            let nodes = [];
            let W, H;
            let animId;

            function resize() {
                const rect = panel.getBoundingClientRect();
                W = canvas.width  = rect.width;
                H = canvas.height = rect.height;
                init();
            }

            function init() {
                nodes = Array.from({ length: NODE_COUNT }, () => ({
                    x:  Math.random() * W,
                    y:  Math.random() * H,
                    vx: (Math.random() - 0.5) * SPEED,
                    vy: (Math.random() - 0.5) * SPEED,
                    r:  Math.random() * 1.8 + 1.2,
                    pulse: Math.random() * Math.PI * 2,
                    pulseSpeed: Math.random() * 0.02 + 0.008
                }));
            }

            function draw() {
                ctx.clearRect(0, 0, W, H);

                // Update & draw nodes
                for (const n of nodes) {
                    // Mouse repulsion
                    const dx = n.x - mouse.x;
                    const dy = n.y - mouse.y;
                    const dist = Math.sqrt(dx * dx + dy * dy);
                    if (dist < MOUSE_RADIUS && dist > 0.1) {
                        const force = (MOUSE_RADIUS - dist) / MOUSE_RADIUS;
                        n.vx += (dx / dist) * force * MOUSE_FORCE * 6;
                        n.vy += (dy / dist) * force * MOUSE_FORCE * 6;
                    }

                    // Velocity damping
                    n.vx *= 0.985;
                    n.vy *= 0.985;

                    // Ensure minimum speed
                    const speed = Math.sqrt(n.vx * n.vx + n.vy * n.vy);
                    if (speed < 0.1) {
                        n.vx += (Math.random() - 0.5) * 0.05;
                        n.vy += (Math.random() - 0.5) * 0.05;
                    }

                    n.x += n.vx;
                    n.y += n.vy;

                    // Bounce off edges
                    if (n.x < 0)  { n.x = 0;  n.vx *= -1; }
                    if (n.x > W)  { n.x = W;  n.vx *= -1; }
                    if (n.y < 0)  { n.y = 0;  n.vy *= -1; }
                    if (n.y > H)  { n.y = H;  n.vy *= -1; }

                    // Pulse animation
                    n.pulse += n.pulseSpeed;
                    const pulseFactor = 0.7 + 0.3 * Math.sin(n.pulse);

                    // Draw node glow
                    const grad = ctx.createRadialGradient(n.x, n.y, 0, n.x, n.y, n.r * 4.5);
                    grad.addColorStop(0, `rgba(${PRIMARY}, ${0.5 * pulseFactor})`);
                    grad.addColorStop(1, `rgba(${PRIMARY}, 0)`);
                    ctx.beginPath();
                    ctx.arc(n.x, n.y, n.r * 4.5, 0, Math.PI * 2);
                    ctx.fillStyle = grad;
                    ctx.fill();

                    // Draw node core
                    ctx.beginPath();
                    ctx.arc(n.x, n.y, n.r * pulseFactor, 0, Math.PI * 2);
                    ctx.fillStyle = NODE_COLOR;
                    ctx.fill();
                }

                // Draw connections
                for (let i = 0; i < nodes.length; i++) {
                    for (let j = i + 1; j < nodes.length; j++) {
                        const a = nodes[i], b = nodes[j];
                        const dx = a.x - b.x;
                        const dy = a.y - b.y;
                        const d  = Math.sqrt(dx * dx + dy * dy);
                        if (d < CONNECT_DIST) {
                            const alpha = (1 - d / CONNECT_DIST) * 0.55;
                            ctx.beginPath();
                            ctx.moveTo(a.x, a.y);
                            ctx.lineTo(b.x, b.y);
                            ctx.strokeStyle = `rgba(${PRIMARY}, ${alpha})`;
                            ctx.lineWidth   = 0.7;
                            ctx.stroke();
                        }
                    }
                }

                // Draw mouse proximity highlight
                for (const n of nodes) {
                    const dx = n.x - mouse.x;
                    const dy = n.y - mouse.y;
                    const d  = Math.sqrt(dx * dx + dy * dy);
                    if (d < MOUSE_RADIUS) {
                        const alpha = (1 - d / MOUSE_RADIUS) * 0.7;
                        ctx.beginPath();
                        ctx.moveTo(mouse.x, mouse.y);
                        ctx.lineTo(n.x, n.y);
                        ctx.strokeStyle = `rgba(${PRIMARY}, ${alpha})`;
                        ctx.lineWidth   = 1.2;
                        ctx.stroke();

                        // Mouse center glow
                        const g = ctx.createRadialGradient(mouse.x, mouse.y, 0, mouse.x, mouse.y, MOUSE_RADIUS);
                        g.addColorStop(0, `rgba(${PRIMARY}, 0.06)`);
                        g.addColorStop(1, `rgba(${PRIMARY}, 0)`);
                        ctx.beginPath();
                        ctx.arc(mouse.x, mouse.y, MOUSE_RADIUS, 0, Math.PI * 2);
                        ctx.fillStyle = g;
                        ctx.fill();
                    }
                }

                animId = requestAnimationFrame(draw);
            }

            // Mouse tracking (relative to panel)
            panel.addEventListener('mousemove', (e) => {
                const rect = panel.getBoundingClientRect();
                mouse.x = e.clientX - rect.left;
                mouse.y = e.clientY - rect.top;
            });

            panel.addEventListener('mouseleave', () => {
                mouse.x = -9999;
                mouse.y = -9999;
            });

            // Start
            window.addEventListener('resize', () => {
                cancelAnimationFrame(animId);
                resize();
                draw();
            });

            resize();
            draw();
        })();

        // Higienização em tempo real do campo Usuário para evitar injeções e caracteres nocivos
        document.getElementById('usuario').addEventListener('input', function(e) {
            const originalVal = e.target.value;
            // Permite apenas letras, números, pontos (.), underlines (_), hífens (-) e arrobas (@)
            const sanitizedVal = originalVal.replace(/[^A-Za-z0-9\._\-@]/g, '');
            if (originalVal !== sanitizedVal) {
                e.target.value = sanitizedVal;
            }
        });
    </script>

</body>
</html>
