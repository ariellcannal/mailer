(function () {
    'use strict';

    class EventEmitter {
        constructor() { this.listeners = {}; }
        on(event, callback) {
            if (!this.listeners[event]) { this.listeners[event] = []; }
            this.listeners[event].push(callback);
        }
        fire(event, ...args) {
            (this.listeners[event] || []).forEach((cb) => cb(...args));
        }
    }

    class Model extends EventEmitter {
        constructor(initialState = {}) {
            super();
            this.state = { ...initialState };
        }
        set(values = {}) { this.state = { ...this.state, ...values }; }
        get(key) { return this.state[key]; }
    }

    class Collection {
        constructor() { this.items = []; }
        add(item) { this.items.push(item); }
        [Symbol.iterator]() { return this.items[Symbol.iterator](); }
    }

    class ButtonView extends EventEmitter {
        constructor(locale) {
            super();
            this.locale = locale;
            this.element = document.createElement('button');
            this.element.type = 'button';
            this.element.className = 'ck ck-button ck-button_with-text';
            this.element.addEventListener('click', (event) => {
                event.preventDefault();
                this.fire('execute');
            });
        }
        set(props = {}) {
            this.props = { ...(this.props || {}), ...props };
            const label = this.props.label || '';
            const icon = this.props.icon ? `<img src="${this.props.icon}" alt="">` : '';
            this.element.innerHTML = `<span class="ck ck-button__icon">${icon}</span><span class="ck ck-button__label">${label}</span>`;
            if (this.props.tooltip) {
                this.element.title = typeof this.props.tooltip === 'string' ? this.props.tooltip : label;
            }
        }
    }

    function createDropdown(locale) {
        const buttonView = new ButtonView(locale);
        const dropdown = { buttonView, element: document.createElement('div') };
        dropdown.element.className = 'ck ck-dropdown';
        dropdown.buttonView.element.classList.add('ck-dropdown__button');
        const panel = document.createElement('div');
        panel.className = 'ck-dropdown__panel';
        panel.style.display = 'none';
        dropdown.element.appendChild(dropdown.buttonView.element);
        dropdown.element.appendChild(panel);
        dropdown.panel = panel;
        dropdown.toggle = function () {
            const isOpen = panel.style.display === 'block';
            panel.style.display = isOpen ? 'none' : 'block';
        };
        dropdown.buttonView.on('execute', dropdown.toggle);
        return dropdown;
    }

    function addListToDropdown(dropdown, collection) {
        dropdown.panel.innerHTML = '';
        collection.items.forEach((item) => {
            if (item.type !== 'button') { return; }
            const model = item.model;
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'ck ck-button ck-button_with-text';
            const label = model.state?.label || '';
            button.textContent = label;
            button.addEventListener('click', (event) => {
                event.preventDefault();
                dropdown.panel.style.display = 'none';
                model.fire('execute');
            });
            dropdown.panel.appendChild(button);
        });
    }

    class ComponentFactory {
        constructor(editor) {
            this.editor = editor;
            this.factories = {};
        }
        add(name, factory) { this.factories[name] = factory; }
        create(name) { return this.factories[name]?.(this.editor.locale); }
    }

    class Plugin {
        constructor(editor) { this.editor = editor; }
    }

    class ClassicEditor {
        static create(element, config = {}) {
            return new Promise((resolve) => {
                const editor = new ClassicEditor(element, config);
                resolve(editor);
            });
        }

        constructor(element, config = {}) {
            this.sourceElement = element;
            this.locale = (config.language || 'pt-br');
            this.ui = { componentFactory: new ComponentFactory(this) };
            this.model = {};
            this.data = { get: () => this.getData(), set: (value) => this.setData(value) };
            this.plugins = [];

            element.style.display = 'none';

            const wrapper = document.createElement('div');
            wrapper.className = 'ck ck-editor';
            const toolbar = document.createElement('div');
            toolbar.className = 'ck ck-toolbar ck-toolbar_grouping mb-2';
            const editable = document.createElement('div');
            editable.className = 'ck ck-editor__editable ck-editor__editable_inline form-control';
            editable.contentEditable = 'true';
            editable.innerHTML = element.value;
            wrapper.appendChild(toolbar);
            wrapper.appendChild(editable);
            element.parentNode.insertBefore(wrapper, element.nextSibling);

            this.__fallbackElement = editable;
            this.wrapper = wrapper;
            this.toolbar = toolbar;

            (config.extraPlugins || []).forEach((PluginCtor) => {
                try {
                    const plugin = new PluginCtor(this);
                    plugin.init?.();
                    this.plugins.push(plugin);
                } catch (error) {
                    console.error('Falha ao inicializar plugin customizado.', error);
                }
            });

            this.buildToolbar(config.toolbar?.items || []);
        }

        buildToolbar(items) {
            items.forEach((item) => {
                if (item === '|') { return; }
                const factoryView = this.ui.componentFactory.create(item);
                if (factoryView) {
                    if (factoryView.element) {
                        this.toolbar.appendChild(factoryView.element);
                    } else if (factoryView.buttonView) {
                        const container = document.createElement('div');
                        container.className = 'ck-dropdown-wrapper';
                        container.appendChild(factoryView.element || factoryView.buttonView.element);
                        if (factoryView.panel) {
                            container.appendChild(factoryView.panel);
                        }
                        this.toolbar.appendChild(container);
                    }
                    return;
                }

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'ck ck-button ck-button_with-text';
                button.textContent = item;
                button.addEventListener('click', () => this.handleCommand(item));
                this.toolbar.appendChild(button);
            });
        }

        handleCommand(command) {
            const exec = (cmd) => document.execCommand(cmd, false, null);
            switch (command) {
                case 'bold': exec('bold'); break;
                case 'italic': exec('italic'); break;
                case 'undo': exec('undo'); break;
                case 'redo': exec('redo'); break;
                case 'bulletedList': exec('insertUnorderedList'); break;
                case 'numberedList': exec('insertOrderedList'); break;
                case 'link': {
                    const url = prompt('Informe a URL do link:');
                    if (url) { document.execCommand('createLink', false, url); }
                    break;
                }
                default: break;
            }
        }

        getData() { return this.__fallbackElement.innerHTML; }
        setData(data) { this.__fallbackElement.innerHTML = data; }
        updateSourceElement() { this.sourceElement.value = this.getData(); }
    }

    window.CKEDITOR = {
        core: { Plugin },
        ui: {
            button: { ButtonView },
            dropdownUtils: { createDropdown, addListToDropdown },
            Model,
            Collection
        },
        utils: { Collection },
        ClassicEditor
    };
    window.ClassicEditor = ClassicEditor;
})();
