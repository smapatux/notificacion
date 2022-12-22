<?php
#     
namespace Herramientas;

class VariablesGlobales
{
	# Privadas
	private $navegacion;

    # Publicas
	public $errores;
    public $notificaciones;

	public function VariablesGlobales()
	{	
        $this->errores = array();
        $this->notificaciones = array();
        $this->navegacion = strtolower($_SERVER['REQUEST_URI']);
	}

    public static function GET($key)
    {
        return isset($_GET[$key]) ? $_GET[$key] : "";
    }

    public static function POST( $key )
    {
        return isset($_POST[$key]) ? $_POST[$key] : "";
    }

    public static function REQUEST( $key )
    {
        return isset($_REQUEST[$key]) ? $_REQUEST[$key] : "";
    }

    public static function inputVariable($param)
    {
        return isset($param) ? $param : "";
    }
}
?>
