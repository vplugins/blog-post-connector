# `/status` Endpoint
The `/status` endpoint provides information about the current status of the SM Post Connector plugin, including its version.
## Endpoint
- **URL**: `/wp-json/sm-connect/v1/status`
- **Method**: `GET`
- **Authentication**: Bearer Token
## Request
To access this endpoint, make a `GET` request and include an `Authorization` header with a valid Bearer token.
### Request Headers
| Header           | Value                                          |
|------------------|------------------------------------------------|
| Authorization    | `Bearer <your_access_token>`                   |
## Example Request
Here’s an example of how to make a request to the `/status` endpoint using AngularJS:
```javascript
// AngularJS Service Example
app.service('StatusService', function($http) {
    var apiUrl = 'http://sm-post-connector.local/wp-json/sm-connect/v1/status';
    var token = '57e530e516f690213e645cc75fa1abde'; // Replace with your actual token
    this.getStatus = function() {
        return $http({
            method: 'GET',
            url: apiUrl,
            headers: {
                'Authorization': 'Bearer ' + token
            }
        }).then(function(response) {
            // Success callback
            return response.data;
        }, function(error) {
            // Error callback
            console.error('Error:', error);
            throw error;
        });
    };
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
