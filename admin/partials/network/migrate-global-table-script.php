<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018 Peter Putzer.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  ***
 *
 * @package mundschenk-at/avatar-privacy
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

use Avatar_Privacy\Data_Storage\Network_Options;

?>
<script type="text/javascript">
	(function($){
		var parent = $( '#<?php echo \esc_js( $this->network_options->get_name( Network_Options::USE_GLOBAL_TABLE ) ); ?>' ),
			migrateButton = $( '#<?php echo \esc_js( $this->network_options->get_name( Network_Options::MIGRATE_FROM_GLOBAL_TABLE ) ); ?>' );
		parent.change(function(){
			migrateButton.prop( 'disabled', this.checked );
		});
	})(jQuery);
</script>
<?php
