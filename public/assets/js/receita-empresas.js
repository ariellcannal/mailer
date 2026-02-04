/**
 * Gerenciamento de consulta de empresas importadas
 */
(function() {
    'use strict';
    
    // Buscar base URL do backend
    const baseUrl = document.querySelector('base')?.href || window.location.origin + '/';
    
    let currentPage = 1;
    let currentFilters = {};
    
    $(document).ready(function() {
        initSelect2();
        initFormSubmit();
    });
    
    /**
     * Inicializa Select2 para CNAEs e UF
     */
    function initSelect2() {
        // Select2 para CNAEs com AJAX
        $('#filtro_cnae').select2({
            theme: 'bootstrap-5',
            ajax: {
                url: baseUrl + 'receita/buscarCnaes',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                cache: true
            },
            placeholder: 'Digite para buscar CNAEs',
            minimumInputLength: 2,
            allowClear: true,
            language: {
                inputTooShort: function() {
                    return 'Digite pelo menos 2 caracteres';
                },
                searching: function() {
                    return 'Buscando...';
                },
                noResults: function() {
                    return 'Nenhum resultado encontrado';
                }
            }
        });
        
        // Select2 para UF
        $('#filtro_uf').select2({
            theme: 'bootstrap-5',
            placeholder: 'Selecione um estado',
            allowClear: true
        });
    }
    
    /**
     * Inicializa submit do formulário de filtros
     */
    function initFormSubmit() {
        $('#form-filtros').on('submit', function(e) {
            e.preventDefault();
            currentPage = 1;
            buscarEmpresas();
        });
    }
    
    /**
     * Buscar empresas com filtros
     */
    function buscarEmpresas(page = 1) {
        currentPage = page;
        
        // Coletar filtros
        currentFilters = {
            nome: $('#filtro_nome').val(),
            cnpj_basico: $('#filtro_cnpj').val(),
            cnae: $('#filtro_cnae').val() || [],
            uf: $('#filtro_uf').val(),
            page: currentPage
        };
        
        // Mostrar loading
        $('#loading').show();
        $('#resultados').hide();
        
        // Fazer requisição AJAX
        $.ajax({
            url: baseUrl + 'receita/buscarEmpresas',
            method: 'GET',
            data: currentFilters,
            dataType: 'json',
            success: function(response) {
                $('#loading').hide();
                $('#resultados').show();
                
                if (response.success) {
                    renderEmpresas(response.data);
                    renderPaginacao(response.pagination);
                } else {
                    alertify.error(response.message || 'Erro ao buscar empresas');
                }
            },
            error: function(xhr, status, error) {
                $('#loading').hide();
                $('#resultados').show();
                alertify.error('Erro ao buscar empresas: ' + error);
                console.error('XHR error:', xhr.responseText);
            }
        });
    }
    
    /**
     * Renderizar tabela de empresas
     */
    function renderEmpresas(empresas) {
        const tbody = $('#tbody-empresas');
        tbody.empty();
        
        if (empresas.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p>Nenhuma empresa encontrada com os filtros selecionados</p>
                    </td>
                </tr>
            `);
            return;
        }
        
        empresas.forEach(function(empresa) {
            const cnpj = `${empresa.cnpj_basico}/${empresa.cnpj_ordem}-${empresa.cnpj_dv}`;
            const telefone1 = empresa.ddd_telefone_1 && empresa.telefone_1 
                ? `(${empresa.ddd_telefone_1}) ${empresa.telefone_1}` 
                : '-';
            const telefone2 = empresa.ddd_telefone_2 && empresa.telefone_2 
                ? `(${empresa.ddd_telefone_2}) ${empresa.telefone_2}` 
                : '-';
            const fax = empresa.ddd_fax && empresa.fax 
                ? `(${empresa.ddd_fax}) ${empresa.fax}` 
                : '-';
            const email = empresa.correio_eletronico || '-';
            const nomeFantasia = empresa.nome_fantasia || empresa.razao_social || '-';
            
            tbody.append(`
                <tr>
                    <td><code>${cnpj}</code></td>
                    <td>${nomeFantasia}</td>
                    <td>${telefone1}</td>
                    <td>${telefone2}</td>
                    <td>${fax}</td>
                    <td><small>${email}</small></td>
                    <td>
                        <a href="${baseUrl}receita/empresa/${empresa.cnpj_basico}/${empresa.cnpj_ordem}/${empresa.cnpj_dv}" 
                           class="btn btn-sm btn-outline-primary" 
                           title="Visualizar detalhes">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
            `);
        });
    }
    
    /**
     * Renderizar paginação
     */
    function renderPaginacao(pagination) {
        if (!pagination || pagination.total_pages <= 1) {
            $('#paginacao').hide();
            return;
        }
        
        $('#paginacao').show();
        
        // Info de paginação
        const inicio = ((pagination.current_page - 1) * pagination.per_page) + 1;
        const fim = Math.min(pagination.current_page * pagination.per_page, pagination.total);
        $('#info-paginacao').text(`Mostrando ${inicio} a ${fim} de ${pagination.total} empresas`);
        
        // Links de paginação
        const links = $('#pagination-links');
        links.empty();
        
        // Botão Anterior
        if (pagination.current_page > 1) {
            links.append(`
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${pagination.current_page - 1}">Anterior</a>
                </li>
            `);
        }
        
        // Páginas
        const maxPages = 5;
        let startPage = Math.max(1, pagination.current_page - Math.floor(maxPages / 2));
        let endPage = Math.min(pagination.total_pages, startPage + maxPages - 1);
        
        if (endPage - startPage < maxPages - 1) {
            startPage = Math.max(1, endPage - maxPages + 1);
        }
        
        for (let i = startPage; i <= endPage; i++) {
            const active = i === pagination.current_page ? 'active' : '';
            links.append(`
                <li class="page-item ${active}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `);
        }
        
        // Botão Próximo
        if (pagination.current_page < pagination.total_pages) {
            links.append(`
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${pagination.current_page + 1}">Próximo</a>
                </li>
            `);
        }
        
        // Event listener para links de paginação
        links.find('a').on('click', function(e) {
            e.preventDefault();
            const page = parseInt($(this).data('page'));
            buscarEmpresas(page);
        });
    }
})();
