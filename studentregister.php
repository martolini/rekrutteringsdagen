<?php
/*
Plugin Name: Interesseregister
Plugin URI: 
Description: Interesseregister for rekrutteringsdagen
Version: 1.0
Author: Martin Skow Røed
Author URI: 
License: 
License URI: 
*/

function exclude_role_from_page_with_notice($role) {
	if (!is_user_logged_in()) {
		echo "<p>Du har ikke rettigheter til å se dette.</p>";
		return true;
	}
	if (in_array($role, wp_get_current_user()->roles)) {
		echo "<p>Du har ikke rettigheter til å se dette.</p>";
		return true;
	}
	return false;
}

$studentregister = new StudentRegister();

class StudentRegister {

	private $linjer;

	public function __construct() {		
		add_role('student', __('Student'), array('read' => 'true'));
		add_role('bedrift', __('Bedrift'), array('read' => 'true'));

	// Forskjellige linjer

		$this->linjer = array(
			'Kybernetikk',
			'Elektronikk',
			'Datateknikk',
			'Komtek',
			'Indøk',
			'Bygg',
			'EMIL',
			);


	// Adding filters and actions
		add_filter('user_contactmethods', array(&$this, 'modify_contact_methods') );


		$this->add_field_to_profile('header');

		$this->add_field_to_profile('birthday');
		$this->add_field_to_profile('tlf');
		$this->add_field_to_profile('linje');
		$this->add_field_to_profile('year');
		$this->add_field_to_profile('cv');

		$this->add_field_to_profile('footer');

		$this->save_field_to_profile('birthday');
		$this->save_field_to_profile('tlf');
		$this->save_field_to_profile('linje');
		$this->save_field_to_profile('year');
		$this->save_field_to_profile('cv');

	// Adding shortcodes
		add_shortcode('vis_alle_studenter', array(&$this, 'display_all_students'));
		add_shortcode('vis_student', array(&$this, 'display_student'));

	}
	
	public function modify_contact_methods($profile_fields) {
		unset($profile_fields['aim']);
		unset($profile_fields['jabber']);
		unset($profile_fields['yim']);
		return $profile_fields;
	}

	public function add_year_to_profile($user) {
		?>
		<tr>
			<th><label for="year">År</label></th>
			<td><select name="year">
				<?php
				$year = esc_attr(get_the_author_meta('year', $user->ID) ); 
				if (!$year)
					$year = "1";
				for ($i = 1; $i<=5; $i++) {
					echo '<option value="' . $i . '" ';
					if ($i == $year)
						echo 'selected="selected"';
					echo '>' . $i . '</option>';
				} ?>
			</select></td>
		</tr>
		<?php
	}

	public function save_year_to_profile($user_id) {
		if (!current_user_can('edit_user', $user_id)) {
			return false;
		}
		update_user_meta($user_id, 'year', $_POST['year']);
	}

	public function add_linje_to_profile($user) {
		?>
		<tr>
			<th><label for="linje">Linje</label></th>
			<td><select name="linje">
				<?php
				$linje = esc_attr(get_the_author_meta('linje', $user->ID) );
				if (!$linje)
					$linje = "";
				foreach ($this->linjer as $l) {
					echo '<option value="' . $l . '" ';
					if ($l == $linje)
						echo 'selected="selected"';
					echo '>' . $l . '</option>';
				} ?>
			</select></td>

		</tr>
		<?php
	}

	public function save_linje_to_profile($user_id) {
		if (!current_user_can('edit_user', $user_id)) {
			return false;
		}
		update_user_meta($user_id, 'linje', $_POST['linje']);
	}

	public function add_tlf_to_profile($user) {
		?>
		<tr>
			<th><label for="tlf">Telefon</label></th>
			<td><input type="text" name="tlf" id="tlf" value="<?php echo esc_attr(get_the_author_meta('tlf', $user->ID) ); ?>" /></td>
		</tr>
		<?php
	}

	public function save_tlf_to_profile($user_id) {
		if (!current_user_can('edit_user', $user_id))
			return false;
		update_user_meta($user_id, 'tlf', $_POST['tlf']);
	}

	public function add_header_to_profile() {
		?>
		<h3><?php _e('Interesseregister', 'frontendprofile'); ?></h3>
		<table class="form-table">
		<?php 
	}

	public function add_field_to_profile($field) {
		add_action( 'show_user_profile', array(&$this, "add_" . $field . "_to_profile") );
		add_action( 'edit_user_profile', array(&$this, "add_" . $field . "_to_profile") );
	}

	public function save_field_to_profile($field) {
		add_action( 'personal_options_update', array(&$this, "save_" . $field . "_to_profile") );
		add_action( 'edit_user_profile_update', array(&$this, "save_" . $field . "_to_profile") );
	}


	public function add_footer_to_profile() {
		echo '</table>';
	}

	public function add_birthday_to_profile($user) {
		if (in_array('bedrift', $user->roles))
			return false;
		?>
		<tr>
			<th><label for="datepicker">Fødselsdato</label></th>
			<td><input type="date" id="datepicker" name="datepicker" value="<?php echo esc_attr(get_the_author_meta('birthday', $user->ID) ); ?>" class="datepicker" /></td>
		</tr>
		<?php 
	} 

	public function add_cv_to_profile($user) {
		?>
		<script type="text/javascript">
		var form = document.getElementById('your-profile');
		form.encoding = "multipart/form-data";
		form.setAttribute('enctype', 'multipart/form-data');
		</script>
		<tr>
			<th><label for="cv">Last opp CV</label></th>
			<td>
				<p>
					<input type="file" name="cv" id="cv" />
					<input type="hidden" name="action" value="save">
					<input type="submit" name="submitcv" id="submitcv" class="button" value="Last opp"> 
				</p>
				<span class="description">
					<?php
					$user_cv = $this->get_user_cv($user->ID);
					if(is_array($user_cv)):
						?>
					<a href="<?php echo $user_cv["url"];?>" target="_blank">Din CV</a>
					<?php
					endif;
					?>
				</span>
			</td>
		</tr>

		<?php 
	}

	public function get_user_cv($user_ID) {

		$author_data = get_the_author_meta( 'cv', $user_ID );
		if (!$author_data)
			return false;
		$uploads = wp_upload_dir();
		$author_data["file"] = $uploads["baseurl"] . $author_data["file"];
		return $author_data;
	}

	public function save_birthday_to_profile($user_id) {
		if (!current_user_can( 'edit_user', $user_id))
			return false;
		if (isset($_POST['datepicker']))
			update_user_meta($user_id, 'birthday', $_POST['datepicker']);
	}

	public function save_cv_to_profile( $user_id ) {

		if ( !current_user_can( 'edit_user', $user_id ) )
			return false;

		$upload=$_FILES['cv'];
		$uploads = wp_upload_dir();

		if(isset($_POST) && isset($_POST['submitcv'])) { 
			if ($upload['tmp_name']) {
				$allowed_types = array('application/pdf');

				if (!in_array($upload['type'], $allowed_types) || $upload['size'] > 10485760) {
					wp_die('Det er kun lov til å laste opp PDFer under 10mb.');
					header('Location: http://www.rekrutteringsdagen.no/profil');
				}

			// handle the uploaded file
				$overrides = array('test_form' => false);
				if ( ! function_exists( 'wp_handle_upload' ) ) require_once( ABSPATH . 'wp-admin/includes/file.php' );
				$file=wp_handle_upload($upload, $overrides);
				$file["file"] = $uploads["subdir"]."/".basename($file["url"]);

				if( $file ) {
	 				//remove previous uploaded 
					$user_data = get_the_author_meta( 'cv', $user_id );
					$author_data["file"] = $uploads["path"] . $author_data["file"];
					$cvpath = $author_data["file"];
					@unlink($cvpath);
					update_user_meta( $user_id, 'cv', $file );
				} 
			}
		}
	}

	public function display_all_students() {
		$args = array(
			'role' => 'student');
		$user_query = new WP_User_Query( $args );
		$fields = array(
			"Navn" => $user->first_name . " " . $user->last_name,
			"Email" => $user->user_email, 
			"Linje" => esc_attr(get_the_author_meta('linje', $user->ID) ),
			"År" => get_the_author_meta('year', $user->ID),
			"CV" => $this->get_user_cv($user->ID));
		if ( ! empty( $user_query->results ) ) {
			echo '<table><tr>';
			foreach ($fields as $field => $value) {
				echo "<th>$field</th>";
			}
			echo '</tr>';
			foreach ( $user_query->results as $user ) {
				$fields = array(
					"Navn" => $user->first_name . " " . $user->last_name,
					"Email" => $user->user_email, 
					"Linje" => esc_attr(get_the_author_meta('linje', $user->ID) ),
					"År" => get_the_author_meta('year', $user->ID),
					"CV" => $this->get_user_cv($user->ID));
				echo "<tr>";
				foreach ($fields as $field => $value) {
					if (strcmp($field, "CV") == 0) {
						if (is_array($value))
							echo '<td><a href="' . $value['url'] . '" target="_blank">Klikk her</a></td>';
						else
							echo '<td>Har ingen</td>';
					}
					else
						echo "<td>$value</td>";
				}
				echo "</tr>";
				//echo '<tr><td><a href="/student' . "?id=$user->ID". '">'  . $user->display_name . '</a></td><td>' . esc_attr(get_the_author_meta('linje', $user->ID) ) . '</td></tr>';
			}
			echo '</table>';
		} 
		else {
			echo 'Fant ingen brukere.';
		}
	}

	public function display_student() {
		if (exclude_role_from_page_with_notice('student'))
			return;
		$user_id = $_GET['id'];
		$user = get_userdata($user_id);
		if ($user) {
			$user_cv = $this->get_user_cv($user->ID);
			?>
			<table>
				<tr>
					<th>Navn</th>
					<td><?php echo $user->first_name . " " . $user->last_name; ?></td>
				</tr>
				<tr>
					<th>Email</th>
					<td><?php echo $user->user_email; ?></td>
				</tr>
				<tr>
					<th>Linje</th>
					<td><?php echo esc_attr(get_the_author_meta('linje', $user->ID)); ?></td>
				</tr>
				<tr>
					<th>År</th>
					<td><?php echo esc_attr(get_the_author_meta('year', $user->ID)); ?></td>
				</tr>
				<tr>
					<th>Info</th>
					<td><?php echo $user->description; ?></td>
				</tr>
				<tr>
					<th>CV</th>
					<td><?php if (is_array($user_cv)) echo '<a href="' . $user_cv['url'] . '" target="_blank">Klikk her</a>'; ?></td>
				</tr>
			</table>
		<?php
		}
	}
}

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Studentregister_List_Table extends WP_List_Table {

	function __construct() {
		add_shortcode('vis_studenter', array(&$this, 'my_render_list_page'));
	}

	function get_columns() {
		$columns = array(
			"fornavn" => "Fornavn",
			"etternavn" => "Etternavn",
			"email" => "Email",
			"linje" => "Linje",
			"year" => "År",
			"cv" => "CV",
			"sommerjobb" => "Sommerjobb",
			"fastjobb" => "Fastjobb",
			);
		return $columns;
	}

	function prepare_items() {
		global $studentregister;

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->items = array();

		$this->_column_headers = array($columns, $hidden, $sortable);

		$args = array(
			'role' => 'student');
		$user_query = new WP_User_Query( $args );
		if ( ! empty( $user_query->results ) ) {
			foreach ($user_query->results as $user) {
				$arr = array();
				$arr['userid'] = $user->ID;
				$arr['fornavn'] = $user->first_name;
				$arr['etternavn'] = $user->last_name;
				$arr['email'] = '<a href="mailto:' . $user->user_email . '?Subject=Rekrutteringsdagen" target="_top">' . $user->user_email . '</a>';
				$arr['linje'] = esc_attr(get_the_author_meta('linje', $user->ID));
				$arr['year'] = esc_attr(get_the_author_meta('year', $user->ID));
				$user_cv = $studentregister->get_user_cv($user->ID);
				$sommerjobber = get_user_meta($user->ID, 'sommerjobb', true);
				$sommerjobb = false;
				$fastjobber = get_user_meta($user->ID, 'fastjobb', true);
				$fastjobb = false;
				if (is_array($somerjobber)) {
					if (in_array(get_current_user_id(), $sommerjobber)) {
						$sommerjobb = true;
					}
				}
				if (is_array($fastjobber)) {
					if (in_array(get_current_user_id(), $fastjobber)) {
						$fastjobb = true;
					}
				}
				$arr['sommerjobb'] = $sommerjobb;
				$arr['fastjobb'] = $fastjobb;

				if (is_array($user_cv))
					$arr['cv'] = '<a href="' . $user_cv['url'] . '" target="_blank">Klikk her</a>';
				else
					$arr['cv'] = 'Finnes ikke';
				if (isset($_REQUEST['search'])) {
					$needle = strtolower($_REQUEST['search']);
					$new_items = array();
					if (!(strpos(strtolower($arr['fornavn']),$needle) === false && strpos(strtolower($arr['etternavn']), $needle) === false && strpos(strtolower($arr['linje']), $needle) === false)) {
						array_push($this->items, $arr);
					}
				}
				else {
					array_push($this->items, $arr);
				}
				//array_push($this->items, $arr);
			}
		}
		usort($this->items, array(&$this, 'usort_reorder'));
	}

	function column_default( $item, $column_name ) {
		switch($column_name) {
			case 'fornavn':
				return '<a href="/vis-student?id=' . $item['userid'] . '">' . $item['fornavn'] . "</a>";
			case 'etternavn':
			case 'linje':
			case 'email':
			case 'year':
			case 'cv':
				return $item[$column_name];
			case 'sommerjobb':
			case 'fastjobb':
				if ($item[$column_name])
					return 'X';
				return '';
			default:
				return print_r($item, true);
		}
	}

	function get_sortable_columns() {
		$sortable_columns = array(
			'fornavn' => array('fornavn', false),
			'etternavn' => array('etternavn', false),
			'linje' => array('linje', false),
			'year' => array('year', false),
			);
		return $sortable_columns;
	}

	function usort_reorder($a, $b) {
		$orderby = (! empty($_GET['orderby']) ) ? $_GET['orderby'] : 'etternavn';
		$order = (! empty($_GET['order']) ) ? $_GET['order'] : 'asc';

		$result = strcmp($a[$orderby], $b[$orderby]);
		return ($order == 'asc') ? $result : -$result;
	}

	function my_render_list_page() {
		if (exclude_role_from_page_with_notice('student'))
			return;
		$thelist = new Studentregister_List_Table();
		$thelist->prepare_items();
		?>
			<form method="POST" action="<?php echo $_REQUEST['page'] ?>">
  				<p class="search-box">
					<label class="screen-reader-text" for="search_id-search-input">
					Søk:</label> 
					<input id="search_id-search-input" type="text" name="search" value="<?php echo isset($_REQUEST['search']) ? $_REQUEST['search'] : ''; ?>" /> 
					<input id="search-submit" class="button" type="submit" name="" value="Søk" />
				</p>
			</form>
		<?php
		$thelist->display();
	}

	function no_items() {
		_e('Fant ingen studenter.');
	}
}

$list_table = new Studentregister_List_Table();

class ShowCompanies {
	private $companies;

	public function __construct() {
		$this->companies = array();
		$args = array(
			'role' => 'bedrift',
			'orderby' => 'registered');
		$user_query = new WP_User_Query( $args );
		if ( ! empty( $user_query->results ) ) {
			foreach ( $user_query->results as $company ) {
				$this->companies[$company->ID] = $company->first_name;
			}
		}

		add_shortcode('velg_bedrifter', array(&$this, 'show_companies'));
	}

	public function show_companies() {
		if (exclude_role_from_page_with_notice('bedrift'))
			return;
		if (!empty($_POST['submit'])) {
			$user = wp_get_current_user();
			update_user_meta($user->ID, 'sommerjobb', $_POST['sommerjobb']);
			update_user_meta($user->ID, 'fastjobb', $_POST['fastjobb']);
		}
		?>
		<p>Huk av de bedriftene hvor du ønsker sommerjobb (S) og fast jobb etter endt utdannelse (F).</p>
		<div>
		<form method="POST" action="<?php echo $_REQUEST['page'] ?>">
			<table>
				<tr>
					<th>S</th>
					<th>F</th>
					<th>Bedrift</th>
				</tr>
			<?php
			$sommerjobb = get_user_meta(get_current_user_id(), 'sommerjobb', true);
			$fastjobb = get_user_meta(get_current_user_id(), 'fastjobb', true);
			foreach ($this->companies as $key => $company) {
				echo "<tr><td>";
				echo '<input type="checkbox" name="sommerjobb[]" value="' . $key . '" ';
				if (in_array($key, $sommerjobb))
					echo "checked";
				echo ">";
				echo "</td><td>";
				echo '<input type="checkbox" name="fastjobb[]" value="' . $key . '" ';
				if (in_array($key, $fastjobb))
					echo "checked";
				echo ">";
				echo "</td><td>";
				echo $company . "</td></tr>";
			}
		?>
			</table>
			<input type="submit" name="submit" value="Velg">
		</form>
	</div>
		<?php

	}
}

$velg_bedrift = new ShowCompanies();
?>