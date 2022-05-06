## Documentation

- `composer install`
- `cp .env.example .env`
- `vi .env`
- `./run.sh bog:consent`

Command run.sh downloads php:8.0-cli docker container and uses it to run the command.

Command lives in [app/Commands/BogConsent.php](https://github.com/Nexus-GE/psd2-cli/blob/master/app/Commands/BogConsent.php) file.

To convert certificates from pfx to pem use:
`openssl pkcs12 -in nexus.pfx -out nexus.pem`

Put certificates in `certs` directory.

