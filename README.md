# Absolutely minimal no dependency URL shortener with redis as backend

1. POST url to shorten. Request constraints:
- HTTP method: POST
- Header: "Content-Type: application/json"
- Header: "Accept: text/plain"
- Request body (JSON): {"url": "<url to shorten>"}

Response headers:
- 200: Shortened URL
- 400: Invalid request method/body
- 415: Invalid headers
- 500: Error connecting to redis

2. GET short url to redirect to the original unshortened url. Constraints:
- HTTP method: GET

Response headers:
- 302: Redirect to original URL
- 400: URL not found

Usage example:
1. Shorten URL:

```
curl 'http://apphost.tld' --header 'Content-Type: application/json' --header 'Accept: text/plain' --data-raw '{"url":"https://google.com"}'
```

Response:

```
HTTP/1.1 200 OK

http://apphost.tld/1a3
```

2. Redirect to original url:

```
curl -vL 'http://pet.loc/1a3'
```

Response:

```
HTTP/1.1 302 Found

Location: https://google.com
```
