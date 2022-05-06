## Documentation

Purpose of this repo is to demonstrate psd2 JWS signing in PHP.
Command `./run.sh bog:consent` requests conset from BOG and dumps returned data.
Project should be used as an example, and is not intended for production use.

### Quick start:

- `composer install`
- `cp .env.example .env`
- `vi .env`
- `./run.sh bog:consent`

Command [./run.sh](https://github.com/Nexus-GE/psd2-cli/blob/master/run.sh) downloads php:8.0-cli docker container and uses it to run the command.
Command source code lives inside [app/Commands/BogConsent.php](https://github.com/Nexus-GE/psd2-cli/blob/master/app/Commands/BogConsent.php) file.

To convert certificates from pfx to pem use:
`openssl pkcs12 -in nexus.pfx -out nexus.pem`

Put certificates in `certs` directory.

Following settings need to be filled out in .env file:

```
THEIRCERT=.pem
OURCERT=.pem
CERTPASSPHRASE=
PROXYPORT=
PROXY=
PROXYUSERPWD=
REDIRECTURL=
```
