# Implementações Pendentes - Sistema de Email Marketing

## ✅ JÁ IMPLEMENTADO

- [x] Exibição de reenvios na tela de visualização (commit a7195da)
- [x] Conversão de formato brasileiro dd/MM/yyyy HH:mm (commit ca2cdd0)
- [x] Pré-visualização sem tracking_token (commit c0abc99)
- [x] Email do remetente na visualização (commit d8fdf59)

---

## 🔨 PENDENTE: Tabela de Reenvios com Modal

### Objetivo
Substituir os 3 cards fixos de reenvio por uma tabela dinâmica onde o usuário pode adicionar até 3 reenvios via modal.

### Implementação

#### 1. Modificar `app/Views/messages/detail.php`

**Substituir o bloco de reenvios (linhas ~224-280) por:**

```php
<!-- Bloco: Reenvios -->
<div class="card mb-4">
    <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="fas fa-redo"></i> Reenvios</h6>
        <button type="button" class="btn btn-sm btn-light" onclick="openResendModal()" id="btnAddResend">
            <i class="fas fa-plus"></i> Adicionar Reenvio
        </button>
    </div>
    <div class="card-body">
        <p class="text-muted mb-3">Configure até 3 reenvios automáticos para contatos que não abriram a mensagem.</p>

        <div class="table-responsive">
            <table class="table table-hover" id="resendsTable">
                <thead>
                    <tr>
                        <th width="10%">#</th>
                        <th width="40%">Assunto</th>
                        <th width="30%">Data/Hora</th>
                        <th width="20%">Ações</th>
                    </tr>
                </thead>
                <tbody id="resendsTableBody">
                    <?php if (!empty($resendRules)): ?>
                        <?php foreach ($resendRules as $rule): ?>
                            <tr data-resend-index="<?= $rule['resend_number'] ?>">
                                <td>Reenvio <?= $rule['resend_number'] ?></td>
                                <td><?= esc($rule['subject_override'] ?: $message['subject'] ?? '') ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($rule['scheduled_at'])) ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="editResend(<?= $rule['resend_number'] ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeResend(<?= $rule['resend_number'] ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr id="emptyResendRow">
                            <td colspan="4" class="text-center text-muted">Nenhum reenvio configurado</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Hidden inputs para enviar dados -->
        <div id="resendHiddenInputs"></div>
    </div>
</div>

<!-- Modal de Reenvio -->
<div class="modal fade" id="resendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resendModalTitle">Adicionar Reenvio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="resendModalIndex">
                
                <div class="mb-3">
                    <label class="form-label">Número do Reenvio *</label>
                    <select class="form-select" id="resendModalNumber" required>
                        <option value="1">Reenvio 1</option>
                        <option value="2">Reenvio 2</option>
                        <option value="3">Reenvio 3</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Data e Hora *</label>
                    <input type="text" class="form-control" id="resendModalScheduledAt" placeholder="dd/mm/aaaa hh:mm" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Assunto</label>
                    <input type="text" class="form-control" id="resendModalSubject" placeholder="Deixe vazio para usar o assunto principal">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveResend()">Salvar</button>
            </div>
        </div>
    </div>
</div>
```

#### 2. Adicionar JavaScript no final de `detail.php`

```javascript
<script>
// Array para armazenar reenvios
let resends = [];

// Inicializar com reenvios existentes (modo edição)
<?php if (!empty($resendRules)): ?>
resends = <?= json_encode(array_map(function($rule) {
    return [
        'number' => $rule['resend_number'],
        'scheduled_at' => date('d/m/Y H:i', strtotime($rule['scheduled_at'])),
        'subject' => $rule['subject_override'] ?? ''
    ];
}, $resendRules)) ?>;
<?php endif; ?>

// Inicializar Tempus Dominus no modal
let resendModalPicker;
$(document).ready(function() {
    const modalInput = document.getElementById('resendModalScheduledAt');
    if (modalInput) {
        resendModalPicker = new tempusDominus.TempusDominus(modalInput, {
            localization: {
                locale: 'pt-BR',
                format: 'dd/MM/yyyy HH:mm'
            },
            display: {
                theme: 'light',
                components: {
                    calendar: true,
                    date: true,
                    month: true,
                    year: true,
                    decades: true,
                    clock: true,
                    hours: true,
                    minutes: true,
                    seconds: false
                }
            },
            restrictions: {
                minDate: new Date(Date.now() + 10 * 60000) // +10 minutos
            }
        });
    }
    
    updateResendTable();
    updateHiddenInputs();
});

function openResendModal() {
    if (resends.length >= 3) {
        alertify.error('Máximo de 3 reenvios permitidos');
        return;
    }
    
    // Determinar próximo número disponível
    const usedNumbers = resends.map(r => r.number);
    let nextNumber = 1;
    for (let i = 1; i <= 3; i++) {
        if (!usedNumbers.includes(i)) {
            nextNumber = i;
            break;
        }
    }
    
    $('#resendModalTitle').text('Adicionar Reenvio');
    $('#resendModalIndex').val('');
    $('#resendModalNumber').val(nextNumber);
    $('#resendModalScheduledAt').val('');
    $('#resendModalSubject').val('');
    $('#resendModal').modal('show');
}

function editResend(number) {
    const resend = resends.find(r => r.number === number);
    if (!resend) return;
    
    $('#resendModalTitle').text('Editar Reenvio');
    $('#resendModalIndex').val(number);
    $('#resendModalNumber').val(resend.number).prop('disabled', true);
    $('#resendModalScheduledAt').val(resend.scheduled_at);
    $('#resendModalSubject').val(resend.subject);
    $('#resendModal').modal('show');
}

function saveResend() {
    const number = parseInt($('#resendModalNumber').val());
    const scheduled_at = $('#resendModalScheduledAt').val();
    const subject = $('#resendModalSubject').val();
    const editIndex = $('#resendModalIndex').val();
    
    if (!scheduled_at) {
        alertify.error('Data e hora são obrigatórios');
        return;
    }
    
    // Validar data mínima
    const parts = scheduled_at.match(/(\d{2})\/(\d{2})\/(\d{4}) (\d{2}):(\d{2})/);
    if (parts) {
        const selectedDate = new Date(parts[3], parts[2] - 1, parts[1], parts[4], parts[5]);
        const minDate = new Date(Date.now() + 10 * 60000);
        if (selectedDate < minDate) {
            alertify.error('Data deve ser pelo menos 10 minutos no futuro');
            return;
        }
    }
    
    if (editIndex) {
        // Editar existente
        const index = resends.findIndex(r => r.number === parseInt(editIndex));
        if (index !== -1) {
            resends[index] = { number, scheduled_at, subject };
        }
    } else {
        // Adicionar novo
        if (resends.some(r => r.number === number)) {
            alertify.error('Já existe um reenvio com este número');
            return;
        }
        resends.push({ number, scheduled_at, subject });
    }
    
    resends.sort((a, b) => a.number - b.number);
    updateResendTable();
    updateHiddenInputs();
    $('#resendModal').modal('hide');
    $('#resendModalNumber').prop('disabled', false);
    alertify.success('Reenvio salvo');
}

function removeResend(number) {
    if (confirm('Deseja remover este reenvio?')) {
        resends = resends.filter(r => r.number !== number);
        updateResendTable();
        updateHiddenInputs();
        alertify.success('Reenvio removido');
    }
}

function updateResendTable() {
    const tbody = $('#resendsTableBody');
    tbody.empty();
    
    if (resends.length === 0) {
        tbody.append('<tr id="emptyResendRow"><td colspan="4" class="text-center text-muted">Nenhum reenvio configurado</td></tr>');
    } else {
        resends.forEach(resend => {
            tbody.append(`
                <tr data-resend-index="${resend.number}">
                    <td>Reenvio ${resend.number}</td>
                    <td>${resend.subject || '<em class="text-muted">Usar assunto principal</em>'}</td>
                    <td>${resend.scheduled_at}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="editResend(${resend.number})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeResend(${resend.number})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `);
        });
    }
    
    // Atualizar botão de adicionar
    if (resends.length >= 3) {
        $('#btnAddResend').prop('disabled', true).attr('title', 'Máximo de 3 reenvios');
    } else {
        $('#btnAddResend').prop('disabled', false).removeAttr('title');
    }
}

function updateHiddenInputs() {
    const container = $('#resendHiddenInputs');
    container.empty();
    
    resends.forEach((resend, index) => {
        container.append(`
            <input type="hidden" name="resends[${index}][number]" value="${resend.number}">
            <input type="hidden" name="resends[${index}][scheduled_at]" value="${resend.scheduled_at}">
            <input type="hidden" name="resends[${index}][subject]" value="${resend.subject}">
        `);
    });
}
</script>
```

---

## 🔨 PENDENTE: Botão "Salvar Rascunho"

### Objetivo
Adicionar botão "Salvar Rascunho" em todos os passos do wizard que salva sem alterar status para "Agendada".

### Implementação

#### 1. Adicionar método `saveDraft()` no `MessageController.php`

```php
/**
 * Salva mensagem como rascunho (não altera status)
 */
public function saveDraft(): ResponseInterface
{
    try {
        $model = new MessageModel();
        $messageId = (int) ($this->request->getPost('message_id') ?? 0);
        
        // Validar opt-out link
        $htmlContent = $this->sanitizeHtmlContent($this->request->getPost('html_content'));
        
        if (!empty(trim($htmlContent))) {
            $validation = $this->validateOptOutLink($htmlContent);
            if (!$validation['valid']) {
                return $this->response->setJSON([
                    'success' => false,
                    'error' => $validation['message']
                ]);
            }
        }
        
        $data = [
            'campaign_id' => $this->request->getPost('campaign_id'),
            'sender_id' => $this->request->getPost('sender_id'),
            'subject' => $this->request->getPost('subject'),
            'from_name' => $this->request->getPost('from_name'),
            'reply_to' => $this->request->getPost('reply_to'),
            'html_content' => $htmlContent,
            'status' => 'draft', // SEMPRE draft
        ];
        
        // Salvar scheduled_at se fornecido
        $scheduledAt = $this->normalizeScheduleInput($this->request->getPost('scheduled_at'));
        if ($scheduledAt) {
            $data['scheduled_at'] = $scheduledAt;
        }
        
        if ($messageId > 0) {
            $model->update($messageId, $data);
        } else {
            $messageId = $model->insert($data);
        }
        
        // Salvar reenvios
        $resendData = (array) $this->request->getPost('resends');
        if (!empty($resendData)) {
            // Limpar reenvios existentes
            $db = \Config\Database::connect();
            $db->table('resend_rules')->where('message_id', $messageId)->delete();
            
            // Salvar novos
            $this->saveResendRules($messageId, $data['scheduled_at'] ?? null, $resendData);
        }
        
        return $this->response->setJSON([
            'success' => true,
            'message_id' => $messageId,
            'message' => 'Rascunho salvo com sucesso'
        ]);
    } catch (\Exception $e) {
        log_message('error', 'Erro ao salvar rascunho: ' . $e->getMessage());
        return $this->response->setJSON([
            'success' => false,
            'error' => 'Erro ao salvar rascunho: ' . $e->getMessage()
        ]);
    }
}
```

#### 2. Adicionar rota em `app/Config/Routes.php`

```php
$routes->post('save-draft', 'MessageController::saveDraft');
```

#### 3. Adicionar botão em cada step do wizard em `detail.php`

**Adicionar em TODOS os steps (1, 2, 3, 4, 5):**

```html
<button type="button" class="btn btn-outline-secondary" onclick="saveDraft()">
    <i class="fas fa-save"></i> Salvar Rascunho
</button>
```

#### 4. Adicionar função JavaScript

```javascript
function saveDraft() {
    const formData = new FormData($('#messageForm')[0]);
    
    $.ajax({
        url: '<?= base_url('messages/save-draft') ?>',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                alertify.success(response.message);
                // Atualizar message_id se for novo
                if (response.message_id) {
                    $('#messageId').val(response.message_id);
                }
            } else {
                alertify.error(response.error || 'Erro ao salvar rascunho');
            }
        },
        error: function() {
            alertify.error('Erro ao salvar rascunho');
        }
    });
}
```

---

## 🔨 PENDENTE: Política Completa de Edição/Exclusão

### Matriz de Permissões

| Status | Primeiro Envio | Botão Editar | Ação ao Clicar | Pode Excluir |
|--------|----------------|--------------|----------------|--------------|
| **draft** | Qualquer | ✅ Sim | Edita normalmente | ✅ Sim |
| **scheduled** | Não passou | ✅ Sim | Prompt "Voltar para rascunho?" | ❌ Não |
| **scheduled** | Passou | ✅ Sim | Edita apenas reenvios | ❌ Não |
| **sending** | - | ✅ Sim | Edita apenas reenvios | ❌ Não |
| **completed** | - | ❌ Não | - | ❌ Não |

### Implementação

#### 1. Atualizar lógica de exibição do botão editar em `messages/index.php`

```php
<?php
$showEditButton = false;
$now = time();

if ($message['status'] === 'draft') {
    $showEditButton = true;
} elseif ($message['status'] === 'scheduled' || $message['status'] === 'sending') {
    $showEditButton = true;
} elseif ($message['status'] === 'completed') {
    $showEditButton = false;
}
?>

<?php if ($showEditButton): ?>
    <a href="<?= base_url('messages/edit/' . $message['id']) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-edit"></i>
    </a>
<?php endif; ?>
```

#### 2. Atualizar lógica de exclusão em `messages/index.php`

```php
<?php if ($message['status'] === 'draft'): ?>
    <form action="<?= base_url('messages/delete/' . $message['id']) ?>" method="POST" style="display:inline;">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Deseja excluir?')">
            <i class="fas fa-trash"></i>
        </button>
    </form>
<?php endif; ?>
```

#### 3. Atualizar `MessageController::edit()` para verificar status

```php
public function edit(int $id): string|RedirectResponse
{
    $model = new MessageModel();
    $message = $model->find($id);

    if (!$message) {
        return redirect()->to('/messages')->with('error', 'Mensagem não encontrada');
    }
    
    $now = time();
    $scheduledTime = !empty($message['scheduled_at']) ? strtotime($message['scheduled_at']) : 0;
    
    // Se agendada e primeiro envio não passou, mostrar prompt
    if ($message['status'] === 'scheduled' && $scheduledTime > $now) {
        // Verificar se falta menos de 1 minuto
        $timeUntilSend = $scheduledTime - $now;
        
        // JavaScript vai mostrar prompt, passar info via session
        session()->setFlashdata('show_draft_prompt', true);
        session()->setFlashdata('time_until_send', $timeUntilSend);
    }
    
    // Obter permissões de edição
    $editPermissions = $this->getEditPermissions($message);
    
    // ... resto do código
}
```

#### 4. Adicionar JavaScript para prompt em `detail.php`

```javascript
<?php if (session()->getFlashdata('show_draft_prompt')): ?>
<script>
$(document).ready(function() {
    const timeUntilSend = <?= session()->getFlashdata('time_until_send') ?? 0 ?>;
    
    alertify.confirm(
        'Voltar para Rascunho?',
        'A mensagem voltará para rascunho. Confirma?',
        function() {
            // Converter para rascunho
            $.post('<?= base_url('messages/convert-to-draft/' . $message['id']) ?>', function(response) {
                alertify.success('Mensagem convertida para rascunho');
                window.location.reload();
            }).fail(function() {
                alertify.error('Erro ao converter para rascunho');
            });
        },
        function() {
            // Cancelou, voltar para visualização
            window.location.href = '<?= base_url('messages/view/' . $message['id']) ?>';
        }
    );
});
</script>
<?php endif; ?>
```

---

## 📝 Checklist de Implementação

- [ ] Tabela de reenvios com modal
  - [ ] Modificar HTML em detail.php
  - [ ] Adicionar JavaScript de gerenciamento
  - [ ] Inicializar Tempus Dominus no modal
  - [ ] Testar adicionar/editar/remover reenvios

- [ ] Botão Salvar Rascunho
  - [ ] Criar método saveDraft() no controller
  - [ ] Adicionar rota
  - [ ] Adicionar botão em todos os steps
  - [ ] Adicionar função JavaScript
  - [ ] Testar salvamento

- [ ] Política de Edição/Exclusão
  - [ ] Atualizar lógica de exibição do botão editar
  - [ ] Atualizar lógica de exclusão
  - [ ] Adicionar prompt de rascunho
  - [ ] Testar todos os cenários

---

## 🧪 Testes Necessários

### Teste 1: Tabela de Reenvios
1. Criar nova mensagem
2. Adicionar 3 reenvios via modal
3. Editar um reenvio
4. Remover um reenvio
5. Salvar mensagem
6. Verificar no banco se reenvios foram salvos

### Teste 2: Botão Salvar Rascunho
1. Criar mensagem e preencher apenas step 1
2. Clicar em "Salvar Rascunho"
3. Verificar se status permanece "draft"
4. Reabrir mensagem e verificar dados salvos

### Teste 3: Política de Edição
1. **Draft:** Editar e excluir normalmente
2. **Agendada (não passou):** Clicar editar → ver prompt → confirmar → virar draft
3. **Agendada (passou):** Editar apenas reenvios
4. **Completed:** Botão editar não aparece

---

## 📊 Status Atual

- ✅ Exibição de reenvios: **CORRIGIDO**
- ⏳ Tabela com modal: **PENDENTE**
- ⏳ Botão Salvar Rascunho: **PENDENTE**
- ⏳ Política completa: **PENDENTE**
