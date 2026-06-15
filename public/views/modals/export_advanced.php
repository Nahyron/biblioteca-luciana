<!-- MODAL DE EXPORTAÇÃO AVANÇADA -->
<div id="modal-export-advanced" class="modal-overlay">
    <div class="modal-content" style="max-width: 450px;">
        <h2>Exportar Relatório Geral</h2>
        <p style="margin-bottom: 1.5rem; color: #666; font-size: 0.9rem;">Escolha o período e a data de referência
            para gerar a planilha de acessos.</p>

        <form id="export-form" onsubmit="event.preventDefault(); runAdvancedExport();">
            <div class="form-group">
                <label>Tipo de Relatório</label>
                <div style="display: flex; gap: 10px; margin-top: 5px;">
                    <label
                        style="flex: 1; border: 1px solid #ddd; padding: 10px; border-radius: 8px; cursor: pointer; text-align: center;">
                        <input type="radio" name="export-period" value="today" checked
                            style="display: block; margin: 0 auto 5px;"> Dia
                    </label>
                    <label
                        style="flex: 1; border: 1px solid #ddd; padding: 10px; border-radius: 8px; cursor: pointer; text-align: center;">
                        <input type="radio" name="export-period" value="week"
                            style="display: block; margin: 0 auto 5px;"> Semana
                    </label>
                    <label
                        style="flex: 1; border: 1px solid #ddd; padding: 10px; border-radius: 8px; cursor: pointer; text-align: center;">
                        <input type="radio" name="export-period" value="month"
                            style="display: block; margin: 0 auto 5px;"> Mês
                    </label>
                </div>
            </div>

            <div class="form-group" style="margin-top: 1.5rem;">
                <label for="export-date">Data de Referência</label>
                <input type="date" id="export-date" required class="form-control"
                    value="<?php echo date('Y-m-d'); ?>">
                <small style="color: #999; display: block; margin-top: 5px;">O sistema usará esta data para
                    localizar o dia, a semana ou o mês correspondente.</small>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 2rem;">
                <button type="submit" class="btn-primary" style="flex: 2;">Gerar Planilha</button>
                <button type="button" class="btn-secondary" style="flex: 1;"
                    onclick="closeExportModal()">Sair</button>
            </div>
        </form>
    </div>
</div>
