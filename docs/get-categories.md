# `/categories` 
The `/categories` endpoint retrieves a list of categories, including their names, IDs, and the number of posts associated with each category.

## Endpoint

- **URL**: `/wp-json/sm-connect/v1/categories`
- **Method**: `GET`
- **Authentication**: Bearer Token

## Request

To access this endpoint, make a `GET` request and include an `Authorization` header with a valid Bearer token.

### Request Headers

| Header           | Value                                          | 
|:-----------------|:-----------------------------------------------| 
| Authorization    | `Bearer <your_access_token>`                   | 

## Response

The response will provide details about the categories.

### Success Response

When the request is successful, the response will be:
```json
{
    "status": 200,
    "message": "Operation successful",
    "data": {
        "categories": {
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
    url: 'SITE_URL/wp-json/sm-connect/v1/categories',
    headers: {
        'Authorization': 'Bearer <your_access_token>'
    }
}).then(function successCallback(response) {
    console.log('Categories:', response.data.data.categories);
}, function errorCallback(response) {
    console.error('Error:', response.data.message);
});
```