# Google Indexing notifier

Send published pages to google indexing API.

## Setup

https://cloud.google.com/docs/authentication/production#obtaining_and_providing_service_account_credentials_manually

```bash
./flow google:storecredentials auth.json
```

## Settings

```yaml
Onedrop:
  GoogleNotify:
    baseUri: 'https://example.com/'
    nodeTypes: 
      - 'Vendor.Package:Document.Some'
```
