# `/status` 

The `/status` endpoint provides information about the current status of the Blog Post Connector plugin, including its version.

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
    url: 'SITE_URL/wp-json/sm-connect/v1/status',
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
        "site_details": {
            "name": "Marketing Agency",
            "description": "We provide SEO, PPC, social media, web design and more.",
            "logo": "http://social-post-integration.local/wp-content/uploads/2024/09/123menlife_logo.png",
            "version": "6.6.2",
            "plugin_version": {
                "current": "0.0.1",
                "latest": "1.0.0Beta"
            }
        }
    }
}
```
