# `/get-post` 
The `/get-post` endpoint is used to get a specific post. 

## Endpoint

- **URL**: `/wp-json/sm-connect/v1/get-post`
- **Method**: `GET`
- **Authentication**: Bearer Token
- **Content-Type**: `application/json`

## Request

To get a post, make a `GET` request with the required parameters included in the query string. You must also include an `Authorization` header with a valid Bearer token.

### Request Headers

| Header           | Value                                          | 
|:-----------------|:-----------------------------------------------| 
| Authorization    | `Bearer <your_access_token>`                   | 

### Request Parameters

| Parameter        | Type   | Description                                                               |
|:-----------------|:-------|:--------------------------------------------------------------------------|
| id               | int    | The ID of the post to be deleted.                                         |

### Example

```javascript
$http({
    method: 'GET',
    url: 'SITE_URL/wp-json/sm-connect/v1/get-post',
    headers: {
        'Authorization': 'Bearer <your_access_token>',
        'Content-Type': 'application/json'
    },
    params: {
        id: 97,
    }
}).then(function successCallback(response) {
    console.log('Post details:', response.data.message);
}, function errorCallback(response) {
    console.error('Error:', response.data.message);
});
```