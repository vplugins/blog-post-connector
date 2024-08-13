# `/create-post` Endpoint
The `/create-post` endpoint is used to create a new post with the specified title, content, status, and author.
## Endpoint
- **URL**: `/wp-json/sm-connect/v1/create-post`
- **Method**: `POST`
- **Authentication**: Bearer Token
- **Content-Type**: `application/json`
## Request
To create a new post, make a `POST` request with the required parameters included in the query string. You must also include an `Authorization` header with a valid Bearer token.
### Request Headers
| Header           | Value                                          |
|------------------|------------------------------------------------|
| Content-Type     | `application/json`                            |
| Authorization    | `Bearer <your_access_token>`                   |
### Request Parameters
| Parameter | Type   | Description                          |
|-----------|--------|--------------------------------------|
| title     | string | The title of the post.                |
| content   | string | The content of the post.              |
| status    | string | The status of the post (e.g., `publish`). |
| author           | int    | The ID of the author of the post.                 |
| featured_image   | string | URL of the new featured image for the post.       |
### Example 
```javascript
$http({
    method: 'POST',
    url: 'http://sm-post-connector.local/wp-json/sm-connect/v1/create-post',
    headers: {
        'Authorization': 'Bearer 57e530e516f690213e645cc75fa1abde',
        'Content-Type': 'application/json'
    },
    data: {
        title: 'Sample Title',
        content: 'Sample content',
        status: 'publish',
        author: 1
    }
}).then(function successCallback(response) {
    console.log('Post created:', response.data.data);
}, function errorCallback(response) {
    console.error('Error:', response.data.message);
});
```
