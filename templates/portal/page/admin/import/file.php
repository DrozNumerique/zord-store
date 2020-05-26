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
<?php if (!defined('IMPORT_CONTINUE')) {?>
        				<label for="continue-import" class="admin-input-checkbox">
	        				<input id="continue-import" type="checkbox" name="continue" value="true"/>
	        				<span id="label-continue"><?php echo $locale->tab->import->continue; ?></span>
	        			</label>
<?php } ?>
