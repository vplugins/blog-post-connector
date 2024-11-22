# `/tags` 
The `/tags` endpoint retrieves a list of tags

## Endpoint

- **URL**: `/wp-json/sm-connect/v1/tags`
- **Method**: `GET`
- **Authentication**: Bearer Token

## Request

To access this endpoint, make a `GET` request and include an `Authorization` header with a valid Bearer token.

### Request Headers

| Header           | Value                                          | 
|:-----------------|:-----------------------------------------------| 
| Authorization    | `Bearer <your_access_token>`                   | 

## Response

The response will provide details about the tags.

### Success Response

When the request is successful, the response will be:
```json
{
    "status": 200,
    "message": "Operation successful",
    "data": {
        "tags": {
            "1": {
                "name": "Test Category",
                "id": 2,
                "num_posts": 12
            },
            "2": {
                "name": "Uncategorized",
                "id": 1,
                "num_posts": 14
            }
        }
    }
}
```

## Example

```javascript
$http({
    method: 'GET',
    url: 'SITE_URL/wp-json/sm-connect/v1/tags',
    headers: {
        'Authorization': 'Bearer <your_access_token>'
    }
}).then(function successCallback(response) {
    console.log('tags:', response.data.data.tags);
}, function errorCallback(response) {
    console.error('Error:', response.data.message);
});
```