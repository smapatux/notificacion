<?php
    # CONTIENE TODA LA INFORMACION IMPORTANTE RELACIONADA AL FUNCIONAMIENTO DEL SISTEMA Y SUS CONEXIONES
    $jsonStr = file_get_contents("conf.json");
    $config  = json_decode($jsonStr);

    $direccion = str_replace('\\', '/', $_SERVER["DOCUMENT_ROOT"]) . "/";
    define("RUTA", $direccion);
    define("DIRECCION", "/");

    #SESSION
    define("SESSION_NAME", $config->session->name);

    #DEBUG
    define("DEBUG", (Boolean) $config->debug);

    #BASE DE DATOS
    define('HOST_DB', $config->database->host);
    define('NOMBRE_DB', $config->database->dbname);
    define('USUARIO_DB', $config->database->user);
    define('PASSWORD_DB', $config->database->pass);
    define('DRIVER_DB', $config->database->driver);

    #REDIS
    define("REDIS_HOST", $config->redis->host);
    define("REDIS_PORT", $config->redis->port);
    define("REDIS_SCHEME", $config->redis->scheme);
    define("REDIS_PASS", $config->redis->pass);
    define("REDIS_PREFIX", $config->redis->prefix);

    #WEBSOCKET
    define("WS_HOST", $config->websocket->host);
    define("WS_PORT", $config->websocket->port);
?>