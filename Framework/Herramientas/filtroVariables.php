<?php 
	/**
	* 
	*/
	class FiltroVariables
	{		
		function __construct() {}

		#Numero idReserva Jalapeño
		public $idReservaJalapeno = array(
				'filter' => FILTER_VALIDATE_REGEXP,
				'options' => array( 'regexp' => "/(?P<id>[\d]+)(-| - )*(?P<indice>[a-z]+)/i" )
			);

		#DATE
		public $Fecha = array(
				'filter' => FILTER_VALIDATE_REGEXP,
				'options' => array( 'regexp' => "/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4}$/" )
			);

		#RFC
		public $RFC = array(
				'filter' => FILTER_VALIDATE_REGEXP,
				'options' =>array('regexp'=>'/^[a-z\d]+$/i')
			);

		# String Nombres
		public $Nombres = array(
				'filter' => FILTER_VALIDATE_REGEXP,
				'options' =>array('regexp'=>'/^[a-zA-ZÀ-ÖØ-öø-ÿ]+\.?(( |\-)[a-zA-ZÀ-ÖØ-öø-ÿ]+\.?)*$/i')
			);

		# String Monto
		public $Monto = array(
				'filter' => FILTER_VALIDATE_REGEXP,
				'options' =>array('regexp'=>'/^[\d]+.[\d]{2}$/')
			);

		#Telefono
		public $Telefono = array(
				'filter' => FILTER_VALIDATE_REGEXP,
				'options' =>array('regexp'=>'/^0{0,2}([\+]?[\d]{1,3} ?)?([\(]([\d]{2,3})[)] ?)?[0-9][0-9 \-]{6,}( ?([xX]|([eE]xt[\.]?)) ?([\d]{1,5}))?$/')
			);

		#Direccion
		public $Direccion = array(
				'filter' => FILTER_VALIDATE_REGEXP,
				'options' =>array('regexp'=>'/^[a-zA-Z1-9À-ÖØ-öø-ÿ]+\.?(( |\-)[a-zA-Z1-9À-ÖØ-öø-ÿ]+\.?)*$/')
			);

		#Numero Interior/Exterior
		public $NumIntExt = array(
				'filter' => FILTER_VALIDATE_REGEXP,
				'options' =>array('regexp'=>'/^[a-zA-Z\d-]*$/')
			);

	}


# Ejemplos
/*

error_reporting(E_ALL | E_STRICT);


class FiltroVariables
{		
	function __construct() {}

	public static $filtroRFC = array(
                        'filter' => FILTER_CALLBACK,
                        'options' => 'FiltroVariables::filtroRfc'
                       );

	public static function filtroRfc( $value )
	{
	    return preg_replace( '/[^a-z\d_]/iu', '', $value );
	}

}


$data = array(
    'product_id'    => 'libgd<script>',
    'component'     => '10',
    'versions'      => '2.0.33',
    'testscalar'    => array('2', '23', '10', '12'),
    'testarray'     => '2',
    'rfc'     => '2DAWDWD34 /  2',
);

$args = array(
    'product_id'   => FILTER_SANITIZE_ENCODED,
    'component'    => array('filter'    => FILTER_VALIDATE_INT,
                            'flags'     => FILTER_FORCE_ARRAY, 
                            'options'   => array('min_range' => 1, 'max_range' => 10)
                           ),
    'versions'     => FILTER_SANITIZE_ENCODED,
    'doesnotexist' => FILTER_VALIDATE_INT,
    'testscalar'   => array(
                            'filter' => FILTER_VALIDATE_INT,
                            'flags'  => FILTER_REQUIRE_SCALAR,
                           ),
    'testarray'    => array(
                            'filter' => FILTER_VALIDATE_INT,
                            'flags'  => FILTER_FORCE_ARRAY,
                           ),
    'rfc'    => FiltroVariables::$filtroRFC

);

$myinputs = filter_var_array($data, $args);

var_dump($myinputs);
echo " ";

*/


 ?>