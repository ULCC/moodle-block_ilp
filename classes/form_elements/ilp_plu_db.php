<?php
class ilp_plu_db_functions extends ilp_db_functions{
	public function get_all_records( $table ){
		return $this->dbc->get_records( $table );
	}
}
