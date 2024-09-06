# `/authors` Endpoint

The `/authors` endpoint retrieves a list of authors, including their names, IDs, and the number of posts they have authored.

## Endpoint

- **URL**: `/wp-json/sm-connect/v1/authors`
- **Method**: `GET`
- **Authentication**: Bearer Token

## Request

To access this endpoint, make a `GET` request and include an `Authorization` header with a valid Bearer token.

### Request Headers

| Header           | Value                                          | 
|:-----------------|:-----------------------------------------------| 
| Authorization    | `Bearer <your_access_token>`                   | 

## Response

The response will provide details about the authors.

### Success Response

When the request is successful, the response will be:
```json
{
    "status": 200,
    "message": "Operation successful",
    "data": {
        "authors": {
            "1": {
                "name": "admin",
                "id": 1,
                "num_posts": "13"
            },
            "2": {
                "name": "Test User",
                "id": 2,
                "num_posts": "0"
            }
        }
    }
}
```

## Example

```javascript
$http({
    method: 'GET',
    url: 'SITE_URL/wp-json/sm-connect/v1/authors',
    headers: {
        'Authorization': 'Bearer <your_access_token>'
    }
}).then(function successCallback(response) {
    console.log('Authors:', response.data.data.authors);
}, function errorCallback(response) {
    console.error('Error:', response.data.message);
});
```