<?php
/**
 *  多进程抓取百度结果页自然结果，包括标题、摘要、图片、链接、来源
 *  @author solariens@163.com
 *  @since 2016-04-15
 */
class BaiduNaturalResultSpider {

	private $_strQuery = null;

	public $thread_count = 4;      //开启进程数

	private $_arrPids = array();

	private $_intPageNum;         //需要抓取的自然结果页数

	public $arrAllResult = array();

	public $dataHandle = null;    //可以回调指定的函数完成对应功能

	private $_arrPattern = array(
		array('name'=>'is_nature_result', 'reg'=>'/data-log=\"(.*?)\"/', 'location'=>1, 'must'=>0, 'replace'=>1),
		array('name'=>'title', 'reg'=>'/<h3(.*?)>(.*?)<\/h3>/', 'location'=>2, 'must'=>1, 'replace'=>1),
		array('name'=>'content', 'reg'=>'/<p class=\"c-line-clamp3 c-color\">(.*?)<\/p>/', 'location'=>1, 'must'=>1, 'replace'=>1),
		array('name'=>'source_url', 'reg'=>'/<div class=\"c-showurl c-line-clamp1\"><span>(.*?)<\/span>/', 'location'=>1, 'must'=>0, 'replace'=>0),
		array('name'=>'url', 'reg'=>'/<div class=\"c-container\"><a(.*?)class=\"c-blocka\" href=\"(.*?)\">/', 'location'=>2, 'must'=>1, 'replace'=>0),
		array('name'=>'img', 'reg'=>'/<div class=\"c-img c-img-s\"><img data-imagedelaysrc=\"(.*?)\"/', 'location'=>1, 'must'=>0, 'replace'=>0),
	);

	public function __construct($strQuery, $intPageNum=76) {
		$this->_strQuery = $strQuery;
		$this->_intPageNum = $intPageNum;
	}

	/*主调用方法*/
	public function run() {

		for ($i=0; $i<$this->thread_count; ++$i) {

			$pid = pcntl_fork();

			if ($pid === -1) {
				throw new exception('fork process failed !');
			} elseif ($pid > 0) {
				$this->_arrPids[$i] = $pid;
			} else {
				$arrResult = $this->worker($i);
				if ($this->dataHandle) {
					call_user_func($this->dataHandle, $arrResult);
				}
				usleep(1000);
				exit;
			}
		}
	}

	/*为worker分配任务*/
	private function worker($intCurThread) {

		$intPage = ceil($this->_intPageNum / $this->thread_count);

		$intBegin = $intCurThread * $intPage;

		$intEnd = ($intCurThread + 1) * $intPage;

		for ($i=$intBegin; $i<$intEnd; ++$i) {

			if ($i > $this->_intPageNum) {
				break;
			}
			$strUrl = 'm.baidu.com/s?word=' . $this->_strQuery;
			$strUrl .= $i == 0 ? '' : '&pn=' . $i*10;
			$strHtml = $this->curl($strUrl);
			$arrMatches = $this->getHtmlContent($strHtml);
			$arrNaturalResult = $this->getNaturalResult($arrMatches);
			if (!empty($arrNaturalResult)) {
				$this->arrResult[$i] = $arrNaturalResult;
			}
		}
		return $this->arrResult;
	}

	private function curl($url) {

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

		$result = curl_exec($ch);

		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if ($httpcode != 200) {
			return false;
		}

		return $result;
	}

	public function getHtmlContent($strHtml) {

		$strReg = '/<div\sclass=\"result\sc-result(.*)">(.*)(<div\sclass=\"c-img\sc-img-s\">(.*)<\/div>)?<\/div>/Uis';

		if (empty($strHtml)) {
			return false;
		}

		preg_match_all($strReg, $strHtml, $arrMatches);

		return $arrMatches[0];
	}

	public function setPattern($arrRule) {
		$this->_arrPattern[] = $arrRule;
	}

	public function getNaturalResult($arrMatches) {

		if (empty($arrMatches) || !is_array($arrMatches)) {
			return false;
		}

		$arrNaturalResult = array();

		foreach ($arrMatches as $key=>$div) {

			foreach ($this->_arrPattern as $val) {
				$strName = $val['name'];
				$$strName = '';
			}

			foreach ($this->_arrPattern as $val) {

				$strName = $val['name'];

				preg_match_all($val['reg'], $div, $matches);

				if (!isset($matches[$val['location']][0]) && $val['must']) {
					continue;
				}

				$$strName = isset($matches[$val['location']][0]) ? $matches[$val['location']][0] : '';

				if ($val['name'] === 'is_nature_result') {

					$$strName = str_replace('\'', '"', $$strName);
					$$strName = json_decode($$strName, true);
					if (${$strName}['ensrcid'] !== 'www_normal') {
						break;;
					}
				} else {
					if ($val['replace']) {
						$$strName = str_replace(array('<em>', '</em>'), '', $$strName);
					}
				}
				$arrNaturalResult[$key][$val['name']] = $$strName;
			}
		}

		return $arrNaturalResult;
	}

	public function __destruct() {
		if (count($this->_arrPids)) {
			foreach ($this->_arrPids as $key=>$pid) {
				$ret = pcntl_waitpid($pid, $status);
				if ($ret === -1 || $ret > 0) {
					unset($this->_arrPids[$key]);
				}
			}
		}
	}
}
