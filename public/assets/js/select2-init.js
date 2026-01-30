/**
 * Select2 Initialization
 * Aplica Select2 em todos os selects da aplicação
 */
(function() {
    'use strict';

    // Aguardar DOM e bibliotecas estarem prontas
    $(document).ready(function() {
        console.log('Inicializando Select2...');

        // Verificar se Select2 está disponível
        if (typeof $.fn.select2 === 'undefined') {
            console.error('Select2 não está disponível!');
            return;
        }

        // 1. CNAE Select (Receita Federal) - Com AJAX
        const cnaesSelect = $('#cnaes_select');
        if (cnaesSelect.length) {
            console.log('Inicializando CNAE select com AJAX...');
            cnaesSelect.select2({
                theme: 'bootstrap-5',
                language: 'pt-BR',
                placeholder: 'Pesquise por código ou descrição do CNAE',
                allowClear: true,
                ajax: {
                    url: function() {
                        const baseUrl = $('base').attr('href') || window.location.origin + '/';
                        return baseUrl + 'receita/buscarCnaes';
                    },
                    dataType: 'json',
                    delay: 250,
                    processResults: function (data) {
                        return { results: data };
                    },
                    cache: true
                }
            });
        }

        // 2. Selects com classe .select2 - Padrão
        $('.select2').each(function() {
            const $select = $(this);
            
            // Pular se já foi inicializado
            if ($select.hasClass('select2-hidden-accessible')) {
                return;
            }

            console.log('Inicializando select:', $select.attr('id') || $select.attr('name'));

            const config = {
                theme: 'bootstrap-5',
                language: 'pt-BR',
                width: '100%',
                placeholder: $select.data('placeholder') || 'Selecione...',
                allowClear: !$select.prop('required')
            };

            // Se for multiple, adicionar configurações específicas
            if ($select.prop('multiple')) {
                config.closeOnSelect = false;
            }

            $select.select2(config);
        });

        // 3. Contact List Select (messages/detail.php) - Especial
        const contactListSelect = $('#contactListSelect');
        if (contactListSelect.length) {
            console.log('Inicializando Contact List select...');
            contactListSelect.select2({
                theme: 'bootstrap-5',
                language: 'pt-BR',
                width: '100%',
                placeholder: 'Selecione as listas',
                closeOnSelect: false,
                allowClear: true
            });
        }

        console.log('Select2 inicializado com sucesso!');
    });
})();
