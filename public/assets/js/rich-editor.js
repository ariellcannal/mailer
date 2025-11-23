(function() {
	'use strict';

	const configElement = document.getElementById('richEditorConfig');
	if (!configElement) {
		return;
	}

	const settings = {
		selector: configElement.dataset.selector || '.js-rich-editor',
		height: Number(configElement.dataset.height || 500),
		templateSearchUrl: configElement.dataset.templateSearchUrl || '',
		fileListUrl: configElement.dataset.fileListUrl || '',
		fileUploadUrl: configElement.dataset.fileUploadUrl || ''
	};

	const icons = {
		images: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M1.201 1C.538 1 0 1.47 0 2.1v14.363c0 .64.534 1.037 1.186 1.037h9.494a3 3 0 0 1-.414-.287 3 3 0 0 1-1.055-2.03 3 3 0 0 1 .693-2.185l.383-.455-.02.018-3.65-3.41a.695.695 0 0 0-.957-.034L1.5 13.6V2.5h15v5.535a2.97 2.97 0 0 1 1.412.932l.088.105V2.1c0-.63-.547-1.1-1.2-1.1zm11.713 2.803a2.146 2.146 0 0 0-2.049 1.992 2.14 2.14 0 0 0 1.28 2.096 2.13 2.13 0 0 0 2.644-3.11 2.13 2.13 0 0 0-1.875-.978"></path><path d="M15.522 19.1a.79.79 0 0 0 .79-.79v-5.373l2.059 2.455a.79.79 0 1 0 1.211-1.015l-3.352-3.995a.79.79 0 0 0-.995-.179.8.8 0 0 0-.299.221l-3.35 3.99a.79.79 0 1 0 1.21 1.017l1.936-2.306v5.185c0 .436.353.79.79.79"></path><path d="M15.522 19.1a.79.79 0 0 0 .79-.79v-5.373l2.059 2.455a.79.79 0 1 0 1.211-1.015l-3.352-3.995a.79.79 0 0 0-.995-.179.8.8 0 0 0-.299.221l-3.35 3.99a.79.79 0 1 0 1.21 1.017l1.936-2.306v5.185c0 .436.353.79.79.79"></path></svg>',
		library: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M17.5 9.303V8h-13v8.5h4.341c.191.54.457 1.044.785 1.5H2a1.5 1.5 0 0 1-1.5-1.5v-13A1.5 1.5 0 0 1 2 2h4.5a1.5 1.5 0 0 1 1.06.44L9.122 4H16a1.5 1.5 0 0 1 1.5 1.5v1A1.5 1.5 0 0 1 19 8v2.531a6 6 0 0 0-1.5-1.228M16 6.5v-1H8.5l-2-2H2v13h1V8a1.5 1.5 0 0 1 1.5-1.5z"></path><path d="M14.5 19.5a5 5 0 1 1 0-10 5 5 0 0 1 0 10M15 14v-2h-1v2h-2v1h2v2h1v-2h2v-1z"></path></svg>',
		templates: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M3 19a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v8.022a6.5 6.5 0 0 0-1.5-.709V2a.5.5 0 0 0-.5-.5H3a.5.5 0 0 0-.5.5v15a.5.5 0 0 0 .5.5h6.313c.173.534.412 1.037.709 1.5z"></path><path d="M9.174 14a6.5 6.5 0 0 0-.155 1H6v-1zm.848-2a6.5 6.5 0 0 0-.524 1H4v-1zm2.012-2c-.448.283-.86.62-1.224 1H6v-1zM12 4v1H4V4zm2 3V6H6v1zm1 2V8H7v1z"></path><path d="M20 15.5a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0M15.5 13a.5.5 0 0 0-.5.5V15h-1.5a.5.5 0 0 0 0 1H15v1.5a.5.5 0 0 0 1 0V16h1.5a.5.5 0 0 0 0-1H16v-1.5a.5.5 0 0 0-.5-.5" clip-rule="evenodd"></path></svg>',
		tags: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><circle cx="10" cy="9.8" r="1.5"></circle><path d="M13.25 2.75V2h.035a6 6 0 0 1 .363.014c.21.013.517.041.785.109.397.1.738.281 1.007.55s.429.587.524.907c.182.608.15 1.314.108 1.913l-.03.408c-.038.487-.073.93-.053 1.353.026.527.136.879.333 1.112.223.263.494.428.72.528a2 2 0 0 0 .335.117l.01.002.613.109v.628h-2.402a3.3 3.3 0 0 1-.42-.415c-.509-.601-.655-1.345-.687-2.009-.025-.527.02-1.094.059-1.592l.026-.347c.044-.621.044-1.067-.049-1.377a.63.63 0 0 0-.148-.276.64.64 0 0 0-.313-.157 3 3 0 0 0-.512-.066 6 6 0 0 0-.286-.01h-.016L13.25 3.5h-.75V2h.75z"></path><path d="M13.25 16.75v.75h.035a7 7 0 0 0 .363-.014 4.6 4.6 0 0 0 .785-.109c.397-.1.738-.28 1.007-.55.268-.269.429-.587.524-.907.182-.608.15-1.314.108-1.912l-.03-.41c-.038-.486-.073-.93-.053-1.352.026-.527.136-.879.333-1.112.223-.263.494-.428.72-.528a2 2 0 0 1 .335-.117l.01-.002.613-.109V9.75h-2.402a3.3 3.3 0 0 0-.42.416c-.509.6-.655 1.344-.687 2.008-.025.527.02 1.095.059 1.592l.026.347c.044.621.044 1.067-.049 1.378a.63.63 0 0 1-.148.275.64.64 0 0 1-.313.157 3 3 0 0 1-.512.066 6 6 0 0 1-.286.01l-.016.001H12.5v1.5h.75zm-6.5-14V2h-.035a6 6 0 0 0-.363.014 4.6 4.6 0 0 0-.785.109 2.13 2.13 0 0 0-1.008.55 2.1 2.1 0 0 0-.524.907c-.181.608-.15 1.314-.108 1.913l.031.408c.038.487.073.93.052 1.353-.025.527-.136.879-.333 1.112a2 2 0 0 1-.718.528 2 2 0 0 1-.337.117l-.01.002L2 9.122v.628h2.402a3.3 3.3 0 0 0 .42-.415c.509-.601.654-1.345.686-2.009.026-.527-.019-1.094-.058-1.592q-.015-.18-.026-.347c-.044-.621-.044-1.067.048-1.377a.63.63 0 0 1 .149-.276.64.64 0 0 1 .312-.157c.13-.032.323-.054.513-.066a6 6 0 0 1 .286-.01h.015L6.75 3.5h.75V2h-.75zm0 14v.75h-.035a7 7 0 0 1-.363-.014 4.6 4.6 0 0 1-.785-.109 2.13 2.13 0 0 1-1.008-.55 2.1 2.1 0 0 1-.524-.907c-.181-.608-.15-1.314-.108-1.912l.031-.41c.038-.486.073-.93.052-1.352-.025-.527-.136-.879-.333-1.112a2 2 0 0 0-.718-.528 2 2 0 0 0-.337-.117l-.01-.002L2 10.378V9.75h2.402q.218.178.42.416c.509.6.654 1.344.686 2.008.026.527-.019 1.095-.058 1.592q-.015.18-.026.347c-.044.621-.044 1.067.048 1.378a.63.63 0 0 0 .149.275.64.64 0 0 0 .312.157c.13.032.323.054.513.066a6 6 0 0 0 .286.01l.015.001H7.5v1.5h-.75z"></path></svg>',
		fullscreen: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M11.5 5.75a.75.75 0 0 1 0-1.5H15a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0V6.81l-2.72 2.72a.75.75 0 0 1-1.06-1.06l2.72-2.72zm-1.97 4.72a.75.75 0 0 1 0 1.06l-2.72 2.72H8.5a.75.75 0 0 1 0 1.5H5a.75.75 0 0 1-.75-.75v-3.5a.75.75 0 0 1 1.5 0v1.69l2.72-2.72a.75.75 0 0 1 1.06 0"></path><path d="M2 0h16a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2m16 1.5H2a.5.5 0 0 0-.5.5v16a.5.5 0 0 0 .5.5h16a.5.5 0 0 0 .5-.5V2a.5.5 0 0 0-.5-.5"></path></svg>'
	};

	const toolbarIcons = {
		undo: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="M7.707 4.293a1 1 0 0 0-1.414 0l-4 4a1 1 0 0 0 0 1.414l4 4a1 1 0 0 0 1.414-1.414L5.414 11H12a3 3 0 0 1 0 6h-3a1 1 0 1 0 0 2h3a5 5 0 1 0 0-10H5.414l2.293-2.293a1 1 0 0 0 0-1.414z"/></svg>',
		redo: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="M12.293 4.293a1 1 0 0 1 1.414 0l4 4a1 1 0 0 1 0 1.414l-4 4a1 1 0 1 1-1.414-1.414L14.586 11H8a3 3 0 0 0 0 6H5a1 1 0 1 1 0-2h3a1 1 0 1 0 0-2H5a5 5 0 1 1 0-10h7.586l-2.293-2.293a1 1 0 0 1 0-1.414z"/></svg>',
		findAndReplace: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="M11.5 3a5.5 5.5 0 1 1-4.422 8.778l-2.65 2.65a1 1 0 0 1-1.414-1.414l2.65-2.65A5.5 5.5 0 0 1 11.5 3m0 2a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7"/><path fill="#2e2e2e" d="M7 7.75a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 0 1.5H8.5v.5H10a.75.75 0 0 1 0 1.5H7.75a.75.75 0 0 1-.75-.75z"/></svg>',
		selectAll: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="M5 3a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zm0 2h2v2H5zm4 0h2v2H9zm4 0h2v2h-2zM5 9h2v2H5zm4 0h2v2H9zm4 0h2v2h-2zM5 13h2v2H5zm4 0h2v2H9zm4 0h2v2h-2z"/></svg>',
		heading: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="M4 4a1 1 0 0 1 1-1h2.5a1 1 0 1 1 0 2H6v4h8V5h-1.5a1 1 0 1 1 0-2H15a1 1 0 0 1 1 1v11a1 1 0 1 1-2 0v-4H6v4a1 1 0 1 1-2 0z"/></svg>',
		style: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="M4.5 4.5 6 6l1.5-1.5L9 6l1.5-1.5L12 6l1.5-1.5L15 6l1.5-1.5L18 6v8l-1.5-1.5L15 14l-1.5-1.5L12 14l-1.5-1.5L9 14l-1.5-1.5L6 14l-1.5-1.5L3 14V6Z"/></svg>',
		fontFamily: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="M5.5 16a1 1 0 0 1-.948-1.317l4-12a1 1 0 0 1 1.896.634l-4 12A1 1 0 0 1 5.5 16m9 0h-1a1 1 0 0 1-.894-1.447l3-6A1 1 0 0 1 16.5 8h1a1 1 0 0 1 .894 1.447l-3 6A1 1 0 0 1 14.5 16"></path><path fill="#2e2e2e" d="M2 15a1 1 0 0 1 .8-.98l12-2.4a1 1 0 0 1 .4 1.96l-12 2.4A1 1 0 0 1 2 15"></path></svg>',
		fontSize: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="M7 4a1 1 0 0 1 1-1h1.5a1 1 0 1 1 0 2H9v11a1 1 0 1 1-2 0zm6 5a1 1 0 0 1 1-1h1.75a1 1 0 1 1 0 2h-.75v6a1 1 0 1 1-2 0z"/></svg>',
		fontColor: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="M10 3a1 1 0 0 1 .949.684l4 12a1 1 0 0 1-1.898.632L12.215 13H7.786l-.836 2.316a1 1 0 1 1-1.896-.632l4-12A1 1 0 0 1 10 3m-1.357 8h2.714L10 6.885z"></path><rect fill="#2e2e2e" x="3" y="16" width="14" height="1.5" rx="0.75"/></svg>',
		fontBackgroundColor: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="M9.293 4.293a1 1 0 0 1 1.414 0l3 3a1 1 0 0 1 0 1.414L8.414 14H12a1 1 0 1 1 0 2H5a1 1 0 0 1-.707-1.707l7-7-1.586-1.586a1 1 0 0 1 0-1.414Z"></path><rect fill="#2e2e2e" x="3" y="16" width="14" height="1.5" rx="0.75"/></svg>',
		highlight: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="m8.293 14.707-2 2a1 1 0 0 1-1.414-1.414l2-2a1 1 0 0 1 1.122-.21l1.71-1.71-3-3a1 1 0 0 1 0-1.414l4-4a1 1 0 0 1 1.414 0l3 3a1 1 0 0 1 0 1.414l-4 4a1 1 0 0 1-1.122.21l-1.71 1.71a1 1 0 0 1-.99.204"></path><rect fill="#ffea00" x="2" y="15.5" width="16" height="2" rx="1"/></svg>',
		bold: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="M6 3a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h5a3.5 3.5 0 0 0 2.864-5.524A3.5 3.5 0 0 0 11 3zm5 5.5A1.5 1.5 0 0 1 12.5 10 1.5 1.5 0 0 1 11 11.5H7v-3zM7 13.5h4A1.5 1.5 0 1 1 11 16H7z"/></svg>',
		italic: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="M12 3a1 1 0 1 1 0 2h-1.38l-2.24 10H10a1 1 0 1 1 0 2H6a1 1 0 1 1 0-2h1.38l2.24-10H8a1 1 0 1 1 0-2z"/></svg>',
		underline: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="M6 3a1 1 0 0 1 1 1v5a3 3 0 0 0 6 0V4a1 1 0 1 1 2 0v5a5 5 0 0 1-10 0V4a1 1 0 0 1 1-1"></path><rect fill="#2e2e2e" x="4" y="16" width="12" height="1.5" rx="0.75"/></svg>',
		strikethrough: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="M6.28 6.22a2.7 2.7 0 0 0-.28 1.22h-2a4.7 4.7 0 0 1 .52-2.2C5.3 3.4 7.14 2.5 9.5 2.5c3.18 0 5.5 1.64 5.5 4.1 0 1.2-.44 2.08-1.2 2.78h-3.02c.66-.3 1.22-.8 1.22-1.68 0-1.06-.98-1.7-2.5-1.7-1.14 0-2.05.38-2.22 1.22M3 10h14a1 1 0 1 1 0 2h-4.3c.46.28.75.74.75 1.35 0 1.33-1.28 2.15-3.2 2.15-2.12 0-3.67-.92-4.1-2.5h2.09c.18.46.74.8 2.01.8 1.1 0 1.7-.32 1.7-.86 0-.46-.47-.84-1.45-.94L7.1 12.9A3 3 0 0 1 3 12v-.5z"/></svg>',
		code: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="m7.293 5.293-4 4a1 1 0 0 0 0 1.414l4 4 1.414-1.414L5.414 10l3.293-3.293zm5.414 0L11.293 6.707 14.586 10l-3.293 3.293 1.414 1.414 4-4a1 1 0 0 0 0-1.414z"/></svg>',
		subscript: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="m5.8 4.1 3.2 4.48L12.2 4.1h2.07l-4 5.4 4.2 5.5h-2.1l-2.27-3.05L7.8 15h-2.1l4.22-5.5-3.98-5.4zm8.95 8.15h-.75c-.25 0-.45.2-.45.44v.47h1.2c.82 0 1.25.62 1.25 1.28 0 .82-.6 1.41-1.55 1.41-.95 0-1.55-.6-1.55-1.33 0-.26.2-.46.45-.46.24 0 .45.2.45.45 0 .3.3.44.65.44.34 0 .55-.2.55-.5 0-.22-.13-.43-.55-.43h-1.2c-.25 0-.45-.2-.45-.44v-1.1c0-.78.6-1.41 1.55-1.41.95 0 1.55.62 1.55 1.36 0 .25-.2.45-.45.45-.24 0-.45-.2-.45-.45 0-.26-.3-.46-.65-.46z"/></svg>',
		superscript: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="m5.8 4.1 3.2 4.48L12.2 4.1h2.07l-4 5.4 4.2 5.5h-2.1l-2.27-3.05L7.8 15h-2.1l4.22-5.5-3.98-5.4zm8.95-.25h-.75c-.25 0-.45.2-.45.44v.47h1.2c.82 0 1.25.62 1.25 1.28 0 .82-.6 1.41-1.55 1.41-.95 0-1.55-.6-1.55-1.33 0-.26.2-.46.45-.46.24 0 .45.2.45.45 0 .3.3.44.65.44.34 0 .55-.2.55-.5 0-.22-.13-.43-.55-.43h-1.2c-.25 0-.45-.2-.45-.44V3.8c0-.78.6-1.41 1.55-1.41.95 0 1.55.62 1.55 1.36 0 .25-.2.45-.45.45-.24 0-.45-.2-.45-.45 0-.26-.3-.46-.65-.46z"/></svg>',
		removeFormat: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="m3.293 2.293 14.414 14.414-1.414 1.414-2.242-2.242L11.83 19H9v-2h1.17l1.151-1.15L8 12.53V17H6V5H3zm11.9 6.1-5.8-5.8 1.4-1.4 2.1 2.1H17v2h-1.207z"/></svg>',
		link: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="M7.05 11.536a3 3 0 0 1 0-4.242l2.122-2.122a3 3 0 0 1 4.243 0 1 1 0 1 1-1.414 1.414 1 1 0 0 0-1.415 0L8.464 8.707a1 1 0 0 0 1.415 1.414l1.414-1.414 1.414 1.414-1.414 1.414a3 3 0 0 1-4.243 0m8.486-4.95a5 5 0 0 0-7.071 0L6.343 8.707a5 5 0 1 0 7.071 7.071l1.414-1.414a1 1 0 1 0-1.414-1.414l-1.414 1.414a3 3 0 0 1-4.243-4.243l2.122-2.121a3 3 0 0 1 4.243 0 1 1 0 0 0 1.414-1.414z"/></svg>',
		blockQuote: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="M5 4h5v4H8l1 4H4L5 8H3zm8 0h5v4h-2l1 4h-5l1-4h-2z"/></svg>',
		uploadImage: icons.images,
		insertImage: icons.images,
		mediaEmbed: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="M4 4h12a2 2 0 0 1 2 2v8.5a1.5 1.5 0 0 1-1.5 1.5H3.5A1.5 1.5 0 0 1 2 14.5V6a2 2 0 0 1 2-2m0 2v8h12V6zm4.5 1.5v5l4-2.5z"/></svg>',
		insertTable: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="M4 4a2 2 0 0 0-2 2v8.5A1.5 1.5 0 0 0 3.5 16h13a1.5 1.5 0 0 0 1.5-1.5V6a2 2 0 0 0-2-2zm0 2h3v3H4zm5 0h3v3H9zm8 0v3h-3V6zM4 11h3v3H4zm5 0h3v3H9zm5 0h3v3h-3z"/></svg>',
		horizontalLine: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><rect fill="#2e2e2e" x="3" y="9" width="14" height="2" rx="1"/></svg>',
		pageBreak: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="M4 3h12a1 1 0 0 1 1 1v4H3V4a1 1 0 0 1 1-1m0 9h12v4a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1zm3-2h6v2H7zm-3 0h2v2H4zm12 0h2v2h-2z"/></svg>',
		specialCharacters: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="M9 3h2v6h2v2h-2v6H9v-6H7V9h2zm-5 4a1 1 0 0 1 0-2h1a1 1 0 1 1 0 2zm11 0a1 1 0 1 1 0-2h1a1 1 0 1 1 0 2z"/></svg>',
		codeBlock: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="M5 4h10a2 2 0 0 1 2 2v8.5a1.5 1.5 0 0 1-1.5 1.5H4.5A1.5 1.5 0 0 1 3 14.5V6a2 2 0 0 1 2-2m0 2v8h10V6zm2 1.5 2.5 2.5L7 12.5zm6 0-2.5 2.5L13 12.5z"/></svg>',
		alignment: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="M3 4h14v2H3zm0 4h10v2H3zm0 4h14v2H3zm0 4h10v2H3z"/></svg>',
		outdent: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="M3 4h14v2H3zm0 4h6v2H3zm0 4h14v2H3zm0 4h6v2H3zm9-7.414V6l-3 3 3 3z"/></svg>',
		indent: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="M3 4h14v2H3zm0 4h6v2H3zm0 4h14v2H3zm0 4h6v2H3zm5-6 3 3V8z"/></svg>',
		bulletedList: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="M5 5a2 2 0 1 1-4 0 2 2 0 0 1 4 0m5 0h8v2h-8zM5 10a2 2 0 1 1-4 0 2 2 0 0 1 4 0m5-1h8v2h-8zm-5 6a2 2 0 1 1-4 0 2 2 0 0 1 4 0m5-1h8v2h-8z"/></svg>',
		numberedList: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="M4.5 4h-1V3h2v6h-1zM4 11h1l-1.5 1.5L5 14H3v-1h1l-1-1zm5-6h8v2H9zm0 5h8v2H9zm0 5h8v2H9z"/></svg>',
		todoList: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="M4.5 4.5 6 6 9 3l-1.5-1.5L6 3 4.5 1.5 3 3zm0 6L6 12l3-3-1.5-1.5L6 9 4.5 7.5 3 9zm0 6L6 18l3-3-1.5-1.5L6 15l-1.5-1.5L3 15zm5.5-12h7v2h-7zm0 6h7v2h-7zm0 6h7v2h-7z"/></svg>',
		sourceEditing: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#2e2e2e" d="m6.293 3.293 1.414 1.414L4.414 8l3.293 3.293-1.414 1.414-4-4a1 1 0 0 1 0-1.414zm7.414 0-1.414 1.414L15.586 8l-3.293 3.293 1.414 1.414 4-4a1 1 0 0 0 0-1.414z"/></svg>'
	};

	function createModal(title, bodyHtml) {
		const modal = document.createElement('div');
		modal.className = 'ck ck-reset_all ck-rounded-corners ck-modal';
		modal.innerHTML = `
            <div class="ck-modal__backdrop"></div>
            <div class="ck-modal__dialog" role="dialog" aria-label="${title}">
                <div class="ck-modal__header">
                    <h3 class="ck-modal__title">${title}</h3>
                </div>
                <div class="ck-modal__body">${bodyHtml}</div>
                <div class="ck-modal__footer">
                    <button type="button" class="ck ck-button ck-button_with-text ck-modal__cancel">Cancelar</button>
                    <button type="button" class="ck ck-button ck-button_with-text ck-button_action ck-modal__confirm">Confirmar</button>
                </div>
            </div>
        `;
		document.body.appendChild(modal);
		document.body.classList.add('ck-editor-body-lock');

		function close() {
			modal.remove();
			document.body.classList.remove('ck-editor-body-lock');
		}

		modal.querySelector('.ck-modal__backdrop').addEventListener('click', close);
		modal.querySelector('.ck-modal__cancel').addEventListener('click', close);

		return { modal, close };
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
			editor.model.insertContent(modelFragment, editor.model.document?.selection);
			return;
		}

		if (editor.__editable) {
			editor.__editable.focus();
			document.execCommand('insertHTML', false, html);
		}
	}

	function fetchJson(url, options = {}) {
		return fetch(url, options).then((response) => response.json());
	}

	function createLocalCkeditorNamespace() {
		class BasicEmitter {
			constructor() {
				this.listeners = {};
			}

			on(eventName, callback) {
				if (!this.listeners[eventName]) {
					this.listeners[eventName] = [];
				}
				this.listeners[eventName].push(callback);
			}

			fire(eventName, payload) {
				(this.listeners[eventName] || []).forEach((listener) => listener(payload));
			}
		}

		class Model extends BasicEmitter {
			constructor(initialState = {}) {
				super();
				Object.assign(this, initialState);
			}

			set(state = {}) {
				Object.assign(this, state);
			}
		}

		class Collection extends Array { }

		class ButtonView extends BasicEmitter {
			constructor(locale) {
				super();
				this.locale = locale;
				this.label = '';
				this.icon = '';
				this.tooltip = '';
				this.withText = true;
			}

			set({ label, icon, tooltip, withText = true }) {
				this.label = label;
				this.icon = icon;
				this.tooltip = tooltip;
				this.withText = withText;
			}
		}

		class Dropdown {
			constructor(locale) {
				this.locale = locale;
				this.buttonView = new ButtonView(locale);
				this.items = [];
			}

			setItems(items = []) {
				this.items = items;
			}
		}

		const componentFactory = {
			components: new Map(),
			add(name, factory) {
				this.components.set(name, factory);
			},
			get(name) {
				return this.components.get(name);
			},
			has(name) {
				return this.components.has(name);
			}
		};

		const ui = {
			componentFactory,
			Model,
			dropdownUtils: {
				createDropdown: (locale) => new Dropdown(locale),
				addListToDropdown: (dropdown, items) => dropdown.setItems(items)
			},
			button: { ButtonView }
		};

		class Plugin {
			constructor(editor) {
				this.editor = editor;
			}
		}

		class ClassicEditor {
			constructor(element, config) {
				this.sourceElement = element;
				this.config = config || {};
				this.locale = 'pt-br';
				this.ui = ui;
				this.__editable = document.createElement('div');
				this.__editable.className = 'ck-fallback-editable form-control';
				this.__editable.contentEditable = 'true';
				this.__editable.style.minHeight = `${settings.height}px`;
				this.__editable.innerHTML = element.value;
				element.style.display = 'none';
				element.parentNode.insertBefore(this.__editable, element.nextSibling);

				this.editing = { view: { focus: () => this.__editable.focus() } };
				this.model = {
					document: { selection: null },
					insertContent: (content) => {
						if (typeof content === 'string') {
							this.__editable.focus();
							document.execCommand('insertHTML', false, content);
						}
					}
				};
				this.data = {
					processor: { toView: (value) => value },
					toModel: (value) => value,
					set: (html) => { this.__editable.innerHTML = html; },
					get: () => this.__editable.innerHTML
				};

				(this.config.extraPlugins || []).forEach((PluginClass) => {
					if (typeof PluginClass === 'function') {
						const plugin = new PluginClass(this);
						if (typeof plugin.init === 'function') {
							plugin.init();
						}
					}
				});

				this.__toolbar = this.buildToolbar(this.config.toolbar?.items || []);
				this.__editable.parentNode.insertBefore(this.__toolbar, this.__editable);
			}

			static create(element, config) {
				return Promise.resolve(new ClassicEditor(element, config));
			}

			buildToolbar(items = []) {
				const toolbar = document.createElement('div');
				toolbar.className = 'ck ck-reset ck-toolbar ck-toolbar_grouping ck-rounded-corners ck-fallback-toolbar ck-fallback-toolbar-advanced';

				const toolbarLabels = {
					undo: 'Desfazer',
					redo: 'Refazer',
					findAndReplace: 'Localizar e substituir',
					selectAll: 'Selecionar tudo',
					heading: 'Cabeçalho',
					style: 'Estilo',
					fontFamily: 'Fonte',
					fontSize: 'Tamanho da fonte',
					fontColor: 'Cor do texto',
					fontBackgroundColor: 'Cor de fundo',
					highlight: 'Marcador',
					bold: 'Negrito',
					italic: 'Itálico',
					underline: 'Sublinhado',
					strikethrough: 'Tachado',
					code: 'Código',
					subscript: 'Subscrito',
					superscript: 'Sobrescrito',
					removeFormat: 'Limpar formatação',
					link: 'Link',
					blockQuote: 'Citação',
					uploadImage: 'Enviar imagem',
					insertImage: 'Inserir imagem',
					mediaEmbed: 'Mídia',
					insertTable: 'Tabela',
					horizontalLine: 'Linha horizontal',
					pageBreak: 'Quebra de página',
					specialCharacters: 'Caracteres especiais',
					codeBlock: 'Bloco de código',
					alignment: 'Alinhamento',
					outdent: 'Recuar',
					indent: 'Avançar',
					bulletedList: 'Lista pontuada',
					numberedList: 'Lista numerada',
					todoList: 'Lista de tarefas',
					sourceEditing: 'Edição de código'
				};

				const actionMap = {
					undo: () => document.execCommand('undo'),
					redo: () => document.execCommand('redo'),
					findAndReplace: null,
					selectAll: () => document.execCommand('selectAll'),
					heading: () => document.execCommand('formatBlock', false, 'h2'),
					style: () => document.execCommand('formatBlock', false, 'p'),
					fontFamily: () => {
						const family = prompt('Fonte desejada (ex: Arial, Roboto)');
						if (family) { document.execCommand('fontName', false, family); }
					},
					fontSize: () => {
						const size = prompt('Tamanho da fonte (1 a 7)', '3');
						if (size) { document.execCommand('fontSize', false, size); }
					},
					fontColor: () => {
						const color = prompt('Cor da fonte (ex: #333333)');
						if (color) { document.execCommand('foreColor', false, color); }
					},
					fontBackgroundColor: () => {
						const color = prompt('Cor de fundo do texto (ex: #ffff00)');
						if (color) { document.execCommand('hiliteColor', false, color); }
					},
					highlight: () => document.execCommand('hiliteColor', false, '#ffff00'),
					bold: () => document.execCommand('bold'),
					italic: () => document.execCommand('italic'),
					underline: () => document.execCommand('underline'),
					strikethrough: () => document.execCommand('strikeThrough'),
					code: () => document.execCommand('formatBlock', false, 'pre'),
					subscript: () => document.execCommand('subscript'),
					superscript: () => document.execCommand('superscript'),
					removeFormat: () => document.execCommand('removeFormat'),
					link: () => {
						const url = prompt('Informe a URL do link');
						if (url) { document.execCommand('createLink', false, url); }
					},
					blockQuote: () => document.execCommand('formatBlock', false, 'blockquote'),
					uploadImage: () => (typeof this.__openImageLibrary === 'function' ? this.__openImageLibrary() : null),
					insertImage: () => (typeof this.__openImageLibrary === 'function' ? this.__openImageLibrary() : null),
					mediaEmbed: () => {
						const url = prompt('URL do conteúdo de mídia');
						if (url) { document.execCommand('insertHTML', false, `<iframe src="${url}" style="width:100%;height:320px;" allowfullscreen></iframe>`); }
					},
					insertTable: () => this.insertTable(),
					horizontalLine: () => document.execCommand('insertHorizontalRule'),
					pageBreak: () => document.execCommand('insertHorizontalRule'),
					specialCharacters: null,
					codeBlock: () => document.execCommand('formatBlock', false, 'pre'),
					alignment: () => document.execCommand('justifyLeft'),
					outdent: () => document.execCommand('outdent'),
					indent: () => document.execCommand('indent'),
					bulletedList: () => document.execCommand('insertUnorderedList'),
					numberedList: () => document.execCommand('insertOrderedList'),
					todoList: () => document.execCommand('insertUnorderedList'),
					sourceEditing: null
				};

				const createButton = (label, command, icon, withText = true) => {
					const button = document.createElement('button');
					button.type = 'button';
					button.className = 'ck ck-button ck-rounded-corners ck-fallback-button';
					if (withText) {
						button.classList.add('ck-button_with-text', 'ck-fallback-button--text');
					} else {
						button.classList.add('ck-fallback-button--icon-only');
					}

					if (icon) {
						const labelSpan = `<span class="ck-fallback-label">${label}</span>`;
						button.innerHTML = withText
							? `<img alt="" src="${icon}" class="ck-fallback-icon"> ${labelSpan}`
							: `<img alt="${label}" src="${icon}" class="ck-fallback-icon">${labelSpan}`;
					} else {
						button.innerHTML = `<span class="ck-fallback-label">${label}</span>`;
					}
					button.title = label;
					if (typeof command === 'function') {
						button.addEventListener('click', () => {
							this.__editable.focus();
							command();
						});
					} else {
						button.classList.add('ck-disabled');
						button.disabled = true;
						button.title = `${label} indisponível offline`;
					}
					toolbar.appendChild(button);
				};

				const renderDropdown = (dropdown) => {
					const wrapper = document.createElement('div');
					wrapper.className = 'ck ck-dropdown ck-rounded-corners ck-fallback-dropdown';
					const button = document.createElement('button');
					button.type = 'button';
					button.className = 'ck ck-button ck-rounded-corners ck-fallback-button';
					if (dropdown.buttonView.withText) {
						button.classList.add('ck-button_with-text', 'ck-fallback-button--text');
					} else {
						button.classList.add('ck-fallback-button--icon-only');
					}
					if (dropdown.buttonView.icon) {
						button.innerHTML = dropdown.buttonView.withText === false
							? `<img alt="${dropdown.buttonView.label}" src="${dropdown.buttonView.icon}" class="ck-fallback-icon"><span class="ck-fallback-label">${dropdown.buttonView.label}</span>`
							: `<img alt="" src="${dropdown.buttonView.icon}" class="ck-fallback-icon"> <span class="ck-fallback-label">${dropdown.buttonView.label}</span>`;
					} else {
						button.innerHTML = `<span class="ck-fallback-label">${dropdown.buttonView.label}</span>`;
					}
					const menu = document.createElement('div');
					menu.className = 'ck ck-toolbar ck-toolbar_grouping ck-fallback-dropdown__menu';
					dropdown.items.forEach((item) => {
						if (item.model) {
							const option = document.createElement('button');
							option.type = 'button';
							option.className = 'ck ck-button ck-button_with-text ck-rounded-corners ck-fallback-button';
							option.textContent = item.model.label;
							option.addEventListener('click', () => item.model.fire('execute'));
							menu.appendChild(option);
						}
					});
					button.addEventListener('click', () => menu.classList.toggle('show'));
					wrapper.appendChild(button);
					wrapper.appendChild(menu);
					toolbar.appendChild(wrapper);
				};

				items.forEach((item) => {
					if (item === '|') {
						const divider = document.createElement('span');
						divider.className = 'ck-fallback-divider';
						toolbar.appendChild(divider);
						return;
					}

					if (componentFactory.has(item)) {
						const view = componentFactory.get(item)(this.locale);
						if (view instanceof Dropdown) {
							renderDropdown(view);
						} else if (view instanceof ButtonView) {
							createButton(view.label, () => view.fire('execute'), view.icon, view.withText);
							view.on('execute', () => view.fire('execute'));
						}
						return;
					}

					if (Object.prototype.hasOwnProperty.call(actionMap, item)) {
						const icon = toolbarIcons[item];
						const label = toolbarLabels[item] || item;
						createButton(label, actionMap[item], icon, !icon);
						return;
					}

					createButton(item, null);
				});

				return toolbar;
			}

			insertTable() {
				const rows = parseInt(prompt('Quantidade de linhas?', '2'), 10);
				const cols = parseInt(prompt('Quantidade de colunas?', '2'), 10);
				if (!rows || !cols) { return; }
				const table = Array.from({ length: rows }, () => `<tr>${'<td>&nbsp;</td>'.repeat(cols)}</tr>`).join('');
				document.execCommand('insertHTML', false, `<table class="table table-bordered">${table}</table>`);
			}

			updateSourceElement() {
				if (this.sourceElement) {
					this.sourceElement.value = this.__editable.innerHTML;
				}
			}

			getData() {
				return this.__editable.innerHTML;
			}
		}

		if (window.jQuery && !window.jQuery.fn.ckeditor) {
			window.jQuery.fn.ckeditor = function(options) {
				const element = this[0];
				return ClassicEditor.create(element, options);
			};
		}

		return { ClassicEditor, Plugin, utils: { Collection }, ui };
	}

	const CKEDITOR_NS = window.CKEDITOR || (window.CKEDITOR = createLocalCkeditorNamespace());
	const BasePlugin = CKEDITOR_NS?.Plugin || CKEDITOR_NS?.core?.Plugin;

	class ImagesDropdownPlugin extends BasePlugin {
		static get pluginName() { return 'ImagesDropdownPlugin'; }

		init() {
			const editor = this.editor;
			const dropdownUtils = CKEDITOR_NS.ui.dropdownUtils;

			const addUpload = () => {
				const fileInput = document.createElement('input');
				fileInput.type = 'file';
				fileInput.accept = 'image/*';
				fileInput.onchange = (event) => {
					const [file] = event.target.files;
					if (!file) { return; }
					const data = new FormData();
					data.append('file', file);
					fetchJson(settings.fileUploadUrl, { method: 'POST', body: data })
						.then((payload) => {
							if (payload.success && payload.file?.url) {
								insertHtml(editor, `<img src="${payload.file.url}" alt="Imagem enviada" style="max-width:100%;height:auto;">`);
							} else {
								alert('Falha ao enviar a imagem.');
							}
						})
						.catch(() => alert('Erro ao concluir o upload.'));
				};
				fileInput.click();
			};

			const addFromUrl = () => {
				const { modal, close } = createModal('Inserir imagem via URL', `
                    <div class="ck-form-row">
                        <label class="ck-form-label">URL da imagem</label>
                        <input type="url" class="ck ck-input" id="ck-image-url" placeholder="https://...">
                    </div>
                    <div class="ck-form-row">
                        <label class="ck-form-label">Texto alternativo</label>
                        <input type="text" class="ck ck-input" id="ck-image-alt" placeholder="Descrição da imagem">
                    </div>
                `);
				modal.querySelector('.ck-modal__confirm').addEventListener('click', () => {
					const url = modal.querySelector('#ck-image-url').value.trim();
					const alt = modal.querySelector('#ck-image-alt').value.trim() || 'Imagem';
					if (url) {
						insertHtml(editor, `<img src="${url}" alt="${alt}" style="max-width:100%;height:auto;">`);
					}
					close();
				});
			};

			const addFromLibrary = () => {
				const body = `
                    <div class="ck-library-actions">
                        <button type="button" class="ck ck-button ck-button_with-text ck-button_action" id="ck-image-upload">Enviar imagem</button>
                        <div class="ck-text-muted" id="ck-image-status">Carregando Banco de Imagens...</div>
                    </div>
                    <div class="ck-image-grid" id="ck-image-grid"></div>
                `;
				const { modal, close } = createModal('Banco de Imagens', body);
				const grid = modal.querySelector('#ck-image-grid');
				const status = modal.querySelector('#ck-image-status');
				let selected = '';

				const renderFiles = (files) => {
					grid.innerHTML = '';
					if (!files.length) {
						status.textContent = 'Nenhuma imagem disponível.';
						return;
					}
					status.textContent = `${files.length} arquivo(s) encontrado(s).`;
					files.forEach((file) => {
						const card = document.createElement('button');
						card.type = 'button';
						card.className = 'ck-image-card ck ck-button ck-button_with-text';
						card.innerHTML = `
                            <div class="ck-image-card__thumb" style="background-image:url('${file.url}')"></div>
                            <div class="ck-image-card__meta">
                                <span class="fw-bold">${file.name}</span>
                                <span class="ck-text-muted">${(file.size / 1024).toFixed(1)} KB</span>
                            </div>
                        `;
						card.addEventListener('click', () => {
							grid.querySelectorAll('.ck-image-card').forEach((item) => item.classList.remove('is-active'));
							card.classList.add('is-active');
							selected = file.url;
						});
						grid.appendChild(card);
					});
				};

				const loadFiles = () => {
					status.textContent = 'Carregando Banco de Imagens...';
					fetchJson(settings.fileListUrl)
						.then((payload) => {
							if (!payload.success) {
								status.textContent = 'Não foi possível carregar o Banco de Imagens.';
								return;
							}
							renderFiles(payload.files || []);
						})
						.catch(() => { status.textContent = 'Erro ao carregar arquivos.'; });
				};

				modal.querySelector('#ck-image-upload').addEventListener('click', addUpload);
				modal.querySelector('.ck-modal__confirm').addEventListener('click', () => {
					if (selected) {
						insertHtml(editor, `<img src="${selected}" alt="Imagem do Banco" style="max-width:100%;height:auto;">`);
					}
					close();
				});

				loadFiles();
			};

			editor.__openImageLibrary = addFromLibrary;

			editor.ui.componentFactory.add('Imagens', (locale) => {
				const dropdown = dropdownUtils.createDropdown(locale);
				dropdown.buttonView.set({ label: 'Imagens', icon: icons.images, tooltip: 'Inserir imagem', withText: false });
				const options = new CKEDITOR_NS.utils.Collection(
					[
						{ id: 'upload', label: 'Enviar Imagem (Upload)', action: addUpload },
						{ id: 'url', label: 'Inserir da URL', action: addFromUrl },
						{ id: 'library', label: 'Inserir do Banco de Imagens', action: addFromLibrary },
					].map((item) => {
						const model = new CKEDITOR_NS.ui.Model({ label: item.label, withText: true });
						model.on('execute', item.action);
						return { type: 'button', model };
					})
				);
				dropdownUtils.addListToDropdown(dropdown, options);
				return dropdown;
			});
		}
	}

	class ImageLibraryPlugin extends BasePlugin {
		static get pluginName() { return 'ImageLibraryPlugin'; }

		init() {
			const editor = this.editor;
			editor.ui.componentFactory.add('BancoImagens', (locale) => {
				const button = new CKEDITOR_NS.ui.button.ButtonView(locale);
				button.set({ label: 'Banco de Imagens', icon: icons.library, tooltip: 'Abrir Banco de Imagens', withText: false });
				button.on('execute', () => {
					if (typeof editor.__openImageLibrary === 'function') {
						editor.__openImageLibrary();
					}
				});
				return button;
			});
		}
	}

	class TemplatesPlugin extends BasePlugin {
		static get pluginName() { return 'TemplatesPlugin'; }

		init() {
			const editor = this.editor;
			editor.ui.componentFactory.add('Templates', (locale) => {
				const button = new CKEDITOR_NS.ui.button.ButtonView(locale);
				button.set({ label: 'Templates', icon: icons.templates, tooltip: 'Inserir template', withText: false });
				button.on('execute', () => this.openTemplateModal(editor));
				return button;
			});
		}

		openTemplateModal(editor) {
			const body = `
                <div class="mb-3">
                    <input type="search" class="ck ck-input" id="ck-template-search" placeholder="Busque pelo nome ou descrição">
                </div>
                <div class="row g-3">
                    <div class="col-md-5">
                        <div class="ck-template-list" id="ck-template-list"></div>
                    </div>
                    <div class="col-md-7">
                        <div class="ck-template-preview" id="ck-template-preview">Selecione um template para visualizar.</div>
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" id="ck-template-replace">
                            <label class="form-check-label" for="ck-template-replace">Substituir todo o conteúdo</label>
                        </div>
                    </div>
                </div>
            `;
			const { modal, close } = createModal('Selecionar template', body);
			const listElement = modal.querySelector('#ck-template-list');
			const preview = modal.querySelector('#ck-template-preview');
			const replaceToggle = modal.querySelector('#ck-template-replace');
			let selectedHtml = '';

			const renderTemplates = (templates) => {
				listElement.innerHTML = '';
				if (!templates.length) {
					listElement.innerHTML = '<div class="ck-text-muted">Nenhum template encontrado.</div>';
					preview.innerHTML = 'Selecione um template para visualizar.';
					selectedHtml = '';
					return;
				}

				templates.forEach((template) => {
					const card = document.createElement('button');
					card.type = 'button';
					card.className = 'ck-template-card ck ck-button ck-button_with-text';
					card.innerHTML = `<strong>${template.name}</strong><br><span class="ck-text-muted">${template.description || 'Sem descrição'}</span>`;
					card.addEventListener('click', () => {
						listElement.querySelectorAll('.ck-template-card').forEach((item) => item.classList.remove('is-active'));
						card.classList.add('is-active');
						selectedHtml = template.html_content || '';
						preview.innerHTML = selectedHtml || 'Sem conteúdo para exibir.';
					});
					listElement.appendChild(card);
				});
				const first = listElement.querySelector('.ck-template-card');
				first?.click();
			};

			const loadTemplates = (term = '') => {
				const url = `${settings.templateSearchUrl}?q=${encodeURIComponent(term)}`;
				fetchJson(url)
					.then((payload) => {
						if (!payload.success) {
							listElement.innerHTML = '<div class="ck-text-muted">Não foi possível carregar os templates.</div>';
							return;
						}
						renderTemplates(payload.templates || []);
					})
					.catch(() => { listElement.innerHTML = '<div class="ck-text-muted">Erro ao buscar templates.</div>'; });
			};

			modal.querySelector('#ck-template-search').addEventListener('input', (event) => {
				loadTemplates(event.target.value || '');
			});

			modal.querySelector('.ck-modal__confirm').addEventListener('click', () => {
				if (!selectedHtml) {
					close();
					return;
				}
				if (replaceToggle.checked) {
					editor.setData(selectedHtml);
				} else {
					insertHtml(editor, selectedHtml);
				}
				close();
			});

			loadTemplates('');
		}
	}

	class TagsPlugin extends BasePlugin {
		static get pluginName() { return 'TagsPlugin'; }

		init() {
			const editor = this.editor;
			const dropdownUtils = CKEDITOR_NS.ui.dropdownUtils;
			editor.ui.componentFactory.add('Tags', (locale) => {
				const dropdown = dropdownUtils.createDropdown(locale);
				dropdown.buttonView.set({ label: 'TAGs', icon: icons.tags, tooltip: 'Inserir TAG', withText: false });
				const tags = [
					{ label: 'Nome', html: '{{nome}}' },
					{ label: 'E-mail', html: '{{email}}' },
					{ label: 'Link de Visualização', html: '<a href="{{webview_link}}" target="_blank">Link de Visualização</a>' },
					{ label: 'Link Opt-out', html: '<a href="{{optout_link}}" target="_blank">Link Opt-out</a>' },
				];
				const definitions = new CKEDITOR_NS.utils.Collection(tags.map((tag) => {
					const model = new CKEDITOR_NS.ui.Model({ label: tag.label, withText: true });
					model.on('execute', () => insertHtml(editor, tag.html));
					return { type: 'button', model };
				}));
				dropdownUtils.addListToDropdown(dropdown, definitions);
				return dropdown;
			});
		}
	}

	class FullscreenPlugin extends BasePlugin {
		static get pluginName() { return 'FullscreenPlugin'; }

		init() {
			const editor = this.editor;
			editor.ui.componentFactory.add('TelaCheia', (locale) => {
				const button = new CKEDITOR_NS.ui.button.ButtonView(locale);
				button.set({ label: 'Tela cheia', icon: icons.fullscreen, tooltip: 'Expandir editor', withText: false });
				button.on('execute', () => {
					const wrapper = editor.ui.getEditableElement()?.closest('.ck-editor');
					if (!wrapper) { return; }
					wrapper.classList.toggle('ck-editor--fullscreen');
					document.body.classList.toggle('ck-editor-body-lock', wrapper.classList.contains('ck-editor--fullscreen'));
				});
				return button;
			});
		}
	}

	function initEditors() {
		const EditorClass = CKEDITOR_NS?.ClassicEditor;
		const $ = window.jQuery;
		const instances = [];

		document.querySelectorAll(settings.selector).forEach((element) => {
			EditorClass
				.create(element, {
					licenseKey: 'GPL',
					language: 'pt-br',
					extraPlugins: [ImagesDropdownPlugin, ImageLibraryPlugin, TemplatesPlugin, TagsPlugin, FullscreenPlugin],
					toolbar: {
						items: [
							'undo', 'redo', '|', 'findAndReplace', 'selectAll', '|',
							'heading', 'style', '|', 'fontFamily', 'fontSize', 'fontColor', 'fontBackgroundColor', 'highlight', '|',
							'bold', 'italic', 'underline', 'strikethrough', 'code', 'subscript', 'superscript', 'removeFormat', '|',
							'link', 'blockQuote', 'uploadImage', 'insertImage', 'mediaEmbed', 'insertTable', 'horizontalLine', 'pageBreak', 'specialCharacters', 'codeBlock', '|',
							'alignment', 'outdent', 'indent', 'bulletedList', 'numberedList', 'todoList', '|',
							'Imagens', 'BancoImagens', 'Templates', 'Tags', 'TelaCheia', '|',
							'sourceEditing'
						],
						shouldNotGroupWhenFull: true
					}
				}).then((editor) => {
					editor.updateSourceElement();
					instances.push(editor);
				});
		});

		window.richEditorInstances = instances;
		window.syncRichEditors = () => {
			instances.forEach((instance) => {
				if (typeof instance.updateSourceElement === 'function') {
					instance.updateSourceElement();
				}
			});
		};

		window.getRichEditorData = () => {
			const first = instances[0];
			if (first && typeof first.getData === 'function') {
				return first.getData();
			}
			const element = document.querySelector(settings.selector);
			return element ? element.value : '';
		};
	}

	document.addEventListener('DOMContentLoaded', initEditors);
})();
