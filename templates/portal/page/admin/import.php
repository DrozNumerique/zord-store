				<div align="center">
        			<form enctype="multipart/form-data" action="<?php echo $baseURL; ?>" method="post" id="import-form">   			
        				<input type="hidden" name="module" value="Admin"/>
        				<input type="hidden" name="action" value="import"/>
        				<input type="hidden" name="<?php echo ini_get("session.upload_progress.name"); ?>" value="import"/>
        				<input type="hidden" name="parameters" value='[]'/>
        				<label id="file-import" for="input-file-import" class="admin-input-file">
        					<span><?php echo $locale->tab->import->input; ?></span>
    	   					<input id="input-file-import" type="file" style="display:none;" name="file"/>
        				</label>
        				<label for="submit-file-import" class="admin-input-file-button">
        					<span id="label-import"><?php echo $locale->tab->import->submit; ?></span>
        					<span id="label-stop" style="display:none;"><?php echo $locale->tab->import->stop; ?></span>
    		                <input id="submit-file-import" type="submit" style="display:none;" disabled/>
        				</label>
        				<br/>
        				<br/>               	
         				<div style="width:300px;">
         					<table class="admin-table" style="margin:auto;">
         						<thead>
               						<tr>
                       					<th style="width:auto;">
                       						<span class="sort" data-column="0">
                       							<?php echo $locale->tab->context->name; ?>
                       							<i class="fa fa-sort fa-fw" title="<?php echo $locale->tab->publish->select; ?>"></i>
                       						</span>
                       					</th>
                       					<th style="width:30px;">
                       						<i class="fa fa-book fa-fw" title="<?php echo $locale->tab->publish->select; ?>"></i>
                       					</th>
                       				</tr>
         						</thead>
         						<tbody>
                                <?php foreach(Zord::getConfig('context') as $name => $config) { ?>
                                <?php   if ($user->hasRole('admin', $name)) { ?>
                                    <tr class="sort">
                                    	<td><?php echo $name; ?></td>
                                    	<td class="state" data-type="publish">
                      						<input name="<?php echo $name; ?>" data-empty="no" type="hidden" value="no"/>
                      						<i class="display fa hidden fa-fw"></i>
                                    	</td>
                                    </tr>
                   				<?php   } ?>
               					<?php } ?>
          						</tbody>
         					</table>
       					</div>
                    </form>
                    <div id="import-notify" style="display:none;">
                    	<div id="import-status">
                    		<div id="import-step" class="admin-message"></div>
                        	<img id="import-wait" src='/library/img/wait.gif' style="height:1em; display:none;"></img>
                    	</div>
                        <div class="admin-progress">
                        	<div id="import-progress" style="width:0%;"></div>
                        </div>    			
                       	<div id="import-report" class="admin-report" style="display:none;"></div>       
                   	</div>
               	</div>         	
