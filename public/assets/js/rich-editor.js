(function ($) {
  "use strict";

  const editorReadyDeferred = (() => {
    let resolveReady;
    let rejectReady;
    const promise = new Promise((resolve, reject) => {
      resolveReady = resolve;
      rejectReady = reject;
    });

    return { promise, resolveReady, rejectReady };
  })();

  window.richEditorReady = editorReadyDeferred.promise;

  if (typeof CKEDITOR === "undefined") {
    console.error(
      "CKEDITOR global is not available. Make sure ckeditor5.umd.js is loaded before rich-editor.js.",
    );
    editorReadyDeferred.resolveReady(null);
    return;
  }

  const DEFAULT_HEX_COLORS = [
    { color: "#000000", label: "Black" },
    { color: "#4D4D4D", label: "Dim grey" },
    { color: "#999999", label: "Grey" },
    { color: "#E6E6E6", label: "Light grey" },
    { color: "#FFFFFF", label: "White", hasBorder: true },
    { color: "#E65C5C", label: "Red" },
    { color: "#E69C5C", label: "Orange" },
    { color: "#E6E65C", label: "Yellow" },
    { color: "#C2E65C", label: "Light green" },
    { color: "#5CE65C", label: "Green" },
    { color: "#5CE6A6", label: "Aquamarine" },
    { color: "#5CE6E6", label: "Turquoise" },
    { color: "#5CA6E6", label: "Light blue" },
    { color: "#5C5CE6", label: "Blue" },
    { color: "#A65CE6", label: "Purple" },
  ];

  const {
    ClassicEditor,
    Autosave,
    Essentials,
    Paragraph,
    Autoformat,
    TextTransformation,
    Bold,
    Table,
    TableToolbar,
    FontBackgroundColor,
    FontColor,
    FontFamily,
    FontSize,
    GeneralHtmlSupport,
    Heading,
    ImageInline,
    ImageToolbar,
    CloudServices,
    Link,
    ImageUpload,
    ImageInsertViaUrl,
    AutoImage,
    ImageTextAlternative,
    ImageStyle,
    ImageResize,
    Indent,
    IndentBlock,
    Italic,
    AutoLink,
    List,
    ImageUtils,
    ImageEditing,
    PlainTableOutput,
    Strikethrough,
    Style,
    TableCaption,
    Alignment,
    Underline,
    Fullscreen,
    Emoji,
    Mention,
    MediaEmbed,
    Markdown,
    PasteFromMarkdownExperimental,
    Code,
    Subscript,
    Superscript,
    Highlight,
    BlockQuote,
    HorizontalLine,
    CodeBlock,
    ImageBlock,
    LinkImage,
    ImageCaption,
    TodoList,
    ShowBlocks,
    SourceEditing,
    TextPartLanguage,
    Title,
    BalloonToolbar,
    BlockToolbar,

    Command,
    Plugin,
    createDropdown,
    Collection,
    UIModel,
    addListToDropdown,
    ButtonView,
    ColorPickerView,
    dropdownView: DropdownView,
    FocusCycler,
    KeystrokeHandler,
    LabeledFieldView,
    View,
    ViewCollection,
    FocusTracker,
    submitHandler,
    normalizeColorOptions,
    removeButtonEnablement,
    ColorGridView,
  } = CKEDITOR;

  const editorPlugins = [
    Alignment,
    Autoformat,
    AutoImage,
    AutoLink,
    Autosave,
    BalloonToolbar,
    BlockQuote,
    BlockToolbar,
    Bold,
    CloudServices,
    Code,
    CodeBlock,
    Emoji,
    Essentials,
    FontBackgroundColor,
    FontColor,
    FontFamily,
    FontSize,
    Fullscreen,
    GeneralHtmlSupport,
    Heading,
    Highlight,
    HorizontalLine,
    ImageBlock,
    ImageCaption,
    ImageEditing,
    ImageInline,
    ImageInsertViaUrl,
    ImageStyle,
    ImageTextAlternative,
    ImageToolbar,
    ImageUpload,
    ImageUtils,
    ImageResize,
    Indent,
    IndentBlock,
    Italic,
    Link,
    LinkImage,
    List,
    MediaEmbed,
    Mention,
    Paragraph,
    PasteFromMarkdownExperimental,
    PlainTableOutput,
    ShowBlocks,
    SourceEditing,
    Strikethrough,
    Style,
    Subscript,
    Superscript,
    Table,
    TableCaption,
    TableToolbar,
    TextPartLanguage,
    TextTransformation,
    TodoList,
    Underline,
  ].filter(Boolean);

  const configElement = document.getElementById("richEditorConfig");
  if (!configElement) {
    editorReadyDeferred.resolveReady(null);
    return;
  }

  const settings = {
    licence: configElement.dataset.licence || "GPL",
    height: Number(configElement.dataset.height || 600),
    templateSearchUrl: configElement.dataset.templateSearchUrl || "",
    fileListUrl: configElement.dataset.fileListUrl || "",
    fileUploadUrl: configElement.dataset.fileUploadUrl || "",
  };

  let editorInstance = null;

  /**
   * Recupera o elemento de origem associado ao editor.
   *
   * @returns {HTMLTextAreaElement|null}
   */
  const getEditorSourceElement = () =>
    document.getElementById("richEditor") ||
    document.querySelector('textarea[name="html_content"]');

  /**
   * Retorna o HTML atual do editor, mesmo antes da instância carregar.
   *
   * @returns {string}
   */
  window.getRichEditorData = function getRichEditorData() {
    if (editorInstance?.getData) {
      return editorInstance.getData();
    }

    const sourceElement = getEditorSourceElement();
    return sourceElement ? sourceElement.value : "";
  };

  /**
   * Sincroniza o editor com o campo original para submissões e salvamentos parciais.
   *
   * @returns {void}
   */
  window.syncRichEditors = function syncRichEditors() {
    const sourceElement = getEditorSourceElement();
    if (sourceElement) {
      sourceElement.value = window.getRichEditorData();
    }
  };

  /**
   * Garante a altura configurada para o editor, inclusive ao alternar para o modo de código-fonte.
   *
   * @param {object} editor Instância do editor.
   * @param {number} height Altura desejada em pixels.
   * @returns {void}
   */
  const enforceEditorHeight = (editor, height) => {
    editor.editing.view.change((writer) => {
      const rootElement = editor.editing.view.document.getRoot();

      if (rootElement) {
        writer.setStyle("height", `${height}px`, rootElement);
        writer.setStyle("min-height", `${height}px`, rootElement);
      }
    });

    const sourceEditing = editor.plugins.get("SourceEditing");

    if (sourceEditing) {
      const applySourceAreaHeight = () => {
        const sourceArea = editor.ui.view.element?.querySelector(
          ".ck-source-editing-area",
        );

        if (sourceArea) {
          sourceArea.style.height = `${height}px`;
          sourceArea.style.minHeight = `${height}px`;
          sourceArea.style.overflow = "auto";
        }
      };

      applySourceAreaHeight();
      sourceEditing.on("change:isSourceEditingMode", applySourceAreaHeight);
    }
  };

  const icons = {
    library:
      '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M17.5 9.303V8h-13v8.5h4.341c.191.54.457 1.044.785 1.5H2a1.5 1.5 0 0 1-1.5-1.5v-13A1.5 1.5 0 0 1 2 2h4.5a1.5 1.5 0 0 1 1.06.44L9.122 4H16a1.5 1.5 0 0 1 1.5 1.5v1A1.5 1.5 0 0 1 19 8v2.531a6 6 0 0 0-1.5-1.228M16 6.5v-1H8.5l-2-2H2v13h1V8a1.5 1.5 0 0 1 1.5-1.5z"></path><path d="M14.5 19.5a5 5 0 1 1 0-10 5 5 0 0 1 0 10M15 14v-2h-1v2h-2v1h2v2h1v-2h2v-1z"></path></svg>',
    templates:
      '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M3 19a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v8.022a6.5 6.5 0 0 0-1.5-.709V2a.5.5 0 0 0-.5-.5H3a.5.5 0 0 0-.5.5v15a.5.5 0 0 0 .5.5h6.313c.173.534.412 1.037.709 1.5z"></path><path d="M9.174 14a6.5 6.5 0 0 0-.155 1H6v-1zm.848-2a6.5 6.5 0 0 0-.524 1H4v-1zm2.012-2c-.448.283-.86.62-1.224 1H6v-1zM12 4v1H4V4zm2 3V6H6v1zm1 2V8H7v1z"></path><path d="M20 15.5a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0M15.5 13a.5.5 0 0 0-.5.5V15h-1.5a.5.5 0 0 0 0 1H15v1.5a.5.5 0 0 0 1 0V16h1.5a.5.5 0 0 0 0-1H16v-1.5a.5.5 0 0 0-.5-.5" clip-rule="evenodd"></path></svg>',
    tags: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><circle cx="10" cy="9.8" r="1.5"></circle><path d="M13.25 2.75V2h.035a6 6 0 0 1 .363.014c.21.013.517.041.785.109.397.1.738.281 1.007.55s.429.587.524.907c.182.608.15 1.314.108 1.913l-.03.408c-.038.487-.073.93-.053 1.353.026.527.136.879.333 1.112.223.263.494.428.72.528a2 2 0 0 0 .335.117l.01.002.613.109v.628h-2.402a3.3 3.3 0 0 1-.42-.415c-.509-.601-.655-1.345-.687-2.009-.025-.527.02-1.094.059-1.592l.026-.347c.044-.621.044-1.067-.049-1.377a.63.63 0 0 0-.148-.276.64.64 0 0 0-.313-.157a3 3 0 0 0-.512-.066 6 6 0 0 0-.286-.01h-.016L13.25 3.5h-.75V2h.75z"></path><path d="M13.25 16.75v.75h.035a7 7 0 0 0 .363-.014 4.6 4.6 0 0 0 .785-.109c.397-.1.738-.28 1.007-.55.268-.269.429-.587.524-.907.182-.608.15-1.314.108-1.912l-.03-.41c-.038-.486-.073-.93-.053-1.352.026-.527.136-.879.333-1.112.223-.263.494-.428.72-.528a2 2 0 0 1 .335-.117l.01-.002.613-.109V9.75h-2.402a3.3 3.3 0 0 0-.42.416c-.509.6-.655 1.344-.687 2.008-.025.527.02 1.095.059 1.592l.026.347c.044.621.044 1.067-.049 1.378a.63.63 0 0 1-.148.275.64.64 0 0 1-.313.157a3 3 0 0 1-.512.066 6 6 0 0 1-.286.01l-.016.001H12.5v1.5h.75zm-6.5-14V2h-.035a6 6 0 0 0-.363.014 4.6 4.6 0 0 0-.785.109 2.13 2.13 0 0 0-1.008.55 2.1 2.1 0 0 0-.524.907c-.181.608-.15 1.314-.108 1.913l.031.408c.038.487.073.93.052 1.353-.025.527-.136.879-.333 1.112a2 2 0 0 1-.718.528 2 2 0 0 1-.337.117l-.01.002L2 9.122v.628h2.402a3.3 3.3 0 0 0 .42-.415c.509-.601.654-1.345.686-2.009.026-.527-.019-1.094-.058-1.592q-.015-.18-.026-.347c-.044-.621-.044-1.067.048-1.377a.63.63 0 0 1 .149-.276.64.64 0 0 1 .312-.157c.13-.032.323-.054.513-.066a6 6 0 0 1 .286-.01h.015L6.75 3.5h.75V2h-.75zm0 14v.75h-.035a7 7 0 0 1-.363-.014 4.6 4.6 0 0 1-.785-.109 2.13 2.13 0 0 1-1.008-.55 2.1 2.1 0 0 1-.524-.907c-.181-.608-.15-1.314-.108-1.912l.031-.41c.038-.486.073-.93.052-1.352-.025-.527-.136-.879-.333-1.112a2 2 0 0 0-.718-.528 2 2 0 0 0-.337-.117l-.01-.002L2 10.378V9.75h2.402q.218.178.42.416c.509.6.654 1.344.686 2.008.026.527-.019 1.095-.058 1.592q-.015.18-.026.347c-.044.621-.044 1.067.048 1.378a.63.63 0 0 0 .149.275.64.64 0 0 0 .312.157c.13.032.323.054.513.066a6 6 0 0 0 .286.01l.015.001H7.5v1.5h-.75z"></path></svg>',
    bgColor:
      '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><rect x="2" y="2" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" rx="2"/><rect x="3" y="3" width="14" height="14" fill="currentColor" stroke="none" rx="1" opacity="0.3"/></svg>',
  };

  /**
   * Adaptador de upload customizado para integrar com o endpoint configurado.
   *
   * @param {File} file Arquivo que será enviado.
   * @returns {Promise<{default: string}>} Retorno com a URL da imagem enviada.
   */
  class CANNALCustomUploadAdapter {
    constructor(loader, uploadUrl) {
      this.loader = loader;
      this.uploadUrl = settings.fileUploadUrl;
      this.request = null;
    }

    upload() {
      return this.loader.file.then(
        (file) =>
          new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append("file", file);

            this.request = $.ajax({
              url: this.uploadUrl,
              method: "POST",
              data: formData,
              processData: false,
              contentType: false,
              dataType: "json",
            })
              .done((payload) => {
                if (payload?.success && payload.file?.url) {
                  resolve({ default: payload.file.url });
                } else {
                  reject("Falha ao enviar a imagem.");
                }
              })
              .fail(() => reject("Erro ao concluir o upload."));
          }),
      );
    }

    abort() {
      if (this.request?.abort) {
        this.request.abort();
      }
    }
  }

  class CANNALCustomUploadCKPlugin extends Plugin {
    static get pluginName() {
      return "CANNALCustomUploadCKPlugin";
    }

    init() {
      this.editor.plugins.get("FileRepository").createUploadAdapter = (
        loader,
      ) => {
        return new CANNALCustomUploadAdapter(loader);
      };
    }
  }

  class PreserveFullHtmlPlugin extends Plugin {
    static get pluginName() {
      return "PreserveFullHtmlPlugin";
    }

    init() {
      const editor = this.editor;
      const dataProcessor = editor.data.processor;

      // Preservar tags HTML completas
      const originalToView = dataProcessor.toView.bind(dataProcessor);
      dataProcessor.toView = function (html) {
        if (html && html.includes("<html")) {
          return originalToView(html);
        }
        return originalToView(html);
      };

      // Preservar ao sair do editor
      const originalGetData = editor.getData.bind(editor);
      editor.getData = function () {
        let html = originalGetData();

        // Garante que temos <html>, <head>, <body>
        if (!html.includes("<html")) {
          html = `<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
</head>
<body>${html}</body>
</html>`;
        }

        return html;
      };
    }
  }

  class CANNALTemplatesCKPlugin extends Plugin {
    static get pluginName() {
      return "CANNALTemplatesCKPlugin";
    }

    init() {
      const editor = this.editor;

      editor.ui.componentFactory.add("Templates", (locale) => {
        const dropdown = createDropdown(locale);

        dropdown.buttonView.set({
          label: "Templates",
          icon: icons.templates,
          tooltip: "Inserir template",
          withText: false,
        });

        const items = new Collection();
        items.add({
          type: "button",
          model: new UIModel({
            label: "Carregar templates...",
            withText: true,
            commandParam: "load",
          }),
        });

        addListToDropdown(dropdown, items);

        this.listenTo(dropdown, "execute", (evt) => {
          if (evt.source.commandParam === "load") {
            this.openTemplatesModal(editor);
          }
        });

        return dropdown;
      });
    }

    openTemplatesModal(editor) {
      const { modal, close } = createModal(
        "Selecionar Template",
        `
				<div id="templates-list" class="list-group" style="max-height: 400px; overflow-y: auto;">
					<div class="text-center text-muted p-3">Carregando templates...</div>
				</div>
			`,
      );

      // Carregar templates
      $.ajax({
        url: settings.templateSearchUrl,
        method: "GET",
        dataType: "json",
        success: (response) => {
          if (response.templates && response.templates.length > 0) {
            let html = "";
            response.templates.forEach((tpl) => {
              html += `
								<button type="button" class="list-group-item list-group-item-action template-item" data-template="${tpl.id}">
									<strong>${tpl.name}</strong>
									<br>
									<small class="text-muted">${tpl.description || "Sem descrição"}</small>
								</button>
							`;
            });
            modal.find("#templates-list").html(html);

            modal.find(".template-item").on("click", function () {
              const templateId = $(this).data("template");
              const selectedTemplate = response.templates.find(
                (t) => t.id === templateId,
              );

              if (selectedTemplate && selectedTemplate.html_content) {
                const selectedHtml = selectedTemplate.html_content;
                insertHtml(editor, selectedHtml);
              }

              close();
            });
          } else {
            modal
              .find("#templates-list")
              .html(
                '<div class="alert alert-info m-0">Nenhum template disponível.</div>',
              );
          }
        },
        error: () => {
          modal
            .find("#templates-list")
            .html(
              '<div class="alert alert-danger m-0">Erro ao carregar templates.</div>',
            );
        },
      });

      modal.find(".ck-modal-confirm").on("click", close);
    }
  }

  class CANNALTagsCKPlugin extends Plugin {
    static get pluginName() {
      return "CANNALTagsCKPlugin";
    }

    init() {
      const editor = this.editor;

      editor.ui.componentFactory.add("Tags", (locale) => {
        const dropdown = createDropdown(locale);

        dropdown.buttonView.set({
          label: "TAG's",
          icon: icons.tags,
          tooltip: "Inserir TAG",
          withText: false,
        });

        const tags = [
          { label: "Nome", html: "{{nome}}" },
          { label: "Apelido", html: "{{apelido}}" },
          { label: "E-mail", html: "{{email}}" },
          {
            label: "Link de Visualização",
            html: '<a href="{{webview_link}}">Link de Visualização</a>',
          },
          {
            label: "Link Opt-out",
            html: '<a href="{{optout_link}}" target="_blank">Sair</a>',
          },
        ];

        const items = new Collection();

        for (const tag of tags) {
          items.add({
            type: "button",
            model: new UIModel({
              label: tag.label,
              withText: true,
              commandParam: tag.html,
            }),
          });
        }

        addListToDropdown(dropdown, items);

        this.listenTo(dropdown, "execute", (evt) => {
          const html = evt.source.commandParam;

          // Se for só texto simples, insere como texto.
          if (!html.includes("<")) {
            editor.model.change((writer) => {
              editor.model.insertContent(writer.createText(html));
            });
          } else {
            // Para HTML: converter view -> model e inserir.
            const viewFragment = editor.data.processor.toView(html);
            const modelFragment = editor.data.toModel(viewFragment);

            editor.model.insertContent(modelFragment);
          }

          editor.editing.view.focus();
        });

        return dropdown;
      });
    }
  }

  /**
   * CANNALGoogleFontsCKPlugin para CKEditor 5
   * Permite importar fontes do Google Fonts via modal AJAX
   */
  class CANNALGoogleFontsCKPlugin extends Plugin {
    static get pluginName() {
      return "CANNALGoogleFontsCKPlugin";
    }

    init() {
      const editor = this.editor;

      // Inicializa array de fontes importadas
      if (!window.importedGoogleFonts) {
        window.importedGoogleFonts = [];
      }

      // Adiciona comando para importar Google Font
      class ImportGoogleFontCommand extends Command {
        execute() {
          try {
            this.editor.plugins
              .get("CANNALGoogleFontsCKPlugin")
              .openGoogleFontsModal(editor);
          } catch (e) {
            console.error("Erro ao executar ImportGoogleFontCommand:", e);
          }
        }
      }

      editor.commands.add(
        "importGoogleFont",
        new ImportGoogleFontCommand(editor),
      );

      // Adiciona botão à toolbar
      editor.ui.componentFactory.add("importGoogleFont", (locale) => {
        const button = new ButtonView(locale);

        button.set({
          tooltip: "Importar fonte do Google Fonts",
          withText: true,
          icon: '<svg viewBox="0 0 48 48" id="b" xmlns="http://www.w3.org/2000/svg" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><defs><style>.c{fill:none;stroke:#000000;stroke-linecap:round;stroke-linejoin:round;}</style></defs><path class="c" d="m31.6814,34.8868c-1.9155,1.29-4.3586,2.0718-7.2514,2.0718-5.59,0-10.3395-3.7723-12.04-8.8541v-.0195c-.43-1.29-.6841-2.6582-.6841-4.085s.2541-2.795.6841-4.085c1.7005-5.0818,6.45-8.8541,12.04-8.8541,3.1664,0,5.9809,1.0945,8.2286,3.2055l6.1568-6.1568c-3.7332-3.4791-8.5805-5.6095-14.3855-5.6095-8.4045,0-15.6559,4.8277-19.1936,11.8641-1.4659,2.8927-2.3064,6.1568-2.3064,9.6359s.8405,6.7432,2.3064,9.6359v.0195c3.5377,7.0168,10.7891,11.8445,19.1936,11.8445,5.805,0,10.6718-1.9155,14.2291-5.1991,4.0655-3.7527,6.4109-9.2645,6.4109-15.8123,0-1.5245-.1368-2.9905-.3909-4.3977h-20.2491v8.3264h11.5709c-.5082,2.6777-2.0327,4.945-4.3195,6.4695h0Z"></path></g></svg>',
        });

        button.on("execute", () => {
          editor.execute("importGoogleFont");
        });

        return button;
      });

      // Detectar Google Fonts ao sair do modo "Fonte"
      const sourceEditing = editor.plugins.get("SourceEditing");
      if (sourceEditing) {
        sourceEditing.on(
          "change:isSourceEditingMode",
          (evt, propertyName, newValue) => {
            try {
              if (!newValue) {
                // Saindo do modo fonte
                setTimeout(() => {
                  const html = editor.getData();
                  this.detectGoogleFontsInHtml(html);
                }, 100);
              }
            } catch (e) {
              // Ignorar erros ao sair do modo fonte
            }
          },
        );
      }
    }

    openGoogleFontsModal(editor) {
      const self = this;
      const modalId = `google-fonts-modal-${Date.now()}`;

      // Cria modal HTML
      const modalHtml = `
				<div class="modal fade" id="${modalId}" tabindex="-1" aria-hidden="true">
					<div class="modal-dialog modal-lg modal-dialog-centered">
						<div class="modal-content">
							<div class="modal-header">
								<h5 class="modal-title">Importar Google Font</h5>
								<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
							</div>
							<div class="modal-body">
								<div class="mb-3">
									<label class="form-label">Pesquisar Fonte</label>
									<input type="text" class="form-control" id="google-font-search" placeholder="Ex: Roboto, Open Sans, Montserrat">
									<small class="form-text text-muted">Digite o nome da fonte para pesquisar</small>
								</div>
								<div id="google-fonts-list" class="list-group" style="max-height: 400px; overflow-y: auto;">
									<div class="text-center text-muted p-3">Digite para pesquisar fontes...</div>
								</div>
								<div class="mt-3">
									<label class="form-label">Pesos (opcional)</label>
									<input type="text" class="form-control" id="google-font-weights" placeholder="Ex: 400,700" value="400,700">
									<small class="form-text text-muted">Separe múltiplos pesos por vírgula</small>
								</div>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
								<button type="button" class="btn btn-primary" id="google-font-confirm">Importar</button>
							</div>
						</div>
					</div>
				</div>
			`;

      // Adiciona modal ao DOM
      const $modal = $(modalHtml);
      $("body").append($modal);
      const bsModal = new bootstrap.Modal($modal[0]);

      let selectedFont = null;

      // Listener para pesquisa
      $modal.find("#google-font-search").on("input", function () {
        const query = $(this).val().trim();
        if (query.length < 2) {
          $modal
            .find("#google-fonts-list")
            .html(
              '<div class="text-center text-muted p-3">Digite pelo menos 2 caracteres...</div>',
            );
          return;
        }

        // Pesquisar fontes via AJAX
        $.ajax({
          url: "/api/google-fonts/search",
          method: "GET",
          data: { q: query },
          dataType: "json",
          success: function (response) {
            if (response.fonts && response.fonts.length > 0) {
              let html = "";
              response.fonts.forEach((font) => {
                html += `
									<button type="button" class="list-group-item list-group-item-action google-font-item" data-font="${font.family}">
										<strong>${font.family}</strong>
										<br>
										<small class="text-muted">${font.variants ? font.variants.join(", ") : "Variantes disponíveis"}</small>
									</button>
								`;
              });
              $modal.find("#google-fonts-list").html(html);

              // Listener para seleção de fonte
              $modal.find(".google-font-item").on("click", function () {
                $modal.find(".google-font-item").removeClass("active");
                $(this).addClass("active");
                selectedFont = $(this).data("font");
              });
            } else {
              $modal
                .find("#google-fonts-list")
                .html(
                  '<div class="alert alert-info m-0">Nenhuma fonte encontrada. Tente outro nome.</div>',
                );
            }
          },
          error: function () {
            $modal
              .find("#google-fonts-list")
              .html(
                '<div class="alert alert-danger m-0">Erro ao pesquisar fontes. Tente novamente.</div>',
              );
          },
        });
      });

      // Listener para confirmar importação
      $modal.find("#google-font-confirm").on("click", function () {
        if (!selectedFont) {
          alert("Por favor, selecione uma fonte.");
          return;
        }

        const weights =
          $modal.find("#google-font-weights").val().trim() || "400,700";
        self.importFont(editor, selectedFont, weights);
        bsModal.hide();
      });

      // Limpar modal ao fechar
      $modal.on("hidden.bs.modal", function () {
        $modal.remove();
      });

      bsModal.show();
    }

    importFont(editor, fontName, weights) {
      // Constrói URL do Google Fonts
      const fontUrl = `https://fonts.googleapis.com/css2?family=${fontName.replace(/\s+/g, "+")}:wght@${weights}&display=swap`;

      // Adiciona à lista de fontes importadas
      const fontData = { name: fontName, url: fontUrl, weights };

      // Verifica se já foi importada
      const exists = window.importedGoogleFonts.some(
        (f) => f.name === fontName,
      );
      if (!exists) {
        window.importedGoogleFonts.push(fontData);

        // Adiciona fonte ao dropdown de fontes do CKEditor
        // Comentado: Acesso a _options causa erro em algumas versões do CKEditor
        // const fontFamilyPlugin = editor.plugins.get('FontFamily');
        // if (fontFamilyPlugin) {
        //	const fontFamilyCommand = editor.commands.get('fontFamily');
        //	if (fontFamilyCommand) {
        //		const fontOption = `${fontName}, sans-serif`;
        //		const currentOptions = fontFamilyCommand._options || [];
        //		if (!currentOptions.includes(fontOption)) {
        //			currentOptions.push(fontOption);
        //			fontFamilyCommand._options = currentOptions;
        //		}
        //	}
        // }

        // Também atualiza a config para futuras referências
        // const fontFamilyConfig = editor.config.get('fontFamily');
        // if (fontFamilyConfig && fontFamilyConfig.options) {
        //	const fontOption = `${fontName}, sans-serif`;
        //	if (!fontFamilyConfig.options.includes(fontOption)) {
        //		fontFamilyConfig.options.push(fontOption);
        //	}
        // }

        // Atualiza preview
        if (window.updateEmailPreview) {
          window.updateEmailPreview();
        }

        alert(
          `Fonte "${fontName}" importada com sucesso! Agora você pode selecioná-la no dropdown "Fonte".`,
        );
      } else {
        alert(`Fonte "${fontName}" já foi importada anteriormente.`);
      }
    }

    detectGoogleFontsInHtml(html) {
      // Detectar links de Google Fonts no <head>
      const googleFontsRegex =
        /https:\/\/fonts\.googleapis\.com\/css2\?family=([^"&]+)/g;
      let match;

      while ((match = googleFontsRegex.exec(html)) !== null) {
        const fontUrl = match[0];
        const fontParam = match[1];

        // Decodificar nome da fonte
        const fontName = decodeURIComponent(
          fontParam.split(":")[0].replace(/\+/g, " "),
        );

        // Extrair pesos
        const weightsMatch = fontUrl.match(/:wght@([^&]+)/);
        const weights = weightsMatch ? weightsMatch[1] : "400,700";

        // Adicionar à lista se não existir
        const exists = window.importedGoogleFonts.some(
          (f) => f.name === fontName,
        );
        if (!exists) {
          window.importedGoogleFonts.push({
            name: fontName,
            url: fontUrl,
            weights: weights,
          });
        }
      }
    }
  }

  /**
   * CANNALBackgroundColorCKPlugin para CKEditor 5
   * Permite selecionar cor de fundo do email (aplicada via CSS inline na tag body)
   * Funciona exatamente como FontColor
   */
  class CANNALBackgroundColorCKPlugin extends Plugin {
    static get pluginName() {
      return "CANNALBackgroundColorCKPlugin";
    }

    init() {
      const editor = this.editor;
      const { commands } = editor;

      // Inicializa cor de fundo padrão
      if (!window.emailBackgroundColor) {
        window.emailBackgroundColor = "#ffffff";

        // Tentar detectar cor do HTML existente
        const html = editor.getData();
        const bodyMatch = html.match(
          /<body[^>]*style="[^"]*background-color:\s*([^;]+);[^"]*"/i,
        );
        if (bodyMatch && bodyMatch[1]) {
          window.emailBackgroundColor = bodyMatch[1].trim();
        } else {
          const styleMatch = html.match(
            /body\s*{\s*[^}]*background-color:\s*([^;]+);/i,
          );
          if (styleMatch && styleMatch[1]) {
            window.emailBackgroundColor = styleMatch[1].trim();
          }
        }
      }

      // Comando para definir cor de fundo
      class SetBackgroundColorCommand extends Command {
        execute(color) {
          try {
            // Atualizar background color
            window.updateBackground(color || "#ffffff");

            // Atualiza preview
            if (window.updateEmailPreview) {
              window.updateEmailPreview();
            }
          } catch (e) {
            console.error("Erro ao executar SetBackgroundColorCommand:", e);
          }
        }
      }

      commands.add("setBackgroundColor", new SetBackgroundColorCommand(editor));

      // Adiciona botão à toolbar com mesma UI do FontColor
      editor.ui.componentFactory.add("BackgroundColor", (locale) => {
        try {
          const dropdown = createDropdown(locale);

          // Armazenar referência ao dropdown para atualizar ícone
          window.bgColorDropdown = dropdown;

          dropdown.buttonView.set({
            label: "Cor de Fundo",
            tooltip: "Cor de fundo do email",
            withText: false,
            icon: icons.bgColor,
            isToggleable: true,
          });

          // Inicializar ícone com cor atual
          if (
            window.emailBackgroundColor &&
            window.emailBackgroundColor !== "#ffffff"
          ) {
            const svg = dropdown.buttonView.element.querySelector("svg");
            if (svg) {
              svg.querySelectorAll("path, rect, circle").forEach((el) => {
                el.setAttribute("fill", window.emailBackgroundColor);
              });
            }
          }

          // Cores predefinidas (mesmas do FontColor)
          const bgColors = DEFAULT_HEX_COLORS.map((c) => c.color);

          // Listener para quando o dropdown é aberto (elemento renderizado)
          this.listenTo(
            dropdown,
            "change:isOpen",
            (evt, propertyName, isOpen) => {
              if (
                isOpen &&
                !dropdown.panelView.element.querySelector(".ck-color-picker")
              ) {
                // Criar color picker customizado com HTML simples
                const colorPickerDiv = document.createElement("div");
                colorPickerDiv.className = "ck-color-picker";
                colorPickerDiv.style.cssText =
                  "padding: 10px; background: #fff;";

                // Botão remover cor
                const removeBtn = document.createElement("button");
                removeBtn.className = "ck-button-remove";
                removeBtn.textContent = "🗑️ Remover cor";
                removeBtn.style.cssText =
                  "width: 100%; padding: 8px; background: #f0f0f0; border: 1px solid #ccc; cursor: pointer; border-radius: 3px; margin-bottom: 10px;";
                removeBtn.addEventListener("click", (e) => {
                  e.preventDefault();
                  commands.execute("setBackgroundColor", null);
                  // Sincronizar input com cor removida
                  if (
                    dropdown.panelView.element.querySelector(".ck-color-input")
                  ) {
                    dropdown.panelView.element.querySelector(
                      ".ck-color-input",
                    ).value = "#ffffff";
                  }
                  // Remover cor do ícone SVG do botão
                  const svg = dropdown.buttonView.element.querySelector("svg");
                  if (svg) {
                    // Restaurar cor original do SVG (branco)
                    svg.querySelectorAll("path, rect, circle").forEach((el) => {
                      el.setAttribute("fill", "#ffffff");
                    });
                  }
                  dropdown.isOpen = false;
                });
                colorPickerDiv.appendChild(removeBtn);

                // Grid de cores
                const colorGrid = document.createElement("div");
                colorGrid.className = "ck-color-grid";
                colorGrid.style.cssText =
                  "display: grid; grid-template-columns: repeat(5, 1fr); gap: 5px; margin-bottom: 10px;";
                bgColors.forEach((color) => {
                  const colorBtn = document.createElement("button");
                  colorBtn.className = "ck-color-button";
                  colorBtn.dataset.color = color;
                  colorBtn.style.cssText = `width: 30px; height: 30px; background: ${color}; border: 2px solid #ccc; cursor: pointer; border-radius: 3px;`;
                  colorBtn.title = color;
                  colorBtn.addEventListener("click", (e) => {
                    e.preventDefault();
                    commands.execute("setBackgroundColor", color);
                    // Sincronizar input com cor selecionada
                    if (
                      dropdown.panelView.element.querySelector(
                        ".ck-color-input",
                      )
                    ) {
                      dropdown.panelView.element.querySelector(
                        ".ck-color-input",
                      ).value = color;
                    }
                    // Atualizar ícone SVG do botão
                    const svg =
                      dropdown.buttonView.element.querySelector("svg");
                    if (svg) {
                      // Mudar cor de todos os paths/rects do SVG
                      svg
                        .querySelectorAll("path, rect, circle")
                        .forEach((el) => {
                          el.setAttribute("fill", color);
                        });
                    }
                    dropdown.isOpen = false;
                  });
                  colorGrid.appendChild(colorBtn);
                });
                colorPickerDiv.appendChild(colorGrid);

                // Input de cor customizada
                const colorInputLabel = document.createElement("label");
                colorInputLabel.textContent = "Cor customizada:";
                colorInputLabel.style.cssText =
                  "display: block; font-size: 12px; margin-bottom: 5px;";
                colorPickerDiv.appendChild(colorInputLabel);

                const colorInputContainer = document.createElement("div");
                colorInputContainer.style.cssText = "display: flex; gap: 5px;";

                const colorInput = document.createElement("input");
                colorInput.type = "color";
                colorInput.className = "ck-color-input";
                colorInput.value = window.emailBackgroundColor;
                colorInput.style.cssText =
                  "flex: 1; height: 40px; cursor: pointer; border: 1px solid #ccc; border-radius: 3px;";
                colorInputContainer.appendChild(colorInput);

                const confirmBtn = document.createElement("button");
                confirmBtn.textContent = "OK";
                confirmBtn.style.cssText =
                  "padding: 8px 16px; background: #007bff; color: #fff; border: none; cursor: pointer; border-radius: 3px;";
                confirmBtn.addEventListener("click", (e) => {
                  e.preventDefault();
                  const color = colorInput.value;
                  commands.execute("setBackgroundColor", color);
                  // Atualizar ícone SVG do botão
                  const svg = dropdown.buttonView.element.querySelector("svg");
                  if (svg) {
                    svg.querySelectorAll("path, rect, circle").forEach((el) => {
                      el.setAttribute("fill", color);
                    });
                  }
                  dropdown.isOpen = false;
                });
                colorInputContainer.appendChild(confirmBtn);
                colorPickerDiv.appendChild(colorInputContainer);

                // Adiciona ao dropdown
                dropdown.panelView.element.appendChild(colorPickerDiv);
              }
            },
          );

          return dropdown;
        } catch (e) {
          // Retornar dropdown vazio se houver erro
          return createDropdown(locale);
        }
      });

      // Restaurar cor quando editor ganha foco (após renderização)
      if (editor.ui.view.editable && editor.ui.view.editable.element) {
        editor.ui.view.editable.element.addEventListener("focus", () => {
          if (
            window.emailBackgroundColor &&
            window.emailBackgroundColor !== "#ffffff"
          ) {
            editor.ui.view.editable.element.style.backgroundColor =
              window.emailBackgroundColor;
          }
        });
      } else {
        // Se ainda não foi renderizado, aguardar
        setTimeout(() => {
          if (editor.ui.view.editable && editor.ui.view.editable.element) {
            editor.ui.view.editable.element.addEventListener("focus", () => {
              if (
                window.emailBackgroundColor &&
                window.emailBackgroundColor !== "#ffffff"
              ) {
                editor.ui.view.editable.element.style.backgroundColor =
                  window.emailBackgroundColor;
              }
            });
          }
        }, 100);
      }

      // Detectar cor de fundo ao sair do modo "Fonte" (SourceEditing)
      const sourceEditing = editor.plugins.get("SourceEditing");
      if (sourceEditing) {
        sourceEditing.on(
          "change:isSourceEditingMode",
          (evt, propertyName, newValue) => {
            try {
              if (!newValue) {
                // Saindo do modo fonte
                setTimeout(() => {
                  window.sourceOut();
                }, 100);
              }
            } catch (e) {
              // Ignorar erros ao sair do modo fonte
            }
          },
        );
      }
    }
  }

  function createModal(title, bodyHtml) {
    const modalId = `ck-modal-${Date.now()}`;
    const $modal = $(`
                        <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}-label" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                        <div class="modal-content">
                                                <div class="modal-header">
                                                        <h5 class="modal-title" id="${modalId}-label">${title}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                                </div>
                                                <div class="modal-body">${bodyHtml}</div>
                                                <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="button" class="btn btn-primary ck-modal-confirm">Confirmar</button>
                                                </div>
                                        </div>
                                </div>
                        </div>
                `);

    $("body").append($modal);
    const modalInstance = new bootstrap.Modal($modal[0]);
    modalInstance.show();

    const close = () => {
      if ($modal[0].contains(document.activeElement)) {
        document.activeElement.blur();
      }
      $modal.on("hidden.bs.modal", () => $modal.remove());
      modalInstance.hide();
    };

    return { modal: $modal, close, modalInstance };
  }

  function insertHtml(editor, html) {
    if (!html) {
      return;
    }

    if (editor.editing?.view?.focus) {
      editor.editing.view.focus();
    }

    if (editor.model?.insertContent && editor.data?.processor?.toView) {
      const viewFragment = editor.data.processor.toView(html);
      const modelFragment = editor.data.toModel(viewFragment);
      editor.model.insertContent(modelFragment);
      return;
    }
  }

  function fetchJson(url, options = {}) {
    const hasFormData = options.body instanceof FormData;
    return $.ajax({
      url,
      method: options.method || "GET",
      data: options.body || options.data,
      dataType: "json",
      processData: !hasFormData,
      contentType: hasFormData ? false : undefined,
    });
  }

  function removeImageSizeAttributes(html) {
    if (!html) {
      return "";
    }

    const parser = new DOMParser();
    const documentFragment = parser.parseFromString(html, "text/html");

    documentFragment.querySelectorAll("img").forEach((image) => {
      image.removeAttribute("width");
      image.removeAttribute("height");
    });

    return documentFragment.body.innerHTML;
  }

  function renderEditorPreview(targetId = "editorPreviewContent") {
    const previewElement = document.getElementById(targetId);
    if (!previewElement) {
      return;
    }
    const content =
      typeof window.getRichEditorData === "function"
        ? window.getRichEditorData()
        : null;
    const sanitized = removeImageSizeAttributes(content || "");

    previewElement.innerHTML =
      sanitized ||
      '<p class="text-muted">Nenhum conteúdo para pré-visualizar.</p>';
  }

  window.renderEditorPreview = renderEditorPreview;

  function initEditors() {
    $("#richEditor").each(function () {
      const element = this;
      const rawHtml = element.value || "";

      // ==========================================
      // EXTRAÇÃO DE COR CORRIGIDA (Mantendo HEX)
      // ==========================================
      try {
        const parser = new DOMParser();
        const doc = parser.parseFromString(rawHtml, "text/html");
        let initialColor = "#ffffff";

        if (doc.body) {
          // 1. Lê o estilo como texto puro, evitando a conversão RGB do navegador
          const styleAttr = doc.body.getAttribute("style") || "";
          const bgMatch = styleAttr.match(/background-color:\s*([^;]+)/i);

          if (bgMatch && bgMatch[1]) {
            initialColor = bgMatch[1].trim();
          } else {
            // 2. Fallback: tenta ler de uma tag <style> se não estiver inline
            const styleTag = doc.querySelector("style");
            if (styleTag) {
              const cssMatch = styleTag.textContent.match(
                /body\s*\{\s*[^}]*background-color:\s*([^;}]+)/i,
              );
              if (cssMatch && cssMatch[1]) initialColor = cssMatch[1].trim();
            }
          }
        }

        // 3. Conversor de segurança (caso o HTML salvo no banco realmente seja um RGB)
        if (initialColor.startsWith("rgb")) {
          const rgb = initialColor.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)/);
          if (rgb) {
            initialColor =
              "#" +
              ("0" + parseInt(rgb[1], 10).toString(16)).slice(-2) +
              ("0" + parseInt(rgb[2], 10).toString(16)).slice(-2) +
              ("0" + parseInt(rgb[3], 10).toString(16)).slice(-2);
          }
        }

        // Salva em caixa baixa para garantir que a comparação com '#ffffff' não falhe (ex: '#FFFFFF')
        window.emailBackgroundColor = initialColor.toLowerCase();
      } catch (e) {
        console.error("Erro ao extrair cor:", e);
        window.emailBackgroundColor = "#ffffff";
      }
      // ==========================================

      try {
        ClassicEditor.create(element, {
          fullPage: true,
          licenseKey: settings.licence,
          language: "pt-br",
          plugins: editorPlugins,
          extraPlugins: [
            PreserveFullHtmlPlugin,
            CANNALTemplatesCKPlugin,
            CANNALTagsCKPlugin,
            CANNALGoogleFontsCKPlugin,
            CANNALBackgroundColorCKPlugin,
            CANNALCustomUploadCKPlugin,
          ],
          menuBar: {
            isVisible: true,
          },
          toolbar: {
            items: [
              "undo",
              "redo",
              "|",
              "sourceEditing",
              "showBlocks",
              "fullscreen",
              "|",
              "Templates",
              "Tags",
              "BackgroundColor",
              "|",
              "importGoogleFont",
              "fontSize",
              "fontFamily",
              "fontColor",
              "fontBackgroundColor",
              "|",
              "bold",
              "italic",
              "underline",
              "strikethrough",
              "subscript",
              "superscript",
              "|",
              "emoji",
              "horizontalLine",
              "link",
              "insertImage",
              "mediaEmbed",
              "insertTable",
              "highlight",
              "blockQuote",
              "|",
              "alignment",
              "|",
              "bulletedList",
              "numberedList",
              "todoList",
              "outdent",
              "indent",
              "|",
              "heading",
              "style",
            ],

            shouldNotGroupWhenFull: true,
          },
          balloonToolbar: [
            "bold",
            "italic",
            "|",
            "link",
            "|",
            "bulletedList",
            "numberedList",
          ],
          blockToolbar: [
            "fontSize",
            "fontColor",
            "fontBackgroundColor",
            "|",
            "bold",
            "italic",
            "|",
            "link",
            "insertTable",
            "|",
            "bulletedList",
            "numberedList",
            "outdent",
            "indent",
          ],
          fullscreen: {
            onEnterCallback: (container) =>
              container.classList.add(
                "editor-container",
                "editor-container_classic-editor",
                "editor-container_include-style",
                "editor-container_include-block-toolbar",
                "editor-container_include-fullscreen",
                "main-container",
              ),
          },
          title: {
            // No placeholder for the title.
            placeholder: undefined,
          },
          placeholder: undefined,
          heading: {
            options: [
              {
                model: "paragraph",
                title: "Paragraph",
                class: "ck-heading_paragraph",
              },
              {
                model: "heading1",
                view: "h2",
                title: "Heading 1",
                class: "ck-heading_heading1",
              },
              {
                model: "heading2",
                view: "h3",
                title: "Heading 2",
                class: "ck-heading_heading2",
              },
              {
                model: "heading3",
                view: "h4",
                title: "Heading 3",
                class: "ck-heading_heading3",
              },
              {
                model: "heading4",
                view: "h5",
                title: "Heading 4",
                class: "ck-heading_heading4",
              },
              {
                model: "heading5",
                view: "h6",
                title: "Heading 5",
                class: "ck-heading_heading5",
              },
            ],
          },
          fontSize: {
            options: [10, 12, 14, "default", 18, 20, 22],
            supportAllValues: true,
          },
          fontColor: {
            colorPicker: {
              format: "hex",
            },
            colors: DEFAULT_HEX_COLORS,
          },
          fontBackgroundColor: {
            colorPicker: {
              format: "hex",
            },
            colors: DEFAULT_HEX_COLORS,
          },
          image: {
            toolbar: [
              "imageStyle:inline",
              "imageStyle:block",
              "imageStyle:side",
              "|",
              "toggleImageCaption",
              "imageTextAlternative",
              "|",
              "resizeImage:200",
              "resizeImage:500",
            ],
            resizeUnit: "px",
            resizeOptions: [
              {
                name: "resizeImage:200",
                value: "200",
                icon: "small",
                label: "200px",
              },
              {
                name: "resizeImage:500",
                value: "500",
                icon: "medium",
                label: "500px",
              },
            ],
            upload: {
              types: ["jpeg", "png", "gif", "bmp", "webp", "tiff", "svg+xml"],
            },
            insert: {
              integrations: ["upload", "url"],
            },
          },
          link: {
            addTargetToExternalLinks: true,
            defaultProtocol: "https://",
          },
          list: {
            properties: {
              styles: true,
              startIndex: true,
              reversed: false,
            },
          },
          table: {
            contentToolbar: [
              "tableColumn",
              "tableRow",
              "mergeTableCells",
              "tableProperties",
              "tableCellProperties",
              "toggleTableCaption",
            ],
            tableCellProperties: {
              borderColors: DEFAULT_HEX_COLORS,
              backgroundColors: DEFAULT_HEX_COLORS,
            },
            tableProperties: {
              borderColors: DEFAULT_HEX_COLORS,
              backgroundColors: DEFAULT_HEX_COLORS,
            },
          },
          htmlSupport: {
            preserveEmptyBlocksInEditingView: true,
            allow: [
              {
                // Permite TODAS as tags HTML comuns em emails
                name: /.*/,
                attributes: true,
                classes: true,
                styles: true,
              },
            ],
            disallow: [], // Garante que nada seja explicitamente proibido
          },
          style: {
            definitions: [
              {
                name: "Button (green)",
                element: "a",
                classes: ["button", "button--green"],
              },
              {
                name: "Button (black)",
                element: "a",
                classes: ["button", "button--black"],
              },
              {
                name: "Article category",
                element: "h3",
                classes: ["category"],
              },
              {
                name: "Title",
                element: "h2",
                classes: ["document-title"],
              },
              {
                name: "Subtitle",
                element: "h3",
                classes: ["document-subtitle"],
              },
              {
                name: "Info box",
                element: "p",
                classes: ["info-box"],
              },
              {
                name: "CTA Link Primary",
                element: "a",
                classes: ["button", "button--green"],
              },
              {
                name: "CTA Link Secondary",
                element: "a",
                classes: ["button", "button--black"],
              },
              {
                name: "Marker",
                element: "span",
                classes: ["marker"],
              },
              {
                name: "Spoiler",
                element: "span",
                classes: ["spoiler"],
              },
            ],
          },
        })
          .then((editor) => {
            editorInstance = editor;
            window.editor = editor;

            enforceEditorHeight(editor, settings.height);

            // Detectar e aplicar cor de fundo ao carregar
            window.updateBackground(window.initialBgColor);

            window.renderEditorPreview = renderEditorPreview;

            // Listener de SourceEditing já foi adicionado no plugin CANNALBackgroundColorCKPlugin

            // Restaurar background-color continuamente
            setInterval(() => {
              if (
                window.emailBackgroundColor &&
                window.emailBackgroundColor !== "#ffffff"
              ) {
                const editableElement = editor.ui.view.editable.element;
                if (
                  editableElement &&
                  editableElement.style.backgroundColor !==
                    window.emailBackgroundColor
                ) {
                  editableElement.style.backgroundColor =
                    window.emailBackgroundColor;
                }
              }
            }, 500);

            editorReadyDeferred.resolveReady(editor);
            // ==========================================
            // GATILHO PARA REAPLICAR A COR AO AVANÇAR ETAPAS
            // ==========================================
            $(".nextStep, .prevStep, .step").on("click", function () {
              setTimeout(() => {
                if (window.emailBackgroundColor) {
                  window.updateBackground(window.emailBackgroundColor);
                }
              }, 150); // Aguarda a aba aparecer completamente
            });
            // ==========================================
          })
          .catch((error) => {
            console.error("Erro ao inicializar CKEditor:", error);
            editorReadyDeferred.resolveReady(null);
          });
      } catch (e) {
        console.error("Erro ao criar CKEditor:", e);
        editorReadyDeferred.resolveReady(null);
      }
    });
  }

  /**
   * Atualiza o preview do email no iframe.
   */
  function updateEmailPreview() {
    const previewFrame = document.getElementById("emailPreviewFrame");
    if (!previewFrame) return;

    // Processa HTML para email
    const processedHtml = window.renderHTML();

    // Atualiza iframe
    const iframeDoc =
      previewFrame.contentDocument || previewFrame.contentWindow.document;
    iframeDoc.open();
    iframeDoc.write(processedHtml);
    iframeDoc.close();
  }

  // Atualiza preview quando a aba Preview é clicada
  $(document).on("shown.bs.tab", "#preview-tab", function () {
    updateEmailPreview();
  });

  // Exporta função globalmente
  window.updateEmailPreview = updateEmailPreview;

  $(document).ready(initEditors);
})(jQuery);

/**
 * ==========================================
 * FUNÇÕES DE FORMATAÇÃO E RENDERIZAÇÃO HTML
 * ==========================================
 */

/**
 * Formata o HTML de saída para melhor legibilidade.
 *
 * @param {string} input - HTML a ser formatado
 * @returns {string} HTML formatado com indentação
 */
function formatHtml(input) {
  // Decodifica entidades HTML antes de formatar
  input = decodeHtmlEntities(input);

  // Lista de elementos block-level que devem ter quebras de linha e indentação
  const elementsToFormat = [
    { name: "html", isVoid: false },
    { name: "head", isVoid: false },
    { name: "body", isVoid: false },
    { name: "address", isVoid: false },
    { name: "article", isVoid: false },
    { name: "aside", isVoid: false },
    { name: "blockquote", isVoid: false },
    { name: "details", isVoid: false },
    { name: "dialog", isVoid: false },
    { name: "dd", isVoid: false },
    { name: "div", isVoid: false },
    { name: "dl", isVoid: false },
    { name: "dt", isVoid: false },
    { name: "fieldset", isVoid: false },
    { name: "figcaption", isVoid: false },
    { name: "figure", isVoid: false },
    { name: "footer", isVoid: false },
    { name: "form", isVoid: false },
    { name: "h1", isVoid: false },
    { name: "h2", isVoid: false },
    { name: "h3", isVoid: false },
    { name: "h4", isVoid: false },
    { name: "h5", isVoid: false },
    { name: "h6", isVoid: false },
    { name: "header", isVoid: false },
    { name: "hgroup", isVoid: false },
    { name: "hr", isVoid: true },
    { name: "li", isVoid: false },
    { name: "main", isVoid: false },
    { name: "nav", isVoid: false },
    { name: "ol", isVoid: false },
    { name: "p", isVoid: false },
    { name: "section", isVoid: false },
    { name: "table", isVoid: false },
    { name: "tbody", isVoid: false },
    { name: "td", isVoid: false },
    { name: "th", isVoid: false },
    { name: "thead", isVoid: false },
    { name: "tr", isVoid: false },
    { name: "ul", isVoid: false },
    { name: "style", isVoid: false },
  ];

  const elementNamesToFormat = elementsToFormat
    .map((element) => element.name)
    .join("|");

  // Adiciona quebras de linha antes e depois das tags
  const lines = input
    .replace(new RegExp(`</?(${elementNamesToFormat})( .*?)?>`, "g"), "\n$&\n")
    .replace(/<br[^>]*>/g, "$&\n")
    .split("\n");

  let indentCount = 0;
  let isPreformattedLine = false;

  return lines
    .filter((line) => line.length)
    .map((line) => {
      isPreformattedLine = isPreformattedBlockLine(line, isPreformattedLine);

      if (isNonVoidOpeningTag(line, elementsToFormat)) {
        return indentLine(line, indentCount++);
      }

      if (isClosingTag(line, elementsToFormat)) {
        return indentLine(line, --indentCount);
      }

      if (isPreformattedLine === "middle" || isPreformattedLine === "last") {
        return line;
      }

      return indentLine(line, indentCount);
    })
    .join("\n");
}

/**
 * Decodifica entidades HTML para suas representações de caracteres.
 *
 * @param {string} html - HTML com entidades
 * @returns {string} HTML com entidades decodificadas
 */
function decodeHtmlEntities(html) {
  const entities = {
    "&quot;": '"',
    "&amp;": "&",
    "&lt;": "<",
    "&gt;": ">",
    "&nbsp;": " ",
    "&apos;": "'",
    "&#39;": "'",
    "&#x2F;": "/",
    "&#x27;": "'",
    "&#x60;": "`",
  };

  return html.replace(
    /&quot;|&amp;|&lt;|&gt;|&nbsp;|&apos;|&#39;|&#x2F;|&#x27;|&#x60;/g,
    (match) => entities[match],
  );
}

/**
 * Verifica se uma linha é uma tag de abertura de elemento não-void.
 *
 * @param {string} line - Linha a verificar
 * @param {Array} elementsToFormat - Elementos a formatar
 * @returns {boolean}
 */
function isNonVoidOpeningTag(line, elementsToFormat) {
  return elementsToFormat.some((element) => {
    if (element.isVoid) {
      return false;
    }

    if (!new RegExp(`<${element.name}( .*?)?>`).test(line)) {
      return false;
    }

    return true;
  });
}

/**
 * Verifica se uma linha é uma tag de fechamento.
 *
 * @param {string} line - Linha a verificar
 * @param {Array} elementsToFormat - Elementos a formatar
 * @returns {boolean}
 */
function isClosingTag(line, elementsToFormat) {
  return elementsToFormat.some((element) => {
    return new RegExp(`</${element.name}>`).test(line);
  });
}

/**
 * Indenta uma linha por um número especificado de caracteres.
 *
 * @param {string} line - Linha a indentar
 * @param {number} indentCount - Número de níveis de indentação
 * @param {string} indentChar - Caractere(s) de indentação (2 espaços por padrão)
 * @returns {string}
 */
function indentLine(line, indentCount, indentChar = "  ") {
  return `${indentChar.repeat(Math.max(0, indentCount))}${line}`;
}

/**
 * Verifica se uma linha pertence a um bloco pré-formatado (<pre>).
 *
 * @param {string} line - Linha a verificar
 * @param {string|boolean} isPreviousLinePreFormatted - Informação sobre a linha anterior
 * @returns {string|boolean}
 */
function isPreformattedBlockLine(line, isPreviousLinePreFormatted) {
  if (new RegExp("<pre( .*?)?>").test(line)) {
    return "first";
  } else if (new RegExp("</pre>").test(line)) {
    return "last";
  } else if (
    isPreviousLinePreFormatted === "first" ||
    isPreviousLinePreFormatted === "middle"
  ) {
    return "middle";
  } else {
    return false;
  }
}

/**
 * Converte CSS de <style> tags para inline styles.
 * Remove todas as classes e IDs, incorporando estilos inline.
 *
 * @param {string} html - HTML com CSS em <style> tags
 * @returns {string} HTML com CSS inline
 */
function inlineStyles(html) {
  try {
    // Cria um parser DOM
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, "text/html");

    // Extrai todas as regras CSS
    const styleElements = doc.querySelectorAll("style");
    const cssRules = [];

    styleElements.forEach((styleEl) => {
      const cssText = styleEl.textContent;
      // Parse simples de CSS (não cobre todos os casos, mas funciona para emails básicos)
      const ruleMatches = cssText.matchAll(/([^{]+)\{([^}]+)\}/g);

      for (const match of ruleMatches) {
        const selector = match[1].trim();
        const styles = match[2].trim();

        cssRules.push({ selector, styles });
      }
    });

    // Aplica estilos inline nos elementos correspondentes
    cssRules.forEach((rule) => {
      try {
        const elements = doc.querySelectorAll(rule.selector);
        elements.forEach((el) => {
          const existingStyle = el.getAttribute("style") || "";
          const newStyles = rule.styles
            .split(";")
            .map((s) => s.trim())
            .filter((s) => s.length > 0)
            .join("; ");

          if (newStyles) {
            const combinedStyles = existingStyle
              ? `${existingStyle}; ${newStyles}`
              : newStyles;
            el.setAttribute("style", combinedStyles);
          }
        });
      } catch (e) {
        // Ignora seletores inválidos
      }
    });

    // Remove tags <style> após aplicar
    styleElements.forEach((el) => el.remove());

    // Remove todos os atributos class e id
    const allElements = doc.querySelectorAll("*");
    allElements.forEach((el) => {
      el.removeAttribute("class");
      el.removeAttribute("id");
    });

    // Retorna HTML serializado
    return doc.documentElement.outerHTML;
  } catch (e) {
    console.error("Erro ao processar inlineStyles:", e);
    return html;
  }
}

/**
 * Atualiza a cor de fundo do email.
 * Se $hex for null, analisa o código e detecta a cor.
 * Atualiza: window.emailBackgroundColor, BG do editor, BG do ícone SVG
 *
 * @param {string|null} hex - Cor em formato hex ou null para detectar
 */
/**
 * Atualiza a cor de fundo do email e blinda contra resets do CKEditor
 */
function updateBackground(hex = null) {
  try {
    // Se não passarem cor, usa a que já está salva na memória (nunca reseta para branco se já tiver cor)
    if (!hex) {
      hex = window.emailBackgroundColor || "#ffffff";
    }

    // Atualiza variável global
    window.emailBackgroundColor = hex;

    // 1. Força a cor no editor usando !important para evitar que ele resete
    if (
      window.editor &&
      window.editor.ui.view.editable &&
      window.editor.ui.view.editable.element
    ) {
      window.editor.ui.view.editable.element.style.setProperty(
        "background-color",
        hex,
        "important",
      );
    }

    // 2. Atualiza a cor do ícone SVG
    if (window.bgColorDropdown && window.bgColorDropdown.buttonView.element) {
      const svg =
        window.bgColorDropdown.buttonView.element.querySelector("svg");
      if (svg) {
        svg.querySelectorAll("path, rect, circle").forEach((el) => {
          el.setAttribute("fill", hex !== "#ffffff" ? hex : "currentColor");
        });
      }
    }
  } catch (e) {
    console.error("Erro ao atualizar background:", e);
  }
}

/**
 * Função chamada ao SAIR do modo Source.
 * Executa: updateBackground() + inlineStyles()
 */
function sourceOut() {
  try {
    // 1. Atualiza background
    updateBackground();

    // 2. Aplica inline styles
    const html = editor.getData();
    const processedHtml = inlineStyles(html);
    editor.setData(processedHtml);
  } catch (e) {
    console.error("Erro ao sair do modo Source:", e);
  }
}

/**
 * Renderiza HTML completo para email.
 * Construa <html><head><body> com tudo necessário.
 * Inclui Google Fonts, BG color inline, estilos inline, sem classes/ids.
 *
 * @returns {string} HTML completo pronto para envio
 */
function renderHTML() {
  // Obtém HTML do editor
  let html = window.getRichEditorData();

  // Obtém fontes Google importadas (se houver)
  const googleFonts = window.importedGoogleFonts || [];

  try {
    // 1. Processa inline styles (remove classes, ids, <style> tags)
    html = inlineStyles(html);

    // 2. Cria estrutura HTML completa
    let headContent = "";

    // Adiciona meta tags essenciais para compatibilidade
    headContent += '  <meta charset="UTF-8">\n';
    headContent +=
      '  <meta name="viewport" content="width=device-width, initial-scale=1.0">\n';
    headContent += '  <meta http-equiv="X-UA-Compatible" content="IE=edge">\n';

    // Adiciona Google Fonts
    if (googleFonts && googleFonts.length > 0) {
      googleFonts.forEach((font) => {
        headContent += `  <link href="${font.url}" rel="stylesheet">\n`;
      });
    }

    // Extrai apenas o conteúdo da body (sem tags body)
    let bodyContent = html;
    const bodyMatch = html.match(/<body[^>]*>([\s\S]*)<\/body>/i);
    if (bodyMatch) {
      bodyContent = bodyMatch[1];
    }

    // Obtém cor de fundo
    const bgColor = window.emailBackgroundColor || "#ffffff";

    // Monta HTML final
    let finalHtml = "<!DOCTYPE html>\n";
    finalHtml += "<html>\n";
    finalHtml += "<head>\n";
    finalHtml += headContent;
    finalHtml += "</head>\n";
    finalHtml += `<body style="background-color: ${bgColor}; margin: 0; padding: 0;">\n`;
    finalHtml += bodyContent;
    finalHtml += "</body>\n";
    finalHtml += "</html>";

    // 3. Formata para legibilidade
    finalHtml = formatHtml(finalHtml);

    return finalHtml;
  } catch (e) {
    console.error("Erro ao renderizar HTML:", e);
    return contentHtml;
  }
}

// Exporta funções globalmente
window.renderHTML = renderHTML;
window.updateBackground = updateBackground;
window.sourceOut = sourceOut;
window.inlineStyles = inlineStyles;
window.formatHtml = formatHtml;
