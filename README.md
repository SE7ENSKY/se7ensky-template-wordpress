se7ensky-template-wordpress
===========================

Project template for local development coupled with Heroku deployment and based on Composer.
It is done after work on and influenced by mchung/heroku-buildpack-wordpress and xyu/heroku-wp projects.
It is meant to be 12factor-compatible, deployable on localhost, cloud platforms and shared hostings.

Deploy to Heroku
----------------
```bash
hk create
hk addon-add cleardb
hk set \
    WP_AUTH_KEY=`dd if=/dev/urandom bs=1 count=30 2>/dev/null | base64`\
    WP_SECURE_AUTH_KEY=`dd if=/dev/urandom bs=1 count=30 2>/dev/null | base64`\
    WP_LOGGED_IN_KEY=`dd if=/dev/urandom bs=1 count=30 2>/dev/null | base64`\
    WP_NONCE_KEY=`dd if=/dev/urandom bs=1 count=30 2>/dev/null | base64`\
    WP_AUTH_SALT=`dd if=/dev/urandom bs=1 count=30 2>/dev/null | base64`\
    WP_SECURE_AUTH_SALT=`dd if=/dev/urandom bs=1 count=30 2>/dev/null | base64`\
    WP_LOGGED_IN_SALT=`dd if=/dev/urandom bs=1 count=30 2>/dev/null | base64`\
    WP_NONCE_SALT=`dd if=/dev/urandom bs=1 count=30 2>/dev/null | base64`
git push heroku master
hk open
```

Run locally
-----------
This project can be run locally for development with as-close-as-possible manner that Heroku runs it.

### Prerequisites
Basic requirements:
* PHP >= 5.5.14 (with FPM, memcached and ftp extensions)
* Nginx
* Composer (installed globally)
* foreman or nf

### Copy environment variables (such as database connection creds and WordPress auth salts)
```bash
hk env > .env
```

### Install dependencies via Composer (WordPress itself, plugins and themes)
```bash
composer install
```

### Run!
```bash
nf start
```
Go [http://localhost:5000/](http://localhost:5000/)!

Workflows
---------
To be written...

Roadmap
-------
* detailed documentation
* hhvm support
