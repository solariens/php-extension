<?php

/**
 * this is a simple hash demo which use php implement
 * @author solariens <solariens@163.com>
 */
class Node {
	public $key;
	public $data;
	public $next;
}

class Hash {
	
	private $size;
	
	private $buckets;

	private $node;

	private $element_num = 0;

	public function __construct($size) {
		$this->size = $size;
		$this->buckets = new SplFixedArray($size);
	}

	public function insertNode($key, $data) {
		if ($this->element_num >= $this->size) {
			echo 'there is no enough space left' . "\n";
			return false;
		}
		$hashKey = $this->_getHashKey($key);
		$this->_initNode($key, $data);
		
		if (isset($this->buckets[$hashKey])) {
			$this->node->next = $this->buckets[$hashKey];
		}
		$this->buckets[$hashKey] = $this->node;
		$this->element_num++;
	}

	public function getNode($key) {
		$hashKey = $this->_getHashKey($key);

		if (isset($this->buckets[$hashKey])) {
			$buckets = $this->buckets[$hashKey];
			while ($buckets != null) {
				if ($buckets->key === $key) {
					return $buckets->data;
				}
				$buckets = $buckets->next;
			}
		}
		return null;
	}

	public function delNode($key) {
		$hashKey = $this->_getHashKey($key);

		if (isset($this->buckets[$hashKey])) {
			while ($this->buckets[$hashKey] != null) {
				if ($this->buckets[$hashKey]->key === $key) {
					$this->buckets[$hashKey] = $this->buckets[$hashKey]->next;
					$this->element_num--;
					return true;
				}
				$this->buckets[$hashKey] = $this->buckets[$hashKey]->next;
			}	
		}
		return false;
	}

	public function setSize($size) {
		$tmpSize = $this->size;
		$this->size += $size;
		$this->buckets->setSize($this->size);
		$this->element_num = 0;
		$this->_reInsertNode($tmpSize);
	}

	private function _reInsertNode($size) {
		$buckets = $this->buckets;
		$this->buckets = new SplFixedArray($this->size);
		for ($i=0; $i<$size; ++$i) {
			if (!isset($buckets[$i])) {
				continue;
			}
			$tmp = $buckets[$i];
			while ($tmp != null) {
				$this->insertNode($tmp->key, $tmp->data);
				$tmp = $tmp->next;
			}
		}	
		unset($buckets);
	}

	private function _initNode($key, $data) {
		$node = new Node();
		$node->key = $key;
		$node->data = $data;
		$node->next = null;
		$this->node = $node;
	}

	private function _getHashKey($key) {
		$len = strlen($key);
		$hashKey = 0;

		for ($i=0; $i<$len; ++$i) {
			$hashKey += ord($key[$i]);
		}

		return $hashKey % $this->size;
	}	
}
