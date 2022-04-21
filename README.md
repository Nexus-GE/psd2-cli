## Documentation


- `composer install`
- `cp .env.example .env`
- `vi .env`
- `./run.sh bog:consent`

Command lives in app/Commands/BogConsent.php file.

To convert certificates from pfx to pem use:
`openssl pkcs12 -in nexus.pfx -out nexus.pem`

Put certificates in `certs` directory.
