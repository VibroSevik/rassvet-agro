## Rassvet Agro | OpenCart 3.0.4.0 + PHP 8.0

## :computer: Installation on local machine

> [!IMPORTANT]
> Requirements for system and environment: <br>
> 1) PHP 8.0 with [OpenCart requirements](https://blog.chenniweb.com/opencart-3-x-minimum-requirements-and-features/).

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

```shell
docker compose exec webserver bash
```

```shell
composer i
```


## After installation

* Install database dump;

As an administrator:

```shell
docker exec -i opencart-db mysql -u root -proot_password opencart_db < dump.sql
```

or 

```shell
docker exec -i <database_container> mysql -u <mysql_user> -p<user_password> <database_name> < /path/to/your/dump.sql
```

> [!IMPORTANT]
> Application temporarily works only with database dump.

* Go to localhost:8080/admin and login into dashboard.

* After go to Modules/Extensions page -> Modifications and refresh modifications for generation OCMOD files.
