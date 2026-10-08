# Worker WhatsApp

1. Configure `encryption.key` com uma chave forte e exclusiva do ambiente.
2. Configure `whatsapp.appId`, `whatsapp.configId`, `whatsapp.graphVersion`, `whatsapp.webhookVerifyToken` e `whatsapp.appSecret` no `.env`.
3. Execute `php spark migrate` para criar as tabelas WhatsApp e o telefone opcional dos contatos.
4. No painel Meta, habilite WhatsApp Embedded Signup na configuração de Login for Business e use o Config ID no `.env`.
5. Em Configurações > WhatsApp, clique em **Conectar com a Meta** e conclua a autenticação no popup. WABA, token e números são vinculados à empresa automaticamente.
6. Cadastre a URL `https://seu-dominio/webhooks/whatsapp` e os campos de verificação/assinatura no produto Webhooks da Meta, assinando o campo `messages`.
7. Agende `php spark whatsapp:process` para executar a cada minuto.

O worker usa lock em `writable/whatsapp_process.lock`, expande até 1.000 destinatários por execução e envia até 50 mensagens por lote. Falhas e eventos de entrega/leitura ficam associados ao tenant e à campanha.