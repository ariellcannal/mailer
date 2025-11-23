(function () {
    'use strict';

    class EventEmitter {
        constructor() {
            this.listeners = {};
        }

        on(event, callback) {
            if (!this.listeners[event]) {
                this.listeners[event] = [];
            }
            this.listeners[event].push(callback);
        }

        fire(event, ...args) {
            (this.listeners[event] || []).forEach((callback) => callback(...args));
        }
    }

    class Model extends EventEmitter {
        constructor(initial = {}) {
            super();
            this.state = { ...initial };
        }

        set(values = {}) {
            this.state = { ...this.state, ...values };
        }

        get(key) {
            return this.state[key];
        }
    }

    class Collection extends Array {}

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

        set(properties = {}) {
            if (properties.label) {
                this.element.textContent = properties.label;
            }

            if (properties.icon) {
                this.element.innerHTML = `<span class="ck ck-button__icon"><img src="${properties.icon}" alt=""></span>` +
                    `<span class="ck ck-button__label">${properties.label || ''}</span>`;
            }

            if (properties.tooltip) {
                this.element.title = typeof properties.tooltip === 'string' ? properties.tooltip : properties.label || '';
            }
        }
    }

    class DropdownView extends EventEmitter {
        constructor(locale) {
            super();
            this.locale = locale;
            this.buttonView = new ButtonView(locale);
            this.panelView = document.createElement('div');
            this.panelView.className = 'ck-dropdown__panel';
            this.panelView.hidden = true;
            this.element = document.createElement('div');
            this.element.className = 'ck-dropdown';
            this.element.appendChild(this.buttonView.element);
            this.element.appendChild(this.panelView);

            this.buttonView.element.addEventListener('click', (event) => {
                event.preventDefault();
                this.toggle();
            });
        }

        toggle(force) {
            const visible = force ?? this.panelView.hidden;
            this.panelView.hidden = !visible;
        }
    }

    function createDropdown(locale) {
        return new DropdownView(locale);
    }

    function addListToDropdown(dropdown, definitions) {
        dropdown.panelView.innerHTML = '';
        definitions.forEach((definition) => {
            if (definition.type !== 'button') {
                return;
            }

            const model = definition.model;
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'ck ck-button ck-button_with-text';
            button.textContent = model.get('label');
            button.addEventListener('click', (event) => {
                event.preventDefault();
                model.fire('execute');
                dropdown.toggle(false);
            });
            dropdown.panelView.appendChild(button);
        });
    }

    class ComponentFactory {
        constructor(editor) {
            this.editor = editor;
            this.factories = new Map();
        }

        add(name, factory) {
            this.factories.set(name, factory);
        }

        create(name) {
            const factory = this.factories.get(name);
            return factory ? factory(this.editor.locale) : null;
        }
    }

    class Plugin {
        constructor(editor) {
            this.editor = editor;
        }

        static get pluginName() {
            return 'Plugin';
        }

        init() {}
    }

    class ClassicEditor {
        constructor(sourceElement, config = {}) {
            this.sourceElement = sourceElement;
            this.locale = { uiLanguage: config.language || 'pt-br' };
            this.config = config;
            this.commands = new Map();
            this.ui = {
                componentFactory: new ComponentFactory(this)
            };
            this.plugins = [];
            this.data = {
                set: (html) => { this.editable.innerHTML = html; },
                get: () => this.editable.innerHTML,
            };
        }

        static create(element, config = {}) {
            return Promise.resolve().then(() => {
                const editor = new ClassicEditor(element, config);
                editor._initEditor();
                return editor;
            });
        }

        _initEditor() {
            this._createLayout();
            this._registerBuiltinCommands();
            this._mountPlugins();
            this._renderToolbar();
        }

        _createLayout() {
            this.wrapper = document.createElement('div');
            this.wrapper.className = 'ck ck-editor__main';

            this.toolbar = document.createElement('div');
            this.toolbar.className = 'ck ck-toolbar ck-toolbar_grouping mb-2';

            this.editable = document.createElement('div');
            this.editable.className = 'ck ck-editor__editable ck-editor__editable_inline form-control';
            this.editable.contentEditable = 'true';
            this.editable.style.minHeight = `${this.config.height || 300}px`;
            this.editable.innerHTML = this.sourceElement.value;

            this.wrapper.appendChild(this.toolbar);
            this.wrapper.appendChild(this.editable);
            this.sourceElement.style.display = 'none';
            this.sourceElement.parentNode.insertBefore(this.wrapper, this.sourceElement.nextSibling);
        }

        _registerBuiltinCommands() {
            const executeCommand = (command) => {
                this.editable.focus();
                document.execCommand(command);
            };

            this.commands.set('bold', () => executeCommand('bold'));
            this.commands.set('italic', () => executeCommand('italic'));
            this.commands.set('underline', () => executeCommand('underline'));
            this.commands.set('bulletedList', () => executeCommand('insertUnorderedList'));
            this.commands.set('numberedList', () => executeCommand('insertOrderedList'));
            this.commands.set('undo', () => executeCommand('undo'));
            this.commands.set('redo', () => executeCommand('redo'));
        }

        _mountPlugins() {
            const extra = this.config.extraPlugins || [];
            extra.forEach((PluginClass) => {
                const plugin = new PluginClass(this);
                this.plugins.push(plugin);
                if (typeof plugin.init === 'function') {
                    plugin.init();
                }
            });
        }

        _renderToolbar() {
            const items = this.config.toolbar?.items || [];
            items.forEach((itemName) => {
                let element = null;

                if (this.ui.componentFactory.create(itemName)) {
                    const component = this.ui.componentFactory.create(itemName);
                    element = component.element || component.buttonView?.element || null;
                } else if (this.commands.has(itemName)) {
                    const button = new ButtonView();
                    const labels = {
                        bold: 'Negrito',
                        italic: 'Itálico',
                        underline: 'Sublinhado',
                        bulletedList: 'Lista com marcadores',
                        numberedList: 'Lista numerada',
                        undo: 'Desfazer',
                        redo: 'Refazer',
                    };
                    button.set({ label: labels[itemName] || itemName });
                    button.on('execute', () => this.commands.get(itemName)());
                    element = button.element;
                }

                if (element) {
                    this.toolbar.appendChild(element);
                }
            });
        }

        execute(command, payload) {
            const handler = this.commands.get(command);
            if (handler) {
                handler(payload);
            }
        }

        setData(html) {
            this.data.set(html);
        }

        getData() {
            return this.data.get();
        }

        updateSourceElement() {
            this.sourceElement.value = this.getData();
        }

        destroy() {
            this.updateSourceElement();
            this.wrapper.remove();
            this.sourceElement.style.display = '';
        }
    }

    window.CKEDITOR = {
        ClassicEditor,
        Plugin,
        ui: {
            button: { ButtonView },
            dropdown: { DropdownView },
            dropdownUtils: { createDropdown, addListToDropdown },
            Model,
        },
        utils: { Collection },
    };

    window.ClassicEditor = ClassicEditor;
    window.Model = Model;
    window.Collection = Collection;
    window.ButtonView = ButtonView;
    window.createDropdown = createDropdown;
    window.addListToDropdown = addListToDropdown;
})();
