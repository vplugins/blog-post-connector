# SM Post Connector Plugin

The SM Post Connector plugin bridges the gap between WordPress and Social Marketing tools, enabling users to manage blog posts directly from their Social Marketing platform.

## Features

- **Token-based Authentication**: Secure communication between WordPress and the Social Marketing tool with token-based authentication.
- **Custom Endpoints**: Create, modify, and delete posts via custom endpoints (`/sm-connect/v1/create-post`, `/sm-connect/v1/get-authors`, etc.).
- **Settings Page**: Configure access token, default post type, author, and category settings via an intuitive settings interface.

## Usage

1. **Configure Settings**:
   - Navigate to WordPress Admin Panel › SM Post Connector › Settings.
   - Set up your access token and default settings for post type, author, and category.

2. **API Endpoints**:
   - Use the provided endpoints (`/sm-connect/v1/create-post`, `/sm-connect/v1/get-authors`, etc.) to manage posts and retrieve data from your Social Marketing tool.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
