<?php 
	namespace Controlador;	
	Class Error
	{
		public $id;
		public $mensaje;
		public $mostrar;
		
		function __construct($id = 0, $mensaje = "Error no detectado", $mostrar = false)
		{
			$this->id = $id;
			$this->mensaje = $mensaje;
			$this->mostrar = $mostrar;
		}

		function obtenerID()
		{
			return $this->id;
		}

		function obtenerMensaje()
		{
			return $this->mensaje;
		}

		# Error visible para el usuario? return (bool)
		function mostrar()
		{
			return $this->mostrar;
		}		
	}	
?>