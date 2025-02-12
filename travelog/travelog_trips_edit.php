<?php

/*
	Plugin: Travelog
	Component: Trips editor
	Author: Shane Warner
	Author URI: http://www.sublimity.ca/

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

*/
		
		$tripID = $_GET['id'];
		
		// Process new stops if requested
		if(isset($_POST['addStopLocationID']) && $_POST['addStopLocationID'] != '') {
			$stop_id = $_POST['addStopLocationID'];
			$new_visit = $_POST['addStopVisit'];
			If($new_visit != 'yyyy/mm/dd hh:mm' && $new_visit != '') {
				$location = Travelog::get_location($stop_id);

				// Add a new visit if passed
				if($location->dates_visited == '') {
					$dates = array($new_visit);
				}else{
					$dates = explode(",",$location->dates_visited);
					if(!in_array($new_visit, $dates)) $dates = array_merge($dates, $new_visit);
				}
				if('' != $dates && count($dates) > 0) {
					arsort($dates); //sort the dates so the most recent is always at the end of the list		
					$location->dates_visited = implode(',', $dates);
				}else{
					$location->dates_visited = '';
				}
				
				// Make sure the location is associated with the trip
				if($location->trips == '') {
					$trips = array($tripID);
				}else{
					$trips = explode(",",$location->trips);
					if(!in_array($tripID, $trips))$trips = array_merge($trips, $tripID);
				}
				$query = "UPDATE " . DB_TABLE . " SET dates_visited = '$location->dates_visited', ";
				$query .= "trips = '". implode(',',$trips)."' ";
				$query .= "WHERE id = '$location->id'";
		
				if (FALSE !== $wpdb->query($query)) {
					$message = '<p>'.__('Stop added sucessfully', DOMAIN).'</p>';
				}
				else {
					$message = '<p>'.__('An error has occured and the location was not updated', DOMAIN).'</p>';
				}
				
			}
		}
		
		// Will be editing an existing location
		$trip = Travelog::get_trip($_GET['id']);
		$update_sql = '';
		// Update the start/end dates if they've changed
		if(isset($_POST['edit_start_date']) && $_POST['edit_start_date'] != $trip->start_date) {
			$update_sql .= "start_date = '".$_POST['edit_start_date']."', ";
			$trip->start_date = $_POST['edit_start_date'];
		}
		if(isset($_POST['edit_end_date']) && $_POST['edit_end_date'] != $trip->end_date) {
			$update_sql .= "end_date = '".$_POST['edit_end_date']."', ";
			$trip->end_date = $_POST['edit_end_date'];
		}
		if($update_sql != '') {
			$update_sql = 'UPDATE '.DB_TRIPS_TABLE.' SET '.substr($update_sql, 0, -2). ' WHERE id =\''.$trip->id."'";
			$wpdb->query($update_sql);
		}

		$action = 'edit';
		$todo = 'update';
		$stops_info = 'show';
		
		// Show Travelog Manager submeny
		Travelog::adminheader();

  ?>  	
		<style type="text/css">
			<!--
				.acBackground {
					border: 1px solid #666;
					border-top-style: none;
					margin: 0;
				}
				
				.acHighlight {
					text-decoration: underline;
				}
				
				.acNotSelected {
					background-color: #eee;
					padding: 3px 4px 3px 17px;
				}
				
				.acSelected {
					background-color: #9cf;
					padding: 3px 4px 3px 17px;
					cursor: pointer;
				}
			-->
			</style>
		
		<form method="post" name="editTripForm" id="editTripForm" action="edit.php?page=travelog.php&area=trips&action=<?= $todo?>">
			<div class="wrap">
        		<p style="float: right;margin-top: 2px;"><a href="options-general.php?page=travelog.php" >Edit Travelog Options</a> &raquo;&nbsp;&nbsp;&nbsp;&nbsp;</p>
                <h2><?php if('add' == $action) { echo __("Add Trip", DOMAIN); }else{ echo __("Edit Trip", DOMAIN); } ?></h2>
				<div style="float: right; margin-right: 20px; clear: right;">
					<?php echo Travelog::embed_map(array('width'=>400, 'height'=>400, 'trips'=>$trip->id, 'show_types'=>1)); ?>
				</div>
                <table cellpadding="3" cellspacing="3">
                    <tbody>
                         <?php if($action == 'edit') : ?>
							 <tr class="alternate">
								<td><label>ID#</label></td><td style="width: auto;"><?= $trip->id ?><input type="hidden" name="edit_id" id="edit_id" value="<?=$trip->id ?>" /></td>
							 </tr>
						<?php endif; ?>
						<tr>
							<td><label for="edit_name"><?=__('Name', DOMAIN)?></label></td><td><input type="text" name="edit_name" value="<?=$trip->name ?>" size="18" /></td>
                         </tr>
                         <tr class="alternate">
							<td><label for="edit_start_date"><?=__('Start Date', DOMAIN)?></label></td><td><input type="text" name="edit_start_date" id="edit_start_date" size="18" value="<?=$trip->start_date ?>" onfocus="if(this.value == 'yyyy/mm/dd hh:mm') this.value = '';"/></td>
                         </tr>
                         <tr>
							<td><label for="edit_end_date"><?=__('End Date', DOMAIN)?></label></td><td><input type="text" name="edit_end_date" id="edit_end_date" size="18" value="<?=$trip->end_date ?>" onfocus="if(this.value == 'yyyy/mm/dd hh:mm') this.value = '';"/></td>
                        </tr>
                        <tr>
							<td><label for="edit_end_date"><?=__('Total Distance', DOMAIN)?></label></td><td><?php echo $trip->get_distance();?>km</td>
                        </tr>
					</tbody>
				</table>
				<fieldset name="stops_info" style="width: 33%;margin: 5px 0;padding-bottom:5px;">
					<?php if(count($trip->stops) == 1) $message = 'Stop'; else $message = 'Stops';?>
					<legend><strong><?= count($trip->stops) ?> <?=__($message, DOMAIN)?>:</strong> (<a href="javascript:void(0)" onclick="var obj = document.getElementById('stops_list');if(this.innerHTML.substr(0,4)=='show'){this.innerHTML='hide'; obj.style.display = 'block';}else{this.innerHTML='show'; obj.style.display = 'none';}"><?php if($stops_info =='show') {echo 'hide';}else{echo 'show';} ?></a>)</legend>
					<div id="stops_list" style="display: <?php if($stops_info =='show') {echo 'block';}else{echo 'none';} ?>;">
						<ol>
							<?php 
								if(count($trip->stops) > 0) {
									foreach($trip->stops as $order => $stop) {
										echo "<li><a href='edit.php?page=travelog.php&amp;area=locations&amp;action=edit&amp;id=".$stop['location_id']."' title='Edit this location'>".$stop['name']."</a> - ".$stop['date']."</li>";
									}
								}else{
									echo "There are no stops on this trip";
								}
							 ?>
						</ol>
						<form id="addStopForm" method="post" action="edit.php?page=travelog.php&area=trips&action=edit&id=<?=$trip->id ?>">
							<fieldset style="margin-bottom:5px;padding-bottom:5px;">
							<legend><strong>Add Stop:</strong> (<a href="javascript:void(0)" onclick="var obj = document.getElementById('addStop');if(this.innerHTML.substr(0,4)=='show'){this.innerHTML='hide'; obj.style.display = 'block';}else{this.innerHTML='show'; obj.style.display = 'none';}">show</a>)</legend>
								<div id="addStop" style="display:none;">Name: <input type="text" name="addStopLocationName" id="addStopLocationName" size="15" value="" /><input type="hidden" name="addStopLocationID" id="addStopLocationID" value="" /> &nbsp;<input type="button" id="editStopButton" title="Edit Location" value="Edit &raquo;" style="display:none;" onclick="window.location='edit.php?page=travelog.php&area=locations&action=edit&id='+document.getElementById('addStopLocationID').value;"/>
									<div id="addStopOptions" style="display:none;margin-bottom:0;">
										<small id="addStopMessage"></small>
										<div id="addVisitOptions" style="margin-top: 10px;">New Visit: <input type="text" name="addStopVisit" id="addStopVisit" size="18" value="yyyy/mm/dd hh:mm" /> <input type="button" onclick="addStop();" value="Add Stop &raquo;" /></div>
									</div>
								</div>
								<script type="text/javascript">
									var stopAdder = new TravelogDataForm('stopAdder', 'addStopForm', null, '', '', 'autocomplete', '', 'addStopLocationName', '', '', '');
									stopAdder.ac.action = 'locationSelected';
									
									function locationSelected(locationID) {
										// search for qualifying visits
										var tripID = document.getElementById('edit_id').value;
										var tripStart = document.getElementById('edit_start_date').value;
										var tripEnd = document.getElementById('edit_end_date').value;
										var messageHolder = document.getElementById('addStopMessage');
										document.getElementById('addStopLocationName').value = tLocations[locationID].name;
										document.getElementById('addStopLocationID').value = locationID;
										var okVisits = new Array();
										
										document.getElementById('editStopButton').style.display = 'inline';
										
										for(visitKey in tLocations[locationID].visits) {
											var visit = tLocations[locationID].visits[visitKey];
											var visitDateTime = visit.date + ' ' + visit.time;
											if(visitDateTime > tripStart && visitDateTime < tripEnd) {
												okVisits[okVisits.length] = visitDateTime;
											}
										}
										
										var locationTrips = tLocations[locationID].trips.split(',');
										var alreadyInTrip = false;
										for(i=0;i<locationTrips.length;i++) {
											if(locationTrips[i] === tripID) alreadyInTrip = true;
										}
										
										if(okVisits.length > 0) {
											var visitViewerCode = '';
											for(i=0;i<okVisits.length;i++) {
												visitViewerCode += okVisits[i]+', ';
											}
											visitViewerCode = visitViewerCode.substring(0, visitViewerCode.length-2);
											
											if(alreadyInTrip) {
												message = 'This location has <span title="'+visitViewerCode+'" style="font-weight:bold;cursor:help;">'+ okVisits.length +'</span> visit';
												if(okVisits.length > 1) message += 's';
												message += ' already in this trip.<br/>To add it again as another stop, add a new visit:';
											}else{
												message = 'This location has <span title="'+visitViewerCode+'" style="font-weight:bold;cursor:help;">'+ okVisits.length +'</span> visit';
												if(okVisits.length > 1) message += 's';
												message += ' during the trip period.<br/><a>';
												if(okVisits.length == 1) {
													message += 'Make it a trip stop';
												}else{
													message += 'Make these trip stops';
												}
												message += '</a> or add a new visit:';
											}	
											messageHolder.innerHTML = message;
										}else{
											if(alreadyInTrip) {
												messageHolder.innerHTML = 'This location is associated with this trip but has no visits during the trip period. Please add a visit:';
											}else{
												messageHolder.innerHTML = 'This location has no visits during the trip period.<br/>Change the trip start/end dates or add a visit:';
											}
										}
										setDisp('addStopOptions','block');
									}
									
									function addStop() {
										var tripID = document.getElementById('edit_id').value;
										var tripStart = document.getElementById('edit_start_date').value;
										var tripEnd = document.getElementById('edit_end_date').value;
										var newVisit = document.getElementById('addStopVisit');
										var locationID = document.getElementById('addStopLocationID').value;
										
										if(newVisit.value != '' && newVisit.value != 'yyyy/mm/dd hh:mm') {
											if(newVisit.value >= tripStart && newVisit.value <= tripEnd) {
												var formElem = document.getElementById('editTripForm'); // Submit form
												formElem.action = 'edit.php?page=travelog.php&area=trips&action=edit&id='+tripID;
												formElem.submit();
											}else{
												alert('The visit date/time you entered is not during the trip period. Check the stop date/time or change the trip start/end dates.');
												return false;
											}
										}else{
											alert('Please enter a valid date/time for the new stop.');
											return false;
										}
									}
								</script>
							</fieldset>
						</form>
					</div>
				</fieldset>
				<table>
					<tbody>
						<tr>
							<td colspan="2"><label for="edit_description"><?=__('Description', DOMAIN)?></label><br />
							<textarea rows="6" cols="50" name="edit_description" id="content"><?=$trip->description ?></textarea></td>
						</tr>
						<tr>
							<td colspan="2" class="submit"><input type="submit" value="<?php if('add' == $action) { echo __("Add Trip", DOMAIN); }else{ echo __("Edit Trip", DOMAIN); } ?> &raquo;" onclick="document.forms[0].submit();" /> <input type="button" value="<?=__("Cancel", DOMAIN)?>" onclick="window.location='edit.php?page=travelog.php&area=trips'" /></td>
						</tr>
					</tbody>
                </table>
                <div style="clear: both;"></div>
			</div>
		</form>