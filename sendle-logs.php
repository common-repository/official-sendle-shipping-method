<?php

if(isset($_REQUEST['logs'])){
	if($_REQUEST['logs']=='flush'){
		global $wpdb, $url;
		$table_name = $wpdb->prefix."sendlelogs";
		$wpdb->query("TRUNCATE TABLE $table_name");
		$url=admin_url('admin.php');//?page=sendle_logs&logs=success');
		echo "<script>location.href='".$url."?page=sendle_logs&logs=success';</script>";
	}
}

if(!class_exists('Sendle_Loader_WP_List_Table')){
	class Sendle_Loader_WP_List_Table {

		public function __construct(){
			add_action('admin_menu', array($this,'ossm_sendle_logs'));
		}
		public function ossm_sendle_logs(){
			$sendle_setting = maybe_unserialize(get_option('woocommerce_ossmsendle_settings'));
			if(isset($sendle_setting['enable_log'])){
				if($sendle_setting['enable_log'] == 'yes'){
					// add submenu items
					add_submenu_page('woocommerce',esc_attr__('Sendle Logs Table','textdomain'),esc_html__('Sendle Logs','textdomain'), ossm_getAssignRole(),'sendle_logs', array($this,'sendle_logs'),4);
				}
			}
		}

		public function sendle_logs(){
			$logs_table = new Sendle_Logs_Table();
			$logs_table->prepare_items();
			$url=admin_url('admin.php');
			?><div class="wrap">
			<?php if(isset($_REQUEST['logs'])){if($_GET['logs']=='success'){ ?>
				<p>All Logs have been deleted successfully.</p>
			<?php }}?>
				<button class="flush" id="flush" onclick="javascript:flush_logs();" >Flush Logs</button>
	      		</div>
				<div class="wrap">
					<h2>All Sendle Logs</h2>
					<?php $logs_table->display(); ?>
				</div>
        		<script>
				function flush_logs() {
					if(confirm('Are you sure you want to delete all sendle logs?')){
						window.location.href = '<?php echo $url."?page=sendle_logs&logs=flush"; ?>';
					}
				}
				</script>
			<?php
		}
	}
	if(!class_exists('WP_List_Table')){
		require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');
	}
	class Sendle_Logs_Table extends WP_List_Table {
		function __construct(){
			parent::__construct(array('ajax'=>false));       //does this table support ajax?
		}

		function get_columns(){
			$columns = array(
			'id'=>'ID',
			//'eventname'=>'Event Name',
			//'orderid'=>'Order Id',
			'logs'=>'Logs',
			'timestamp'=>'Time'
			);
			return $columns;
		}
		function column_default($item, $column_name){
			switch($column_name) {
				case 'id':
				//case 'eventname':
				//case 'orderid':
				case 'logs':
				case 'timestamp':

			  return $item[$column_name];
			default:
			  return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
			}
		}


		function prepare_items() {
			global $wpdb;
			$table_name = $wpdb->prefix."sendlelogs";
			$per_page = 50;
			$current_page = $this->get_pagenum();
			if(1 < $current_page){
				$offset = $per_page*($current_page-1);
			} else {
				$offset = 0;
			}

			$srcCon = "";

			$items = $wpdb->get_results( "SELECT id, logs, timestamp FROM $table_name WHERE 1=1 ".$srcCon." ".$wpdb->prepare("ORDER BY id DESC LIMIT %d OFFSET %d;", $per_page, $offset),ARRAY_A);
			$columns = $this->get_columns();
			$this->_column_headers = array($columns);
			$count = $wpdb->get_var("SELECT COUNT(id) FROM $table_name  WHERE 1=1 ".$srcCon." ");
			$this->items = $items;
			// Set the pagination
			$this->set_pagination_args(array('total_items'=>$count,'per_page'=>$per_page,'total_pages'=>ceil($count/$per_page)));
		}
	}
}

$Sendle_Loader_WP_List_Table = new Sendle_Loader_WP_List_Table();

?>
