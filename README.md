# Notes

## Testing

*products.json*: File with sample products

If you use Lando + json-server to test, don't use `localhost` or `127.0.0.1`, put the local IP address

Example:
```
json-server --host=10.178.109.55 products.json

```
