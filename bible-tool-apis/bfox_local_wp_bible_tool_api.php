<?php

class BfoxLocalWPBibleToolApi extends BfoxLocalBibleToolApi {
	function rowsForRef(BfoxRef $ref) {
		global $wpdb;

		$tableName = $wpdb->escape($this->tableName);

		$indexCol = $wpdb->escape($this->indexCol);
		$indexCol2 = $wpdb->escape($this->indexCol2);

		if (empty($indexCol2)) $refWhere = $ref->sql_where($indexCol);
		else $refWhere = $ref->sql_where2($indexCol, $indexCol2);

		$sql = $wpdb->prepare("SELECT * FROM $tableName WHERE $refWhere");
		$rows = $wpdb->get_results($sql);

		return $rows;
	}
}

?>