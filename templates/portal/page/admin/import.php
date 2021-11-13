				<div align="center">
        			<form enctype="multipart/form-data" action="<?php echo $baseURL; ?>" method="post" id="import-form">   			
        				<input type="hidden" name="module" value="Admin"/>
        				<input type="hidden" name="action" value="import"/>
<?php $this->render('file'); ?>
                    </form>
                    <div id="import-notify" style="display:none;">
                    	<div id="import-status">
                    		<div id="import-step" class="admin-message"></div>
                        	<img id="import-wait" src='/img/wait.gif' style="height:1em; display:none;"></img>
                    	</div>
                        <div class="admin-progress">
                        	<div id="import-progress" style="width:0%;"></div>
                        </div>    			
                       	<div id="import-report" class="admin-report" style="display:none;" data-over="false"></div>       
                   	</div>
               	</div>         	
