# `/update-post` Endpoint
The `/update-post` endpoint is used to update an existing post with the specified parameters such as title, content, status, author, and featured image.

## Endpoint
- **URL**: `/wp-json/sm-connect/v1/update-post`
- **Method**: `POST`
- **Authentication**: Bearer Token
- **Content-Type**: `application/json`

## Request
To update a post, make a `POST` request with the required parameters included in the query string. You must also include an `Authorization` header with a valid Bearer token.

### Request Headers

| Header           | Value                                          | 
|:-----------------|:-----------------------------------------------| 
| Authorization    | `Bearer <your_access_token>`                   | 


### Request Parameters
| Parameter        | Type   | Description                                       |
|:-----------------|:-------|:--------------------------------------------------|
| id               | int    | The ID of the post to be updated.                 |
| title            | string | The new title for the post.                       |
| content          | string | The new content for the post.                     |
| status           | string | The new status of the post (e.g., `publish`).     |
| author           | int    | The ID of the author of the post.                 |
| featured_image   | string | URL of the new featured image for the post.       |

### Example 
```javascript
$http({
    method: 'POST',
    url: 'http://sm-post-connector.local/wp-json/sm-connect/v1/update-post',
    headers: {
        'Authorization': 'Bearer 57e530e516f690213e645cc75fa1abde',
        'Content-Type': 'application/json'
    },
    params: {
        id: 97,
        title: 'Updated',
        content: 'Updated',
        status: 'publish',
        author: 1,
        featured_image: 'https://cdn.pixabay.com/photo/2018/07/10/21/53/tournament-3529744_1280.jpg'
    }
}).then(function successCallback(response) {
    console.log('Post updated:', response.data.data);
}, function errorCallback(response) {
    console.error('Error:', response.data.message);
});
```