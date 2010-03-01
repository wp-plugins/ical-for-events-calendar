<?php
/*
 * Wordpress admin option menu for iCal for Events Calendar plugin.
 */

function ical_ec_option_menu_init()
{
	add_options_page(
		'iCal for Events Calendar Settings',
		'iCal for Events Calendar',
		'administrator', 'ical-ec-admin.php', 'ical_ec_option_menu');

	add_action('admin_init', 'ical_ec_register_settings');
}

function ical_ec_register_settings()
{
	register_setting('ical-ec-settings', 'ical-ec-history-length-months');
}
 
function ical_ec_option_menu()
{
	?>
	
	<div class="wrap">
		<h2>iCal for Events Calendar</h2>

		<form method="post" action="options.php">
			<?php settings_fields('ical-ec-settings'); ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">History Length in Months</th>
					<td>
						<input
							type="text"
							name="ical-ec-history-length-months"
							class="small-text"
							value="<?php echo get_option('ical-ec-history-length-months'); ?>"
							/>
						<span class="setting-description">
							The number of past months to include in the iCalendar file.
							Leave blank to include all past events.
						</span>
					</td>
				</tr>
			</table>
			
			<p class="submit">
				<input
					type="submit"
					class="button-primary"
					value="<?php _e('Save Changes') ?>"
					/>
			</p>

		</form>
	</div>


	<?php
}

function ical_ec_action_links($links)
{ 
	// Add a link to this plugin's settings page
	$settings_link = '<a href="options-general.php?page=ical-ec-admin.php">';
	$settings_link .= __('Settings') . '</a>';
	array_unshift($links, $settings_link);
	return $links;
}

?>