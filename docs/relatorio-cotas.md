# Relatório de Auditoria de Cotas

Data: 2026-09-19 · Escopo: leitura (nenhum código de produção alterado)

## 1. Resumo executivo

**Não — o sistema não atende à regra de negócio em que a cota fica ocupada somente durante o período do cadastro.**

A implementação atual usa um contador **global** (não por período): `availableCount() = quantity − (nº de cadastros aprovados do veículo/tipo)` , ignorando `start_date`/`end_date`. Trechos principais:

- `app/Models/VehicleQuotaConfiguration.php:38-50` — `soldCount()` conta **todos** os aprovados (global); `availableCount()` subtrai do total.
- `app/Models/QuotaType.php:36-41` — `soldCount()` idem, por tipo.
- `app/Models/Vehicle.php:55-63` — `availableQuotaConfigurations()` filtra configurações com `availableCount() > 0`.
- `app/Http/Controllers/Public/ClientRegistrationController.php:32-52` — `$availableQuotaOptions` é montado a partir da disponibilidade global.
- `app/Http/Requests/StoreClientRegistrationRequest.php:61-83` — `Rule::in()` valida `vehicle_id` e `quota_type_id` contra as listas de disponibilidade global.
- `app/Services/ClientRegistrationService.php:46+` — `create()` **não** revalida disponibilidade nem usa `lockForUpdate`.

Como a cota **nunca é liberada** após `end_date` (nem há status cancelado/expirado, nem evento/Job de restauração), a disponibilidade decresce monotonamente com o tempo. `app/Console/Commands/PurgeExpiredRegistrations.php` **apaga** registros antigos por retenção — não restaura cota.

## 2. Modelo atual de disponibilidade

Comportamento observado (modelo "global/contador computado", sem restauração automática):

1. Disponibilidade é derivada (não persistida): não há coluna `sold`/`available` no banco.
2. Contagem global: só `status = aprovado` consome (`VehicleQuotaConfiguration::soldCount()`).
3. Sem lógica de data: nenhuma cláusula sobre `start_date`/`end_date` nas queries de disponibilidade.
4. Sem bloqueio de concorrência: nenhum `SELECT ... FOR UPDATE` (via `lockForUpdate()`) em lugar nenhum.
5. Sem mecanismo de restauração: não há `Jobs/`, não há `Schedule::` em `bootstrap/app.php`; `routes/console.php` só tem `inspire`.

## 3. Cenários testados (a–j)

Suíte nova: `tests/Feature/QuotaAvailabilityAuditTest.php` (16 testes). `@falhaEsperada` = teste falha e evidencia o bug; `@reproducao` = teste passa e comprova a inconsistência.

| # | Cenário | Esperado (regra de negócio) | Resultado | Evidência |
|---|---|---|---|---|
| a | 1 aprovação reduz disponibilidade do período | Disponível 8 → 7 | **OK** | `test_a_single_approved_booking_reduces_availability_for_its_period` (passa) |
| b | Cota volta depois que o período termina | Disponível 8 | **FALHA (7)** | `test_b_coverage_returns_after_end_date_passes` — `@falhaEsperada` (falhou: 7, esperado 8) |
| c | Períodos disjuntos não interferem | Disponível 8 | **FALHA (5)** | `test_c_disjoint_periods_do_not_interfere_with_each_other` — `@falhaEsperada` (falhou: 5, esperado 8) |
| d | 9ª reserva no mesmo período é rejeitada | 422 | **OK** | `test_d_ninth_booking_in_same_period_is_rejected_by_validation` (passa) |
| d | 9ª reserva em outro período é aceita | 201 (período livre) | **FALHA (422)** | `test_d_other_period_should_stay_full_but_global_count_blocks_it` — `@falhaEsperada` (falhou: 422, esperado 201) |
| e | Overlap parcial reduz só na janela | Disponível 6 | **FALHA (5)** | `test_e_partial_overlap_should_reduce_availability_for_the_window` — `@falhaEsperada` (falhou: 5, esperado 6) |
| e | Borda: fim = início de outro período conta como overlap | Conta como conflito (decisão em aberto) | **FALHA (5)** | `test_e_touching_boundary_end_equals_start_is_counted_as_overlap` — `@falhaEsperada` (falhou: 5, esperado 6, e não compara datas) |
| f | Reprovado não consome | Disponível 8 | **OK** | `test_f_rejected_registration_does_not_consume_quota` (passa) |
| g | Pendente não consome | Disponível 8 | **OK** | `test_g_pending_registrations_do_not_consume_quota` (passa) |
| g | Não existe status cancelado/expirado | Status disponíveis | **Confirmado** | `test_g_there_is_no_expired_or_cancelled_status` (passa) — `app/Enums/RegistrationStatus.php:7-10` (`novo, em_analise, aprovado, reprovado`) |
| h | Banco permite > quantity aprovados no mesmo período | Deve impedir (constraint) | **Reproduzido (passa hoje)** | `test_h_database_allows_more_approved_than_total_quantity_for_same_period` — `@reproducao` |
| i | Tipos de cota diferentes no mesmo veículo não interferem | Disponível 8 | **OK** | `test_i_different_quota_types_on_same_vehicle_do_not_consume_each_other` (passa) |
| j | Veículos diferentes não interferem | Disponível 8 | **OK** | `test_j_different_vehicles_do_not_interfere` (passa) |
| extra | Validação aceita período maior que a duração do tipo (FS=2d aceita 62d) | Deveria validar duração | **Reproduzido (passa hoje)** | `test_store_accepts_period_longer_than_quota_type_duration` — `@reproducao` |
| extra | Admin reduz quantity abaixo das reservas ativas | Deveria impedir | **Reproduzido (passa hoje)** | `test_quota_quantity_can_be_reduced_below_active_reservations` — `@reproducao` |
| extra | Remover configuração com reservas ativas | Deveria impedir | **Reproduzido (passa hoje)** | `test_removing_vehicle_quota_configuration_with_active_reservations_deletes_it` — `@reproducao` |

Resultado: **11 passam / 5 falham** (as 5 `@falhaEsperada` de b, c, d_other_period, e_partial, e_touching) — exatamente os cenários que dependem de datas.

## 4. Bugs por severidade

**Crítico**
- **C-F-01 · Aprovação não respeita período, nem limite por janela.** `soldCount()` conta todos os aprovados do par veículo/tipo, sem comparar datas; não há UNIQUE/CHECK nem `lockForUpdate`. Causa: `VehicleQuotaConfiguration::soldCount()` (`app/Models/VehicleQuotaConfiguration.php:38-50`), sem restrição no banco (migration `2026_09_18_000000_create_vehicle_related_tables.php:28-37` só tem `unique(['vehicle_id','quota_type_id'])`). Impacto: pode aprovar mais reservas que o `quantity` no mesmo período (overselling), sonegar vagas em períodos disjuntos e nunca liberar a cota após `end_date`. Evidenciado por b, c, d_other_period, e_partial, e_touching, h.

**Alto**
- **C-F-02 · Nenhum mecanismo de liberação da cota.** Não existem status `cancelado`/`expirado` (`RegistrationStatus`), nem Jobs/Schedule para restaurar. Impacto: disponibilidade decresce para sempre; períodos passados consomem cota indefinidamente. Evidenciado por b e c.
- **C-F-03 · Tornar `aprovado` não revalida disponibilidade.** `RegistrationController::updateStatus` muda o status sem checar vagas no período. Impacto: overselling mesmo quando a reserva inicial era válida. Confirma que a janela de 201→aprovado fica sem controle.
- **C-F-04 · Excesso de permissividade do admin sem checagem de reservas.** `VehicleController::updateQuotaConfiguration` (`app/Http/Controllers/Admin/VehicleController.php:126-139`) e `removeQuotaConfiguration` (linha 176) aceitam `quantity >= 0` ou deletam configuração mesmo com aprovados. Caminho inconsistente com `QuotaTypeController::removeVehicleConfiguration` (`app/Http/Controllers/Admin/QuotaTypeController.php:124`), que só desativa quando há registros. Impacto: histórico órfão e disponibilidade distorcida. Evidenciado pelos testes extras.

**Médio**
- **C-F-05 · Falta validação de duração vs `quota_type.days`.** `StoreClientRegistrationRequest` (`app/Http/Requests/StoreClientRegistrationRequest.php:106-107`) só valida `start <= end`. Impacto: cliente reserva período maior que o tipo permite (ex.: Mensal de 2 dias aceita 62 dias). Evidenciado por `test_store_accepts_period_longer_than_quota_type_duration`.
- **C-F-06 · TOCTOU na criação.** `create()` (`ClientRegistrationService.php:51`) não revalida disponibilidade nem trava a linha; a checagem acontece só na validação do request. Impacto: duas submissões simultâneas podem ambas passar a validação e estourar a cota. Associado a C-F-01.

**Baixo / informação**
- **C-F-07 · `QuotaType::soldCount()` com definição incompleta.** Conta apenas `status = aprovado`, contagem global por tipo (sem datas). Consistente com o modelo atual, mas herda todos os defeitos de datas.
- **C-F-08 · Documentação pendente:** `resources/views/admin/registrations/edit.blade.php` não é rastreada (untracked) e `AdminRegistrationEditingTest.php:29` (`assertSee('Adicionar foto')`) já falha **antes** desta auditoria (a view agora usa "Tirar foto"/"Galeria"/"Nenhuma imagem selecionada"). Foi confirmado isoladamente e não faz parte do escopo de cotas.

## 5. Recomendação de arquitetura (não aplicada)

1. **Disponibilidade por período (overlap)** — substituir a contagem global pela contagem de reservas `aprovado` (e possivelmente `em_analise`/`novo`, a decidir) com overlap nos intervalos:
   `WHERE vehicle_id = ? AND quota_type_id = ? AND status = 'aprovado' AND start_date <= :end AND end_date >= :start`.
2. **Transação com `lockForUpdate`** na linha de `vehicle_quota_configurations` no `create()` e no `updateStatus()`/aprovação, para eliminar C-F-06 e o overselling concorrente.
3. **Índice composto** para o overlap: `(vehicle_id, quota_type_id, status, start_date, end_date)`.
4. **Restauração da cota**: ao rejeitar, adicionar status `cancelado`/`expirado`, ou Job agendado (Schedule) que promova `aprovado → expirado` quando `end_date < hoje`. Recomenda-se manter as `client_registrations` (não apagar) — `PurgeExpiredRegistrations` continua para retenção, mas não deve ser usado como "libera cota".
5. **Regras do admin**: bloquear redução de `quantity` abaixo de reservas ativas e impedir remoção de configuração com reservas; unificar com `QuotaTypeController`.
6. **Validação de duração**: período do cadastro deve respeitar `quota_type.days` (`end_date - start_date + 1 <= days`).

## 6. Perguntas em aberto

1. **Borda (inclusive vs exclusive):** reserva que termina exatamente no dia em que outra começa (fim dia 10, início dia 10) deve conflitar? Os testes tratam como conflito (`start <= end_ativo && end >= start_ativo`); confirmar com o negócio.
2. **Quais status consomem cota?** Só `aprovado` (atual), ou `em_analise`/`novo` também reservam para evitar overselling na fila de análise?
3. **Duração do período:** o `quota_type.days` deve ser a duração mínima, exata ou máxima do período do cadastro?
4. **Expiração:** reserva `aprovado` com `end_date < hoje` deve virar automaticamente `expirado` (status novo) por Schedule, ou a cota já pode ser liberada pela regra de overlap sem precisar de status?
5. **Exclusividade física:** um veículo pode ter reservas de tipos de cota diferentes no mesmo período (hoje sim, conforme teste i)? O negócio quer impedir?

## 7. Arquivos analisados e comandos

**Arquivos analisados (leitura)**
- `app/Models/Vehicle.php` · `app/Models/VehicleQuotaConfiguration.php` · `app/Models/QuotaType.php` · `app/Models/ClientRegistration.php`
- `app/Enums/RegistrationStatus.php`
- `app/Http/Controllers/Public/ClientRegistrationController.php`
- `app/Http/Requests/StoreClientRegistrationRequest.php` · `app/Http/Requests/UpdateClientRegistrationRequest.php`
- `app/Services/ClientRegistrationService.php`
- `app/Http/Controllers/Admin/RegistrationController.php` · `VehicleController.php` · `QuotaTypeController.php`
- `app/Policies/*` · `app/Console/Commands/PurgeExpiredRegistrations.php`
- `routes/console.php` · `bootstrap/app.php`
- `database/migrations/2026_09_18_000000_create_vehicle_related_tables.php` · `2026_09_09_000000_create_client_registrations_table.php`

**Testes criados (permitidos pela auditoria, sem alterar produção)**
- `tests/Feature/QuotaAvailabilityAuditTest.php`

**Comandos executados**
- `php artisan test --filter=QuotaAvailabilityAuditTest --compact` → 11 passaram, 5 falharam (as `@falhaEsperada`).
- Suíte Feature completa: 192 testes → 186 passaram, 6 falharam (5 esperadas de cotas + 1 pré-existente `AdminRegistrationEditingTest.php:29`, fora do escopo — confirmada isoladamente).
- `vendor/bin/pint --dirty --format agent` → formatação OK.