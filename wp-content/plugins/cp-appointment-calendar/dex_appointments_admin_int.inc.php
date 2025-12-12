<?php

if ( !is_admin() ) 
{
    echo 'Direct access not allowed.';
    exit;
}

?>
<div class="wrap">
<h1>CP Appointment Calendar</h1>



<div style="border:1px solid #664444;background-color:#FDFDC8;width:90%;padding-left:10px;padding-right:10px;font-size:12px;">
    <p><strong>Important note:</strong><br />
        <br /> There are two more advanced plugins based in this one that contains <strong>all the features present in this plugin plus a large additional set of features</strong>.
        
 <div id="metabox_basic_settings" class="postbox" >
  <h3 class='hndle' style="padding:5px;"><span>We recommend to move to one of the following plugins:</span></h3>
  <div class="inside"> 
   
  
    
    <div class="plugin-card plugin-card-appointment-hour-booking">
       <div class="plugin-card-top">
				<div class="name column-name">
					<h3>
						<a href="plugin-install.php?tab=plugin-information&amp;plugin=appointment-hour-booking&amp;" class="thickbox open-plugin-details-modal">
						Appointment Hour Booking						<img src="https://ps.w.org/appointment-hour-booking/assets/icon-256x256.png" class="plugin-icon" alt="">
						</a>
					</h3>
				</div>
				<div class="action-links">
					<ul class="plugin-action-buttons"><ul class="plugin-action-buttons">
                    <?php if (!is_plugin_active('appointment-hour-booking/app-booking-plugin.php')) { ?>
                      <li><a class="install-now button" data-slug="appointment-hour-booking" href="<?php echo wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=appointment-hour-booking'), 'install-plugin_appointment-hour-booking'); ?>" aria-label="Install Appointment Hour Booking" data-name="Appointment Hour Booking">Install Now</a></li>
                    <?php } else { ?>
                      <li><a class="install-now button" data-slug="appointment-hour-booking" href="admin.php?page=cp_apphourbooking" aria-label=" Appointment Hour Booking" data-name="Appointment Hour Booking">Settings</a></li>
                    <?php } ?>
                    <li><a href="plugin-install.php?tab=plugin-information&amp;plugin=appointment-hour-booking" class="thickbox open-plugin-details-modal" aria-label="More information about Appointment Hour Booking" data-title="Appointment Hour Booking">More Details</a></li></ul>				</div>
				<div class="desc column-description">
					<p>Booking forms for appointments/services with a start time and a defined duration over a schedule. The start time is visually selected by the end user from a set of start times (based in "open" hours and service duration).</p>
					
				</div>
			</div>
    </div>  
    
   <div class="plugin-card plugin-card-appointment-booking-calendar">
       <div class="plugin-card-top">
				<div class="name column-name">
					<h3>
						<a href="plugin-install.php?tab=plugin-information&amp;plugin=appointment-booking-calendar&amp;" class="thickbox open-plugin-details-modal">
						Appointment Booking Calendar						<img src="https://ps.w.org/appointment-booking-calendar/assets/icon-256x256.png" class="plugin-icon" alt="">
						</a>
					</h3>
				</div>
				<div class="action-links">
					<ul class="plugin-action-buttons"><ul class="plugin-action-buttons">
                    <?php if (!is_plugin_active('appointment-booking-calendar/cpabc_appointments.php')) { ?>
                     <li><a class="install-now button" data-slug="appointment-booking-calendar" href="<?php echo wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=appointment-booking-calendar'), 'install-plugin_appointment-booking-calendar'); ?>" aria-label="Install  Appointment Booking Calendar" data-name="Appointment Booking Calendar">Install Now</a></li>
                    <?php } else { ?>
                     <li><a class="install-now button" data-slug="appointment-booking-calendar" href="" aria-label="  Appointment Booking Calendar" data-name="Appointment Booking Calendar">Settings</a></li>
                    <?php } ?>
                    <li><a href="plugin-install.php?tab=plugin-information&amp;plugin=appointment-booking-calendar" class="thickbox open-plugin-details-modal" aria-label="More information about Appointment Booking Calendar" data-title="Appointment Booking Calendar">More Details</a></li></ul>				</div>
				<div class="desc column-description">
					<p>Appointment booking calendar for booking time-slots into dates from a set of available time-slots in a calendar. Includes PayPal payments integration for processing the bookings.</p>
					
				</div>
			</div>
    </div>  
    
    <div style="clear:both"></div>    

  </div>    
 </div>

</div>

<br />
 <div id="metabox_basic_settings" class="postbox" >
  <h3 class='hndle' style="padding:5px;"><span>Shortcode for publishing the booking form:</span></h3>
  <div class="inside"> 
   To publish the booking form of this plugin add the following shortocode into a post or page:
      
   <br /><textarea readonly  rows="1" cols="80" style="font-weight:bold">[APPOINTMENT_CALENDAR_FORM_WILL_APPEAR_HERE]</textarea> 
  </div> 
 </div> 
 
<form method="post" action="options.php"> 

<?php settings_fields( 'dex-appointments-group' ); ?>

<?php do_settings_sections( 'dex-appointments-group' ); ?>

   
<div id="normal-sortables" class="meta-box-sortables">


 <div id="metabox_basic_settings" class="postbox" >
  <h3 class='hndle' style="padding:5px;"><span>Calendar Configuration / Administration</span></h3>
  <div class="inside">
  
   <link rel="stylesheet" type="text/css" href="<?php echo plugins_url('TDE_AppCalendar/all-css.css', __FILE__); ?>" />
   <link rel="stylesheet" type="text/css" href="<?php echo plugins_url('TDE_AppCalendar/simpleeditor.css', __FILE__); ?>" />
   <link rel="stylesheet" type="text/css" href="<?php echo plugins_url('TDE_AppCalendar/tabview.css', __FILE__); ?>" />
   <script>
   var pathCalendar = "<?php echo cp_appointment_get_site_url(true); ?>";
   </script>
   <script type="text/javascript" src="<?php echo plugins_url('TDE_AppCalendar/all-scripts.js', __FILE__); ?>"></script>
   <script type="text/javascript" language="JavaScript" src="<?php echo plugins_url('TDE_AppCalendar/tabview.js', __FILE__); ?>"></script>
   <script type="text/javascript" language="JavaScript" src="<?php echo plugins_url('TDE_AppCalendar/simpleeditor-beta-min.js', __FILE__); ?>"></script>
   
   <script>initAppCalendar("cal<?php echo TDE_APP_DEFAULT_CALENDAR_ID; ?>","3","1","<?php echo TDE_APP_DEFAULT_CALENDAR_LANGUAGE; ?>",{m1:"Please, select your appointment."});</script>
   
   <div style="padding:10px"><div id="caladmin"><div id="cal<?php echo TDE_APP_DEFAULT_CALENDAR_ID; ?>Container"></div></div></div>
   <div style="clear:both;height:20px" ></div>
   <div id="demo" class="yui-navset" style="padding-left:10px;width:690px;"></div>
   <div style="clear:both;height:20px" ></div>      
   
  </div>    
 </div> 
 
 <div id="metabox_basic_settings" class="postbox" >
  <h3 class='hndle' style="padding:5px;"><span>Public Calendar Settings</span></h3>
  <div class="inside"> 
   
   <table class="form-table">
        <tr valign="top">        
        <th scope="row">Calendar language</th>
        <td>
             <?php $value = get_option('calendar_language',DEX_APPOINTMENTS_DEFAULT_CALENDAR_LANGUAGE); ?>
             <select name="calendar_language">               
               <option value="DE" <?php if ($value == 'DE') echo ' selected="selected"'; ?>>German</option>
               <option value="DU" <?php if ($value == 'DU') echo ' selected="selected"'; ?>>Dutch</option>
               <option value="EN" <?php if ($value == 'EN') echo ' selected="selected"'; ?>>English</option>
               <option value="FR" <?php if ($value == 'FR') echo ' selected="selected"'; ?>>French</option>
               <option value="IT" <?php if ($value == 'IT') echo ' selected="selected"'; ?>>Italian</option>
               <option value="JP" <?php if ($value == 'JP') echo ' selected="selected"'; ?>>Japanese</option>
               <option value="PT" <?php if ($value == 'PT') echo ' selected="selected"'; ?>>Portuguese</option>               
               <option value="SP" <?php if ($value == 'SP') echo ' selected="selected"'; ?>>Spanish</option>            
            </select>
        </td>
        </tr>
        
        <tr valign="top">        
        <th scope="row">Date format</th>
        <td>           
             <?php $value = get_option('calendar_dateformat',DEX_APPOINTMENTS_DEFAULT_CALENDAR_DATEFORMAT); ?>
             <select name="calendar_dateformat">               
               <option value="0" <?php if ($value == '0') echo ' selected="selected"'; ?>>mm/dd/yyyy</option>
               <option value="1" <?php if ($value == '1') echo ' selected="selected"'; ?>>dd/mm/yyyy</option>         
             </select>           
        </td>
        </tr>
        
        <tr valign="top">        
        <th scope="row">Calendar Pages</th>
        <td>           
             <?php $value = get_option('calendar_pages',DEX_APPOINTMENTS_DEFAULT_CALENDAR_PAGES); ?>
             <select name="calendar_pages">               
               <option value="1" <?php if ($value == '1') echo ' selected="selected"'; ?>>1</option>
               <option value="2" <?php if ($value == '2') echo ' selected="selected"'; ?>>2</option>         
               <option value="3" <?php if ($value == '3') echo ' selected="selected"'; ?>>3</option>         
               <option value="4" <?php if ($value == '4') echo ' selected="selected"'; ?>>4</option>         
               <option value="5" <?php if ($value == '5') echo ' selected="selected"'; ?>>5</option>         
               <option value="6" <?php if ($value == '6') echo ' selected="selected"'; ?>>6</option>         
               <option value="7" <?php if ($value == '7') echo ' selected="selected"'; ?>>7</option>         
               <option value="8" <?php if ($value == '8') echo ' selected="selected"'; ?>>8</option>         
               <option value="9" <?php if ($value == '9') echo ' selected="selected"'; ?>>9</option>         
               <option value="10" <?php if ($value == '10') echo ' selected="selected"'; ?>>10</option>         
               <option value="11" <?php if ($value == '11') echo ' selected="selected"'; ?>>11</option>         
               <option value="12" <?php if ($value == '12') echo ' selected="selected"'; ?>>12</option>         
             </select>           
        </td>
        </tr>        
        
        <tr valign="top">        
        <th scope="row">Military time</th>
        <td>
             <?php $value = get_option('calendar_militarytime',DEX_APPOINTMENTS_DEFAULT_CALENDAR_MILITARYTIME); ?>
             <select name="calendar_militarytime">               
               <option value="0" <?php if ($value == '0') echo ' selected="selected"'; ?>>No</option>
               <option value="1" <?php if ($value == '1') echo ' selected="selected"'; ?>>Yes</option>         
             </select>         
        </td>
        </tr>
        
        <tr valign="top">        
        <th scope="row">Start weekday</th>
        <td>
             <?php $value = get_option('calendar_weekday',DEX_APPOINTMENTS_DEFAULT_CALENDAR_WEEKDAY); ?>
             <select name="calendar_weekday">               
               <option value="0" <?php if ($value == '0') echo ' selected="selected"'; ?>>Sunday</option>
               <option value="1" <?php if ($value == '1') echo ' selected="selected"'; ?>>Monday</option>
               <option value="2" <?php if ($value == '2') echo ' selected="selected"'; ?>>Tuesday</option>         
               <option value="3" <?php if ($value == '3') echo ' selected="selected"'; ?>>Wednesday</option>         
               <option value="4" <?php if ($value == '4') echo ' selected="selected"'; ?>>Thursday</option>         
               <option value="5" <?php if ($value == '5') echo ' selected="selected"'; ?>>Friday</option>         
               <option value="6" <?php if ($value == '6') echo ' selected="selected"'; ?>>Saturday</option>
             </select>         
        </td>
        </tr>              
        
        <tr valign="top">        
        <th scope="row">Minimum  available date</th>
        <td><input type="text" name="calendar_mindate" size="40" value="<?php echo esc_attr(get_option('calendar_mindate',DEX_APPOINTMENTS_DEFAULT_CALENDAR_MINDATE)); ?>" /><br />
         <em style="font-size:11px;">Examples: 2012-10-25, today, today + 3 days</em>
        </td>
        </tr>
        
        <tr valign="top">        
        <th scope="row">Maximum available date</th>
        <td>
         <input type="text" name="calendar_maxdate" size="40" value="<?php echo esc_attr(get_option('calendar_maxdate',DEX_APPOINTMENTS_DEFAULT_CALENDAR_MAXDATE)); ?>" /><br />
         <em style="font-size:11px;">Examples: 2012-10-25, today, today + 3 days</em>
        </td>
        </tr>
        
   </table>   

  </div>    
 </div>

 <div id="metabox_basic_settings" class="postbox" >
  <h3 class='hndle' style="padding:5px;"><span>Paypal Payment Configuration</span></h3>
  <div class="inside">

    <table class="form-table">
        <tr valign="top">        
        <th scope="row">Enable Paypal Payments?</th>
        <td><input type="checkbox" name="enable_paypal" disabled size="40" value="1" <?php if (get_option('enable_paypal',DEX_APPOINTMENTS_DEFAULT_ENABLE_PAYPAL)) echo 'checked'; ?> /> The feature for working without PayPal is implemented/available in the <a href="https://wordpress.dwbooster.com/calendars/cp-appointment-calendar#download">pro version</a>.</em>
        </td>
        </tr>    
    
        <tr valign="top">        
        <th scope="row">Paypal email</th>
        <td><input required type="email" name="paypal_email" size="40" value="<?php echo esc_attr(get_option('paypal_email',DEX_APPOINTMENTS_DEFAULT_PAYPAL_EMAIL)); ?>" /></td>
        </tr>
         
        <tr valign="top">
        <th scope="row">Request cost</th>
        <td><input type="text" name="request_cost" value="<?php echo esc_attr(get_option('request_cost',DEX_APPOINTMENTS_DEFAULT_COST)); ?>" /></td>
        </tr>
        
        
        <tr valign="top">
        <th scope="row">Paypal product name</th>
        <td><input type="text" name="paypal_product_name" size="50" value="<?php echo esc_attr(get_option('paypal_product_name',DEX_APPOINTMENTS_DEFAULT_PRODUCT_NAME)); ?>" /></td>
        </tr>        
        
        <tr valign="top">
        <th scope="row">Currency</th>
        <td><input type="text" name="currency" value="<?php echo esc_attr(get_option('currency',DEX_APPOINTMENTS_DEFAULT_CURRENCY)); ?>" /></td>
        </tr>
        
        <tr valign="top">
        <th scope="row">URL to return after successful  payment</th>
        <td><input type="text" name="url_ok" size="70" value="<?php echo esc_attr(get_option('url_ok',DEX_APPOINTMENTS_DEFAULT_OK_URL)); ?>" /></td>
        </tr>        
        
        <tr valign="top">
        <th scope="row">URL to return after an incomplete or cancelled payment</th>
        <td><input type="text" name="url_cancel" size="70" value="<?php echo esc_attr(get_option('url_cancel',DEX_APPOINTMENTS_DEFAULT_CANCEL_URL)); ?>" /></td>
        </tr>        
        
        
        <tr valign="top">
        <th scope="row">Paypal language</th>
        <td><input type="text" name="paypal_language" value="<?php echo esc_attr(get_option('paypal_language',DEX_APPOINTMENTS_DEFAULT_PAYPAL_LANGUAGE)); ?>" /></td>
        </tr>             
     </table>  

  </div>    
 </div>    
 
 
 <div id="metabox_basic_settings" class="postbox" >
  <h3 class='hndle' style="padding:5px;"><span>Notification Settings</span></h3>
  <div class="inside">
     <table class="form-table">    
        <tr valign="top">
        <th scope="row">Notification "from" email</th>
        <td><input required type="email" name="notification_from_email" size="40" value="<?php echo esc_attr(get_option('notification_from_email', DEX_APPOINTMENTS_DEFAULT_PAYPAL_EMAIL)); ?>" /></td>
        </tr>             
        <tr valign="top">
        <th scope="row">Send notification to email</th>
        <td><input required type="email" name="notification_destination_email" size="40" value="<?php echo esc_attr(get_option('notification_destination_email', DEX_APPOINTMENTS_DEFAULT_PAYPAL_EMAIL)); ?>" /></td>
        </tr>
        <tr valign="top">
        <th scope="row">Email subject confirmation to user</th>
        <td><input type="text" name="email_subject_confirmation_to_user" size="70" value="<?php echo esc_attr(get_option('email_subject_confirmation_to_user', DEX_APPOINTMENTS_DEFAULT_SUBJECT_CONFIRMATION_EMAIL)); ?>" /></td>
        </tr>
        <tr valign="top">
        <th scope="row">Email confirmation to user</th>
        <td><textarea cols="70" rows="5" name="email_confirmation_to_user"><?php echo esc_attr(get_option('email_confirmation_to_user', DEX_APPOINTMENTS_DEFAULT_CONFIRMATION_EMAIL)); ?></textarea></td>
        </tr>
        <tr valign="top">
        <th scope="row">Email subject notification to admin</th>
        <td><input type="text" name="email_subject_notification_to_admin" size="70" value="<?php echo esc_attr(get_option('email_subject_notification_to_admin', DEX_APPOINTMENTS_DEFAULT_SUBJECT_NOTIFICATION_EMAIL)); ?>" /></td>
        </tr>
        <tr valign="top">
        <th scope="row">Email notification to admin</th>
        <td><textarea cols="70" rows="5" name="email_notification_to_admin"><?php echo esc_attr(get_option('email_notification_to_admin', DEX_APPOINTMENTS_DEFAULT_NOTIFICATION_EMAIL)); ?></textarea></td>
        </tr>                                                
     </table>  
  </div>    
 </div>  
 
  
</div> 


        
    <?php submit_button(); ?>

[<a href="https://wordpress.dwbooster.com/contact-us" target="_blank">Request Custom Modifications</a>] | [<a href="https://wordpress.dwbooster.com/calendars/cp-appointment-calendar" target="_blank">Help</a>]
</form>
</div>















