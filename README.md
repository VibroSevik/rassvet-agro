## Rassvet Agro | OpenCart 3.0.3.2 + PHP 7

## :computer: Installation on local machine

> [!IMPORTANT]
> Requirements for system and environment: <br>
> 1) PHP 7.4 with [OpenCart requirements](https://blog.chenniweb.com/opencart-3-x-minimum-requirements-and-features/).

As an administrator:

```shell
cd C:\Apache24\bin
```

```shell
httpd.exe -k start
```


## :whale: Installation on docker

```shell
docker compose up -d --build
```


## After installation

* Install database dump;

As an administrator:

```shell
docker exec -i opencart-db mysql -u root -p root_password opencart_db < dump.sql
```

or 

```shell
docker exec -i <database_container> mysql -u <mysql_user> -p <user_password> <database_name> < /path/to/your/dump.sql
```


* Go to localhost:8080 and login into dashboard.

> [!IMPORTANT]
> Standard credentials for admin: <br>
> login: admin <br>
> password: foo

> [!IMPORTANT]
> Application works only with database dump.