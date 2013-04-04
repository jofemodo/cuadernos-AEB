<?php
/**
 * Generacion de fichero de adeudos domiciliados
 *
 * Este módulo genera un fichero de adeudos domiciliados según
 * el formato del Cuaderno 19 de la Asociación Española de Banca
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
 * @package    c19.class.php
 * @author     Eduardo Gonzalez <egonzalez@cyberpymes.com>
 * @copyright  2010 CyberPymes
 * @version    1.2.1
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 3
 *
 * @modified   Marzo 2012, by José Fernando Moyano <fernando@zauber.es>
**/

class C19 {

        public $empty    = "";
        public $name     = "";
        public $nif      = "";
        public $entidad  = "";
        public $oficina  = "";
        public $dc       = "";
        public $cuenta   = "";
        public $sufijo   = "";
        public $concepto = "";

        // Constructor for php4
        public function C19($config_wdb) {
                $cc=$this->ccparts($config_wdb['cc'], 'A');
                $this->name     = $this->string_format($config_wdb['name']);
                $this->nif      = $config_wdb['nif'];
                $this->entidad  = $cc[0];
                $this->oficina  = $cc[1];
                $this->dc       = $cc[2];
                $this->cuenta   = $cc[3];
                $this->sufijo   = $this->string_format($config_wdb['sufijo']);
                $this->concepto = $this->string_format($config_wdb['concepto']);
        }

        // Constructor for php5
        public function __construct($config_wdb) {
                $cc=$this->ccparts($config_wdb['cc'], 'A');
                $this->name     = $this->string_format($config_wdb['name']);
                $this->nif      = $config_wdb['nif'];
                $this->entidad  = $cc[0];
                $this->oficina  = $cc[1];
                $this->dc       = $cc[2];
                $this->cuenta   = $cc[3];
                $this->sufijo   = $this->string_format($config_wdb['sufijo']);
                $this->concepto = $this->string_format($config_wdb['concepto']);
        }

        public function cabecera_presentador() {
                $z['a1'] = "51";
                $z['a2'] = "80";
                $z['b1'] = sprintf("%09s", $this->nif).sprintf("%03s", $this->sufijo);
                $z['b2'] = date("dmy");
                $z['b3'] = sprintf("%6s", $this->empty);
                $z['c']  = $this->format($this->name, 40, 'l', ' ');
                $z['d']  = sprintf("%20s", $this->empty);
                $z['e1'] = sprintf("%04s", $this->entidad);
                $z['e2'] = sprintf("%04s", $this->oficina);
                $z['e3'] = sprintf("%12s", $this->empty);
                $z['f']  = sprintf("%40s", $this->empty);
                $z['g']  = sprintf("%14s", $this->empty);

                $cabecera_presentador = implode("", $z);
                return $cabecera_presentador;

        }

        public function cabecera_ordenante() {
                $z['a1'] = "53";
                $z['a2'] = "80";
                $z['b1'] = sprintf("%09s", $this->nif).sprintf("%03s", $this->sufijo);
                $z['b2'] = date("dmy");
                $z['b3'] = date("dmy", time()+24*3600); // Fecha de mañana
                $z['c']  = $this->format($this->name, 40, 'l', ' ');
                $z['d1'] = sprintf("%04s",  $this->entidad);
                $z['d2'] = sprintf("%04s",  $this->oficina);
                $z['d3'] = sprintf("%02s",  $this->dc);
                $z['d4'] = sprintf("%010s", $this->cuenta);
                $z['e1'] = sprintf("%8s", $this->empty);
                $z['e2'] = "01";
                $z['e3'] = sprintf("%10s", $this->empty);
                $z['f']  = sprintf("%40s", $this->empty);
                $z['g']  = sprintf("%14s", $this->empty);

                $cabecera_ordenante = implode("", $z);
                return $cabecera_ordenante;
        }

        public function individual_obligatorio($data) {
                $cc=$this->ccparts($data['cc'], 'A');
                $importe = number_format($data['importe'], 2, '', '');
                $z['a1'] = "56";
                $z['a2'] = "80";
                $z['b1'] = sprintf("%09s", $this->nif).sprintf("%03s", $this->sufijo);
                $z['b2'] = sprintf("%012s", $data['id_cliente']);
                $z['c']  = $this->format($data['nombre_cliente'], 40, 'l', ' ');
                $z['d1'] = sprintf("%04s", $cc[0]);
                $z['d2'] = sprintf("%04s", $cc[1]);
                $z['d3'] = sprintf("%02s", $cc[2]);
                $z['d4'] = sprintf("%010s", $cc[3]);
                $z['e']  = sprintf("%010s", $importe);
                $z['f1'] = sprintf("%06s", $data['cod_devolucion']);
                $z['f2'] = sprintf("%010s", $data['ref_interna']);
                $z['g']  = sprintf("%-40s", $this->clean_string($data['concepto']));
                $z['h']  = sprintf("%8s", $this->empty);

                $individual_obligatorio = implode("", $z);
                return $individual_obligatorio;
        }

        public function total_ordenante($data) {
                $importe = number_format($data['importe'], 2, '', '');
                $z['a1'] = "58";
                $z['a2'] = "80";
                $z['b1'] = sprintf("%09s", $this->nif).sprintf("%03s", $this->sufijo);
                $z['b2'] = sprintf("%12s", $this->empty);
                $z['c']  = sprintf("%40s", $this->empty);
                $z['d']  = sprintf("%20s", $this->empty);
                $z['e1'] = sprintf("%010s", $importe);
                $z['e2'] = sprintf("%6s", $this->empty);
                $z['f1'] = sprintf("%010s", $data['total_domiciliaciones']);
                $z['f2'] = sprintf("%010s", $data['total_registros']);
                $z['f3'] = sprintf("%20s", $this->empty);
                $z['g']  = sprintf("%18s", $this->empty);

                $total_ordenante = implode("", $z);
                return $total_ordenante;
        }

        public function total_general($data) {
                $total_importes = number_format($data['total_importes'], 2, '', '');
                $z['a1'] = "59";
                $z['a2'] = "80";
                $z['b1'] = sprintf("%09s", $this->nif).sprintf("%03s", $this->sufijo);
                $z['b2'] = sprintf("%12s", $this->empty);
                $z['c']  = sprintf("%40s", $this->empty);
                $z['b2'] = sprintf("%12s", $this->empty);
                $z['c']  = sprintf("%40s", $this->empty);
                $z['d1'] = sprintf("%04s", $data['total_ordenantes']);
                $z['d2'] = sprintf("%16s", $this->empty);
                $z['e1'] = sprintf("%010s", $total_importes);
                $z['e2'] = sprintf("%6s", $this->empty);
                $z['f1'] = sprintf("%010s", $data['total_domiciliaciones']);
                $z['f2'] = sprintf("%010s", $data['total_registros']);
                $z['f3'] = sprintf("%20s", $this->empty);
                $z['g']  = sprintf("%18s", $this->empty);

                $total_general = implode("", $z);
                return $total_general;
        }

        public function ccparts($string, $op="") {

                // Remove all non-numbers
                $string = preg_replace("/[^0-9]+/", "", $string);

                // Define separator string
                $sep = ".";

                // Extract each part from the string
                $entidad = substr($string,0,4);
                $oficina = substr($string,4,4);
                $control = substr($string,8,2);
                $cuenta  = substr($string,10,10);

                switch($op) {
                        // Return just one part
                        case "1": return $entidad; break;
                        case "2": return $oficina; break;
                        case "3": return $control; break;
                        case "4": return $cuenta; break;
                        // Return array
                        case "A": return array($entidad,$oficina,$control,$cuenta); break;
                        // Or return the whole string
                        default:
                                return $entidad.$sep.$oficina.$sep.$control.$sep.$cuenta;
                                break;
                }
        }

        public function string_format($data) {
                $replace_from = array('á','é','í','ó','ú','ñ','ü','ç','Á','É','Í','Ó','Ú','Ñ','Ü','Ç');
                $replace_to   = array('a','e','i','o','u','n','u','c','A','E','I','O','U','N','U','C');
                $data = str_replace($replace_from, $replace_to, $data);
                $data = preg_replace('/[^(\x20-\x7F)]*/','', $data);
                $data = strtoupper(strtolower($data));
                return($data);
        }

        public function format($string, $length, $position="r", $separator=" ") {
                $string = $this->clean_string($string);
                if(!$separator) $separator = " ";
                if(strlen($string)>$length) {
                        $string = substr($string, 0, $length);
                } else {
                        switch($position) {
                                case "l": $position = '-'; break;
                                default:  $position = '';  break;
                        }
                        $format = "%'".$separator.$position.$length."s";
                        $string = sprintf($format, $string);
                }
                return $string;
        }

        public function clean_string($data) {
                return $this->string_format(trim($data));
        }
}

?>
