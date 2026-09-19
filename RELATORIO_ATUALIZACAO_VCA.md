# Relatório de Atualização — VCA

## 1. Objetivo

Organizar em nova branch a base da gestão administrativa de veículos e cotas, a página pública "Como funciona" e a integração da arquitetura necessária ao sistema já existente, preservando o fluxo atual de cadastro público e sem alterar a geração do contrato/PDF. Inclui também a correção da recorrência do select customizado e a investigação do modelo do contrato.

## 2. Implementações realizadas

### Fase 1

- Criação do domínio básico de veículos e cotas com modelos e relacionamentos principais.
- Estrutura inicial de cotas por veículo e tipos configuráveis.
- CRUD administrativo simples de veículos com listagem, criação, edição, ativação/inativação e detalhamento.
- CRUD administrativo de tipos de cota, com associação de veículos e quantidade.
- Registro de configurações de cota por veículo com quantidade e status.
- Cálculo de disponibilidade por configuração: `availableCount() = max(quantity - sold, 0)`, considerando apenas cadastros aprovados.

### Fase 2

- Integração dos relacionamentos de veículo/cota ao cadastro público existente via `ClientRegistration`.
- Seleção de veículo, tipo de cota, data de início e data de fim no formulário público, com validação de compatibilidade `vehicle_id` + `quota_type_id`.
- O campo `quota_type_id` é obrigatório somente enquanto houver veículos/cotas disponíveis; caso contrário permanece opcional (sem quebrar o fluxo sem frota configurada).
- Persistência de `start_date`, `end_date`, `quota_days` e das fotos/observação do veículo no cadastro.

### Fase 3

- Criação da rota pública `/como-funciona`.
- Página pública com identidade visual consistente com o layout VCA.
- Links de navegação na área pública (cabeçalho e rodapé).
- Conteúdo do tipo "Como funciona" com foco em veículos, cotas, uso e CTA para cadastro.

### Correção do select customizado (Composite/select de cotas)

- Removida a re-entrega de `cs:sync` como gatilho de atualização do menu visual (era a causa do loop infinito `Maximum call stack size exceeded`).
- O `<select>` nativo permanece como fonte da verdade; o custom select sincroniza sem disparar sincronização em cascata.
- Adicionado guard de reentrância (`applyingQuotaValue`) no filtro de cotas para impedir recursão.
- O filtro de cota continua ocultando/desabilitando opções incompatíveis com o veículo, sem interferir nas demais etapas.

## 3. Banco de dados

Migrations criadas:

- `2026_09_18_000000_create_vehicle_related_tables.php`
- `2026_09_19_000000_add_vehicle_photo_and_observation_fields_to_client_registrations.php`

Tabelas criadas:

- `vehicles`
  - `id`
  - `model`
  - `plate` (unique)
  - `active`
  - timestamps
- `quota_types`
  - `id`
  - `code` (unique)
  - `name`
  - `days` (unsignedInteger)
  - `active`
  - timestamps
- `vehicle_quota_configurations`
  - `id`
  - `vehicle_id`
  - `quota_type_id`
  - `quantity` (unsignedInteger default 0)
  - `active`
  - unique `[vehicle_id, quota_type_id]`
  - timestamps

Campos adicionados ao cadastro existente:

- `client_registrations.vehicle_id`
- `client_registrations.quota_type_id`
- `client_registrations.start_date`
- `client_registrations.end_date`
- `client_registrations.quota_days`
- `client_registrations.vehicle_pickup_photo_path`
- `client_registrations.vehicle_delivery_photo_path`
- `client_registrations.vehicle_observation`

Relacionamentos principais:

- `Vehicle` -> `hasMany(VehicleQuotaConfiguration)`
- `QuotaType` -> `hasMany(VehicleQuotaConfiguration)`
- `VehicleQuotaConfiguration` -> `belongsTo(Vehicle)`
- `VehicleQuotaConfiguration` -> `belongsTo(QuotaType)`
- `ClientRegistration` -> `belongsTo(Vehicle)`
- `ClientRegistration` -> `belongsTo(QuotaType)`

## 4. Backend

Models:

- `app/Models/Vehicle.php`
- `app/Models/QuotaType.php`
- `app/Models/VehicleQuotaConfiguration.php`
- atualização de `app/Models/ClientRegistration.php`: relacionamento opcional com veículo e tipo de cota, constantes `DOCUMENTS`/`UPLOAD_DOCUMENTS` e `documentPath()` com mapeamento das colunas `vehicle_*_photo_path`.

Controllers:

- `app/Http/Controllers/Admin/VehicleController.php`
- `app/Http/Controllers/Admin/QuotaTypeController.php`
- `app/Http/Controllers/Public/HowItWorksController.php`
- atualização de `app/Http/Controllers/Public/ClientRegistrationController.php`: oferta de veículos/cotas no formulário e resposta JSON 422 em falha de processamento.

Services:

- atualização de `app/Services/ClientRegistrationService.php`: itera apenas `UPLOAD_DOCUMENTS` no armazenamento (corrige colunas inexistentes `vehicle_pickup_path`/`vehicle_delivery_path`) e persiste veículo/cota/período quando presentes.

Request:

- `app/Http/Requests/StoreClientRegistrationRequest.php`: valida `vehicle_id`/`quota_type_id` (compatíveis, apenas quando há disponibilidade), `start_date`/`end_date`, e regra `after()` confirmando que a cota pertence ao veículo selecionado e possui disponibilidade.

Policies:

- `app/Policies/VehiclePolicy.php`
- `app/Policies/QuotaTypePolicy.php`

Rotas:

- `GET /como-funciona` -> `public.how-it-works`
- CRUD administrativo em `/admin/veiculos` e `/admin/cotas` (index, create, store, show, edit, update, destroy, toggle-status, configurações).

## 5. Frontend

Telas e componentes:

- `resources/views/admin/vehicles/index.blade.php`
- `resources/views/admin/vehicles/create.blade.php`
- `resources/views/admin/vehicles/edit.blade.php`
- `resources/views/admin/vehicles/show.blade.php`
- `resources/views/admin/quotas/index.blade.php`
- `resources/views/admin/quotas/create.blade.php`
- `resources/views/admin/quotas/edit.blade.php`
- `resources/views/admin/quotas/show.blade.php`
- `resources/views/public/how-it-works.blade.php`
- atualização de `resources/views/client-registrations/create.blade.php` (veículo, cota, datas)
- atualização de `resources/views/admin/registrations/show.blade.php` (seção "Informações do veículo" e grid de documentos sobre `UPLOAD_DOCUMENTS` — mantém os 4 documentos reais do formulário; fotos de retirada/entrega são exibidas na seção própria)
- atualização de `resources/views/partials/admin-nav.blade.php` (itens Veículos e Cotas)
- atualização de `resources/views/layouts/public.blade.php` (navegação e rodapé)

JavaScript:

- `resources/js/custom-select.js`: respeita `hidden`/`disabled`, `syncOptions()` e recebe `cs:sync` sem re-entrega em cascata.
- `resources/js/client-registration.js`: filtro de cotas por veículo com guard de reentrância, dispatch pontual de `cs:sync` e sincronização do nome do signatário.

Responsividade:

- interface desenhada para uso mobile-first e compatível com desktop.

## 6. Storage

A estrutura de fotos e documentos do cadastro existente continua sendo tratada como storage privado e fora da raiz web, preservando o padrão atual do projeto. As novidades desta etapa não introduziram upload público novo nem endpoint público de visualização de imagens. As fotos de retirada/entrega usam o endpoint administrativo existente via `documentPath()`.

## 7. Segurança

- Autorização administrativa por `Policy` e `Gate` para veículos e cotas.
- Validação de campos no backend por `Request`, regras de placa/quantidade e de compatibilidade veículo/cota.
- Preservação do storage privado e da arquitetura atual de documentos do cadastro.
- Sem novo endpoint público para fotos, edição de veículos ou operação sensível sem autenticação.
- A lógica de concorrência e disponibilidade dinâmica exige validação adicional em próxima etapa, conforme a arquitetura do sistema e os requisitos do documento.

## 8. Correções aplicadas na iteração atual

### 8.1. Formulário público

- Ajustado o rótulo da cota para mostrar apenas o código, nome e quantidade disponível, sem repetir veículo/placa no campo de seleção.
- Mantida a lógica de filtragem por veículo e disponibilidade real, sem alterar o fluxo comercial existente.
- Corrigida a sincronização do campo `contract_signer_name`, que agora recebe o nome completo do cadastro em vez de apenas a primeira letra.
- `vehicle_id` e `quota_type_id` tornam-se obrigatórios apenas quando existem veículos/cotas disponíveis.

### 8.2. Cadastro administrativo existente

- Adicionados campos opcionais no cadastro existente para informações do veículo: fotos da retirada, fotos da entrega e observação.
- A estrutura foi mantida no model do cadastro atual, sem a criação de módulo de utilização, QuotaUsage ou CRUD separado.
- A seção foi incluída no detalhe do cadastro administrativo já existente, preservando o fluxo e a arquitetura atual.
- O grid de documentos do detalhe exibe apenas `UPLOAD_DOCUMENTS` (os documentos reais do formulário); as fotos do veículo têm seção própria, evitando colunas fantasmas `vehicle_*_path`.

### 8.3. Reprodução e correção da recursão `Maximum call stack size exceeded`

- Reproduzido com suíte de testes e bundle atual: a recursão acontecia porque o filtro de cotas alterava o valor do `<select>` nativo, o custom select reemitia `cs:sync`, e o filtro reagia novamente, em loop infinito.
- Correção: guard de reentrância (`applyingQuotaValue`) no filtro; `custom-select.js` passou a escutar `cs:sync` e sincronizar o menu visual sem re-entrega; o nativo continua sendo a fonte da verdade.
- `node --check` aprovado em ambos os arquivos; bundle de produção regerado com `npm run build` (presença de `cs:sync` verificada no novo bundle).

## 9. Testes executados

Suíte completa (ambiente local com PHP CLI disponível, MySQL em `127.0.0.1:3306`, banco `cadastro_test`):

1. `node --check resources/js/custom-select.js && node --check resources/js/client-registration.js`
   - Resultado: sintaxe sem erros.

2. `php artisan test --filter='test_invalid_vehicle_and_quota_combination_is_rejected|test_public_form_lists_only_quota_code_name_and_availability_for_the_selected_vehicle|test_valid_vehicle_and_quota_combination_is_accepted'`
   - Resultado (antes da correção da coluna): 2 passed / 1 failed (`test_valid_vehicle_and_quota_combination_is_accepted` falhava por `Unknown column 'vehicle_pickup_path'`).
   - Causa raiz: o serviço iterava `DOCUMENTS` (que inclui `vehicle_pickup`/`vehicle_delivery`) e gravava `vehicle_pickup_path`/`vehicle_delivery_path`, colunas inexistentes (as colunas reais são `vehicle_*_photo_path`). Os payloads legados também não enviavam `start_date`/`end_date`, agora obrigatórios.
   - Correção: serviço itera apenas `UPLOAD_DOCUMENTS`; payloads legados atualizados com `start_date`/`end_date`; import de `ClientRegistrationService` adicionado ao teste de resposta JSON (o mock resolvia para o namespace `Tests\Feature`, nunca sendo aplicado).
   - Resultado final: 3 passed.

3. `php artisan test tests/Feature/ClientRegistrationTest.php tests/Feature/VehicleAndHowItWorksTest.php tests/Feature/AdminQuotaTypesTest.php`
   - Resultado: 25 testes, 25 passed (101 assertions).

4. `php artisan test` (suíte completa)
   - Resultado: 181 testes, 181 passed (752 assertions).

## 10. Regressão

A suíte completa de testes está verde (181/181). Nenhuma regressão detectada nas rotas, validações, storage ou fluxo de contrato após as correções.

## 11. Contrato/PDF

- **Nenhuma alteração no código do contrato/PDF foi realizada nesta etapa.**
- O disco `local` resolve para `storage/app/private/`; o caminho do template (`config('contracts.template_path')` = `contracts/templates/Contrato_VCA_Clube_de_Mobilidade.pdf`) é calculado corretamente pelo código.
- O diretório `storage/app/private/contracts/templates/` **não existe** no ambiente real: o modelo oficial do contrato não está provisionado/versionado (o único PDF sintético é o dos testes, em disco fake).
- Isso é um item de **provisionamento de ambiente**, não um bug de código. O fluxo de contrato (assinatura, overlay, armazenamento) foi validado nos testes com template sintético e funciona.

## 12. Pendências

- Provisionar o modelo oficial do contrato (`storage/app/private/contracts/templates/Contrato_VCA_Clube_de_Mobilidade.pdf`) no ambiente real; o fluxo depende disso fora dos testes.
- A lógica de concorrência e disponibilidade dinâmica por veículo/tipo exige validação adicional (transações/locks) para venda simultânea.
- Em `QuotaTypeController::updateVehicleConfiguration` e `VehicleController::updateQuotaConfiguration`, a configuração `{configuration}` não é escopada pelo pai (possível manipulação de configuração de outro veículo/cota).
- `VehicleController::removeQuotaConfiguration` exclui mesmo com histórico vendido (divergente do comportamento do controller de cotas).
- As telas de detalhe (veículos/cotas) enviam a quantidade atual via campo oculto no "Atualizar"; não há edição à quantidade nas views.

## 13. Git

- Branch criada a partir de `vca`: **`feature/cadastro-cotas-veiculos`**
- Base: `vca` (`9362c09`), sincronizada com `origin/vca`.
- Commits (em ordem):

| Hash | Mensagem |
|---|---|
| `a25268d` | `feat(veiculos): CRUD de veículos, tipos de cota e página Como Funciona` |
| `56a8684` | `feat(cadastro): integrar veículo, cota e período no formulário público` |
| `98d1bb1` | `fix(ui): corrigir recursão cs:sync e validação de veículo/cota no select` |
| `8f426bf` | `test(cadastro): cobrir combinação veículo/cota e atualizar payloads legados` |
| `c10d370` | `chore(config): alinhar nome do pacote e porta do banco de teste` |
| *(próximo)* | `docs(vca): relatório de atualização — veículos, cotas e correções` |

- Nenhuma alteração em `vca`; nenhum push realizado até este commit de documentação.
- `.env.example` restaurado (não faz parte da feature; permanece intacto).
- `public/build/` é ignorado pelo git (gitignore), portanto o bundle não versionado.
- Pull Request destinado a `vca` sem merge (executado após este commit).

## 14. Diagnóstico final da cota no formulário público

A causa raiz do problema de `quota_type_id` estava na validação local da etapa de revisão: o campo era tratado como simplesmente preenchido, sem confirmar se a cota selecionada era compatível com o `vehicle_id` atual. Em caso de troca de veículo, a opção antiga ficava persistida em memória ou oculta, gerando falso erro de "precisa corrigir" mesmo quando a cota escolhida era válida.

### Correção aplicada

- Mantém somente as opções de cota compatíveis com o veículo selecionado em `resources/js/client-registration.js`.
- Limpa a seleção anterior quando o veículo muda.
- Valida explicitamente `vehicle_id + quota_type_id` antes de permitir seguir para a revisão.
- Preserva a regra de obrigatoriedade do campo; não remove `required` e nem a validação de disponibilidade real do backend em `app/Http/Requests/StoreClientRegistrationRequest.php`.

### Diagnóstico do select customizado

A recursão em `Maximum call stack size exceeded` era disparada por sincronização redundante entre o `<select>` nativo e o custom select: o filtro de cota alterava o valor do campo, o custom select reemitia `cs:sync`, e o próprio filtro reagia de novo ao evento, criando loop infinito.

### Correção aplicada

- Mantém o `<select>` nativo como fonte da verdade em `resources/js/custom-select.js`.
- O custom select escuta `cs:sync` e sincroniza o menu visual sem re-entrega.
- Guard de reentrância (`applyingQuotaValue`) no filtro em `resources/js/client-registration.js`.
- Preserva o comportamento de ocultar e desabilitar opções incompatíveis sem mexer em outras etapas do cadastro.

### Resultado

- Combinação inválida: rejeitada com erro no backend (teste verde).
- Combinação válida: aceita e finaliza o cadastro com sucesso (teste verde) após a correção das colunas de armazenamento de veículo.
- Recursão `cs:sync`: eliminada; bundle de produção validado com `npm run build`.

## 15. Estado final

- Suíte de testes: **181/181 passed**.
- Parte de veículos/cotas do formulário: validada por testes funcionais e de seleção pública.
- Contrato/PDF: código inalterado; requer provisionamento do modelo oficial no ambiente real.
- Branch: `feature/cadastro-cotas-veiculos` com 6 commits, pronta para Pull Request em `vca` (sem merge).