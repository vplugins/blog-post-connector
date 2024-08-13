# `/status` 

The `/status` endpoint provides information about the current status of the SM Post Connector plugin, including its version.

## Endpoint

- **URL**: `/wp-json/sm-connect/v1/status`
- **Method**: `GET`
- **Authentication**: Bearer Token

## Request

To access this endpoint, make a `GET` request and include an `Authorization` header with a valid Bearer token.

### Request Headers

| Header           | Value                                          | 
|:-----------------|:-----------------------------------------------| 
| Authorization    | `Bearer <your_access_token>`                   | 

## Example Request

Here’s an example of how to make a request to the `/status` endpoint using AngularJS:

```javascript
$http({
    method: 'GET',
    url: 'http://sm-post-connector.local/wp-json/sm-connect/v1/status',
    headers: {
        'Authorization': 'Bearer 57e530e516f690213e645cc75fa1abde' // Replace with your actual token
    }
}).then(function successCallback(response) {
    // Success callback
    console.log('Status:', response.data.data.version);
    return response.data;
}, function errorCallback(response) {
    // Error callback
    console.error('Error:', response.data.message);
    throw response.data.message;
});
```

## Response

```json
{
    "status": 200,
    "message": "Operation successful",
    "data": {
        "version": "0.0.1"
    }
}
```
