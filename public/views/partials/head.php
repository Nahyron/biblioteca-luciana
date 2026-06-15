<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Controle de Acesso</title>
    
    <!-- Favicon Oficial -->
    <link rel="icon" href="favicon.jpg" type="image/jpeg">

    <!-- Constantes Globais para o JavaScript -->
    <script>
        window.BASE_URL = "<?php echo BASE_URL; ?>";
        window.API_URL = "<?php echo PUBLIC_URL; ?>/api.php";
    </script>

    <!-- Base URL para importação correta de Assets em qualquer ambiente/subpasta -->
    <base href="<?php echo PUBLIC_URL; ?>/">

    <!-- Fontes e Estilos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Design System e Módulos CSS (SOLID) -->
    <link rel="stylesheet" href="assets/css/modules/variables.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/modules/base.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/modules/navigation.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/modules/vision.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/modules/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/modules/tables.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/modules/modals.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/modules/components.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/modules/face-enrollment.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/modules/responsive.css?v=<?php echo time(); ?>">

    <!-- Dependências Externas (CDNs) -->
    <!-- Chart.js: Usado para renderizar os gráficos de fluxo no Dashboard -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Face-api.js: Motor de IA que roda detecção facial diretamente no navegador do usuário -->
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <!-- html2pdf.js: Usado para gerar o relatório PDF a partir do HTML/Canvases do Dashboard -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
