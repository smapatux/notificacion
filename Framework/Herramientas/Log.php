<?php
# JALAPEÑO TOURS
# Genera un registro de mensajes generales del sistema
# Ultima Actualización: 21 Marzo 2016
namespace Herramientas;

class Log
{
    # METODOS PARA MANIPULAR ARCHIVOS Y DIRECTORIOS
    ###############################################
    public static function guardarReporte($nombreDelArchivo, $contenido, $tipoReporte)
    {
        if (isset($nombreDelArchivo) && isset($contenido)) {
            $directorioCreado = self::__crearDirectorio($tipoReporte);

            if ($directorioCreado) {
                $nombreDelReporte = $directorioCreado . "/" . $nombreDelArchivo . "." . date("d-m-Y") . ".txt";
                $reporte          = fopen($nombreDelReporte, "a");
                fputs($reporte, "\n" . $contenido);
                fclose($reporte);
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    private static function __crearDirectorio($direccion)
    {
        $init_path = getcwd();

        $error         = false;
        $rutaPrincipal = $init_path . "/log_sistema/";
        $ruta          = $init_path . "/log_sistema/" . $direccion;

        # Direcotrio principal
        if (is_dir($rutaPrincipal)) {
            $tmpError = false; # Directorio ya esta creado
        } elseif (mkdir($rutaPrincipal, 0777)) {
            $tmpError = false; #Directorio creado
        } else {
            $tmpError = true;
        }

        if (is_dir($ruta)) {
            $error = false; # Directorio ya esta creado
        } elseif (mkdir($ruta, 0777)) {
            $error = false; #Directorio creado
        } else {
            $error = true;
        }

        return ($error) ? false : $ruta;
    }

}
