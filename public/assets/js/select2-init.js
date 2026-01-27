/**
 * Inicialização global do Select2
 * Aplica Select2 em todos os <select> da aplicação
 */
(function() {
    'use strict';

    // Configuração padrão do Select2
    const defaultConfig = {
        theme: 'bootstrap-5',
        language: 'pt-BR',
        width: '100%',
        placeholder: 'Selecione uma opção',
        allowClear: true
    };

    /**
     * Inicializa Select2 em um elemento
     */
    function initSelect2(element) {
        const $element = $(element);
        
        // Não inicializar se já foi inicializado
        if ($element.data('select2')) {
            return;
        }

        // Configuração personalizada do elemento
        const customConfig = {
            placeholder: $element.data('placeholder') || $element.attr('data-placeholder') || defaultConfig.placeholder,
            allowClear: !$element.prop('required'),
            multiple: $element.prop('multiple'),
            disabled: $element.prop('disabled')
        };

        // Merge das configurações
        const config = Object.assign({}, defaultConfig, customConfig);

        // Inicializar Select2
        $element.select2(config);
    }

    /**
     * Inicializa todos os selects da página
     */
    function initAllSelects() {
        // Selects com classe .select2
        $('.select2').each(function() {
            initSelect2(this);
        });

        // Todos os outros selects (exceto os que já têm Select2)
        $('select').not('.select2').not('[data-no-select2]').each(function() {
            initSelect2(this);
        });
    }

    // Inicializar quando o DOM estiver pronto
    $(document).ready(function() {
        initAllSelects();
    });

    // Reinicializar quando conteúdo dinâmico for adicionado
    $(document).on('DOMNodeInserted', function(e) {
        const $target = $(e.target);
        
        // Se o elemento inserido é um select
        if ($target.is('select')) {
            initSelect2($target[0]);
        }
        
        // Se o elemento inserido contém selects
        $target.find('select').each(function() {
            initSelect2(this);
        });
    });

    // Expor função global para inicialização manual
    window.initSelect2 = initSelect2;
    window.initAllSelects = initAllSelects;
})();
