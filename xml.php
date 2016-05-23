<?php

/* 该类功能，XML->ARRAY，ARRAY->XML支持xml属性 如：array('item'=>array('name'=>'liuheng', 'sex'=>1, '@attr'=>array('attr'=>'name')))
 *  author solariens@163.com
 */

class Xml {

	private $strNodeName = '@value';

	private $strAttrName = '@attr';

	private $arrExceptKey = array();

	private $arrAction = array(
		array('_act00', '_act01'),
		array('_act10', '_act11'),
	);

	public function __construct() {

		$this->arrExceptKey[] = $this->strNodeName;
		$this->arrExceptKey[] = $this->strAttrName;

	}

	public function xml2Array($xml) {

		$objXml = @simplexml_load_string($xml);

		$arrRet = $this->_obj2Array($objXml);

		return $arrRet;
	}

	public function array2Xml(Array $arr) {

		$xml = '<?xml version="1.0" encoding="UTF-8"?><DOCUMENT content_method="full">';

		$xml .= $this->_fillXmlContent($arr);

		$xml .= '</DOCUMENT>';

		return $xml;
	}

	public function setNodeName($strNodeName) {
		$this->strNodeName = $strNodeName;
		foreach ($this->arrExceptKey as $key=>$val) {
			if ($val === $this->strNodeName) {
				unset($this->arrExceptKey[$key]);
				break;
			}
		}
		$this->arrExceptKey[] = $strNodeName;
	}

	private function _obj2Array($objXml) {
		if (!is_object($objXml)) {
			return false;
		}
		if (count($objXml) > 0) {

			$keys = $result = array();

			foreach ($objXml as $key=>$val) {
				isset($keys[$key]) ? $keys[$key] += 1 : $keys[$key] = 1;
				if ($keys[$key] == 1) {

					$result[$key] = $this->_obj2Array($val);
				} elseif ($keys[$key] == 2) {

					$result[$key] = array($result, $this->_obj2Array($val));
				} elseif ($keys[$key] > 2) {

					$result[$key][] = $this->_obj2Array($val);
				}
			}

			return $result;
		} else {
			return (string)$objXml;
		}
	}

	private function _fillXmlContent($arr, $xml='') {

		foreach ($arr as $key=>$val) {
			if (is_numeric($key)) {
				return false;
			}
			if (in_array($key, $this->arrExceptKey)) {
				continue;
			}
			$bool = is_array($val);
			$arrAttr = isset($val[$this->strAttrName]) ? $val[$this->strAttrName] : array();
			$xml .= $this->_getXmlHeader($key, $val, $bool, $arrAttr);
			if ($bool) {
				$xml = $this->_fillXmlContent($val, $xml);
			}
			$xml .= $this->_getXmlFooter($key, $val, $bool, $arrAttr);
		}

		return $xml;
	}

	private function _getXmlHeader($key, $val, $bool, $arrAttr) {

		$xml = '';

		if ($bool) {
			$intNodeName = intval(isset($val[$this->strNodeName]));
			$intAttr = intval(!empty($arrAttr));
			$fnAction = $this->arrAction[$intNodeName][$intAttr];
			$xml = call_user_func_array(array($this, $fnAction), array($key, $val, $arrAttr));
 		} else {
 			$xml = sprintf('<%s><![CDATA[%s]]></%s>', $key, $val, $key);
 		}
		return $xml;
	}

	private function _act00($key, $val, $arrAttr) {
		$xml = sprintf('<%s>', $key);
		return $xml;
	}

	private function _act01($key, $val, $arrAttr) {
		$xml = sprintf('<%s', $key);
		foreach ($arrAttr as $k=>$v) {
			$xml .= sprintf(' %s="%s" ', $k, $v);
		}
		$xml .= '>';
		return $xml;
	}

	private function _act10($key, $val, $arrAttr) {
		$xml = sprintf('<%s><![CDATA[%s]]></%s>', $key, $val[$this->strNodeName], $key);
		return $xml;
	}

	private function _act11($key, $val, $arrAttr) {
		$xml = sprintf('<%s', $key);
		foreach ($arrAttr as $k=>$v) {
			$xml .= sprintf(' %s="%s" ', $k, $v);
		}
		$xml .= sprintf('><![CDATA[%s]]></%s>', $val[$this->strNodeName], $key);
		return $xml;
	}

	private function _getXmlFooter($key, $val, $bool, $arrAttr) {

		$xml = '';

		if ($bool && !isset($val[$this->strNodeName])) {
			$xml = sprintf('</%s>', $key);
		}
		
		return $xml;
	}
}
