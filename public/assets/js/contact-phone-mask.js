(function() {
	'use strict';

	function formatBrazilPhone(input) {
		let digits = input.value.replace(/\D/g, '');

		if ((digits.length === 12 || digits.length === 13) && digits.startsWith('55')) {
			digits = digits.slice(2);
		}

		digits = digits.slice(0, 11);
		if (digits.length === 0) {
			input.value = '';
			return;
		}

		if (digits.length <= 2) {
			input.value = `(${digits}`;
			return;
		}

		const areaCode = digits.slice(0, 2);
		const localNumber = digits.slice(2);
		const firstBlockLength = digits.length > 10 ? 5 : 4;
		const firstBlock = localNumber.slice(0, firstBlockLength);
		const lastBlock = localNumber.slice(firstBlockLength);

		input.value = `(${areaCode}) ${firstBlock}${lastBlock ? `-${lastBlock}` : ''}`;
	}

	document.addEventListener('DOMContentLoaded', function() {
		document.querySelectorAll('[data-br-phone-mask]').forEach(function(input) {
			formatBrazilPhone(input);
			input.addEventListener('input', function() {
				formatBrazilPhone(input);
			});
		});
	});
})();