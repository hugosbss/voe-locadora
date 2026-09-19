# Relatório — Correção da regra de cotas

## 1. Objetivo

Corrigir a regra de disponibilidade de cotas para que ela considere o **período** (datas de início e fim do cadastro), e não apenas um contador global de "vendidas". A disponibilidade passa a ser calculada por **sobreposição de período** para cada par veículo + tipo de cota, com proteção contra concorrência no momento da reserva.

## 2. Decisões de negócio

Todas as regras ficam centralizadas em `config/quotas.php` (fonte única de verdade, consumida por `App\Services\QuotaAvailabilityService`):

| Decisão | Valor / comportamento |
| --- | --- |
| Status que consomem vaga | `novo`, `em_analise`, `aprovado` (`reprovado` **não** consome) |
| Datas | **Inclusivas**: conflito quando `start_a <= end_b` **e** `end_a >= start_b` |
| Duração | `duration_mode` = `exact` (padrão), `max` ou `min`; compara `(end - start + 1)` com `quota_type.days` |
| TTL do status "novo" | `novo_ttl_hours = null` — apenas documentado; **nenhum** job/schedule/expiração é criado nesta etapa |
| Exclusividade do veículo | `vehicle_exclusive` = `false` (padrão); quando `true`, qualquer reserva consumidora do veículo ocupa a vaga, independente do tipo de cota |

`config/quotas.php`:
- `consuming_statuses` (linhas 22–26)
- `duration_mode` (linha 34)
- `novo_ttl_hours` (linha 42)
- `vehicle_exclusive` (linha 50)

## 3. Arquitetura

### `App\Services\QuotaAvailabilityService`
Ponto único de verdade para toda disponibilidade. Métodos principais:

| Método | Linha | Papel |
| --- | --- | --- |
| `consumingStatusValues()` | 32 | Status consumidores vindos do enum/config |
| `durationMode()` / `vehicleExclusive()` | 40 / 45 | Configuração |
| `periodDays()` | 53 | Dias inclusivos do período |
| `isPeriodValidForType()` | 61 | Aplica `exact`/`max`/`min` |
| `configurationFor()` / `activeConfigurationsForVehicle()` | 82 / 306 | Configuração do par veículo + cota |
| `lockVehicleFor()` / `lockConfigurationFor()` | 91 / 96 | `SELECT ... FOR UPDATE` |
| `reservedFor()` / `availableFor()` | 112 / 138 | Sobreposição de período |
| `assertCanReserve()` | 157 | Validação central (com `lock` opcional) |
| `hasActiveReservations()` | 192 | Reservas futuras de uma configuração |
| `peakConcurrentReservations()` | 209 | Pico simultâneo (sweep-line) |
| `availabilityMapForVehicle()` | 245 | Payload do endpoint público |
| `statsForConfiguration()` / `statsForVehicle()` | 269 / 284 | Painel admin |

### `App\Exceptions\QuotaUnavailableException`
- Estende **`\Exception`** de propósito (não `RuntimeException`) — o `store()` público captura `RuntimeException` para falha de assinatura/arquivo; herdar dela transformaria a recusa de cota em 500.
- `readonly string $field` define a chave de erro de validação.
- Factories: `notConfigured()` (`quota_type_id`), `invalidDuration()` (`end_date`), `unavailable()` (`quota_type_id`).

### Concorrência
`ClientRegistrationService::create()` injeta o service (linha 34) e chama `assertCanReserve(..., lock: true)` **no início da transação** (linhas 53–58), antes de gravar os arquivos. Com `vehicle_exclusive = true`, a linha do veículo é travada primeiro. No MySQL o `lockForUpdate` serializa as requisições; no SQLite o lock é ignorado (comportamento conhecido do driver).

## 4. Arquivos alterados / criados

### Criados
- `config/quotas.php` — regras centrais.
- `app/Services/QuotaAvailabilityService.php` — fonte única de verdade.
- `app/Exceptions/QuotaUnavailableException.php` — exceção de domínio.
- `database/migrations/2026_09_19_083824_add_quota_availability_index_to_client_registrations_table.php` — índice composto `(vehicle_id, quota_type_id, status, start_date, end_date)`; `down()` remove o índice (reversível).

### Domínio / modelos
- `app/Enums/RegistrationStatus.php:26,34` — `consumesQuota()` e `consuming()`.
- `app/Models/VehicleQuotaConfiguration.php` — removidos `soldCount`/`availableCount`.
- `app/Models/QuotaType.php` — removido `soldCount`; mantida a relação `configurations()`.
- `app/Models/Vehicle.php` — removidos os contadores globais (`totalSoldQuotaCount`, `totalAvailableQuotaCount`, `availableQuotaConfigurations`); mantido `totalQuotaCount()` (linha 38). PHPDoc aponta para o service.

### Aplicação
- `app/Http/Controllers/Public/ClientRegistrationController.php`
  - `create()`: filtra configurações ativas com `quantity > 0` e quota ativa; rótulo `"%s · %s (%d vagas)"` (linha 72).
  - `quotaAvailability()` (linha 94): endpoint JSON que devolve disponibilidade e validade de duração por cota.
  - `store()`: captura `QuotaUnavailableException` (linha 165) devolvendo 422/redirect amigável.
- `app/Http/Requests/StoreClientRegistrationRequest.php`
  - Regras de `vehicle_id`, `quota_type_id`, `start_date`, `end_date` (linhas 82–85), com `start_date >= hoje (America/Sao_Paulo)`.
  - `after()` revalida via `assertCanReserve` (linha 119), convertendo a exceção em erro de validação no campo certo.
  - `today()` (linha 135) e mensagens PT-BR (linhas 178–186).
- `app/Services/ClientRegistrationService.php` — assert com lock dentro da transação.
- `app/Http/Controllers/Admin/RegistrationController.php` — `revalidateQuotaOnReactivate()` (linha 237), acionado apenas na transição **não-consumidor → consumidor** (linha 204). Editar `start_date`/`end_date` não é possível hoje (`UpdateClientRegistrationRequest` só aceita `vehicle_observation` + fotos).
- `app/Http/Controllers/Admin/VehicleController.php` — estatísticas no `index` (linha 30) e `show` (linhas 85–95); `quantity` não pode ser reduzida abaixo do pico simultâneo (linha 173); remover configuração com reservas ativas apenas a desativa (linha 193).
- `app/Http/Controllers/Admin/QuotaTypeController.php` — mesmas regras de pico (linha 181) e desativação (linha 201).

### Rotas / infra
- `routes/web.php:42` — rota `client-registrations.quota-availability` com `throttle:quota_availability`.
- `app/Providers/AppServiceProvider.php:47` — limiter `quota_availability`.
- `config/rate.php:41` — `quota_availability_per_minute` (padrão 60).

### Frontend
- `resources/js/client-registration.js` — `quotaAvailabilityUrl`/`quotaDurationMode` (linhas 33–34); `setupQuotaVehicleFilter()` reescrito (linha 743): consulta o endpoint, desabilita opções sem vaga/duração inválida, injeta hint dinâmico e preenche `end_date` em modo `exact` (`applyDuration`, linha 879).
- `resources/views/client-registrations/create.blade.php` — opções com `data-days`/`data-base-label`, campos de data e configuração CSP-safe (`application/json`).
- `resources/views/admin/vehicles/index.blade.php:23-25` — Cotas / Reservadas hoje / Disponíveis hoje.
- `resources/views/admin/vehicles/show.blade.php:44-82` — "Resumo de hoje" + colunas por configuração.
- `resources/views/admin/quotas/show.blade.php:47,77-78` — reservadas/disponíveis hoje.

## 5. Testes

### Novo teste de auditoria — `tests/Feature/QuotaAvailabilityAuditTest.php`
22 testes cobrindo os cenários a–o:

| Grupo | Testes |
| --- | --- |
| a–c | Reserva aprovada reduz disponibilidade; volta após o fim; períodos disjuntos não interferem |
| d–e | 9ª reserva no mesmo período é rejeitada; sobreposição parcial/encostando conflita |
| f–g | Reprovado não consome; novo/em análise consomem; reprovar libera; não há status expirado/cancelado |
| h | Criação concorrente revalida sob lock; reversão de reprovado revalida; transição entre consumidores não revalida |
| i–j | Tipos de cota compartilham o veículo só quando exclusivo; veículos diferentes não interferem |
| k | Modos `exact`/`max`/`min`; store rejeita período incompatível e aceita menor no `max` |
| l | Endpoint de disponibilidade reporta dados e valida entrada |
| m–n | Não reduzir `quantity` abaixo do pico; remover configuração com reservas ativas desativa |
| o | Reservas semanais mantêm 7 disponíveis em cada janela |

### `tests/Concerns/CreatesQuotaContext.php` (novo)
Trait compartilhada que cria veículo + tipo de cota + configuração com vagas e um período dinâmico compatível com `exact`, usada pelos testes legados.

### Ajustes em testes legados
- `tests/Feature/ClientRegistrationTest.php` — payload com `vehicle_id`/`quota_type_id`/datas; rótulo `(N vagas)`; datas dinâmicas; texto atual do admin ("Fotos do veículo").
- `tests/Feature/ClientContractTest.php` — payload com contexto de cota e período dinâmico.
- `tests/Feature/ThrottleTest.php` — contexto de cota criado no `setUp` e usado no payload.
- `tests/Feature/UploadSecurityTest.php` — `baseData()` passa a incluir contexto de cota e período dinâmico.
- `tests/Feature/AdminRegistrationEditingTest.php` — asserções alinhadas à UI atual ("Editar informações do veículo", "Tirar foto", "Galeria").

### Resultado

```
php artisan test
{"tool":"phpunit","result":"passed","tests":214,"passed":214,"assertions":889,"duration_ms":24138}
```

Pint aplicado em `tests/Feature/ClientRegistrationTest.php` (`concat_space`, `ordered_imports`). Vite reconstruído (`npm run build`).

## 6. Notas / armadilhas

- `QuotaUnavailableException` **não** estende `RuntimeException` (ver seção 3).
- `ClientRegistration::$fillable` **não** inclui `status`; testes que alteram status diretamente precisam de atribuição + `save()`.
- `lockForUpdate` não tem efeito no SQLite; a suíte usa MySQL (`cadastro_test`).
- O admin **não** edita `vehicle_id`/`quota_type_id`/`start_date`/`end_date`, então a revalidação só ocorre na reativação de status.
- `novo_ttl_hours` está documentado, mas desligado; não há schedule/job.

## 7. Pendências

- Commits não realizados (a pedido).
- `docs/relatorio-cotas.md` (etapa anterior) permanece no working tree.
- O working tree contém mudanças de etapas anteriores não commitadas (contrato preenchido, "Como funciona", upload admin), além desta correção.
