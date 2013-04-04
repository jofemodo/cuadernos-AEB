<?php
/**
 * Importación de Datos del Cuaderno 43 de AEB
 *
 * Este módulo parsea los datos de extracto de cuenta bancaria en
 * el formato del Cuaderno 43 de la Asociación Española de Banca
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @package    c43.class.php
 * @author     José Fernando Moyano <fernando@zauber.es>
 * @copyright  2012 José Fernando Moyano (ZauBeR)
 * @version    1.0.0
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 3
 *
**/

class C43 {

  var $movimientos=array();

	var $cliente_nombre="";
	var $cliente_codigo="";
	var $entidad="";
	var $oficina="";
	var $cuenta="";
	var $fecha_contable="";
	var $fecha_inicial="";
	var $fecha_final="";
	var $saldo_inicial=0;
	var $saldo_final=0;
	var $divisa="";

	var $n_debe=0;
	var $total_debe=0;
	var $n_haber=0;
	var $total_haber=0;
	var $n_regs=0;

	var $status="";		/* OK, ERROR */
	var $message="";

        
	// Constructor for php4
	function C43($fpath) {
		$this->parse_file($fpath);
	}

	// Constructor for php5
	function __construct($fpath) {
		$this->parse_file($fpath);
	}
  
	function set_status($s,$m="") {
		$this->status=$s;
		$this->message=$m;
	}

	function parse_file($fpath) {
		if (file_exists($fpath)) {
			$data=file($fpath);
			if (count($data)==0) $this->set_status("ERROR","El fichero no contiene datos");
			else return $this->parse_data($data);
		} else {
			$this->set_status("ERROR","El fichero no existe");
		}
		return 0;
	}
  
	function parse_data(&$data) {
		if (!is_array($data)) {
			$data=explode("\n",$data);
		}
		if (count($data)==0) {
			return $this->set_status("ERROR","No hay datos");
		}

		$pos=0;
		try {
			//$this->parse_cabecera_fichero($data,$pos);
			$this->parse_cabecera_cuenta($data,$pos);
			while ($mov=$this->parse_movimiento($data,$pos)) $this->movimientos[]=$mov;
			$this->parse_final_cuenta($data,$pos);
			$this->parse_final_fichero($data,$pos);
			$this->set_status("OK","");
		} catch (Exception $e) {
			$this->set_status("ERROR",$e->getMessage());
			//echo "EXCEPCIÓN: ".$e->getMessage()."\n";
		}
		return $pos;
	}

	function parse_cabecera_fichero(&$data,&$pos) {
		$row=$data[$pos++];
		$cod=substr($row,0,2);
		if ($cod!="00") throw new Exception("Cabecera de Fichero: Código Incorrecto");
		$this->entidad=substr($row,2,4);
		$this->fecha_contable="20".substr($row,6,6);
		//$this->n_regs++;
	}

	function parse_cabecera_cuenta(&$data,&$pos) {
		$row=$data[$pos++];
		$cod=substr($row,0,2);
		if ($cod!="11") throw new Exception("Cabecera de Cuenta: Código Incorrecto");
		$this->entidad=substr($row,2,4);
		$this->oficina=substr($row,6,4);
		$this->cuenta=substr($row,10,10);
		$this->fecha_inicial="20".substr($row,20,6);
		$this->fecha_final="20".substr($row,26,6);
		$cod=substr($row,32,1);
		if ($cod!="1" && $cod!="2") throw new Exception("Cabecera de Cuenta: Código de Saldo Inicial Incorrecto");
		$this->saldo_inicial=0.01*intval(substr($row,33,14));
		if ($cod=="1") $this->saldo_inicial*=-1;
		$cod=substr($row,47,3);
		$this->divisa=$this->get_divisa($cod);
		$cod=substr($row,50,1);
		if ($cod!="3" && $cod!="1") throw new Exception("Cabecera de Cuenta: Código de Modalidad Incorrecto");
		$this->cliente_nombre=substr($row,51,26);
		$this->cliente_codigo=substr($row,77,3);
		$this->n_regs++;
	}

	function parse_movimiento(&$data,&$pos) {
		$row=$data[$pos];
		$cod=substr($row,0,2);
		if ($cod!="22") return false;

		$pos++;
		$mov=array();
		$mov['oficina']=substr($row,6,4);
		$mov['fecha_operacion']="20".substr($row,10,6);
		$mov['fecha_valor']="20".substr($row,16,6);
		$mov['concepto_comun']=substr($row,22,2);
		$mov['concepto_entidad']=substr($row,24,3);
		$cod=substr($row,27,1);
		if ($cod!="1" && $cod!="2") throw new Exception("Registro Principal de Movimiento: Clave Debe/Haber Incorrecta");
		$mov['importe']=0.01*intval(substr($row,28,14));
		if ($cod=="1") {
			$this->n_debe++;
			$this->total_debe+=$mov['importe'];
			$mov['importe']*=-1;
		} else {
			$this->n_haber++;
			$this->total_haber+=$mov['importe'];
		}
		$mov['num_doc']=substr($row,42,10);
		$mov['ref1']=substr($row,52,12);
		$mov['ref2']=substr($row,64,16);
		$this->n_regs++;

		$nc=1;
		do {
			$row=$data[$pos];
			$cod=substr($row,0,2);
			if ($cod=="23") {
				$pos++;
				$cod=substr($row,2,2);
				if ($cod!=="0".$nc) throw new Exception("Registro Complementario de Concepto $nc: Código de Dato Incorrecto");
				if (!isset($mov['conceptos_extra'])) $mov['conceptos_extra']=array();
				$mov['conceptos_extra'][]=substr($row,4,38);
				$mov['conceptos_extra'][]=substr($row,42,38);
				$nc++;
				$this->n_regs++;
				$regcomp=1;
			} else $regcomp=0;
		} while ($regcomp);

		return $mov;
	}

	function parse_final_cuenta(&$data,&$pos) {
		$row=$data[$pos++];
		$cod=substr($row,0,2);
		if ($cod!="33") throw new Exception("Registro Final de Cuenta: Código Incorrecto");
		$entidad=substr($row,2,4);
		$oficina=substr($row,6,4);
		$cuenta=substr($row,10,10);
		$n_debe=substr($row,20,5);
		if ($n_debe!=$this->n_debe) throw new Exception("Registro Final de Cuenta: Nº de Apuntes Debe Incorrecto");
		$total_debe=0.01*intval(substr($row,25,14));
		if ("".$total_debe!="".$this->total_debe) throw new Exception("Registro Final de Cuenta: Total Importes Debe Incorrecto");
		$n_haber=substr($row,39,5);
		if ($n_haber!=$this->n_haber) throw new Exception("Registro Final de Cuenta: Nº de Apuntes Haber Incorrecto");
		$total_haber=0.01*intval(substr($row,44,14));
		if ("".$total_haber!="".$this->total_haber) throw new Exception("Registro Final de Cuenta: Total Importes Haber Incorrecto");
		$cod=substr($row,58,1);
		if ($cod!="1" && $cod!="2") throw new Exception("Registro Final de Cuenta: Código de Saldo Final Incorrecto");
		$this->saldo_final=0.01*intval(substr($row,59,14));
		if ($cod=="1") $this->saldo_final*=-1;
		if ("".($total_haber-$total_debe)!="".($this->saldo_final-$this->saldo_inicial)) throw new Exception("Registro Final de Cuenta: Balance Incorrecto");
		$cod=substr($row,73,3);
		$divisa=$this->get_divisa($cod);
		if ($divisa!=$this->divisa) throw new Exception("Registro Final de Cuenta: Código de Divisa Incorrecto");
		$this->n_regs++;
	}

	function parse_final_fichero(&$data,&$pos) {
		$row=$data[$pos++];
		$cod=substr($row,0,2);
		if ($cod!="88") throw new Exception("Registro Final de Fichero: Código Incorrecto");
		$nueves=substr($row,2,18);
		if ($nueves!="999999999999999999") throw new Exception("Registro Final de Fichero: 18 Nueves Incorrectos");
		$n_regs=intval(substr($row,20,6));
		if ($n_regs!=$this->n_regs) throw new Exception("Registro Final de Fichero: Nº Total de Registros Incorrecto");
	}

	function get_divisa($cod) {
		if ($cod="978") return "euro";
		else return $cod;
	}
}

?>
