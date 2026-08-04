# `/webhook/enable` and `/webhook/disable`

These endpoints turn outbound webhook delivery on and off for the current connection. Use them when a connection is no longer active on the Social Marketing side — disabling stops the plugin sending webhooks to a connection that can no longer accept them, without deactivating or uninstalling the plugin.

Both endpoints are **idempotent**: calling `disable` on an already-disabled connection succeeds as a no-op, and the same is true for `enable`.

## Endpoints

- **URL**: `/wp-json/sm-connect/v1/webhook/enable`
- **URL**: `/wp-json/sm-connect/v1/webhook/disable`
- **Method**: `POST`
- **Authentication**: Bearer Token
- **Content-Type**: `application/json`

## Request

Make a `POST` request with an `Authorization` header containing a valid Bearer token. Neither endpoint takes a body or any parameters.

### Request Headers

| Header           | Value                                          |
|:-----------------|:-----------------------------------------------|
| Authorization    | `Bearer <your_access_token>`                   |

## Response

The response reports the resulting state in `data.webhook_status`, so the value can be logged directly.

| Field                  | Type   | Description                                       |
|:-----------------------|:-------|:--------------------------------------------------|
| status                 | int    | HTTP status code.                                 |
| message                | string | Human-readable result message.                    |
| data.webhook_status    | string | Either `enabled` or `disabled`.                   |

```json
{
    "status": 200,
    "message": "Webhook delivery disabled",
    "data": {
        "webhook_status": "disabled"
    }
}
```

A `500` with the message `Failed to update webhook delivery state` means the new state could not be persisted. The state is verified after writing, so a `200` always reflects what is actually stored — treat any non-`200` as "state unchanged" and retry.

### Example

```javascript
$http({
    method: 'POST',
    url: 'SITE_URL/wp-json/sm-connect/v1/webhook/disable',
    headers: {
        'Authorization': 'Bearer <your_access_token>',
        'Content-Type': 'application/json'
    }
}).then(function successCallback(response) {
    console.log('Webhook status:', response.data.data.webhook_status);
}, function errorCallback(response) {
    console.error('Error:', response.data.message);
});
```

## Reading the current state

The [Status](status.md) endpoint reports the current value as `data.webhook_status`, so the state can be read without toggling it.

## What disabling does and does not stop

While delivery is disabled, the plugin stops sending content webhooks — post created, updated, trashed, restored and deleted, plus category, tag and author events.

Plugin **lifecycle** events (`activated`, `deactivated`, `deleted`) are still sent. These report the state of the connection itself rather than site content, and they are how Social Marketing learns that a previously muted site is available again. Note that the disabled state persists across a deactivate/reactivate cycle: re-enabling delivery always requires an explicit call to `/webhook/enable`.
