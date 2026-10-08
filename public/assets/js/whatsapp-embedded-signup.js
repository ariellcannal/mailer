(function () {
	'use strict';

	const container = document.getElementById('metaEmbeddedSignup');
	const button = document.getElementById('connectWhatsAppMeta');
	const feedback = document.getElementById('whatsappConnectFeedback');
	if (!container || !button || !feedback) return;

	const appId = container.dataset.appId;
	const configId = container.dataset.configId;
	const graphVersion = container.dataset.graphVersion;
	const connectUrl = container.dataset.connectUrl;
	const csrfNameInput = container.querySelector('[data-csrf-name]');
	const csrfHashInput = container.querySelector('[data-csrf-hash]');
	let authorizationCode = '';
	let signupInfo = null;
	let submitting = false;

	button.disabled = true;

	function showFeedback(message, success) {
		feedback.className = `alert ${success ? 'alert-success' : 'alert-danger'} mt-3`;
		feedback.textContent = message;
	}

	async function completeConnection() {
		if (submitting || authorizationCode === '' || !signupInfo?.waba_id || !signupInfo?.phone_number_id) return;
		submitting = true;
		button.disabled = true;
		button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Conectando...';

		const formData = new URLSearchParams();
		formData.set(csrfNameInput.value, csrfHashInput.value);
		formData.set('code', authorizationCode);
		formData.set('waba_id', String(signupInfo.waba_id));
		formData.set('phone_number_id', String(signupInfo.phone_number_id));

		try {
			const response = await fetch(connectUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
				body: formData.toString()
			});
			const result = await response.json();
			if (result.csrfHash) csrfHashInput.value = result.csrfHash;
			if (!response.ok || !result.success) throw new Error(result.message || 'Não foi possível concluir a conexão.');
			showFeedback(result.message, true);
			window.setTimeout(() => window.location.reload(), 900);
		} catch (error) {
			showFeedback(error.message || 'Falha ao conectar com a Meta.', false);
			button.disabled = false;
			button.innerHTML = '<i class="fab fa-meta me-2" aria-hidden="true"></i>Conectar com a Meta';
			submitting = false;
		}
	}

	window.addEventListener('message', function (event) {
		let host;
		try {
			host = new URL(event.origin).hostname;
		} catch (error) {
			return;
		}
		if (host !== 'facebook.com' && !host.endsWith('.facebook.com')) return;

		let message = event.data;
		if (typeof message === 'string') {
			try {
				message = JSON.parse(message);
			} catch (error) {
				return;
			}
		}
		if (message?.type !== 'WA_EMBEDDED_SIGNUP') return;

		if (message.event === 'FINISH') {
			signupInfo = message.data || {};
			completeConnection();
		} else if (message.event === 'CANCEL' || message.event === 'ERROR') {
			showFeedback('A conexão Meta foi cancelada ou não pôde ser concluída.', false);
		}
	});

	window.fbAsyncInit = function () {
		window.FB.init({ appId, autoLogAppEvents: true, xfbml: false, version: graphVersion });
		button.disabled = false;
	};

	button.addEventListener('click', function () {
		if (!window.FB || submitting) {
			showFeedback('O SDK da Meta ainda está carregando. Tente novamente em instantes.', false);
			return;
		}

		authorizationCode = '';
		signupInfo = null;
		feedback.className = '';
		feedback.textContent = '';
		window.FB.login(function (response) {
			authorizationCode = response?.authResponse?.code || '';
			if (authorizationCode === '') {
				showFeedback('A Meta não retornou o código de autorização.', false);
				return;
			}
			completeConnection();
		}, {
			config_id: configId,
			response_type: 'code',
			override_default_response_type: true,
			extras: { featureType: 'whatsapp_embedded_signup', sessionInfoVersion: 3 }
		});
	});

	if (!document.getElementById('facebook-jssdk')) {
		const sdk = document.createElement('script');
		sdk.id = 'facebook-jssdk';
		sdk.src = 'https://connect.facebook.net/en_US/sdk.js';
		sdk.async = true;
		document.head.append(sdk);
	}
})();