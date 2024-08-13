# `/delete-post` Endpoint
The `/delete-post` endpoint is used to delete a specific post. You can choose to either move the post to the trash or delete it permanently.
## Endpoint
- **URL**: `/wp-json/sm-connect/v1/delete-post`
- **Method**: `DELETE`
- **Authentication**: Bearer Token
- **Content-Type**: `application/json`
## Request
To delete a post, make a `DELETE` request with the required parameters included in the query string. You must also include an `Authorization` header with a valid Bearer token.
### Request Headers
| Header           | Value                                          |
|------------------|------------------------------------------------|
| Content-Type     | `application/json`                            |
| Authorization    | `Bearer <your_access_token>`                   |
### Request Parameters
| Parameter        | Type   | Description                                        |
|------------------|--------|----------------------------------------------------|
| id               | int    | The ID of the post to be deleted.                 |
| trash            | bool   | Set to `true` to permanently delete the post, or omit to move it to trash. |
### Example
```javascript
$http({
    method: 'DELETE',
    url: 'http://sm-post-connector.local/wp-json/sm-connect/v1/delete-post',
    headers: {
        'Authorization': 'Bearer 57e530e516f690213e645cc75fa1abde',
        'Content-Type': 'application/json'
    },
    params: {
        id: 97,
        trash: true
    }
}).then(function successCallback(response) {
    console.log('Post deleted:', response.data.message);
}, function errorCallback(response) {
    console.error('Error:', response.data.message);
});
```