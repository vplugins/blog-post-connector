# FAQ - Blog Post Connector Plugin

## General Questions

### What is the Blog Post Connector Plugin?

The Blog Post Connector Plugin is a WordPress plugin that provides REST API endpoints for managing posts, authors, categories, and more. It allows you to interact with WordPress data programmatically, enabling CRUD (Create, Read, Update, Delete) operations via HTTP requests.

### How do I authenticate requests?

Requests to the Blog Post Connector API endpoints require authentication via a Bearer token. Ensure you include the `Authorization` header with a valid token in your requests.

## Endpoints FAQ

### `/status`

**Q: What does the `/status` endpoint do?**

A: The `/status` endpoint provides information about the current version of the plugin.

**Q: What is the required HTTP method for this endpoint?**

A: The `/status` endpoint uses the `GET` method.