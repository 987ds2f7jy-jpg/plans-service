# Contrato da API de Planos

Este documento descreve as rotas expostas pelo serviço, os parâmetros de entrada, regras de negócio inferidas do código e os formatos de saída esperados. O objetivo é servir como base para outro agente reimplementar esta API em outro projeto sem depender desta codebase.

## Visão geral

- Base path: `/api`
- Formato de entrada: JSON
- Formato de saída: JSON
- Autenticação: não há middleware de autenticação declarado em [routes/api.php](/home/lucas/plans-service/routes/api.php)
- Conteúdo atual de rotas:
    - `POST /api/payments/webhook`
    - `POST /api/plans/psychology/subscribe`
    - `POST /api/plans/weight-loss/subscribe`
    - `POST /api/plans/family/subscribe`
    - `POST /api/plans/family/add-member`
    - `POST /api/subscription-score/find`
    - `POST /api/subscription-score/use`

## Convenções de resposta

### Erros de validação

As rotas usam `FormRequest`. Em Laravel, quando a validação falha, a resposta padrão é `422 Unprocessable Entity` com estrutura semelhante a:

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "field_name": ["Mensagem de validação"]
    }
}
```

Observação: o projeto atual possui algumas classes de validação com sintaxe inconsistente, mas a intenção funcional das regras está clara e foi considerada neste documento.

### Erros de regra de negócio

Quando uma regra de domínio falha dentro dos services/controllers, a API responde com:

```json
{
    "error": "mensagem de erro"
}
```

Normalmente com status `422`.

### Erros internos

Quando ocorre exceção não tratada pelo domínio:

```json
{
    "error": "mensagem fixa da rota"
}
```

Normalmente com status `500`.

## Modelos de dados relevantes

### Subscription

Objeto retornado nas rotas de assinatura.

```json
{
    "id": 123,
    "plan_id": 2,
    "external_key": "user@example.com",
    "status": 4,
    "payment_verified_at": null,
    "created_at": "2026-04-25T12:00:00.000000Z",
    "updated_at": "2026-04-25T12:00:00.000000Z",
    "payment": {
        "id": 999,
        "subscription_id": 123,
        "gateway_payment_id": "pay_123",
        "external_id": "subscription_123",
        "provider": "provider-name",
        "provider_payment_id": "provider_abc",
        "amount": "49.90",
        "payment_method": "pix",
        "status": "pending",
        "paid_at": null,
        "webhook_url": "https://example.com/api/payments/webhook",
        "metadata": {
            "subscription_id": 123,
            "plan_id": 2,
            "plan_name": "Plano de psicologia"
        },
        "customer": {
            "email": "user@example.com",
            "first_name": "John",
            "last_name": "Doe",
            "document": "12345678900"
        },
        "provider_response": null,
        "last_synced_at": "2026-04-25T12:00:00.000000Z",
        "created_at": "2026-04-25T12:00:00.000000Z",
        "updated_at": "2026-04-25T12:00:00.000000Z"
    }
}
```

### Subscription status

Valores observados em [app/Enums/Subscription/SubscriptionStatusEnum.php](/home/lucas/plans-service/app/Enums/Subscription/SubscriptionStatusEnum.php):

- `1`: `active`
- `2`: `inactive`
- `3`: `cancelled`
- `4`: `pending`

### Payment status

Valores observados em [app/Enums/Payment/PaymentStatusEnum.php](/home/lucas/plans-service/app/Enums/Payment/PaymentStatusEnum.php):

- `pending`
- `approved`
- `rejected`
- `cancelled`

### Payment method

Valores observados em [app/Enums/Payment/PaymentMethodEnum.php](/home/lucas/plans-service/app/Enums/Payment/PaymentMethodEnum.php):

- `pix`
- `credit_card`

### SubscriptionScore

Formato retornado por [app/Http/Resources/SubscriptionScoreResource.php](/home/lucas/plans-service/app/Http/Resources/SubscriptionScoreResource.php):

```json
{
    "data": {
        "id": 55,
        "subscription_id": 123,
        "score_id": 8,
        "status": 1,
        "score": {
            "id": 8,
            "specialization_id": 2,
            "concil_type": "medico"
        },
        "subscription": {
            "id": 123,
            "plan_id": 2,
            "external_key": "user@example.com",
            "status": 1
        }
    }
}
```

### SubscriptionScore status

Valores observados em [app/Enums/SubscriptionScore/SubscriptionScoreStatusEnum.php](/home/lucas/plans-service/app/Enums/SubscriptionScore/SubscriptionScoreStatusEnum.php):

- `1`: `enable`
- `2`: `used`
- `3`: `disable`

## Rotas

## `POST /api/plans/psychology/subscribe`

Cria uma assinatura pendente para o plano de psicologia e inicializa o fluxo de pagamento.

### Request body

```json
{
    "external_key": "user@example.com",
    "payment_method": "pix",
    "metadata": {
        "origin": "mobile-app"
    },
    "customer": {
        "email": "user@example.com",
        "first_name": "John",
        "last_name": "Doe",
        "document": "12345678900"
    },
    "card": {
        "token": "card_token",
        "issuer_id": "issuer_123",
        "installments": 1,
        "payment_method_id": "visa"
    }
}
```

### Campos de entrada

- `external_key`: obrigatório, `string`, formato `email`
- `payment_method`: opcional na validação, mas obrigatório em tempo de execução quando o plano tiver preço maior que zero; valores aceitos: `pix`, `credit_card`
- `metadata`: opcional, objeto livre
- `customer`: opcional, objeto
- `customer.email`: opcional, `email`
- `customer.first_name`: opcional, string até 255 caracteres
- `customer.last_name`: opcional, string até 255 caracteres
- `customer.document`: opcional, string até 30 caracteres
- `card`: opcional, objeto
- `card.token`: obrigatório se `payment_method = credit_card`
- `card.issuer_id`: opcional
- `card.installments`: obrigatório se `payment_method = credit_card`, inteiro mínimo `1`
- `card.payment_method_id`: opcional

### Regras de negócio

- Busca um plano ativo com nome exato `Plano de psicologia`.
- Cria `subscription` com status inicial `pending`.
- Se o plano for gratuito (`price <= 0`), a assinatura é ativada imediatamente.
- Se o plano for pago:
    - cria ou sincroniza um `payment`
    - envia `external_id = "subscription_{subscription_id}"` para o gateway
    - define `webhook_url` como `config('services.payment_api.webhook_url')` ou fallback para `/api/payments/webhook`
- Após aprovação do pagamento, a assinatura passa para status `active`.

### Respostas possíveis

#### `201 Created`

Retorna a assinatura recém-criada com relacionamento `payment` carregado.

Para plano gratuito:

```json
{
    "id": 123,
    "plan_id": 1,
    "external_key": "user@example.com",
    "status": 1,
    "payment_verified_at": "2026-04-25T12:00:00.000000Z",
    "created_at": "2026-04-25T12:00:00.000000Z",
    "updated_at": "2026-04-25T12:00:00.000000Z",
    "payment": null
}
```

Para plano pago:

```json
{
    "id": 123,
    "plan_id": 1,
    "external_key": "user@example.com",
    "status": 4,
    "payment_verified_at": null,
    "created_at": "2026-04-25T12:00:00.000000Z",
    "updated_at": "2026-04-25T12:00:00.000000Z",
    "payment": {
        "id": 999,
        "subscription_id": 123,
        "gateway_payment_id": "pay_123",
        "external_id": "subscription_123",
        "provider": "provider-name",
        "provider_payment_id": "provider_abc",
        "amount": "49.90",
        "payment_method": "pix",
        "status": "pending",
        "paid_at": null,
        "webhook_url": "https://example.com/api/payments/webhook",
        "metadata": {
            "subscription_id": 123,
            "plan_id": 1,
            "plan_name": "Plano de psicologia"
        },
        "customer": {
            "email": "user@example.com",
            "first_name": "John",
            "last_name": "Doe",
            "document": "12345678900"
        },
        "provider_response": null,
        "last_synced_at": "2026-04-25T12:00:00.000000Z",
        "created_at": "2026-04-25T12:00:00.000000Z",
        "updated_at": "2026-04-25T12:00:00.000000Z"
    }
}
```

#### `422 Unprocessable Entity`

Casos prováveis:

- erro de validação
- `Psychology plan is not active`
- `payment_method is required for paid plans`
- outras mensagens do gateway ou camada de serviço propagadas como `InvalidArgumentException`

#### `500 Internal Server Error`

```json
{
    "error": "Unable to subscribe external user to psychology plan"
}
```

## `POST /api/plans/weight-loss/subscribe`

Cria uma assinatura pendente para o plano de emagrecimento e inicializa o fluxo de pagamento.

### Request body

Mesmo contrato de `POST /api/plans/psychology/subscribe`.

### Regras de negócio

- Busca um plano ativo com nome exato `Plano de emagrecimento`.
- Cria `subscription` com status inicial `pending`.
- Quando a assinatura é ativada, existe uma rotina de sincronização de acesso externo ao serviço `app_nutricao`.
- A assinatura do plano dá acesso a múltiplos scores, inferidos por especialidade:
    - clínica médica
    - nutrição
    - educação física

### Respostas possíveis

#### `201 Created`

Mesmo shape da rota de psicologia.

#### `422 Unprocessable Entity`

Casos prováveis:

- erro de validação
- `Weight loss plan is not active`
- `payment_method is required for paid plans`

#### `500 Internal Server Error`

```json
{
    "error": "Unable to subscribe external user to weight loss plan"
}
```

## `POST /api/plans/family/subscribe`

Cria uma assinatura pendente para o plano família e inicializa o fluxo de pagamento.

### Request body

Mesmo contrato de `POST /api/plans/psychology/subscribe`.

### Regras de negócio

- Busca um plano ativo com nome exato `Plano familiar`.
- Cria `subscription` com status inicial `pending`.
- O score associado ao plano é um score de clínica médica.
- O titular da assinatura é definido por `external_key`.

### Respostas possíveis

#### `201 Created`

Mesmo shape da rota de psicologia.

#### `422 Unprocessable Entity`

Casos prováveis:

- erro de validação
- `Family plan is not active`
- `payment_method is required for paid plans`

#### `500 Internal Server Error`

```json
{
    "error": "Unable to subscribe external user to family plan"
}
```

## `POST /api/plans/family/add-member`

Adiciona um membro a uma assinatura do plano família.

### Request body

```json
{
    "holder_external_key": "holder@example.com",
    "subscription_id": 123,
    "external_key": "member@example.com"
}
```

### Campos de entrada

- `holder_external_key`: obrigatório, `email`
- `subscription_id`: obrigatório, inteiro
- `external_key`: obrigatório, `email`

### Regras de negócio

- A assinatura informada precisa existir.
- Apenas o titular pode adicionar membros.
- A assinatura precisa estar `active`.
- A assinatura precisa pertencer ao plano família.
- O titular não pode ser adicionado como membro.
- O mesmo membro não pode ser adicionado duas vezes à mesma assinatura.
- O limite total do plano é `4` usuários contando o titular.
    - na prática: titular + até 3 membros adicionais

### Resposta de sucesso

#### `201 Created`

```json
{
    "id": 77,
    "subscription_id": 123,
    "external_key": "member@example.com",
    "created_at": "2026-04-25T12:00:00.000000Z",
    "updated_at": "2026-04-25T12:00:00.000000Z"
}
```

### Respostas de erro

#### `422 Unprocessable Entity`

Casos prováveis:

- erro de validação
- `Subscription not found`
- `Only the holder can add members to this plan`
- `The family plan must be active to add members`
- `Subscription is not from the family plan`
- `The holder is already part of this plan`
- `Member is already linked to this family plan`
- `Family plan has reached the maximum number of users`

#### `500 Internal Server Error`

```json
{
    "error": "Unable to add member to family plan"
}
```

## `POST /api/subscription-score/find`

Localiza um score disponível para uso por um assinante ou membro de assinatura.

### Request body

```json
{
    "plan_id": 2,
    "external_key": "user@example.com",
    "specialization_id": 10
}
```

### Campos de entrada

- `plan_id`: obrigatório, inteiro
- `external_key`: obrigatório, `email`
- `specialization_id`: obrigatório, inteiro

### Regras de negócio

- Primeiro tenta encontrar uma `subscription` ativa com:
    - `plan_id` igual ao informado
    - `external_key` igual ao informado
    - `status = active`
- Se não encontrar, tenta localizar `subscription_member` por `external_key`.
- Encontrando membro, busca a assinatura ativa dele para o `plan_id` informado.
- Com a assinatura encontrada, carrega `subscription_scores` com status `enable`.
- Retorna o primeiro score cujo `score.specialization_id` seja igual ao `specialization_id` informado.

### Respostas possíveis

#### `200 OK`

```json
{
    "data": {
        "id": 55,
        "subscription_id": 123,
        "score_id": 8,
        "status": 1,
        "score": {
            "id": 8,
            "specialization_id": 10,
            "concil_type": "medico"
        },
        "subscription": {
            "id": 123,
            "plan_id": 2,
            "external_key": "holder@example.com",
            "status": 1
        }
    }
}
```

#### `404 Not Found`

Quando não houver score disponível para aquele tipo de consulta:

```json
{
    "error": "Nao tem score para esse tipo de consulta"
}
```

#### `422 Unprocessable Entity`

Casos prováveis:

- erro de validação
- outras falhas de domínio propagadas como `InvalidArgumentException`

#### `500 Internal Server Error`

```json
{
    "error": "Failed to find subscription score"
}
```

## `POST /api/subscription-score/use`

Marca um score como utilizado.

### Request body

```json
{
    "score_id": 55
}
```

### Campos de entrada

- `score_id`: obrigatório, inteiro

### Regras de negócio

- Faz lock transacional do registro.
- O score precisa existir.
- O score precisa estar com status `enable`.
- Em sucesso, altera o status para `used`.

### Respostas possíveis

#### `200 OK`

```json
{
    "data": {
        "id": 55,
        "subscription_id": 123,
        "score_id": 8,
        "status": 2,
        "score": {
            "id": 8,
            "specialization_id": 10,
            "concil_type": "medico"
        },
        "subscription": {
            "id": 123,
            "plan_id": 2,
            "external_key": "holder@example.com",
            "status": 1
        }
    }
}
```

#### `422 Unprocessable Entity`

Casos prováveis:

- erro de validação
- `Subscription score not found`
- `This score has already been used`

#### `500 Internal Server Error`

```json
{
    "error": "Failed to use subscription score"
}
```

## `POST /api/payments/webhook`

Recebe eventos do gateway de pagamento.

### Headers esperados

- Header de assinatura:
    - nome padrão: `X-Webhook-Signature`
    - pode ser sobrescrito por `config('services.payment_api.webhook_signature_header')`

### Request body

```json
{
    "id": "gateway_event_or_payment_id",
    "external_id": "subscription_123",
    "provider": "provider-name",
    "provider_payment_id": "provider_abc",
    "payment_method": "pix",
    "status": "approved",
    "amount": 49.9,
    "paid_at": "2026-04-25T12:00:00Z",
    "metadata": {
        "subscription_id": 123,
        "plan_id": 2,
        "plan_name": "Plano de psicologia"
    }
}
```

### Campos de entrada

- `id`: obrigatório, string
- `external_id`: obrigatório, string
- `provider`: opcional, string
- `provider_payment_id`: opcional, string
- `payment_method`: opcional, string
- `status`: obrigatório, string
- `amount`: opcional, numérico
- `paid_at`: opcional, data
- `metadata`: opcional, objeto

### Regras de negócio

- Valida a assinatura do webhook antes de qualquer processamento.
- Se a assinatura for inválida, retorna `401`.
- Em caso válido:
    - normaliza os headers
    - despacha processamento assíncrono em job
    - retorna `202`
- O processamento do webhook:
    - deduplica eventos por hash de `id + external_id + status + paid_at`
    - tenta localizar o pagamento por `external_id`
    - se não achar, tenta por `gateway_payment_id = id`
    - atualiza o pagamento
    - propaga o status para a assinatura:
        - `approved` -> `active`
        - `pending` -> `pending`
        - `cancelled` -> `cancelled`
        - `rejected` -> `inactive`

### Respostas possíveis

#### `202 Accepted`

```json
{
    "status": "accepted"
}
```

#### `401 Unauthorized`

```json
{
    "error": "invalid signature"
}
```

#### `422 Unprocessable Entity`

Erro de validação do payload.

## Regras implícitas para reimplementação

- `external_key` representa o identificador externo do usuário e hoje está sendo validado como e-mail.
- O sistema depende de nomes literais de planos:
    - `Plano de psicologia`
    - `Plano de emagrecimento`
    - `Plano familiar`
- O sistema depende de especializações literais para alguns scores:
    - `Clinica Medica`
    - `Nutricao`
- Os scores concedidos por plano hoje são:
    - psicologia: 4 scores da especialidade de psicologia
    - emagrecimento: 1 score de clínica médica, 1 de nutrição, 1 de educação física
    - família: 1 score de clínica médica
- O contrato público hoje expõe status numéricos para `subscription.status` e `subscription_score.status`.
- O contrato público hoje expõe `payment.status` como string.
- As rotas de assinatura retornam o model Eloquent praticamente cru, não um resource dedicado.

## Inconsistências observadas no código atual

Estas inconsistências são importantes para a outra implementação, porque o comportamento pretendido está mais claro que a implementação literal atual.

- Em [app/Http/Requests/Plan/AddFamilyPlanMemberRequest.php](/home/lucas/plans-service/app/Http/Requests/Plan/AddFamilyPlanMemberRequest.php), [app/Http/Requests/SubscriptionScore/FindAvailableSubscriptionScoreRequest.php](/home/lucas/plans-service/app/Http/Requests/SubscriptionScore/FindAvailableSubscriptionScoreRequest.php) e [app/Http/Requests/SubscriptionScore/UseSubscriptionScoreRequest.php](/home/lucas/plans-service/app/Http/Requests/SubscriptionScore/UseSubscriptionScoreRequest.php) há chaves e valores sem aspas. Para reimplementação, considerar a intenção declarada neste documento.
- Em [app/Enums/Professional/ConcilTypeEnum.php](/home/lucas/plans-service/app/Enums/Professional/ConcilTypeEnum.php) os valores string também aparecem sem aspas. A intenção aparente é:
    - `doctor = "medico"`
    - `speechTherapist = "fonoaudiologo"`
    - `psychologist = "psicologo"`
    - `nutritionist = "nutricionista"`
    - `physicalEducator = "educador_fisico"`
- O model [app/Models/Subscription.php](/home/lucas/plans-service/app/Models/Subscription.php) usa o campo `payment_verified_at`, mas a migration atual de `subscriptions` não cria essa coluna. Para reimplementação, decidir explicitamente se o campo fará parte do schema.
- Em [app/Services/Plan/PsychologyPlanService.php](/home/lucas/plans-service/app/Services/Plan/PsychologyPlanService.php) e [app/Services/Plan/WeightLossPlanService.php](/home/lucas/plans-service/app/Services/Plan/WeightLossPlanService.php) aparece `council_type`, enquanto o restante do código e schema usam `concil_type`. Para reimplementação, padronizar o nome.

## Arquivos de referência

- Rotas: [routes/api.php](/home/lucas/plans-service/routes/api.php)
- Controllers:
    - [app/Http/Controllers/Plan/PlanController.php](/home/lucas/plans-service/app/Http/Controllers/Plan/PlanController.php)
    - [app/Http/Controllers/SubscriptionScore/SubscriptionScoreController.php](/home/lucas/plans-service/app/Http/Controllers/SubscriptionScore/SubscriptionScoreController.php)
    - [app/Http/Controllers/PaymentWebhookController.php](/home/lucas/plans-service/app/Http/Controllers/PaymentWebhookController.php)
- Requests:
    - [app/Http/Requests/Plan/SubscribePlanRequest.php](/home/lucas/plans-service/app/Http/Requests/Plan/SubscribePlanRequest.php)
    - [app/Http/Requests/Plan/AddFamilyPlanMemberRequest.php](/home/lucas/plans-service/app/Http/Requests/Plan/AddFamilyPlanMemberRequest.php)
    - [app/Http/Requests/SubscriptionScore/FindAvailableSubscriptionScoreRequest.php](/home/lucas/plans-service/app/Http/Requests/SubscriptionScore/FindAvailableSubscriptionScoreRequest.php)
    - [app/Http/Requests/SubscriptionScore/UseSubscriptionScoreRequest.php](/home/lucas/plans-service/app/Http/Requests/SubscriptionScore/UseSubscriptionScoreRequest.php)
    - [app/Http/Requests/PaymentWebhookRequest.php](/home/lucas/plans-service/app/Http/Requests/PaymentWebhookRequest.php)
- Services:
    - [app/Services/Plan/PsychologyPlanService.php](/home/lucas/plans-service/app/Services/Plan/PsychologyPlanService.php)
    - [app/Services/Plan/WeightLossPlanService.php](/home/lucas/plans-service/app/Services/Plan/WeightLossPlanService.php)
    - [app/Services/Plan/FamilyPlanService.php](/home/lucas/plans-service/app/Services/Plan/FamilyPlanService.php)
    - [app/Services/SubscriptionScore/SubscriptionScoreService.php](/home/lucas/plans-service/app/Services/SubscriptionScore/SubscriptionScoreService.php)
    - [app/Services/Payment/PaymentService.php](/home/lucas/plans-service/app/Services/Payment/PaymentService.php)
