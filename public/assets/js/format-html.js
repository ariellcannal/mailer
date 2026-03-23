/**
 * Formata o HTML para melhor legibilidade e compatibilidade com clientes de email.
 * Baseado no exemplo: https://github.com/ckeditor/ckeditor5-demos/blob/master/email-editing/format-html.js
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
		{ name: 'html', isVoid: false },
		{ name: 'head', isVoid: false },
		{ name: 'body', isVoid: false },
		{ name: 'address', isVoid: false },
		{ name: 'article', isVoid: false },
		{ name: 'aside', isVoid: false },
		{ name: 'blockquote', isVoid: false },
		{ name: 'details', isVoid: false },
		{ name: 'dialog', isVoid: false },
		{ name: 'dd', isVoid: false },
		{ name: 'div', isVoid: false },
		{ name: 'dl', isVoid: false },
		{ name: 'dt', isVoid: false },
		{ name: 'fieldset', isVoid: false },
		{ name: 'figcaption', isVoid: false },
		{ name: 'figure', isVoid: false },
		{ name: 'footer', isVoid: false },
		{ name: 'form', isVoid: false },
		{ name: 'h1', isVoid: false },
		{ name: 'h2', isVoid: false },
		{ name: 'h3', isVoid: false },
		{ name: 'h4', isVoid: false },
		{ name: 'h5', isVoid: false },
		{ name: 'h6', isVoid: false },
		{ name: 'header', isVoid: false },
		{ name: 'hgroup', isVoid: false },
		{ name: 'hr', isVoid: true },
		{ name: 'li', isVoid: false },
		{ name: 'main', isVoid: false },
		{ name: 'nav', isVoid: false },
		{ name: 'ol', isVoid: false },
		{ name: 'p', isVoid: false },
		{ name: 'section', isVoid: false },
		{ name: 'table', isVoid: false },
		{ name: 'tbody', isVoid: false },
		{ name: 'td', isVoid: false },
		{ name: 'th', isVoid: false },
		{ name: 'thead', isVoid: false },
		{ name: 'tr', isVoid: false },
		{ name: 'ul', isVoid: false },
		{ name: 'style', isVoid: false }
	];

	const elementNamesToFormat = elementsToFormat.map(element => element.name).join('|');

	// Adiciona quebras de linha antes e depois das tags
	const lines = input
		.replace(new RegExp(`</?(${ elementNamesToFormat })( .*?)?>`, 'g'), '\n$&\n')
		.replace(/<br[^>]*>/g, '$&\n')
		.split('\n');

	let indentCount = 0;
	let isPreformattedLine = false;

	return lines
		.filter(line => line.length)
		.map(line => {
			isPreformattedLine = isPreformattedBlockLine(line, isPreformattedLine);

			if (isNonVoidOpeningTag(line, elementsToFormat)) {
				return indentLine(line, indentCount++);
			}

			if (isClosingTag(line, elementsToFormat)) {
				return indentLine(line, --indentCount);
			}

			if (isPreformattedLine === 'middle' || isPreformattedLine === 'last') {
				return line;
			}

			return indentLine(line, indentCount);
		})
		.join('\n');
}

/**
 * Decodifica entidades HTML para suas representações de caracteres.
 * 
 * @param {string} html - HTML com entidades
 * @returns {string} HTML com entidades decodificadas
 */
function decodeHtmlEntities(html) {
	const entities = {
		'&quot;': '"',
		'&amp;': '&',
		'&lt;': '<',
		'&gt;': '>',
		'&nbsp;': ' ',
		'&apos;': "'",
		'&#39;': "'",
		'&#x2F;': '/',
		'&#x27;': "'",
		'&#x60;': '`'
	};

	return html.replace(/&quot;|&amp;|&lt;|&gt;|&nbsp;|&apos;|&#39;|&#x2F;|&#x27;|&#x60;/g,
		match => entities[match]);
}

/**
 * Verifica se uma linha é uma tag de abertura de elemento não-void.
 * 
 * @param {string} line - Linha a verificar
 * @param {Array} elementsToFormat - Elementos a formatar
 * @returns {boolean}
 */
function isNonVoidOpeningTag(line, elementsToFormat) {
	return elementsToFormat.some(element => {
		if (element.isVoid) {
			return false;
		}

		if (!new RegExp(`<${ element.name }( .*?)?>`).test(line)) {
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
	return elementsToFormat.some(element => {
		return new RegExp(`</${ element.name }>`).test(line);
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
function indentLine(line, indentCount, indentChar = '  ') {
	return `${ indentChar.repeat(Math.max(0, indentCount)) }${ line }`;
}

/**
 * Verifica se uma linha pertence a um bloco pré-formatado (<pre>).
 * 
 * @param {string} line - Linha a verificar
 * @param {string|boolean} isPreviousLinePreFormatted - Informação sobre a linha anterior
 * @returns {string|boolean}
 */
function isPreformattedBlockLine(line, isPreviousLinePreFormatted) {
	if (new RegExp('<pre( .*?)?>'). test(line)) {
		return 'first';
	} else if (new RegExp('</pre>').test(line)) {
		return 'last';
	} else if (isPreviousLinePreFormatted === 'first' || isPreviousLinePreFormatted === 'middle') {
		return 'middle';
	} else {
		return false;
	}
}

/**
 * Converte CSS de <style> tags para inline styles.
 * Simplificado para funcionar sem bibliotecas externas.
 * 
 * @param {string} html - HTML com CSS em <style> tags
 * @returns {string} HTML com CSS inline
 */
function inlineStyles(html) {
	// Preserva o style da body antes de processar
	// Tenta encontrar style na tag body (com ou sem outros atributos)
	let bodyStyle = null;
	const bodyMatch = html.match(/<body[^>]*>/i);
	if (bodyMatch) {
		const styleMatch = bodyMatch[0].match(/style="([^"]*)"/);
		if (styleMatch) {
			bodyStyle = styleMatch[1];
		}
	}
	
	// Cria um parser DOM
	const parser = new DOMParser();
	const doc = parser.parseFromString(html, 'text/html');
	
	// Extrai todas as regras CSS
	const styleElements = doc.querySelectorAll('style');
	const cssRules = [];
	
	styleElements.forEach(styleEl => {
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
	cssRules.forEach(rule => {
		try {
			const elements = doc.querySelectorAll(rule.selector);
			elements.forEach(el => {
				const existingStyle = el.getAttribute('style') || '';
				const newStyles = rule.styles
					.split(';')
					.map(s => s.trim())
					.filter(s => s.length > 0)
					.join('; ');
				
				if (newStyles) {
					const combinedStyles = existingStyle 
						? `${existingStyle}; ${newStyles}` 
						: newStyles;
					el.setAttribute('style', combinedStyles);
				}
			});
		} catch (e) {
			// Ignora seletores inválidos
			console.warn('Seletor CSS inválido:', rule.selector, e);
		}
	});
	
	// Remove tags <style> após aplicar
	styleElements.forEach(el => el.remove());
	
	// Retorna HTML serializado
	let result = doc.documentElement.outerHTML;
	
	// Restaura o style da body se foi preservado
	if (bodyStyle) {
		result = result.replace(/<body([^>]*)>/i, (match, attrs) => {
			// Se já tem style, não sobrescreve
			if (/style="/.test(match)) {
				return match;
			}
			return `<body${attrs} style="${bodyStyle}">`;
		});
	}
	
	return result;
}

/**
 * Ajusta HTML para compatibilidade com clientes de email antigos.
 * 
 * @param {string} html - HTML a ser ajustado
 * @returns {string} HTML ajustado
 */
function adjustForOldEmailClients(html) {
	// Adiciona DOCTYPE se não existir
	if (!html.trim().toLowerCase().startsWith('<!doctype')) {
		html = '<!DOCTYPE html>\n' + html;
	}
	
	// Garante que tabelas tenham atributos necessários
	html = html.replace(/<table(?![^>]*cellpadding)/gi, '<table cellpadding="0"');
	html = html.replace(/<table(?![^>]*cellspacing)/gi, '<table cellspacing="0"');
	html = html.replace(/<table(?![^>]*border)/gi, '<table border="0"');
	
	return html;
}

/**
 * Processa HTML completo para email: inline styles + ajustes + formatação.
 * 
 * @param {string} html - HTML bruto do editor
 * @param {Array} googleFonts - Array de fontes Google importadas [{name, url}]
 * @returns {string} HTML processado e pronto para email
 */
function processEmailHtml(html, googleFonts = []) {
	
	// 1. Adiciona Google Fonts ao <head> se houver
	if (googleFonts && googleFonts.length > 0) {
		const fontLinks = googleFonts
			.map(font => `<link href="${font.url}" rel="stylesheet">`)
			.join('\n');
		
		// Insere dentro do <head> ou cria <head> se não existir
		if (html.includes('<head>')) {
			html = html.replace('<head>', `<head>\n${fontLinks}`);
		} else if (html.includes('<html>')) {
			html = html.replace('<html>', `<html>\n<head>\n${fontLinks}\n</head>`);
		} else {
			html = `<html>\n<head>\n${fontLinks}\n</head>\n<body>\n${html}\n</body>\n</html>`;
		}
	}
	
	// 2. Converte CSS para inline
	html = inlineStyles(html);
	
	// 3. Ajusta para clientes antigos
	html = adjustForOldEmailClients(html);
	
	// 4. Formata para legibilidade
	html = formatHtml(html);
	
	return html;
}

// Exporta funções globalmente
window.formatHtml = formatHtml;
window.inlineStyles = inlineStyles;
window.adjustForOldEmailClients = adjustForOldEmailClients;
window.processEmailHtml = processEmailHtml;
