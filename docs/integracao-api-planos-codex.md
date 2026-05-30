# Integracao da API de Planos para outro agente Codex

Este documento explica como outro agente Codex deve integrar um projeto consumidor com a API local de planos que esta rodando neste repositorio via Docker/Sail.

## Ambiente atual

- Servico: API de planos Laravel.
- Stack local: Docker/Sail via `docker-compose`.
- URL para chamadas feitas do host Windows: `http://127.0.0.1/api`.
- URL para chamadas feitas de outro container Docker: `http://host.docker.internal/api`.
- Rota de saude/teste: `GET /api/test`.
- Autenticacao: nenhuma rota da API possui middleware de autenticacao declarado atualmente.
- Formato preferencial: JSON.
- Headers recomendados:

```http
Accept: application/json
Content-Type: application/json
```

No projeto consumidor, centralize a URL em uma variavel de ambiente:

```env
PLANS_SERVICE_URL=http://127.0.0.1/api
```

Se o consumidor estiver rodando dentro de Docker:

```env
PLANS_SERVICE_URL=http://host.docker.internal/api
```

## Checagem rapida

### Request

```http
GET /api/test
```

### Response 200

```json
{
  "message": "API is working"
}
```

## Dados locais disponiveis para teste

Planos seedados:

| id | nome | preco | status |
| --- | --- | ---: | --- |
| 1 | Plano de psicologia | 100 | active |
| 2 | Plano de emagrecimento | 150 | active |
| 3 | Plano familiar | 200 | active |

Usuario de teste criado:

```text
wesleymestre090@gmail.com
```

Para esse usuario foram criadas assinaturas ativas nos tres planos. Tambem existem 2 scores disponiveis por especialidade em cada plano, para facilitar testes com `POST /api/subscription-score/find`.

| plan_id | subscription_id | scores disponiveis |
| ---: | ---: | ---: |
| 1 | 1 | 48 |
| 2 | 2 | 48 |
| 3 | 3 | 48 |

Especialidades seedadas mais usadas nos testes:

| id | nome | council_type |
| ---: | --- | --- |
| 2 | Clinica Medica | medico |
| 22 | Psicologia | psicologo |
| 23 | Nutricao | nutricionista |
| 24 | Educacao Fisica | educador_fisico |

## Convencoes de status

### Subscription status

| valor | significado |
| ---: | --- |
| 1 | active |
| 2 | inactive |
| 3 | cancelled |
| 4 | pending |

### SubscriptionScore status

| valor | significado |
| ---: | --- |
| 1 | enable |
| 2 | used |
| 3 | disable |

### Payment status

```text
pending
approved
rejected
cancelled
```

### Payment method

```text
pix
credit_card
```

## Tratamento de erros

Erros de validacao retornam `422` com o formato padrao do Laravel:

```json
{
  "message": "The plan id field is required. (and 2 more errors)",
  "errors": {
    "plan_id": ["The plan id field is required."],
    "external_key": ["The external key field is required."],
    "specialization_id": ["The specialization id field is required."]
  }
}
```

Erros de regra de negocio normalmente retornam:

```json
{
  "error": "mensagem do erro"
}
```

## Rotas

Todas as rotas abaixo usam a base `PLANS_SERVICE_URL`.

Exemplo:

```text
POST ${PLANS_SERVICE_URL}/subscription-score/find
```

equivale localmente a:

```text
POST http://127.0.0.1/api/subscription-score/find
```

## 1. Assinar plano de psicologia

```http
POST /api/plans/psychology/subscribe
```

Cria uma assinatura para o plano `Plano de psicologia`.

### Request

```json
{
  "external_key": "usuario@email.com",
  "payment_method": "pix",
  "metadata": {
    "origin": "consumer-service"
  },
  "customer": {
    "email": "usuario@email.com",
    "first_name": "Nome",
    "last_name": "Sobrenome",
    "document": "12345678900"
  }
}
```

Para cartao de credito:

```json
{
  "external_key": "usuario@email.com",
  "payment_method": "credit_card",
  "customer": {
    "email": "usuario@email.com",
    "first_name": "Nome",
    "last_name": "Sobrenome",
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

### Campos

| campo | tipo | obrigatorio | observacao |
| --- | --- | --- | --- |
| external_key | string/email | sim | Identificador externo do usuario |
| payment_method | string | para plano pago | `pix` ou `credit_card` |
| metadata | object | nao | Dados livres |
| customer.email | string/email | nao | Email do pagador |
| customer.first_name | string | nao | Maximo 255 |
| customer.last_name | string | nao | Maximo 255 |
| customer.document | string | nao | Maximo 30 |
| card.token | string | se credit_card | Token do cartao |
| card.installments | integer | se credit_card | Minimo 1 |

### Response 201

O retorno e o model de assinatura com `payment` carregado.

```json
{
  "id": 123,
  "plan_id": 1,
  "external_key": "usuario@email.com",
  "status": 4,
  "payment_verified_at": null,
  "created_at": "2026-05-29T12:00:00.000000Z",
  "updated_at": "2026-05-29T12:00:00.000000Z",
  "payment": {
    "id": 999,
    "subscription_id": 123,
    "gateway_payment_id": "pay_123",
    "external_id": "subscription_123",
    "provider": "provider-name",
    "provider_payment_id": "provider_abc",
    "amount": "100.00",
    "payment_method": "pix",
    "status": "pending",
    "paid_at": null,
    "webhook_url": "http://127.0.0.1/api/payments/webhook",
    "metadata": {
      "subscription_id": 123,
      "plan_id": 1,
      "plan_name": "Plano de psicologia"
    },
    "customer": {
      "email": "usuario@email.com",
      "first_name": "Nome",
      "last_name": "Sobrenome",
      "document": "12345678900"
    },
    "provider_response": null,
    "last_synced_at": "2026-05-29T12:00:00.000000Z",
    "created_at": "2026-05-29T12:00:00.000000Z",
    "updated_at": "2026-05-29T12:00:00.000000Z"
  }
}
```

### Erros esperados

- `422`: validacao ou regra de negocio.
- `500`: falha ao assinar plano.

### Observacao importante sobre ambiente local

Os planos seedados sao pagos. A rota tenta chamar uma API externa de pagamento usando:

```env
PAYMENT_API_BASE_URL=
PAYMENT_API_KEY=
```

Se essas variaveis nao estiverem configuradas, a assinatura pode falhar com erro interno. Para testes de score, prefira usar os dados pre-criados do usuario `wesleymestre090@gmail.com`.

## 2. Assinar plano de emagrecimento

```http
POST /api/plans/weight-loss/subscribe
```

Mesmo contrato de entrada e saida da rota de psicologia.

### Response 201

```json
{
  "id": 124,
  "plan_id": 2,
  "external_key": "usuario@email.com",
  "status": 4,
  "payment_verified_at": null,
  "created_at": "2026-05-29T12:00:00.000000Z",
  "updated_at": "2026-05-29T12:00:00.000000Z",
  "payment": {
    "id": 1000,
    "subscription_id": 124,
    "external_id": "subscription_124",
    "amount": "150.00",
    "payment_method": "pix",
    "status": "pending"
  }
}
```

Campos completos de `payment` seguem o mesmo shape da rota de psicologia.

## 3. Assinar plano familiar

```http
POST /api/plans/family/subscribe
```

Mesmo contrato de entrada e saida da rota de psicologia.

### Response 201

```json
{
  "id": 125,
  "plan_id": 3,
  "external_key": "titular@email.com",
  "status": 4,
  "payment_verified_at": null,
  "created_at": "2026-05-29T12:00:00.000000Z",
  "updated_at": "2026-05-29T12:00:00.000000Z",
  "payment": {
    "id": 1001,
    "subscription_id": 125,
    "external_id": "subscription_125",
    "amount": "200.00",
    "payment_method": "pix",
    "status": "pending"
  }
}
```

Campos completos de `payment` seguem o mesmo shape da rota de psicologia.

## 4. Adicionar membro ao plano familiar

```http
POST /api/plans/family/add-member
```

Adiciona um usuario membro a uma assinatura ativa do plano familiar.

### Request

```json
{
  "holder_external_key": "titular@email.com",
  "subscription_id": 123,
  "external_key": "membro@email.com"
}
```

### Campos

| campo | tipo | obrigatorio | observacao |
| --- | --- | --- | --- |
| holder_external_key | string/email | sim | Email do titular da assinatura |
| subscription_id | integer | sim | ID da assinatura familiar |
| external_key | string/email | sim | Email do membro |

### Response 201

```json
{
  "id": 77,
  "subscription_id": 123,
  "external_key": "membro@email.com",
  "created_at": "2026-05-29T12:00:00.000000Z",
  "updated_at": "2026-05-29T12:00:00.000000Z"
}
```

### Erros esperados

```json
{
  "error": "Only the holder can add members to this plan"
}
```

Outras mensagens possiveis:

- `Subscription not found`
- `Only the holder can add members to this plan`
- `The family plan must be active to add members`
- `Subscription is not from the family plan`
- `The holder is already part of this plan`
- `Member is already linked to this family plan`
- `Family plan has reached the maximum number of users`

## 5. Buscar score disponivel

```http
POST /api/subscription-score/find
```

Busca um score disponivel para um usuario e uma especialidade.

A API procura primeiro uma assinatura ativa com:

- `plan_id` informado.
- `external_key` informado.
- `status = active`.

Se nao encontrar uma assinatura direta, tenta localizar o usuario como membro de uma assinatura familiar.

### Request

```json
{
  "plan_id": 1,
  "external_key": "wesleymestre090@gmail.com",
  "specialization_id": 22
}
```

### Campos

| campo | tipo | obrigatorio | observacao |
| --- | --- | --- | --- |
| plan_id | integer | sim | ID do plano |
| external_key | string/email | sim | Usuario titular ou membro |
| specialization_id | integer | sim | ID da especialidade desejada |

### Response 200

```json
{
  "data": {
    "id": 43,
    "subscription_id": 1,
    "score_id": 27,
    "status": 1,
    "score": {
      "id": 27,
      "specialization_id": 22,
      "concil_type": "psicologo"
    },
    "subscription": {
      "id": 1,
      "plan_id": 1,
      "external_key": "wesleymestre090@gmail.com",
      "status": 1
    }
  }
}
```

### Response 404

```json
{
  "error": "Nao tem score para esse tipo de consulta"
}
```

### Exemplo curl

```bash
curl -X POST http://127.0.0.1/api/subscription-score/find \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"plan_id":1,"external_key":"wesleymestre090@gmail.com","specialization_id":22}'
```

### Exemplos com dados locais

Psicologia:

```json
{
  "plan_id": 1,
  "external_key": "wesleymestre090@gmail.com",
  "specialization_id": 22
}
```

Nutricao:

```json
{
  "plan_id": 2,
  "external_key": "wesleymestre090@gmail.com",
  "specialization_id": 23
}
```

Clinica Medica:

```json
{
  "plan_id": 3,
  "external_key": "wesleymestre090@gmail.com",
  "specialization_id": 2
}
```

## 6. Usar score

```http
POST /api/subscription-score/use
```

Marca um score como utilizado.

### Request

```json
{
  "score_id": 43
}
```

Observacao: `score_id` aqui e o ID do registro `subscription_score`, retornado como `data.id` em `/subscription-score/find`. Nao e o campo `data.score_id`.

### Response 200

```json
{
  "data": {
    "id": 43,
    "subscription_id": 1,
    "score_id": 27,
    "status": 2,
    "score": {
      "id": 27,
      "specialization_id": 22,
      "concil_type": "psicologo"
    },
    "subscription": {
      "id": 1,
      "plan_id": 1,
      "external_key": "wesleymestre090@gmail.com",
      "status": 1
    }
  }
}
```

### Erros esperados

```json
{
  "error": "Subscription score not found"
}
```

```json
{
  "error": "This score has already been used"
}
```

## 7. Webhook de pagamento

```http
POST /api/payments/webhook
```

Recebe eventos de pagamento. A rota valida assinatura se `PAYMENT_API_WEBHOOK_SECRET` estiver configurado.

### Header de assinatura

Padrao:

```http
X-Webhook-Signature: <assinatura>
```

O nome do header pode ser alterado por:

```env
PAYMENT_API_WEBHOOK_SIGNATURE_HEADER=
```

### Request

```json
{
  "id": "gateway_event_or_payment_id",
  "external_id": "subscription_123",
  "provider": "provider-name",
  "provider_payment_id": "provider_abc",
  "payment_method": "pix",
  "status": "approved",
  "amount": 100,
  "paid_at": "2026-05-29T12:00:00Z",
  "metadata": {
    "subscription_id": 123,
    "plan_id": 1,
    "plan_name": "Plano de psicologia"
  }
}
```

### Campos

| campo | tipo | obrigatorio |
| --- | --- | --- |
| id | string | sim |
| external_id | string | sim |
| provider | string | nao |
| provider_payment_id | string | nao |
| payment_method | string | nao |
| status | string | sim |
| amount | number | nao |
| paid_at | date | nao |
| metadata | object | nao |

### Response 202

```json
{
  "status": "accepted"
}
```

### Response 401

```json
{
  "error": "invalid signature"
}
```

## Fluxos recomendados no projeto consumidor

### Fluxo para validar se usuario tem credito de consulta

1. Receber `external_key`, `plan_id` e `specialization_id`.
2. Chamar `POST /api/subscription-score/find`.
3. Se retornar `200`, guardar `data.id` como ID do score disponivel.
4. Se retornar `404`, bloquear fluxo e informar que nao ha score disponivel.
5. Se retornar `422` ou `500`, tratar como erro operacional.

### Fluxo para consumir uma consulta

1. Chamar `POST /api/subscription-score/find`.
2. Usar `data.id` da resposta.
3. Chamar `POST /api/subscription-score/use` com `{ "score_id": data.id }`.
4. Considerar sucesso quando a resposta voltar com `data.status = 2`.

### Fluxo para assinar plano

1. Chamar uma das rotas de subscribe conforme o produto.
2. Enviar `external_key` e dados de pagamento.
3. Aguardar retorno da API de pagamento e webhook.
4. Considerar a assinatura ativa quando `subscription.status = 1`.

No ambiente local atual, o gateway de pagamento nao esta configurado. Para testes de integracao do consumidor, use os dados pre-criados ou configure um mock HTTP para o gateway de pagamento.

## Recomendacoes para o agente Codex consumidor

- Criar um client isolado, por exemplo `PlansServiceClient`.
- Nao espalhar URLs fixas no codigo; usar `PLANS_SERVICE_URL`.
- Configurar timeout curto, por exemplo 5 a 10 segundos.
- Logar `status`, `body` e endpoint em falhas.
- Diferenciar erro de negocio (`404` sem score, `422`) de indisponibilidade (`500`, timeout, conexao recusada).
- Para `use`, enviar sempre `data.id` retornado pelo `find`, nao `data.score_id`.
- Em testes locais dentro de Docker, usar `http://host.docker.internal/api`.
- Em testes locais no host, usar `http://127.0.0.1/api`.

## Checklist de integracao

- [ ] Variavel `PLANS_SERVICE_URL` configurada.
- [ ] `GET /test` validado contra `${PLANS_SERVICE_URL}/test`.
- [ ] `POST /subscription-score/find` implementado.
- [ ] `POST /subscription-score/use` implementado.
- [ ] Tratamento de `404` para ausencia de score.
- [ ] Tratamento de `422` para validacao/regra de negocio.
- [ ] Tratamento de `500` e timeout.
- [ ] Testes com `wesleymestre090@gmail.com`.
- [ ] Testes para `plan_id` 1, 2 e 3.
- [ ] Testes para especialidades 2, 22, 23 e 24.
